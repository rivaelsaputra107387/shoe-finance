<?php

namespace App\Http\Middleware;

use App\Models\Central\Unit;
use App\Support\UnitContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tidak punya sesi untuk menentukan unit, jadi pengajuan masuk ke unit yang dikonfigurasi.
 */
class ActivatePurchaseRequestUnit
{
    public function __construct(private UnitContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $unit = Unit::where('code', config('finlog.purchase_request_unit'))->first();

        if (! $unit) {
            return response()->json(['message' => 'Unit penerima pengajuan belum dikonfigurasi.'], 503);
        }

        $this->context->activate($unit);

        return $next($request);
    }
}
