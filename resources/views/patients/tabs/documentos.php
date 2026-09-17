<div class="card">
  <div class="card-header">
    <h2 class="card-title">Documentos del paciente (<?= count($documents) ?>)</h2>
    <?php if (can('documents.manage')): ?>
      <a class="btn btn-sm btn-primary" href="<?= e(url('documentos/nuevo', ['patient_id' => $pid])) ?>"><i class="bi bi-plus-lg me-1"></i> Nuevo documento</a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Documento</th><th>Tipo</th><th>Estado</th><th class="d-none d-md-table-cell">Versión</th><th class="d-none d-md-table-cell">Creado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($documents as $d): ?>
        <tr>
          <td><a class="cell-title" href="<?= e(url('documentos/' . $d['id'])) ?>"><?= e($d['title']) ?></a>
            <div class="cell-sub"><?= e($d['created_by_name']) ?></div></td>
          <td class="cell-sub"><?= e(label('document_type', $d['type'])) ?></td>
          <td><?= badge('document_status', $d['status']) ?></td>
          <td class="d-none d-md-table-cell cell-sub">v<?= (int) $d['current_version'] ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($d['created_at'])) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-light" href="<?= e(url('documentos/' . $d['id'] . '/imprimir')) ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i></a>
            <a class="btn btn-sm btn-light" href="<?= e(url('documentos/' . $d['id'])) ?>">Abrir</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$documents): ?>
        <tr><td colspan="6"><div class="empty"><i class="bi bi-file-earmark-text"></i>Sin documentos generados.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
