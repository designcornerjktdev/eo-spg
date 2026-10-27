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
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->canViewCampaign($submission->campaign_id);
        }

        return false;
    }

    public function update(User $user, FormSubmission $submission): bool
    {
        return false;
    }

    public function void(User $user, FormSubmission $submission): bool
    {
        return $user->isStaff();
    }

    public function import(User $user): bool
    {
        return $user->isStaff();
    }

    public function export(User $user): bool
    {
        return $user->isStaff() || $user->isClient();
    }
}
