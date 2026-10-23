<?php

namespace Tests\Feature\Reporting;

use App\Models\CampaignLocation;
use App\Models\SubmissionFieldValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class DashboardFieldMetricsTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    private function submit(CampaignLocation $location, array $overrides): void
    {
        $this->submitPublicEntry($location, $overrides)->assertSessionHasNoErrors();
    }

    private function field(array $page, string $key): array
    {
        return collect($page['props']['summary']['custom_fields'])->firstWhere('key', $key);
    }

    public function test_yes_no_metrics_count_no_answers_and_use_field_labels(): void
    {
        $world = $this->world();
        $this->submit($world['location'], ['kunjungi_poi' => true, 'usia' => 20]);
        $this->submit($world['location'], ['kunjungi_poi' => false, 'usia' => 30]);
        $this->submit($world['location'], ['kunjungi_poi' => false, 'usia' => 40]);

        $this->assertSame('0', SubmissionFieldValue::query()->where('field_key', 'kunjungi_poi')->where('value_text', '0')->first()?->value_text);

        $page = $this->actingAs($world['eo'])->get('/dashboard')->assertOk()->viewData('page');

        $poi = $this->field($page, 'kunjungi_poi');
        $this->assertSame('Mengunjungi POI minimal 2x seminggu', $poi['label']);
        $this->assertSame([['label' => 'No', 'total' => 2], ['label' => 'Yes', 'total' => 1]], $poi['options']);

        $age = $this->field($page, 'usia');
        $this->assertSame(3, $age['responses']);
        $this->assertEquals(30, $age['avg']);
        $this->assertEquals(20, $age['min']);
        $this->assertEquals(40, $age['max']);
    }

    public function test_legacy_empty_no_answers_still_count_as_no(): void
    {
        $world = $this->world();
        $this->submit($world['location'], ['kunjungi_poi' => false]);
        // Rows written before the fix stored "No" as an empty string.
        SubmissionFieldValue::query()->where('field_key', 'kunjungi_poi')->update(['value_text' => '']);

        $page = $this->actingAs($world['eo'])->get('/dashboard')->viewData('page');

        $this->assertSame([['label' => 'No', 'total' => 1]], $this->field($page, 'kunjungi_poi')['options']);
    }

    public function test_field_metrics_follow_location_filter(): void
    {
        $world = $this->world();
        $other = CampaignLocation::factory()->create(['campaign_id' => $world['campaign']->id]);

        $this->submit($world['location'], ['usia' => 20]);
        $this->submit($other, ['usia' => 50]);

        $page = $this->actingAs($world['eo'])->get('/dashboard?location_id='.$world['location']->id)->viewData('page');

        $this->assertSame(1, $page['props']['summary']['totals']['submissions']);
        $age = $this->field($page, 'usia');
        $this->assertSame(1, $age['responses']);
        $this->assertEquals(20, $age['avg']);
    }
}
