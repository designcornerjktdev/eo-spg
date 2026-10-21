<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isClient();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->isSuperadmin() || ($user->isClient() && $user->client_id === $client->id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isSuperadmin();
    }
}
