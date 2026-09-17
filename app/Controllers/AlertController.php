<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AlertService;

final class AlertController extends Controller
{
    public function index(): void
    {
        AlertService::runIfDue(10);
        $status = (string) $this->input('estado', '');
        $type = (string) $this->input('tipo', '');

        $where = ['1 = 1'];
        $params = [];
        if (array_key_exists($status, options('alert_status'))) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        } else {
            $where[] = "a.status IN ('pendiente','leida')";
        }
        if (array_key_exists($type, options('alert_type'))) {
            $where[] = 'a.type = ?';
            $params[] = $type;
        }
        $sql = implode(' AND ', $where);

        $this->view('alerts/index', [
            'title' => 'Alertas',
            'alerts' => Database::all(
                "SELECT a.*, p.first_name, p.last_name, p.file_number FROM alerts a
                   LEFT JOIN patients p ON p.id = a.patient_id
                  WHERE $sql ORDER BY a.status = 'pendiente' DESC, a.created_at DESC LIMIT 200", $params
            ),
            'counts' => Database::all("SELECT type, COUNT(*) AS n FROM alerts WHERE status IN ('pendiente','leida') GROUP BY type"),
            'status' => $status, 'type' => $type,
        ]);
    }

    public function status(int $id): void
    {
        $alert = Database::one('SELECT * FROM alerts WHERE id = ?', [$id]) ?: $this->notFound();
        $status = (string) $this->input('status', '');
        if (!array_key_exists($status, options('alert_status'))) {
            $this->json(['success' => false, 'message' => 'Estado no válido.'], 422);
        }
        Database::query(
            'UPDATE alerts SET status = ?, resolved_at = IF(? IN ("resuelta","descartada"), NOW(), NULL) WHERE id = ?',
            [$status, $status, $id]
        );
        if (is_ajax()) {
            $this->json(['success' => true, 'message' => 'Alerta actualizada.']);
        }
        flash('success', 'Alerta marcada como ' . mb_strtolower(label('alert_status', $status)) . '.');
        redirect_back('/alertas');
    }

    public function recalculate(): void
    {
        AlertService::runIfDue(0, true);
        flash('success', 'Alertas recalculadas según las reglas del sistema.');
        redirect('/alertas');
    }
}
