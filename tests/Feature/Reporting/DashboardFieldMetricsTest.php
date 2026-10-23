<?php

namespace Tests\Feature\Reporting;

use App\Enums\FieldType;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
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

    public function test_yes_no_questions_count_towards_qualified_but_get_no_chart(): void
    {
        $world = $this->world();
        $this->submit($world['location'], ['kunjungi_poi' => true, 'usia' => 20]);
        $this->submit($world['location'], ['kunjungi_poi' => false, 'usia' => 30]);
        $this->submit($world['location'], ['kunjungi_poi' => false, 'usia' => 40]);

        $page = $this->actingAs($world['eo'])->get('/dashboard')->assertOk()->viewData('page');
        $summary = $page['props']['summary'];

        // Only the all-Yes entry qualifies, and no Yes/No question appears as its own chart.
        $this->assertSame(1, $summary['totals']['qualified']);
        $this->assertSame([], collect($summary['custom_fields'])->where('type', 'yes_no')->values()->all());
        $this->assertSame(0, SubmissionFieldValue::query()->where('field_type', 'yes_no')->count());

        $age = $this->field($page, 'usia');
        $this->assertSame('Usia', $age['label']);
        $this->assertSame(3, $age['responses']);
        $this->assertEquals(30, $age['avg']);
        $this->assertEquals(20, $age['min']);
        $this->assertEquals(40, $age['max']);
    }

    public function test_chart_series_match_the_totals(): void
    {
        $world = $this->world();
        $this->travelTo(now()->setTime(10, 15));
        $this->submit($world['location'], ['usia' => 20]);
        $this->submit($world['location'], ['usia' => 41, 'kunjungi_poi' => false]);
        $this->travelTo(now()->setTime(14, 5));
        $this->submit($world['location'], ['usia' => 45]);

        $summary = $this->actingAs($world['eo'])->get('/dashboard')->viewData('page')['props']['summary'];

        // Per day splits qualified / not qualified and adds up to the headline numbers.
        $this->assertSame([['day' => now()->toDateString(), 'total' => 3, 'qualified' => 2]], $summary['per_day']);
        $this->assertSame(2, $summary['totals']['qualified']);

        $this->assertSame([['hour' => 10, 'total' => 2], ['hour' => 14, 'total' => 1]], $summary['per_hour']);
        $this->assertSame([['id' => $world['location']->id, 'label' => 'PIK Avenue', 'total' => 3]], $summary['per_location']);

        $bins = collect($this->field(['props' => ['summary' => $summary]], 'usia')['bins']);
        $this->assertSame(3, $bins->sum('total'));
        $this->assertSame('20-23', $bins->first()['label']);
    }

    public function test_older_yes_no_dashboard_rows_are_ignored(): void
    {
        $world = $this->world();
        $this->submit($world['location'], ['usia' => 25]);
        // Rows indexed before Yes/No left the dashboard.
        SubmissionFieldValue::query()->create([
            'form_submission_id' => FormSubmission::query()->sole()->id,
            'field_key' => 'kunjungi_poi',
            'field_type' => 'yes_no',
            'value_text' => '1',
        ]);

        $summary = $this->actingAs($world['eo'])->get('/dashboard')->viewData('page')['props']['summary'];

        $this->assertSame(['usia'], collect($summary['custom_fields'])->pluck('key')->all());
    }

    public function test_yes_no_fields_cannot_be_put_on_the_dashboard(): void
    {
        $this->assertFalse(FieldType::YesNo->canShowOnDashboard());
        $this->assertTrue(FieldType::Number->canShowOnDashboard());
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
