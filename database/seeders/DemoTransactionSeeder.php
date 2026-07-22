<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 13 realistic financial transactions for June 2026.
     */
    public function run(): void
    {
        // 1. Get creator user (staff) and active period
        $creator = User::where('email', 'staff@shoeworkshop.com')->first() ?? User::where('email', 'owner@shoeworkshop.com')->first();
        $period = FiscalPeriod::where('name', 'Juni 2026')->first();

        if (!$creator || !$period) {
            $this->command->error("User Staff Kasir/Owner atau Periode fiskal 'Juni 2026' tidak ditemukan. Pastikan seeder dasar sudah dijalankan!");
            return;
        }

        // Get account instances by code for quick access
        $accounts = Account::active()->get()->keyBy('code');

        // Define transactions
        $transactions = [
            [
                'date' => '2026-06-01',
                'ref' => 'BM-001',
                'desc' => 'Setoran Modal Awal Pemilik (Investasi Awal)',
                'lines' => [
                    ['code' => '1110', 'debit' => 25000000.00, 'credit' => 0], // Kas
                    ['code' => '1120', 'debit' => 75000000.00, 'credit' => 0], // Bank BCA
                    ['code' => '3110', 'debit' => 0, 'credit' => 100000000.00], // Modal Disetor
                ]
            ],
            [
                'date' => '2026-06-02',
                'ref' => 'BK-001',
                'desc' => 'Pembayaran Sewa Ruko Dimuka 1 Tahun',
                'lines' => [
                    ['code' => '1160', 'debit' => 6000000.00, 'credit' => 0], // Sewa Dibayar Dimuka
                    ['code' => '1120', 'debit' => 0, 'credit' => 6000000.00], // Bank BCA
                ]
            ],
            [
                'date' => '2026-06-03',
                'ref' => 'BK-002',
                'desc' => 'Pembelian Peralatan Workshop (Mesin Cuci & Dryer)',
                'lines' => [
                    ['code' => '1210', 'debit' => 5000000.00, 'credit' => 0], // Peralatan
                    ['code' => '1120', 'debit' => 0, 'credit' => 5000000.00], // Bank BCA
                ]
            ],
            [
                'date' => '2026-06-05',
                'ref' => 'BK-003',
                'desc' => 'Pembelian Bahan Baku Cuci & Sabun Penolong',
                'lines' => [
                    ['code' => '1140', 'debit' => 3000000.00, 'credit' => 0], // Persediaan Bahan Baku
                    ['code' => '1110', 'debit' => 0, 'credit' => 3000000.00], // Kas
                ]
            ],
            [
                'date' => '2026-06-10',
                'ref' => 'BM-002',
                'desc' => 'Pendapatan Jasa Cuci Sepatu Mingguan (Kasir Kas)',
                'lines' => [
                    ['code' => '1110', 'debit' => 1500000.00, 'credit' => 0], // Kas
                    ['code' => '4110', 'debit' => 0, 'credit' => 1500000.00], // Jasa Cuci Sepatu
                ]
            ],
            [
                'date' => '2026-06-12',
                'ref' => 'BM-003',
                'desc' => 'Pendapatan Jasa Repaint Sepatu (Kasir Kas)',
                'lines' => [
                    ['code' => '1110', 'debit' => 2000000.00, 'credit' => 0], // Kas
                    ['code' => '4120', 'debit' => 0, 'credit' => 2000000.00], // Jasa Repaint
                ]
            ],
            [
                'date' => '2026-06-15',
                'ref' => 'BM-004',
                'desc' => 'Pendapatan Deep Clean Sepatu Korporasi (Transfer)',
                'lines' => [
                    ['code' => '1120', 'debit' => 4500000.00, 'credit' => 0], // Bank BCA
                    ['code' => '4150', 'debit' => 0, 'credit' => 4500000.00], // Jasa Deep Clean
                ]
            ],
            [
                'date' => '2026-06-20',
                'ref' => 'BM-005',
                'desc' => 'Penyelesaian Jasa Repair Sepatu Pelanggan (Piutang)',
                'lines' => [
                    ['code' => '1130', 'debit' => 1200000.00, 'credit' => 0], // Piutang Usaha
                    ['code' => '4130', 'debit' => 0, 'credit' => 1200000.00], // Jasa Repair
                ]
            ],
            [
                'date' => '2026-06-25',
                'ref' => 'BK-004',
                'desc' => 'Pembayaran Gaji Staff Cuci/Produksi',
                'lines' => [
                    ['code' => '5130', 'debit' => 2000000.00, 'credit' => 0], // Beban Gaji Produksi
                    ['code' => '1110', 'debit' => 0, 'credit' => 2000000.00], // Kas
                ]
            ],
            [
                'date' => '2026-06-28',
                'ref' => 'BK-005',
                'desc' => 'Pembayaran Beban Listrik & Air Ruko',
                'lines' => [
                    ['code' => '6140', 'debit' => 800000.00, 'credit' => 0], // Beban Listrik & Air
                    ['code' => '1110', 'debit' => 0, 'credit' => 800000.00], // Kas
                ]
            ],
            [
                'date' => '2026-06-29',
                'ref' => 'BK-006',
                'desc' => 'Penarikan Uang Tunai Oleh Pemilik (Prive)',
                'lines' => [
                    ['code' => '3120', 'debit' => 1000000.00, 'credit' => 0], // Prive
                    ['code' => '1110', 'debit' => 0, 'credit' => 1000000.00], // Kas
                ]
            ],
            [
                'date' => '2026-06-30',
                'ref' => 'BM-006',
                'desc' => 'Pendapatan Bunga Rekening Tabungan Bank BCA',
                'lines' => [
                    ['code' => '1120', 'debit' => 150000.00, 'credit' => 0], // Bank BCA
                    ['code' => '7110', 'debit' => 0, 'credit' => 150000.00], // Pendapatan Bunga Bank
                ]
            ],
            [
                'date' => '2026-06-30',
                'ref' => 'BK-007',
                'desc' => 'Beban Administrasi Bulanan Bank BCA',
                'lines' => [
                    ['code' => '8110', 'debit' => 25000.00, 'credit' => 0], // Beban Admin Bank
                    ['code' => '1120', 'debit' => 0, 'credit' => 25000.00], // Bank BCA
                ]
            ],
            // ── JURNAL PENYESUAIAN AKHIR PERIODE ──
            [
                'date' => '2026-06-30',
                'ref' => 'AJP-001',
                'desc' => 'Jurnal Penyesuaian: Amortisasi Beban Sewa Ruko Bulan Juni (1/12)',
                'lines' => [
                    ['code' => '6130', 'debit' => 500000.00, 'credit' => 0], // Beban Sewa
                    ['code' => '1160', 'debit' => 0, 'credit' => 500000.00], // Sewa Dibayar Dimuka
                ]
            ],
            [
                'date' => '2026-06-30',
                'ref' => 'AJP-002',
                'desc' => 'Jurnal Penyesuaian: Beban Penyusutan Peralatan Bulan Juni',
                'lines' => [
                    ['code' => '6190', 'debit' => 100000.00, 'credit' => 0], // Beban Penyusutan Peralatan
                    ['code' => '1211', 'debit' => 0, 'credit' => 100000.00], // Akumulasi Penyusutan Peralatan
                ]
            ],
            [
                'date' => '2026-06-30',
                'ref' => 'AJP-003',
                'desc' => 'Jurnal Penyesuaian: Pemakaian Bahan Baku Cuci Habis Pakai',
                'lines' => [
                    ['code' => '5110', 'debit' => 1200000.00, 'credit' => 0], // Beban Bahan Baku
                    ['code' => '1140', 'debit' => 0, 'credit' => 1200000.00], // Persediaan Bahan Baku
                ]
            ],
        ];

        // Seed inside a database transaction to ensure safety
        DB::transaction(function () use ($transactions, $creator, $period, $accounts) {
            foreach ($transactions as $txData) {
                $entry = JournalEntry::create([
                    'entry_date' => $txData['date'],
                    'reference' => $txData['ref'],
                    'description' => $txData['desc'],
                    'fiscal_period_id' => $period->id,
                    'created_by' => $creator->id,
                    'is_closing' => false,
                ]);

                foreach ($txData['lines'] as $lineData) {
                    $account = $accounts->get($lineData['code']);

                    if (!$account) {
                        throw new \Exception("Akun dengan kode {$lineData['code']} tidak ditemukan di COA!");
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $account->id,
                        'debit' => $lineData['debit'],
                        'credit' => $lineData['credit'],
                    ]);
                }

                // Balance integrity check
                if (!$entry->is_balanced) {
                    throw new \Exception("Jurnal transaksi '{$txData['desc']}' tidak seimbang!");
                }
            }
        });

        $this->command->info("Sukses mempopulasi " . count($transactions) . " demo transaksi Juni 2026!");
    }
}
