<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pengguna pindah ke database pusat, dan MySQL tidak mendukung foreign key lintas database.
     * Kolom ID pengguna tetap ada. Daftar dibaca dari skema karena riwayat tiap database bisa berbeda.
     */
    public function up(): void
    {
        $connection = DB::connection();

        $foreignKeys = $connection->select(
            'SELECT TABLE_NAME AS table_name, CONSTRAINT_NAME AS constraint_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ? AND TABLE_NAME NOT IN (?, ?)',
            [$connection->getDatabaseName(), 'users', 'users', 'sessions']
        );

        foreach ($foreignKeys as $foreignKey) {
            $connection->statement("ALTER TABLE `{$foreignKey->table_name}` DROP FOREIGN KEY `{$foreignKey->constraint_name}`");
        }
    }

    public function down(): void
    {
        // Tidak dipulihkan: setelah pengguna pindah, tabel users di database unit bukan lagi sumber data.
    }
};
