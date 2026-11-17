<?php

namespace App\Support;

class IdentityTables
{
    /**
     * Tabel yang pindah ke database pusat, dan tidak dipakai lagi di database unit.
     */
    public const NAMES = [
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'roles',
        'permissions',
        'personal_access_tokens',
        'notifications',
        'password_reset_tokens',
        'sessions',
        'users',
        'cache',
        'cache_locks',
        'job_batches',
        'jobs',
        'failed_jobs',
    ];
}
