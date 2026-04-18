<?php
/**
 * SGC — API: Guardar sesión rápida desde perfil de paciente
 * POST /api/sesiones/guardar-rapida.php
 * Body JSON: { paciente_id, plantilla, contenido_json, asistio, fecha? }
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';

header('Content-Type: application/json; charset=UTF-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido.', 405);
}

// Autenticación
requireAuth();

// Solo roles con permiso de gestionar sesiones
$rol = $_SESSION['rol_activo'] ?? '';
$rolesPermitidos = [ROL_OPERATIVO, ROL_SUPERVISOR, ROL_GERENTE, ROL_SUPERADMIN];
if (!in_array($rol, $rolesPermitidos, true)) {
    jsonError('Sin permisos para registrar sesiones.', 403);
}

// Leer body JSON
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonError('Cuerpo de la petición inválido.');
}

// Validar campos obligatorios
$pacienteId = (int)($body['paciente_id'] ?? 0);
$plantilla  = trim($body['plantilla'] ?? '');
$asistio    = trim($body['asistio'] ?? 'asistio');
$fecha      = trim($body['fecha'] ?? date('Y-m-d'));
$contenido  = $body['contenido_json'] ?? [];

$errores = [];

if (!$pacienteId) {
    $errores[] = 'Paciente inválido.';
}

$plantillasValidas = ['soap', 'dap', 'libre'];
if (!in_array($plantilla, $plantillasValidas, true)) {
    $errores[] = 'Plantilla inválida. Use: soap, dap o libre.';
}

$asistiosValidos = ['asistio', 'falto', 'cancelada', 'reprogramada'];
if (!in_array($asistio, $asistiosValidos, true)) {
    $asistio = 'asistio';
}

// Validar fecha
try {
    $fechaDT = new DateTime($fecha);
    $fecha   = $fechaDT->format('Y-m-d');
} catch (Exception $e) {
    $fecha = date('Y-m-d');
}

if (!empty($errores)) {
    jsonError(implode(' ', $errores), 422, $errores);
}

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

// Verificar que el paciente pertenece a esta clínica
$stmtPac = $pdo->prepare(
    "SELECT id, nombre, apellido, estado_flujo FROM pacientes
     WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
);
$stmtPac->execute([':id' => $pacienteId, ':cid' => $clinicaId]);
$paciente = $stmtPac->fetch();

if (!$paciente) {
    jsonError('Paciente no encontrado en esta clínica.', 404);
}

// Construir nota clínica según plantilla
switch ($plantilla) {
    case 'soap':
        $objetoSesion  = $contenido['subjetivo'] ?? '';
        $observaciones = sprintf(
            "SOAP\n\nSubjetivo: %s\n\nObjetivo: %s\n\nAnálisis: %s\n\nPlan: %s",
            $contenido['subjetivo'] ?? '',
            $contenido['objetivo']  ?? '',
            $contenido['analisis']  ?? '',
            $contenido['plan']      ?? ''
        );
        $tecnicas = 'Plantilla SOAP';
        break;

    case 'dap':
        $objetoSesion  = $contenido['descripcion'] ?? '';
        $observaciones = sprintf(
            "DAP\n\nDescripción: %s\n\nAnálisis: %s\n\nPlan: %s",
            $contenido['descripcion'] ?? '',
            $contenido['analisis']    ?? '',
            $contenido['plan']        ?? ''
        );
        $tecnicas = 'Plantilla DAP';
        break;

    default: // libre
        $objetoSesion  = $contenido['notas'] ?? '';
        $observaciones = $contenido['notas'] ?? '';
        $tecnicas      = 'Nota libre';
        break;
}

// Obtener número de sesión (siguiente en la secuencia del paciente)
$stmtNum = $pdo->prepare(
    "SELECT COALESCE(MAX(numero_sesion), 0) + 1 FROM sesiones WHERE paciente_id = :pid"
);
$stmtNum->execute([':pid' => $pacienteId]);
$numeroSesion = (int) $stmtNum->fetchColumn();

// Obtener plan activo (si existe)
$stmtPlan = $pdo->prepare(
    "SELECT id FROM planes_intervencion
     WHERE paciente_id = :pid AND estado = 'activo' LIMIT 1"
);
$stmtPlan->execute([':pid' => $pacienteId]);
$planId = $stmtPlan->fetchColumn() ?: null;

try {
    // Insertar sesión
    $stmtIns = $pdo->prepare(
        "INSERT INTO sesiones
         (paciente_id, plan_id, clinica_id, operativo_id,
          fecha_sesion, numero_sesion,
          objetivo_sesion, tecnicas_aplicadas, observaciones_clinicas,
          asistio, created_at)
         VALUES
         (:pid, :planid, :cid, :uid,
          :fecha, :num,
          :objetivo, :tecnicas, :obs,
          :asistio, NOW())"
    );

    $stmtIns->execute([
        ':pid'      => $pacienteId,
        ':planid'   => $planId,
        ':cid'      => $clinicaId,
        ':uid'      => $userId,
        ':fecha'    => $fecha,
        ':num'      => $numeroSesion,
        ':objetivo' => $objetoSesion,
        ':tecnicas' => $tecnicas,
        ':obs'      => $observaciones,
        ':asistio'  => $asistio,
    ]);

    $sesionId = (int) $pdo->lastInsertId();

    // Actualizar estado_flujo del paciente a 'sesiones' si todavía está en plan o anterior
    $etapasAnteriores = ['registrado', 'anamnesis', 'evaluacion', 'diagnostico', 'plan'];
    if (in_array($paciente['estado_flujo'], $etapasAnteriores, true)) {
        $pdo->prepare(
            "UPDATE pacientes SET estado_flujo = 'sesiones', updated_at = NOW()
             WHERE id = :id"
        )->execute([':id' => $pacienteId]);
    }

    // Actualizar sesiones_completadas en cronograma si hay uno activo y el paciente asistió
    if ($asistio === 'asistio' && $planId) {
        $pdo->prepare(
            "UPDATE cronogramas
             SET sesiones_completadas = sesiones_completadas + 1, updated_at = NOW()
             WHERE plan_id = :planid AND estado = 'activo'"
        )->execute([':planid' => $planId]);
    }

    // Evento de auditoría
    auditarAcceso('GUARDAR_SESION_RAPIDA', 'sesiones', $sesionId);

    jsonSuccess([
        'sesion_id'     => $sesionId,
        'numero_sesion' => $numeroSesion,
        'fecha'         => $fecha,
        'asistio'       => $asistio,
        'plantilla'     => $plantilla,
    ], "Sesión #{$numeroSesion} guardada correctamente.");

} catch (PDOException $e) {
    error_log('[SGC-SESION-RAPIDA] ' . $e->getMessage());
    jsonError('Error al guardar la sesión. Intente nuevamente.', 500);
}
