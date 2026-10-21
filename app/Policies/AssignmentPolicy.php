<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isSpg();
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isSpg() && $assignment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->isSuperadmin();
    }

    public function fill(User $user, Assignment $assignment): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isSpg() && $assignment->user_id === $user->id;
    }
}
