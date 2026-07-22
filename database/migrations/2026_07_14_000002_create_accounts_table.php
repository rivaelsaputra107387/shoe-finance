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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // e.g. "1110", "4110"
            $table->string('name');
            $table->enum('type', ['Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban']);
            $table->enum('normal_balance', ['Debet', 'Kredit']);
            $table->enum('report_category', ['Neraca', 'Laba Rugi']);
            $table->enum('cash_flow_category', ['Operasi', 'Investasi', 'Pendanaan'])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('report_category');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
