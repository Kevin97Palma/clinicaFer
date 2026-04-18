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

$pacienteId            = (int)($data['paciente_id'] ?? 0);
$nombreTest            = trim($data['nombre_test'] ?? '');
$fechaAplicacion       = $data['fecha_aplicacion'] ?? '';
$interpretacion        = trim($data['interpretacion_cualitativa'] ?? '');

$errors = [];
if (!$pacienteId)   $errors[] = 'Paciente requerido.';
if (!$nombreTest)   $errors[] = 'Nombre del test requerido.';
if (!$fechaAplicacion) $errors[] = 'Fecha de aplicación requerida.';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

verificarPacienteEnClinica($pacienteId, $clinicaId);

$stmt = $pdo->prepare(
    "INSERT INTO evaluaciones
        (paciente_id, clinica_id, operativo_id, nombre_test, fecha_aplicacion,
         resultados_cuantitativos_json, interpretacion_cualitativa, created_at)
     VALUES (:pid, :cid, :uid, :test, :fecha, :res, :interp, NOW())"
);
$stmt->execute([
    ':pid'   => $pacienteId,
    ':cid'   => $clinicaId,
    ':uid'   => $userId,
    ':test'  => $nombreTest,
    ':fecha' => $fechaAplicacion,
    ':res'   => '{}',
    ':interp'=> $interpretacion,
]);

auditarAcceso('CREAR_EVALUACION', 'evaluaciones', (int)$pdo->lastInsertId());
jsonSuccess(['id' => (int)$pdo->lastInsertId()], 'Evaluación guardada.');
