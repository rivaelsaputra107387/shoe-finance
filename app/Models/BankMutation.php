<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
