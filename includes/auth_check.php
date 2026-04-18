<?php
/**
 * SGC — Middleware de autenticación
 * Incluir al inicio de cualquier página o API que requiera sesión activa.
 */

require_once dirname(__DIR__) . '/config/app.php';

/**
 * Verifica que el usuario tenga sesión activa.
 * Si no, redirige al login (páginas) o devuelve JSON 401 (API).
 */
function requireAuth(bool $esApi = false): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['clinica_activa_id'])) {
        if ($esApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Sesión no iniciada o expirada.',
            ]);
            exit;
        }
        $login = APP_URL . '/modules/auth/login.php';
        header("Location: $login");
        exit;
    }
}

/**
 * Devuelve el ID del usuario en sesión.
 */
function usuarioId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Devuelve el ID de la clínica activa.
 */
function clinicaId(): int
{
    return (int) ($_SESSION['clinica_activa_id'] ?? 0);
}

/**
 * Devuelve el rol activo del usuario.
 */
function rolActivo(): string
{
    return $_SESSION['rol_activo'] ?? '';
}

/**
 * Genera un CSRF token y lo guarda en sesión.
 */
function generarCsrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Valida el CSRF token enviado en el formulario/API.
 */
function validarCsrfToken(string $tokenEnviado): bool
{
    $tokenGuardado = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    return hash_equals($tokenGuardado, $tokenEnviado);
}

/**
 * Registra un acceso en la tabla de auditoría.
 */
function auditarAcceso(string $accion, string $modulo, ?int $pacienteId = null, string $detalle = ''): void
{
    try {
        require_once dirname(__DIR__) . '/config/database.php';
        $stmt = db()->prepare(
            "INSERT INTO auditoria_accesos (usuario_id, clinica_id, paciente_id, accion, modulo, detalle, ip)
             VALUES (:uid, :cid, :pid, :accion, :modulo, :detalle, :ip)"
        );
        $stmt->execute([
            ':uid'    => usuarioId(),
            ':cid'    => clinicaId(),
            ':pid'    => $pacienteId,
            ':accion' => $accion,
            ':modulo' => $modulo,
            ':detalle'=> $detalle,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    } catch (Throwable $e) {
        error_log('[SGC-AUDIT] ' . $e->getMessage());
    }
}
