<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
