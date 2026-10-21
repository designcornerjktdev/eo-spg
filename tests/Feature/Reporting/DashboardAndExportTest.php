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

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/fill', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

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

    public function test_client_cannot_see_hidden_or_unmasked_identity_fields(): void
    {
        $world = $this->world();
        $world['form']->fields()->where('key', 'pekerjaan')->update(['visible_to_client' => false]);

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/fill', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $submission = FormSubmission::query()->first();
        $response = $this->actingAs($world['userA'])->get('/submissions/'.$submission->id);
        $response->assertOk();
        $response->assertDontSee('Karyawan');
        $response->assertDontSee('081234567890');
        $response->assertDontSee('Siti Aminah');
    }

    public function test_client_cannot_open_other_client_submission(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/fill', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $submission = FormSubmission::query()->first();

        $this->actingAs($world['userB'])->get('/submissions/'.$submission->id)->assertForbidden();
        $this->actingAs($world['userB'])->get('/campaigns/'.$world['campaign']->id)->assertForbidden();
    }

    public function test_csv_export_follows_visibility(): void
    {
        $world = $this->world();
        $world['form']->fields()->where('key', 'pekerjaan')->update(['visible_to_client' => false]);

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/fill', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $csv = $this->actingAs($world['userA'])->get('/submissions/export')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('Karyawan', $csv);
        $this->assertStringContainsString('submitted_at', $csv);
    }

    public function test_dashboard_counts_match_active_submissions(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/fill', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $this->actingAs($world['userA'])->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('summary.totals.submissions', 1)
            ->where('summary.totals.qualified', 1)
        );
    }
}
