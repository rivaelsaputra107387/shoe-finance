<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JournalEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'staff', 'finance']);
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        if ($user->hasRole('staff')) {
            return $journalEntry->created_by === $user->id;
        }

        return $user->hasAnyRole(['owner', 'finance']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'staff', 'finance']);
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        // Journal entries cannot be edited once saved in closed periods, if it is a closing entry, or if it is posted.
        if ($journalEntry->is_closing || !$journalEntry->fiscalPeriod->is_open || $journalEntry->status === 'posted') {
            return false;
        }

        if ($user->hasRole('staff')) {
            return $journalEntry->created_by === $user->id;
        }

        return $user->hasAnyRole(['owner', 'finance']);
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        // Prevent deletion of closing entries, closed period transactions, or posted entries
        if ($journalEntry->is_closing || !$journalEntry->fiscalPeriod->is_open || $journalEntry->status === 'posted') {
            return false;
        }

        if ($user->hasRole('staff')) {
            return $journalEntry->created_by === $user->id;
        }

        return $user->hasRole('owner');
    }

    public function post(User $user, JournalEntry $journalEntry): bool
    {
        if ($journalEntry->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['owner', 'finance']);
    }

    public function restore(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasRole('owner');
    }

    public function forceDelete(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasRole('owner');
    }
}
