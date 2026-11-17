<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Percakapan Baru');
            $table->timestamps();
        });

        // Seed a default session for existing logs if any
        $usersWithLogs = DB::table('ai_chat_logs')->select('user_id')->distinct()->get();
        foreach ($usersWithLogs as $u) {
            $sessionId = DB::table('ai_chat_sessions')->insertGetId([
                'user_id' => $u->user_id,
                'title' => 'Sesi Default',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Schema::table('ai_chat_logs', function (Blueprint $table) use ($u, $sessionId) {
                // If it's the first time running, we add the column, then update.
                // Wait, Schema::table doesn't let us conditionally update in the closure like this easily.
            });
        }
        
        // Actually, let's just add the column nullable first, update it, then make it non-nullable.
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->after('user_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
        });

        foreach ($usersWithLogs as $u) {
            // Find or create default session
            $session = DB::table('ai_chat_sessions')->where('user_id', $u->user_id)->first();
            DB::table('ai_chat_logs')->where('user_id', $u->user_id)->update(['session_id' => $session->id]);
        }

        Schema::table('ai_chat_logs', function (Blueprint $table) {
            // Un-nullable it now
            $table->unsignedBigInteger('session_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropForeign(['session_id']);
            $table->dropColumn('session_id');
        });
        
        Schema::dropIfExists('ai_chat_sessions');
    }
};
