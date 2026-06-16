<?php

namespace App\Helpers;

class Response
{
    /**
     * Set CORS headers to allow Cross-Origin Resource Sharing.
     */
    public static function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400'); // Cache preflight for 1 day
    }

    /**
     * Send standard JSON response and end execution.
     *
     * @param int $statusCode
     * @param array $payload
     */
    private static function json(int $statusCode, array $payload): void
    {
        self::setCorsHeaders();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload);
        exit();
    }

    /**
     * Send success JSON response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     */
    public static function success(string $message, mixed $data = [], int $statusCode = 200): void
    {
        self::json($statusCode, [
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send error JSON response.
     *
     * @param string $message
     * @param array $errors
     * @param int $statusCode
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::json($statusCode, [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ]);
    }
}
