<?php
/**
 * SGC API — Marcar notificaciones como leídas
 * POST {id: X}       → marca una notificación
 * POST {all: true}   → marca todas las del usuario
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/response.php';

requireMethod('POST');
requireAuth(true);

$pdo    = db();
$userId = usuarioId();
$body   = getJsonBody();

if (!empty($body['all'])) {
    // Marcar todas como leídas
    $stmt = $pdo->prepare(
        "UPDATE notificaciones SET leida = 1 WHERE usuario_id = :uid AND leida = 0"
    );
    $stmt->execute([':uid' => $userId]);
    jsonSuccess(['actualizadas' => $stmt->rowCount()], 'Todas las notificaciones marcadas como leídas.');
}

$id = (int) ($body['id'] ?? 0);
if ($id <= 0) {
    jsonError('ID de notificación inválido.', [], 400);
}

// Verificar que la notificación pertenece al usuario actual
$stmtCheck = $pdo->prepare(
    "SELECT id FROM notificaciones WHERE id = :id AND usuario_id = :uid"
);
$stmtCheck->execute([':id' => $id, ':uid' => $userId]);
if (!$stmtCheck->fetch()) {
    jsonError('Notificación no encontrada.', [], 404);
}

$stmtUp = $pdo->prepare(
    "UPDATE notificaciones SET leida = 1 WHERE id = :id AND usuario_id = :uid"
);
$stmtUp->execute([':id' => $id, ':uid' => $userId]);

jsonSuccess(null, 'Notificación marcada como leída.');
