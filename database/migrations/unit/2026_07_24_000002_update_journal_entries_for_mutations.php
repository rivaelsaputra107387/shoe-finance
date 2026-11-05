<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, change the ENUM definition.
        // For SQLite, ENUM is just string, but we can attempt to change it.
        // We will just try using standard change, if it fails because of sqlite, we catch it or ignore.
        
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
        });

        // To safely alter enum across drivers without dbal issues in older setups, 
        // raw statement is sometimes used, but Laravel 11 handles it.
        try {
            DB::statement("ALTER TABLE journal_entries MODIFY COLUMN status ENUM('draft', 'unapproved', 'posted') DEFAULT 'draft'");
        } catch (\Exception $e) {
            // SQLite or driver that doesn't support MODIFY COLUMN natively.
            // In SQLite, the existing status column allows any string anyway, so no change needed.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn(['submitted_by', 'submitted_at']);
        });
        
        try {
            DB::statement("ALTER TABLE journal_entries MODIFY COLUMN status ENUM('draft', 'posted') DEFAULT 'posted'");
        } catch (\Exception $e) {
            // Ignore
        }
    }
};
