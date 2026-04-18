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
requirePermiso('pacientes.ver_lista');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$pacienteId    = (int)($data['paciente_id'] ?? 0);
$escala        = (int)($data['escala_avance'] ?? 0);
$fechaEval     = $data['fecha_evaluacion'] ?? date('Y-m-d');
$observaciones = trim($data['observaciones'] ?? '');

$errors = [];
if (!$pacienteId)          $errors[] = 'Paciente requerido.';
if ($escala < 0 || $escala > 100) $errors[] = 'Escala debe estar entre 0 y 100.';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

verificarPacienteEnClinica($pacienteId, $clinicaId);

// Plan activo
$stmtPlan = $pdo->prepare(
    "SELECT id FROM planes_intervencion
     WHERE paciente_id = :pid AND estado = 'activo' LIMIT 1"
);
$stmtPlan->execute([':pid' => $pacienteId]);
$plan = $stmtPlan->fetch();

$stmt = $pdo->prepare(
    "INSERT INTO seguimientos
        (paciente_id, plan_id, clinica_id, operativo_id, fecha_evaluacion,
         escala_avance, observaciones, created_at)
     VALUES (:pid, :plid, :cid, :uid, :fecha, :escala, :obs, NOW())"
);
$stmt->execute([
    ':pid'   => $pacienteId,
    ':plid'  => $plan ? $plan['id'] : null,
    ':cid'   => $clinicaId,
    ':uid'   => $userId,
    ':fecha' => $fechaEval,
    ':escala'=> $escala,
    ':obs'   => $observaciones ?: null,
]);

$segId = (int)$pdo->lastInsertId();

// Actualizar flujo
$pdo->prepare(
    "UPDATE pacientes SET estado_flujo = 'seguimiento', updated_at = NOW()
     WHERE id = :pid AND estado_flujo IN ('sesiones','plan')"
)->execute([':pid' => $pacienteId]);

auditarAcceso('CREAR_SEGUIMIENTO', 'seguimientos', $segId);
jsonSuccess(['id' => $segId], 'Seguimiento registrado.');
