<?php
/**
 * SGC — Router principal / entry point
 * Redirige al login si no hay sesión, o al dashboard si la hay.
 */

require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['clinica_activa_id'])) {
    header('Location: ' . APP_URL . '/modules/dashboard/index.php');
} else {
    header('Location: ' . APP_URL . '/modules/auth/login.php');
}
exit;
