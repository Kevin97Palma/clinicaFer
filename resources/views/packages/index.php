<div class="page-head">
  <div><h1>Paquetes de sesiones</h1><p class="lead-sm">Control de sesiones compradas, utilizadas y restantes.</p></div>
  <form method="get" action="<?= e(url('paquetes')) ?>">
    <select class="form-select" name="estado" onchange="this.form.submit()" aria-label="Filtrar por estado">
      <option value="">Todos los estados</option>
      <?php foreach (options('package_status') as $v => $l): ?><option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Paciente</th><th>Paquete</th><th>Uso</th><th class="d-none d-md-table-cell">Compra</th><th class="text-end">Total</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($packages as $p): ?>
        <tr>
          <td><a class="cell-title" href="<?= e(url('pacientes/' . $p['patient_id'], ['tab' => 'pagos'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
            <div class="cell-sub"><?= e($p['file_number']) ?></div></td>
          <td><?= e($p['name']) ?></td>
          <td style="min-width:150px">
            <div class="d-flex justify-content-between small"><span><?= (int) $p['sessions_used'] ?>/<?= (int) $p['sessions_total'] ?></span>
              <span class="<?= (int) $p['sessions_remaining'] <= 1 && $p['status'] === 'activo' ? 'text-warning fw-semibold' : 'cell-sub' ?>"><?= (int) $p['sessions_remaining'] ?> restantes</span></div>
            <div class="progress mt-1"><div class="progress-bar" style="width: <?= (int) round($p['sessions_used'] * 100 / max(1, (int) $p['sessions_total'])) ?>%"></div></div>
          </td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($p['purchased_at'])) ?></td>
          <td class="text-end"><?= e(money($p['total_paid'])) ?>
            <?php if ((float) $p['discount'] > 0): ?><div class="cell-sub">desc. <?= e(money($p['discount'])) ?></div><?php endif; ?></td>
          <td><?= badge('package_status', $p['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('paquetes/' . $p['id'])) ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$packages): ?>
        <tr><td colspan="7"><div class="empty"><i class="bi bi-box-seam"></i>Sin paquetes registrados.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
