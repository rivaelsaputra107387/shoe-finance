<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $normal_balance
 * @property string $report_category
 * @property string|null $cash_flow_category
 * @property int|null $parent_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Account> $children
 * @property-read int|null $children_count
 * @property-read string $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $journalEntryLines
 * @property-read int|null $journal_entry_lines_count
 * @property-read Account|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account forReport(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account labaRugi()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account neraca()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCashFlowCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereNormalBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereReportCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account withoutTrashed()
 * @mixin \Eloquent
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'report_category',
        'cash_flow_category',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    // ───────────────────────────────────────
    // Scopes
    // ───────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForReport($query, string $category)
    {
        return $query->where('report_category', $category);
    }

    public function scopeNeraca($query)
    {
        return $query->where('report_category', 'Neraca');
    }

    public function scopeLabaRugi($query)
    {
        return $query->where('report_category', 'Laba Rugi');
    }

    // ───────────────────────────────────────
    // Accessors
    // ───────────────────────────────────────

    /**
     * Display format: "1110 - Kas"
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    // ───────────────────────────────────────
    // Balance Calculations
    // ───────────────────────────────────────

    /**
     * Calculate the balance for this account within a fiscal period.
     * Returns positive value in the direction of normal_balance.
     */
    public function getBalanceForPeriod(int $fiscalPeriodId, bool $excludeClosing = false): float
    {
        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($fiscalPeriodId, $excludeClosing) {
                $q->where('fiscal_period_id', $fiscalPeriodId)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
                
                if ($excludeClosing) {
                    $q->where('is_closing', false);
                }
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $totalDebit = (float) $totals->total_debit;
        $totalCredit = (float) $totals->total_credit;

        // For Debet normal balance accounts: balance = debit - credit
        // For Kredit normal balance accounts: balance = credit - debit
        if ($this->normal_balance === 'Debet') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }

    /**
     * Get raw debit and credit totals for a fiscal period.
     */
    public function getRawTotalsForPeriod(int $fiscalPeriodId, bool $excludeClosing = false): array
    {
        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($fiscalPeriodId, $excludeClosing) {
                $q->where('fiscal_period_id', $fiscalPeriodId)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
                
                if ($excludeClosing) {
                    $q->where('is_closing', false);
                }
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return [
            'debit' => (float) $totals->total_debit,
            'credit' => (float) $totals->total_credit,
        ];
    }

    /**
     * Get cumulative raw debit and credit totals UP TO the end of a fiscal period.
     */
    public function getCumulativeRawTotalsForPeriod(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::find($fiscalPeriodId);
        if (!$period) {
            return ['debit' => 0.0, 'credit' => 0.0];
        }

        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($period) {
                $q->where('entry_date', '<=', $period->end_date)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return [
            'debit' => (float) $totals->total_debit,
            'credit' => (float) $totals->total_credit,
        ];
    }

    /**
     * Get cumulative raw debit and credit totals BEFORE the start of a fiscal period.
     */
    public function getCumulativeRawTotalsBeforePeriod(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::find($fiscalPeriodId);
        if (!$period) {
            return ['debit' => 0.0, 'credit' => 0.0];
        }

        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($period) {
                $q->where('entry_date', '<', $period->start_date)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
            })
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return [
            'debit' => (float) $totals->total_debit,
            'credit' => (float) $totals->total_credit,
        ];
    }

    /**
     * Get debit/credit balance for trial balance display.
     * Returns the balance in the appropriate column (debit or credit).
     */
    public function getTrialBalanceForPeriod(int $fiscalPeriodId): array
    {
        $totals = $this->getRawTotalsForPeriod($fiscalPeriodId);
        $balance = $totals['debit'] - $totals['credit'];

        if ($balance > 0) {
            return ['debit' => $balance, 'credit' => 0];
        } elseif ($balance < 0) {
            return ['debit' => 0, 'credit' => abs($balance)];
        }

        return ['debit' => 0, 'credit' => 0];
    }

    /**
     * Get cumulative debit/credit balance for trial balance display up to a period.
     */
    public function getCumulativeTrialBalanceForPeriod(int $fiscalPeriodId): array
    {
        $totals = $this->getCumulativeRawTotalsForPeriod($fiscalPeriodId);
        $balance = $totals['debit'] - $totals['credit'];

        if ($balance > 0) {
            return ['debit' => $balance, 'credit' => 0];
        } elseif ($balance < 0) {
            return ['debit' => 0, 'credit' => abs($balance)];
        }

        return ['debit' => 0, 'credit' => 0];
    }
}
