<?php

namespace Tests\Feature\Submissions;

use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class SubmissionImportTest extends TestCase
{
    use BuildsCampaignWorld;
    use RefreshDatabase;

    private const HEADER = 'submitted_at,Nama,Usia,No Handphone,Pekerjaan,Domisili,Akun Instagram,Mengunjungi POI minimal 2x seminggu,"Biasa bekerja, belajar, atau berlibur di area tersebut",Mengonsumsi CSD minimal 2x sebulan';

    private function csv(string ...$rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('entries.csv', implode("\n", [self::HEADER, ...$rows]));
    }

    public function test_staff_imports_rows_matched_by_label(): void
    {
        ['eo' => $eo, 'location' => $location] = $this->world();

        $this->actingAs($eo)
            ->post(route('submissions.import'), [
                'location_id' => $location->id,
                'file' => $this->csv(
                    '2026-10-20 14:30,Siti,24,081234567890,Karyawan,Bandung,siti,Ya,Yes,1',
                    ',Budi,30,081298765432,Mahasiswa,Jakarta,,No,Yes,Yes',
                ),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 entries imported.');

        $siti = FormSubmission::query()->where('answers->nama', 'Siti')->firstOrFail();
        $this->assertSame('2026-10-20 14:30:00', $siti->submitted_at->toDateTimeString());
        $this->assertTrue($siti->qualified);
        $this->assertSame($location->id, $siti->location_id);

        $budi = FormSubmission::query()->where('answers->nama', 'Budi')->firstOrFail();
        $this->assertFalse($budi->qualified);
    }

    public function test_nothing_is_saved_when_a_row_is_invalid(): void
    {
        ['eo' => $eo, 'location' => $location] = $this->world();

        $this->actingAs($eo)
            ->post(route('submissions.import'), [
                'location_id' => $location->id,
                'file' => $this->csv(
                    ',Siti,24,081234567890,Karyawan,Bandung,siti,Ya,Ya,Ya',
                    ',Budi,500,081298765432,Mahasiswa,Jakarta,,Ya,Ya,Ya',
                ),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, FormSubmission::query()->count());
        $this->assertStringContainsString('Row 3', session('errors')->first('file'));
    }

    public function test_clients_cannot_import(): void
    {
        ['userA' => $client, 'location' => $location] = $this->world();

        $this->actingAs($client)
            ->post(route('submissions.import'), [
                'location_id' => $location->id,
                'file' => $this->csv(',Siti,24,081234567890,Karyawan,Bandung,siti,Ya,Ya,Ya'),
            ])
            ->assertForbidden();
    }

    public function test_template_lists_the_form_columns(): void
    {
        ['eo' => $eo, 'location' => $location] = $this->world();

        $text = $this->exportedText(
            $this->actingAs($eo)->get(route('submissions.import-template', ['location_id' => $location->id]))->assertOk()
        );

        $this->assertStringStartsWith('submitted_at,Nama,Usia', $text);
    }
}
