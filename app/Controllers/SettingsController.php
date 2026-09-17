<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use App\Services\FileStorage;
use App\Services\SettingsService;
use RuntimeException;

final class SettingsController extends Controller
{
    private const KEYS = ['brand_name', 'professional_name', 'professional_title', 'registration_number', 'phone', 'email',
        'address', 'session_fee', 'session_duration', 'review_every_sessions', 'inactivity_days', 'currency', 'timezone',
        'consume_package_on_no_show', 'consume_package_on_cancel', 'file_number_prefix'];

    public function index(): void
    {
        $this->view('settings/index', ['title' => 'Configuración', 'settings' => SettingsService::all()]);
    }

    public function update(): void
    {
        $this->validate($_POST, [
            'brand_name' => 'required|max:120', 'professional_name' => 'max:120', 'professional_title' => 'max:120',
            'registration_number' => 'max:60', 'email' => 'email|max:150', 'phone' => 'max:30', 'address' => 'max:255',
            'session_fee' => 'required|numeric|min:0', 'session_duration' => 'required|int|min:10|maxnum:480',
            'review_every_sessions' => 'required|int|min:1|maxnum:100', 'inactivity_days' => 'required|int|min:1|maxnum:365',
            'currency' => 'required|in_catalog:currency', 'file_number_prefix' => 'required|max:10',
        ]);
        foreach (self::KEYS as $key) {
            if (in_array($key, ['consume_package_on_no_show', 'consume_package_on_cancel'], true)) {
                SettingsService::set($key, isset($_POST[$key]) ? '1' : '0');
                continue;
            }
            if (array_key_exists($key, $_POST)) {
                $value = trim((string) $_POST[$key]);
                if ($key === 'timezone' && !in_array($value, timezone_identifiers_list(), true)) {
                    continue;
                }
                SettingsService::set($key, $value);
            }
        }

        foreach (['logo' => 'logo_file', 'signature' => 'signature_file'] as $field => $key) {
            if (!empty($_FILES[$field]['name'])) {
                try {
                    $meta = FileStorage::store($_FILES[$field], 'branding', ['png', 'jpg', 'jpeg']);
                    SettingsService::set($key, $meta['stored_name']);
                } catch (RuntimeException $e) {
                    flash('danger', ucfirst($field) . ': ' . $e->getMessage());
                }
            }
            if (!empty($_POST['remove_' . $field])) {
                SettingsService::set($key, '');
            }
        }

        AuditService::log('EDITAR', 'configuracion', null, 'Configuración general actualizada', array_keys(array_intersect_key($_POST, array_flip(self::KEYS))));
        flash('success', 'Configuración guardada.');
        redirect('/configuracion');
    }

    // ── Roles y permisos ──────────────────────────────────────
    public function roles(): void
    {
        $this->view('settings/roles', [
            'title' => 'Roles y permisos',
            'roles' => Database::all("SELECT * FROM roles ORDER BY id"),
            'permissions' => Database::all('SELECT * FROM permissions ORDER BY module, id'),
            'assigned' => Database::all('SELECT role_id, permission_id FROM role_permissions'),
        ]);
    }

    public function updateRoles(): void
    {
        $roles = array_column(Database::all("SELECT id FROM roles WHERE slug <> 'administrador'"), 'id');
        $permissions = array_column(Database::all('SELECT id FROM permissions'), 'id');
        $selected = (array) ($_POST['permissions'] ?? []);

        Database::transaction(function () use ($roles, $permissions, $selected) {
            foreach ($roles as $roleId) {
                Database::query('DELETE FROM role_permissions WHERE role_id = ?', [(int) $roleId]);
                foreach ((array) ($selected[$roleId] ?? []) as $permId) {
                    if (in_array((int) $permId, array_map('intval', $permissions), true)) {
                        Database::insert('role_permissions', ['role_id' => (int) $roleId, 'permission_id' => (int) $permId]);
                    }
                }
            }
        });
        AuditService::log('EDITAR', 'configuracion', null, 'Permisos por rol actualizados', ['role_permissions']);
        flash('success', 'Permisos actualizados. El rol Administrador siempre conserva acceso completo.');
        redirect('/configuracion/roles');
    }

    // ── Catálogo de instrumentos ──────────────────────────────
    public function instruments(): void
    {
        $this->view('settings/instruments', [
            'title' => 'Catálogo de instrumentos',
            'instruments' => Database::all(
                'SELECT ic.*, (SELECT COUNT(*) FROM evaluation_instruments ei WHERE ei.instrument_id = ic.id) AS uses
                   FROM instrument_catalog ic ORDER BY ic.name'
            ),
        ]);
    }

    public function storeInstrument(): void
    {
        $this->validate($_POST, ['name' => 'required|max:120', 'description' => 'max:255']);
        if (Database::value('SELECT id FROM instrument_catalog WHERE name = ?', [trim((string) $_POST['name'])])) {
            flash('warning', 'Ya existe un instrumento con ese nombre.');
            redirect('/configuracion/instrumentos');
        }
        $id = Database::insert('instrument_catalog', [
            'name' => trim((string) $_POST['name']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'is_active' => 1,
        ]);
        AuditService::log('CREAR', 'configuracion', $id, 'Instrumento agregado al catálogo');
        flash('success', 'Instrumento agregado.');
        redirect('/configuracion/instrumentos');
    }

    public function updateInstrument(int $id): void
    {
        Database::one('SELECT id FROM instrument_catalog WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['name' => 'required|max:120', 'description' => 'max:255']);
        Database::update('instrument_catalog', [
            'name' => trim((string) $_POST['name']),
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ], $id);
        AuditService::log('EDITAR', 'configuracion', $id, 'Instrumento actualizado');
        flash('success', 'Instrumento actualizado.');
        redirect('/configuracion/instrumentos');
    }

    // ── Logo y firma (servidos desde fuera del directorio público) ──
    public function logo(): void
    {
        $this->sendBranding('logo_file');
    }

    public function signature(): void
    {
        $this->sendBranding('signature_file');
    }

    private function sendBranding(string $key): void
    {
        $stored = SettingsService::get($key, '');
        if (!$stored) {
            http_response_code(404);
            exit;
        }
        $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
        FileStorage::send($stored, $ext === 'png' ? 'image/png' : 'image/jpeg', $key . '.' . $ext, true);
    }
}
