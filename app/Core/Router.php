<?php

class Router
{
    private static array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    public static function get(string $path, $handler): void
    {
        self::$routes['GET'][self::normalizePath($path)] = $handler;
    }

    public static function post(string $path, $handler): void
    {
        self::$routes['POST'][self::normalizePath($path)] = $handler;
    }

    private static function normalizePath(string $path): string
    {
        if ($path === '') return '/';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return $path;
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $routeKey = self::normalizePath($path);

        if (!isset(self::$routes[$method][$routeKey])) {
            http_response_code(404);
            echo "404 - Page not found (" . htmlspecialchars($routeKey) . ")";
            return;
        }

        $handler = self::$routes[$method][$routeKey];

        if (is_string($handler)) {
            [$class, $action] = explode('@', $handler);
        } elseif (is_array($handler) && count($handler) === 2) {
            $class  = $handler[0];
            $action = $handler[1];
        } else {
            http_response_code(500);
            echo "Invalid route handler";
            return;
        }

        if (!class_exists($class)) {
            http_response_code(500);
            echo "Controller $class not found";
            return;
        }

        global $db;
        $controller = new $class($db);

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo "Method $action not found in controller $class";
            return;
        }

        $controller->$action();
    }
}