<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use RuntimeException;

final class AppointmentService
{
    /** Verifica que el profesional no tenga otra cita activa que se superponga. */
    public static function conflict(int $professionalId, string $startsAt, string $endsAt, ?int $ignoreId = null): ?array
    {
        return Database::one(
            "SELECT a.id, a.starts_at, p.first_name, p.last_name FROM appointments a JOIN patients p ON p.id = a.patient_id
              WHERE a.professional_id = ? AND a.status IN ('programada','confirmada','atendida')
                AND a.starts_at < ? AND a.ends_at > ? AND a.id <> ? LIMIT 1",
            [$professionalId, $endsAt, $startsAt, $ignoreId ?? 0]
        );
    }

    public static function create(array $data): int
    {
        $data['duration_min'] = (int) ($data['duration_min'] ?: SettingsService::int('session_duration', 45));
        $start = new \DateTime($data['starts_at']);
        $data['starts_at'] = $start->format('Y-m-d H:i:s');
        $data['ends_at'] = (clone $start)->modify('+' . $data['duration_min'] . ' minutes')->format('Y-m-d H:i:s');

        if ($c = self::conflict((int) $data['professional_id'], $data['starts_at'], $data['ends_at'])) {
            throw new RuntimeException('El horario se cruza con la cita de ' . $c['first_name'] . ' ' . $c['last_name'] . ' (' . fdate($c['starts_at'], true) . ').');
        }
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'programada';
        $id = Database::insert('appointments', $data);
        AuditService::log('CREAR', 'agenda', $id, 'Cita programada para ' . $data['starts_at']);
        AlertService::resolve('INACTIVIDAD_PACIENTE', 'patient', (int) $data['patient_id']);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $old = Database::one('SELECT * FROM appointments WHERE id = ?', [$id]);
        $start = new \DateTime($data['starts_at']);
        $data['starts_at'] = $start->format('Y-m-d H:i:s');
        $data['ends_at'] = (clone $start)->modify('+' . (int) $data['duration_min'] . ' minutes')->format('Y-m-d H:i:s');
        $status = $data['status'] ?? $old['status'];
        if (in_array($status, ['programada', 'confirmada', 'atendida'], true)
            && ($c = self::conflict((int) $data['professional_id'], $data['starts_at'], $data['ends_at'], $id))) {
            throw new RuntimeException('El horario se cruza con la cita de ' . $c['first_name'] . ' ' . $c['last_name'] . ' (' . fdate($c['starts_at'], true) . ').');
        }
        $data['updated_by'] = Auth::id();
        Database::update('appointments', $data, $id);
        AuditService::log('EDITAR', 'agenda', $id, 'Cita modificada', AuditService::diff($old, $data));
    }
}
