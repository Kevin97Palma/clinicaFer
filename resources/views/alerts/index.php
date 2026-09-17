<?php
$byType = [];
foreach ($counts as $c) {
    $byType[$c['type']] = (int) $c['n'];
}
$icons = ['INACTIVIDAD_PACIENTE' => 'hourglass-split', 'PAQUETE_POR_FINALIZAR' => 'box-seam', 'EVALUACION_PENDIENTE' => 'clipboard2-pulse',
    'INFORME_PENDIENTE' => 'file-earmark-text', 'REVISION_TERAPEUTICA' => 'graph-up', 'PAGO_PENDIENTE' => 'cash-coin', 'OTRA' => 'bell'];
$tones = ['INACTIVIDAD_PACIENTE' => 'warning', 'PAQUETE_POR_FINALIZAR' => 'accent', 'EVALUACION_PENDIENTE' => 'info',
    'INFORME_PENDIENTE' => 'primary', 'REVISION_TERAPEUTICA' => 'success', 'PAGO_PENDIENTE' => 'danger', 'OTRA' => 'info'];
?>
<div class="page-head">
  <div><h1>Alertas</h1><p class="lead-sm">Generadas automáticamente por reglas del sistema; se resuelven solas cuando la condición deja de cumplirse.</p></div>
  <form method="post" action="<?= e(url('alertas/recalcular')) ?>">
    <?= csrf_field() ?><button class="btn btn-light"><i class="bi bi-arrow-repeat me-1"></i> Recalcular</button>
  </form>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($icons as $t => $icon): ?>
    <?php if ($t === 'OTRA' && empty($byType[$t])) continue; ?>
    <div class="col-6 col-lg-2">
      <a class="card stat" href="<?= e(url('alertas', ['tipo' => $t])) ?>">
        <div class="stat-icon tone-<?= e($tones[$t]) ?>"><i class="bi bi-<?= e($icon) ?>"></i></div>
        <div><div class="stat-value"><?= $byType[$t] ?? 0 ?></div><div class="stat-label"><?= e(label('alert_type', $t)) ?></div></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title"><?= $type ? e(label('alert_type', $type)) : 'Alertas abiertas' ?></h2>
    <form method="get" action="<?= e(url('alertas')) ?>" class="d-flex gap-2">
      <?php if ($type): ?><input type="hidden" name="tipo" value="<?= e($type) ?>"><?php endif; ?>
      <select class="form-select form-select-sm" name="estado" onchange="this.form.submit()">
        <option value="">Abiertas</option>
        <?php foreach (options('alert_status') as $v => $l): ?><option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <?php if ($type || $status): ?><a class="btn btn-sm btn-light" href="<?= e(url('alertas')) ?>">Limpiar</a><?php endif; ?>
    </form>
  </div>
  <div>
    <?php foreach ($alerts as $a): ?>
      <div class="alert-row <?= $a['status'] === 'pendiente' ? 'is-new' : '' ?>">
        <div class="dot tone-<?= e($tones[$a['type']] ?? 'info') ?>"><i class="bi bi-<?= e($icons[$a['type']] ?? 'bell') ?>"></i></div>
        <div class="flex-grow-1 min-w-0">
          <div><?= e($a['message']) ?></div>
          <div class="cell-sub">
            <?= e(label('alert_type', $a['type'])) ?> · <?= e(fdate($a['created_at'], true)) ?> · <?= e(label('alert_status', $a['status'])) ?>
            <?php if ($a['patient_id']): ?> · <a href="<?= e(url('pacientes/' . $a['patient_id'])) ?>"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></a><?php endif; ?>
          </div>
        </div>
        <div class="d-flex gap-1">
          <?php if ($a['status'] === 'pendiente'): ?>
            <form method="post" action="<?= e(url('alertas/' . $a['id'] . '/estado')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="status" value="leida"><button class="btn btn-sm btn-light" title="Marcar leída"><i class="bi bi-eye"></i></button></form>
          <?php endif; ?>
          <?php if (in_array($a['status'], ['pendiente', 'leida'], true)): ?>
            <form method="post" action="<?= e(url('alertas/' . $a['id'] . '/estado')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="status" value="resuelta"><button class="btn btn-sm btn-soft" title="Resolver"><i class="bi bi-check2"></i></button></form>
            <form method="post" action="<?= e(url('alertas/' . $a['id'] . '/estado')) ?>"><?= csrf_field() ?>
              <input type="hidden" name="status" value="descartada"><button class="btn btn-sm btn-light" title="Descartar"><i class="bi bi-x"></i></button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$alerts): ?><div class="empty py-5"><i class="bi bi-check2-circle"></i>No hay alertas con ese filtro.</div><?php endif; ?>
  </div>
</div>
