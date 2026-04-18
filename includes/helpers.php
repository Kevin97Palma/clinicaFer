<?php
/**
 * SGC — Funciones utilitarias globales
 */

/**
 * Genera el código de paciente para una clínica.
 * Formato: PREFIJO-AÑO-NNN  (ej: CATPI-2025-001)
 */
function generarCodigoPaciente(int $clinicaId): string
{
    require_once dirname(__DIR__) . '/config/database.php';

    // Obtener prefijo de la clínica desde config_json
    $stmt = db()->prepare("SELECT nombre, config_json FROM clinicas WHERE id = :id");
    $stmt->execute([':id' => $clinicaId]);
    $clinica = $stmt->fetch();

    $config  = json_decode($clinica['config_json'] ?? '{}', true);
    $prefijo = $config['codigo_prefijo'] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $clinica['nombre']), 0, 5));
    $anio    = date('Y');

    // Contar pacientes existentes de este año en la clínica
    $stmt2 = db()->prepare(
        "SELECT COUNT(*) FROM pacientes WHERE clinica_id = :cid AND YEAR(created_at) = :anio"
    );
    $stmt2->execute([':cid' => $clinicaId, ':anio' => $anio]);
    $count = (int) $stmt2->fetchColumn();

    return sprintf('%s-%s-%03d', $prefijo, $anio, $count + 1);
}

/**
 * Calcula la edad en años a partir de una fecha de nacimiento.
 */
function calcularEdad(string $fechaNacimiento): int
{
    return (int) (new DateTime($fechaNacimiento))->diff(new DateTime())->y;
}

/**
 * Sanitiza un string para prevenir XSS.
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Formatea una fecha para mostrar en español.
 */
function formatearFecha(?string $fecha, string $formato = 'd/m/Y'): string
{
    if (!$fecha) return '—';
    try {
        return (new DateTime($fecha))->format($formato);
    } catch (Exception $e) {
        return $fecha;
    }
}

/**
 * Formatea fecha y hora.
 */
function formatearFechaHora(?string $fechaHora): string
{
    return formatearFecha($fechaHora, 'd/m/Y H:i');
}

/**
 * Devuelve el label HTML de un estado de paciente con badge Bootstrap.
 */
function badgeEstado(string $estado): string
{
    $map = [
        'registrado'  => ['success', 'Registrado'],
        'en_espera'   => ['warning', 'En espera'],
        'activo'      => ['primary', 'Activo'],
        'egresado'    => ['secondary', 'Egresado'],
        'suspendido'  => ['danger', 'Suspendido'],
    ];
    [$color, $label] = $map[$estado] ?? ['light', $estado];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Devuelve badge del flujo clínico.
 */
function badgeFlujo(string $flujo): string
{
    $map = [
        'registrado'  => ['secondary', 'Registrado'],
        'anamnesis'   => ['info',      'Anamnesis'],
        'evaluacion'  => ['primary',   'Evaluación'],
        'diagnostico' => ['warning',   'Diagnóstico'],
        'plan'        => ['success',   'Plan'],
        'sesiones'    => ['success',   'Sesiones'],
        'seguimiento' => ['dark',      'Seguimiento'],
    ];
    [$color, $label] = $map[$flujo] ?? ['light', $flujo];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Calcula el porcentaje de avance del cronograma.
 */
function calcularAvanceCronograma(int $completadas, int $total): int
{
    if ($total === 0) return 0;
    return (int) round(($completadas / $total) * 100);
}

/**
 * Verifica si un JSON string es válido y lo decodifica.
 */
function safeJsonDecode(?string $json, array $default = []): array
{
    if (!$json) return $default;
    $decoded = json_decode($json, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
}

/**
 * Devuelve la IP real del cliente (considera proxies).
 */
function getClientIp(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            return explode(',', $_SERVER[$h])[0];
        }
    }
    return 'unknown';
}

/**
 * Trunca un texto a N caracteres sin cortar palabras.
 */
function truncar(string $texto, int $max = 100): string
{
    if (mb_strlen($texto) <= $max) return $texto;
    return mb_substr($texto, 0, $max - 3) . '...';
}

/**
 * Redirige a una URL y termina la ejecución.
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Nombre completo de un usuario a partir de nombre + apellido.
 */
function nombreCompleto(string $nombre, string $apellido): string
{
    return trim($nombre . ' ' . $apellido);
}
