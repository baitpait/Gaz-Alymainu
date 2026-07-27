<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->is_active && $user->isManager();
    }
}
