<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DummyTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@finlog.com')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Get all required accounts
        $kas = Account::where('code', '1110')->first();
        $bankBca = Account::where('code', '1120')->first(); // Assuming BCA
        
        $incomes = Account::whereIn('code', ['4110', '4120', '4130', '4140', '4150'])->get();
        $expenses = Account::whereIn('code', ['5110', '5120', '5130', '6110', '6130', '6140', '8110'])->get();
        $ikhtisarLabaRugi = Account::where('code', '3200')->first();
        $modal = Account::where('code', '3110')->first();

        // Target omzet 250jt - 330jt per month.
        // Approx 8.3m - 11m per day.
        
        $startDate = Carbon::create(2025, 11, 1);
        $endDate = Carbon::create(2026, 6, 30);
        
        $currentDate = $startDate->copy();

        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        while ($currentDate <= $endDate) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            
            // Create/Ensure Fiscal Period exists
            $periodName = $indonesianMonths[$monthStart->month] . ' ' . $monthStart->year;
            $period = FiscalPeriod::firstOrCreate(
                ['name' => $periodName],
                [
                    'start_date' => $monthStart,
                    'end_date' => $monthEnd,
                    'status' => 'closed',
                ]
            );
            $period->update(['status' => 'closed']);

            // Generate daily transactions for this month
            $daysInMonth = $monthStart->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $monthStart->copy()->addDays($day - 1);
                if ($date > $endDate) break;

                // 1. Generate Daily Incomes (3 - 5 transactions per day)
                $dailyIncomeCount = rand(3, 5);
                for ($i = 0; $i < $dailyIncomeCount; $i++) {
                    $incomeAccount = $incomes->random();
                    // 1jt - 3jt per transaction to reach ~8-11m a day
                    $amount = rand(1500000, 3500000); 
                    
                    $entry = JournalEntry::create([
                        'entry_date' => $date,
                        'reference' => 'INV-' . $date->format('Ymd') . '-' . Str::random(4),
                        'description' => 'Penerimaan ' . $incomeAccount->name . ' dari Pelanggan',
                        'fiscal_period_id' => $period->id,
                        'created_by' => $admin->id,
                        'status' => 'posted',
                    ]);

                    // Debit Kas/Bank
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => rand(0, 1) ? $kas->id : $bankBca->id,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);

                    // Credit Income
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $incomeAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);
                }

                // 2. Generate Daily Expenses (1 - 2 transactions per day)
                $dailyExpenseCount = rand(1, 2);
                for ($i = 0; $i < $dailyExpenseCount; $i++) {
                    $expenseAccount = $expenses->random();
                    // Expenses are roughly 50% of income, so 500k - 2m per transaction
                    $amount = rand(500000, 2000000); 
                    
                    $entry = JournalEntry::create([
                        'entry_date' => $date,
                        'reference' => 'EXP-' . $date->format('Ymd') . '-' . Str::random(4),
                        'description' => 'Pembayaran ' . $expenseAccount->name,
                        'fiscal_period_id' => $period->id,
                        'created_by' => $admin->id,
                        'status' => 'posted',
                    ]);

                    // Debit Expense
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $expenseAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                    ]);

                    // Credit Kas/Bank
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => rand(0, 1) ? $kas->id : $bankBca->id,
                        'debit' => 0,
                        'credit' => $amount,
                    ]);
                }
            }
            
            // 3. Create Closing Entry for this month
            // Sum all revenues
            $totalRevenue = JournalEntryLine::whereHas('journalEntry', function($q) use ($period) {
                $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false);
            })->whereHas('account', function($q) {
                $q->where('code', 'like', '4%');
            })->sum('credit');

            // Sum all expenses
            $totalExpense = JournalEntryLine::whereHas('journalEntry', function($q) use ($period) {
                $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false);
            })->whereHas('account', function($q) {
                $q->where('code', 'like', '5%')->orWhere('code', 'like', '6%')->orWhere('code', 'like', '8%');
            })->sum('debit');

            $closingEntryRev = JournalEntry::create([
                'entry_date' => $monthEnd,
                'reference' => 'JP-' . $monthEnd->format('YmdHis'),
                'description' => 'Jurnal Penutup - Menutup Akun Pendapatan',
                'fiscal_period_id' => $period->id,
                'created_by' => $admin->id,
                'status' => 'posted',
                'is_closing' => true,
            ]);
            // Debit all individual revenue accounts (simplified to just one line for total in dummy)
            // But to be proper we debit the specific accounts. For dummy, we can just debit a placeholder or loop.
            // Let's loop the balances:
            $revBalances = JournalEntryLine::whereHas('journalEntry', function($q) use ($period) {
                    $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false);
                })->whereHas('account', function($q) { $q->where('code', 'like', '4%'); })
                ->selectRaw('account_id, SUM(credit) - SUM(debit) as bal')
                ->groupBy('account_id')->get();
            
            foreach($revBalances as $bal) {
                if ($bal->bal > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $closingEntryRev->id,
                        'account_id' => $bal->account_id,
                        'debit' => $bal->bal,
                        'credit' => 0,
                    ]);
                }
            }
            JournalEntryLine::create([
                'journal_entry_id' => $closingEntryRev->id,
                'account_id' => $ikhtisarLabaRugi->id,
                'debit' => 0,
                'credit' => $totalRevenue,
            ]);


            $closingEntryExp = JournalEntry::create([
                'entry_date' => $monthEnd,
                'reference' => 'JP-' . $monthEnd->format('YmdHis') . '-2',
                'description' => 'Jurnal Penutup - Menutup Akun Beban',
                'fiscal_period_id' => $period->id,
                'created_by' => $admin->id,
                'status' => 'posted',
                'is_closing' => true,
            ]);
            JournalEntryLine::create([
                'journal_entry_id' => $closingEntryExp->id,
                'account_id' => $ikhtisarLabaRugi->id,
                'debit' => $totalExpense,
                'credit' => 0,
            ]);
            $expBalances = JournalEntryLine::whereHas('journalEntry', function($q) use ($period) {
                    $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false);
                })->whereHas('account', function($q) { $q->where(function($q2){$q2->where('code','like','5%')->orWhere('code','like','6%')->orWhere('code','like','8%');}); })
                ->selectRaw('account_id, SUM(debit) - SUM(credit) as bal')
                ->groupBy('account_id')->get();
            
            foreach($expBalances as $bal) {
                if ($bal->bal > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $closingEntryExp->id,
                        'account_id' => $bal->account_id,
                        'debit' => 0,
                        'credit' => $bal->bal,
                    ]);
                }
            }

            // Close Ikhtisar to Modal
            $netProfit = $totalRevenue - $totalExpense;
            if ($netProfit != 0) {
                $closingEntryNet = JournalEntry::create([
                    'entry_date' => $monthEnd,
                    'reference' => 'JP-' . $monthEnd->format('YmdHis') . '-3',
                    'description' => 'Jurnal Penutup - Menutup Ikhtisar Laba Rugi ke Modal',
                    'fiscal_period_id' => $period->id,
                    'created_by' => $admin->id,
                    'status' => 'posted',
                    'is_closing' => true,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $closingEntryNet->id,
                    'account_id' => $ikhtisarLabaRugi->id,
                    'debit' => $netProfit > 0 ? $netProfit : 0,
                    'credit' => $netProfit < 0 ? abs($netProfit) : 0,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $closingEntryNet->id,
                    'account_id' => $modal->id,
                    'debit' => $netProfit < 0 ? abs($netProfit) : 0,
                    'credit' => $netProfit > 0 ? $netProfit : 0,
                ]);
            }

            // Move to next month
            $currentDate->addMonth();
        }

        // Keep Juli 2026 as active open period
        FiscalPeriod::firstOrCreate(
            ['name' => 'Juli 2026'],
            [
                'start_date' => Carbon::create(2026, 7, 1),
                'end_date' => Carbon::create(2026, 7, 31),
                'status' => 'open',
            ]
        );
    }
}
