<?php

namespace App\Middleware;

use App\Config\JWT;
use App\Helpers\Response;

class AuthMiddleware
{
    /**
     * Handle authentication checking.
     *
     * @param array $request
     * @return array Modified request array
     */
    public function handle(array $request): array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader)) {
            Response::error('Authentication token required.', [], 401);
        }

        // Parse token (Bearer <token>)
        if (!preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            Response::error('Invalid authorization header format.', [], 401);
        }

        $token = $matches[1];
        $decoded = JWT::validateToken($token);

        if (!$decoded) {
            Response::error('Invalid or expired authentication token.', [], 401);
        }

        // Inject the decoded user details into the request context
        $request['user'] = [
            'id' => (int) $decoded->sub,
            'role' => $decoded->role
        ];

        return $request;
    }
}
