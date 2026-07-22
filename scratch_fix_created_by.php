<?php

use App\Models\User;
use App\Models\JournalEntry;

// Load Laravel Bootstrap
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Find the staff user
$staff = User::where('email', 'staff@shoeworkshop.com')->first();

if (!$staff) {
    echo "ERROR: User staff@shoeworkshop.com tidak ditemukan.\n";
    exit(1);
}

// 2. Update all journal entries to be created by the staff user
$updated = JournalEntry::where('created_by', '!=', $staff->id)
    ->update(['created_by' => $staff->id]);

echo "SUCCESS: Berhasil memperbarui {$updated} entri jurnal agar dibuat oleh Staff Kasir.\n";
