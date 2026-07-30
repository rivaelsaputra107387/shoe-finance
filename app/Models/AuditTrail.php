<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $table_name
 * @property int $record_id
 * @property string $action
 * @property array<array-key, mixed>|null $old_data
 * @property array<array-key, mixed>|null $new_data
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail byAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail forRecord(string $tableName, int $recordId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail forTable(string $tableName)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereNewData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereOldData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereTableName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditTrail whereUserId($value)
 * @mixin \Eloquent
 */
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
