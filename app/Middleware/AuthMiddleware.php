<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;

final class AuthMiddleware
{
    public static function handle(?string $permission, string $path): void
    {
        if (!Auth::check()) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'Su sesión expiró. Inicie sesión nuevamente.'], 401);
            }
            redirect('/login');
        }

        // Cierre por inactividad
        $idle = Config::get('session_idle_minutes') * 60;
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $idle) {
            Auth::logout();
            flash('warning', 'La sesión se cerró por inactividad.');
            redirect('/login');
        }
        $_SESSION['last_activity'] = time();

        // Cambio obligatorio de contraseña
        if (Auth::user()['must_change_password'] && !in_array($path, ['/perfil/password', '/logout'], true)) {
            redirect('/perfil/password');
        }

        if ($permission !== null && !Auth::can($permission)) {
            if (is_ajax()) {
                json_response(['success' => false, 'message' => 'No tiene permiso para esta acción.'], 403);
            }
            http_response_code(403);
            \App\Core\View::render('errors/403', ['title' => 'Acceso restringido']);
            exit;
        }
    }
}
