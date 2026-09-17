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

final class EvaluationController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->input('estado', '');
        $where = '1 = 1';
        $params = [];
        if (array_key_exists($status, options('evaluation_status'))) {
            $where = 'e.status = ?';
            $params[] = $status;
        }
        $this->view('evaluations/index', [
            'title' => 'Evaluaciones psicológicas',
            'status' => $status,
            'evaluations' => Database::all(
                "SELECT e.*, p.first_name, p.last_name, p.file_number, u.name AS professional_name,
                        (SELECT COUNT(*) FROM evaluation_instruments ei WHERE ei.evaluation_id = e.id) AS instruments
                   FROM psychological_evaluations e JOIN patients p ON p.id = e.patient_id
                   LEFT JOIN users u ON u.id = e.professional_id
                  WHERE $where ORDER BY e.start_date DESC", $params
            ),
            'counts' => Database::all('SELECT status, COUNT(*) AS n FROM psychological_evaluations GROUP BY status'),
        ]);
    }

    public function create(): void
    {
        $patientId = (int) $this->input('patient_id', 0);
        $this->view('evaluations/form', [
            'title' => 'Nueva evaluación',
            'patient' => $patientId ? Patient::find($patientId) : null,
            'professionals' => Database::all("SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                                               WHERE u.is_active = 1 AND r.slug IN ('administrador','psicologo') ORDER BY u.name"),
        ]);
    }

    public function store(): void
    {
        $this->validate($_POST, ['patient_id' => 'required|int', 'start_date' => 'required|date', 'reason' => 'required',
            'areas' => 'required|in_catalog:evaluation_area']);
        $areas = array_values(array_intersect((array) $_POST['areas'], array_keys(options('evaluation_area'))));
        $id = Database::insert('psychological_evaluations', [
            'patient_id' => (int) $_POST['patient_id'],
            'professional_id' => (int) ($_POST['professional_id'] ?? Auth::id()),
            'start_date' => $_POST['start_date'],
            'reason' => trim((string) $_POST['reason']),
            'areas' => implode(',', $areas),
            'status' => 'en_proceso',
            'created_by' => Auth::id(),
        ]);
        AuditService::log('CREAR', 'evaluaciones', $id, 'Evaluación iniciada para el paciente ' . (int) $_POST['patient_id']);
        flash('success', 'Evaluación creada. Agregue los instrumentos aplicados.');
        redirect('/evaluaciones/' . $id);
    }

    public function show(int $id): void
    {
        $ev = Database::one(
            'SELECT e.*, p.first_name, p.last_name, p.file_number, p.birth_date, u.name AS professional_name
               FROM psychological_evaluations e JOIN patients p ON p.id = e.patient_id
               LEFT JOIN users u ON u.id = e.professional_id WHERE e.id = ?', [$id]
        ) ?: $this->notFound();
        AuditService::clinicalAccess((int) $ev['patient_id'], 'evaluacion');

        $this->view('evaluations/show', [
            'title' => 'Evaluación · ' . $ev['first_name'] . ' ' . $ev['last_name'],
            'evaluation' => $ev,
            'instruments' => Database::all(
                'SELECT ei.*, ic.name AS instrument_name, f.original_name FROM evaluation_instruments ei
                   JOIN instrument_catalog ic ON ic.id = ei.instrument_id
                   LEFT JOIN patient_files f ON f.id = ei.file_id
                  WHERE ei.evaluation_id = ? ORDER BY ei.applied_at, ei.id', [$id]
            ),
            'catalog' => Database::all('SELECT id, name FROM instrument_catalog WHERE is_active = 1 ORDER BY name'),
            'documents' => Database::all('SELECT id, title, status FROM documents WHERE evaluation_id = ? ORDER BY created_at DESC', [$id]),
        ]);
    }

    public function update(int $id): void
    {
        $ev = Database::one('SELECT * FROM psychological_evaluations WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['status' => 'required|in_catalog:evaluation_status', 'end_date' => 'date', 'reason' => 'required']);
        $data = $this->only(['reason', 'end_date', 'status', 'clinical_integration', 'conclusions', 'recommendations']);
        if (!empty($_POST['areas'])) {
            $data['areas'] = implode(',', array_values(array_intersect((array) $_POST['areas'], array_keys(options('evaluation_area')))));
        }
        if ($data['status'] === 'informe_entregado' && !$ev['report_delivered_at']) {
            $data['report_delivered_at'] = date('Y-m-d');
        }
        $data['updated_by'] = Auth::id();
        Database::update('psychological_evaluations', $data, $id);

        if (in_array($data['status'], ['informe_entregado'], true)) {
            AlertService::resolve('INFORME_PENDIENTE', 'evaluation', $id);
            AlertService::resolve('EVALUACION_PENDIENTE', 'evaluation', $id);
        }
        AuditService::log('EDITAR', 'evaluaciones', $id, 'Evaluación actualizada', AuditService::diff($ev, $data));
        flash('success', 'Evaluación actualizada.');
        redirect('/evaluaciones/' . $id);
    }

    public function storeInstrument(int $evaluationId): void
    {
        $ev = Database::one('SELECT * FROM psychological_evaluations WHERE id = ?', [$evaluationId]) ?: $this->notFound();
        $this->validate($_POST, ['instrument_id' => 'required|int', 'applied_at' => 'required|date']);

        $fileId = null;
        if (!empty($_FILES['file']['name'])) {
            try {
                $fileId = FileStorage::storePatientFile((int) $ev['patient_id'], $_FILES['file'], 'evaluacion', 'Protocolo o resultados');
            } catch (\RuntimeException $e) {
                flash('danger', $e->getMessage());
                redirect('/evaluaciones/' . $evaluationId);
            }
        }
        $id = Database::insert('evaluation_instruments', [
            'evaluation_id' => $evaluationId,
            'instrument_id' => (int) $_POST['instrument_id'],
            'applied_at' => $_POST['applied_at'],
            'scores' => trim((string) ($_POST['scores'] ?? '')) ?: null,
            'interpretation' => trim((string) ($_POST['interpretation'] ?? '')) ?: null,
            'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
            'file_id' => $fileId,
            'created_by' => Auth::id(),
        ]);
        AuditService::log('CREAR', 'evaluaciones', $evaluationId, 'Instrumento registrado (' . $id . ')');
        flash('success', 'Instrumento registrado. La interpretación siempre requiere validación profesional.');
        redirect('/evaluaciones/' . $evaluationId);
    }

    public function updateInstrument(int $id): void
    {
        $inst = Database::one('SELECT * FROM evaluation_instruments WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['applied_at' => 'required|date']);
        $data = $this->only(['applied_at', 'scores', 'interpretation', 'notes']);
        Database::update('evaluation_instruments', $data, $id);
        AuditService::log('EDITAR', 'evaluaciones', (int) $inst['evaluation_id'], 'Instrumento actualizado (' . $id . ')', AuditService::diff($inst, $data));
        flash('success', 'Instrumento actualizado.');
        redirect('/evaluaciones/' . $inst['evaluation_id']);
    }

    public function deleteInstrument(int $id): void
    {
        $inst = Database::one('SELECT * FROM evaluation_instruments WHERE id = ?', [$id]) ?: $this->notFound();
        Database::query('DELETE FROM evaluation_instruments WHERE id = ?', [$id]);
        AuditService::log('ELIMINAR', 'evaluaciones', (int) $inst['evaluation_id'], 'Instrumento eliminado (' . $id . ')');
        flash('success', 'Instrumento eliminado.');
        redirect('/evaluaciones/' . $inst['evaluation_id']);
    }
}
