<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckYandexToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->yandex_token) {
            return response()->json([
                'error' => 'Yandex token not available',
                'solution' => 'Please re-authenticate with Yandex'
            ], 403);
        }
        
        return $next($request);
    }
}