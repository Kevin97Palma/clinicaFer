<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use PDO;
use Throwable;

/** Respaldo SQL comprimido generado desde PHP, guardado fuera del directorio público. */
final class BackupService
{
    public static function dir(): string
    {
        return Config::get('storage_path') . '/backups';
    }

    public static function run(string $type = 'manual'): array
    {
        $file = 'respaldo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.sql.gz';
        $path = self::dir() . '/' . $file;
        try {
            if (!is_dir(self::dir()) && !mkdir(self::dir(), 0750, true)) {
                throw new \RuntimeException('No existe el directorio de respaldos.');
            }
            $gz = gzopen($path, 'wb6');
            if (!$gz) {
                throw new \RuntimeException('No se pudo crear el archivo de respaldo.');
            }
            $pdo = Database::connection();
            gzwrite($gz, "-- Respaldo SGC Psicología · " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
            foreach ($tables as [$table]) {
                $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
                gzwrite($gz, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");

                // Omitir columnas generadas en los INSERT
                $cols = array_column(array_filter(
                    $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(),
                    fn($c) => !str_contains(strtolower((string) $c['Extra']), 'generated')
                ), 'Field');
                $colList = implode(',', array_map(fn($c) => "`$c`", $cols));

                $stmt = $pdo->query("SELECT $colList FROM `$table`", PDO::FETCH_NUM);
                $batch = [];
                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                    if (count($batch) === 200) {
                        gzwrite($gz, "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }
                if ($batch) {
                    gzwrite($gz, "INSERT INTO `$table` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
                }
                gzwrite($gz, "\n");
            }
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
            gzclose($gz);
            @chmod($path, 0640);

            $size = (int) filesize($path);
            Database::insert('backup_logs', ['user_id' => Auth::id(), 'type' => $type, 'file_name' => $file, 'size_bytes' => $size, 'result' => 'exito', 'message' => count($tables) . ' tablas respaldadas']);
            AuditService::log('RESPALDO', 'respaldos', null, "Respaldo generado: $file");
            return ['ok' => true, 'file' => $file, 'size' => $size];
        } catch (Throwable $e) {
            error_log('[SGC-BACKUP] ' . $e->getMessage());
            if (is_file($path)) {
                @unlink($path);
            }
            Database::insert('backup_logs', ['user_id' => Auth::id(), 'type' => $type, 'file_name' => null, 'result' => 'error', 'message' => mb_substr($e->getMessage(), 0, 255)]);
            return ['ok' => false, 'message' => 'No se pudo generar el respaldo. Revise el registro de errores.'];
        }
    }
}
