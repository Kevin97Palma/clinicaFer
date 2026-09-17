<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
        unset($_SESSION['_old']);
    }

    protected function json(array $payload, int $status = 200): void
    {
        json_response($payload, $status);
    }

    protected function authorize(string $permission): void
    {
        if (!Auth::can($permission)) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'No tiene permiso para esta acción.'], 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso restringido']);
            exit;
        }
    }

    /** Valida la entrada; si falla, vuelve al formulario con errores y datos previos. */
    protected function validate(array $input, array $rules): array
    {
        $errors = Validator::make($input, $rules);
        if ($errors) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'Revise los campos marcados.', 'errors' => $errors], 422);
            }
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $input;
            flash('danger', 'Revise los campos marcados.');
            redirect_back();
        }
        return $input;
    }

    protected function input(string $key, $default = null)
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    /** Toma solo los campos permitidos; cadenas vacías pasan a NULL. */
    protected function only(array $keys, ?array $source = null): array
    {
        $source ??= $_POST;
        $out = [];
        foreach ($keys as $k) {
            $v = $source[$k] ?? null;
            if (is_string($v)) {
                $v = trim($v);
                $v = $v === '' ? null : $v;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    protected function notFound(): void
    {
        http_response_code(404);
        if (is_ajax()) {
            json_response(['success' => false, 'message' => 'Registro no encontrado.'], 404);
        }
        View::render('errors/404', ['title' => 'No encontrado']);
        exit;
    }
}
