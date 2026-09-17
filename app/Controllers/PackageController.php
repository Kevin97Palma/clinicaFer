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

final class PackageController extends Controller
{
    public function index(): void
    {
        $status = (string) $this->input('estado', '');
        $where = '1 = 1';
        $params = [];
        if (array_key_exists($status, options('package_status'))) {
            $where = 'sp.status = ?';
            $params[] = $status;
        }
        $this->view('packages/index', [
            'title' => 'Paquetes de sesiones',
            'status' => $status,
            'packages' => Database::all(
                "SELECT sp.*, p.first_name, p.last_name, p.file_number FROM session_packages sp
                   JOIN patients p ON p.id = sp.patient_id WHERE $where ORDER BY sp.status = 'activo' DESC, sp.purchased_at DESC", $params
            ),
        ]);
    }

    public function store(): void
    {
        $this->validate($_POST, ['patient_id' => 'required|int', 'name' => 'required|max:120', 'purchased_at' => 'required|date',
            'sessions_total' => 'required|int|min:1|maxnum:200', 'price_regular' => 'required|numeric|min:0',
            'total_paid' => 'required|numeric|min:0', 'discount' => 'numeric|min:0', 'expires_at' => 'date']);
        $patientId = (int) $_POST['patient_id'];
        Patient::find($patientId) ?: $this->notFound();

        $id = Database::transaction(function () use ($patientId) {
            $pkgId = Database::insert('session_packages', [
                'patient_id' => $patientId,
                'name' => trim((string) $_POST['name']),
                'purchased_at' => $_POST['purchased_at'],
                'expires_at' => trim((string) ($_POST['expires_at'] ?? '')) ?: null,
                'sessions_total' => (int) $_POST['sessions_total'],
                'price_regular' => (float) $_POST['price_regular'],
                'discount' => (float) ($_POST['discount'] ?? 0),
                'total_paid' => (float) $_POST['total_paid'],
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                'created_by' => Auth::id(),
            ]);
            if (!empty($_POST['register_payment'])) {
                Database::insert('payments', [
                    'patient_id' => $patientId, 'package_id' => $pkgId, 'paid_at' => $_POST['purchased_at'],
                    'concept' => 'Paquete: ' . trim((string) $_POST['name']), 'amount' => (float) $_POST['total_paid'],
                    'method' => 'efectivo', 'status' => 'pagado', 'created_by' => Auth::id(),
                ]);
            }
            return $pkgId;
        });

        AuditService::log('CREAR', 'paquetes', $id, 'Paquete creado para el paciente ' . $patientId);
        flash('success', 'Paquete creado.');
        redirect_back('/paquetes');
    }

    public function show(int $id): void
    {
        $pkg = Database::one(
            'SELECT sp.*, p.first_name, p.last_name, p.file_number FROM session_packages sp
               JOIN patients p ON p.id = sp.patient_id WHERE sp.id = ?', [$id]
        ) ?: $this->notFound();

        $this->view('packages/show', [
            'title' => 'Paquete de sesiones',
            'package' => $pkg,
            'movements' => Database::all(
                'SELECT m.*, u.name AS created_by_name, s.session_date FROM package_movements m
                   LEFT JOIN users u ON u.id = m.created_by LEFT JOIN therapy_sessions s ON s.id = m.session_id
                  WHERE m.package_id = ? ORDER BY m.created_at DESC, m.id DESC', [$id]
            ),
            'sessions' => Database::all('SELECT * FROM therapy_sessions WHERE package_id = ? ORDER BY session_date', [$id]),
            'payments' => Database::all('SELECT * FROM payments WHERE package_id = ? ORDER BY paid_at', [$id]),
        ]);
    }

    /** Corrección manual del consumo (solo administrador), con motivo y auditoría. */
    public function adjust(int $id): void
    {
        $pkg = Database::one('SELECT * FROM session_packages WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['sessions_used' => 'required|int|min:0', 'reason' => 'required|max:255']);
        $used = (int) $_POST['sessions_used'];
        if ($used > (int) $pkg['sessions_total']) {
            flash('danger', 'El consumo no puede superar el total de sesiones del paquete.');
            redirect('/paquetes/' . $id);
        }
        PackageService::adjust($id, $used, trim((string) $_POST['reason']));
        flash('success', 'Consumo corregido. El ajuste quedó registrado en los movimientos y en la auditoría.');
        redirect('/paquetes/' . $id);
    }

    public function status(int $id): void
    {
        $pkg = Database::one('SELECT * FROM session_packages WHERE id = ?', [$id]) ?: $this->notFound();
        $status = (string) $this->input('status', '');
        if (!array_key_exists($status, options('package_status'))) {
            flash('danger', 'Estado no válido.');
            redirect('/paquetes/' . $id);
        }
        Database::query('UPDATE session_packages SET status = ?, updated_by = ? WHERE id = ?', [$status, Auth::id(), $id]);
        if ($status !== 'activo') {
            AlertService::resolve('PAQUETE_POR_FINALIZAR', 'package', $id);
        }
        AuditService::log('CAMBIAR_ESTADO', 'paquetes', $id, 'Paquete: ' . $pkg['status'] . ' → ' . $status);
        flash('success', 'Paquete actualizado.');
        redirect('/paquetes/' . $id);
    }
}
