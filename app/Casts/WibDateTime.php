<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Kolom waktu yang disimpan sebagai jam dinding WIB, bukan UTC.
 *
 * audit_trails.created_at sejak awal diisi DEFAULT CURRENT_TIMESTAMP oleh MySQL,
 * yang mengikuti zona server (WIB). Cast ini membaca nilai itu sebagai WIB dan
 * menulis nilai baru sebagai WIB juga, supaya data lama dan baru konsisten dan
 * tidak bergantung pada zona server database.
 */
class WibDateTime implements CastsAttributes
{
    public const TIMEZONE = 'Asia/Jakarta';

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value, self::TIMEZONE)->setTimezone(config('app.timezone'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $moment = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value, config('app.timezone'));

        return $moment->setTimezone(self::TIMEZONE)->format('Y-m-d H:i:s');
    }
}
