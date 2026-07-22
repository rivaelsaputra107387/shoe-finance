<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function getBalanceForPeriod(int $fiscalPeriodId): float
    {
        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($fiscalPeriodId) {
                $q->where('fiscal_period_id', $fiscalPeriodId)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
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
    public function getRawTotalsForPeriod(int $fiscalPeriodId): array
    {
        $totals = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($fiscalPeriodId) {
                $q->where('fiscal_period_id', $fiscalPeriodId)
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
}
