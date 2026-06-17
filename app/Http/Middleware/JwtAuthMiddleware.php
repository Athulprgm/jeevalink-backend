<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\JWT;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');

        if (empty($authHeader)) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token required.',
                'errors' => []
            ], 401);
        }

        // Parse token (Bearer <token>)
        if (!preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authorization header format.',
                'errors' => []
            ], 401);
        }

        $token = $matches[1];
        $decoded = JWT::validateToken($token);

        if (!$decoded) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired authentication token.',
                'errors' => []
            ], 401);
        }

        // Fetch user
        $user = User::find($decoded->sub);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found.',
                'errors' => []
            ], 404);
        }

        // Set authenticated user in Laravel's Auth guard for this request
        Auth::setUser($user);

        // Also inject the custom user metadata so that the existing PHP logic continues to work
        $request->merge([
            'user' => [
                'id' => (int) $decoded->sub,
                'role' => $decoded->role
            ]
        ]);

        return $next($request);
    }
}
