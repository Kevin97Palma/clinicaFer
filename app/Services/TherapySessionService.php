<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use RuntimeException;

/**
 * Registro de sesiones: el corazón de la automatización.
 * BEGIN → guardar sesión → objetivos → paquete + movimiento → cita → paciente → próxima cita → alertas → COMMIT
 */
final class TherapySessionService
{
    /** Mensajes informativos para la profesional tras guardar. */
    public array $messages = [];

    private function consumes(string $attendance): bool
    {
        return $attendance === 'atendido'
            || ($attendance === 'no_asistio' && SettingsService::get('consume_package_on_no_show', '0') === '1')
            || (in_array($attendance, ['cancelado', 'reprogramado'], true) && SettingsService::get('consume_package_on_cancel', '0') === '1');
    }

    /**
     * @param array $data       campos de therapy_sessions
     * @param array $objectives [objective_id => nuevo estado|'' ]
     * @param array $options    next_starts_at, next_duration, charge_pending(bool)
     */
    public function create(array $data, array $objectives, array $options = []): int
    {
        return Database::transaction(function () use ($data, $objectives, $options) {
            $patientId = (int) $data['patient_id'];
            $patient = Database::one('SELECT * FROM patients WHERE id = ? FOR UPDATE', [$patientId]);
            if (!$patient) {
                throw new RuntimeException('Paciente no encontrado.');
            }

            $attended = $data['attendance'] === 'atendido';
            $data['session_number'] = $attended ? (int) $patient['sessions_count'] + 1 : null;
            $data['plan_id'] = $data['plan_id'] ?? Database::value(
                "SELECT id FROM treatment_plans WHERE patient_id = ? AND status = 'activo' ORDER BY start_date DESC LIMIT 1", [$patientId]
            );
            $data['created_by'] = Auth::id();

            // Valor de la sesión: del paquete si corresponde, o el valor configurado
            $package = $this->consumes($data['attendance']) ? PackageService::activeFor($patientId) : null;
            if ($package) {
                $data['package_id'] = (int) $package['id'];
                $data['fee'] = PackageService::unitValues($package)[1];
            } elseif (!isset($data['fee'])) {
                $data['fee'] = $attended ? (float) SettingsService::get('session_fee', '0') : 0;
            }

            $sessionId = Database::insert('therapy_sessions', $data);

            $this->syncObjectives($sessionId, $objectives);

            if ($package) {
                $pkg = PackageService::consume((int) $package['id'], $sessionId);
                PackageService::syncAlert($pkg);
                $remaining = (int) $pkg['sessions_remaining'];
                if ($remaining === 1) {
                    $this->messages[] = ['warning', 'Queda 1 sesión disponible en el paquete.'];
                } elseif ($remaining === 0) {
                    $this->messages[] = ['warning', 'Se utilizó la última sesión del paquete.'];
                }
            }

            // Cobro pendiente si no hay paquete
            if ($attended && !$package && !empty($options['charge_pending']) && (float) $data['fee'] > 0) {
                Database::insert('payments', [
                    'patient_id' => $patientId, 'session_id' => $sessionId, 'paid_at' => $data['session_date'],
                    'concept' => 'Sesión N.º ' . $data['session_number'], 'amount' => $data['fee'],
                    'method' => 'efectivo', 'status' => 'pendiente', 'created_by' => Auth::id(),
                ]);
                $this->messages[] = ['info', 'Se registró un cobro pendiente de ' . money($data['fee']) . '.'];
            }

            if (!empty($data['appointment_id'])) {
                Database::query('UPDATE appointments SET status = ?, updated_by = ? WHERE id = ?',
                    [$this->appointmentStatus($data['attendance']), Auth::id(), $data['appointment_id']]);
            }

            if ($attended) {
                Database::query(
                    'UPDATE patients SET sessions_count = sessions_count + 1,
                            last_session_at = GREATEST(COALESCE(last_session_at, ?), ?) WHERE id = ?',
                    [$data['session_date'], $data['session_date'], $patientId]
                );
                AlertService::resolve('INACTIVIDAD_PACIENTE', 'patient', $patientId);
                $this->checkReview($patient);
            }

            if (!empty($options['next_starts_at'])) {
                try {
                    AppointmentService::create([
                        'patient_id' => $patientId,
                        'professional_id' => $data['professional_id'],
                        'starts_at' => $options['next_starts_at'],
                        'duration_min' => (int) ($options['next_duration'] ?? $data['duration_min']),
                        'type' => 'sesion',
                        'reason' => $data['next_objective'] ? mb_substr($data['next_objective'], 0, 255) : null,
                    ]);
                    $this->messages[] = ['success', 'Próxima sesión programada para el ' . fdate($options['next_starts_at'], true) . '.'];
                } catch (RuntimeException $e) {
                    // La sesión se guarda igual; solo se informa el cruce de horario
                    $this->messages[] = ['warning', 'No se programó la próxima sesión: ' . $e->getMessage()];
                }
            }

            AuditService::log('CREAR', 'sesiones', $sessionId, 'Sesión registrada (' . $data['attendance'] . ')');
            return $sessionId;
        });
    }

    public function update(int $sessionId, array $data, array $objectives): void
    {
        Database::transaction(function () use ($sessionId, $data, $objectives) {
            $old = Database::one('SELECT * FROM therapy_sessions WHERE id = ? FOR UPDATE', [$sessionId]);
            $patientId = (int) $old['patient_id'];
            $data['updated_by'] = Auth::id();

            $wasConsumed = $this->consumes($old['attendance']) && $old['package_id'];
            $nowConsumes = $this->consumes($data['attendance']);

            // Corrección de asistencia: revertir o aplicar el descuento de paquete
            if ($wasConsumed && !$nowConsumes) {
                $pkg = PackageService::revert((int) $old['package_id'], $sessionId, 'Corrección de asistencia');
                PackageService::syncAlert($pkg);
                $data['package_id'] = null;
            } elseif (!$old['package_id'] && $nowConsumes && ($package = PackageService::activeFor($patientId))) {
                $pkg = PackageService::consume((int) $package['id'], $sessionId);
                PackageService::syncAlert($pkg);
                $data['package_id'] = (int) $package['id'];
            }

            $wasAttended = $old['attendance'] === 'atendido';
            $isAttended = $data['attendance'] === 'atendido';
            if ($wasAttended !== $isAttended) {
                Database::query('UPDATE patients SET sessions_count = GREATEST(CAST(sessions_count AS SIGNED) + ?, 0) WHERE id = ?',
                    [$isAttended ? 1 : -1, $patientId]);
                if ($isAttended && !$old['session_number']) {
                    $data['session_number'] = (int) Database::value('SELECT sessions_count FROM patients WHERE id = ?', [$patientId]);
                }
            }

            Database::update('therapy_sessions', $data, $sessionId);
            Database::query(
                "UPDATE patients SET last_session_at = (SELECT MAX(session_date) FROM therapy_sessions WHERE patient_id = ? AND attendance = 'atendido') WHERE id = ?",
                [$patientId, $patientId]
            );
            if ($old['appointment_id']) {
                Database::query('UPDATE appointments SET status = ? WHERE id = ?', [$this->appointmentStatus($data['attendance']), $old['appointment_id']]);
            }

            $this->syncObjectives($sessionId, $objectives);
            AuditService::log('EDITAR', 'sesiones', $sessionId, 'Sesión editada', AuditService::diff($old, $data));
        });
    }

    /** Relaciona objetivos trabajados y actualiza su estado en el plan. */
    private function syncObjectives(int $sessionId, array $objectives): void
    {
        Database::query('DELETE FROM therapy_session_objectives WHERE session_id = ?', [$sessionId]);
        foreach ($objectives as $objectiveId => $status) {
            $objectiveId = (int) $objectiveId;
            $status = in_array($status, ['pendiente', 'en_proceso', 'logrado'], true) ? $status : null;
            Database::insert('therapy_session_objectives', [
                'session_id' => $sessionId, 'objective_id' => $objectiveId, 'status_after' => $status,
            ]);
            if ($status) {
                Database::query(
                    "UPDATE treatment_objectives SET status = ?, achieved_at = IF(? = 'logrado', COALESCE(achieved_at, CURDATE()), NULL) WHERE id = ?",
                    [$status, $status, $objectiveId]
                );
            }
        }
    }

    private function appointmentStatus(string $attendance): string
    {
        return ['atendido' => 'atendida', 'cancelado' => 'cancelada', 'no_asistio' => 'no_asistio', 'reprogramado' => 'reprogramada'][$attendance];
    }

    private function checkReview(array $patient): void
    {
        $every = max(1, SettingsService::int('review_every_sessions', 8));
        $since = (int) Database::value(
            "SELECT COUNT(*) FROM therapy_sessions WHERE patient_id = ? AND attendance = 'atendido'
               AND session_date > COALESCE((SELECT MAX(review_date) FROM progress_reviews WHERE patient_id = ?), '1900-01-01')",
            [$patient['id'], $patient['id']]
        );
        if ($since >= $every) {
            AlertService::raise('REVISION_TERAPEUTICA',
                "{$patient['first_name']} {$patient['last_name']}: completó {$since} sesiones. Considerar evaluación de progreso.",
                (int) $patient['id'], 'patient', (int) $patient['id']);
            $this->messages[] = ['info', 'El paciente alcanzó el número configurado de sesiones para revisión de progreso.'];
        }
    }

    public static function nextNumber(int $patientId): int
    {
        return (int) Database::value('SELECT sessions_count FROM patients WHERE id = ?', [$patientId]) + 1;
    }
}
