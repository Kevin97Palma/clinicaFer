<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private static array $sections = [];
    private static array $open = [];

    /** Captura un bloque (p. ej. scripts) desde una vista para imprimirlo en el layout. */
    public static function start(string $name): void
    {
        self::$open[] = $name;
        ob_start();
    }

    public static function stop(): void
    {
        $name = array_pop(self::$open);
        self::$sections[$name] = (self::$sections[$name] ?? '') . ob_get_clean();
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? '';
    }

    /** Renderiza una vista dentro de un layout. Toda salida debe escaparse con e(). */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::fetch($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::fetch($layout, $data + ['content' => $content]);
    }

    public static function fetch(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/resources/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Vista no encontrada: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
