<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->canViewCampaign($campaign->id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->isStaff();
    }

    /**
     * Deleting a campaign stays with the superadmin.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isSuperadmin();
    }
}
