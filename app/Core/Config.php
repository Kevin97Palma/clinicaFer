<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];

    public static function set(array $items): void
    {
        self::$items = $items;
    }

    public static function get(string $key, $default = null)
    {
        return self::$items[$key] ?? $default;
    }
}
