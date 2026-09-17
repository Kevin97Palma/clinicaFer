<?php
declare(strict_types=1);

use App\Core\Env;

// Credenciales exclusivamente desde .env — nunca escribirlas aquí.
return [
    'host'    => Env::get('DB_HOST', 'localhost'),
    'port'    => (int) Env::get('DB_PORT', '3306'),
    'name'    => Env::get('DB_NAME', 'psicologia'),
    'user'    => Env::get('DB_USER', ''),
    'pass'    => Env::get('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
