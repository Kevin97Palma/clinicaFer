<div class="page-head">
  <div><h1>Pagos</h1><p class="lead-sm">Registro financiero: pagos, pendientes y paquetes activos.</p></div>
  <div class="d-flex gap-2">
    <a class="btn btn-light" href="<?= e(url('pagos/desglose')) ?>"><i class="bi bi-receipt me-1"></i> Desglose de sesiones</a>
    <a class="btn btn-light" href="<?= e(url('paquetes')) ?>"><i class="bi bi-box-seam me-1"></i> Paquetes</a>
    <?php if (can('payments.manage')): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-new-payment"><i class="bi bi-plus-lg me-1"></i> Nuevo pago</button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card stat"><div class="stat-icon tone-success"><i class="bi bi-cash-stack"></i></div>
    <div><div class="stat-value"><?= e(money($totals['pagado'])) ?></div><div class="stat-label">Cobrado en el periodo</div></div></div></div>
  <div class="col-md-4"><div class="card stat"><div class="stat-icon tone-warning"><i class="bi bi-hourglass-split"></i></div>
    <div><div class="stat-value"><?= e(money($totals['pendiente'])) ?></div><div class="stat-label">Pendiente</div></div></div></div>
  <div class="col-md-4"><div class="card stat"><div class="stat-icon tone-neutral"><i class="bi bi-x-circle"></i></div>
    <div><div class="stat-value"><?= e(money($totals['anulado'])) ?></div><div class="stat-label">Anulado</div></div></div></div>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('pagos')) ?>">
    <div class="col-md-3"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($from) ?>"></div>
    <div class="col-md-3"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
    <div class="col-md-3"><label class="form-label" for="estado">Estado</label>
      <select class="form-select" id="estado" name="estado">
        <option value="">Todos</option>
        <?php foreach (options('payment_status') as $v => $l): ?><option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button></div>
  </form>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Fecha</th><th>Paciente</th><th>Concepto</th><th class="d-none d-md-table-cell">Método</th><th class="text-end">Valor</th><th>Estado</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="cell-sub"><?= e(fdate($p['paid_at'])) ?></td>
              <td><a class="cell-title" href="<?= e(url('pacientes/' . $p['patient_id'], ['tab' => 'pagos'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
                <div class="cell-sub"><?= e($p['file_number']) ?></div></td>
              <td><?= e($p['concept']) ?><?php if ($p['void_reason']): ?><div class="cell-sub text-danger">Anulado: <?= e($p['void_reason']) ?></div><?php endif; ?></td>
              <td class="d-none d-md-table-cell cell-sub"><?= e(label('payment_method', $p['method'])) ?></td>
              <td class="text-end fw-semibold"><?= e(money($p['amount'])) ?></td>
              <td><?= badge('payment_status', $p['status']) ?></td>
              <td class="text-end">
                <?php if ($p['status'] === 'pendiente' && can('payments.manage')): ?>
                  <form method="post" action="<?= e(url('pagos/' . $p['id'] . '/cobrar')) ?>" class="d-inline"><?= csrf_field() ?>
                    <button class="btn btn-sm btn-soft">Cobrar</button></form>
                <?php endif; ?>
                <?php if ($p['status'] !== 'anulado' && can('payments.void')): ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-void"
                          data-action="<?= e(url('pagos/' . $p['id'] . '/anular')) ?>"><i class="bi bi-x-circle"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$payments): ?>
            <tr><td colspan="7"><div class="empty"><i class="bi bi-wallet2"></i>Sin pagos en el periodo.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card">
      <div class="card-header"><h2 class="card-title">Paquetes activos</h2>
        <a class="btn btn-sm btn-light" href="<?= e(url('paquetes')) ?>">Ver todos</a></div>
      <div class="card-body">
        <?php foreach (array_slice($packages, 0, 8) as $pk): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <a class="cell-title" href="<?= e(url('paquetes/' . $pk['id'])) ?>"><?= e($pk['first_name'] . ' ' . $pk['last_name']) ?></a>
              <span class="badge-soft badge-<?= (int) $pk['sessions_remaining'] <= 1 ? 'warning' : 'neutral' ?>"><?= (int) $pk['sessions_remaining'] ?> restantes</span>
            </div>
            <div class="pkg-dots mt-1">
              <?php for ($i = 0; $i < (int) $pk['sessions_total']; $i++): ?><span class="<?= $i < (int) $pk['sessions_used'] ? 'used' : '' ?>"></span><?php endfor; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$packages): ?><div class="empty py-3"><i class="bi bi-box-seam"></i>Sin paquetes activos.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (can('payments.manage')): ?>
<div class="modal fade" id="modal-new-payment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pagos')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nuevo pago</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12" data-patient-picker>
            <label class="form-label" for="np-patient">Paciente *</label>
            <input type="hidden" name="patient_id" required>
            <div class="position-relative">
              <input type="search" class="form-control" id="np-patient" placeholder="Buscar paciente" autocomplete="off">
              <div class="search-results" hidden></div>
            </div>
          </div>
          <div class="col-6"><label class="form-label" for="np-date">Fecha *</label>
            <input type="date" class="form-control" id="np-date" name="paid_at" value="<?= date('Y-m-d') ?>" required></div>
          <div class="col-6"><label class="form-label" for="np-amount">Valor *</label>
            <input type="number" step="0.01" min="0" class="form-control" id="np-amount" name="amount" value="<?= e(number_format((float) setting('session_fee', 0), 2, '.', '')) ?>" required></div>
          <div class="col-12"><label class="form-label" for="np-concept">Concepto *</label>
            <input class="form-control" id="np-concept" name="concept" value="Sesión terapéutica" maxlength="200" required></div>
          <div class="col-6"><label class="form-label" for="np-method">Método</label>
            <select class="form-select" id="np-method" name="method"><?= select_options('payment_method', 'efectivo') ?></select></div>
          <div class="col-6"><label class="form-label" for="np-status">Estado</label>
            <select class="form-select" id="np-status" name="status"><?= select_options('payment_status', 'pagado') ?></select></div>
          <div class="col-12"><label class="form-label" for="np-ref">Referencia</label>
            <input class="form-control" id="np-ref" name="reference" maxlength="100"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('payments.void')): ?>
<div class="modal fade" id="modal-void" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="post" action="" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Anular pago</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <p class="cell-sub">El pago se conserva con estado anulado y queda en auditoría.</p>
          <label class="form-label" for="vd-reason">Motivo *</label>
          <input class="form-control" id="vd-reason" name="void_reason" maxlength="255" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-danger">Anular</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
