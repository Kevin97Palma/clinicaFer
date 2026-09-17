<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\SettingsService;

final class Patient
{
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, u.name AS professional_name FROM patients p LEFT JOIN users u ON u.id = p.professional_id WHERE p.id = ?',
            [$id]
        );
    }

    public static function fullName(array $p): string
    {
        return $p['first_name'] . ' ' . $p['last_name'];
    }

    /** Número de expediente correlativo por año: PSI-2026-0001 */
    public static function nextFileNumber(): string
    {
        $prefix = SettingsService::get('file_number_prefix', 'PSI') . '-' . date('Y') . '-';
        $last = Database::value(
            'SELECT file_number FROM patients WHERE file_number LIKE ? ORDER BY file_number DESC LIMIT 1',
            [$prefix . '%']
        );
        $n = $last ? (int) substr((string) $last, -4) + 1 : 1;
        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /** Búsqueda por nombre, apellido, identificación o expediente. */
    public static function search(string $term, int $limit = 10): array
    {
        $like = '%' . $term . '%';
        return Database::all(
            "SELECT id, file_number, first_name, last_name, birth_date, identification, status
               FROM patients
              WHERE first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?
                 OR identification LIKE ? OR file_number LIKE ?
              ORDER BY (status = 'activo') DESC, last_name, first_name LIMIT " . (int) $limit,
            [$like, $like, $like, $like, $like]
        );
    }

    /** Datos que acompañan al encabezado del expediente. */
    public static function header(int $id): array
    {
        return [
            'last_session' => Database::value("SELECT MAX(session_date) FROM therapy_sessions WHERE patient_id = ? AND attendance = 'atendido'", [$id]),
            'next_appointment' => Database::one(
                "SELECT * FROM appointments WHERE patient_id = ? AND starts_at >= NOW() AND status IN ('programada','confirmada') ORDER BY starts_at LIMIT 1", [$id]
            ),
            'package' => Database::one("SELECT * FROM session_packages WHERE patient_id = ? AND status = 'activo' ORDER BY purchased_at, id LIMIT 1", [$id]),
            'balance' => (float) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE patient_id = ? AND status = 'pendiente'", [$id]),
        ];
    }

    public static function guardians(int $id): array
    {
        return Database::all('SELECT * FROM patient_guardians WHERE patient_id = ? ORDER BY is_primary DESC, id', [$id]);
    }

    public static function activeDiagnoses(int $id): array
    {
        return Database::all("SELECT * FROM diagnoses WHERE patient_id = ? AND status = 'activo' ORDER BY diagnosed_at DESC, id DESC", [$id]);
    }

    public static function activePlan(int $id): ?array
    {
        return Database::one("SELECT * FROM treatment_plans WHERE patient_id = ? AND status = 'activo' ORDER BY start_date DESC LIMIT 1", [$id]);
    }

    /** Objetivos del plan agrupados por área, con porcentaje de cumplimiento administrativo. */
    public static function planProgress(int $planId): array
    {
        $areas = Database::all('SELECT * FROM treatment_areas WHERE plan_id = ? ORDER BY sort_order, id', [$planId]);
        $objectives = Database::all('SELECT * FROM treatment_objectives WHERE plan_id = ? ORDER BY id', [$planId]);
        $byArea = [];
        foreach ($objectives as $o) {
            $byArea[(int) $o['area_id']][] = $o;
        }
        $total = count($objectives);
        $done = count(array_filter($objectives, fn($o) => $o['status'] === 'logrado'));
        $inProgress = count(array_filter($objectives, fn($o) => $o['status'] === 'en_proceso'));
        return [
            'areas' => $areas,
            'by_area' => $byArea,
            'objectives' => $objectives,
            'total' => $total,
            'achieved' => $done,
            'in_progress' => $inProgress,
            'percent' => $total ? (int) round($done * 100 / $total) : 0,
        ];
    }
}
