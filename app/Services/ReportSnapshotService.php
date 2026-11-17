<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds and compares snapshots of the financial reports, so a migration can prove
 * the numbers did not change. Writes nothing, and must stay compatible with the old
 * production code and MySQL version because the "before" snapshot runs there.
 */
class ReportSnapshotService
{
    public const FORMAT_VERSION = 1;

    private const TOLERANCE = 0.005;

    /**
     * @param  string[]  $periodStartDates  Empty means every period that has journal entries.
     */
    public function build(array $periodStartDates = []): array
    {
        // The transaction gives one consistent view across all queries, and the
        // rollback guarantees nothing is persisted.
        DB::beginTransaction();

        try {
            $periods = $this->periods($periodStartDates);

            $reports = [];
            foreach ($periods as $period) {
                $reports[$period->start_date->format('Y-m-d')] = $this->normalize($this->reportsFor($period->id));
            }

            return [
                'format_version' => self::FORMAT_VERSION,
                'meta' => [
                    'generated_at' => now()->toDateTimeString(),
                    'connection' => config('database.default'),
                    'database' => DB::connection()->getDatabaseName(),
                ],
                'data' => $this->normalize($this->dataTotals()),
                'reports' => $reports,
            ];
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Compare two snapshots. Returns one line per difference, empty when identical.
     * Metadata is ignored, and numbers are compared with a half-cent tolerance.
     *
     * @return string[]
     */
    public function compare(array $before, array $after): array
    {
        if (($before['format_version'] ?? null) !== ($after['format_version'] ?? null)) {
            return ['format_version: versi format snapshot berbeda'];
        }

        $differences = [];
        $this->diff($before['data'] ?? [], $after['data'] ?? [], 'data', $differences);
        $this->diff($before['reports'] ?? [], $after['reports'] ?? [], 'reports', $differences);

        return $differences;
    }

    /**
     * @return Collection<int, FiscalPeriod>
     */
    private function periods(array $startDates): Collection
    {
        $query = FiscalPeriod::query()->orderBy('start_date');

        if ($startDates !== []) {
            $periods = $query->whereIn('start_date', $startDates)->get();
            $found = $periods->map(fn (FiscalPeriod $period) => $period->start_date->format('Y-m-d'))->all();
            $missing = array_diff($startDates, $found);

            if ($missing !== []) {
                throw new \InvalidArgumentException('Periode tidak ditemukan: '.implode(', ', $missing));
            }

            return $periods;
        }

        // Empty periods are skipped: they have no numbers to protect, and the empty
        // July to September periods are deleted during cutover.
        return $query->whereExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('journal_entries')
                ->whereColumn('journal_entries.fiscal_period_id', 'fiscal_periods.id')
                ->whereNull('journal_entries.deleted_at');
        })->get();
    }

    private function reportsFor(int $periodId): array
    {
        return [
            'trial_balance' => (new TrialBalanceService)->generate($periodId),
            'income_statement' => (new IncomeStatementService)->generate($periodId),
            'balance_sheet' => (new BalanceSheetService)->generate($periodId),
            'equity_statement' => (new EquityStatementService)->generate($periodId),
            'cash_flow' => (new CashFlowReportService)->generate($periodId),
            'general_ledger' => $this->generalLedger($periodId),
        ];
    }

    private function generalLedger(int $periodId): array
    {
        $service = new LedgerService;
        $ledger = [];

        Account::query()
            ->whereHas('journalEntryLines')
            ->orderBy('code')
            ->each(function (Account $account) use ($service, $periodId, &$ledger) {
                $ledger[$account->code] = $service->generateForAccount($account->id, $periodId);
            });

        return $ledger;
    }

    private function dataTotals(): array
    {
        return [
            'accounts' => DB::table('accounts')->count(),
            'journal_entries' => DB::table('journal_entries')->count(),
            'journal_entries_active' => DB::table('journal_entries')->whereNull('deleted_at')->count(),
            'journal_entry_lines' => DB::table('journal_entry_lines')->count(),
            'journal_debit' => (float) DB::table('journal_entry_lines')->sum('debit'),
            'journal_credit' => (float) DB::table('journal_entry_lines')->sum('credit'),
            'bank_mutations' => DB::table('bank_mutations')->count(),
            'bank_mutations_active' => DB::table('bank_mutations')->whereNull('deleted_at')->count(),
            'bank_mutation_amount' => (float) DB::table('bank_mutations')->sum('amount'),
        ];
    }

    /**
     * Reduce report output to plain, stable values.
     */
    private function normalize(mixed $value): mixed
    {
        if ($value instanceof FiscalPeriod) {
            // Only the identity: status legitimately changes, and it is not a number we protect.
            return [
                'name' => $value->name,
                'start_date' => $value->start_date->format('Y-m-d'),
                'end_date' => $value->end_date->format('Y-m-d'),
            ];
        }

        if ($value instanceof Model) {
            $value = collect($value->attributesToArray())->except(['created_at', 'updated_at', 'deleted_at'])->all();
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            $normalized = array_map(fn ($item) => $this->normalize($item), $value);

            if (! array_is_list($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }

        return is_float($value) ? round($value, 2) : $value;
    }

    private function diff(mixed $before, mixed $after, string $path, array &$differences): void
    {
        if (is_array($before) && is_array($after)) {
            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                $child = "{$path}.{$key}";

                if (! array_key_exists($key, $before)) {
                    $differences[] = "{$child}: hanya ada di snapshot sesudah";
                } elseif (! array_key_exists($key, $after)) {
                    $differences[] = "{$child}: hilang di snapshot sesudah";
                } else {
                    $this->diff($before[$key], $after[$key], $child, $differences);
                }
            }

            return;
        }

        if (! $this->sameValue($before, $after)) {
            $differences[] = "{$path}: ".json_encode($before).' menjadi '.json_encode($after);
        }
    }

    private function sameValue(mixed $before, mixed $after): bool
    {
        if (is_numeric($before) && is_numeric($after)) {
            return abs((float) $before - (float) $after) < self::TOLERANCE;
        }

        return $before === $after;
    }
}
