<?php

namespace App\Domain\Submissions\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Same-day self-correction by the SPG. Old data is never overwritten: the old row is voided
 * and a new row is created (see ADR 003).
 */
class SubmissionCorrector
{
    public function __construct(
        private readonly SubmissionRecorder $recorder,
        private readonly SubmissionVoider $voider,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $answers
     */
    public function correct(FormSubmission $submission, User $actor, array $answers, bool $consentAccepted = false): FormSubmission
    {
        $this->assertCorrectable($submission);

        return DB::transaction(function () use ($submission, $actor, $answers, $consentAccepted) {
            $replacement = $this->recorder->record($submission->assignment, $actor, $answers, $consentAccepted);
            $replacement->update(['corrects_submission_id' => $submission->id]);

            $this->voider->void($submission, $actor, 'Corrected by SPG, replaced by entry #'.$replacement->id);
            $this->audit->record($actor, 'submission.corrected', $replacement, ['corrects_submission_id' => $submission->id], $replacement->only(['answers']));

            return $replacement->refresh();
        });
    }

    public function cancel(FormSubmission $submission, User $actor, string $reason): FormSubmission
    {
        $this->assertCorrectable($submission);

        return $this->voider->void($submission, $actor, 'Cancelled by SPG: '.trim($reason));
    }

    private function assertCorrectable(FormSubmission $submission): void
    {
        if (! $submission->isCorrectableBySubmitter()) {
            throw ValidationException::withMessages([
                'submission' => 'Entries can only be corrected or cancelled on the same day. Contact the EO admin for corrections.',
            ]);
        }
    }
}
