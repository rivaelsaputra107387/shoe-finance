<?php

namespace App\Models;

use App\Models\Central\Unit;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deactivated_at' => 'datetime',
        ];
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return \Illuminate\Support\Facades\Storage::url($this->profile_photo_path);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=10b981&background=ecfdf5';
    }

    // ───────────────────────────────────────
    // Relationships
    // ───────────────────────────────────────

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_user')->withPivot('granted_by')->withTimestamps();
    }

    // ───────────────────────────────────────
    // Akses Unit
    // ───────────────────────────────────────

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Unit yang boleh dibuka. Owner otomatis semua unit, dan Staff tidak melihat unit persiapan.
     */
    public function accessibleUnits(): Collection
    {
        if ($this->hasRole('owner')) {
            return Unit::ordered()->get();
        }

        $units = $this->units()->ordered()->get();

        return $this->hasRole('finance')
            ? $units
            : $units->reject(fn (Unit $unit) => $unit->isInPreparation())->values();
    }

    public function canAccessUnit(Unit $unit): bool
    {
        return $this->accessibleUnits()->contains('id', $unit->id);
    }

    /**
     * Laporan Gabungan memuat angka semua unit, jadi hanya untuk Owner dan Finance yang mengakses semua unit aktif.
     */
    public function canViewConsolidated(): bool
    {
        if (! $this->hasAnyRole(['owner', 'finance'])) {
            return false;
        }

        $activeUnits = Unit::where('status', Unit::STATUS_ACTIVE)->pluck('id');
        $accessible = $this->accessibleUnits()->pluck('id');

        return $activeUnits->isNotEmpty() && $activeUnits->diff($accessible)->isEmpty();
    }
}
