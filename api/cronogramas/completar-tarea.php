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

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$tareaId = (int)($data['tarea_id'] ?? 0);

if (!$tareaId) {
    jsonError('Tarea requerida.');
}

$pdo       = db();
$clinicaId = clinicaId();

// Verificar que la tarea pertenece a la clínica (via cronograma)
$stmtCheck = $pdo->prepare(
    "SELECT t.id, t.cronograma_id, t.tipo, t.estado
     FROM tareas_cronograma t
     JOIN cronogramas cr ON cr.id = t.cronograma_id
     WHERE t.id = :id AND cr.clinica_id = :cid"
);
$stmtCheck->execute([':id' => $tareaId, ':cid' => $clinicaId]);
$tarea = $stmtCheck->fetch();

if (!$tarea) {
    jsonError('Tarea no encontrada.', [], 404);
}
if ($tarea['estado'] === 'completada') {
    jsonError('La tarea ya está completada.');
}

// Marcar como completada
$pdo->prepare(
    "UPDATE tareas_cronograma
     SET estado = 'completada', fecha_completada = NOW()
     WHERE id = :id"
)->execute([':id' => $tareaId]);

// Incrementar sesiones completadas del cronograma (solo si es tipo sesion)
if ($tarea['tipo'] === 'sesion') {
    $pdo->prepare(
        "UPDATE cronogramas
         SET sesiones_completadas = sesiones_completadas + 1, updated_at = NOW()
         WHERE id = :cid"
    )->execute([':cid' => $tarea['cronograma_id']]);

    // Verificar si el cronograma se completó
    $stmtCron = $pdo->prepare(
        "SELECT total_sesiones, sesiones_completadas FROM cronogramas WHERE id = :id"
    );
    $stmtCron->execute([':id' => $tarea['cronograma_id']]);
    $cron = $stmtCron->fetch();

    if ($cron && $cron['sesiones_completadas'] >= $cron['total_sesiones']) {
        $pdo->prepare(
            "UPDATE cronogramas SET estado = 'completado', updated_at = NOW() WHERE id = :id"
        )->execute([':id' => $tarea['cronograma_id']]);
    }
}

jsonSuccess([], 'Tarea completada.');
