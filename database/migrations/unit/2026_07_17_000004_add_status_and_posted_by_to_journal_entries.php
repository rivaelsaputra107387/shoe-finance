<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add draft/posted status flow to journal_entries per ERD/SRS.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->enum('status', ['draft', 'posted'])->default('posted')->after('is_closing');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            $table->timestamp('posted_at')->nullable()->after('posted_by');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['posted_by']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'posted_by', 'posted_at']);
        });
    }
};
