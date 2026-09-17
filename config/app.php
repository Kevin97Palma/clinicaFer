<?php
declare(strict_types=1);

use App\Core\Env;

/**
 * Configuración general. Todo valor sensible viene del archivo .env (fuera de Git).
 */
$url = rtrim(Env::get('APP_URL', 'http://localhost'), '/');

return [
    'name'          => Env::get('APP_NAME', 'SGC Psicología'),
    'env'           => Env::get('APP_ENV', 'production'),
    'debug'         => Env::get('APP_DEBUG', 'false') === 'true',
    'url'           => $url,
    'base_path'     => rtrim((string) parse_url($url, PHP_URL_PATH), '/'),
    'timezone'      => Env::get('APP_TIMEZONE', 'America/Guayaquil'),
    'storage_path'  => rtrim(Env::get('STORAGE_PATH', BASE_PATH . '/storage'), '/'),
    'max_upload_mb' => (int) Env::get('MAX_UPLOAD_MB', '10'),
    'session_idle_minutes' => (int) Env::get('SESSION_IDLE_MINUTES', '60'),
    'login_max_attempts'   => 5,
    'login_lock_minutes'   => 15,
    'db' => require __DIR__ . '/database.php',
];
