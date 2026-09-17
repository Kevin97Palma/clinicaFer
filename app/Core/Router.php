<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

/**
 * Router mínimo con parámetros {id}, protección de autenticación/permiso por ruta y CSRF en POST.
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    private function add(string $method, string $path, array $handler, array $options): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'options');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $base = Config::get('base_path');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim(preg_replace('#^/public#', '', $path), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $opts = $route['options'];
            $public = $opts['public'] ?? false;

            if ($method === 'POST') {
                CsrfMiddleware::handle();
            }
            if (!$public) {
                AuthMiddleware::handle($opts['perm'] ?? null, $path);
            }

            $params = array_map('intval', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = $route['handler'];
            (new $class())->$action(...array_values($params));
            return;
        }

        http_response_code(404);
        if (is_ajax()) {
            json_response(['success' => false, 'message' => 'Recurso no encontrado.'], 404);
        }
        View::render('errors/404', ['title' => 'Página no encontrada'], Auth::check() ? 'layouts/app' : 'layouts/guest');
    }
}
