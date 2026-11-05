<?php

namespace App\Services\Consolidation;

use App\Models\Account;
use App\Models\Central\CalendarPeriod;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Services\BalanceSheetService;
use App\Services\EquityStatementService;
use App\Services\IncomeStatementService;
use App\Support\UnitContext;

/**
 * Menyusun laporan gabungan. Setiap kolom unit adalah hasil service laporan yang sama dengan laporan per unit,
 * jadi rumusnya tidak ditulis dua kali. Hanya membaca data.
 *
 * Satu baris: label, nilai per unit, eliminasi, dan gabungan (jumlah unit ditambah eliminasi).
 */
class ConsolidatedReports
{
    private const TOLERANCE = 0.01;

    public function __construct(
        private IntercompanyReconciler $reconciler,
        private ConsolidationStatus $status,
    ) {}

    public function incomeStatement(CalendarPeriod $period): array
    {
        $scope = ConsolidationScope::for($period);
        $codes = $scope->unitCodes();

        $data = $scope->mapUnits(fn (Unit $unit, FiscalPeriod $fiscalPeriod) => [
            'report' => (new IncomeStatementService)->generate($fiscalPeriod->id),
            'accounts' => $this->accountMap(),
        ]);

        $section = fn (string $key) => $this->groupByHeader($data, $key, $codes);

        $revenue = $section('revenue');
        $hpp = $section('hpp');
        $operational = $section('operational_expenses');
        $otherRevenue = $section('other_revenue');
        $otherExpenses = $section('other_expenses');
        $admin = $section('admin_expenses');

        $totalRevenue = $this->total('Total Pendapatan', $revenue, $codes);
        $totalHpp = $this->total('Total HPP', $hpp, $codes);
        $grossProfit = $this->combine('Laba Kotor', [[$totalRevenue, 1], [$totalHpp, -1]], $codes);
        $totalOperational = $this->total('Total Beban Operasional', $operational, $codes);
        $operatingProfit = $this->combine('Laba Operasi', [[$grossProfit, 1], [$totalOperational, -1]], $codes);
        $totalOtherRevenue = $this->total('Total Pendapatan Lain-lain', $otherRevenue, $codes);
        $totalOtherExpenses = $this->total('Total Beban Lain-lain', $otherExpenses, $codes);
        $totalAdmin = $this->total('Total Beban Administrasi & Pajak', $admin, $codes);

        // Pendapatan dan beban antar unit dihapus dari sisinya masing-masing. Bila keduanya tidak sama,
        // selisihnya dikembalikan di sini, supaya laba bersih gabungan tetap sama dengan jumlah laba tiap unit.
        $eliminatedNet = $totalRevenue['elimination'] - $totalHpp['elimination'] - $totalOperational['elimination']
            + $totalOtherRevenue['elimination'] - $totalOtherExpenses['elimination'] - $totalAdmin['elimination'];
        $difference = abs($eliminatedNet) >= self::TOLERANCE
            ? [$this->line('Selisih Antar Unit (belum cocok)', array_fill_keys($codes, 0.0), -$eliminatedNet)]
            : [];

        $netProfit = $this->line(
            'Laba Bersih',
            collect($data)->map(fn (array $item) => (float) $item['report']['net_profit'])->all(),
            0.0,
            'total',
        );

        $rows = [
            $this->heading('Pendapatan Usaha'), ...$revenue, $totalRevenue,
            $this->heading('Harga Pokok Penjualan'), ...$hpp, $totalHpp, $grossProfit,
            $this->heading('Beban Operasional'), ...$operational, $totalOperational, $operatingProfit,
            $this->heading('Pendapatan Lain-lain'), ...$otherRevenue, $totalOtherRevenue,
            $this->heading('Beban Lain-lain'), ...$otherExpenses, $totalOtherExpenses,
            $this->heading('Beban Administrasi & Pajak'), ...$admin, $totalAdmin,
            ...$difference,
            $netProfit,
        ];

        return $this->report('Laba Rugi Gabungan', $scope, $rows, [
            'net_profit' => $netProfit['combined'],
        ]);
    }

    public function balanceSheet(CalendarPeriod $period): array
    {
        $scope = ConsolidationScope::for($period);
        $sections = $this->balanceSheetSections($scope);
        $rows = $sections['rows'];

        return $this->report('Neraca Gabungan', $scope, $rows, [
            'is_balanced' => $sections['is_balanced'],
            'balanced_columns' => $sections['balanced_columns'],
            'total_assets' => $sections['total_assets']['combined'],
            'total_equity' => $sections['total_equity']['combined'],
        ]);
    }

    public function equityStatement(CalendarPeriod $period): array
    {
        $scope = ConsolidationScope::for($period);
        $codes = $scope->unitCodes();

        $data = $scope->mapUnits(fn (Unit $unit, FiscalPeriod $fiscalPeriod) => (new EquityStatementService)->generate($fiscalPeriod->id));
        $column = fn (string $key, int $sign = 1) => collect($data)->map(fn (array $report) => $sign * (float) $report[$key])->all();

        $balanceSheet = $this->balanceSheetSections($scope);
        $rows = [
            $this->line('Modal Awal Periode', $column('beginning_capital')),
            $this->line('Setoran Modal', $column('additional_capital')),
            $this->line('Laba Bersih Periode Ini', $column('net_profit')),
            $this->line('Prive', $column('prive', -1)),
        ];

        if (abs($balanceSheet['difference']) >= self::TOLERANCE) {
            $rows[] = $this->line('Selisih Antar Unit (belum cocok)', array_fill_keys($codes, 0.0), $balanceSheet['difference']);
        }

        $ending = $this->line('Modal Akhir Periode', $column('ending_capital'), $balanceSheet['difference'], 'total');
        $rows[] = $ending;

        return $this->report('Perubahan Ekuitas Gabungan', $scope, $rows, [
            'ending_capital' => $ending['combined'],
            'total_equity' => $balanceSheet['total_equity']['combined'],
            'matches_balance_sheet' => abs($ending['combined'] - $balanceSheet['total_equity']['combined']) < self::TOLERANCE,
        ]);
    }

    public function reconciliation(CalendarPeriod $period): array
    {
        $scope = ConsolidationScope::for($period);
        $pairs = $this->reconciler->reconcile($scope);

        return [
            'title' => 'Cek Kecocokan Antar Unit',
            'period' => $this->periodData($period),
            'status' => $this->status->for($scope, $pairs),
            'pairs' => $this->withLines($scope, $pairs),
        ];
    }

    private function balanceSheetSections(ConsolidationScope $scope): array
    {
        $codes = $scope->unitCodes();

        $data = $scope->mapUnits(fn (Unit $unit, FiscalPeriod $fiscalPeriod) => [
            'report' => (new BalanceSheetService)->generate($fiscalPeriod->id),
            'accounts' => $this->accountMap(),
        ]);

        $section = fn (string $key) => $this->groupByHeader($data, $key, $codes);

        $currentAssets = $section('current_assets');
        $fixedAssets = $section('fixed_assets');
        $currentLiabilities = $section('current_liabilities');
        $longTermLiabilities = $section('long_term_liabilities');
        $equity = $this->equityRows($data, $codes);

        $totalCurrentAssets = $this->total('Total Aset Lancar', $currentAssets, $codes);
        $totalFixedAssets = $this->total('Total Aset Tetap', $fixedAssets, $codes);
        $totalAssets = $this->combine('Total Aset', [[$totalCurrentAssets, 1], [$totalFixedAssets, 1]], $codes, 'total');
        $totalCurrentLiabilities = $this->total('Total Kewajiban Lancar', $currentLiabilities, $codes);
        $totalLongTermLiabilities = $this->total('Total Kewajiban Jangka Panjang', $longTermLiabilities, $codes);
        $totalLiabilities = $this->combine('Total Kewajiban', [[$totalCurrentLiabilities, 1], [$totalLongTermLiabilities, 1]], $codes);

        // Bila piutang dan utang antar unit tidak sama, selisihnya masuk ekuitas pada kolom eliminasi.
        $difference = round($totalAssets['elimination'] - $totalLiabilities['elimination'], 2);

        if (abs($difference) >= self::TOLERANCE) {
            $equity[] = $this->line('Selisih Antar Unit (belum cocok)', array_fill_keys($codes, 0.0), $difference);
        }

        $totalEquity = $this->total('Total Ekuitas', $equity, $codes);
        $totalLiabilitiesAndEquity = $this->combine('Total Kewajiban dan Ekuitas', [[$totalLiabilities, 1], [$totalEquity, 1]], $codes, 'total');

        $balancedColumns = collect($codes)
            ->mapWithKeys(fn (string $code) => [$code => abs($totalAssets['amounts'][$code] - $totalLiabilitiesAndEquity['amounts'][$code]) < self::TOLERANCE])
            ->all();
        $balancedColumns['elimination'] = abs($totalAssets['elimination'] - $totalLiabilitiesAndEquity['elimination']) < self::TOLERANCE;
        $balancedColumns['combined'] = abs($totalAssets['combined'] - $totalLiabilitiesAndEquity['combined']) < self::TOLERANCE;

        return [
            'rows' => [
                $this->heading('Aset Lancar'), ...$currentAssets, $totalCurrentAssets,
                $this->heading('Aset Tetap'), ...$fixedAssets, $totalFixedAssets, $totalAssets,
                $this->heading('Kewajiban Lancar'), ...$currentLiabilities, $totalCurrentLiabilities,
                $this->heading('Kewajiban Jangka Panjang'), ...$longTermLiabilities, $totalLongTermLiabilities, $totalLiabilities,
                $this->heading('Ekuitas'), ...$equity, $totalEquity, $totalLiabilitiesAndEquity,
            ],
            'difference' => $difference,
            'total_assets' => $totalAssets,
            'total_equity' => $totalEquity,
            'is_balanced' => ! in_array(false, $balancedColumns, true),
            'balanced_columns' => $balancedColumns,
        ];
    }

    /**
     * Modal Disetor, Laba Ditahan, Laba Belum Ditutup, dan Prive tetap terlihat per unit, tanpa eliminasi investasi.
     */
    private function equityRows(array $data, array $codes): array
    {
        $rows = [];

        foreach ($data as $unitCode => $item) {
            foreach (collect($item['report']['equity'])->all() as $row) {
                $key = $row['code'];

                $rows[$key] ??= ['label' => $row['name'], 'code' => $key === '-' ? null : $key, 'amounts' => array_fill_keys($codes, 0.0)];
                $rows[$key]['amounts'][$unitCode] += (float) $row['balance'];
            }
        }

        uksort($rows, fn ($a, $b) => ($a === '-' ? '3999' : $a) <=> ($b === '-' ? '3999' : $b));

        return array_values(array_map(
            fn (array $row) => $this->line($row['label'], $row['amounts'], 0.0, 'row', $row['code']),
            $rows,
        ));
    }

    /**
     * Sub-akun tiap unit bisa berbeda, jadi yang disejajarkan adalah akun induknya. Rincian sub-akun tetap dibawa.
     */
    private function groupByHeader(array $data, string $key, array $codes): array
    {
        $groups = [];

        foreach ($data as $unitCode => $item) {
            foreach (collect($item['report'][$key])->all() as $row) {
                $account = $item['accounts'][$row['code']] ?? null;
                $headerCode = $account['parent_code'] ?? $row['code'];
                $amount = (float) $row['balance'];

                $groups[$headerCode] ??= [
                    'code' => $headerCode,
                    'label' => $account['parent_name'] ?? $row['name'],
                    'amounts' => array_fill_keys($codes, 0.0),
                    'elimination' => 0.0,
                    'details' => [],
                ];

                $groups[$headerCode]['amounts'][$unitCode] += $amount;

                if ($account['is_intercompany'] ?? false) {
                    $groups[$headerCode]['elimination'] -= $amount;
                }

                $groups[$headerCode]['details'][$row['code']] ??= [
                    'code' => $row['code'],
                    'label' => $row['name'],
                    'amounts' => array_fill_keys($codes, 0.0),
                ];
                $groups[$headerCode]['details'][$row['code']]['amounts'][$unitCode] += $amount;
            }
        }

        ksort($groups);

        return array_values(array_map(function (array $group) {
            $line = $this->line($group['label'], $group['amounts'], $group['elimination'], 'row', $group['code']);
            ksort($group['details']);
            $line['details'] = array_values($group['details']);

            return $line;
        }, $groups));
    }

    private function accountMap(): array
    {
        return Account::with('parent')->get()->mapWithKeys(fn (Account $account) => [$account->code => [
            'parent_code' => $account->parent?->code,
            'parent_name' => $account->parent?->name,
            'is_intercompany' => (bool) $account->is_intercompany,
        ]])->all();
    }

    private function line(string $label, array $amounts, float $elimination = 0.0, string $type = 'row', ?string $code = null): array
    {
        $amounts = array_map(fn ($value) => round((float) $value, 2), $amounts);
        $elimination = round($elimination, 2);

        return [
            'type' => $type,
            'code' => $code,
            'label' => $label,
            'amounts' => $amounts,
            'elimination' => $elimination,
            'combined' => round(array_sum($amounts) + $elimination, 2),
            'details' => [],
        ];
    }

    private function heading(string $label): array
    {
        return ['type' => 'heading', 'code' => null, 'label' => $label, 'amounts' => [], 'elimination' => 0.0, 'combined' => 0.0, 'details' => []];
    }

    private function total(string $label, array $rows, array $codes): array
    {
        $amounts = array_fill_keys($codes, 0.0);
        $elimination = 0.0;

        foreach ($rows as $row) {
            foreach ($codes as $code) {
                $amounts[$code] += $row['amounts'][$code] ?? 0.0;
            }
            $elimination += $row['elimination'];
        }

        return $this->line($label, $amounts, $elimination, 'subtotal');
    }

    /**
     * @param  array<int, array{0: array, 1: int}>  $parts  Baris dan tandanya
     */
    private function combine(string $label, array $parts, array $codes, string $type = 'subtotal'): array
    {
        $amounts = array_fill_keys($codes, 0.0);
        $elimination = 0.0;

        foreach ($parts as [$row, $sign]) {
            foreach ($codes as $code) {
                $amounts[$code] += $sign * ($row['amounts'][$code] ?? 0.0);
            }
            $elimination += $sign * $row['elimination'];
        }

        return $this->line($label, $amounts, $elimination, $type);
    }

    private function report(string $title, ConsolidationScope $scope, array $rows, array $checks): array
    {
        $pairs = $this->reconciler->reconcile($scope);

        return [
            'title' => $title,
            'period' => $this->periodData($scope->period),
            'units' => $scope->unitColumns(),
            'rows' => $rows,
            'checks' => $checks,
            'status' => $this->status->for($scope, $pairs),
        ];
    }

    private function periodData(CalendarPeriod $period): array
    {
        return [
            'name' => $period->name,
            'start_date' => $period->start_date->toDateString(),
            'end_date' => $period->end_date->toDateString(),
        ];
    }

    private function withLines(ConsolidationScope $scope, array $pairs): array
    {
        $byCode = collect($scope->included)->keyBy(fn (array $item) => $item['unit']->code);
        $context = app(UnitContext::class);

        return array_map(function (array $pair) use ($byCode, $context) {
            $cumulative = $pair['type'] === 'balance_sheet';
            $lines = function (?string $unitCode, ?string $accountCode) use ($byCode, $context, $cumulative) {
                $item = $unitCode ? $byCode->get($unitCode) : null;

                if (! $item || ! $accountCode) {
                    return [];
                }

                return $context->run($item['unit'], fn () => $this->reconciler->linesFor($item['unit'], $item['period'], $accountCode, $cumulative));
            };

            return $pair + [
                'lines_a' => $pair['matched'] ? [] : $lines($pair['unit_a'], $pair['account_a']),
                'lines_b' => $pair['matched'] ? [] : $lines($pair['unit_b'], $pair['account_b']),
            ];
        }, $pairs);
    }
}
