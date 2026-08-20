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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('finlog_request_id')->unique();
            $table->string('request_number')->unique();
            $table->boolean('is_batch')->default(false);
            $table->integer('total_spks')->default(1);
            $table->longText('spk_list')->nullable();
            $table->unsignedBigInteger('primary_work_order_id')->nullable();
            $table->string('primary_spk_number')->nullable();
            $table->enum('type', ['SHOPPING', 'PRODUCTION_PO']);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'PURCHASED', 'RECEIVED', 'CANCELLED'])->default('PENDING');
            $table->unsignedBigInteger('requested_by_user_id');
            $table->string('requested_by_name');
            $table->string('requested_by_role');
            $table->decimal('total_estimated_cost', 15, 2);
            $table->text('notes')->nullable();
            $table->string('callback_webhook_url');
            $table->string('idempotency_key')->unique()->nullable();
            $table->longText('payload_raw')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('received_material_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
