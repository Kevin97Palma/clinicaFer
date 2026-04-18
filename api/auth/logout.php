<?php
/**
 * SGC API — Logout
 * POST /api/auth/logout.php
 */

require_once dirname(__DIR__, 2) . '/config/app.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirigir al login
header('Location: ' . APP_URL . '/modules/auth/login.php');
exit;
