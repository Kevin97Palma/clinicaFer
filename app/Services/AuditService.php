<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Bitácora de operaciones sensibles. Solo se registran nombres de campos modificados,
 * nunca el contenido clínico.
 */
final class AuditService
{
    public static function log(string $action, string $module, ?int $recordId = null, ?string $description = null, array $changedFields = [], ?int $userId = null): void
    {
        try {
            Database::insert('audit_logs', [
                'user_id'        => $userId ?? Auth::id(),
                'action'         => $action,
                'module'         => $module,
                'record_id'      => $recordId,
                'description'    => $description ? mb_substr($description, 0, 255) : null,
                'changed_fields' => $changedFields ? mb_substr(implode(', ', $changedFields), 0, 1000) : null,
                'ip_address'     => client_ip(),
                'user_agent'     => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            error_log('[SGC-AUDIT] ' . $e->getMessage());
        }
    }

    /** Devuelve los nombres de los campos cuyo valor cambió. */
    public static function diff(array $old, array $new): array
    {
        $changed = [];
        foreach ($new as $k => $v) {
            if (array_key_exists($k, $old) && (string) ($old[$k] ?? '') !== (string) ($v ?? '')) {
                $changed[] = $k;
            }
        }
        return $changed;
    }

    /** Registra el acceso a información clínica, como máximo una vez cada 10 minutos por paciente/sección. */
    public static function clinicalAccess(int $patientId, string $section): void
    {
        $key = $patientId . ':' . $section;
        $last = $_SESSION['_clinical_seen'][$key] ?? 0;
        if (time() - $last > 600) {
            $_SESSION['_clinical_seen'][$key] = time();
            self::log('VER_EXPEDIENTE_CLINICO', 'expediente', $patientId, 'Sección: ' . $section);
        }
    }
}
