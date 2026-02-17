<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][] = ['path' => $path, 'handler' => $handler];
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][] = ['path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            $pattern = $this->convertToRegex($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $params = array_map(
                    fn($v) => ctype_digit($v) ? (int)$v : $v,
                    $params
                );
                [$controllerClass, $action] = $route['handler'];

                $controller = new $controllerClass();
                $controller->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        View::render('pages/404');
    }

    private function convertToRegex(string $path): string
    {
        // Special handling for forum topic IDs (should be numeric)
        if (strpos($path, '/forum/') !== false && strpos($path, '/{id}') !== false) {
            $pattern = preg_replace('/\{id\}/', '(?P<id>\d+)', $path);
        } else {
            // Default handling for other IDs (like card IDs which can contain letters, numbers, underscores, hyphens)
            $pattern = preg_replace('/\{id\}/', '(?P<id>[^/]+)', $path);
        }
        
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
