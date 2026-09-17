<?php
declare(strict_types=1);

namespace App\Middleware;

final class CsrfMiddleware
{
    public static function handle(): void
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'El formulario expiró. Recargue la página.'], 419);
            }
            http_response_code(419);
            flash('danger', 'El formulario expiró. Intente nuevamente.');
            redirect_back();
        }
    }
}
