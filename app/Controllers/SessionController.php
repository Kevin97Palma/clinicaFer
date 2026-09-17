<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AuditService;
use App\Services\PackageService;
use App\Services\TherapySessionService;

final class SessionController extends Controller
{
    private const FIELDS = ['session_date', 'session_time', 'duration_min', 'modality', 'objective_worked', 'emotional_state',
        'intervention', 'patient_response', 'participation', 'progress', 'clinical_notes', 'homework',
        'recommendations', 'next_objective', 'attendance'];

    private const RULES = [
        'session_date' => 'required|date', 'session_time' => 'time', 'duration_min' => 'required|int|min:5|maxnum:480',
        'modality' => 'required|in_catalog:modality', 'attendance' => 'required|in_catalog:attendance',
        'emotional_state' => 'in_catalog:emotional_state', 'participation' => 'in_catalog:participation', 'progress' => 'in_catalog:progress',
    ];

    public function index(): void
    {
        $from = (string) $this->input('desde', date('Y-m-d', strtotime('-60 days')));
        $to = (string) $this->input('hasta', date('Y-m-d'));
        $attendance = (string) $this->input('asistencia', '');
        $patientId = (int) $this->input('patient_id', 0);

        $where = ['s.session_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if (array_key_exists($attendance, options('attendance'))) {
            $where[] = 's.attendance = ?';
            $params[] = $attendance;
        }
        if ($patientId) {
            $where[] = 's.patient_id = ?';
            $params[] = $patientId;
        }
        $sql = implode(' AND ', $where);

        $this->view('sessions/index', [
            'title' => 'Sesiones',
            'sessions' => Database::all(
                "SELECT s.*, p.first_name, p.last_name, p.file_number, u.name AS professional_name
                   FROM therapy_sessions s JOIN patients p ON p.id = s.patient_id LEFT JOIN users u ON u.id = s.professional_id
                  WHERE $sql ORDER BY s.session_date DESC, s.session_time DESC LIMIT 300", $params
            ),
            'from' => $from, 'to' => $to, 'attendance' => $attendance,
            'summary' => Database::all("SELECT attendance, COUNT(*) AS n FROM therapy_sessions s WHERE $sql GROUP BY attendance", $params),
        ]);
    }

    public function create(): void
    {
        $appointmentId = (int) $this->input('appointment_id', 0);
        $appointment = null;
        $patientId = (int) $this->input('patient_id', 0);

        if ($appointmentId) {
            $appointment = Database::one('SELECT * FROM appointments WHERE id = ?', [$appointmentId]) ?: $this->notFound();
            if (Database::value('SELECT id FROM therapy_sessions WHERE appointment_id = ?', [$appointmentId])) {
                flash('info', 'Esa cita ya tiene una sesión registrada.');
                redirect('/sesiones/' . Database::value('SELECT id FROM therapy_sessions WHERE appointment_id = ?', [$appointmentId]));
            }
            $patientId = (int) $appointment['patient_id'];
        }

        $patient = $patientId ? Patient::find($patientId) : null;
        $this->view('sessions/form', $this->formData($patient, null, $appointment) + ['title' => 'Nueva sesión']);
    }

    public function store(): void
    {
        $this->validate($_POST, self::RULES + ['patient_id' => 'required|int']);
        $patientId = (int) $_POST['patient_id'];
        $patient = Patient::find($patientId) ?: $this->notFound();

        $data = $this->only(self::FIELDS);
        $data['patient_id'] = $patientId;
        $data['professional_id'] = (int) ($_POST['professional_id'] ?? Auth::id());
        $data['appointment_id'] = !empty($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : null;
        $data['duration_min'] = (int) $data['duration_min'];

        $service = new TherapySessionService();
        $sessionId = $service->create($data, $this->objectivesInput(), [
            'next_starts_at' => !empty($_POST['next_starts_at']) ? str_replace('T', ' ', (string) $_POST['next_starts_at']) : null,
            'next_duration' => (int) ($_POST['next_duration'] ?? $data['duration_min']),
            'charge_pending' => !empty($_POST['charge_pending']),
        ]);

        flash('success', 'Sesión registrada correctamente.');
        foreach ($service->messages as [$type, $message]) {
            flash($type, $message);
        }
        if (empty($_POST['next_starts_at']) && $data['attendance'] === 'atendido') {
            flash('info', '¿Desea programar la próxima sesión? Puede hacerlo desde el expediente o la agenda.');
        }
        redirect('/pacientes/' . $patientId . '?tab=sesiones');
    }

    public function show(int $id): void
    {
        $session = Database::one(
            'SELECT s.*, p.first_name, p.last_name, p.file_number, p.birth_date, u.name AS professional_name, uc.name AS created_by_name
               FROM therapy_sessions s JOIN patients p ON p.id = s.patient_id
               LEFT JOIN users u ON u.id = s.professional_id LEFT JOIN users uc ON uc.id = s.created_by WHERE s.id = ?',
            [$id]
        ) ?: $this->notFound();
        AuditService::clinicalAccess((int) $session['patient_id'], 'sesion');

        $this->view('sessions/show', [
            'title' => 'Sesión · ' . $session['first_name'] . ' ' . $session['last_name'],
            'session' => $session,
            'objectives' => Database::all(
                'SELECT o.*, a.name AS area_name, tso.status_after FROM therapy_session_objectives tso
                   JOIN treatment_objectives o ON o.id = tso.objective_id JOIN treatment_areas a ON a.id = o.area_id
                  WHERE tso.session_id = ?', [$id]
            ),
            'package' => $session['package_id'] ? Database::one('SELECT * FROM session_packages WHERE id = ?', [$session['package_id']]) : null,
            'payment' => Database::one('SELECT * FROM payments WHERE session_id = ?', [$id]),
        ]);
    }

    public function edit(int $id): void
    {
        $session = Database::one('SELECT * FROM therapy_sessions WHERE id = ?', [$id]) ?: $this->notFound();
        $patient = Patient::find((int) $session['patient_id']);
        $this->view('sessions/form', $this->formData($patient, $session, null) + ['title' => 'Editar sesión']);
    }

    public function update(int $id): void
    {
        $session = Database::one('SELECT * FROM therapy_sessions WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, self::RULES);
        $data = $this->only(self::FIELDS);
        $data['duration_min'] = (int) $data['duration_min'];
        $data['professional_id'] = (int) ($_POST['professional_id'] ?? $session['professional_id']);

        (new TherapySessionService())->update($id, $data, $this->objectivesInput());
        flash('success', 'Sesión actualizada.');
        redirect('/sesiones/' . $id);
    }

    /** [objetivo_id => estado] de los objetivos marcados como trabajados. */
    private function objectivesInput(): array
    {
        $out = [];
        foreach ((array) ($_POST['objectives'] ?? []) as $objectiveId) {
            $objectiveId = (int) $objectiveId;
            $out[$objectiveId] = (string) ($_POST['objective_status'][$objectiveId] ?? '');
        }
        return $out;
    }

    private function formData(?array $patient, ?array $session, ?array $appointment): array
    {
        $plan = $patient ? Patient::activePlan((int) $patient['id']) : null;
        $selected = $session ? array_column(Database::all(
            'SELECT objective_id, status_after FROM therapy_session_objectives WHERE session_id = ?', [$session['id']]
        ), 'status_after', 'objective_id') : [];

        return [
            'patient' => $patient,
            'session' => $session,
            'appointment' => $appointment,
            'plan' => $plan,
            'progress' => $plan ? Patient::planProgress((int) $plan['id']) : null,
            'selected_objectives' => $selected,
            'package' => $patient ? PackageService::activeFor((int) $patient['id']) : null,
            'next_number' => $patient ? ($session['session_number'] ?? TherapySessionService::nextNumber((int) $patient['id'])) : null,
            'professionals' => Database::all("SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                                               WHERE u.is_active = 1 AND r.slug IN ('administrador','psicologo') ORDER BY u.name"),
            'session_fee' => (float) setting('session_fee', 0),
        ];
    }
}
