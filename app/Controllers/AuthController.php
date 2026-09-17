<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        $this->view('auth/login', ['title' => 'Iniciar sesión'], 'layouts/guest');
    }

    public function login(): void
    {
        $login = mb_substr((string) $this->input('login', ''), 0, 150);
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['_old'] = ['login' => $login];

        if ($login === '' || $password === '') {
            flash('danger', 'Ingrese su usuario y contraseña.');
            redirect('/login');
        }

        $user = Database::one('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1', [$login, $login]);
        $generic = 'Usuario o contraseña incorrectos.';

        if ($user && $user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $mins = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
            flash('danger', "Cuenta bloqueada temporalmente por intentos fallidos. Intente en $mins min.");
            redirect('/login');
        }

        // password_verify también contra un hash ficticio para no revelar si el usuario existe
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsaltIeYtBZfR8S3SxmTbD4r6LwL2m8sTK';
        $valid = password_verify($password, $hash) && $user && $user['is_active'];

        if (!$valid) {
            if ($user) {
                $attempts = (int) $user['failed_attempts'] + 1;
                $lock = $attempts >= Config::get('login_max_attempts')
                    ? date('Y-m-d H:i:s', time() + Config::get('login_lock_minutes') * 60) : null;
                Database::query('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $attempts, $lock, $user['id']]);
                AuditService::log('LOGIN_FALLIDO', 'auth', (int) $user['id'], $lock ? 'Cuenta bloqueada temporalmente' : "Intento fallido $attempts", [], (int) $user['id']);
            }
            usleep(300000);
            flash('danger', $user && !$user['is_active'] && password_verify($password, $hash) ? 'El usuario está desactivado.' : $generic);
            redirect('/login');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        Database::query('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$user['id']]);
        Auth::login($user);
        AuditService::log('LOGIN', 'auth', (int) $user['id'], 'Inicio de sesión');

        $role = Database::value('SELECT slug FROM roles WHERE id = ?', [$user['role_id']]);
        redirect($role === 'asistente' ? '/agenda' : '/');
    }

    public function logout(): void
    {
        AuditService::log('LOGOUT', 'auth', Auth::id(), 'Cierre de sesión');
        Auth::logout();
        session_destroy();
        redirect('/login');
    }

    public function showPassword(): void
    {
        $this->view('auth/password', ['title' => 'Cambiar contraseña', 'forced' => (bool) Auth::user()['must_change_password']]);
    }

    public function updatePassword(): void
    {
        $data = $this->validate($_POST, ['current' => 'required', 'password' => 'required|min:10', 'password_confirmation' => 'required']);
        $user = Database::one('SELECT id, password_hash FROM users WHERE id = ?', [Auth::id()]);
        if (!password_verify($data['current'], $user['password_hash'])) {
            $_SESSION['_errors'] = ['current' => 'La contraseña actual no es correcta.'];
            redirect('/perfil/password');
        }
        if ($data['password'] !== $data['password_confirmation']) {
            $_SESSION['_errors'] = ['password_confirmation' => 'Las contraseñas no coinciden.'];
            redirect('/perfil/password');
        }
        if (!preg_match('/[A-Za-z]/', $data['password']) || !preg_match('/\d/', $data['password'])) {
            $_SESSION['_errors'] = ['password' => 'Use letras y números.'];
            redirect('/perfil/password');
        }
        Database::query('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($data['password'], PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        AuditService::log('EDITAR', 'usuarios', (int) $user['id'], 'Cambio de contraseña propia', ['password']);
        flash('success', 'Contraseña actualizada correctamente.');
        redirect('/');
    }
}
