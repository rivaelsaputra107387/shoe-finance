<?php

namespace App\Policies;

use App\Models\FiscalPeriod;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FiscalPeriodPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner');
    }

    public function view(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasRole('owner');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('owner');
    }

    public function update(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasRole('owner') && $fiscalPeriod->status === 'open';
    }

    public function delete(User $user, FiscalPeriod $fiscalPeriod): bool
    {
        return $user->hasRole('owner') && $fiscalPeriod->journalEntries()->count() === 0;
    }
}
