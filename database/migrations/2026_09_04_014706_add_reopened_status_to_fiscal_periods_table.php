<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE fiscal_periods MODIFY COLUMN status ENUM('open', 'closed', 'reopened') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE fiscal_periods MODIFY COLUMN status ENUM('open', 'closed') DEFAULT 'open'");
    }
};
