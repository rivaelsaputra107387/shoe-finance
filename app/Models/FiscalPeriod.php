<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $status
 * @property int|null $closed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $closedBy
 * @property-read bool $is_open
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod closed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod open()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FiscalPeriod whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FiscalPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ───────────────────────────────────────
    // Scopes
    // ───────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // ───────────────────────────────────────
    // Accessors
    // ───────────────────────────────────────

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    // ───────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────

    /**
     * Get the currently active (open) fiscal period.
     */
    public static function active(): ?self
    {
        return static::open()->latest('start_date')->first();
    }

    /**
     * Check if a given date falls within this period.
     */
    public function containsDate($date): bool
    {
        $date = \Carbon\Carbon::parse($date);
        return $date->between($this->start_date, $this->end_date);
    }
}
