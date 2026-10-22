<?php

namespace App\Domain\Submissions\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmissionVoider
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function void(FormSubmission $submission, User $actor, string $reason): FormSubmission
    {
        if ($submission->isVoid()) {
            throw ValidationException::withMessages([
                'status' => 'This entry has already been voided.',
            ]);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'void_reason' => 'A void reason is required.',
            ]);
        }

        $old = $submission->only(['status', 'void_reason']);

        $submission->update([
            'status' => SubmissionStatus::Void,
            'voided_at' => now(),
            'voided_by' => $actor->id,
            'void_reason' => $reason,
        ]);

        $this->audit->record($actor, 'submission.voided', $submission, $old, $submission->only(['status', 'void_reason']));

        return $submission->refresh();
    }
}
