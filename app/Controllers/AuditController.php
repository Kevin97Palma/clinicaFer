<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class AuditController extends Controller
{
    public function index(): void
    {
        $from = (string) $this->input('desde', date('Y-m-d', strtotime('-30 days')));
        $to = (string) $this->input('hasta', date('Y-m-d'));
        $action = (string) $this->input('accion', '');
        $userId = (int) $this->input('usuario', 0);
        $page = max(1, (int) $this->input('pagina', 1));
        $perPage = 50;

        $where = ['DATE(a.created_at) BETWEEN ? AND ?'];
        $params = [$from, $to];
        if (array_key_exists($action, options('audit_action'))) {
            $where[] = 'a.action = ?';
            $params[] = $action;
        }
        if ($userId) {
            $where[] = 'a.user_id = ?';
            $params[] = $userId;
        }
        $sql = implode(' AND ', $where);
        $total = (int) Database::value("SELECT COUNT(*) FROM audit_logs a WHERE $sql", $params);

        $this->view('audit/index', [
            'title' => 'Auditoría',
            'logs' => Database::all(
                "SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                  WHERE $sql ORDER BY a.created_at DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params
            ),
            'users' => Database::all('SELECT id, name FROM users ORDER BY name'),
            'from' => $from, 'to' => $to, 'action' => $action, 'user_id' => $userId,
            'page' => $page, 'pages' => (int) ceil($total / $perPage), 'total' => $total,
        ]);
    }
}
