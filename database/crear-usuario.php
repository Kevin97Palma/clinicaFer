<?php
declare(strict_types=1);

/**
 * Crea un usuario o asigna una contraseña a uno existente (solo por línea de comandos).
 *
 *   php database/crear-usuario.php --usuario=kevin --nombre="Kevin Palma" --email=kevin@dominio.com --rol=administrador [--password=SECRETO]
 *   php database/crear-usuario.php --usuario=kevin --password                (genera una contraseña aleatoria)
 *
 * Roles: administrador | psicologo | asistente
 * La contraseña nunca se guarda en el repositorio: se imprime una sola vez en pantalla.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta por línea de comandos.\n");
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;

$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $arg, $m)) {
        $args[$m[1]] = $m[2] ?? true;
    }
}

$username = isset($args['usuario']) && is_string($args['usuario']) ? mb_strtolower(trim($args['usuario'])) : null;
if (!$username) {
    exit("Falta --usuario. Ejemplo:\n  php database/crear-usuario.php --usuario=kevin --nombre=\"Kevin Palma\" --email=kevin@dominio.com --rol=administrador\n");
}

function randomPassword(int $length = 16): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#%+=';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

$password = (isset($args['password']) && is_string($args['password']) && $args['password'] !== '') ? $args['password'] : randomPassword();
if (mb_strlen($password) < 10) {
    exit("La contraseña debe tener al menos 10 caracteres.\n");
}
$mustChange = isset($args['forzar-cambio']);

$existing = Database::one('SELECT * FROM users WHERE username = ?', [$username]);

if ($existing) {
    Database::query(
        'UPDATE users SET password_hash = ?, must_change_password = ?, failed_attempts = 0, locked_until = NULL, is_active = 1 WHERE id = ?',
        [password_hash($password, PASSWORD_DEFAULT), $mustChange ? 1 : 0, $existing['id']]
    );
    $action = 'Contraseña actualizada';
    $id = (int) $existing['id'];
} else {
    $name = isset($args['nombre']) && is_string($args['nombre']) ? trim($args['nombre']) : null;
    $email = isset($args['email']) && is_string($args['email']) ? mb_strtolower(trim($args['email'])) : null;
    $roleSlug = isset($args['rol']) && is_string($args['rol']) ? $args['rol'] : 'psicologo';

    if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Para crear un usuario nuevo indique --nombre y --email válidos.\n");
    }
    $roleId = Database::value('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
    if (!$roleId) {
        exit("Rol no válido: use administrador, psicologo o asistente.\n");
    }
    if (Database::value('SELECT id FROM users WHERE email = ?', [$email])) {
        exit("Ya existe un usuario con ese correo.\n");
    }
    $id = Database::insert('users', [
        'role_id' => (int) $roleId,
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'professional_title' => isset($args['titulo']) && is_string($args['titulo']) ? $args['titulo'] : null,
        'registration_number' => isset($args['registro']) && is_string($args['registro']) ? $args['registro'] : null,
        'is_active' => 1,
        'must_change_password' => $mustChange ? 1 : 0,
    ]);
    $action = 'Usuario creado';
}

$role = Database::one('SELECT r.name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$id]);

echo str_repeat('=', 58) . "\n";
echo "  $action (#$id)\n";
echo str_repeat('=', 58) . "\n";
echo "  Usuario:     $username\n";
echo "  Rol:         {$role['name']}\n";
echo "  Contraseña:  $password\n";
echo str_repeat('-', 58) . "\n";
echo "  Guárdela ahora: no se vuelve a mostrar y se almacena cifrada.\n";
echo str_repeat('=', 58) . "\n";
