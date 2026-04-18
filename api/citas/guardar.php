<?php
/**
 * SGC API — Crear / actualizar cita
 * POST /api/citas/guardar.php
 * Body: { id? (edición), paciente_id, fecha_hora, duracion_minutos, tipo, notas }
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('POST');
requirePermiso('citas.gestionar', true);

$body = getJsonBody() ?: $_POST;
$errores = validarCampos($body, ['paciente_id', 'fecha_hora', 'tipo']);
if ($errores) jsonError('Datos incompletos.', $errores, 422);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();
$citaId    = (int)($body['id'] ?? 0);

// Verificar paciente
verificarPacienteEnClinica((int)$body['paciente_id']);

if ($citaId) {
    // Actualizar
    $stmt = $pdo->prepare(
        "UPDATE citas SET
            paciente_id = :pid, fecha_hora = :fh, duracion_minutos = :dur,
            tipo = :tipo, estado = :estado, notas = :notas
         WHERE id = :id AND clinica_id = :cid"
    );
    $stmt->execute([
        ':pid'    => $body['paciente_id'],
        ':fh'     => $body['fecha_hora'],
        ':dur'    => (int)($body['duracion_minutos'] ?? 60),
        ':tipo'   => $body['tipo'],
        ':estado' => $body['estado'] ?? 'programada',
        ':notas'  => $body['notas'] ?? null,
        ':id'     => $citaId,
        ':cid'    => $clinicaId,
    ]);
    jsonSuccess(['id' => $citaId], 'Cita actualizada.');
} else {
    // Crear
    $stmt = $pdo->prepare(
        "INSERT INTO citas (paciente_id, clinica_id, operativo_id, fecha_hora, duracion_minutos, tipo, notas, created_by)
         VALUES (:pid, :cid, :oid, :fh, :dur, :tipo, :notas, :cb)"
    );
    $stmt->execute([
        ':pid'   => $body['paciente_id'],
        ':cid'   => $clinicaId,
        ':oid'   => $body['operativo_id'] ?? $userId,
        ':fh'    => $body['fecha_hora'],
        ':dur'   => (int)($body['duracion_minutos'] ?? 60),
        ':tipo'  => $body['tipo'],
        ':notas' => $body['notas'] ?? null,
        ':cb'    => $userId,
    ]);
    $id = (int) $pdo->lastInsertId();
    auditarAcceso('CREAR_CITA', 'citas', (int)$body['paciente_id']);
    jsonSuccess(['id' => $id], 'Cita creada exitosamente.', 201);
}
