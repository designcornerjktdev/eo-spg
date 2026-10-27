<?php

namespace Tests\Feature\Reporting;

use App\Models\AuditLog;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class DashboardAndExportTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_eo_can_void_with_reason_and_audit(): void
    {
        $world = $this->world();

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        $submission = FormSubmission::query()->first();

        $this->actingAs($world['eo'])->post('/submissions/'.$submission->id.'/void', [
            'void_reason' => 'Salah isi usia',
        ])->assertRedirect();

        $this->assertSame('void', $submission->fresh()->status->value);
        $this->assertSame('Salah isi usia', $submission->fresh()->void_reason);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'submission.voided',
            'auditable_id' => $submission->id,
        ]);
        $this->assertGreaterThan(0, AuditLog::query()->count());
    }

    public function test_client_sees_identity_unmasked_but_not_hidden_fields(): void
    {
        $world = $this->world();
        $world['form']->fields()->where('key', 'pekerjaan')->update(['visible_to_client' => false]);

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        $submission = FormSubmission::query()->first();
        $response = $this->actingAs($world['userA'])->get('/submissions/'.$submission->id);
        $response->assertOk();
        $response->assertDontSee('Karyawan');
        $response->assertSee('081234567890');
        $response->assertSee('Siti Aminah');
    }

    public function test_client_cannot_open_other_client_submission(): void
    {
        $world = $this->world();

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        $submission = FormSubmission::query()->first();

        $this->actingAs($world['userB'])->get('/submissions/'.$submission->id)->assertForbidden();
        $this->actingAs($world['userB'])->get('/campaigns/'.$world['campaign']->id)->assertForbidden();
    }

    public function test_empty_filters_are_not_passed_to_the_page(): void
    {
        $world = $this->world();

        // Submitting the filter bar with nothing chosen sends empty values; they must not come back as "null".
        $this->actingAs($world['eo'])->get('/submissions?campaign_id=&location_id=&q=')
            ->assertInertia(fn ($page) => $page->where('filters', []));
    }

    public function test_invalid_entries_are_left_out_of_the_export(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['location'], ['nama' => 'Valid Visitor'])->assertSessionHasNoErrors();
        $this->submitPublicEntry($world['location'], ['nama' => 'Duplicate Visitor'])->assertSessionHasNoErrors();
        $duplicate = FormSubmission::query()->latest('id')->first();

        $this->actingAs($world['eo'])->post('/submissions/'.$duplicate->id.'/void', ['void_reason' => 'Filled in twice'])->assertSessionHasNoErrors();

        $export = $this->exportedText($this->actingAs($world['eo'])->get('/submissions/export')->assertOk());
        $this->assertStringContainsString('Valid Visitor', $export);
        $this->assertStringNotContainsString('Duplicate Visitor', $export);
    }

    public function test_export_follows_visibility(): void
    {
        $world = $this->world();
        $world['form']->fields()->where('key', 'pekerjaan')->update(['visible_to_client' => false]);

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        $csv = $this->exportedText($this->actingAs($world['userA'])->get('/submissions/export')->assertOk());
        $this->assertStringNotContainsString('Karyawan', $csv);
        $this->assertStringContainsString('submitted_at', $csv);
    }

    public function test_dashboard_counts_match_active_submissions(): void
    {
        $world = $this->world();

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        $this->actingAs($world['userA'])->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('summary.totals.submissions', 1)
            ->where('summary.totals.qualified', 1)
        );
    }
}
