<?php

namespace App\Middleware;

use App\Helpers\Response;

class AdminMiddleware
{
    /**
     * Handle admin verification checking.
     *
     * @param array $request
     * @return array
     */
    public function handle(array $request): array
    {
        // Require AuthMiddleware to have executed first
        if (!isset($request['user']) || empty($request['user']['id'])) {
            Response::error('Authentication required.', [], 401);
        }

        if ($request['user']['role'] !== 'admin') {
            Response::error('Access denied. Administrator privileges required.', [], 403);
        }

        return $request;
    }
}
