<?php

namespace App\Services\Consolidation;

use App\Models\Account;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use App\Services\AccountBalanceService;
use Illuminate\Support\Collection;

/**
 * Mencocokkan akun antar unit. Piutang satu unit harus sama dengan utang unit lawannya (saldo sampai
 * akhir periode), dan pendapatan satu unit harus sama dengan beban unit lawannya (nilai periode ini).
 */
class IntercompanyReconciler
{
    private const TOLERANCE = 0.01;

    private const MAX_LINES = 200;

    /**
     * @return array<int, array> Satu baris untuk setiap pasangan yang punya nilai
     */
    public function reconcile(ConsolidationScope $scope): array
    {
        $balances = $scope->mapUnits(fn (Unit $unit, FiscalPeriod $period) => $this->unitBalances($period));
        $names = Unit::query()->pluck('name', 'code');
        $pairs = [];

        foreach ($balances as $unitCode => $rows) {
            foreach ($rows as $row) {
                if (! in_array($row['side'], ['receivable', 'revenue'], true)) {
                    continue;
                }

                $pairs[] = $this->pair($unitCode, $row, $balances, $names);
            }
        }

        // Sisi lawan yang tidak punya pasangan terbaca dari unit pencatat beban atau utang.
        foreach ($balances as $unitCode => $rows) {
            foreach ($rows as $row) {
                if (in_array($row['side'], ['payable', 'expense'], true) && ! isset($balances[$row['counterparty']])) {
                    $pairs[] = $this->pair($unitCode, $row, $balances, $names);
                }
            }
        }

        return array_values(array_filter(
            $pairs,
            fn (array $pair) => abs($pair['amount_a']) >= self::TOLERANCE || abs($pair['amount_b']) >= self::TOLERANCE,
        ));
    }

    public function hasDifferences(array $pairs): bool
    {
        return collect($pairs)->contains(fn (array $pair) => ! $pair['matched']);
    }

    /**
     * Baris jurnal di balik satu akun antar unit, termasuk yang belum posted, untuk menelusuri selisih.
     */
    public function linesFor(Unit $unit, FiscalPeriod $period, string $accountCode, bool $cumulative): array
    {
        return JournalEntryLine::query()
            ->join('journal_entries as je', 'je.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'journal_entry_lines.account_id')
            ->where('a.code', $accountCode)
            ->whereNull('je.deleted_at')
            ->where('je.is_closing', false)
            ->when(
                $cumulative,
                fn ($query) => $query->where('je.entry_date', '<=', $period->end_date),
                fn ($query) => $query->where('je.fiscal_period_id', $period->id),
            )
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->limit(self::MAX_LINES)
            ->get(['je.id as journal_id', 'je.entry_date', 'je.reference', 'je.description', 'je.status', 'journal_entry_lines.debit', 'journal_entry_lines.credit'])
            ->map(fn ($line) => [
                'journal_id' => $line->journal_id,
                'entry_date' => substr((string) $line->entry_date, 0, 10),
                'reference' => $line->reference,
                'description' => $line->description,
                'status' => $line->status,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ])
            ->all();
    }

    /**
     * @return Collection<int, array>
     */
    private function unitBalances(FiscalPeriod $period): Collection
    {
        $service = new AccountBalanceService;
        $cumulative = $service->getCumulativeTotalsUpTo($period->id);
        $periodOnly = $service->getPeriodTotals($period->id, excludeClosing: true);

        return Account::where('is_intercompany', true)->orderBy('code')->get()->map(function (Account $account) use ($cumulative, $periodOnly) {
            $side = match ($account->code[0]) {
                '1' => 'receivable',
                '2' => 'payable',
                '4' => 'revenue',
                default => 'expense',
            };

            $isBalanceSheet = in_array($side, ['receivable', 'payable'], true);
            $row = ($isBalanceSheet ? $cumulative : $periodOnly)->get($account->id);
            $debit = $row ? (float) $row->total_debit : 0.0;
            $credit = $row ? (float) $row->total_credit : 0.0;

            return [
                'side' => $side,
                'code' => $account->code,
                'name' => $account->name,
                'counterparty' => $account->counterparty_unit_code,
                'amount' => in_array($side, ['receivable', 'expense'], true) ? $debit - $credit : $credit - $debit,
            ];
        });
    }

    private function pair(string $unitCode, array $row, array $balances, Collection $names): array
    {
        $isSellingSide = in_array($row['side'], ['receivable', 'revenue'], true);
        $oppositeSide = ['receivable' => 'payable', 'payable' => 'receivable', 'revenue' => 'expense', 'expense' => 'revenue'][$row['side']];
        $isBalanceSheet = in_array($row['side'], ['receivable', 'payable'], true);

        $opposite = collect($balances[$row['counterparty']] ?? [])
            ->first(fn (array $candidate) => $candidate['side'] === $oppositeSide && $candidate['counterparty'] === $unitCode);

        $amountB = $opposite['amount'] ?? 0.0;

        return [
            'type' => $isBalanceSheet ? 'balance_sheet' : 'income_statement',
            'label' => $isBalanceSheet ? 'Piutang dan Utang' : 'Pendapatan dan Beban',
            'unit_a' => $unitCode,
            'unit_a_name' => $names[$unitCode] ?? $unitCode,
            'account_a' => $row['code'],
            'name_a' => $row['name'],
            'amount_a' => round($row['amount'], 2),
            'unit_b' => $row['counterparty'],
            'unit_b_name' => $names[$row['counterparty']] ?? $row['counterparty'],
            'account_b' => $opposite['code'] ?? null,
            'name_b' => $opposite['name'] ?? null,
            'amount_b' => round($amountB, 2),
            'difference' => round($row['amount'] - $amountB, 2),
            'matched' => abs($row['amount'] - $amountB) < self::TOLERANCE,
            'counterparty_included' => isset($balances[$row['counterparty']]),
            'selling_side' => $isSellingSide,
        ];
    }
}
