<?php

namespace App\Models\Central;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Unit extends Model
{
    public const STATUS_PREPARATION = 'persiapan';

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_INACTIVE = 'nonaktif';

    protected $connection = 'central';

    protected $fillable = [
        'code',
        'name',
        'legal_name',
        'npwp',
        'database',
        'status',
        'books_start_date',
        'activated_at',
        'deactivated_at',
        'sort_order',
        'intercompany_slot',
    ];

    protected function casts(): array
    {
        return [
            'books_start_date' => 'date',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')->withPivot('granted_by')->withTimestamps();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isInPreparation(): bool
    {
        return $this->status === self::STATUS_PREPARATION;
    }
}
