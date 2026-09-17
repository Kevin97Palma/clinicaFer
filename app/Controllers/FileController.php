<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Services\AuditService;
use App\Services\FileStorage;
use RuntimeException;

final class FileController extends Controller
{
    public function store(int $patientId): void
    {
        Patient::find($patientId) ?: $this->notFound();
        $this->validate($_POST, ['category' => 'required|in_catalog:file_category', 'description' => 'max:255']);
        try {
            FileStorage::storePatientFile($patientId, $_FILES['file'] ?? [], (string) $_POST['category'], trim((string) ($_POST['description'] ?? '')) ?: null);
            flash('success', 'Archivo adjuntado al expediente.');
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
        }
        redirect('/pacientes/' . $patientId . '?tab=archivos');
    }

    public function download(int $id): void
    {
        $file = Database::one('SELECT * FROM patient_files WHERE id = ? AND deleted_at IS NULL', [$id]) ?: $this->notFound();
        AuditService::log('DESCARGAR_DOCUMENTO', 'archivos', $id, 'Descarga de archivo del paciente ' . $file['patient_id']);
        $inline = in_array($file['mime_type'], ['application/pdf', 'image/jpeg', 'image/png'], true);
        FileStorage::send($file['stored_name'], $file['mime_type'], $file['original_name'], $inline);
    }

    /** Baja lógica: el archivo deja de listarse pero la operación queda auditada. */
    public function delete(int $id): void
    {
        $file = Database::one('SELECT * FROM patient_files WHERE id = ?', [$id]) ?: $this->notFound();
        Database::query('UPDATE patient_files SET deleted_at = NOW() WHERE id = ?', [$id]);
        AuditService::log('ELIMINAR', 'archivos', $id, 'Archivo eliminado del expediente ' . $file['patient_id'] . ' por usuario ' . Auth::id());
        flash('success', 'Archivo eliminado del expediente.');
        redirect('/pacientes/' . $file['patient_id'] . '?tab=archivos');
    }
}
