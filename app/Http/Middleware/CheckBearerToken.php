<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBearerToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // For now, accept any token. In production, validate against stored tokens.
        // You could store tokens in a table or session.
        $request->attributes->set('user_id', 1); // Assume user ID 1

        return $next($request);
    }
}
