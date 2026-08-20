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
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('workshop_item_id')->nullable();
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->string('spk_number')->nullable();
            $table->unsignedBigInteger('material_id');
            $table->string('material_name');
            $table->string('specification')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('estimated_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
