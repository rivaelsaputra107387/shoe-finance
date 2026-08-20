<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_batch' => 'boolean',
        'spk_list' => 'array',
        'payload_raw' => 'array',
        'received_at' => 'datetime',
        'approved_at' => 'datetime',
        'purchased_at' => 'datetime',
        'received_material_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookEventLog::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
