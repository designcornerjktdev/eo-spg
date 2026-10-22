<?php

namespace Tests\Feature\Submissions;

use App\Enums\SubmissionStatus;
use App\Models\AuditLog;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class SpgCorrectionTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    private function submit(array $world): FormSubmission
    {
        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertSessionHasNoErrors();

        return FormSubmission::query()->latest('id')->firstOrFail();
    }

    public function test_spg_can_correct_own_submission_on_same_day(): void
    {
        $world = $this->world();
        $original = $this->submit($world);

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/correction', [
            'answers' => [...$this->validAnswers(), 'no_handphone' => '089999999999'],
            'consent_accepted' => true,
        ])->assertSessionHasNoErrors()->assertRedirect('/tasks/'.$world['assignment']->id);

        $original->refresh();
        $replacement = FormSubmission::query()->where('corrects_submission_id', $original->id)->firstOrFail();

        $this->assertSame(SubmissionStatus::Void, $original->status);
        $this->assertSame('081234567890', $original->answers['no_handphone']);
        $this->assertStringContainsString('#'.$replacement->id, $original->void_reason);
        $this->assertSame('089999999999', $replacement->answers['no_handphone']);
        $this->assertTrue(AuditLog::query()->where('action', 'submission.corrected')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'submission.voided')->exists());
    }

    public function test_invalid_correction_keeps_original_untouched(): void
    {
        $world = $this->world();
        $original = $this->submit($world);

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/correction', [
            'answers' => ['nama' => 'Siti'],
            'consent_accepted' => true,
        ])->assertSessionHasErrors();

        $this->assertSame(SubmissionStatus::Submitted, $original->refresh()->status);
        $this->assertSame(1, FormSubmission::query()->count());
    }

    public function test_spg_can_cancel_own_submission_with_reason(): void
    {
        $world = $this->world();
        $original = $this->submit($world);

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => 'Data dobel'])
            ->assertSessionHasNoErrors();

        $original->refresh();
        $this->assertSame(SubmissionStatus::Void, $original->status);
        $this->assertSame('Dibatalkan SPG: Data dobel', $original->void_reason);
    }

    public function test_correction_is_rejected_after_the_day_ends(): void
    {
        $world = $this->world();
        $original = $this->submit($world);
        $original->update(['submitted_at' => now()->subDay()]);

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => 'Telat'])
            ->assertSessionHasErrors('submission');

        $this->assertSame(SubmissionStatus::Submitted, $original->refresh()->status);
    }

    public function test_other_spg_and_eo_cannot_use_spg_correction(): void
    {
        $world = $this->world();
        $original = $this->submit($world);

        $this->actingAs($world['otherSpg'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => 'Iseng'])
            ->assertForbidden();
        $this->actingAs($world['eo'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => 'Iseng'])
            ->assertForbidden();

        $this->assertSame(SubmissionStatus::Submitted, $original->refresh()->status);
    }

    public function test_void_submission_cannot_be_corrected_again(): void
    {
        $world = $this->world();
        $original = $this->submit($world);

        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/cancellation', ['reason' => 'Data dobel']);
        $this->actingAs($world['spg'])->post('/submissions/'.$original->id.'/correction', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertSessionHasErrors('submission');

        $this->assertSame(1, FormSubmission::query()->count());
    }
}
