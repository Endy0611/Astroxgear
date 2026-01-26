<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class Authenticate
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (auth()->guard('sanctum')->check()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthenticated.'
        ], 401);
    }
}