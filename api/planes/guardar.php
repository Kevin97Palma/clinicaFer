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

$pacienteId  = (int)($data['paciente_id'] ?? 0);
$objetivos   = trim($data['objetivos_generales'] ?? '');
$enfoque     = trim($data['enfoque_terapeutico'] ?? '');
$frecuencia  = (int)($data['frecuencia_sesiones'] ?? 2);
$duracion    = (int)($data['duracion_estimada_semanas'] ?? 12);
$estado      = $data['estado'] ?? 'borrador';

$errors = [];
if (!$pacienteId) $errors[] = 'Paciente requerido.';
if (!$objetivos)  $errors[] = 'Objetivos generales requeridos.';
if ($frecuencia < 1 || $frecuencia > 7) $errors[] = 'Frecuencia inválida (1-7).';
if ($duracion < 1 || $duracion > 104)   $errors[] = 'Duración inválida (1-104 semanas).';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$estadosValidos = ['borrador', 'activo', 'completado', 'suspendido'];
if (!in_array($estado, $estadosValidos)) $estado = 'borrador';

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

verificarPacienteEnClinica($pacienteId, $clinicaId);

// Si el nuevo plan es activo, desactivar los anteriores
if ($estado === 'activo') {
    $pdo->prepare(
        "UPDATE planes_intervencion SET estado = 'suspendido', updated_at = NOW()
         WHERE paciente_id = :pid AND estado = 'activo'"
    )->execute([':pid' => $pacienteId]);
}

$stmt = $pdo->prepare(
    "INSERT INTO planes_intervencion
        (paciente_id, clinica_id, operativo_id, objetivos_generales, enfoque_terapeutico,
         frecuencia_sesiones, duracion_estimada_semanas, estado, created_at, updated_at)
     VALUES (:pid, :cid, :uid, :obj, :enf, :frec, :dur, :est, NOW(), NOW())"
);
$stmt->execute([
    ':pid'  => $pacienteId,
    ':cid'  => $clinicaId,
    ':uid'  => $userId,
    ':obj'  => $objetivos,
    ':enf'  => $enfoque ?: null,
    ':frec' => $frecuencia,
    ':dur'  => $duracion,
    ':est'  => $estado,
]);
$planId = (int)$pdo->lastInsertId();

// Actualizar flujo del paciente
$pdo->prepare(
    "UPDATE pacientes SET estado_flujo = 'plan', updated_at = NOW()
     WHERE id = :pid AND estado_flujo IN ('registrado','anamnesis','evaluacion','diagnostico')"
)->execute([':pid' => $pacienteId]);

// Si el plan es activo, generar cronograma automáticamente
if ($estado === 'activo') {
    $totalSesiones = $frecuencia * $duracion;
    $fechaFin = date('Y-m-d', strtotime('+' . $duracion . ' weeks'));

    $stmtCron = $pdo->prepare(
        "INSERT INTO cronogramas
            (paciente_id, plan_id, clinica_id, operativo_id, fecha_inicio, fecha_fin_estimada,
             total_sesiones, sesiones_completadas, estado, auto_generado, created_at, updated_at)
         VALUES (:pid, :plid, :cid, :uid, CURDATE(), :ffin, :tot, 0, 'activo', 1, NOW(), NOW())"
    );
    $stmtCron->execute([
        ':pid'  => $pacienteId,
        ':plid' => $planId,
        ':cid'  => $clinicaId,
        ':uid'  => $userId,
        ':ffin' => $fechaFin,
        ':tot'  => $totalSesiones,
    ]);
    $cronogramaId = (int)$pdo->lastInsertId();

    // Generar tareas automáticas (una por sesión)
    $diasPorSemana = 7 / $frecuencia;
    for ($i = 1; $i <= $totalSesiones; $i++) {
        $diasOffset = (int)round(($i - 1) * $diasPorSemana);
        $fechaTarea = date('Y-m-d', strtotime('+' . $diasOffset . ' days'));
        $pdo->prepare(
            "INSERT INTO tareas_cronograma
                (cronograma_id, paciente_id, titulo, tipo, fecha_programada,
                 responsable_id, estado, orden, created_at)
             VALUES (:cid, :pid, :titulo, 'sesion', :fecha, :uid, 'pendiente', :ord, NOW())"
        )->execute([
            ':cid'   => $cronogramaId,
            ':pid'   => $pacienteId,
            ':titulo'=> 'Sesion ' . $i,
            ':fecha' => $fechaTarea,
            ':uid'   => $userId,
            ':ord'   => $i,
        ]);
    }

    auditarAcceso('CREAR_PLAN', 'planes_intervencion', $planId);
    jsonSuccess([
        'id'           => $planId,
        'cronograma_id'=> $cronogramaId,
        'tareas'       => $totalSesiones,
    ], 'Plan creado y cronograma generado automaticamente con ' . $totalSesiones . ' sesiones.');
}

auditarAcceso('CREAR_PLAN', 'planes_intervencion', $planId);
jsonSuccess(['id' => $planId], 'Plan guardado.');
