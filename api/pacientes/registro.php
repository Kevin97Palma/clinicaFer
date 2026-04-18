<?php
/**
 * SGC API — Registrar paciente
 * POST /api/pacientes/registro.php
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requireMethod('POST');
requirePermiso('pacientes.registrar', true);

$body = getJsonBody() ?: $_POST;

$requeridos = ['nombre','apellido','fecha_nacimiento','sexo','representante_nombre',
               'representante_cedula','motivo_consulta'];
$errores = validarCampos($body, $requeridos);
if ($errores) jsonError('Faltan campos obligatorios.', $errores, 422);

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

$pdo->beginTransaction();
try {
    // Generar código único de paciente
    $codigo = generarCodigoPaciente($clinicaId);

    // Crear usuario representante (si tiene email o cédula)
    $repUserId = null;
    $repCedula = trim($body['representante_cedula']);
    $repEmail  = trim($body['representante_email'] ?? '');

    // Buscar si ya existe usuario con esa cédula
    $stmtBuscar = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = :cedula LIMIT 1");
    $stmtBuscar->execute([':cedula' => $repCedula]);
    $userExistente = $stmtBuscar->fetch();

    if (!$userExistente && $repEmail) {
        // Crear usuario representante nuevo
        $hashPass = password_hash($repCedula, PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare(
            "INSERT INTO usuarios (cedula, nombre, apellido, email, password_hash, primer_login)
             VALUES (:cedula, :nombre, :apellido, :email, :pass, 1)"
        );
        $nombreRep   = trim($body['representante_nombre']);
        $partes      = explode(' ', $nombreRep, 2);
        $nombreParts = $partes[0];
        $apellidoParts = $partes[1] ?? 'N/A';
        $stmtUser->execute([
            ':cedula'   => $repCedula,
            ':nombre'   => $nombreParts,
            ':apellido' => $apellidoParts,
            ':email'    => $repEmail,
            ':pass'     => $hashPass,
        ]);
        $repUserId = (int) $pdo->lastInsertId();

        // Asignar rol representante en la clínica
        $pdo->prepare(
            "INSERT INTO usuario_clinica (usuario_id, clinica_id, rol) VALUES (:uid, :cid, 'representante')"
        )->execute([':uid' => $repUserId, ':cid' => $clinicaId]);
    } elseif ($userExistente) {
        $repUserId = (int) $userExistente['id'];
    }

    // Insertar paciente
    $stmt = $pdo->prepare(
        "INSERT INTO pacientes (
            clinica_id, codigo_paciente, nombre, apellido, fecha_nacimiento, sexo, cedula,
            representante_nombre, representante_cedula, representante_telefono,
            representante_email, representante_parentesco,
            motivo_consulta, fecha_ingreso,
            operativo_asignado_id, usuario_representante_id,
            estado, estado_flujo, created_by
        ) VALUES (
            :cid, :codigo, :nombre, :apellido, :fnac, :sexo, :cedula,
            :rep_nombre, :rep_cedula, :rep_tel,
            :rep_email, :rep_parentesco,
            :motivo, :fecha_ingreso,
            :operativo_id, :rep_user_id,
            'registrado', 'registrado', :created_by
        )"
    );

    $stmt->execute([
        ':cid'             => $clinicaId,
        ':codigo'          => $codigo,
        ':nombre'          => trim($body['nombre']),
        ':apellido'        => trim($body['apellido']),
        ':fnac'            => $body['fecha_nacimiento'],
        ':sexo'            => $body['sexo'],
        ':cedula'          => $body['cedula'] ?? null,
        ':rep_nombre'      => trim($body['representante_nombre']),
        ':rep_cedula'      => $repCedula,
        ':rep_tel'         => $body['representante_telefono'] ?? null,
        ':rep_email'       => $repEmail ?: null,
        ':rep_parentesco'  => $body['representante_parentesco'] ?? null,
        ':motivo'          => trim($body['motivo_consulta']),
        ':fecha_ingreso'   => $body['fecha_ingreso'] ?? date('Y-m-d'),
        ':operativo_id'    => tieneNivel(ROL_SUPERVISOR) ? ($body['operativo_id'] ?? $userId) : $userId,
        ':rep_user_id'     => $repUserId,
        ':created_by'      => $userId,
    ]);

    $pacienteId = (int) $pdo->lastInsertId();

    // Si es operativo, verificar si necesita solicitud de aprobación
    $necesitaSolicitud = !tieneNivel(ROL_SUPERVISOR);
    if ($necesitaSolicitud) {
        // El operativo creó el paciente, estado queda en 'registrado' hasta aprobación
        // (No cambia estado_flujo aún)
    }

    $pdo->commit();
    auditarAcceso('REGISTRAR_PACIENTE', 'pacientes', $pacienteId, "Código: $codigo");

    jsonSuccess([
        'paciente_id'   => $pacienteId,
        'codigo'        => $codigo,
        'rep_usuario_creado' => !$userExistente && $repEmail,
        'redirect'      => APP_URL . '/modules/pacientes/perfil.php?id=' . $pacienteId,
    ], "Paciente registrado con código $codigo.");

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[SGC-PACIENTES] ' . $e->getMessage());
    jsonError('Error al registrar el paciente.', [], 500);
}
