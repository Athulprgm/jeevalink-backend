<?php

namespace App\Helpers;

use App\Helpers\Response;

class Router
{
    private static array $routes = [];

    /**
     * Add a route definition.
     *
     * @param string $method
     * @param string $path
     * @param string $controller
     * @param string $action
     * @param array $middlewares
     */
    public static function add(string $method, string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    public static function get(string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::add('GET', $path, $controller, $action, $middlewares);
    }

    public static function post(string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::add('POST', $path, $controller, $action, $middlewares);
    }

    public static function put(string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::add('PUT', $path, $controller, $action, $middlewares);
    }

    public static function patch(string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::add('PATCH', $path, $controller, $action, $middlewares);
    }

    public static function delete(string $path, string $controller, string $action, array $middlewares = []): void
    {
        self::add('DELETE', $path, $controller, $action, $middlewares);
    }

    /**
     * Dispatch request to matching route.
     *
     * @param string $requestUri
     * @param string $requestMethod
     */
    public static function dispatch(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $method = strtoupper($requestMethod);

        // Normalize and strip subdirectory prefix if hosted inside XAMPP htdocs subfolders
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptDir !== '/' && $scriptDir !== '.') {
            if (str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
            }
        }

        // Ensure path starts with a leading slash
        if (empty($path) || !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Preflight OPTIONS request
        if ($method === 'OPTIONS') {
            Response::setCorsHeaders();
            http_response_code(204);
            exit();
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert {param} placeholder to regex capturing groups
            // e.g. /api/v1/requests/{id}/fulfill -> ^/api/v1/requests/([^/]+)/fulfill$
            $routePattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $routePattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Drop index 0 which contains full match
                array_shift($matches);

                // Prepare request array
                $request = [
                    'uri' => $path,
                    'method' => $method,
                    'user' => null,
                    'body' => self::getRequestBody()
                ];

                // Execute middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middlewareInstance = new $middlewareClass();
                    $request = $middlewareInstance->handle($request);
                }

                $controllerClass = $route['controller'];
                $action = $route['action'];

                if (!class_exists($controllerClass)) {
                    Response::error("System Error: Controller '{$controllerClass}' does not exist.", [], 500);
                }

                $controllerInstance = new $controllerClass();
                if (!method_exists($controllerInstance, $action)) {
                    Response::error("System Error: Method '{$action}' does not exist in controller.", [], 500);
                }

                // Call controller action, passing $request and route variables as separate parameters
                call_user_func_array([$controllerInstance, $action], array_merge([$request], $matches));
                return;
            }
        }

        Response::error("Endpoint Not Found", [], 404);
    }

    /**
     * Parse and retrieve JSON request body.
     *
     * @return array
     */
    private static function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return $_POST;
        }

        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $_POST;
    }
}
