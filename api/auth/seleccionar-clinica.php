<?php
/**
 * SGC API — Seleccionar clínica activa cuando el usuario tiene múltiples
 * POST /api/auth/seleccionar-clinica.php
 * Body: { clinica_id }
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';

requireMethod('POST');

// En este paso solo verificamos que el usuario se haya autenticado,
// NO que tenga clínica activa (aún no la tiene, ese es el propósito de este endpoint)
if (empty($_SESSION['user_id'])) {
    jsonError('Sesión no iniciada.', [], 401);
}

$body      = getJsonBody() ?: ['clinica_id' => $_POST['clinica_id'] ?? null];
$clinicaId = (int) ($body['clinica_id'] ?? 0);

if (!$clinicaId) jsonError('clinica_id requerido.', [], 422);

// Verificar que el usuario tenga acceso a esa clínica
$clinicas = $_SESSION['clinicas_disponibles'] ?? [];
$seleccionada = null;
foreach ($clinicas as $c) {
    if ((int)$c['clinica_id'] === $clinicaId) {
        $seleccionada = $c;
        break;
    }
}

if (!$seleccionada) {
    jsonError('No tiene acceso a esa clínica.', [], 403);
}

$_SESSION['clinica_activa_id']     = $seleccionada['clinica_id'];
$_SESSION['clinica_activa_nombre'] = $seleccionada['clinica_nombre'];
$_SESSION['rol_activo']            = $seleccionada['rol'];

jsonSuccess([
    'redirect' => APP_URL . '/modules/dashboard/index.php',
    'clinica'  => $seleccionada,
], 'Clínica seleccionada.');
