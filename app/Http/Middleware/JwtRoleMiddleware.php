<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class JwtRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
                'errors' => []
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            $msg = 'Access denied.';
            if (in_array('admin', $roles) && in_array('volunteer', $roles)) {
                $msg = 'Access denied. Administrator or Volunteer role required.';
            } elseif (in_array('admin', $roles)) {
                $msg = 'Access denied. Administrator privileges required.';
            }
            
            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors' => []
            ], 403);
        }

        return $next($request);
    }
}
