<?php

namespace Database\Seeders;

use App\Models\FiscalPeriod;
use Illuminate\Database\Seeder;

class FiscalPeriodSeeder extends Seeder
{
    /**
     * Seed the initial fiscal period: Juni 2026.
     */
    public function run(): void
    {
        FiscalPeriod::create([
            'name' => 'Juni 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'open',
        ]);
    }
}
