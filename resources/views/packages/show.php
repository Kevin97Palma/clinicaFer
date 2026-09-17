<?php $p = $package; ?>
<div class="page-head">
  <div>
    <h1><?= e($p['name']) ?></h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $p['patient_id'], ['tab' => 'pagos'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
      · <?= e($p['file_number']) ?> · comprado el <?= e(fdate($p['purchased_at'])) ?> <?= badge('package_status', $p['status']) ?>
    </p>
  </div>
  <a class="btn btn-light" href="<?= e(url('paquetes')) ?>">Volver</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Consumo</h2></div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="stat-value"><?= (int) $p['sessions_used'] ?> / <?= (int) $p['sessions_total'] ?></span>
          <span class="badge-soft badge-<?= (int) $p['sessions_remaining'] <= 1 ? 'warning' : 'success' ?>"><?= (int) $p['sessions_remaining'] ?> restantes</span>
        </div>
        <div class="pkg-dots mb-3">
          <?php for ($i = 0; $i < (int) $p['sessions_total']; $i++): ?><span class="<?= $i < (int) $p['sessions_used'] ? 'used' : '' ?>"></span><?php endfor; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Movimiento</th><th>Motivo</th><th>Sesión</th><th>Usuario</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $m): ?>
              <tr>
                <td><?= badge('movement_type', $m['type']) ?> <span class="cell-sub"><?= $m['quantity'] > 0 ? '+' : '' ?><?= (int) $m['quantity'] ?></span></td>
                <td class="cell-sub"><?= e($m['reason']) ?></td>
                <td class="cell-sub"><?= $m['session_id'] ? '<a href="' . e(url('sesiones/' . $m['session_id'])) . '">' . e(fdate($m['session_date'])) . '</a>' : '—' ?></td>
                <td class="cell-sub"><?= e($m['created_by_name'] ?: 'Sistema') ?></td>
                <td class="cell-sub"><?= e(fdate($m['created_at'], true)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$movements): ?><tr><td colspan="5" class="text-muted">Sin movimientos registrados.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($payments): ?>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Pagos del paquete</h2></div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Concepto</th><th class="text-end">Valor</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
              <tr><td><?= e(fdate($pay['paid_at'])) ?></td><td><?= e($pay['concept']) ?></td>
                <td class="text-end"><?= e(money($pay['amount'])) ?></td><td><?= badge('payment_status', $pay['status']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Detalle económico</h2></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Precio regular</dt><dd><?= e(money($p['price_regular'])) ?></dd>
          <dt>Descuento</dt><dd><?= e(money($p['discount'])) ?></dd>
          <dt>Total pagado</dt><dd class="fw-semibold"><?= e(money($p['total_paid'])) ?></dd>
          <dt>Valor por sesión</dt><dd><?= e(money((float) $p['total_paid'] / max(1, (int) $p['sessions_total']))) ?></dd>
          <dt>Vence</dt><dd><?= e(fdate($p['expires_at'])) ?></dd>
        </dl>
      </div>
    </div>

    <?php if (can('packages.adjust')): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Corregir consumo</h2></div>
        <form method="post" action="<?= e(url('paquetes/' . $p['id'] . '/ajustar')) ?>" class="card-body needs-validation" novalidate
              data-confirm="¿Corregir manualmente el consumo del paquete? Queda registrado en la auditoría.">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="sessions_used">Sesiones utilizadas *</label>
            <input type="number" class="form-control" id="sessions_used" name="sessions_used" min="0" max="<?= (int) $p['sessions_total'] ?>" value="<?= (int) $p['sessions_used'] ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="reason">Motivo *</label>
            <input class="form-control" id="reason" name="reason" maxlength="255" required>
          </div>
          <button class="btn btn-accent w-100">Aplicar corrección</button>
        </form>
      </div>
    <?php endif; ?>

    <?php if (can('payments.manage')): ?>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Estado</h2></div>
        <form method="post" action="<?= e(url('paquetes/' . $p['id'] . '/estado')) ?>" class="card-body">
          <?= csrf_field() ?>
          <select class="form-select mb-2" name="status"><?= select_options('package_status', $p['status']) ?></select>
          <button class="btn btn-light w-100">Cambiar estado</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
