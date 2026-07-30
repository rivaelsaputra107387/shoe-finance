<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property numeric $debit
 * @property numeric $credit
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JournalEntryLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class JournalEntryLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
