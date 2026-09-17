<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AlertService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        AlertService::runIfDue();
        $today = date('Y-m-d');

        $stats = [
            'active_patients' => (int) Database::value("SELECT COUNT(*) FROM patients WHERE status = 'activo'"),
            'sessions_today' => (int) Database::value('SELECT COUNT(*) FROM appointments WHERE DATE(starts_at) = ?', [$today]),
            'evaluations_pending' => (int) Database::value("SELECT COUNT(*) FROM psychological_evaluations WHERE status = 'en_proceso'"),
            'reports_pending' => (int) Database::value("SELECT (SELECT COUNT(*) FROM psychological_evaluations WHERE status IN ('finalizada','informe_pendiente'))
                                                             + (SELECT COUNT(*) FROM documents WHERE status = 'borrador')"),
            'payments_pending' => (float) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pendiente'"),
            'income_month' => (float) Database::value("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pagado' AND paid_at >= ?", [date('Y-m-01')]),
            'new_patients_month' => (int) Database::value('SELECT COUNT(*) FROM patients WHERE intake_date >= ?', [date('Y-m-01')]),
        ];

        $appointments = Database::all(
            "SELECT a.*, p.first_name, p.last_name, p.file_number, p.birth_date, u.name AS professional_name,
                    s.id AS session_id
               FROM appointments a
               JOIN patients p ON p.id = a.patient_id
               LEFT JOIN users u ON u.id = a.professional_id
               LEFT JOIN therapy_sessions s ON s.appointment_id = a.id
              WHERE DATE(a.starts_at) = ? ORDER BY a.starts_at",
            [$today]
        );

        $week = Database::all(
            "SELECT DATE(starts_at) AS d, COUNT(*) AS n FROM appointments
              WHERE starts_at >= CURDATE() AND starts_at < CURDATE() + INTERVAL 7 DAY
                AND status IN ('programada','confirmada') GROUP BY DATE(starts_at) ORDER BY d"
        );

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'appointments' => $appointments,
            'week' => $week,
            'alerts' => can('alerts.view') ? AlertService::openForUser(12) : [],
            'recent_sessions' => can('clinical.view') ? Database::all(
                "SELECT s.*, p.first_name, p.last_name FROM therapy_sessions s JOIN patients p ON p.id = s.patient_id
                  ORDER BY s.session_date DESC, s.id DESC LIMIT 6"
            ) : [],
        ]);
    }
}
