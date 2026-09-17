<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AppointmentService;
use App\Services\AuditService;
use RuntimeException;

final class AgendaController extends Controller
{
    public function index(): void
    {
        $this->view('agenda/index', [
            'title' => 'Agenda',
            'professionals' => $this->professionals(),
            'default_duration' => (int) setting('session_duration', 45),
            'open_new' => (bool) $this->input('nueva', false),
            'today' => Database::all(
                "SELECT a.*, p.first_name, p.last_name FROM appointments a JOIN patients p ON p.id = a.patient_id
                  WHERE DATE(a.starts_at) = CURDATE() ORDER BY a.starts_at"
            ),
        ]);
    }

    /** Eventos del calendario en JSON (FullCalendar). */
    public function events(): void
    {
        $start = (string) $this->input('start', date('Y-m-01'));
        $end = (string) $this->input('end', date('Y-m-t'));
        $professional = (int) $this->input('professional_id', 0);

        $params = [substr($start, 0, 19), substr($end, 0, 19)];
        $sql = "SELECT a.*, p.first_name, p.last_name, p.file_number, s.id AS session_id
                  FROM appointments a JOIN patients p ON p.id = a.patient_id
                  LEFT JOIN therapy_sessions s ON s.appointment_id = a.id
                 WHERE a.starts_at >= ? AND a.starts_at < ?";
        if ($professional) {
            $sql .= ' AND a.professional_id = ?';
            $params[] = $professional;
        }

        $colors = ['programada' => '#3b6c93', 'confirmada' => '#2f6f66', 'atendida' => '#3d7a4a',
            'cancelada' => '#8a8f8d', 'no_asistio' => '#a8443c', 'reprogramada' => '#9a6a12'];

        $events = array_map(fn($a) => [
            'id' => (int) $a['id'],
            'title' => $a['first_name'] . ' ' . $a['last_name'],
            'start' => str_replace(' ', 'T', $a['starts_at']),
            'end' => str_replace(' ', 'T', $a['ends_at']),
            'backgroundColor' => $colors[$a['status']] ?? '#3b6c93',
            'extendedProps' => [
                'patient_id' => (int) $a['patient_id'],
                'file_number' => $a['file_number'],
                'status' => $a['status'],
                'status_label' => label('appointment_status', $a['status']),
                'type' => $a['type'],
                'type_label' => label('appointment_type', $a['type']),
                'reason' => $a['reason'],
                'notes' => $a['notes'],
                'duration' => (int) $a['duration_min'],
                'professional_id' => (int) $a['professional_id'],
                'session_id' => $a['session_id'] ? (int) $a['session_id'] : null,
                'starts_at' => str_replace(' ', 'T', substr($a['starts_at'], 0, 16)),
            ],
        ], Database::all($sql, $params));

        $this->json(['success' => true, 'data' => $events]);
    }

    public function show(int $id): void
    {
        $a = Database::one(
            'SELECT a.*, p.first_name, p.last_name FROM appointments a JOIN patients p ON p.id = a.patient_id WHERE a.id = ?', [$id]
        ) ?: $this->notFound();
        $this->json(['success' => true, 'data' => $a]);
    }

    public function store(): void
    {
        $this->validate($_POST, [
            'patient_id' => 'required|int', 'professional_id' => 'required|int', 'starts_at' => 'required',
            'duration_min' => 'required|int|min:5|maxnum:480', 'type' => 'in_catalog:appointment_type', 'reason' => 'max:255',
        ]);
        try {
            $id = AppointmentService::create([
                'patient_id' => (int) $_POST['patient_id'],
                'professional_id' => (int) $_POST['professional_id'],
                'starts_at' => str_replace('T', ' ', (string) $_POST['starts_at']),
                'duration_min' => (int) $_POST['duration_min'],
                'type' => $_POST['type'] ?? 'sesion',
                'reason' => trim((string) ($_POST['reason'] ?? '')) ?: null,
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
            ]);
        } catch (RuntimeException $e) {
            if (is_ajax()) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            flash('danger', $e->getMessage());
            redirect_back('/agenda');
        }
        if (is_ajax()) {
            $this->json(['success' => true, 'message' => 'Cita programada.', 'id' => $id]);
        }
        flash('success', 'Cita programada correctamente.');
        redirect_back('/agenda');
    }

    public function update(int $id): void
    {
        Database::one('SELECT id FROM appointments WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['professional_id' => 'required|int', 'starts_at' => 'required', 'duration_min' => 'required|int|min:5|maxnum:480']);
        try {
            AppointmentService::update($id, [
                'professional_id' => (int) $_POST['professional_id'],
                'starts_at' => str_replace('T', ' ', (string) $_POST['starts_at']),
                'duration_min' => (int) $_POST['duration_min'],
                'type' => $_POST['type'] ?? 'sesion',
                'reason' => trim((string) ($_POST['reason'] ?? '')) ?: null,
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
            ]);
        } catch (RuntimeException $e) {
            if (is_ajax()) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            flash('danger', $e->getMessage());
            redirect_back('/agenda');
        }
        if (is_ajax()) {
            $this->json(['success' => true, 'message' => 'Cita actualizada.']);
        }
        flash('success', 'Cita actualizada.');
        redirect_back('/agenda');
    }

    public function status(int $id): void
    {
        $a = Database::one('SELECT * FROM appointments WHERE id = ?', [$id]) ?: $this->notFound();
        $status = (string) $this->input('status', '');
        if (!array_key_exists($status, options('appointment_status'))) {
            $this->json(['success' => false, 'message' => 'Estado no válido.'], 422);
        }
        Database::query('UPDATE appointments SET status = ?, updated_by = ? WHERE id = ?', [$status, Auth::id(), $id]);
        AuditService::log('CAMBIAR_ESTADO', 'agenda', $id, 'Cita: ' . $a['status'] . ' → ' . $status);

        $message = 'Cita marcada como ' . mb_strtolower(label('appointment_status', $status)) . '.';
        if ($status === 'atendida' && can('clinical.manage')) {
            $exists = Database::value('SELECT id FROM therapy_sessions WHERE appointment_id = ?', [$id]);
            if (!$exists) {
                flash('success', $message);
                flash('info', 'Registre la sesión para actualizar el expediente.');
                if (is_ajax()) {
                    $this->json(['success' => true, 'redirect' => url('sesiones/nueva', ['appointment_id' => $id])]);
                }
                redirect('/sesiones/nueva?appointment_id=' . $id);
            }
        }
        if (is_ajax()) {
            $this->json(['success' => true, 'message' => $message]);
        }
        flash('success', $message);
        redirect_back('/agenda');
    }

    private function professionals(): array
    {
        return Database::all("SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                               WHERE u.is_active = 1 AND r.slug IN ('administrador','psicologo') ORDER BY u.name");
    }
}
