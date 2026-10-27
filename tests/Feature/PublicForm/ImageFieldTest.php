<?php

namespace Tests\Feature\PublicForm;

use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class ImageFieldTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    private function withPhotoField(array $world, bool $visibleToClient = true): void
    {
        $world['form']->fields()->create([
            'key' => 'foto_struk',
            'label' => 'Receipt photo',
            'type' => 'image',
            'required' => true,
            'visible_to_client' => $visibleToClient,
            'sort_order' => 99,
        ]);
    }

    public function test_photo_is_stored_privately_and_viewable_by_staff(): void
    {
        Storage::fake('local');
        $world = $this->world();
        $this->withPhotoField($world);

        $this->submitPublicEntry($world['location'], ['foto_struk' => UploadedFile::fake()->image('struk.jpg')])
            ->assertSessionHasNoErrors();

        $entry = FormSubmission::query()->sole();
        $path = $entry->answers['foto_struk'];
        Storage::disk('local')->assertExists($path);
        // Multipart posts must still store Yes/No answers as booleans.
        $this->assertTrue($entry->answers['kunjungi_poi']);
        $this->assertTrue($entry->qualified);

        $url = '/submissions/'.$entry->id.'/files/foto_struk';
        $this->actingAs($world['eo'])->get($url)->assertOk();
        $this->actingAs($world['userA'])->get($url)->assertOk();
        $this->actingAs($world['userB'])->get($url)->assertForbidden();
        $this->actingAs($world['eo'])->get('/submissions/'.$entry->id.'/files/nama')->assertNotFound();

        $this->actingAs($world['eo'])->get('/submissions/'.$entry->id)
            ->assertInertia(fn ($page) => $page->where('submission.answers.foto_struk', url($url)));
    }

    public function test_photo_is_converted_to_resized_webp(): void
    {
        Storage::fake('local');
        $world = $this->world();
        $this->withPhotoField($world);

        $this->submitPublicEntry($world['location'], ['foto_struk' => UploadedFile::fake()->image('struk.jpg', 4000, 3000)])
            ->assertSessionHasNoErrors();

        $path = FormSubmission::query()->sole()->answers['foto_struk'];
        $this->assertStringEndsWith('.webp', $path);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('local')->get($path));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame([1600, 1200], [$width, $height]);
    }

    public function test_excel_export_embeds_photo_thumbnails(): void
    {
        Storage::fake('local');
        $world = $this->world();
        $this->withPhotoField($world);
        $this->submitPublicEntry($world['location'], ['foto_struk' => UploadedFile::fake()->image('struk.jpg', 800, 600)]);
        $this->submitPublicEntry($world['location']->fresh(), ['foto_struk' => UploadedFile::fake()->image('dua.jpg', 800, 600)]);

        $response = $this->actingAs($world['eo'])->get('/submissions/export');
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $response->streamedContent());
        $sheet = IOFactory::load($file)->getActiveSheet();
        unlink($file);

        $this->assertSame('Receipt photo', $sheet->getCell('U1')->getValue());
        $this->assertCount(2, $sheet->getDrawingCollection());
        $this->assertNull($sheet->getCell('U2')->getValue());
        $this->assertSame('Receipt photo (link)', $sheet->getCell('V1')->getValue());
        $this->assertStringContainsString('/submissions/', $sheet->getCell('V2')->getValue());
        $this->assertSame($sheet->getCell('V2')->getValue(), $sheet->getCell('V2')->getHyperlink()->getUrl());
        $this->assertSame('Siti Aminah', $sheet->getCell('L2')->getValue());
        $this->assertSame('latitude', $sheet->getCell('H1')->getValue());

        $this->actingAs($world['userB'])->get('/submissions/export')->assertOk();
    }

    public function test_photo_hidden_from_clients_cannot_be_opened_by_them(): void
    {
        Storage::fake('local');
        $world = $this->world();
        $this->withPhotoField($world, visibleToClient: false);
        $this->submitPublicEntry($world['location'], ['foto_struk' => UploadedFile::fake()->image('struk.jpg')]);

        $entry = FormSubmission::query()->sole();
        $this->actingAs($world['userA'])->get('/submissions/'.$entry->id.'/files/foto_struk')->assertForbidden();
    }

    public function test_required_photo_must_be_an_image(): void
    {
        Storage::fake('local');
        $world = $this->world();
        $this->withPhotoField($world);

        $this->submitPublicEntry($world['location'])->assertSessionHasErrors('answers.foto_struk');
        $this->submitPublicEntry($world['location'], ['foto_struk' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('answers.foto_struk');

        $this->assertSame(0, FormSubmission::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
