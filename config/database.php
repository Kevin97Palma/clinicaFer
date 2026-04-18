<?php
/**
 * SGC — Conexión PDO singleton (compatible PHP 7.1+)
 */

define('DB_HOST',    '31.97.102.179');
define('DB_NAME',    'sgc_clinica');
define('DB_USER',    'root');
define('DB_PASS',    'Soporte@Palm4');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                error_log('[SGC-DB] ' . $e->getMessage());
                http_response_code(503);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
                exit;
            }
        }
        return self::$instance;
    }

    private function __clone() {}
}

/** Atajo global: db() devuelve la instancia PDO. */
function db()
{
    return Database::getInstance();
}
