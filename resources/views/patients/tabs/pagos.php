<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Pagos</h2>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-light" href="<?= e(url('pagos/desglose', ['patient_id' => $pid])) ?>"><i class="bi bi-receipt me-1"></i> Desglose</a>
          <?php if (can('payments.manage')): ?>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-payment" data-reset
                    data-fill='<?= e(json_encode(['patient_id' => $pid])) ?>'><i class="bi bi-plus-lg me-1"></i> Registrar pago</button>
          <?php endif; ?>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th class="text-end">Valor</th><th>Estado</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="cell-sub"><?= e(fdate($p['paid_at'])) ?></td>
              <td><span class="cell-title"><?= e($p['concept']) ?></span><?php if ($p['reference']): ?><div class="cell-sub">Ref. <?= e($p['reference']) ?></div><?php endif; ?></td>
              <td class="cell-sub"><?= e(label('payment_method', $p['method'])) ?></td>
              <td class="text-end fw-semibold"><?= e(money($p['amount'])) ?></td>
              <td><?= badge('payment_status', $p['status']) ?></td>
              <td class="text-end">
                <?php if ($p['status'] === 'pendiente' && can('payments.manage')): ?>
                  <form method="post" action="<?= e(url('pagos/' . $p['id'] . '/cobrar')) ?>" class="d-inline"><?= csrf_field() ?>
                    <button class="btn btn-sm btn-soft">Marcar pagado</button>
                  </form>
                <?php endif; ?>
                <?php if ($p['status'] !== 'anulado' && can('payments.void')): ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-void"
                          data-action="<?= e(url('pagos/' . $p['id'] . '/anular')) ?>" title="Anular"><i class="bi bi-x-circle"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$payments): ?>
            <tr><td colspan="6"><div class="empty"><i class="bi bi-wallet2"></i>Sin pagos registrados.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Paquetes de sesiones</h2>
        <?php if (can('payments.manage')): ?>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-package" data-reset
                  data-fill='<?= e(json_encode(['patient_id' => $pid])) ?>'><i class="bi bi-plus-lg me-1"></i> Nuevo paquete</button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($packages as $pk): ?>
          <div class="clinical-box mb-3" style="white-space:normal">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <a class="cell-title" href="<?= e(url('paquetes/' . $pk['id'])) ?>"><?= e($pk['name']) ?></a>
                <div class="cell-sub"><?= e(fdate($pk['purchased_at'])) ?> · <?= e(money($pk['total_paid'])) ?>
                  <?php if ((float) $pk['discount'] > 0): ?>· descuento <?= e(money($pk['discount'])) ?><?php endif; ?></div>
              </div>
              <?= badge('package_status', $pk['status']) ?>
            </div>
            <div class="d-flex justify-content-between mt-2 small">
              <span><strong><?= (int) $pk['sessions_used'] ?> / <?= (int) $pk['sessions_total'] ?></strong> utilizadas</span>
              <span><?= (int) $pk['sessions_remaining'] ?> restantes</span>
            </div>
            <div class="pkg-dots mt-1">
              <?php for ($i = 0; $i < (int) $pk['sessions_total']; $i++): ?>
                <span class="<?= $i < (int) $pk['sessions_used'] ? 'used' : '' ?>"></span>
              <?php endfor; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$packages): ?>
          <div class="empty py-3"><i class="bi bi-box-seam"></i>Sin paquetes registrados.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
