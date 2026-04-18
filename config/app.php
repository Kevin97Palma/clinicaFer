<?php
/**
 * SGC — Constantes globales de la aplicación
 */

// Entorno
define('APP_ENV',  'development'); // cambiar a 'production' en Hostinger
define('APP_NAME', 'SGC — Sistema de Gestión Clínica');
define('APP_VERSION', '1.0.0');

// URL base (sin barra final)
// Detectar entorno automáticamente
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0') !== false) {
    define('APP_URL', 'http://localhost/sgc-clinica');
} else {
    define('APP_URL', 'https://socket-studio.com/demo-aplicaciones/SistemaClinico');
}

// Rutas del servidor
define('ROOT_PATH',    dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_MAX_MB', 20);

// Sesión
define('SESSION_NAME',    'sgc_session');
define('SESSION_LIFETIME', 7200); // 2 horas en segundos

// Seguridad
define('CSRF_TOKEN_NAME', 'sgc_csrf_token');
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_BLOCK_MINUTES', 15);

// Estados de paciente
define('ESTADO_REGISTRADO',  'registrado');
define('ESTADO_EN_ESPERA',   'en_espera');
define('ESTADO_ACTIVO',      'activo');
define('ESTADO_EGRESADO',    'egresado');
define('ESTADO_SUSPENDIDO',  'suspendido');

// Flujo clínico
define('FLUJO_REGISTRADO',  'registrado');
define('FLUJO_ANAMNESIS',   'anamnesis');
define('FLUJO_EVALUACION',  'evaluacion');
define('FLUJO_DIAGNOSTICO', 'diagnostico');
define('FLUJO_PLAN',        'plan');
define('FLUJO_SESIONES',    'sesiones');
define('FLUJO_SEGUIMIENTO', 'seguimiento');

// Roles
define('ROL_SUPERADMIN',    'superadmin');
define('ROL_GERENTE',       'gerente');
define('ROL_SUPERVISOR',    'supervisor');
define('ROL_OPERATIVO',     'operativo');
define('ROL_REPRESENTANTE', 'representante');

// Prefijo de código de paciente por clínica
define('CODIGO_CLINICA_DEFAULT', 'PAC');

// Display de errores según entorno
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Zona horaria (Ecuador)
date_default_timezone_set('America/Guayaquil');

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    // Compatible con PHP 7.1 (session_set_cookie_params con array requiere PHP 7.3+)
    session_set_cookie_params(
        SESSION_LIFETIME,
        '/',
        '',
        (APP_ENV === 'production'),
        true
    );
    session_start();
}
