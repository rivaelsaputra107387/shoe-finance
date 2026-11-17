<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanViewConsolidated
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()->canViewConsolidated(),
            403,
            'Laporan Gabungan hanya untuk Owner dan Finance yang memiliki akses ke semua unit aktif.'
        );

        return $next($request);
    }
}
