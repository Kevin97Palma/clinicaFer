<div class="page-head">
  <div><h1>Plantillas de documentos</h1><p class="lead-sm">Base para generar informes y certificados con variables del expediente.</p></div>
  <a class="btn btn-primary" href="<?= e(url('plantillas/nueva')) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva plantilla</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Plantilla</th><th>Tipo</th><th>Estado</th><th class="d-none d-md-table-cell">Última modificación</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $t): ?>
        <tr>
          <td class="cell-title"><?= e($t['name']) ?></td>
          <td class="cell-sub"><?= e(label('document_type', $t['type'])) ?></td>
          <td><?= $t['is_active'] ? '<span class="badge-soft badge-success">Activa</span>' : '<span class="badge-soft badge-neutral">Inactiva</span>' ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($t['updated_at'], true)) ?><?= $t['updated_by_name'] ? ' · ' . e($t['updated_by_name']) : '' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('plantillas/' . $t['id'] . '/editar')) ?>">Editar</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
