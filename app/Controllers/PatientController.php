<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AlertService;
use App\Services\AuditService;
use App\Services\FileStorage;

final class PatientController extends Controller
{
    private const RULES = [
        'first_name' => 'required|max:100', 'last_name' => 'required|max:100',
        'birth_date' => 'required|date|before_or_today', 'sex' => 'required|in_catalog:sex',
        'identification' => 'max:30', 'phone' => 'max:30', 'email' => 'email|max:150',
        'address' => 'max:255', 'school' => 'max:150', 'grade' => 'max:60',
        'intake_date' => 'required|date', 'status' => 'required|in_catalog:patient_status',
    ];

    private const FIELDS = ['first_name', 'last_name', 'birth_date', 'sex', 'identification', 'phone', 'email',
        'address', 'school', 'grade', 'intake_date', 'status', 'consultation_reason', 'general_notes', 'professional_id'];

    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $status = (string) $this->input('estado', '');
        $page = max(1, (int) $this->input('pagina', 1));
        $perPage = 20;

        $where = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $where[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name,' ',p.last_name) LIKE ? OR p.identification LIKE ? OR p.file_number LIKE ?)";
            array_push($params, ...array_fill(0, 5, "%$q%"));
        }
        if (array_key_exists($status, options('patient_status'))) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        $sql = implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) FROM patients p WHERE $sql", $params);
        $patients = Database::all(
            "SELECT p.*, u.name AS professional_name,
                    (SELECT MIN(a.starts_at) FROM appointments a WHERE a.patient_id = p.id AND a.starts_at >= NOW() AND a.status IN ('programada','confirmada')) AS next_appointment
               FROM patients p LEFT JOIN users u ON u.id = p.professional_id
              WHERE $sql ORDER BY p.last_name, p.first_name LIMIT " . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $params
        );

        $this->view('patients/index', [
            'title' => 'Pacientes', 'patients' => $patients, 'q' => $q, 'status' => $status,
            'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $perPage),
            'counts' => Database::all('SELECT status, COUNT(*) AS n FROM patients GROUP BY status'),
        ]);
    }

    public function search(): void
    {
        $q = trim((string) $this->input('q', ''));
        if (mb_strlen($q) < 2) {
            $this->json(['success' => true, 'data' => []]);
        }
        $data = array_map(fn($p) => [
            'id' => (int) $p['id'], 'name' => Patient::fullName($p), 'file_number' => $p['file_number'],
            'identification' => $p['identification'], 'age' => age($p['birth_date']), 'status_label' => label('patient_status', $p['status']),
        ], Patient::search($q));
        $this->json(['success' => true, 'data' => $data]);
    }

    public function create(): void
    {
        $this->view('patients/form', [
            'title' => 'Nuevo paciente', 'patient' => null,
            'file_number' => Patient::nextFileNumber(), 'professionals' => $this->professionals(),
        ]);
    }

    public function store(): void
    {
        $this->validate($_POST, self::RULES + ['guardian_first_name' => 'max:100', 'guardian_last_name' => 'max:100', 'guardian_email' => 'email|max:150']);
        $data = $this->only(self::FIELDS);
        $data['professional_id'] = $data['professional_id'] ?: null;
        $data['file_number'] = Patient::nextFileNumber();
        $data['created_by'] = Auth::id();

        $id = Database::transaction(function () use ($data) {
            $id = Database::insert('patients', $data);
            if (!empty($_POST['guardian_first_name'])) {
                Database::insert('patient_guardians', [
                    'patient_id' => $id,
                    'first_name' => trim((string) $_POST['guardian_first_name']),
                    'last_name' => trim((string) ($_POST['guardian_last_name'] ?? '')),
                    'relationship' => array_key_exists($_POST['guardian_relationship'] ?? '', options('relationship')) ? $_POST['guardian_relationship'] : 'otro',
                    'identification' => trim((string) ($_POST['guardian_identification'] ?? '')) ?: null,
                    'phone' => trim((string) ($_POST['guardian_phone'] ?? '')) ?: null,
                    'email' => trim((string) ($_POST['guardian_email'] ?? '')) ?: null,
                    'is_primary' => 1, 'authorized_info' => 1,
                ]);
            }
            return $id;
        });

        AuditService::log('CREAR', 'pacientes', $id, 'Paciente registrado: ' . $data['file_number']);
        flash('success', 'Paciente registrado con el expediente ' . $data['file_number'] . '.');
        redirect('/pacientes/' . $id);
    }

    public function edit(int $id): void
    {
        $patient = Patient::find($id) ?: $this->notFound();
        $this->view('patients/form', [
            'title' => 'Editar paciente', 'patient' => $patient,
            'file_number' => $patient['file_number'], 'professionals' => $this->professionals(),
        ]);
    }

    public function update(int $id): void
    {
        $patient = Patient::find($id) ?: $this->notFound();
        $this->validate($_POST, self::RULES);
        $data = $this->only(self::FIELDS);
        $data['professional_id'] = $data['professional_id'] ?: null;
        $data['updated_by'] = Auth::id();
        Database::update('patients', $data, $id);
        AuditService::log('EDITAR', 'pacientes', $id, 'Datos del paciente actualizados', AuditService::diff($patient, $data));
        if ($patient['status'] !== $data['status']) {
            AuditService::log('CAMBIAR_ESTADO', 'pacientes', $id, 'Estado: ' . $patient['status'] . ' → ' . $data['status']);
        }
        flash('success', 'Datos actualizados correctamente.');
        redirect('/pacientes/' . $id);
    }

    /** Panel único del paciente: todas las pestañas del expediente. */
    public function show(int $id): void
    {
        $patient = Patient::find($id) ?: $this->notFound();
        $tab = (string) $this->input('tab', 'resumen');
        $clinicalTabs = ['anamnesis', 'sesiones', 'evaluaciones', 'plan'];
        if (in_array($tab, $clinicalTabs, true) && !can('clinical.view') && !($tab === 'evaluaciones' && can('evaluations.view'))) {
            $tab = 'resumen';
        }
        if (in_array($tab, $clinicalTabs, true)) {
            AuditService::clinicalAccess($id, $tab);
        }

        $data = [
            'title' => Patient::fullName($patient), 'patient' => $patient, 'tab' => $tab,
            'header' => Patient::header($id), 'guardians' => Patient::guardians($id),
            'professionals' => $this->professionals(),
            'alerts' => can('alerts.view') ? AlertService::openForUser(10, $id) : [],
        ];

        switch ($tab) {
            case 'anamnesis':
                $data['anamnesis'] = Database::one('SELECT a.*, u.name AS updated_by_name FROM anamnesis a LEFT JOIN users u ON u.id = a.updated_by WHERE a.patient_id = ?', [$id]);
                break;
            case 'sesiones':
                $data['sessions'] = Database::all(
                    'SELECT s.*, u.name AS professional_name FROM therapy_sessions s LEFT JOIN users u ON u.id = s.professional_id
                      WHERE s.patient_id = ? ORDER BY s.session_date DESC, s.id DESC', [$id]
                );
                $data['reviews'] = Database::all('SELECT * FROM progress_reviews WHERE patient_id = ? ORDER BY review_date DESC', [$id]);
                break;
            case 'evaluaciones':
                $data['evaluations'] = Database::all(
                    'SELECT e.*, u.name AS professional_name, (SELECT COUNT(*) FROM evaluation_instruments ei WHERE ei.evaluation_id = e.id) AS instruments
                       FROM psychological_evaluations e LEFT JOIN users u ON u.id = e.professional_id
                      WHERE e.patient_id = ? ORDER BY e.start_date DESC', [$id]
                );
                break;
            case 'plan':
                $data['plans'] = Database::all('SELECT * FROM treatment_plans WHERE patient_id = ? ORDER BY start_date DESC', [$id]);
                $data['progress'] = [];
                foreach ($data['plans'] as $plan) {
                    $data['progress'][(int) $plan['id']] = Patient::planProgress((int) $plan['id']);
                }
                break;
            case 'documentos':
                $data['documents'] = Database::all(
                    'SELECT d.*, u.name AS created_by_name FROM documents d LEFT JOIN users u ON u.id = d.created_by
                      WHERE d.patient_id = ? ORDER BY d.created_at DESC', [$id]
                );
                $data['templates'] = Database::all("SELECT id, name, type FROM document_templates WHERE is_active = 1 ORDER BY name");
                break;
            case 'pagos':
                $data['payments'] = Database::all('SELECT * FROM payments WHERE patient_id = ? ORDER BY paid_at DESC, id DESC', [$id]);
                $data['packages'] = Database::all('SELECT * FROM session_packages WHERE patient_id = ? ORDER BY purchased_at DESC, id DESC', [$id]);
                break;
            case 'agenda':
                $data['appointments'] = Database::all(
                    'SELECT a.*, u.name AS professional_name, s.id AS session_id FROM appointments a
                       LEFT JOIN users u ON u.id = a.professional_id
                       LEFT JOIN therapy_sessions s ON s.appointment_id = a.id
                      WHERE a.patient_id = ? ORDER BY a.starts_at DESC', [$id]
                );
                break;
            case 'archivos':
                $data['files'] = Database::all(
                    'SELECT f.*, u.name AS uploaded_by_name FROM patient_files f LEFT JOIN users u ON u.id = f.uploaded_by
                      WHERE f.patient_id = ? AND f.deleted_at IS NULL ORDER BY f.created_at DESC', [$id]
                );
                $data['consents'] = Database::all(
                    'SELECT c.*, g.first_name AS g_first, g.last_name AS g_last FROM consents c
                       LEFT JOIN patient_guardians g ON g.id = c.guardian_id WHERE c.patient_id = ? ORDER BY c.consent_date DESC', [$id]
                );
                break;
            default:
                $data['diagnoses'] = can('clinical.view') ? Patient::activeDiagnoses($id) : [];
                $plan = can('clinical.view') ? Patient::activePlan($id) : null;
                $data['plan'] = $plan;
                $data['progress'] = $plan ? Patient::planProgress((int) $plan['id']) : null;
                $data['recent_sessions'] = can('clinical.view') ? Database::all(
                    "SELECT * FROM therapy_sessions WHERE patient_id = ? ORDER BY session_date DESC, id DESC LIMIT 5", [$id]
                ) : [];
                $data['anamnesis'] = can('clinical.view') ? Database::one('SELECT id, status, updated_at, medications, external_professionals FROM anamnesis WHERE patient_id = ?', [$id]) : null;
                $data['evaluations'] = can('evaluations.view') ? Database::all(
                    'SELECT * FROM psychological_evaluations WHERE patient_id = ? ORDER BY start_date DESC LIMIT 3', [$id]
                ) : [];
        }

        $this->view('patients/show', $data);
    }

    // ── Representantes ────────────────────────────────────────
    public function storeGuardian(int $id): void
    {
        Patient::find($id) ?: $this->notFound();
        $this->validate($_POST, ['first_name' => 'required|max:100', 'last_name' => 'required|max:100',
            'relationship' => 'required|in_catalog:relationship', 'email' => 'email|max:150', 'phone' => 'max:30']);
        $data = $this->only(['first_name', 'last_name', 'relationship', 'identification', 'phone', 'email', 'notes']);
        $data['patient_id'] = $id;
        $data['is_primary'] = isset($_POST['is_primary']) ? 1 : 0;
        $data['authorized_info'] = isset($_POST['authorized_info']) ? 1 : 0;
        if ($data['is_primary']) {
            Database::query('UPDATE patient_guardians SET is_primary = 0 WHERE patient_id = ?', [$id]);
        }
        $gid = Database::insert('patient_guardians', $data);
        AuditService::log('CREAR', 'representantes', $gid, 'Representante agregado al paciente ' . $id);
        flash('success', 'Representante registrado.');
        redirect('/pacientes/' . $id);
    }

    public function updateGuardian(int $id): void
    {
        $g = Database::one('SELECT * FROM patient_guardians WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['first_name' => 'required|max:100', 'last_name' => 'required|max:100',
            'relationship' => 'required|in_catalog:relationship', 'email' => 'email|max:150', 'phone' => 'max:30']);
        $data = $this->only(['first_name', 'last_name', 'relationship', 'identification', 'phone', 'email', 'notes']);
        $data['is_primary'] = isset($_POST['is_primary']) ? 1 : 0;
        $data['authorized_info'] = isset($_POST['authorized_info']) ? 1 : 0;
        if ($data['is_primary']) {
            Database::query('UPDATE patient_guardians SET is_primary = 0 WHERE patient_id = ?', [$g['patient_id']]);
        }
        Database::update('patient_guardians', $data, $id);
        AuditService::log('EDITAR', 'representantes', $id, 'Representante actualizado', AuditService::diff($g, $data));
        flash('success', 'Representante actualizado.');
        redirect('/pacientes/' . $g['patient_id']);
    }

    public function deleteGuardian(int $id): void
    {
        $g = Database::one('SELECT * FROM patient_guardians WHERE id = ?', [$id]) ?: $this->notFound();
        Database::query('DELETE FROM patient_guardians WHERE id = ?', [$id]);
        AuditService::log('ELIMINAR', 'representantes', $id, 'Representante eliminado del paciente ' . $g['patient_id']);
        flash('success', 'Representante eliminado.');
        redirect('/pacientes/' . $g['patient_id']);
    }

    // ── Anamnesis (guardado parcial) ──────────────────────────
    public function saveAnamnesis(int $id): void
    {
        Patient::find($id) ?: $this->notFound();
        $fields = ['family_data', 'consultation_reason', 'prenatal_history', 'perinatal_history', 'motor_development',
            'language_development', 'socioemotional_development', 'medical_history', 'medications', 'external_professionals',
            'neurological_history', 'psychiatric_history', 'school_history', 'family_dynamics', 'behavior', 'sleep',
            'feeding', 'screen_use', 'social_relations', 'clinical_observations'];
        $data = $this->only($fields);
        $autosave = (bool) $this->input('autosave', false);
        $data['status'] = (!$autosave && $this->input('status') === 'completa') ? 'completa' : 'borrador';
        $data['updated_by'] = Auth::id();

        $existing = Database::one('SELECT * FROM anamnesis WHERE patient_id = ?', [$id]);
        if ($existing) {
            if ($autosave) {
                $data['status'] = $existing['status'];
            }
            Database::update('anamnesis', $data, (int) $existing['id']);
            $anamnesisId = (int) $existing['id'];
        } else {
            $data['patient_id'] = $id;
            $data['created_by'] = Auth::id();
            $anamnesisId = Database::insert('anamnesis', $data);
        }
        AuditService::log($existing ? 'EDITAR' : 'CREAR', 'anamnesis', $anamnesisId,
            'Anamnesis del paciente ' . $id . ($autosave ? ' (guardado automático)' : ''),
            $existing ? AuditService::diff($existing, $data) : []);

        if (is_ajax()) {
            $this->json(['success' => true, 'message' => 'Anamnesis guardada.']);
        }
        flash('success', $data['status'] === 'completa' ? 'Anamnesis marcada como completa.' : 'Anamnesis guardada. Puede continuar más tarde.');
        redirect('/pacientes/' . $id . '?tab=anamnesis');
    }

    // ── Diagnósticos ──────────────────────────────────────────
    public function storeDiagnosis(int $id): void
    {
        Patient::find($id) ?: $this->notFound();
        $this->validate($_POST, ['diagnosis' => 'required|max:255', 'classification_system' => 'required|in_catalog:classification_system',
            'type' => 'required|in_catalog:diagnosis_type', 'diagnosed_at' => 'required|date', 'code' => 'max:30']);
        $data = $this->only(['diagnosis', 'code', 'classification_system', 'type', 'diagnosed_at', 'notes']);
        $data['patient_id'] = $id;
        $data['created_by'] = Auth::id();
        $did = Database::insert('diagnoses', $data);
        AuditService::log('CREAR', 'diagnosticos', $did, 'Diagnóstico registrado para el paciente ' . $id);
        flash('success', 'Diagnóstico registrado.');
        redirect('/pacientes/' . $id);
    }

    public function updateDiagnosis(int $id): void
    {
        $d = Database::one('SELECT * FROM diagnoses WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['diagnosis' => 'required|max:255', 'classification_system' => 'required|in_catalog:classification_system',
            'type' => 'required|in_catalog:diagnosis_type', 'diagnosed_at' => 'required|date', 'status' => 'required|in_catalog:diagnosis_status']);
        $data = $this->only(['diagnosis', 'code', 'classification_system', 'type', 'diagnosed_at', 'status', 'notes']);
        $data['updated_by'] = Auth::id();
        Database::update('diagnoses', $data, $id);
        AuditService::log('EDITAR', 'diagnosticos', $id, 'Diagnóstico actualizado', AuditService::diff($d, $data));
        flash('success', 'Diagnóstico actualizado. El historial se conserva.');
        redirect('/pacientes/' . $d['patient_id']);
    }

    // ── Consentimientos ───────────────────────────────────────
    public function storeConsent(int $id): void
    {
        Patient::find($id) ?: $this->notFound();
        $this->validate($_POST, ['type' => 'required|in_catalog:consent_type', 'consent_date' => 'required|date', 'status' => 'required|in_catalog:consent_status']);
        $data = $this->only(['type', 'consent_date', 'status', 'notes']);
        $data['patient_id'] = $id;
        $data['guardian_id'] = ($_POST['guardian_id'] ?? '') !== '' ? (int) $_POST['guardian_id'] : null;
        $data['created_by'] = Auth::id();

        if (!empty($_FILES['file']['name'])) {
            try {
                $data['file_id'] = FileStorage::storePatientFile($id, $_FILES['file'], 'consentimiento', 'Consentimiento firmado');
            } catch (\RuntimeException $e) {
                flash('danger', $e->getMessage());
                redirect('/pacientes/' . $id . '?tab=archivos');
            }
        }
        $cid = Database::insert('consents', $data);
        AuditService::log('CREAR', 'consentimientos', $cid, 'Consentimiento registrado para el paciente ' . $id);
        flash('success', 'Consentimiento registrado.');
        redirect('/pacientes/' . $id . '?tab=archivos');
    }

    public function updateConsent(int $id): void
    {
        $c = Database::one('SELECT * FROM consents WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['status' => 'required|in_catalog:consent_status']);
        $data = ['status' => $_POST['status'], 'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null, 'updated_by' => Auth::id()];
        Database::update('consents', $data, $id);
        AuditService::log('CAMBIAR_ESTADO', 'consentimientos', $id, 'Consentimiento: ' . $c['status'] . ' → ' . $data['status']);
        flash('success', 'Consentimiento actualizado.');
        redirect('/pacientes/' . $c['patient_id'] . '?tab=archivos');
    }

    private function professionals(): array
    {
        return Database::all("SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                               WHERE u.is_active = 1 AND r.slug IN ('administrador','psicologo') ORDER BY u.name");
    }
}
