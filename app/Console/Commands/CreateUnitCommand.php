<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Services\Coa\CoaSynchronizer;
use App\Services\FiscalCalendar;
use App\Support\AccessAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perintah DDL tidak bisa dibungkus transaksi, jadi setiap langkah aman diulang bila gagal di tengah jalan.
 */
class CreateUnitCommand extends Command
{
    protected $signature = 'finlog:create-unit
        {code : Kode unit, huruf kecil}
        {--name= : Nama unit}
        {--database= : Nama database unit}
        {--start=2026-10-01 : Awal pembukuan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Buat unit baru dalam status persiapan: database, skema, COA, akun antar unit, dan periode.';

    public function handle(CoaSynchronizer $synchronizer, FiscalCalendar $calendar): int
    {
        $code = $this->argument('code');
        $database = (string) $this->option('database');
        $name = (string) $this->option('name');

        if (! preg_match('/^[a-z][a-z0-9_]{1,28}$/', $code)) {
            $this->error('Kode unit hanya boleh huruf kecil, angka, dan garis bawah.');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{1,63}$/', $database)) {
            $this->error('Isi --database dengan nama database yang valid.');

            return self::FAILURE;
        }

        if ($name === '') {
            $this->error('Isi --name dengan nama unit.');

            return self::FAILURE;
        }

        $host = DB::connection('central')->getConfig('host');
        $this->info("Tujuan: {$host} / {$database}");

        $conflict = Unit::where('database', $database)->where('code', '!=', $code)->first();

        if ($conflict) {
            $this->error("Database {$database} sudah dipakai unit {$conflict->code}.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Buat atau lanjutkan unit {$code}?")) {
            return self::FAILURE;
        }

        $this->line('1. Database');
        // Koneksi tanpa database dipilih, karena CREATE DATABASE mengakhiri transaksi yang sedang berjalan.
        DB::connection('unit')->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $unit = Unit::firstOrCreate(['code' => $code], [
            'name' => $name,
            'database' => $database,
            'status' => Unit::STATUS_PREPARATION,
            'books_start_date' => $this->option('start'),
            'sort_order' => (int) Unit::query()->max('sort_order') + 1,
            'intercompany_slot' => (int) Unit::query()->max('intercompany_slot') + 1,
        ]);

        if ($unit->wasRecentlyCreated) {
            AccessAudit::record('unit.created', 'unit', $unit->id, null, ['code' => $code, 'database' => $database]);
        }

        $this->line('2. Skema');
        $result = $this->call('finlog:migrate-units', ['--unit' => $code, '--force' => true]);

        if ($result !== self::SUCCESS) {
            return $result;
        }

        $this->call('finlog:drop-identity-tables', ['unit' => $code, '--force' => true]);

        $this->line('3. COA');
        $synchronizer->sync($unit);
        $copied = $synchronizer->copyTemplate($unit);
        $this->line("   {$copied} sub-akun template disalin");

        $this->line('4. Akun antar unit di unit lain');
        foreach (Unit::ordered()->where('id', '!=', $unit->id)->get() as $other) {
            $synchronizer->sync($other);
        }

        $this->line('5. Periode');
        $added = $calendar->syncUnit($unit);
        $this->line("   {$added} periode ditambahkan");

        $this->info("Unit {$code} siap dalam status {$unit->fresh()->status}. Sesuaikan sub-akun, input saldo awal, lalu jalankan finlog:activate-unit.");

        return self::SUCCESS;
    }
}
