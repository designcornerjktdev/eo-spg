<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Full account changes: profile, status, password and deletion.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Granting campaigns to a client. Admins may do this, but cannot touch other account details.
     */
    public function manageCampaignAccess(User $user, User $model): bool
    {
        return $model->isClient() && ($user->isSuperadmin() || $user->isAdmin());
    }
}
