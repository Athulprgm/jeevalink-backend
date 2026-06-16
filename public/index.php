<?php

/**
 * Jeevalink Backend API - Front Controller & Entry Point
 * Handles autoloading, environment variables, CORS, error handling, and routing.
 */

// 1. Load Composer Autoloader
$autoloaderPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'System Error: Composer vendor autoload file not found. Please run "composer install".'
    ]);
    exit();
}
require_once $autoloaderPath;

// 2. Load Environment Variables from .env
use Dotenv\Dotenv;
use App\Helpers\Response;
use App\Helpers\Router;

try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (Exception $e) {
    // Gracefully fallback to default environment variables if .env is missing
}

// 3. Configure Production Error and Exception Handling
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    // In production, mask detailed errors unless in development mode
    $isDev = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
    Response::error(
        $isDev ? "PHP Warning/Error: {$message}" : "An internal server error occurred.",
        $isDev ? ['file' => $file, 'line' => $line] : [],
        500
    );
});

set_exception_handler(function (Throwable $exception) {
    $isDev = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
    Response::error(
        $isDev ? "Fatal Exception: " . $exception->getMessage() : "An unexpected server error occurred.",
        $isDev ? [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString())
        ] : [],
        500
    );
});

// Register exception shutdowns
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $isDev = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
        Response::error(
            $isDev ? "Fatal PHP Error: " . $error['message'] : "An internal system error occurred.",
            $isDev ? ['file' => $error['file'], 'line' => $error['line']] : [],
            500
        );
    }
});

// 4. Load API Routes Mapping
$routesPath = dirname(__DIR__) . '/app/Routes/api.php';
if (!file_exists($routesPath)) {
    Response::error("System Error: API route configuration file not found.", [], 500);
}
require_once $routesPath;

// 5. Dispatch Request to Router
Router::dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
