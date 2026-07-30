<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string|null $reference
 * @property string $description
 * @property int $fiscal_period_id
 * @property int $created_by
 * @property bool $is_closing
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property int|null $submitted_by
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\FiscalPeriod $fiscalPeriod
 * @property-read bool $is_balanced
 * @property-read float $total_credit
 * @property-read float $total_debit
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntryLine> $lines
 * @property-read int|null $lines_count
 * @property-read \App\Models\User|null $postedBy
 * @property-read \App\Models\User|null $submittedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry closing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry forPeriod(int $fiscalPeriodId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry posted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry regular()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereEntryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereFiscalPeriodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereIsClosing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry wherePostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntry withoutTrashed()
 * @mixin \Eloquent
 */
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
        'submitted_by',
        'submitted_at',
        'posted_by',
        'posted_at',
    ];

    /**
     * Eager load lines + account by default to avoid N+1 on table views.
     */
    protected $with = ['lines.account'];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_closing' => 'boolean',
            'submitted_at' => 'datetime',
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

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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
