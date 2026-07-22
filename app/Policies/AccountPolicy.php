<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'staff', 'finance']);
    }

    public function view(User $user, Account $account): bool
    {
        return $user->hasAnyRole(['owner', 'staff', 'finance']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'finance']);
    }

    public function update(User $user, Account $account): bool
    {
        return $user->hasAnyRole(['owner', 'finance']);
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->hasRole('owner');
    }

    public function restore(User $user, Account $account): bool
    {
        return $user->hasRole('owner');
    }

    public function forceDelete(User $user, Account $account): bool
    {
        return $user->hasRole('owner');
    }
}
