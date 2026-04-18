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
requirePermiso('clinicas.crear');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$nombre    = trim($data['nombre'] ?? '');
$ruc       = trim($data['ruc'] ?? '');
$telefono  = trim($data['telefono'] ?? '');
$email     = trim($data['email'] ?? '');
$direccion = trim($data['direccion'] ?? '');

$errors = [];
if (!$nombre) $errors[] = 'Nombre requerido.';
if (!$ruc)    $errors[] = 'RUC requerido.';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$pdo = db();

$stmtDup = $pdo->prepare("SELECT id FROM clinicas WHERE ruc = :ruc AND deleted_at IS NULL");
$stmtDup->execute([':ruc' => $ruc]);
if ($stmtDup->fetch()) {
    jsonError('Ya existe una clinica con ese RUC.');
}

$stmt = $pdo->prepare(
    "INSERT INTO clinicas (nombre, ruc, telefono, email, direccion, activo, created_at)
     VALUES (:nom, :ruc, :tel, :email, :dir, 1, NOW())"
);
$stmt->execute([
    ':nom'  => $nombre,
    ':ruc'  => $ruc,
    ':tel'  => $telefono ?: null,
    ':email'=> $email ?: null,
    ':dir'  => $direccion ?: null,
]);

$clinicaId = (int)$pdo->lastInsertId();

auditarAcceso('CREAR_CLINICA', 'clinicas', $clinicaId);
jsonSuccess(['id' => $clinicaId], 'Clinica creada exitosamente.');
