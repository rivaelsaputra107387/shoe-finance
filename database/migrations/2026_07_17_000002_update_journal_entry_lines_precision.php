<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update decimal precision from (15,2) to (18,2) to match ERD spec.
     */
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->decimal('debit', 18, 2)->default(0)->change();
            $table->decimal('credit', 18, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->decimal('debit', 15, 2)->default(0)->change();
            $table->decimal('credit', 15, 2)->default(0)->change();
        });
    }
};
