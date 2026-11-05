<?php

namespace Database\Seeders;

use App\Models\Central\CoaMaster;
use Illuminate\Database\Seeder;

class CoaMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('data/coa_master.php') as $row) {
            CoaMaster::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
