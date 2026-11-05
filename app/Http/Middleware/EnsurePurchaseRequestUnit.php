<?php

namespace App\Http\Middleware;

use App\Support\UnitContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengajuan Belanja sementara hanya ada di satu unit, jadi unit lain tidak membuka menunya.
 */
class EnsurePurchaseRequestUnit
{
    public function __construct(private UnitContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->context->current()?->code === config('finlog.purchase_request_unit'), 404);

        return $next($request);
    }
}
