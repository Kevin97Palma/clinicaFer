<?php
/**
 * SGC API — Login
 * POST /api/auth/login.php
 * Body: { email, password }
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('POST');

$body = getJsonBody();
// También aceptar form data
if (empty($body)) {
    $body = ['email' => $_POST['email'] ?? '', 'password' => $_POST['password'] ?? ''];
}

$errores = validarCampos($body, ['email', 'password']);
if ($errores) jsonError('Datos incompletos.', $errores);

$email    = trim(strtolower($body['email']));
$password = $body['password'];

$pdo = db();

// 1. Buscar usuario por email
$stmt = $pdo->prepare(
    "SELECT id, cedula, nombre, apellido, email, password_hash, activo, primer_login,
            intentos_login, bloqueado_hasta
     FROM usuarios
     WHERE email = :email AND deleted_at IS NULL"
);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

if (!$usuario) {
    jsonError('Credenciales incorrectas.', [], 401);
}

// 2. Verificar si está bloqueado
if ($usuario['bloqueado_hasta'] && new DateTime() < new DateTime($usuario['bloqueado_hasta'])) {
    $hasta = (new DateTime($usuario['bloqueado_hasta']))->format('H:i');
    jsonError("Cuenta bloqueada por intentos fallidos. Intente después de las {$hasta}.", [], 429);
}

// 3. Verificar contraseña
if (!password_verify($password, $usuario['password_hash'])) {
    // Incrementar intentos
    $intentos = (int)$usuario['intentos_login'] + 1;
    if ($intentos >= LOGIN_MAX_ATTEMPTS) {
        $bloqueo = (new DateTime())->modify('+' . LOGIN_BLOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
        $pdo->prepare("UPDATE usuarios SET intentos_login = ?, bloqueado_hasta = ? WHERE id = ?")
            ->execute([$intentos, $bloqueo, $usuario['id']]);
        jsonError("Demasiados intentos fallidos. Cuenta bloqueada por " . LOGIN_BLOCK_MINUTES . " minutos.", [], 429);
    }
    $pdo->prepare("UPDATE usuarios SET intentos_login = ? WHERE id = ?")
        ->execute([$intentos, $usuario['id']]);
    jsonError('Credenciales incorrectas.', [], 401);
}

// 4. Verificar que está activo
if (!$usuario['activo']) {
    jsonError('Usuario inactivo. Contacte al administrador.', [], 403);
}

// 5. Obtener clínicas del usuario
$stmtClinicas = $pdo->prepare(
    "SELECT uc.clinica_id, uc.rol, c.nombre as clinica_nombre, c.logo
     FROM usuario_clinica uc
     JOIN clinicas c ON c.id = uc.clinica_id
     WHERE uc.usuario_id = :uid AND uc.activo = 1 AND c.activo = 1"
);
$stmtClinicas->execute([':uid' => $usuario['id']]);
$clinicas = $stmtClinicas->fetchAll();

if (empty($clinicas)) {
    jsonError('No tiene clínicas asignadas. Contacte al administrador.', [], 403);
}

// 6. Resetear intentos y establecer sesión
$pdo->prepare("UPDATE usuarios SET intentos_login = 0, bloqueado_hasta = NULL WHERE id = ?")
    ->execute([$usuario['id']]);

$_SESSION['user_id']              = $usuario['id'];
$_SESSION['user_nombre']          = $usuario['nombre'];
$_SESSION['user_apellido']        = $usuario['apellido'];
$_SESSION['user_email']           = $usuario['email'];
$_SESSION['clinicas_disponibles'] = $clinicas;

// Si solo tiene una clínica, seleccionarla automáticamente
if (count($clinicas) === 1) {
    $_SESSION['clinica_activa_id']   = $clinicas[0]['clinica_id'];
    $_SESSION['clinica_activa_nombre'] = $clinicas[0]['clinica_nombre'];
    $_SESSION['rol_activo']          = $clinicas[0]['rol'];
}

$response = [
    'usuario' => [
        'id'          => $usuario['id'],
        'nombre'      => $usuario['nombre'],
        'apellido'    => $usuario['apellido'],
        'email'       => $usuario['email'],
        'primer_login'=> (bool) $usuario['primer_login'],
    ],
    'clinicas'        => $clinicas,
    'seleccion_requerida' => count($clinicas) > 1,
    'redirect'        => count($clinicas) === 1
        ? APP_URL . '/modules/dashboard/index.php'
        : APP_URL . '/modules/auth/seleccionar-clinica.php',
];

jsonSuccess($response, 'Login exitoso.');
