<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;

final class ReportController extends Controller
{
    /** Definición de cada reporte: consulta de detalle y columnas. */
    private function reports(): array
    {
        return [
            'sesiones_realizadas' => ['Sesiones realizadas', 'clinical.view'],
            'cancelaciones' => ['Cancelaciones', 'clinical.view'],
            'inasistencias' => ['Inasistencias', 'clinical.view'],
            'pacientes_nuevos' => ['Pacientes nuevos', 'patients.view'],
            'pacientes_activos' => ['Pacientes activos', 'patients.view'],
            'evaluaciones_realizadas' => ['Evaluaciones realizadas', 'evaluations.view'],
            'evaluaciones_pendientes' => ['Evaluaciones pendientes', 'evaluations.view'],
            'documentos_pendientes' => ['Documentos pendientes', 'documents.view'],
            'ingresos' => ['Ingresos', 'payments.view'],
            'pagos_pendientes' => ['Pagos pendientes', 'payments.view'],
            'paquetes_activos' => ['Paquetes activos', 'payments.view'],
        ];
    }

    public function index(): void
    {
        [$from, $to, $patientId, $professionalId] = $this->filters();
        $report = (string) $this->input('reporte', 'sesiones_realizadas');
        $definitions = $this->reports();
        if (!isset($definitions[$report]) || !can($definitions[$report][1])) {
            $report = 'sesiones_realizadas';
        }

        $this->view('reports/index', [
            'title' => 'Reportes',
            'from' => $from, 'to' => $to, 'patient_id' => $patientId, 'professional_id' => $professionalId,
            'report' => $report, 'definitions' => $definitions,
            'summary' => $this->summary($from, $to, $patientId, $professionalId),
            'rows' => $this->rows($report, $from, $to, $patientId, $professionalId),
            'charts' => $this->charts($from, $to),
            'professionals' => Database::all("SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                                               WHERE r.slug IN ('administrador','psicologo') ORDER BY u.name"),
            'patient' => $patientId ? Database::one('SELECT id, first_name, last_name, file_number FROM patients WHERE id = ?', [$patientId]) : null,
        ]);
    }

    public function export(): void
    {
        [$from, $to, $patientId, $professionalId] = $this->filters();
        $report = (string) $this->input('reporte', 'sesiones_realizadas');
        $definitions = $this->reports();
        if (!isset($definitions[$report]) || !can($definitions[$report][1])) {
            $this->notFound();
        }
        $rows = $this->rows($report, $from, $to, $patientId, $professionalId);
        AuditService::log('EXPORTAR', 'reportes', null, "Exportación CSV: $report ($from a $to)");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $report . '_' . $from . '_' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
        if ($rows) {
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $r) {
                fputcsv($out, $r, ';');
            }
        } else {
            fputcsv($out, ['Sin datos en el periodo'], ';');
        }
        fclose($out);
        exit;
    }

    private function filters(): array
    {
        return [
            (string) $this->input('desde', date('Y-m-01')),
            (string) $this->input('hasta', date('Y-m-t')),
            (int) $this->input('patient_id', 0),
            (int) $this->input('professional_id', 0),
        ];
    }

    private function summary(string $from, string $to, int $patientId, int $professionalId): array
    {
        $s = ['patient' => $patientId ?: null, 'professional' => $professionalId ?: null];
        $p = [$from, $to];
        $filter = '';
        if ($patientId) {
            $filter .= ' AND s.patient_id = ' . $patientId;
        }
        if ($professionalId) {
            $filter .= ' AND s.professional_id = ' . $professionalId;
        }

        $sessions = Database::one(
            "SELECT COUNT(*) AS total,
                    SUM(attendance = 'atendido') AS atendidas,
                    SUM(attendance = 'cancelado') AS canceladas,
                    SUM(attendance = 'no_asistio') AS inasistencias
               FROM therapy_sessions s WHERE s.session_date BETWEEN ? AND ?" . $filter, $p
        );
        $payFilter = $patientId ? ' AND patient_id = ' . $patientId : '';
        return [
            'sessions' => (int) $sessions['total'],
            'attended' => (int) $sessions['atendidas'],
            'cancelled' => (int) $sessions['canceladas'],
            'no_show' => (int) $sessions['inasistencias'],
            'attendance_rate' => (int) $sessions['total'] > 0 ? round($sessions['atendidas'] * 100 / $sessions['total']) : 0,
            'new_patients' => (int) Database::value("SELECT COUNT(*) FROM patients WHERE intake_date BETWEEN ? AND ?" . ($patientId ? ' AND id = ' . $patientId : ''), $p),
            'active_patients' => (int) Database::value("SELECT COUNT(*) FROM patients WHERE status = 'activo'"),
            'income' => can('payments.view') ? (float) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pagado' AND paid_at BETWEEN ? AND ?" . $payFilter, $p) : 0,
            'pending' => can('payments.view') ? (float) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pendiente' AND paid_at BETWEEN ? AND ?" . $payFilter, $p) : 0,
            'evaluations' => (int) Database::value('SELECT COUNT(*) FROM psychological_evaluations WHERE start_date BETWEEN ? AND ?', $p),
        ];
    }

    private function rows(string $report, string $from, string $to, int $patientId, int $professionalId): array
    {
        $p = [$from, $to];
        $sesFilter = ($patientId ? ' AND s.patient_id = ' . $patientId : '') . ($professionalId ? ' AND s.professional_id = ' . $professionalId : '');
        $patFilter = $patientId ? ' AND p.id = ' . $patientId : '';

        switch ($report) {
            case 'sesiones_realizadas':
            case 'cancelaciones':
            case 'inasistencias':
                $attendance = ['sesiones_realizadas' => 'atendido', 'cancelaciones' => 'cancelado', 'inasistencias' => 'no_asistio'][$report];
                return Database::all(
                    "SELECT s.session_date AS Fecha, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, p.file_number AS Expediente,
                            s.session_number AS Sesion, s.modality AS Modalidad, u.name AS Profesional, s.fee AS Valor
                       FROM therapy_sessions s JOIN patients p ON p.id = s.patient_id LEFT JOIN users u ON u.id = s.professional_id
                      WHERE s.session_date BETWEEN ? AND ? AND s.attendance = '$attendance' $sesFilter
                      ORDER BY s.session_date DESC", $p
                );
            case 'pacientes_nuevos':
                return Database::all(
                    "SELECT p.intake_date AS Ingreso, p.file_number AS Expediente, CONCAT(p.first_name, ' ', p.last_name) AS Paciente,
                            p.birth_date AS Nacimiento, p.status AS Estado, u.name AS Profesional
                       FROM patients p LEFT JOIN users u ON u.id = p.professional_id
                      WHERE p.intake_date BETWEEN ? AND ? $patFilter ORDER BY p.intake_date DESC", $p
                );
            case 'pacientes_activos':
                return Database::all(
                    "SELECT p.file_number AS Expediente, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, p.sessions_count AS Sesiones,
                            p.last_session_at AS UltimaSesion, u.name AS Profesional
                       FROM patients p LEFT JOIN users u ON u.id = p.professional_id
                      WHERE p.status = 'activo' $patFilter ORDER BY p.last_session_at DESC"
                );
            case 'evaluaciones_realizadas':
                return Database::all(
                    "SELECT e.start_date AS Inicio, e.end_date AS Fin, CONCAT(p.first_name, ' ', p.last_name) AS Paciente,
                            e.areas AS Areas, e.status AS Estado, u.name AS Profesional
                       FROM psychological_evaluations e JOIN patients p ON p.id = e.patient_id LEFT JOIN users u ON u.id = e.professional_id
                      WHERE e.start_date BETWEEN ? AND ?" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY e.start_date DESC', $p
                );
            case 'evaluaciones_pendientes':
                return Database::all(
                    "SELECT e.start_date AS Inicio, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, e.status AS Estado,
                            DATEDIFF(CURDATE(), e.start_date) AS DiasTranscurridos
                       FROM psychological_evaluations e JOIN patients p ON p.id = e.patient_id
                      WHERE e.status IN ('en_proceso','finalizada','informe_pendiente')" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY e.start_date'
                );
            case 'documentos_pendientes':
                return Database::all(
                    "SELECT d.created_at AS Creado, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, d.title AS Documento,
                            d.type AS Tipo, d.status AS Estado
                       FROM documents d JOIN patients p ON p.id = d.patient_id
                      WHERE d.status = 'borrador'" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY d.created_at'
                );
            case 'ingresos':
                return Database::all(
                    "SELECT pay.paid_at AS Fecha, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, pay.concept AS Concepto,
                            pay.method AS Metodo, pay.amount AS Valor
                       FROM payments pay JOIN patients p ON p.id = pay.patient_id
                      WHERE pay.status = 'pagado' AND pay.paid_at BETWEEN ? AND ?" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY pay.paid_at DESC', $p
                );
            case 'pagos_pendientes':
                return Database::all(
                    "SELECT pay.paid_at AS Fecha, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, pay.concept AS Concepto, pay.amount AS Valor,
                            DATEDIFF(CURDATE(), pay.paid_at) AS DiasPendiente
                       FROM payments pay JOIN patients p ON p.id = pay.patient_id
                      WHERE pay.status = 'pendiente'" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY pay.paid_at'
                );
            case 'paquetes_activos':
                return Database::all(
                    "SELECT sp.purchased_at AS Compra, CONCAT(p.first_name, ' ', p.last_name) AS Paciente, sp.name AS Paquete,
                            sp.sessions_total AS Total, sp.sessions_used AS Usadas, sp.sessions_remaining AS Restantes, sp.total_paid AS Pagado
                       FROM session_packages sp JOIN patients p ON p.id = sp.patient_id
                      WHERE sp.status = 'activo'" . ($patientId ? ' AND p.id = ' . $patientId : '') . ' ORDER BY sp.sessions_remaining'
                );
        }
        return [];
    }

    private function charts(string $from, string $to): array
    {
        $monthly = Database::all(
            "SELECT DATE_FORMAT(session_date, '%Y-%m') AS mes,
                    SUM(attendance = 'atendido') AS atendidas,
                    SUM(attendance = 'cancelado') AS canceladas,
                    SUM(attendance = 'no_asistio') AS inasistencias
               FROM therapy_sessions WHERE session_date >= DATE_SUB(?, INTERVAL 5 MONTH) AND session_date <= ?
              GROUP BY mes ORDER BY mes", [$from, $to]
        );
        $income = can('payments.view') ? Database::all(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS mes, SUM(amount) AS total FROM payments
              WHERE status = 'pagado' AND paid_at >= DATE_SUB(?, INTERVAL 5 MONTH) AND paid_at <= ?
              GROUP BY mes ORDER BY mes", [$from, $to]
        ) : [];
        $diagnoses = can('clinical.view') ? Database::all(
            "SELECT diagnosis, COUNT(*) AS n FROM diagnoses WHERE status = 'activo' AND type IN ('confirmado','presuntivo')
              GROUP BY diagnosis ORDER BY n DESC LIMIT 6"
        ) : [];
        return ['monthly' => $monthly, 'income' => $income, 'diagnoses' => $diagnoses];
    }
}
