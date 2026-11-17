<?php

namespace App\Services;

use App\Models\Central\CalendarPeriod;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Support\UnitContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Kalender periode bersama semua unit. Tanggal periode diatur di pusat, status buka/tutup milik tiap unit.
 * Penulisan lintas database tidak bisa satu transaksi, jadi semua langkah aman untuk diulang.
 */
class FiscalCalendar
{
    public function __construct(private UnitContext $context) {}

    /**
     * @return string[] Kode unit yang gagal, untuk diulang dengan finlog:calendar-sync
     */
    public function addPeriod(string $name, string $startDate, string $endDate): array
    {
        CalendarPeriod::firstOrCreate(['start_date' => $startDate], ['name' => $name, 'end_date' => $endDate]);

        return $this->syncAllUnits();
    }

    /**
     * Periode berikutnya untuk unit aktif, dipakai tutup buku. Unit lain disusulkan setelah transaksi selesai.
     */
    public function nextPeriodForActiveUnit(CarbonInterface $startDate, CarbonInterface $endDate, string $name): FiscalPeriod
    {
        $calendar = CalendarPeriod::firstOrCreate(
            ['start_date' => $startDate->toDateString()],
            ['name' => $name, 'end_date' => $endDate->toDateString()],
        );

        return $this->ensurePeriod($calendar);
    }

    /**
     * @return string[] Kode unit yang gagal
     */
    public function syncAllUnits(): array
    {
        $failed = [];

        foreach (Unit::ordered()->where('status', '!=', Unit::STATUS_INACTIVE)->get() as $unit) {
            try {
                $this->syncUnit($unit);
            } catch (\Throwable $e) {
                Log::error("Sinkronisasi kalender unit {$unit->code} gagal: {$e->getMessage()}");
                $failed[] = $unit->code;
            }
        }

        return $failed;
    }

    /**
     * @return int Jumlah periode yang ditambahkan
     */
    public function syncUnit(Unit $unit): int
    {
        return $this->context->run($unit, function () use ($unit) {
            $added = 0;

            $periods = CalendarPeriod::where('start_date', '>=', $unit->books_start_date->toDateString())
                ->orderBy('start_date')
                ->get();

            foreach ($periods as $calendar) {
                if ($this->ensurePeriod($calendar)->wasRecentlyCreated) {
                    $added++;
                }
            }

            return $added;
        });
    }

    /**
     * Masukkan periode sebuah unit ke kalender, dipakai sekali saat kalender pertama dibuat.
     *
     * @return int Jumlah periode yang dimasukkan
     */
    public function importFrom(Unit $unit): int
    {
        return $this->context->run($unit, function () {
            $imported = 0;

            foreach (FiscalPeriod::orderBy('start_date')->get() as $period) {
                $calendar = CalendarPeriod::firstOrCreate(
                    ['start_date' => $period->start_date->toDateString()],
                    ['name' => $period->name, 'end_date' => $period->end_date->toDateString()],
                );

                if ($calendar->wasRecentlyCreated) {
                    $imported++;
                }
            }

            return $imported;
        });
    }

    /**
     * Alasan periode tidak bisa dihapus dari kalender, atau null bila bisa.
     */
    public function removalBlocker(string $startDate): ?string
    {
        foreach (Unit::ordered()->get() as $unit) {
            $blocker = $this->context->run($unit, function () use ($startDate, $unit) {
                $period = FiscalPeriod::where('start_date', $startDate)->first();

                if (! $period) {
                    return null;
                }

                if ($period->status === 'closed') {
                    return "periode ini sudah ditutup di {$unit->name}";
                }

                $journals = JournalEntry::where('fiscal_period_id', $period->id)->count();

                return $journals > 0 ? "sudah ada {$journals} jurnal di {$unit->name}" : null;
            });

            if ($blocker) {
                return $blocker;
            }
        }

        return null;
    }

    public function removePeriod(string $startDate): void
    {
        foreach (Unit::ordered()->get() as $unit) {
            $this->context->run($unit, fn () => FiscalPeriod::where('start_date', $startDate)->delete());
        }

        CalendarPeriod::where('start_date', $startDate)->delete();
    }

    private function ensurePeriod(CalendarPeriod $calendar): FiscalPeriod
    {
        return FiscalPeriod::firstOrCreate(
            ['start_date' => $calendar->start_date->toDateString()],
            ['name' => $calendar->name, 'end_date' => $calendar->end_date->toDateString(), 'status' => 'open'],
        );
    }
}
