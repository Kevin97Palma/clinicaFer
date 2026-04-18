<?php
/**
 * SGC — Helper para respuestas JSON de la API (compatible PHP 7.1+)
 */

/**
 * Respuesta exitosa JSON y termina ejecución.
 * @param mixed $data
 */
function jsonSuccess($data = null, $message = 'OK', $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data,
        'errors'  => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Respuesta de error JSON y termina ejecución.
 */
function jsonError($message, $errors = [], $code = 400)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data'    => null,
        'errors'  => $errors,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Valida que el método HTTP sea el esperado.
 */
function requireMethod()
{
    $methods = func_get_args();
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods)) {
        jsonError('Método no permitido.', [], 405);
    }
}

/**
 * Obtiene el body JSON de la petición.
 */
function getJsonBody()
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : [];
}

/**
 * Valida campos requeridos y devuelve errores.
 */
function validarCampos(array $datos, array $requeridos)
{
    $errores = [];
    foreach ($requeridos as $campo) {
        if (empty($datos[$campo])) {
            $errores[] = "El campo '{$campo}' es obligatorio.";
        }
    }
    return $errores;
}
