<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class SuspenseAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan parent Aset Lancar ada, kalau tidak ambil parent terdekat atau buat tanpa parent
        $parent = Account::where('code', '1100')->first();
        
        Account::firstOrCreate(
            ['code' => '9999'],
            [
                'name' => 'Akun Sementara / Suspense',
                'type' => 'Aset',
                'normal_balance' => 'Debet',
                'report_category' => 'Neraca',
                'cash_flow_category' => null,
                'is_active' => true,
                'parent_id' => $parent ? $parent->id : null,
            ]
        );
    }
}
