<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AlertService;
use App\Services\AuditService;
use App\Services\PackageService;

final class PaymentController extends Controller
{
    public function index(): void
    {
        $from = (string) $this->input('desde', date('Y-m-01'));
        $to = (string) $this->input('hasta', date('Y-m-t'));
        $status = (string) $this->input('estado', '');

        $where = ['p.paid_at BETWEEN ? AND ?'];
        $params = [$from, $to];
        if (array_key_exists($status, options('payment_status'))) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        $sql = implode(' AND ', $where);

        $this->view('payments/index', [
            'title' => 'Pagos',
            'payments' => Database::all(
                "SELECT p.*, pa.first_name, pa.last_name, pa.file_number FROM payments p
                   JOIN patients pa ON pa.id = p.patient_id WHERE $sql ORDER BY p.paid_at DESC, p.id DESC LIMIT 300", $params
            ),
            'totals' => Database::one(
                "SELECT COALESCE(SUM(CASE WHEN status = 'pagado' THEN amount END), 0) AS pagado,
                        COALESCE(SUM(CASE WHEN status = 'pendiente' THEN amount END), 0) AS pendiente,
                        COALESCE(SUM(CASE WHEN status = 'anulado' THEN amount END), 0) AS anulado
                   FROM payments p WHERE $sql", $params
            ),
            'packages' => Database::all(
                "SELECT sp.*, pa.first_name, pa.last_name FROM session_packages sp JOIN patients pa ON pa.id = sp.patient_id
                  WHERE sp.status = 'activo' ORDER BY sp.sessions_remaining, sp.purchased_at"
            ),
            'from' => $from, 'to' => $to, 'status' => $status,
            'professionals' => [],
        ]);
    }

    public function store(): void
    {
        $this->validate($_POST, ['patient_id' => 'required|int', 'paid_at' => 'required|date', 'concept' => 'required|max:200',
            'amount' => 'required|numeric|min:0', 'method' => 'required|in_catalog:payment_method', 'status' => 'required|in_catalog:payment_status']);
        Patient::find((int) $_POST['patient_id']) ?: $this->notFound();

        $data = $this->only(['paid_at', 'concept', 'amount', 'method', 'status', 'reference', 'notes']);
        $data['patient_id'] = (int) $_POST['patient_id'];
        $data['package_id'] = !empty($_POST['package_id']) ? (int) $_POST['package_id'] : null;
        $data['created_by'] = Auth::id();
        $id = Database::insert('payments', $data);

        AuditService::log('CREAR', 'pagos', $id, 'Pago registrado: ' . money($data['amount']) . ' (' . $data['status'] . ')');
        flash('success', 'Pago registrado.');
        redirect_back('/pagos');
    }

    public function update(int $id): void
    {
        $p = Database::one('SELECT * FROM payments WHERE id = ?', [$id]) ?: $this->notFound();
        if ($p['status'] === 'anulado') {
            flash('warning', 'Un pago anulado no se puede modificar.');
            redirect_back('/pagos');
        }
        $this->validate($_POST, ['paid_at' => 'required|date', 'concept' => 'required|max:200', 'amount' => 'required|numeric|min:0']);
        $data = $this->only(['paid_at', 'concept', 'amount', 'method', 'status', 'reference', 'notes']);
        $data['updated_by'] = Auth::id();
        Database::update('payments', $data, $id);
        AuditService::log('EDITAR', 'pagos', $id, 'Pago actualizado', AuditService::diff($p, $data));
        flash('success', 'Pago actualizado.');
        redirect_back('/pagos');
    }

    public function markPaid(int $id): void
    {
        $p = Database::one('SELECT * FROM payments WHERE id = ?', [$id]) ?: $this->notFound();
        if ($p['status'] !== 'pendiente') {
            flash('warning', 'El pago no está pendiente.');
            redirect_back('/pagos');
        }
        Database::query("UPDATE payments SET status = 'pagado', paid_at = CURDATE(), updated_by = ? WHERE id = ?", [Auth::id(), $id]);
        AlertService::resolve('PAGO_PENDIENTE', 'payment', $id);
        AuditService::log('CAMBIAR_ESTADO', 'pagos', $id, 'Pago marcado como pagado');
        flash('success', 'Pago registrado como pagado.');
        redirect_back('/pagos');
    }

    /** Los pagos no se eliminan: se anulan conservando la trazabilidad. */
    public function void(int $id): void
    {
        $p = Database::one('SELECT * FROM payments WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['void_reason' => 'required|max:255']);
        Database::query(
            "UPDATE payments SET status = 'anulado', void_reason = ?, voided_by = ?, voided_at = NOW() WHERE id = ?",
            [trim((string) $_POST['void_reason']), Auth::id(), $id]
        );
        AlertService::resolve('PAGO_PENDIENTE', 'payment', $id);
        AuditService::log('ANULAR', 'pagos', $id, 'Pago anulado: ' . $_POST['void_reason'], ['status']);
        flash('success', 'Pago anulado. El registro se conserva para auditoría.');
        redirect_back('/pagos');
    }

    /** Desglose de sesiones para reembolso. */
    public function breakdown(): void
    {
        $patientId = (int) $this->input('patient_id', 0);
        $from = (string) $this->input('desde', date('Y-m-01', strtotime('-3 months')));
        $to = (string) $this->input('hasta', date('Y-m-d'));
        $data = ['title' => 'Desglose de sesiones', 'patient' => null, 'rows' => [], 'from' => $from, 'to' => $to,
            'subtotal' => 0.0, 'discount' => 0.0, 'total' => 0.0, 'payments' => []];

        if ($patientId) {
            $patient = Patient::find($patientId) ?: $this->notFound();
            $sessions = Database::all(
                "SELECT s.*, sp.price_regular, sp.total_paid, sp.sessions_total, sp.name AS package_name
                   FROM therapy_sessions s LEFT JOIN session_packages sp ON sp.id = s.package_id
                  WHERE s.patient_id = ? AND s.session_date BETWEEN ? AND ? AND s.attendance = 'atendido'
                  ORDER BY s.session_date, s.id",
                [$patientId, $from, $to]
            );
            $rows = [];
            $subtotal = $paid = 0.0;
            foreach ($sessions as $s) {
                if ($s['package_id']) {
                    [$regular, $unitPaid] = PackageService::unitValues([
                        'price_regular' => $s['price_regular'], 'total_paid' => $s['total_paid'], 'sessions_total' => $s['sessions_total'],
                    ]);
                } else {
                    $regular = $unitPaid = (float) $s['fee'];
                }
                $subtotal += $regular;
                $paid += $unitPaid;
                $rows[] = $s + ['regular_value' => $regular, 'paid_value' => $unitPaid];
            }
            $data = array_merge($data, [
                'patient' => $patient, 'rows' => $rows,
                'subtotal' => $subtotal, 'discount' => round($subtotal - $paid, 2), 'total' => $paid,
                'payments' => Database::all(
                    "SELECT * FROM payments WHERE patient_id = ? AND paid_at BETWEEN ? AND ? AND status = 'pagado' ORDER BY paid_at",
                    [$patientId, $from, $to]
                ),
            ]);
            AuditService::log('EXPORTAR', 'pagos', $patientId, "Desglose de sesiones $from a $to");
        }

        $this->view('payments/breakdown', $data);
    }

    /** Guarda el desglose como documento del paciente (copia histórica). */
    public function breakdownDocument(): void
    {
        $this->validate($_POST, ['patient_id' => 'required|int', 'content' => 'required']);
        $patientId = (int) $_POST['patient_id'];
        $patient = Patient::find($patientId) ?: $this->notFound();
        $content = \App\Services\DocumentService::sanitize((string) $_POST['content']);

        $id = Database::transaction(function () use ($patientId, $content) {
            $docId = Database::insert('documents', [
                'patient_id' => $patientId, 'type' => 'desglose_sesiones',
                'title' => 'Desglose de sesiones ' . fdate($_POST['from'] ?? date('Y-m-d')) . ' — ' . fdate($_POST['to'] ?? date('Y-m-d')),
                'status' => 'emitido', 'issued_at' => date('Y-m-d H:i:s'), 'current_version' => 1, 'created_by' => Auth::id(),
            ]);
            Database::insert('document_versions', ['document_id' => $docId, 'version' => 1, 'content' => $content, 'created_by' => Auth::id()]);
            return $docId;
        });
        AuditService::log('GENERAR_DOCUMENTO', 'documentos', $id, 'Desglose de sesiones generado para ' . Patient::fullName($patient));
        flash('success', 'Desglose guardado como documento del paciente.');
        redirect('/documentos/' . $id);
    }
}
