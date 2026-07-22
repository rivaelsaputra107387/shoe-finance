<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Seed the Chart of Accounts (COA) based on Excel data.
     * Account code conventions from PRD:
     * 1xxx = Aset (1100 lancar, 1200 tetap)
     * 2xxx = Kewajiban
     * 3xxx = Ekuitas
     * 4xxx = Pendapatan
     * 5xxx = HPP & Beban Gaji Produksi
     * 6xxx = Beban Operasional Umum
     * 7xxx = Pendapatan/Beban Lain-lain
     * 8xxx = Beban Admin Bank & Pajak
     */
    public function run(): void
    {
        $accounts = $this->getAccountData();

        // First pass: create parent accounts (no parent_id)
        foreach ($accounts as $account) {
            if ($account['parent_code'] === null) {
                Account::create([
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                    'report_category' => $account['report_category'],
                    'cash_flow_category' => $account['cash_flow_category'],
                    'is_active' => true,
                ]);
            }
        }

        // Second pass: create child accounts with parent_id
        foreach ($accounts as $account) {
            if ($account['parent_code'] !== null) {
                $parent = Account::where('code', $account['parent_code'])->first();
                Account::create([
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                    'report_category' => $account['report_category'],
                    'cash_flow_category' => $account['cash_flow_category'],
                    'parent_id' => $parent?->id,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Get all COA account data.
     * Based on PRD conventions and typical Shoe Workshop accounting structure.
     */
    private function getAccountData(): array
    {
        return [
            // ═══════════════════════════════════════
            // ASET (1xxx) — Neraca, Saldo Normal: Debet
            // ═══════════════════════════════════════

            // Aset Lancar (parent group)
            ['code' => '1100', 'name' => 'Aset Lancar', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],
            ['code' => '1110', 'name' => 'Kas', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1111', 'name' => 'Kas Kecil', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1120', 'name' => 'Bank BCA', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1121', 'name' => 'Bank Mandiri', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1130', 'name' => 'Piutang Usaha', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1131', 'name' => 'Piutang Karyawan', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1140', 'name' => 'Persediaan Bahan Baku', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1141', 'name' => 'Persediaan Bahan Penolong', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1150', 'name' => 'Perlengkapan', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1160', 'name' => 'Sewa Dibayar Dimuka', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],
            ['code' => '1170', 'name' => 'Asuransi Dibayar Dimuka', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '1100'],

            // Aset Tetap (parent group)
            ['code' => '1200', 'name' => 'Aset Tetap', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],
            ['code' => '1210', 'name' => 'Peralatan', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1211', 'name' => 'Akumulasi Penyusutan Peralatan', 'type' => 'Aset', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'Mesin', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1221', 'name' => 'Akumulasi Penyusutan Mesin', 'type' => 'Aset', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1230', 'name' => 'Kendaraan', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1231', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'Aset', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1240', 'name' => 'Bangunan', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1241', 'name' => 'Akumulasi Penyusutan Bangunan', 'type' => 'Aset', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1250', 'name' => 'Inventaris Kantor', 'type' => 'Aset', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],
            ['code' => '1251', 'name' => 'Akumulasi Penyusutan Inventaris', 'type' => 'Aset', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Investasi', 'parent_code' => '1200'],

            // ═══════════════════════════════════════
            // KEWAJIBAN (2xxx) — Neraca, Saldo Normal: Kredit
            // ═══════════════════════════════════════

            ['code' => '2100', 'name' => 'Kewajiban Lancar', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],
            ['code' => '2110', 'name' => 'Utang Usaha', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '2100'],
            ['code' => '2120', 'name' => 'Utang Gaji', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '2100'],
            ['code' => '2130', 'name' => 'Utang Pajak', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '2100'],
            ['code' => '2140', 'name' => 'Pendapatan Diterima Dimuka', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '2100'],
            ['code' => '2150', 'name' => 'Utang Lain-lain', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Operasi', 'parent_code' => '2100'],

            ['code' => '2200', 'name' => 'Kewajiban Jangka Panjang', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],
            ['code' => '2210', 'name' => 'Utang Bank', 'type' => 'Kewajiban', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Pendanaan', 'parent_code' => '2200'],

            // ═══════════════════════════════════════
            // EKUITAS (3xxx) — Neraca, Saldo Normal: Kredit
            // ═══════════════════════════════════════

            ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Pendanaan', 'parent_code' => null],
            ['code' => '3110', 'name' => 'Modal Disetor', 'type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => 'Pendanaan', 'parent_code' => '3100'],
            ['code' => '3120', 'name' => 'Prive', 'type' => 'Ekuitas', 'normal_balance' => 'Debet', 'report_category' => 'Neraca', 'cash_flow_category' => 'Pendanaan', 'parent_code' => '3100'],
            ['code' => '3200', 'name' => 'Ikhtisar Laba Rugi', 'type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],
            ['code' => '3300', 'name' => 'Laba Ditahan', 'type' => 'Ekuitas', 'normal_balance' => 'Kredit', 'report_category' => 'Neraca', 'cash_flow_category' => null, 'parent_code' => null],

            // ═══════════════════════════════════════
            // PENDAPATAN (4xxx) — Laba Rugi, Saldo Normal: Kredit
            // ═══════════════════════════════════════

            ['code' => '4100', 'name' => 'Pendapatan Usaha', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '4110', 'name' => 'Pendapatan Jasa Cuci Sepatu', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],
            ['code' => '4120', 'name' => 'Pendapatan Jasa Repaint', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],
            ['code' => '4130', 'name' => 'Pendapatan Jasa Repair', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],
            ['code' => '4140', 'name' => 'Pendapatan Jasa Unyellowing', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],
            ['code' => '4150', 'name' => 'Pendapatan Jasa Deep Clean', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],
            ['code' => '4160', 'name' => 'Pendapatan Jasa Lainnya', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '4100'],

            // ═══════════════════════════════════════
            // HPP & BEBAN GAJI PRODUKSI (5xxx) — Laba Rugi, Saldo Normal: Debet
            // ═══════════════════════════════════════

            ['code' => '5100', 'name' => 'Harga Pokok Penjualan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '5110', 'name' => 'Beban Bahan Baku', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],
            ['code' => '5120', 'name' => 'Beban Bahan Penolong', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],
            ['code' => '5130', 'name' => 'Beban Gaji Produksi', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],
            ['code' => '5140', 'name' => 'Beban Overhead Produksi', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],
            ['code' => '5150', 'name' => 'Beban Penyusutan Mesin', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],
            ['code' => '5160', 'name' => 'Beban Packing & Pengiriman', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '5100'],

            // ═══════════════════════════════════════
            // BEBAN OPERASIONAL (6xxx) — Laba Rugi, Saldo Normal: Debet
            // ═══════════════════════════════════════

            ['code' => '6100', 'name' => 'Beban Operasional', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '6110', 'name' => 'Beban Gaji Karyawan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6120', 'name' => 'Beban Kompensasi', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6130', 'name' => 'Beban Sewa', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6140', 'name' => 'Beban Listrik & Air', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6150', 'name' => 'Beban Telepon & Internet', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6160', 'name' => 'Beban Transportasi', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6170', 'name' => 'Beban Perlengkapan Kantor', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6180', 'name' => 'Beban Kebersihan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6190', 'name' => 'Beban Penyusutan Peralatan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6200', 'name' => 'Beban Penyusutan Kendaraan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6210', 'name' => 'Beban Penyusutan Bangunan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6220', 'name' => 'Beban Marketing & Iklan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6230', 'name' => 'Beban Asuransi', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6240', 'name' => 'Beban Perawatan & Perbaikan', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],
            ['code' => '6250', 'name' => 'Beban Lain-lain Operasional', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '6100'],

            // ═══════════════════════════════════════
            // PENDAPATAN/BEBAN LAIN-LAIN (7xxx) — Laba Rugi
            // ═══════════════════════════════════════

            ['code' => '7100', 'name' => 'Pendapatan Lain-lain', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '7110', 'name' => 'Pendapatan Bunga Bank', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '7100'],
            ['code' => '7120', 'name' => 'Pendapatan Lain-lain Usaha', 'type' => 'Pendapatan', 'normal_balance' => 'Kredit', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '7100'],

            ['code' => '7200', 'name' => 'Beban Lain-lain', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '7210', 'name' => 'Beban Bunga', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Pendanaan', 'parent_code' => '7200'],
            ['code' => '7220', 'name' => 'Kerugian Piutang Tak Tertagih', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '7200'],
            ['code' => '7230', 'name' => 'Beban Lain-lain Non-Operasional', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '7200'],

            // ═══════════════════════════════════════
            // BEBAN ADMIN BANK & PAJAK (8xxx) — Laba Rugi, Saldo Normal: Debet
            // ═══════════════════════════════════════

            ['code' => '8100', 'name' => 'Beban Administrasi & Pajak', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => null],
            ['code' => '8110', 'name' => 'Beban Admin Bank', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '8100'],
            ['code' => '8120', 'name' => 'Beban Pajak', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '8100'],
            ['code' => '8130', 'name' => 'Beban Materai', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'cash_flow_category' => 'Operasi', 'parent_code' => '8100'],
        ];
    }
}
