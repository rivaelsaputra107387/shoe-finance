<?php

namespace App\Http\Middleware;

use App\Models\Central\Unit;
use App\Support\UnitContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pintu masuk data unit: memeriksa akses setiap permintaan, lalu mengaktifkan koneksi unit.
 * Harus berjalan sebelum SubstituteBindings supaya model rute dicari di database unit.
 */
class EnsureActiveUnit
{
    public const SESSION_KEY = 'active_unit_id';

    public const HEADER = 'X-Finlog-Unit';

    public function __construct(private UnitContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $unit = Unit::find($request->session()->get(self::SESSION_KEY));

        if (! $unit || ! $request->user()->canAccessUnit($unit)) {
            $request->session()->forget(self::SESSION_KEY);

            // Permintaan tulis ditolak, bukan dialihkan, supaya pemanggil tahu datanya tidak tersimpan.
            abort_unless($request->isMethodSafe(), 403, 'Anda tidak memiliki akses ke unit ini.');

            return redirect('/app/units');
        }

        if ($this->isFromAnotherUnit($request, $unit)) {
            return response()->json([
                'code' => 'unit_conflict',
                'message' => 'Unit aktif telah berubah. Muat ulang halaman ini.',
                'active_unit' => $unit->name,
            ], 409);
        }

        abort_if($unit->isInactive() && ! $request->isMethodSafe(), 403, 'Unit ini nonaktif dan hanya bisa dibaca.');

        $this->context->activate($unit);
        Log::shareContext(['user_id' => $request->user()->id]);

        return $next($request);
    }

    /**
     * Unit aktif tersimpan di sesi yang dipakai bersama semua tab. Tab yang masih menampilkan unit lain
     * tidak boleh menulis, karena datanya akan masuk ke unit yang tidak sedang dilihat pengguna.
     */
    private function isFromAnotherUnit(Request $request, Unit $unit): bool
    {
        $pageUnit = $request->header(self::HEADER) ?? $request->query('unit');

        if ($pageUnit === null) {
            return ! $request->isMethodSafe();
        }

        return $pageUnit !== $unit->code;
    }
}
