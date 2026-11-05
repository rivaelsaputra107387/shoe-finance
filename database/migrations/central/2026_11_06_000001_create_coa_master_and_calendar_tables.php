<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_master', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('type');
            $table->string('normal_balance');
            $table->string('report_category');
            $table->string('cash_flow_category')->nullable();
            $table->string('parent_code', 10)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });

        Schema::create('calendar_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date')->unique();
            $table->date('end_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_periods');
        Schema::dropIfExists('coa_master');
    }
};
