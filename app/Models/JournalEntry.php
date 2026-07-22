<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_date',
        'reference',
        'description',
        'fiscal_period_id',
        'created_by',
        'is_closing',
        'status',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_closing' => 'boolean',
            'posted_at'  => 'datetime',
        ];
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // ───────────────────────────────────────
    // Accessors
    // ───────────────────────────────────────

    /**
     * Check if total debit equals total credit.
     */
    public function getIsBalancedAttribute(): bool
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return round((float) $totals->total_debit, 2) === round((float) $totals->total_credit, 2);
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    // ───────────────────────────────────────
    // Scopes
    // ───────────────────────────────────────

    public function scopeClosing($query)
    {
        return $query->where('is_closing', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_closing', false);
    }

    public function scopeForPeriod($query, int $fiscalPeriodId)
    {
        return $query->where('fiscal_period_id', $fiscalPeriodId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    // ───────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────

    /**
     * Check if this entry can be edited (period must be open).
     */
    public function isEditable(): bool
    {
        return $this->fiscalPeriod->is_open && !$this->is_closing && $this->status === 'draft';
    }

    /**
     * Post the journal entry.
     */
    public function post(): void
    {
        $this->update([
            'status' => 'posted',
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);
    }
}
