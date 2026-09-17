<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Services\SettingsService;

/** Escapa salida HTML (prevención XSS). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '', array $query = []): string
{
    $u = Config::get('url') . '/' . ltrim($path, '/');
    return $query ? $u . '?' . http_build_query($query) : $u;
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function redirect_back(string $fallback = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // Solo redirigir a URLs propias del sistema
    if ($ref !== '' && str_starts_with($ref, Config::get('url'))) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Valor anterior del formulario tras un error de validación. */
function old(string $key, $default = '')
{
    return $_SESSION['_old'][$key] ?? $default;
}

function errors(): array
{
    static $errors = null;
    if ($errors === null) {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old_consumed']);
    }
    return $errors;
}

function field_error(string $key): string
{
    $err = errors()[$key] ?? null;
    return $err ? '<div class="invalid-feedback d-block">' . e($err) . '</div>' : '';
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function auth_user(): ?array
{
    return Auth::user();
}

function setting(string $key, $default = '')
{
    return SettingsService::get($key, $default);
}

function money($value): string
{
    $symbol = setting('currency', 'USD') === 'USD' ? '$' : setting('currency', 'USD') . ' ';
    return $symbol . number_format((float) $value, 2, '.', ',');
}

function fdate(?string $date, bool $withTime = false): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '—';
}

/** Fecha en palabras: «lunes 17 de septiembre» (sin depender del locale del servidor). */
function strftime_es(?string $date, bool $withYear = false): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $text = $days[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' de ' . $months[(int) date('n', $ts) - 1];
    return $withYear ? $text . ' de ' . date('Y', $ts) : $text;
}

function ftime(?string $time): string
{
    return $time ? substr(date('H:i', strtotime($time)), 0, 5) : '—';
}

/** Edad calculada desde la fecha de nacimiento (nunca almacenada). */
function age(?string $birthDate): string
{
    if (!$birthDate) {
        return '—';
    }
    $diff = (new DateTime($birthDate))->diff(new DateTime('today'));
    if ($diff->y < 3) {
        return $diff->y . ' a ' . $diff->m . ' m';
    }
    return $diff->y . ' años';
}

function age_years(?string $birthDate): int
{
    return $birthDate ? (new DateTime($birthDate))->diff(new DateTime('today'))->y : 0;
}

function label(string $group, ?string $value): string
{
    static $catalog = null;
    $catalog ??= require BASE_PATH . '/config/catalogs.php';
    return $catalog[$group][$value]['label'] ?? ($value === null ? '—' : ucfirst(str_replace('_', ' ', $value)));
}

function options(string $group): array
{
    static $catalog = null;
    $catalog ??= require BASE_PATH . '/config/catalogs.php';
    return array_map(fn($o) => $o['label'], $catalog[$group] ?? []);
}

function badge(string $group, ?string $value): string
{
    static $catalog = null;
    $catalog ??= require BASE_PATH . '/config/catalogs.php';
    $tone = $catalog[$group][$value]['tone'] ?? 'neutral';
    return '<span class="badge-soft badge-' . e($tone) . '">' . e(label($group, $value)) . '</span>';
}

function select_options(string $group, $selected = null, bool $empty = false): string
{
    $html = $empty ? '<option value="">Seleccione…</option>' : '';
    foreach (options($group) as $value => $text) {
        $sel = (string) $selected === (string) $value ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($text) . '</option>';
    }
    return $html;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $i = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
    return mb_strtoupper($i);
}

function nl2br_e(?string $text): string
{
    return nl2br(e($text));
}

function client_ip(): ?string
{
    $ip = $_SERVER['HTTP_X_REAL_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    return $ip && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function request_path(): string
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = Config::get('base_path');
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim($path, '/');
}

function nav_active(string $prefix): string
{
    $p = request_path();
    return ($prefix === '/' ? $p === '/' : str_starts_with($p, $prefix)) ? ' active' : '';
}
