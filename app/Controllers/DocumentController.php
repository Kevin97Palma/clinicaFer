<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AlertService;
use App\Services\AuditService;
use App\Services\DocumentService;

final class DocumentController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->input('estado', '');
        $type = (string) $this->input('tipo', '');
        $where = ['1 = 1'];
        $params = [];
        if (array_key_exists($status, options('document_status'))) {
            $where[] = 'd.status = ?';
            $params[] = $status;
        }
        if (array_key_exists($type, options('document_type'))) {
            $where[] = 'd.type = ?';
            $params[] = $type;
        }
        $sql = implode(' AND ', $where);

        $this->view('documents/index', [
            'title' => 'Documentos',
            'documents' => Database::all(
                "SELECT d.*, p.first_name, p.last_name, p.file_number, u.name AS created_by_name
                   FROM documents d JOIN patients p ON p.id = d.patient_id LEFT JOIN users u ON u.id = d.created_by
                  WHERE $sql ORDER BY d.created_at DESC LIMIT 200", $params
            ),
            'status' => $status, 'type' => $type,
        ]);
    }

    public function create(): void
    {
        $patientId = (int) $this->input('patient_id', 0);
        $patient = $patientId ? Patient::find($patientId) : null;
        $templateId = (int) $this->input('template_id', 0);
        $type = (string) $this->input('type', '');
        $evaluationId = (int) $this->input('evaluation_id', 0);

        $template = $templateId
            ? Database::one('SELECT * FROM document_templates WHERE id = ?', [$templateId])
            : ($type ? Database::one("SELECT * FROM document_templates WHERE type = ? AND is_active = 1 ORDER BY id LIMIT 1", [$type]) : null);

        $content = '';
        if ($template && $patient) {
            $content = DocumentService::render($template['content'], DocumentService::variables((int) $patient['id']));
            if ($evaluationId) {
                $content = $this->appendEvaluation($content, $evaluationId);
            }
        }

        $this->view('documents/form', [
            'title' => 'Nuevo documento',
            'document' => null, 'patient' => $patient, 'template' => $template,
            'templates' => Database::all('SELECT id, name, type FROM document_templates WHERE is_active = 1 ORDER BY type, name'),
            'content' => $content, 'type' => $type ?: ($template['type'] ?? 'personalizado'),
            'evaluation_id' => $evaluationId,
        ]);
    }

    public function store(): void
    {
        $this->validate($_POST, ['patient_id' => 'required|int', 'title' => 'required|max:200', 'type' => 'required|in_catalog:document_type']);
        $patientId = (int) $_POST['patient_id'];
        Patient::find($patientId) ?: $this->notFound();
        $content = DocumentService::sanitize((string) ($_POST['content'] ?? ''));
        $issue = ($_POST['action'] ?? '') === 'emitir';

        $id = Database::transaction(function () use ($patientId, $content, $issue) {
            $docId = Database::insert('documents', [
                'patient_id' => $patientId,
                'template_id' => !empty($_POST['template_id']) ? (int) $_POST['template_id'] : null,
                'evaluation_id' => !empty($_POST['evaluation_id']) ? (int) $_POST['evaluation_id'] : null,
                'type' => $_POST['type'],
                'title' => mb_substr(trim((string) $_POST['title']), 0, 200),
                'status' => $issue ? 'emitido' : 'borrador',
                'issued_at' => $issue ? date('Y-m-d H:i:s') : null,
                'current_version' => 1,
                'created_by' => Auth::id(),
            ]);
            Database::insert('document_versions', ['document_id' => $docId, 'version' => 1, 'content' => $content, 'created_by' => Auth::id()]);
            return $docId;
        });

        AuditService::log('GENERAR_DOCUMENTO', 'documentos', $id, 'Documento creado: ' . $_POST['title']);
        flash('success', $issue ? 'Documento emitido.' : 'Documento guardado como borrador.');
        redirect('/documentos/' . $id);
    }

    public function show(int $id): void
    {
        $doc = $this->findDoc($id);
        AuditService::clinicalAccess((int) $doc['patient_id'], 'documento');
        $this->view('documents/show', [
            'title' => $doc['title'],
            'document' => $doc,
            'version' => Database::one('SELECT * FROM document_versions WHERE document_id = ? ORDER BY version DESC LIMIT 1', [$id]),
            'versions' => Database::all(
                'SELECT v.version, v.created_at, u.name AS created_by_name FROM document_versions v
                   LEFT JOIN users u ON u.id = v.created_by WHERE v.document_id = ? ORDER BY v.version DESC', [$id]
            ),
        ]);
    }

    public function edit(int $id): void
    {
        $doc = $this->findDoc($id);
        if ($doc['status'] === 'anulado') {
            flash('warning', 'Un documento anulado no se puede editar.');
            redirect('/documentos/' . $id);
        }
        $version = Database::one('SELECT * FROM document_versions WHERE document_id = ? ORDER BY version DESC LIMIT 1', [$id]);
        $this->view('documents/form', [
            'title' => 'Editar documento',
            'document' => $doc, 'patient' => Patient::find((int) $doc['patient_id']), 'template' => null,
            'templates' => Database::all('SELECT id, name, type FROM document_templates WHERE is_active = 1 ORDER BY type, name'),
            'content' => $version['content'], 'type' => $doc['type'], 'evaluation_id' => (int) $doc['evaluation_id'],
        ]);
    }

    /** Guardar crea una nueva versión: las versiones anteriores quedan intactas. */
    public function update(int $id): void
    {
        $doc = $this->findDoc($id);
        $this->validate($_POST, ['title' => 'required|max:200']);
        $content = DocumentService::sanitize((string) ($_POST['content'] ?? ''));
        $issue = ($_POST['action'] ?? '') === 'emitir';
        $last = Database::one('SELECT * FROM document_versions WHERE document_id = ? ORDER BY version DESC LIMIT 1', [$id]);

        Database::transaction(function () use ($id, $doc, $content, $issue, $last) {
            $version = (int) $doc['current_version'];
            if ($content !== $last['content']) {
                $version++;
                Database::insert('document_versions', ['document_id' => $id, 'version' => $version, 'content' => $content, 'created_by' => Auth::id()]);
            }
            Database::update('documents', [
                'title' => mb_substr(trim((string) $_POST['title']), 0, 200),
                'current_version' => $version,
                'status' => $issue ? 'emitido' : $doc['status'],
                'issued_at' => $issue && !$doc['issued_at'] ? date('Y-m-d H:i:s') : $doc['issued_at'],
                'updated_by' => Auth::id(),
            ], $id);
        });

        AuditService::log('EDITAR', 'documentos', $id, 'Documento actualizado' . ($issue ? ' y emitido' : ''), ['content', 'title']);
        if ($issue) {
            AlertService::resolve('INFORME_PENDIENTE', 'document', $id);
        }
        flash('success', $issue ? 'Documento emitido. La versión queda guardada como copia histórica.' : 'Cambios guardados.');
        redirect('/documentos/' . $id);
    }

    public function status(int $id): void
    {
        $doc = $this->findDoc($id);
        $status = (string) $this->input('status', '');
        if (!array_key_exists($status, options('document_status'))) {
            flash('danger', 'Estado no válido.');
            redirect('/documentos/' . $id);
        }
        Database::update('documents', [
            'status' => $status,
            'issued_at' => $status === 'emitido' && !$doc['issued_at'] ? date('Y-m-d H:i:s') : $doc['issued_at'],
            'updated_by' => Auth::id(),
        ], $id);
        AuditService::log($status === 'anulado' ? 'ANULAR' : 'CAMBIAR_ESTADO', 'documentos', $id, 'Documento: ' . $doc['status'] . ' → ' . $status);
        if ($status !== 'borrador') {
            AlertService::resolve('INFORME_PENDIENTE', 'document', $id);
        }
        flash('success', 'Documento actualizado.');
        redirect('/documentos/' . $id);
    }

    /** Vista imprimible con membrete y firma (imprimir o guardar como PDF desde el navegador). */
    public function printView(int $id): void
    {
        $doc = $this->findDoc($id);
        $version = (int) $this->input('v', 0);
        $row = $version
            ? Database::one('SELECT * FROM document_versions WHERE document_id = ? AND version = ?', [$id, $version])
            : Database::one('SELECT * FROM document_versions WHERE document_id = ? ORDER BY version DESC LIMIT 1', [$id]);
        if (!$row) {
            $this->notFound();
        }
        AuditService::log('DESCARGAR_DOCUMENTO', 'documentos', $id, 'Impresión/PDF del documento v' . $row['version']);
        $this->view('documents/print', [
            'title' => $doc['title'], 'document' => $doc, 'version' => $row,
        ], 'layouts/print');
    }

    private function appendEvaluation(string $content, int $evaluationId): string
    {
        $ev = Database::one('SELECT * FROM psychological_evaluations WHERE id = ?', [$evaluationId]);
        if (!$ev) {
            return $content;
        }
        $instruments = Database::all(
            'SELECT ei.*, ic.name FROM evaluation_instruments ei JOIN instrument_catalog ic ON ic.id = ei.instrument_id
              WHERE ei.evaluation_id = ? ORDER BY ei.applied_at', [$evaluationId]
        );
        $html = '<h3>Instrumentos aplicados</h3><table><tr><th>Instrumento</th><th>Fecha</th><th>Resultados</th><th>Interpretación</th></tr>';
        foreach ($instruments as $i) {
            $html .= '<tr><td>' . e($i['name']) . '</td><td>' . e(fdate($i['applied_at'])) . '</td><td>'
                . nl2br(e((string) $i['scores'])) . '</td><td>' . nl2br(e((string) $i['interpretation'])) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '<h3>Integración clínica</h3><p>' . nl2br(e((string) $ev['clinical_integration'])) . '</p>';
        $html .= '<h3>Conclusiones</h3><p>' . nl2br(e((string) $ev['conclusions'])) . '</p>';
        $html .= '<h3>Recomendaciones</h3><p>' . nl2br(e((string) $ev['recommendations'])) . '</p>';
        return $content . $html;
    }

    private function findDoc(int $id): array
    {
        $doc = Database::one(
            'SELECT d.*, p.first_name, p.last_name, p.file_number, p.birth_date, p.identification, u.name AS created_by_name
               FROM documents d JOIN patients p ON p.id = d.patient_id LEFT JOIN users u ON u.id = d.created_by WHERE d.id = ?',
            [$id]
        );
        if (!$doc) {
            $this->notFound();
        }
        return $doc;
    }
}
