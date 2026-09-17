<div class="page-head">
  <div><h1>Planes terapéuticos</h1><p class="lead-sm">Objetivos por área y su cumplimiento administrativo.</p></div>
  <form method="get" action="<?= e(url('planes')) ?>">
    <select class="form-select" name="estado" onchange="this.form.submit()" aria-label="Filtrar por estado">
      <option value="">Todos</option>
      <?php foreach (options('plan_status') as $v => $l): ?><option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Paciente</th><th>Inicio</th><th>Revisión</th><th>Objetivo general</th><th>Avance</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($plans as $p): $pct = (int) $p['total'] ? (int) round($p['achieved'] * 100 / $p['total']) : 0; ?>
        <tr>
          <td><a class="cell-title" href="<?= e(url('pacientes/' . $p['patient_id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
            <div class="cell-sub"><?= e($p['file_number']) ?></div></td>
          <td class="cell-sub"><?= e(fdate($p['start_date'])) ?></td>
          <td class="cell-sub <?= $p['review_date'] && $p['review_date'] <= date('Y-m-d') && $p['status'] === 'activo' ? 'text-danger fw-semibold' : '' ?>"><?= e(fdate($p['review_date'])) ?></td>
          <td style="max-width:320px"><?= e(mb_strimwidth((string) $p['general_objective'], 0, 110, '…')) ?></td>
          <td style="min-width:140px">
            <div class="progress"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
            <div class="cell-sub"><?= (int) $p['achieved'] ?>/<?= (int) $p['total'] ?> objetivos</div>
          </td>
          <td><?= badge('plan_status', $p['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('planes/' . $p['id'])) ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$plans): ?>
        <tr><td colspan="7"><div class="empty"><i class="bi bi-bullseye"></i>No hay planes con ese estado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
