<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use App\Services\DocumentService;

final class TemplateController extends Controller
{
    public function index(): void
    {
        $this->view('templates/index', [
            'title' => 'Plantillas de documentos',
            'templates' => Database::all('SELECT t.*, u.name AS updated_by_name FROM document_templates t
                                           LEFT JOIN users u ON u.id = t.updated_by ORDER BY t.type, t.name'),
        ]);
    }

    public function create(): void
    {
        $this->view('templates/form', ['title' => 'Nueva plantilla', 'template' => null]);
    }

    public function store(): void
    {
        $this->validate($_POST, ['name' => 'required|max:150', 'type' => 'required|in_catalog:document_type']);
        $id = Database::insert('document_templates', [
            'name' => trim((string) $_POST['name']),
            'type' => $_POST['type'],
            'content' => DocumentService::sanitize((string) ($_POST['content'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_by' => Auth::id(),
        ]);
        AuditService::log('CREAR', 'plantillas', $id, 'Plantilla creada: ' . $_POST['name']);
        flash('success', 'Plantilla creada.');
        redirect('/plantillas');
    }

    public function edit(int $id): void
    {
        $t = Database::one('SELECT * FROM document_templates WHERE id = ?', [$id]) ?: $this->notFound();
        $this->view('templates/form', ['title' => 'Editar plantilla', 'template' => $t]);
    }

    public function update(int $id): void
    {
        $t = Database::one('SELECT * FROM document_templates WHERE id = ?', [$id]) ?: $this->notFound();
        $this->validate($_POST, ['name' => 'required|max:150', 'type' => 'required|in_catalog:document_type']);
        Database::update('document_templates', [
            'name' => trim((string) $_POST['name']),
            'type' => $_POST['type'],
            'content' => DocumentService::sanitize((string) ($_POST['content'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_by' => Auth::id(),
        ], $id);
        AuditService::log('EDITAR', 'plantillas', $id, 'Plantilla actualizada: ' . $t['name'], ['name', 'type', 'content', 'is_active']);
        flash('success', 'Plantilla actualizada. Los documentos ya emitidos no cambian.');
        redirect('/plantillas');
    }
}
