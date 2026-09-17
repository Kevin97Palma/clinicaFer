<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class PackageService
{
    /** Paquete activo más antiguo con sesiones disponibles. */
    public static function activeFor(int $patientId): ?array
    {
        return Database::one(
            "SELECT * FROM session_packages WHERE patient_id = ? AND status = 'activo' AND sessions_remaining > 0
             ORDER BY purchased_at, id LIMIT 1",
            [$patientId]
        );
    }

    /** Descuenta una sesión y registra el movimiento. Devuelve el paquete actualizado. */
    public static function consume(int $packageId, int $sessionId): array
    {
        Database::query('UPDATE session_packages SET sessions_used = sessions_used + 1 WHERE id = ?', [$packageId]);
        Database::insert('package_movements', [
            'package_id' => $packageId, 'session_id' => $sessionId, 'type' => 'consumo', 'quantity' => 1,
            'reason' => 'Sesión registrada', 'created_by' => Auth::id(),
        ]);
        return self::refreshStatus($packageId);
    }

    /** Revierte el consumo de una sesión (p. ej. si se corrige la asistencia). */
    public static function revert(int $packageId, int $sessionId, string $reason): array
    {
        Database::query('UPDATE session_packages SET sessions_used = GREATEST(sessions_used - 1, 0) WHERE id = ?', [$packageId]);
        Database::insert('package_movements', [
            'package_id' => $packageId, 'session_id' => $sessionId, 'type' => 'reversion', 'quantity' => -1,
            'reason' => $reason, 'created_by' => Auth::id(),
        ]);
        return self::refreshStatus($packageId);
    }

    /** Corrección manual del consumo (solo administrador), con auditoría. */
    public static function adjust(int $packageId, int $newUsed, string $reason): void
    {
        Database::transaction(function () use ($packageId, $newUsed, $reason) {
            $pkg = Database::one('SELECT * FROM session_packages WHERE id = ? FOR UPDATE', [$packageId]);
            $delta = $newUsed - (int) $pkg['sessions_used'];
            if ($delta === 0) {
                return;
            }
            Database::query('UPDATE session_packages SET sessions_used = ?, updated_by = ? WHERE id = ?', [$newUsed, Auth::id(), $packageId]);
            Database::insert('package_movements', [
                'package_id' => $packageId, 'type' => 'ajuste_manual', 'quantity' => $delta,
                'reason' => $reason, 'created_by' => Auth::id(),
            ]);
            $pkgNow = self::refreshStatus($packageId);
            AuditService::log('AJUSTE_PAQUETE', 'paquetes', $packageId,
                "Consumo corregido de {$pkg['sessions_used']} a {$newUsed}. Motivo: $reason", ['sessions_used']);
            self::syncAlert($pkgNow);
        });
    }

    public static function refreshStatus(int $packageId): array
    {
        $pkg = Database::one('SELECT * FROM session_packages WHERE id = ?', [$packageId]);
        if ($pkg['status'] === 'activo' && (int) $pkg['sessions_remaining'] <= 0) {
            Database::query("UPDATE session_packages SET status = 'finalizado' WHERE id = ?", [$packageId]);
            $pkg['status'] = 'finalizado';
        } elseif ($pkg['status'] === 'finalizado' && (int) $pkg['sessions_remaining'] > 0) {
            Database::query("UPDATE session_packages SET status = 'activo' WHERE id = ?", [$packageId]);
            $pkg['status'] = 'activo';
        }
        return $pkg;
    }

    /** Crea o resuelve la alerta de paquete por finalizar según el saldo actual. */
    public static function syncAlert(array $pkg): void
    {
        if ($pkg['status'] === 'activo' && (int) $pkg['sessions_remaining'] === 1) {
            $p = Database::one('SELECT first_name, last_name FROM patients WHERE id = ?', [$pkg['patient_id']]);
            AlertService::raise('PAQUETE_POR_FINALIZAR', "{$p['first_name']} {$p['last_name']}: queda 1 sesión del paquete.",
                (int) $pkg['patient_id'], 'package', (int) $pkg['id']);
        } elseif ((int) $pkg['sessions_remaining'] > 1 || $pkg['status'] !== 'activo') {
            AlertService::resolve('PAQUETE_POR_FINALIZAR', 'package', (int) $pkg['id']);
        }
    }

    /** Valor unitario regular y pagado de una sesión del paquete. */
    public static function unitValues(array $pkg): array
    {
        $n = max(1, (int) $pkg['sessions_total']);
        return [round((float) $pkg['price_regular'] / $n, 2), round((float) $pkg['total_paid'] / $n, 2)];
    }
}
