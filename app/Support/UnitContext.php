<?php

namespace App\Support;

use App\Models\Central\Unit;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menentukan database unit yang dipakai query tanpa koneksi eksplisit.
 *
 * Setiap unit punya koneksi sendiri (unit_{kode}), jadi model yang sudah dimuat
 * tetap terikat ke unitnya dan perpindahan unit tidak perlu membuka koneksi ulang.
 */
class UnitContext
{
    private ?Unit $current = null;

    public function activate(Unit $unit): void
    {
        DB::setDefaultConnection($this->register($unit));
        $this->current = $unit;

        Log::shareContext(['unit_code' => $unit->code]);
    }

    public function current(): ?Unit
    {
        return $this->current;
    }

    public function forget(): void
    {
        DB::setDefaultConnection('central');
        $this->current = null;

        Log::shareContext(['unit_code' => null]);
    }

    /**
     * Jalankan callback pada unit tertentu, lalu kembalikan konteks sebelumnya walau terjadi error.
     */
    public function run(Unit $unit, callable $callback): mixed
    {
        $previous = $this->current;

        $this->activate($unit);

        try {
            return $callback($unit);
        } finally {
            $previous ? $this->activate($previous) : $this->forget();
        }
    }

    /**
     * Kunci cache untuk data unit, supaya nilai satu unit tidak terbaca di unit lain.
     */
    public function cacheKey(string $key): string
    {
        if (! $this->current) {
            throw new \LogicException('Kunci cache unit butuh unit aktif.');
        }

        return "unit:{$this->current->code}:{$key}";
    }

    public function connectionName(Unit $unit): string
    {
        return $this->register($unit);
    }

    private function register(Unit $unit): string
    {
        $name = "unit_{$unit->code}";

        if (! Config::has("database.connections.{$name}")) {
            Config::set("database.connections.{$name}", array_merge(
                Config::get('database.connections.unit'),
                ['database' => $unit->database],
            ));
        }

        return $name;
    }
}
