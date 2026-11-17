<?php

namespace App\Models\Central;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CentralAuditTrail extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'central';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'old_data',
        'new_data',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
