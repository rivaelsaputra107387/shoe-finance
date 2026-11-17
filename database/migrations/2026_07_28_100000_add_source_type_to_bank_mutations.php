<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_mutations', function (Blueprint $table) {
            $table->string('source_type')->default('excel')->after('bank_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_mutations', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};
