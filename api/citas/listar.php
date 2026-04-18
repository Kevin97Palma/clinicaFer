<?php
/**
 * SGC API — Listar citas
 * GET /api/citas/listar.php?fecha=YYYY-MM-DD&estado=&paciente_id=
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('GET');
requireAuth(true);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();
$rol       = rolActivo();

$fecha      = $_GET['fecha'] ?? '';
$estado     = $_GET['estado'] ?? '';
$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$desde      = $_GET['desde'] ?? '';
$hasta      = $_GET['hasta'] ?? '';

$where  = "WHERE c.clinica_id = :cid";
$params = [':cid' => $clinicaId];

// Representante solo ve sus propias citas
if ($rol === ROL_REPRESENTANTE) {
    $where .= " AND p.usuario_representante_id = :uid";
    $params[':uid'] = $userId;
} elseif ($rol === ROL_OPERATIVO) {
    $where .= " AND c.operativo_id = :uid";
    $params[':uid'] = $userId;
}

if ($fecha) { $where .= " AND DATE(c.fecha_hora) = :fecha"; $params[':fecha'] = $fecha; }
if ($desde) { $where .= " AND DATE(c.fecha_hora) >= :desde"; $params[':desde'] = $desde; }
if ($hasta) { $where .= " AND DATE(c.fecha_hora) <= :hasta"; $params[':hasta'] = $hasta; }
if ($estado) { $where .= " AND c.estado = :estado"; $params[':estado'] = $estado; }
if ($pacienteId) { $where .= " AND c.paciente_id = :pid"; $params[':pid'] = $pacienteId; }

$stmt = $pdo->prepare(
    "SELECT c.id, c.fecha_hora, c.duracion_minutos, c.tipo, c.estado, c.notas,
            p.id as paciente_id, p.nombre as pac_nombre, p.apellido as pac_apellido, p.codigo_paciente,
            u.nombre as operativo_nombre, u.apellido as operativo_apellido
     FROM citas c
     JOIN pacientes p ON p.id = c.paciente_id
     JOIN usuarios u ON u.id = c.operativo_id
     $where
     ORDER BY c.fecha_hora ASC"
);
$stmt->execute($params);
$citas = $stmt->fetchAll();

jsonSuccess(['citas' => $citas, 'total' => count($citas)]);
