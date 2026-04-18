<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requireMethod('POST');
requireAuth();
requirePermiso('usuarios.crear');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$nombre   = trim($data['nombre'] ?? '');
$apellido = trim($data['apellido'] ?? '');
$cedula   = trim($data['cedula'] ?? '');
$email    = trim($data['email'] ?? '');
$telefono = trim($data['telefono'] ?? '');
$rol      = $data['rol'] ?? 'operativo';

$errors = [];
if (!$nombre)   $errors[] = 'Nombre requerido.';
if (!$apellido) $errors[] = 'Apellido requerido.';
if (!$cedula)   $errors[] = 'Cedula requerida.';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valido requerido.';

$rolesPermitidos = ['operativo', 'supervisor', 'gerente'];
if (!in_array($rol, $rolesPermitidos)) {
    $errors[] = 'Rol invalido.';
}

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$pdo       = db();
$clinicaId = clinicaId();

// Verificar duplicados
$stmtDup = $pdo->prepare("SELECT id FROM usuarios WHERE (email = :email OR cedula = :cedula) AND deleted_at IS NULL");
$stmtDup->execute([':email' => $email, ':cedula' => $cedula]);
if ($stmtDup->fetch()) {
    jsonError('Ya existe un usuario con ese email o cedula.');
}

// Crear usuario con clave = cedula
$passHash = password_hash($cedula, PASSWORD_BCRYPT);

$pdo->beginTransaction();

$stmtU = $pdo->prepare(
    "INSERT INTO usuarios (cedula, nombre, apellido, email, password_hash, telefono, activo, primer_login, created_at)
     VALUES (:ced, :nom, :ape, :email, :pass, :tel, 1, 1, NOW())"
);
$stmtU->execute([
    ':ced'  => $cedula,
    ':nom'  => $nombre,
    ':ape'  => $apellido,
    ':email'=> $email,
    ':pass' => $passHash,
    ':tel'  => $telefono ?: null,
]);
$userId = (int)$pdo->lastInsertId();

// Asignar a la clínica con el rol
$pdo->prepare(
    "INSERT INTO usuario_clinica (usuario_id, clinica_id, rol, activo, created_at)
     VALUES (:uid, :cid, :rol, 1, NOW())"
)->execute([':uid' => $userId, ':cid' => $clinicaId, ':rol' => $rol]);

$pdo->commit();

auditarAcceso('CREAR_USUARIO', 'usuarios', $userId);
jsonSuccess(['id' => $userId], 'Usuario creado. Contrasena inicial: cedula del usuario.');
