<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isClient();
    }

    public function view(User $user, Product $product): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isClient()) {
            return $user->client_id === $product->client_id;
        }

        return false;
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
