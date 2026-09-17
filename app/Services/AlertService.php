<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Reglas automáticas de alertas.
 * Cada regla calcula el conjunto de condiciones vigentes: crea las alertas nuevas
 * (sin duplicar las abiertas) y resuelve automáticamente las que ya no aplican.
 */
final class AlertService
{
    private const OPEN = "('pendiente','leida')";

    /** Crea una alerta si no existe otra abierta con el mismo tipo y referencia. */
    public static function raise(string $type, string $message, ?int $patientId, string $refType, int $refId, ?string $dueDate = null): bool
    {
        $exists = Database::value(
            "SELECT id FROM alerts WHERE type = ? AND reference_type = ? AND reference_id = ? AND status IN " . self::OPEN . " LIMIT 1",
            [$type, $refType, $refId]
        );
        if ($exists) {
            Database::query('UPDATE alerts SET message = ? WHERE id = ?', [$message, $exists]);
            return false;
        }
        // Tampoco repetir una alerta descartada manualmente para la misma condición en los últimos 30 días
        $dismissed = Database::value(
            "SELECT id FROM alerts WHERE type = ? AND reference_type = ? AND reference_id = ? AND status = 'descartada'
               AND message = ? AND created_at >= NOW() - INTERVAL 30 DAY LIMIT 1",
            [$type, $refType, $refId, $message]
        );
        if ($dismissed) {
            return false;
        }
        Database::insert('alerts', [
            'patient_id' => $patientId, 'type' => $type, 'message' => $message,
            'reference_type' => $refType, 'reference_id' => $refId, 'due_date' => $dueDate,
        ]);
        return true;
    }

    public static function resolve(string $type, string $refType, int $refId): void
    {
        Database::query(
            "UPDATE alerts SET status = 'resuelta', resolved_at = NOW()
              WHERE type = ? AND reference_type = ? AND reference_id = ? AND status IN " . self::OPEN,
            [$type, $refType, $refId]
        );
    }

    /** Ejecuta las reglas como máximo cada $minutes minutos (o siempre con $force). */
    public static function runIfDue(int $minutes = 10, bool $force = false): void
    {
        $last = SettingsService::get('alerts_last_run', '');
        if (!$force && $last && strtotime($last) > time() - $minutes * 60) {
            return;
        }
        try {
            self::runAll();
            SettingsService::set('alerts_last_run', date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
            error_log('[SGC-ALERTS] ' . $e->getMessage());
        }
    }

    public static function runAll(): void
    {
        self::inactivity();
        self::packages();
        self::evaluations();
        self::reports();
        self::therapeuticReview();
        self::pendingPayments();
    }

    /** Sincroniza: crea las vigentes y resuelve las que ya no cumplen la condición. */
    private static function sync(string $type, string $refType, array $current): void
    {
        foreach ($current as $refId => $a) {
            self::raise($type, $a['message'], $a['patient_id'], $refType, (int) $refId, $a['due'] ?? null);
        }
        $open = Database::all(
            "SELECT id, reference_id FROM alerts WHERE type = ? AND reference_type = ? AND status IN " . self::OPEN,
            [$type, $refType]
        );
        foreach ($open as $row) {
            if (!isset($current[(int) $row['reference_id']])) {
                Database::query("UPDATE alerts SET status = 'resuelta', resolved_at = NOW() WHERE id = ?", [$row['id']]);
            }
        }
    }

    private static function inactivity(): void
    {
        $days = SettingsService::int('inactivity_days', 30);
        $rows = Database::all(
            "SELECT p.id, p.first_name, p.last_name, COALESCE(p.last_session_at, p.intake_date) AS ref_date
               FROM patients p
              WHERE p.status = 'activo' AND COALESCE(p.last_session_at, p.intake_date) < CURDATE() - INTERVAL ? DAY
                AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.patient_id = p.id AND a.starts_at >= NOW()
                                AND a.status IN ('programada','confirmada'))",
            [$days]
        );
        $current = [];
        foreach ($rows as $r) {
            $n = (int) (new \DateTime($r['ref_date']))->diff(new \DateTime('today'))->days;
            $current[$r['id']] = ['patient_id' => (int) $r['id'], 'message' => "{$r['first_name']} {$r['last_name']}: paciente sin asistir durante {$n} días."];
        }
        self::sync('INACTIVIDAD_PACIENTE', 'patient', $current);
    }

    private static function packages(): void
    {
        $rows = Database::all(
            "SELECT sp.id, sp.patient_id, sp.sessions_remaining, p.first_name, p.last_name
               FROM session_packages sp JOIN patients p ON p.id = sp.patient_id
              WHERE sp.status = 'activo' AND sp.sessions_remaining <= 1"
        );
        $current = [];
        foreach ($rows as $r) {
            $msg = (int) $r['sessions_remaining'] === 1
                ? "{$r['first_name']} {$r['last_name']}: queda 1 sesión del paquete."
                : "{$r['first_name']} {$r['last_name']}: el paquete no tiene sesiones disponibles.";
            $current[$r['id']] = ['patient_id' => (int) $r['patient_id'], 'message' => $msg];
        }
        self::sync('PAQUETE_POR_FINALIZAR', 'package', $current);
    }

    private static function evaluations(): void
    {
        $rows = Database::all(
            "SELECT e.id, e.patient_id, e.start_date, p.first_name, p.last_name
               FROM psychological_evaluations e JOIN patients p ON p.id = e.patient_id
              WHERE e.status = 'en_proceso' AND e.start_date <= CURDATE() - INTERVAL 21 DAY"
        );
        $current = [];
        foreach ($rows as $r) {
            $current[$r['id']] = ['patient_id' => (int) $r['patient_id'], 'message' => "{$r['first_name']} {$r['last_name']}: evaluación pendiente de finalizar (iniciada el " . fdate($r['start_date']) . ")."];
        }
        self::sync('EVALUACION_PENDIENTE', 'evaluation', $current);
    }

    private static function reports(): void
    {
        $current = [];
        foreach (Database::all(
            "SELECT e.id, e.patient_id, p.first_name, p.last_name FROM psychological_evaluations e
               JOIN patients p ON p.id = e.patient_id WHERE e.status IN ('finalizada','informe_pendiente')"
        ) as $r) {
            $current[$r['id']] = ['patient_id' => (int) $r['patient_id'], 'message' => "{$r['first_name']} {$r['last_name']}: informe de evaluación pendiente."];
        }
        self::sync('INFORME_PENDIENTE', 'evaluation', $current);

        $docs = [];
        foreach (Database::all(
            "SELECT d.id, d.patient_id, d.title, p.first_name, p.last_name FROM documents d
               JOIN patients p ON p.id = d.patient_id
              WHERE d.status = 'borrador' AND d.created_at <= NOW() - INTERVAL 3 DAY"
        ) as $r) {
            $docs[$r['id']] = ['patient_id' => (int) $r['patient_id'], 'message' => "{$r['first_name']} {$r['last_name']}: informe pendiente — «{$r['title']}» sigue en borrador."];
        }
        self::sync('INFORME_PENDIENTE', 'document', $docs);
    }

    private static function therapeuticReview(): void
    {
        $every = max(1, SettingsService::int('review_every_sessions', 8));
        $current = [];
        // Sesiones atendidas desde la última revisión de progreso
        foreach (Database::all(
            "SELECT p.id, p.first_name, p.last_name,
                    (SELECT COUNT(*) FROM therapy_sessions s WHERE s.patient_id = p.id AND s.attendance = 'atendido'
                       AND s.session_date > COALESCE((SELECT MAX(r.review_date) FROM progress_reviews r WHERE r.patient_id = p.id), '1900-01-01')) AS since_review
               FROM patients p WHERE p.status = 'activo'"
        ) as $r) {
            if ((int) $r['since_review'] >= $every) {
                $current[$r['id']] = ['patient_id' => (int) $r['id'],
                    'message' => "{$r['first_name']} {$r['last_name']}: completó {$r['since_review']} sesiones. Considerar evaluación de progreso."];
            }
        }
        self::sync('REVISION_TERAPEUTICA', 'patient', $current);

        $plans = [];
        foreach (Database::all(
            "SELECT tp.id, tp.patient_id, tp.review_date, p.first_name, p.last_name FROM treatment_plans tp
               JOIN patients p ON p.id = tp.patient_id
              WHERE tp.status = 'activo' AND tp.review_date IS NOT NULL AND tp.review_date <= CURDATE()"
        ) as $r) {
            $plans[$r['id']] = ['patient_id' => (int) $r['patient_id'], 'due' => $r['review_date'],
                'message' => "{$r['first_name']} {$r['last_name']}: plan terapéutico pendiente de revisión."];
        }
        self::sync('REVISION_TERAPEUTICA', 'plan', $plans);
    }

    private static function pendingPayments(): void
    {
        $current = [];
        foreach (Database::all(
            "SELECT pay.id, pay.patient_id, pay.amount, pay.concept, p.first_name, p.last_name FROM payments pay
               JOIN patients p ON p.id = pay.patient_id
              WHERE pay.status = 'pendiente' AND pay.paid_at <= CURDATE() - INTERVAL 7 DAY"
        ) as $r) {
            $current[$r['id']] = ['patient_id' => (int) $r['patient_id'],
                'message' => "{$r['first_name']} {$r['last_name']}: pago pendiente de " . money($r['amount']) . " ({$r['concept']})."];
        }
        self::sync('PAGO_PENDIENTE', 'payment', $current);
    }

    /** Alertas abiertas visibles, filtrando por permisos del usuario. */
    public static function openForUser(int $limit = 20, ?int $patientId = null): array
    {
        $types = [];
        if (can('patients.view')) {
            $types[] = 'INACTIVIDAD_PACIENTE';
            $types[] = 'OTRA';
        }
        if (can('payments.view')) {
            $types[] = 'PAQUETE_POR_FINALIZAR';
            $types[] = 'PAGO_PENDIENTE';
        }
        if (can('evaluations.view')) {
            $types[] = 'EVALUACION_PENDIENTE';
        }
        if (can('documents.view') || can('evaluations.view')) {
            $types[] = 'INFORME_PENDIENTE';
        }
        if (can('clinical.view')) {
            $types[] = 'REVISION_TERAPEUTICA';
        }
        if (!$types) {
            return [];
        }
        $in = implode(',', array_fill(0, count($types), '?'));
        $params = $types;
        $sql = "SELECT a.*, p.first_name, p.last_name FROM alerts a LEFT JOIN patients p ON p.id = a.patient_id
                 WHERE a.status IN " . self::OPEN . " AND a.type IN ($in)";
        if ($patientId) {
            $sql .= ' AND a.patient_id = ?';
            $params[] = $patientId;
        }
        $sql .= " ORDER BY a.status = 'pendiente' DESC, a.created_at DESC LIMIT " . (int) $limit;
        return Database::all($sql, $params);
    }
}
