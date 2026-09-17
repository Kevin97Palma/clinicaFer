<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class SettingsService
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::all('SELECT `key`, value FROM settings') as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, $default = '')
    {
        $v = self::all()[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`, value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)',
            [$key, $value, Auth::id()]
        );
        self::$cache = null;
    }

    public static function int(string $key, int $default): int
    {
        $v = self::get($key, (string) $default);
        return is_numeric($v) ? (int) $v : $default;
    }
}
