<?php

class Router
{
    // Guardem les rutes registrades (GET i POST)
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
        // Ens assegurem que el path sigui tipus "/profile" o "/"
        if ($path === '') return '/';
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return $path;
    }

    public static function dispatch(): void
    {
        // Agafem el mètode (GET/POST) i el path de la URL
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Traiem el "base path" per suportar el projecte en subcarpetes
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $routeKey = self::normalizePath($path);

        // Si la ruta no existeix, 404
        if (!isset(self::$routes[$method][$routeKey])) {
            http_response_code(404);
            $pageTitle = 'Valomen.gg | 404 Not Found';
            $pageCss   = '404.css';
            require __DIR__ . '/../View/partials/header.php';
            require __DIR__ . '/../View/404.view.php';
            require __DIR__ . '/../View/partials/footer.php';
            return;
        }

        $handler = self::$routes[$method][$routeKey];

        // Separem controlador i mètode
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

        // La BD es crea a public/index.php i aquí la reutilitzem
        global $db;
        $controller = new $class($db);

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo "Method $action not found in controller $class";
            return;
        }

        // Executem el controlador
        $controller->$action();
    }
}