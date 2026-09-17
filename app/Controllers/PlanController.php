<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AlertService;
use App\Services\AuditService;

final class PlanController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->input('estado', 'activo');
        $params = [];
        $where = '1 = 1';
        if (array_key_exists($status, options('plan_status'))) {
            $where = 'tp.status = ?';
            $params[] = $status;
        }
        $plans = Database::all(
            "SELECT tp.*, p.first_name, p.last_name, p.file_number,
                    (SELECT COUNT(*) FROM treatment_objectives o WHERE o.plan_id = tp.id) AS total,
                    (SELECT COUNT(*) FROM treatment_objectives o WHERE o.plan_id = tp.id AND o.status = 'logrado') AS achieved
               FROM treatment_plans tp JOIN patients p ON p.id = tp.patient_id
              WHERE $where ORDER BY tp.start_date DESC", $params
        );
        $this->view('plans/index', ['title' => 'Planes terapéuticos', 'plans' => $plans, 'status' => $status]);
    }

    public function store(int $patientId): void
    {
        Patient::find($patientId) ?: $this->notFound();
        $this->validate($_POST, ['start_date' => 'required|date', 'review_date' => 'date', 'general_objective' => 'required']);

        $planId = Database::transaction(function () use ($patientId) {
            $planId = Database::insert('treatment_plans', [
                'patient_id' => $patientId,
                'start_date' => $_POST['start_date'],
                'review_date' => trim((string) ($_POST['review_date'] ?? '')) ?: null,
                'general_objective' => trim((string) $_POST['general_objective']),
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
            ]);
            foreach ((array) ($_POST['areas'] ?? []) as $i => $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    Database::insert('treatment_areas', ['plan_id' => $planId, 'name' => mb_substr($name, 0, 120), 'sort_order' => (int) $i]);
                }
            }
            return $planId;
        });
        AuditService::log('CREAR', 'planes', $planId, 'Plan terapéutico creado para el paciente ' . $patientId);
        flash('success', 'Plan terapéutico creado. Agregue objetivos a cada área.');
        redirect('/planes/' . $planId);
    }

    public function show(int $id): void
    {
        $plan = Database::one(
            'SELECT tp.*, p.first_name, p.last_name, p.file_number, p.birth_date FROM treatment_plans tp
               JOIN patients p ON p.id = tp.patient_id WHERE tp.id = ?', [$id]
        ) ?: $this->notFound();
        AuditService::clinicalAccess((int) $plan['patient_id'], 'plan');

        $this->view('plans/show', [
            'title' => 'Plan terapéutico',
            'plan' => $plan,
            'progress' => Patient::planProgress($id),
            'reviews' => Database::all('SELECT * FROM progress_reviews WHERE plan_id = ? ORDER BY review_date DESC', [$id]),
        ]);
    }

    public function update(int $id): void
    {
        $plan = Database::one('SELECT * FROM treatment_plans WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['start_date' => 'required|date', 'review_date' => 'date',
            'general_objective' => 'required', 'status' => 'required|in_catalog:plan_status']);
        $data = $this->only(['start_date', 'review_date', 'general_objective', 'status', 'notes']);
        $data['updated_by'] = Auth::id();
        Database::update('treatment_plans', $data, $id);
        if ($data['status'] !== 'activo') {
            AlertService::resolve('REVISION_TERAPEUTICA', 'plan', $id);
        }
        AuditService::log('EDITAR', 'planes', $id, 'Plan actualizado', AuditService::diff($plan, $data));
        flash('success', 'Plan actualizado.');
        redirect('/planes/' . $id);
    }

    public function storeArea(int $planId): void
    {
        Database::one('SELECT id FROM treatment_plans WHERE id = ?', [$planId]) ?: $this->notFound();
        $this->validate($_POST, ['name' => 'required|max:120']);
        $order = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM treatment_areas WHERE plan_id = ?', [$planId]);
        Database::insert('treatment_areas', ['plan_id' => $planId, 'name' => trim((string) $_POST['name']), 'sort_order' => $order]);
        AuditService::log('CREAR', 'planes', $planId, 'Área agregada al plan');
        flash('success', 'Área agregada.');
        redirect('/planes/' . $planId);
    }

    public function deleteArea(int $id): void
    {
        $area = Database::one('SELECT * FROM treatment_areas WHERE id = ?', [$id]) ?: $this->notFound();
        Database::query('DELETE FROM treatment_areas WHERE id = ?', [$id]);
        AuditService::log('ELIMINAR', 'planes', (int) $area['plan_id'], 'Área eliminada del plan: ' . $area['name']);
        flash('success', 'Área eliminada junto con sus objetivos.');
        redirect('/planes/' . $area['plan_id']);
    }

    public function storeObjective(int $areaId): void
    {
        $area = Database::one('SELECT * FROM treatment_areas WHERE id = ?', [$areaId]) ?: $this->notFound();
        $this->validate($_POST, ['objective' => 'required|max:255', 'indicator' => 'max:255']);
        $id = Database::insert('treatment_objectives', [
            'area_id' => $areaId, 'plan_id' => (int) $area['plan_id'],
            'objective' => trim((string) $_POST['objective']),
            'indicator' => trim((string) ($_POST['indicator'] ?? '')) ?: null,
            'status' => 'pendiente',
        ]);
        AuditService::log('CREAR', 'planes', (int) $area['plan_id'], 'Objetivo agregado (' . $id . ')');
        flash('success', 'Objetivo agregado.');
        redirect('/planes/' . $area['plan_id']);
    }

    public function updateObjective(int $id): void
    {
        $o = Database::one('SELECT * FROM treatment_objectives WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['objective' => 'required|max:255', 'status' => 'required|in_catalog:objective_status']);
        Database::query(
            "UPDATE treatment_objectives SET objective = ?, indicator = ?, status = ?,
                    achieved_at = IF(? = 'logrado', COALESCE(achieved_at, CURDATE()), NULL) WHERE id = ?",
            [trim((string) $_POST['objective']), trim((string) ($_POST['indicator'] ?? '')) ?: null, $_POST['status'], $_POST['status'], $id]
        );
        AuditService::log('EDITAR', 'planes', (int) $o['plan_id'], 'Objetivo actualizado (' . $id . ')');
        flash('success', 'Objetivo actualizado.');
        redirect('/planes/' . $o['plan_id']);
    }

    public function deleteObjective(int $id): void
    {
        $o = Database::one('SELECT * FROM treatment_objectives WHERE id = ?', [$id]) ?: $this->notFound();
        Database::query('DELETE FROM treatment_objectives WHERE id = ?', [$id]);
        AuditService::log('ELIMINAR', 'planes', (int) $o['plan_id'], 'Objetivo eliminado (' . $id . ')');
        flash('success', 'Objetivo eliminado.');
        redirect('/planes/' . $o['plan_id']);
    }

    // ── Evaluación de progreso ────────────────────────────────
    public function createReview(int $patientId): void
    {
        $patient = Patient::find($patientId) ?: $this->notFound();
        $plan = Patient::activePlan($patientId);
        $progress = $plan ? Patient::planProgress((int) $plan['id']) : null;
        $lastReview = Database::value('SELECT MAX(review_date) FROM progress_reviews WHERE patient_id = ?', [$patientId]);

        $group = fn(string $status) => $progress ? implode("\n", array_map(fn($o) => '• ' . $o['objective'],
            array_filter($progress['objectives'], fn($o) => $o['status'] === $status))) : '';

        $this->view('reviews/form', [
            'title' => 'Evaluación de progreso',
            'patient' => $patient, 'plan' => $plan, 'progress' => $progress,
            'sessions_since' => (int) Database::value(
                "SELECT COUNT(*) FROM therapy_sessions WHERE patient_id = ? AND attendance = 'atendido' AND session_date > COALESCE(?, '1900-01-01')",
                [$patientId, $lastReview]
            ),
            'prefill' => [
                'objectives_initial' => $progress ? implode("\n", array_map(fn($o) => '• ' . $o['objective'], $progress['objectives'])) : '',
                'objectives_achieved' => $group('logrado'),
                'objectives_in_progress' => $group('en_proceso'),
                'objectives_no_progress' => $group('pendiente'),
            ],
        ]);
    }

    public function storeReview(int $patientId): void
    {
        $patient = Patient::find($patientId) ?: $this->notFound();
        $this->validate($_POST, ['review_date' => 'required|date', 'general_status' => 'required|in_catalog:general_status',
            'clinical_decision' => 'required|in_catalog:clinical_decision']);
        $data = $this->only(['review_date', 'objectives_initial', 'objectives_achieved', 'objectives_in_progress',
            'objectives_no_progress', 'patient_report', 'family_report', 'school_report', 'clinical_observation',
            'general_status', 'clinical_decision']);
        $data['patient_id'] = $patientId;
        $data['plan_id'] = !empty($_POST['plan_id']) ? (int) $_POST['plan_id'] : null;
        $data['sessions_at_review'] = (int) $patient['sessions_count'];
        $data['created_by'] = Auth::id();

        $id = Database::insert('progress_reviews', $data);
        AlertService::resolve('REVISION_TERAPEUTICA', 'patient', $patientId);
        if ($data['plan_id']) {
            AlertService::resolve('REVISION_TERAPEUTICA', 'plan', $data['plan_id']);
            // La próxima revisión se agenda según la frecuencia configurada
            Database::query('UPDATE treatment_plans SET review_date = DATE_ADD(?, INTERVAL 2 MONTH) WHERE id = ?', [$data['review_date'], $data['plan_id']]);
        }
        AuditService::log('CREAR', 'revisiones', $id, 'Evaluación de progreso del paciente ' . $patientId);
        flash('success', 'Evaluación de progreso registrada.');
        redirect('/revisiones/' . $id);
    }

    public function showReview(int $id): void
    {
        $review = Database::one(
            'SELECT r.*, p.first_name, p.last_name, p.file_number, u.name AS created_by_name FROM progress_reviews r
               JOIN patients p ON p.id = r.patient_id LEFT JOIN users u ON u.id = r.created_by WHERE r.id = ?', [$id]
        ) ?: $this->notFound();
        AuditService::clinicalAccess((int) $review['patient_id'], 'revision');
        $this->view('reviews/show', ['title' => 'Evaluación de progreso', 'review' => $review]);
    }
}
