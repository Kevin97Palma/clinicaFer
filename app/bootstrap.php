<?php
declare(strict_types=1);

/**
 * Arranque de la aplicación: entorno, autoload, errores, sesión y cabeceras.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Helpers/functions.php';

App\Core\Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';
App\Core\Config::set($config);

date_default_timezone_set($config['timezone']);

// Errores: nunca mostrar detalles (credenciales, SQL) en producción
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
$logDir = $config['storage_path'] . '/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-' . date('Y-m') . '.log');
}

set_exception_handler(function (Throwable $e) use ($config): void {
    error_log('[SGC] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error inesperado. Intente nuevamente.']);
        return;
    }
    $debug = $config['debug'] ? $e->getMessage() : null;
    require BASE_PATH . '/resources/views/errors/500.php';
});

if (PHP_SAPI !== 'cli') {
    $https = (($_SERVER['HTTPS'] ?? '') === 'on')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || str_starts_with($config['url'], 'https://');

    session_name('sgc_psi');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $config['base_path'] === '' ? '/' : $config['base_path'] . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// La zona horaria configurable desde el panel prevalece sobre la del .env
try {
    $tz = App\Services\SettingsService::get('timezone');
    if ($tz && in_array($tz, timezone_identifiers_list(), true)) {
        date_default_timezone_set($tz);
    }
    App\Core\Database::connection()->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
} catch (Throwable $e) {
    error_log('[SGC] No se pudo cargar la configuración: ' . $e->getMessage());
}
