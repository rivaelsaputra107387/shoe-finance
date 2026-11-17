<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_intercompany')->default(false);
            $table->string('counterparty_unit_code', 30)->nullable();
        });

        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->unique('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->dropUnique(['start_date']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'is_intercompany', 'counterparty_unit_code']);
        });
    }
};
