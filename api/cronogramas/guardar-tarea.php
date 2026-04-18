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

$cronogramaId   = (int)($data['cronograma_id'] ?? 0);
$titulo         = trim($data['titulo'] ?? '');
$tipo           = $data['tipo'] ?? 'sesion';
$fechaProgramada= $data['fecha_programada'] ?? '';
$descripcion    = trim($data['descripcion'] ?? '');

$errors = [];
if (!$cronogramaId)    $errors[] = 'Cronograma requerido.';
if (!$titulo)          $errors[] = 'Título requerido.';
if (!$fechaProgramada) $errors[] = 'Fecha requerida.';

$tiposValidos = ['sesion', 'evaluacion', 'tarea_hogar', 'seguimiento'];
if (!in_array($tipo, $tiposValidos)) $tipo = 'sesion';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

// Verificar que el cronograma pertenece a la clínica
$stmtCheck = $pdo->prepare(
    "SELECT id, paciente_id FROM cronogramas WHERE id = :id AND clinica_id = :cid"
);
$stmtCheck->execute([':id' => $cronogramaId, ':cid' => $clinicaId]);
$cron = $stmtCheck->fetch();
if (!$cron) {
    jsonError('Cronograma no encontrado.', [], 404);
}

// Obtener el siguiente orden
$stmtOrd = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as next_ord FROM tareas_cronograma WHERE cronograma_id = :cid");
$stmtOrd->execute([':cid' => $cronogramaId]);
$nextOrd = (int)$stmtOrd->fetchColumn();

$stmt = $pdo->prepare(
    "INSERT INTO tareas_cronograma
        (cronograma_id, paciente_id, titulo, descripcion, tipo, fecha_programada,
         responsable_id, estado, orden, created_at)
     VALUES (:cid, :pid, :titulo, :desc, :tipo, :fecha, :uid, 'pendiente', :ord, NOW())"
);
$stmt->execute([
    ':cid'  => $cronogramaId,
    ':pid'  => $cron['paciente_id'],
    ':titulo'=> $titulo,
    ':desc' => $descripcion ?: null,
    ':tipo' => $tipo,
    ':fecha'=> $fechaProgramada,
    ':uid'  => $userId,
    ':ord'  => $nextOrd,
]);

// Actualizar total_sesiones en cronograma
$pdo->prepare(
    "UPDATE cronogramas SET total_sesiones = total_sesiones + 1, updated_at = NOW()
     WHERE id = :id"
)->execute([':id' => $cronogramaId]);

jsonSuccess(['id' => (int)$pdo->lastInsertId()], 'Tarea agregada.');
