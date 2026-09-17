<div class="page-head">
  <div><h1>Auditoría</h1><p class="lead-sm"><?= (int) $total ?> operaciones registradas en el periodo. Se guardan los campos modificados, nunca el contenido clínico.</p></div>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('auditoria')) ?>">
    <div class="col-md-3"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($from) ?>"></div>
    <div class="col-md-3"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
    <div class="col-md-3"><label class="form-label" for="accion">Acción</label>
      <select class="form-select" id="accion" name="accion">
        <option value="">Todas</option>
        <?php foreach (options('audit_action') as $v => $l): ?><option value="<?= e($v) ?>" <?= $action === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label" for="usuario">Usuario</label>
      <select class="form-select" id="usuario" name="usuario">
        <option value="">Todos</option>
        <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $user_id === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo</th><th>Registro</th><th>Descripción</th><th class="d-none d-lg-table-cell">Campos</th><th class="d-none d-lg-table-cell">IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td class="cell-sub"><?= e(fdate($l['created_at'], true)) ?></td>
          <td class="cell-sub"><?= e($l['user_name'] ?: 'Sistema') ?></td>
          <td><?= badge('audit_action', $l['action']) ?></td>
          <td class="cell-sub"><?= e($l['module']) ?></td>
          <td class="cell-sub"><?= $l['record_id'] ? '#' . (int) $l['record_id'] : '—' ?></td>
          <td class="cell-sub"><?= e($l['description']) ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($l['changed_fields'] ?: '—') ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($l['ip_address'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="8"><div class="empty"><i class="bi bi-clipboard-check"></i>Sin registros con esos filtros.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="card-header justify-content-center">
      <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($i = max(1, $page - 4); $i <= min($pages, $page + 4); $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= e(url('auditoria', ['desde' => $from, 'hasta' => $to, 'accion' => $action, 'usuario' => $user_id ?: null, 'pagina' => $i])) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>
