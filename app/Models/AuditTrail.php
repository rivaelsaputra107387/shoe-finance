<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    public $timestamps = false; // Only created_at, no updated_at

    protected $fillable = [
        'user_id',
        'table_name',
        'record_id',
        'action',
        'old_data',
        'new_data',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ───────────────────────────────────────
    // Scopes
    // ───────────────────────────────────────

    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeForRecord($query, string $tableName, int $recordId)
    {
        return $query->where('table_name', $tableName)->where('record_id', $recordId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
