<div class="card">
  <div class="card-header">
    <h2 class="card-title">Evaluaciones psicológicas (<?= count($evaluations) ?>)</h2>
    <?php if (can('evaluations.manage')): ?>
      <a class="btn btn-sm btn-primary" href="<?= e(url('evaluaciones/nueva', ['patient_id' => $pid])) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva evaluación</a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Inicio</th><th>Motivo</th><th>Áreas</th><th>Instrumentos</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($evaluations as $ev): ?>
        <tr>
          <td><?= e(fdate($ev['start_date'])) ?><div class="cell-sub"><?= e($ev['professional_name']) ?></div></td>
          <td class="cell-sub" style="max-width:280px"><?= e(mb_strimwidth((string) $ev['reason'], 0, 110, '…')) ?></td>
          <td class="cell-sub"><?= e(implode(', ', array_map(fn($a) => label('evaluation_area', $a), array_filter(explode(',', (string) $ev['areas']))))) ?></td>
          <td><?= (int) $ev['instruments'] ?></td>
          <td><?= badge('evaluation_status', $ev['status']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('evaluaciones/' . $ev['id'])) ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$evaluations): ?>
        <tr><td colspan="6"><div class="empty"><i class="bi bi-clipboard2-pulse"></i>Sin evaluaciones registradas.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
