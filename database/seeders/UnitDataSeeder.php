<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data awal satu database unit. Jalankan di dalam konteks unit.
 */
class UnitDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FiscalPeriodSeeder::class,
            AccountSeeder::class,
            SuspenseAccountSeeder::class,
        ]);
    }
}
