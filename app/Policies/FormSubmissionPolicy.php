<?php

namespace App\Policies;

use App\Models\FormSubmission;
use App\Models\User;

class FormSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FormSubmission $submission): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->client_id === $submission->campaign?->client_id;
        }

        return $submission->submitted_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isSpg();
    }

    public function update(User $user, FormSubmission $submission): bool
    {
        return false;
    }

    public function void(User $user, FormSubmission $submission): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Self-correction/cancellation by the owning SPG; the same-day limit is checked in SubmissionCorrector.
     */
    public function correct(User $user, FormSubmission $submission): bool
    {
        return $user->isSpg() && $submission->submitted_by === $user->id;
    }

    public function export(User $user): bool
    {
        return $user->isSuperadmin() || $user->isClient();
    }
}
