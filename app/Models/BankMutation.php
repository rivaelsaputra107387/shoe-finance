<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $description
 * @property numeric $amount
 * @property string $bank_source
 * @property string $source_type
 * @property string $mutation_type
 * @property string|null $matched_invoice_ref
 * @property array<array-key, mixed>|null $matched_invoice_data
 * @property int|null $journal_entry_id
 * @property string $status
 * @property int|null $uploaded_by
 * @property int|null $matched_by
 * @property int|null $completed_by
 * @property int|null $submitted_by
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $completer
 * @property-read \App\Models\JournalEntry|null $journalEntry
 * @property-read \App\Models\User|null $matcher
 * @property-read \App\Models\User|null $poster
 * @property-read \App\Models\User|null $submitter
 * @property-read \App\Models\User|null $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereBankSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedInvoiceData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMatchedInvoiceRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereMutationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation wherePostedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankMutation withoutTrashed()
 * @mixin \Eloquent
 */
class BankMutation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'description',
        'amount',
        'bank_source',
        'source_type',
        'mutation_type',
        'matched_invoice_ref',
        'matched_invoice_data',
        'status',
        'journal_entry_id',
        'uploaded_by',
        'matched_by',
        'completed_by',
        'submitted_by',
        'posted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'matched_invoice_data' => 'array',
    ];

    /**
     * Relasi ke JournalEntry
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    // Audit Trail Relations
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
