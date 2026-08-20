<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWorkshopApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.workshop.api_token') ?: env('WORKSHOP_API_TOKEN', 'default_secret_token');

        if (!$token || $token !== $expectedToken) {
            return response()->json(['message' => 'Unauthorized. Invalid or missing Bearer token.'], 401);
        }

        return $next($request);
    }
}
