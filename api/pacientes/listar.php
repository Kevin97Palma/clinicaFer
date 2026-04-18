<?php
/**
 * SGC API — Listar pacientes
 * GET /api/pacientes/listar.php?q=&estado=&flujo=&p=1
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('GET');
requirePermiso('pacientes.ver_lista', true);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

$busqueda = trim($_GET['q'] ?? '');
$estado   = $_GET['estado'] ?? '';
$flujo    = $_GET['flujo'] ?? '';
$pagina   = max(1, (int)($_GET['p'] ?? 1));
$porPagina = (int) min(50, max(5, (int)($_GET['per_page'] ?? 15)));
$offset    = ($pagina - 1) * $porPagina;

$where  = "WHERE p.clinica_id = :cid AND p.deleted_at IS NULL";
$params = [':cid' => $clinicaId];

if ($busqueda) {
    $where .= " AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.codigo_paciente LIKE :q)";
    $params[':q'] = '%' . $busqueda . '%';
}
if ($estado) { $where .= " AND p.estado = :estado"; $params[':estado'] = $estado; }
if ($flujo)  { $where .= " AND p.estado_flujo = :flujo"; $params[':flujo'] = $flujo; }
if (!tieneNivel(ROL_SUPERVISOR)) {
    $where .= " AND p.operativo_asignado_id = :uid";
    $params[':uid'] = $userId;
}

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM pacientes p $where");
$stmtTotal->execute($params);
$total = (int) $stmtTotal->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT p.id, p.codigo_paciente, p.nombre, p.apellido, p.fecha_nacimiento,
            p.sexo, p.estado, p.estado_flujo, p.fecha_ingreso,
            p.representante_nombre, p.representante_telefono,
            u.nombre as operativo_nombre, u.apellido as operativo_apellido
     FROM pacientes p
     LEFT JOIN usuarios u ON u.id = p.operativo_asignado_id
     $where ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset"
);
$stmt->execute(array_merge($params, [':limit' => $porPagina, ':offset' => $offset]));
$pacientes = $stmt->fetchAll();

jsonSuccess([
    'pacientes'   => $pacientes,
    'total'       => $total,
    'pagina'      => $pagina,
    'total_paginas' => (int) ceil($total / $porPagina),
]);
