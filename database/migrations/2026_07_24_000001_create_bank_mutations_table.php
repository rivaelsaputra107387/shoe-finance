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
        Schema::create('bank_mutations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description', 500);
            $table->decimal('amount', 15, 2);
            $table->string('bank_source', 50); // BCA, MANDIRI, dll.
            $table->enum('mutation_type', ['IN', 'OUT']);
            
            // Decoupled API Reference
            $table->string('matched_invoice_ref')->nullable();
            $table->json('matched_invoice_data')->nullable();
            
            // Relasi ke Jurnal
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            
            // Status Enum
            $table->enum('status', ['pending', 'matched', 'drafted', 'unapproved', 'posted'])->default('pending');
            
            // Audit Kolom Lengkap
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_mutations');
    }
};
