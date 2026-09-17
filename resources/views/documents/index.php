<div class="page-head">
  <div><h1>Documentos clínicos</h1><p class="lead-sm">Informes, certificados y documentos generados desde el expediente.</p></div>
  <div class="d-flex gap-2">
    <?php if (can('templates.manage')): ?><a class="btn btn-light" href="<?= e(url('plantillas')) ?>"><i class="bi bi-layout-text-window me-1"></i> Plantillas</a><?php endif; ?>
    <?php if (can('documents.manage')): ?><a class="btn btn-primary" href="<?= e(url('documentos/nuevo')) ?>"><i class="bi bi-plus-lg me-1"></i> Nuevo documento</a><?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('documentos')) ?>">
    <div class="col-md-4">
      <label class="form-label" for="tipo">Tipo</label>
      <select class="form-select" id="tipo" name="tipo">
        <option value="">Todos</option>
        <?php foreach (options('document_type') as $v => $l): ?><option value="<?= e($v) ?>" <?= $type === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="estado">Estado</label>
      <select class="form-select" id="estado" name="estado">
        <option value="">Todos</option>
        <?php foreach (options('document_status') as $v => $l): ?><option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button></div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Documento</th><th>Paciente</th><th>Tipo</th><th>Estado</th><th class="d-none d-md-table-cell">Creado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($documents as $d): ?>
        <tr>
          <td><a class="cell-title" href="<?= e(url('documentos/' . $d['id'])) ?>"><?= e($d['title']) ?></a>
            <div class="cell-sub">v<?= (int) $d['current_version'] ?> · <?= e($d['created_by_name']) ?></div></td>
          <td><a href="<?= e(url('pacientes/' . $d['patient_id'])) ?>"><?= e($d['first_name'] . ' ' . $d['last_name']) ?></a>
            <div class="cell-sub"><?= e($d['file_number']) ?></div></td>
          <td class="cell-sub"><?= e(label('document_type', $d['type'])) ?></td>
          <td><?= badge('document_status', $d['status']) ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($d['created_at'])) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-light" href="<?= e(url('documentos/' . $d['id'] . '/imprimir')) ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i></a>
            <a class="btn btn-sm btn-light" href="<?= e(url('documentos/' . $d['id'])) ?>">Abrir</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$documents): ?>
        <tr><td colspan="6"><div class="empty"><i class="bi bi-file-earmark-text"></i>Sin documentos.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
