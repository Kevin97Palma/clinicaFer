<?php
$byStatus = [];
foreach ($counts as $c) {
    $byStatus[$c['status']] = (int) $c['n'];
}
?>
<div class="page-head">
  <div><h1>Evaluaciones psicológicas</h1><p class="lead-sm">Proceso de evaluación, instrumentos aplicados e informes.</p></div>
  <?php if (can('evaluations.manage')): ?>
    <a class="btn btn-primary" href="<?= e(url('evaluaciones/nueva')) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva evaluación</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <?php foreach (options('evaluation_status') as $v => $l): ?>
    <div class="col-6 col-lg-3">
      <a class="card stat" href="<?= e(url('evaluaciones', ['estado' => $v])) ?>">
        <div class="stat-icon tone-<?= ['en_proceso' => 'info', 'finalizada' => 'primary', 'informe_pendiente' => 'warning', 'informe_entregado' => 'success'][$v] ?>"><i class="bi bi-clipboard2-pulse"></i></div>
        <div><div class="stat-value"><?= $byStatus[$v] ?? 0 ?></div><div class="stat-label"><?= e($l) ?></div></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title"><?= $status !== '' ? e(label('evaluation_status', $status)) : 'Todas las evaluaciones' ?></h2>
    <?php if ($status !== ''): ?><a class="btn btn-sm btn-light" href="<?= e(url('evaluaciones')) ?>">Ver todas</a><?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Paciente</th><th>Inicio</th><th class="d-none d-md-table-cell">Áreas</th><th>Instrumentos</th><th>Estado</th><th class="d-none d-lg-table-cell">Profesional</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($evaluations as $ev): ?>
        <tr>
          <td><a class="cell-title" href="<?= e(url('pacientes/' . $ev['patient_id'])) ?>"><?= e($ev['first_name'] . ' ' . $ev['last_name']) ?></a>
            <div class="cell-sub"><?= e($ev['file_number']) ?></div></td>
          <td class="cell-sub"><?= e(fdate($ev['start_date'])) ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(implode(', ', array_map(fn($a) => label('evaluation_area', $a), array_filter(explode(',', (string) $ev['areas']))))) ?></td>
          <td><?= (int) $ev['instruments'] ?></td>
          <td><?= badge('evaluation_status', $ev['status']) ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($ev['professional_name']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('evaluaciones/' . $ev['id'])) ?>">Abrir</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$evaluations): ?>
        <tr><td colspan="7"><div class="empty"><i class="bi bi-clipboard2-pulse"></i>Sin evaluaciones registradas.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
