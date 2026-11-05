<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Services\FiscalCalendar;
use Illuminate\Console\Command;

class CalendarSyncCommand extends Command
{
    protected $signature = 'finlog:calendar-sync {--import-from= : Kode unit yang periodenya dimasukkan ke kalender terlebih dahulu}';

    protected $description = 'Tambahkan periode kalender yang belum ada ke semua unit. Aman diulang.';

    public function handle(FiscalCalendar $calendar): int
    {
        if ($code = $this->option('import-from')) {
            $unit = Unit::where('code', $code)->first();

            if (! $unit) {
                $this->error('Unit tidak ditemukan.');

                return self::FAILURE;
            }

            $this->info($calendar->importFrom($unit)." periode dimasukkan dari {$unit->code}.");
        }

        $failed = $calendar->syncAllUnits();

        if ($failed !== []) {
            $this->error('Gagal pada unit: '.implode(', ', $failed).'. Lihat log, lalu ulangi.');

            return self::FAILURE;
        }

        $this->info('Kalender sudah sama di semua unit.');

        return self::SUCCESS;
    }
}
