<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDeactivated()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort_unless($request->isMethodSafe(), 403, 'Akun ini sudah dinonaktifkan.');

            return redirect('/login')->withErrors(['email' => 'Akun ini sudah dinonaktifkan.']);
        }

        return $next($request);
    }
}
