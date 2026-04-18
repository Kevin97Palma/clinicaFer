<?php
/**
 * SGC — Verificación de roles y permisos
 */

require_once dirname(__DIR__) . '/config/roles.php';

/**
 * Requiere que el usuario tenga permiso para una acción.
 * Si no, redirige o devuelve 403.
 */
function requirePermiso(string $accion, bool $esApi = false): void
{
    require_once __DIR__ . '/auth_check.php';
    requireAuth($esApi);

    if (!tienePermiso($accion)) {
        if ($esApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No tiene permiso para realizar esta acción.',
            ]);
            exit;
        }
        http_response_code(403);
        include dirname(__DIR__) . '/modules/errors/403.php';
        exit;
    }
}

/**
 * Requiere que el usuario tenga al menos el nivel de rol indicado.
 */
function requireNivel(string $rol_minimo, bool $esApi = false): void
{
    require_once __DIR__ . '/auth_check.php';
    requireAuth($esApi);

    if (!tieneNivel($rol_minimo)) {
        if ($esApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Nivel de acceso insuficiente.',
            ]);
            exit;
        }
        http_response_code(403);
        include dirname(__DIR__) . '/modules/errors/403.php';
        exit;
    }
}

/**
 * Verifica que el paciente pertenezca a la clínica activa.
 * Evita acceso cruzado entre clínicas.
 */
function verificarPacienteEnClinica(int $pacienteId): array
{
    require_once dirname(__DIR__) . '/config/database.php';
    $stmt = db()->prepare(
        "SELECT id, nombre, apellido, estado, estado_flujo, operativo_asignado_id
         FROM pacientes
         WHERE id = :id AND clinica_id = :cid AND deleted_at IS NULL"
    );
    $stmt->execute([':id' => $pacienteId, ':cid' => clinicaId()]);
    $paciente = $stmt->fetch();

    if (!$paciente) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado.']);
        exit;
    }
    return $paciente;
}

/**
 * Verifica que el operativo activo tenga asignado el paciente,
 * o que el rol sea suficientemente alto para ver pacientes de otros.
 */
function verificarAccesoPaciente(int $pacienteId): array
{
    $paciente = verificarPacienteEnClinica($pacienteId);

    if (tieneNivel(ROL_SUPERVISOR)) {
        return $paciente; // supervisor+ puede ver todos
    }

    // Operativo solo puede editar su propio paciente
    if ((int)$paciente['operativo_asignado_id'] !== usuarioId()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tiene acceso a este paciente.']);
        exit;
    }
    return $paciente;
}
