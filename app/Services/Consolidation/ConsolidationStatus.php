<?php

namespace App\Services\Consolidation;

use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;

/**
 * Laporan gabungan hanya FINAL bila semua unit sudah tutup buku dan semua pasangan antar unit cocok.
 */
class ConsolidationStatus
{
    public const FINAL = 'FINAL';

    public const PROVISIONAL = 'SEMENTARA';

    public function __construct(private IntercompanyReconciler $reconciler) {}

    public function for(ConsolidationScope $scope, array $pairs): array
    {
        $units = array_values($scope->mapUnits(fn (Unit $unit, FiscalPeriod $period) => [
            'code' => $unit->code,
            'name' => $unit->name,
            'period_status' => $period->status,
            'closed' => $period->status === 'closed',
            'unposted' => JournalEntry::where('fiscal_period_id', $period->id)->whereIn('status', ['draft', 'unapproved'])->count(),
        ]));

        $excluded = array_map(fn (array $item) => [
            'code' => $item['unit']->code,
            'name' => $item['unit']->name,
            'reason' => $item['reason'],
            'blocks_final' => $item['blocks_final'],
        ], $scope->excluded);

        $differences = collect($pairs)->where('matched', false)->count();
        $reasons = [];

        foreach ($units as $unit) {
            if (! $unit['closed']) {
                $reasons[] = "{$unit['name']} belum tutup buku";
            }

            if ($unit['unposted'] > 0) {
                $reasons[] = "{$unit['name']} masih punya {$unit['unposted']} jurnal belum posted, dan angkanya belum termasuk di laporan ini";
            }
        }

        foreach ($excluded as $unit) {
            if ($unit['blocks_final']) {
                $reasons[] = "{$unit['name']}: {$unit['reason']}";
            }
        }

        if ($differences > 0) {
            $reasons[] = "{$differences} pasangan antar unit belum cocok";
        }

        if ($units === []) {
            $reasons[] = 'Belum ada unit yang dapat dihitung pada periode ini';
        }

        return [
            'label' => $reasons === [] ? self::FINAL : self::PROVISIONAL,
            'units' => $units,
            'excluded' => $excluded,
            'differences' => $differences,
            'reasons' => $reasons,
        ];
    }
}
