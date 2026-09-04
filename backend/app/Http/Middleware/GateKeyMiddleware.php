<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GateKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('app.gate_scanner_key');

        if (! $configuredKey) {
            return response()->json(['message' => 'Gate scanner key not configured.'], 503);
        }

        $providedKey = $request->header('X-Gate-Key');

        if (! $providedKey || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
