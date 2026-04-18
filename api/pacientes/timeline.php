<?php
/**
 * GET /api/pacientes/timeline.php?id=X&offset=0&limit=10
 * Devuelve los últimos N eventos clínicos de un paciente en orden cronológico inverso.
 * Eventos: sesiones, citas, evaluaciones, diagnosticos, planes_intervencion
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';

header('Content-Type: application/json');

requireAuth(true);

$pacienteId = (int)($_GET['id'] ?? 0);
$offset     = max(0, (int)($_GET['offset'] ?? 0));
$limit      = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$clinicaId  = clinicaId();

if (!$pacienteId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de paciente requerido.']);
    exit;
}

$pdo = db();

// Verificar que el paciente pertenece a esta clínica
$stmtCheck = $pdo->prepare(
    "SELECT id FROM pacientes WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
);
$stmtCheck->execute([':id' => $pacienteId, ':cid' => $clinicaId]);
if (!$stmtCheck->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Paciente no encontrado en esta clínica.']);
    exit;
}

// UNION de eventos clínicos
$sql = "
SELECT tipo, fecha, ref, descripcion, operativo_id FROM (

    SELECT 'sesion' AS tipo,
           fecha_sesion AS fecha,
           CAST(numero_sesion AS CHAR) AS ref,
           objetivo_sesion AS descripcion,
           operativo_id
    FROM sesiones
    WHERE paciente_id = :pid1

    UNION ALL

    SELECT 'cita' AS tipo,
           fecha_hora AS fecha,
           tipo AS ref,
           CONCAT('Cita de ', tipo, ' — ', estado) AS descripcion,
           operativo_id
    FROM citas
    WHERE paciente_id = :pid2 AND clinica_id = :cid1

    UNION ALL

    SELECT 'evaluacion' AS tipo,
           fecha_aplicacion AS fecha,
           nombre_test AS ref,
           nombre_test AS descripcion,
           operativo_id
    FROM evaluaciones
    WHERE paciente_id = :pid3

    UNION ALL

    SELECT 'diagnostico' AS tipo,
           fecha_diagnostico AS fecha,
           COALESCE(codigo_dsm5, codigo_cie10, codigo_cie11, '') AS ref,
           impresion_diagnostica AS descripcion,
           operativo_id
    FROM diagnosticos
    WHERE paciente_id = :pid4

    UNION ALL

    SELECT 'plan' AS tipo,
           created_at AS fecha,
           estado AS ref,
           CONCAT('Plan ', estado, ' — ', COALESCE(enfoque_terapeutico, '')) AS descripcion,
           operativo_id
    FROM planes_intervencion
    WHERE paciente_id = :pid5

) AS eventos
ORDER BY fecha DESC
";

// Contar total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM ($sql) AS cnt");
$stmtCount->execute([
    ':pid1' => $pacienteId,
    ':pid2' => $pacienteId, ':cid1' => $clinicaId,
    ':pid3' => $pacienteId,
    ':pid4' => $pacienteId,
    ':pid5' => $pacienteId,
]);
$total = (int)$stmtCount->fetchColumn();

// Paginado
$stmtEvt = $pdo->prepare("$sql LIMIT :lim OFFSET :off");
$stmtEvt->bindValue(':pid1', $pacienteId, PDO::PARAM_INT);
$stmtEvt->bindValue(':pid2', $pacienteId, PDO::PARAM_INT);
$stmtEvt->bindValue(':cid1', $clinicaId,  PDO::PARAM_INT);
$stmtEvt->bindValue(':pid3', $pacienteId, PDO::PARAM_INT);
$stmtEvt->bindValue(':pid4', $pacienteId, PDO::PARAM_INT);
$stmtEvt->bindValue(':pid5', $pacienteId, PDO::PARAM_INT);
$stmtEvt->bindValue(':lim',  $limit,      PDO::PARAM_INT);
$stmtEvt->bindValue(':off',  $offset,     PDO::PARAM_INT);
$stmtEvt->execute();
$eventos = $stmtEvt->fetchAll();

// Configuración visual por tipo
$config = [
    'sesion'      => ['icono' => 'chat-heart-fill',    'color' => '#198754', 'label' => 'Sesión clínica'],
    'cita'        => ['icono' => 'calendar-check-fill', 'color' => '#0d6efd', 'label' => 'Cita'],
    'evaluacion'  => ['icono' => 'journal-check',       'color' => '#6f42c1', 'label' => 'Evaluación'],
    'diagnostico' => ['icono' => 'search-heart-fill',   'color' => '#dc3545', 'label' => 'Diagnóstico'],
    'plan'        => ['icono' => 'diagram-3-fill',      'color' => '#fd7e14', 'label' => 'Plan'],
];

// Recopilar IDs de operativos únicos para lookup
$operativoIds = array_unique(array_filter(array_column($eventos, 'operativo_id')));
$operativos   = [];
if (!empty($operativoIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($operativoIds), '?'));
    $stmtOp = $pdo->prepare(
        "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo
         FROM usuarios WHERE id IN ($inPlaceholders)"
    );
    $stmtOp->execute(array_values($operativoIds));
    foreach ($stmtOp->fetchAll() as $op) {
        $operativos[$op['id']] = $op['nombre_completo'];
    }
}

// Formatear respuesta
$resultado = [];
foreach ($eventos as $ev) {
    $tipo  = $ev['tipo'];
    $cfg   = $config[$tipo] ?? ['icono' => 'circle', 'color' => '#6c757d', 'label' => $tipo];
    $fecha = $ev['fecha'] ? new DateTime($ev['fecha']) : null;

    $descripcionShort = $ev['descripcion'] ?? '';
    if (mb_strlen($descripcionShort) > 100) {
        $descripcionShort = mb_substr($descripcionShort, 0, 97) . '…';
    }

    $resultado[] = [
        'tipo'             => $tipo,
        'tipo_label'       => $cfg['label'],
        'icono'            => $cfg['icono'],
        'color_hex'        => $cfg['color'],
        'fecha_fmt'        => $fecha ? $fecha->format('d/m/Y H:i') : '—',
        'fecha_iso'        => $ev['fecha'] ?? null,
        'ref'              => $ev['ref'] ?? '',
        'descripcion_short'=> $descripcionShort,
        'operativo'        => $ev['operativo_id'] ? ($operativos[$ev['operativo_id']] ?? null) : null,
    ];
}

echo json_encode([
    'success' => true,
    'data'    => [
        'eventos' => $resultado,
        'total'   => $total,
        'offset'  => $offset,
    ],
]);
