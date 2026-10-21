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
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->client_id === $campaign->client_id;
        }

        return $campaign->assignments()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isSuperadmin();
    }
}
