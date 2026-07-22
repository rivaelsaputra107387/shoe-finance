<?php

// Boot Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$action = $argv[1] ?? 'insert';

if ($action === 'insert') {
    echo "Inserting test transaction for Scenario B...\n";

    DB::transaction(function () {
        // Clean up previous if any
        JournalEntry::where('reference', 'TEST-SCENARIO-B')->delete();

        $owner = User::where('email', 'owner@shoeworkshop.com')->first();
        $period = FiscalPeriod::where('name', 'Juni 2026')->first();
        
        $accBeban = Account::where('code', '7230')->first(); // Beban Lain-lain Non-Operasional
        $accKas = Account::where('code', '1110')->first(); // Kas

        if (!$owner || !$period || !$accBeban || !$accKas) {
            throw new Exception("Missing dependencies for Scenario B test");
        }

        $entry = JournalEntry::create([
            'entry_date' => '2026-06-30',
            'reference' => 'TEST-SCENARIO-B',
            'description' => 'Uji Beban Lain-lain (Skenario B)',
            'fiscal_period_id' => $period->id,
            'created_by' => $owner->id,
            'is_closing' => false,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $accBeban->id,
            'debit' => 50000.00,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $accKas->id,
            'debit' => 0,
            'credit' => 50000.00,
        ]);

        if (!$entry->is_balanced) {
            throw new Exception("Test entry is not balanced!");
        }

        echo "Test entry TEST-SCENARIO-B successfully inserted.\n";
    });
} elseif ($action === 'delete') {
    echo "Deleting test transaction TEST-SCENARIO-B to clean up database...\n";
    $deleted = JournalEntry::where('reference', 'TEST-SCENARIO-B')->delete();
    echo "Deleted $deleted transaction entries.\n";
} else {
    echo "Unknown action: $action\n";
}
