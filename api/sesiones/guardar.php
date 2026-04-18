<?php
/**
 * SGC API — Registrar nota de sesión clínica
 * POST /api/sesiones/guardar.php
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('POST');
requirePermiso('sesiones.gestionar', true);

$body   = getJsonBody() ?: $_POST;
$errores = validarCampos($body, ['paciente_id', 'plan_id', 'fecha_sesion', 'asistio']);
if ($errores) jsonError('Datos incompletos.', $errores, 422);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();
$pacienteId = (int)$body['paciente_id'];

verificarPacienteEnClinica($pacienteId);

// Calcular número de sesión
$stmtNum = $pdo->prepare(
    "SELECT COUNT(*) FROM sesiones WHERE paciente_id = :pid AND plan_id = :plid"
);
$stmtNum->execute([':pid' => $pacienteId, ':plid' => $body['plan_id']]);
$numSesion = (int)$stmtNum->fetchColumn() + 1;

$stmt = $pdo->prepare(
    "INSERT INTO sesiones (
        paciente_id, plan_id, clinica_id, operativo_id,
        fecha_sesion, numero_sesion, objetivo_sesion,
        tecnicas_aplicadas, respuesta_paciente, observaciones_clinicas,
        tareas_asignadas, asistio
     ) VALUES (
        :pid, :plid, :cid, :uid,
        :fecha, :num, :objetivo,
        :tecnicas, :respuesta, :obs,
        :tareas, :asistio
     )"
);
$stmt->execute([
    ':pid'      => $pacienteId,
    ':plid'     => $body['plan_id'],
    ':cid'      => $clinicaId,
    ':uid'      => $userId,
    ':fecha'    => $body['fecha_sesion'],
    ':num'      => $numSesion,
    ':objetivo' => $body['objetivo_sesion'] ?? null,
    ':tecnicas' => $body['tecnicas_aplicadas'] ?? null,
    ':respuesta'=> $body['respuesta_paciente'] ?? null,
    ':obs'      => $body['observaciones_clinicas'] ?? null,
    ':tareas'   => $body['tareas_asignadas'] ?? null,
    ':asistio'  => $body['asistio'],
]);

$sesionId = (int)$pdo->lastInsertId();

// Actualizar cronograma si asistió
if ($body['asistio'] === 'asistio') {
    $pdo->prepare(
        "UPDATE cronogramas SET sesiones_completadas = sesiones_completadas + 1
         WHERE plan_id = :plid AND clinica_id = :cid"
    )->execute([':plid' => $body['plan_id'], ':cid' => $clinicaId]);
}

auditarAcceso('REGISTRAR_SESION', 'sesiones', $pacienteId, "Sesión #$numSesion");
jsonSuccess(['sesion_id' => $sesionId, 'numero_sesion' => $numSesion], "Sesión #$numSesion registrada.", 201);
