<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates audit_trails table per ERD §4.8 and SRS FR-18.
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('table_name', 50);
            $table->unsignedBigInteger('record_id');
            $table->string('action', 20); // create|update|delete|close_period
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
