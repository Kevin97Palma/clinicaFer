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

$pacienteId    = (int)($data['paciente_id'] ?? 0);
$impresion     = trim($data['impresion_diagnostica'] ?? '');
$fechaDx       = $data['fecha_diagnostico'] ?? date('Y-m-d');
$dsm5          = trim($data['codigo_dsm5'] ?? '');
$cie10         = trim($data['codigo_cie10'] ?? '');
$cie11         = trim($data['codigo_cie11'] ?? '');
$severidad     = $data['nivel_severidad'] ?? '';
$diferencial   = trim($data['diagnostico_diferencial'] ?? '');
$observaciones = trim($data['observaciones'] ?? '');

$errors = [];
if (!$pacienteId) $errors[] = 'Paciente requerido.';
if (!$impresion)  $errors[] = 'Impresión diagnóstica requerida.';

if ($errors) {
    jsonError('Datos incompletos.', $errors);
}

$severidadesValidas = ['', 'leve', 'moderado', 'severo'];
if (!in_array($severidad, $severidadesValidas)) {
    $severidad = '';
}

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

verificarPacienteEnClinica($pacienteId, $clinicaId);

$stmt = $pdo->prepare(
    "INSERT INTO diagnosticos
        (paciente_id, clinica_id, operativo_id, impresion_diagnostica, codigo_dsm5,
         codigo_cie10, codigo_cie11, nivel_severidad, diagnostico_diferencial,
         observaciones, fecha_diagnostico, created_at)
     VALUES (:pid, :cid, :uid, :imp, :dsm5, :cie10, :cie11, :sev, :dif, :obs, :fecha, NOW())"
);
$stmt->execute([
    ':pid'  => $pacienteId,
    ':cid'  => $clinicaId,
    ':uid'  => $userId,
    ':imp'  => $impresion,
    ':dsm5' => $dsm5 ?: null,
    ':cie10'=> $cie10 ?: null,
    ':cie11'=> $cie11 ?: null,
    ':sev'  => $severidad ?: null,
    ':dif'  => $diferencial ?: null,
    ':obs'  => $observaciones ?: null,
    ':fecha'=> $fechaDx,
]);

$dxId = (int)$pdo->lastInsertId();

// Actualizar estado_flujo del paciente si estaba en evaluacion
$pdo->prepare(
    "UPDATE pacientes SET estado_flujo = 'diagnostico', updated_at = NOW()
     WHERE id = :pid AND estado_flujo IN ('registrado','anamnesis','evaluacion')"
)->execute([':pid' => $pacienteId]);

auditarAcceso('CREAR_DIAGNOSTICO', 'diagnosticos', $dxId);
jsonSuccess(['id' => $dxId], 'Diagnóstico guardado.');
