<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use App\Services\BackupService;

final class BackupController extends Controller
{
    public function index(): void
    {
        $this->view('backups/index', [
            'title' => 'Respaldos',
            'backups' => Database::all(
                'SELECT b.*, u.name AS user_name FROM backup_logs b LEFT JOIN users u ON u.id = b.user_id
                  ORDER BY b.created_at DESC LIMIT 50'
            ),
        ]);
    }

    public function store(): void
    {
        $result = BackupService::run('manual');
        if ($result['ok']) {
            flash('success', 'Respaldo generado (' . round($result['size'] / 1024) . ' KB). Descárguelo y guárdelo en un lugar seguro.');
        } else {
            flash('danger', $result['message']);
        }
        redirect('/respaldos');
    }

    public function download(int $id): void
    {
        $backup = Database::one("SELECT * FROM backup_logs WHERE id = ? AND result = 'exito'", [$id]) ?: $this->notFound();
        $path = BackupService::dir() . '/' . $backup['file_name'];
        if (!is_file($path)) {
            flash('danger', 'El archivo de respaldo ya no está disponible en el servidor.');
            redirect('/respaldos');
        }
        AuditService::log('EXPORTAR', 'respaldos', $id, 'Descarga de respaldo ' . $backup['file_name']);
        header('Content-Type: application/gzip');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($backup['file_name']) . '"');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
