<?php
/**
 * SGC API — Guardar consentimiento (POST, multipart/form-data)
 * Endpoint: /api/consentimientos/guardar.php
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';

requireMethod('POST');
requirePermiso('pacientes.ver_lista', true);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

// Leer campos POST
$pacienteId      = (int) ($_POST['paciente_id'] ?? 0);
$tipo            = trim($_POST['tipo'] ?? '');
$fechaFirma      = trim($_POST['fecha_firma'] ?? '');
$fechaVencimiento = trim($_POST['fecha_vencimiento'] ?? '') ?: null;

// Validaciones básicas
if ($pacienteId <= 0) {
    jsonError('ID de paciente inválido.', [], 400);
}
if (empty($tipo)) {
    jsonError('El tipo de consentimiento es obligatorio.', [], 400);
}
if (empty($fechaFirma)) {
    jsonError('La fecha de firma es obligatoria.', [], 400);
}

// Verificar que el paciente pertenece a la clínica
$stmtPac = $pdo->prepare(
    "SELECT id FROM pacientes WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
);
$stmtPac->execute([':id' => $pacienteId, ':cid' => $clinicaId]);
if (!$stmtPac->fetch()) {
    jsonError('Paciente no encontrado en esta clínica.', [], 404);
}

// Manejar archivo subido (opcional)
$archivoPath = null;

if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['archivo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonError('Error al subir el archivo. Código: ' . $file['error'], [], 400);
    }

    // Validar tamaño
    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        jsonError('El archivo supera el límite de ' . UPLOAD_MAX_MB . 'MB.', [], 400);
    }

    // Validar tipo MIME
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $finfo        = new finfo(FILEINFO_MIME_TYPE);
    $mimeType     = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMimes)) {
        jsonError('Tipo de archivo no permitido. Use PDF, JPG o PNG.', [], 400);
    }

    // Crear directorio de destino si no existe
    $dirDestino = STORAGE_PATH . '/consentimientos/' . $clinicaId;
    if (!is_dir($dirDestino)) {
        mkdir($dirDestino, 0755, true);
    }

    // Nombre único
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreFinal = $pacienteId . '_' . time() . '.' . strtolower($ext);
    $pathCompleto = $dirDestino . '/' . $nombreFinal;

    if (!move_uploaded_file($file['tmp_name'], $pathCompleto)) {
        jsonError('No se pudo guardar el archivo. Verifique los permisos del servidor.', [], 500);
    }

    // Ruta relativa para guardar en BD (desde raíz del proyecto)
    $archivoPath = 'storage/consentimientos/' . $clinicaId . '/' . $nombreFinal;
}

// Determinar estado
$estado = 'firmado';
if ($fechaVencimiento) {
    $hoy  = new DateTime();
    $venc = new DateTime($fechaVencimiento);
    if ($venc < $hoy) {
        $estado = 'vencido';
    }
}

// Verificar si ya existe un consentimiento para este paciente
$stmtExiste = $pdo->prepare(
    "SELECT id FROM consentimientos WHERE paciente_id = :pid AND clinica_id = :cid ORDER BY created_at DESC LIMIT 1"
);
$stmtExiste->execute([':pid' => $pacienteId, ':cid' => $clinicaId]);
$existente = $stmtExiste->fetch();

if ($existente) {
    // UPDATE del consentimiento más reciente
    $campos = "tipo = :tipo, fecha_firma = :ff, fecha_vencimiento = :fv, estado = :estado";
    $params = [
        ':tipo'   => $tipo,
        ':ff'     => $fechaFirma,
        ':fv'     => $fechaVencimiento,
        ':estado' => $estado,
        ':id'     => $existente['id'],
    ];
    if ($archivoPath !== null) {
        $campos    .= ', archivo_path = :path';
        $params[':path'] = $archivoPath;
    }
    $stmtUp = $pdo->prepare("UPDATE consentimientos SET {$campos} WHERE id = :id");
    $stmtUp->execute($params);
} else {
    // INSERT nuevo
    $stmtIn = $pdo->prepare(
        "INSERT INTO consentimientos
            (paciente_id, clinica_id, tipo, fecha_firma, fecha_vencimiento, archivo_path, estado, creado_por)
         VALUES
            (:pid, :cid, :tipo, :ff, :fv, :path, :estado, :uid)"
    );
    $stmtIn->execute([
        ':pid'    => $pacienteId,
        ':cid'    => $clinicaId,
        ':tipo'   => $tipo,
        ':ff'     => $fechaFirma,
        ':fv'     => $fechaVencimiento,
        ':path'   => $archivoPath,
        ':estado' => $estado,
        ':uid'    => $userId,
    ]);
}

auditarAcceso('GUARDAR_CONSENTIMIENTO', 'consentimientos', $pacienteId);
jsonSuccess(['estado' => $estado], 'Consentimiento guardado correctamente.');
