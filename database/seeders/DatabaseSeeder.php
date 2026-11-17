<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Order matters: roles/permissions must be created before users.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            FiscalPeriodSeeder::class,
            AccountSeeder::class,
            SuspenseAccountSeeder::class,
        ]);
    }
}
