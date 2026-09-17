<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Acceso a MySQL con PDO y consultas preparadas.
 * Los nombres de tabla/columna de insert/update provienen siempre del código, nunca del usuario.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = Config::get('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => "`$c`", $cols)),
            implode(', ', array_map(fn($c) => ":$c", $cols))
        );
        self::query($sql, $data);
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, int $id): void
    {
        if (!$data) {
            return;
        }
        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        $data['__id'] = $id;
        self::query("UPDATE `$table` SET $set WHERE id = :__id", $data);
    }

    /** Ejecuta varias operaciones como una unidad: COMMIT o ROLLBACK completo. */
    public static function transaction(callable $fn)
    {
        $pdo = self::connection();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
