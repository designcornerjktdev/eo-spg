<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isClient() || $user->isSpg();
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->client_id === $product->client_id;
        }

        return $product->campaigns()->whereHas('assignments', fn ($query) => $query->where('user_id', $user->id))->exists();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isSuperadmin();
    }
}
