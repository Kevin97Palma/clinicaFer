<?php
declare(strict_types=1);

namespace App\Core;

/** Estado de la sesión autenticada y verificación de permisos por rol. */
final class Auth
{
    private static ?array $user = null;
    private static ?array $permissions = null;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && self::id()) {
            self::$user = Database::one(
                'SELECT u.id, u.name, u.username, u.email, u.role_id, u.professional_title, u.registration_number,
                        u.must_change_password, u.is_active, r.slug AS role_slug, r.name AS role_name
                   FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
                [self::id()]
            );
            if (!self::$user || !self::$user['is_active']) {
                self::$user = null;
                self::logout();
            }
        }
        return self::$user;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['last_activity'] = time();
        self::$user = null;
        self::$permissions = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        self::$user = null;
        self::$permissions = null;
    }

    public static function permissions(): array
    {
        if (self::$permissions === null) {
            $u = self::user();
            self::$permissions = $u ? array_column(Database::all(
                'SELECT p.slug FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?',
                [$u['role_id']]
            ), 'slug') : [];
        }
        return self::$permissions;
    }

    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        if ($u['role_slug'] === 'administrador') {
            return true;
        }
        return in_array($permission, self::permissions(), true);
    }
}
