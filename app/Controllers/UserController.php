<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->view('users/index', [
            'title' => 'Usuarios',
            'users' => Database::all(
                'SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.name'
            ),
            'roles' => Database::all('SELECT * FROM roles ORDER BY id'),
            'generated' => $_SESSION['_generated_password'] ?? null,
        ]);
        unset($_SESSION['_generated_password']);
    }

    public function store(): void
    {
        $this->validate($_POST, ['name' => 'required|max:120', 'username' => 'required|max:60', 'email' => 'required|email|max:150',
            'role_id' => 'required|int', 'password' => 'required|min:10']);
        $username = mb_strtolower(trim((string) $_POST['username']));
        if (!preg_match('/^[a-z0-9._-]+$/', $username)) {
            $_SESSION['_errors'] = ['username' => 'Use solo letras, números, punto, guion o guion bajo.'];
            redirect('/usuarios');
        }
        if (Database::value('SELECT id FROM users WHERE username = ? OR email = ?', [$username, trim((string) $_POST['email'])])) {
            flash('danger', 'Ya existe un usuario con ese nombre de usuario o correo.');
            redirect('/usuarios');
        }
        $id = Database::insert('users', [
            'role_id' => (int) $_POST['role_id'],
            'name' => trim((string) $_POST['name']),
            'username' => $username,
            'email' => mb_strtolower(trim((string) $_POST['email'])),
            'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
            'professional_title' => trim((string) ($_POST['professional_title'] ?? '')) ?: null,
            'registration_number' => trim((string) ($_POST['registration_number'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'is_active' => 1,
            'must_change_password' => isset($_POST['must_change_password']) ? 1 : 0,
        ]);
        AuditService::log('CREAR', 'usuarios', $id, 'Usuario creado: ' . $username);
        flash('success', 'Usuario creado. Entregue la contraseña por un canal seguro.');
        redirect('/usuarios');
    }

    public function update(int $id): void
    {
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['name' => 'required|max:120', 'email' => 'required|email|max:150', 'role_id' => 'required|int']);

        if ((int) $id === (int) Auth::id() && (int) $_POST['role_id'] !== (int) $user['role_id']) {
            flash('danger', 'No puede cambiar su propio rol.');
            redirect('/usuarios');
        }
        $data = [
            'name' => trim((string) $_POST['name']),
            'email' => mb_strtolower(trim((string) $_POST['email'])),
            'role_id' => (int) $_POST['role_id'],
            'professional_title' => trim((string) ($_POST['professional_title'] ?? '')) ?: null,
            'registration_number' => trim((string) ($_POST['registration_number'] ?? '')) ?: null,
            'phone' => trim((string) ($_POST['phone'] ?? '')) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ((int) $id === (int) Auth::id() && !$data['is_active']) {
            flash('danger', 'No puede desactivar su propio usuario.');
            redirect('/usuarios');
        }
        $changed = AuditService::diff($user, $data);

        if (!empty($_POST['new_password'])) {
            if (mb_strlen((string) $_POST['new_password']) < 10) {
                flash('danger', 'La nueva contraseña debe tener al menos 10 caracteres.');
                redirect('/usuarios');
            }
            $data['password_hash'] = password_hash((string) $_POST['new_password'], PASSWORD_DEFAULT);
            $data['must_change_password'] = 1;
            $data['failed_attempts'] = 0;
            $data['locked_until'] = null;
            $changed[] = 'password';
        }
        Database::update('users', $data, $id);
        AuditService::log('EDITAR', 'usuarios', $id, 'Usuario actualizado: ' . $user['username'], $changed);
        flash('success', 'Usuario actualizado.');
        redirect('/usuarios');
    }
}
