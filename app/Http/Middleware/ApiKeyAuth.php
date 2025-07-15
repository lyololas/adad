<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     securityScheme="api_key",
 *     name="X-API-KEY"
 * )
 */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing API key.'
            ], 401);
        }

        $user = User::findByApiKey($apiKey);
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API key.'
            ], 401);
        }

        // Check if API key is expired
        if ($user->api_key_expires_at && $user->api_key_expires_at->isPast()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API key expired.'
            ], 401);
        }

        // Attach user to request for downstream use
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
} 