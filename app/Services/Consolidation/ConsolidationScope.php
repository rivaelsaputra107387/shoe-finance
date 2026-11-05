<?php

namespace App\Services\Consolidation;

use App\Models\Central\CalendarPeriod;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Support\UnitContext;

/**
 * Unit yang dihitung untuk satu periode kalender. ID periode bisa berbeda antar unit,
 * jadi periode tiap unit dicari lewat tanggal mulainya.
 */
class ConsolidationScope
{
    /**
     * @param  array<int, array{unit: Unit, period: FiscalPeriod}>  $included
     * @param  array<int, array{unit: Unit, reason: string, blocks_final: bool}>  $excluded
     */
    private function __construct(
        public CalendarPeriod $period,
        public array $included,
        public array $excluded,
    ) {}

    public static function for(CalendarPeriod $period): self
    {
        $context = app(UnitContext::class);
        $start = $period->start_date->toDateString();
        $included = [];
        $excluded = [];

        foreach (Unit::ordered()->get() as $unit) {
            if ($unit->isInPreparation()) {
                $excluded[] = ['unit' => $unit, 'reason' => 'Masih persiapan', 'blocks_final' => false];

                continue;
            }

            if ($unit->books_start_date->toDateString() > $start) {
                $excluded[] = ['unit' => $unit, 'reason' => 'Pembukuan belum dimulai pada periode ini', 'blocks_final' => false];

                continue;
            }

            if ($unit->isInactive() && $unit->deactivated_at && $unit->deactivated_at->toDateString() < $start) {
                $excluded[] = ['unit' => $unit, 'reason' => 'Sudah nonaktif pada periode ini', 'blocks_final' => false];

                continue;
            }

            $fiscalPeriod = $context->run($unit, fn () => FiscalPeriod::where('start_date', $start)->first());

            if (! $fiscalPeriod) {
                $excluded[] = ['unit' => $unit, 'reason' => 'Periode ini belum ada di unit', 'blocks_final' => true];

                continue;
            }

            $included[] = ['unit' => $unit, 'period' => $fiscalPeriod];
        }

        return new self($period, $included, $excluded);
    }

    /**
     * Jalankan callback pada setiap unit yang dihitung.
     *
     * @return array<string, mixed> Hasil per kode unit
     */
    public function mapUnits(callable $callback): array
    {
        $context = app(UnitContext::class);
        $results = [];

        foreach ($this->included as $item) {
            $results[$item['unit']->code] = $context->run(
                $item['unit'],
                fn () => $callback($item['unit'], $item['period']),
            );
        }

        return $results;
    }

    public function unitCodes(): array
    {
        return array_map(fn (array $item) => $item['unit']->code, $this->included);
    }

    public function unitColumns(): array
    {
        return array_map(fn (array $item) => ['code' => $item['unit']->code, 'name' => $item['unit']->name], $this->included);
    }
}
