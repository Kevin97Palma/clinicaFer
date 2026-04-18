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
requireNivel(ROL_SUPERVISOR);  // Solo supervisor, gerente o superadmin

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$pacienteId  = (int)($data['paciente_id'] ?? 0);
$operativoId = isset($data['operativo_id']) && $data['operativo_id'] !== ''
    ? (int)$data['operativo_id']
    : null;

if (!$pacienteId) {
    jsonError('Paciente requerido.');
}

$pdo       = db();
$clinicaId = clinicaId();

// Verificar que el paciente pertenece a la clínica
$stmtP = $pdo->prepare(
    "SELECT id, operativo_asignado_id FROM pacientes
     WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
);
$stmtP->execute([':id' => $pacienteId, ':cid' => $clinicaId]);
$paciente = $stmtP->fetch();

if (!$paciente) {
    jsonError('Paciente no encontrado.', [], 404);
}

// Verificar que el operativo pertenece a la clínica (si se especificó)
if ($operativoId) {
    $stmtOp = $pdo->prepare(
        "SELECT u.id FROM usuarios u
         JOIN usuario_clinica uc ON uc.usuario_id = u.id
         WHERE u.id = :uid AND uc.clinica_id = :cid AND u.activo = 1"
    );
    $stmtOp->execute([':uid' => $operativoId, ':cid' => $clinicaId]);
    if (!$stmtOp->fetch()) {
        jsonError('El usuario seleccionado no pertenece a esta clinica.');
    }
}

// Actualizar asignación
$pdo->prepare(
    "UPDATE pacientes
     SET operativo_asignado_id = :oid, updated_at = NOW()
     WHERE id = :id"
)->execute([':oid' => $operativoId, ':id' => $pacienteId]);

auditarAcceso('REASIGNAR_OPERATIVO', 'pacientes', $pacienteId);

$msg = $operativoId ? 'Psicologo asignado correctamente.' : 'Asignacion removida.';
jsonSuccess(['paciente_id' => $pacienteId, 'operativo_id' => $operativoId], $msg);
