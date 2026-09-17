<?php
$counts = [];
foreach ($summary as $s) {
    $counts[$s['attendance']] = (int) $s['n'];
}
?>
<div class="page-head">
  <div><h1>Sesiones</h1><p class="lead-sm">Registro histórico de sesiones terapéuticas.</p></div>
  <?php if (can('clinical.manage')): ?>
    <a class="btn btn-primary" href="<?= e(url('sesiones/nueva')) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva sesión</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <?php foreach (options('attendance') as $val => $lab): ?>
    <div class="col-6 col-md-3">
      <div class="card stat">
        <div class="stat-icon tone-<?= $val === 'atendido' ? 'success' : ($val === 'no_asistio' ? 'danger' : 'neutral') ?>"><i class="bi bi-<?= $val === 'atendido' ? 'check2' : ($val === 'no_asistio' ? 'person-x' : 'dash-circle') ?>"></i></div>
        <div><div class="stat-value"><?= $counts[$val] ?? 0 ?></div><div class="stat-label"><?= e($lab) ?></div></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('sesiones')) ?>">
    <div class="col-md-3"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($from) ?>"></div>
    <div class="col-md-3"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
    <div class="col-md-3">
      <label class="form-label" for="asistencia">Asistencia</label>
      <select class="form-select" id="asistencia" name="asistencia">
        <option value="">Todas</option>
        <?php foreach (options('attendance') as $v => $l): ?><option value="<?= e($v) ?>" <?= $attendance === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button></div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Fecha</th><th>Paciente</th><th>N.º</th><th class="d-none d-md-table-cell">Modalidad</th><th>Asistencia</th><th class="d-none d-lg-table-cell">Avance</th><th class="d-none d-lg-table-cell">Profesional</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($sessions as $s): ?>
        <tr>
          <td><?= e(fdate($s['session_date'])) ?><div class="cell-sub"><?= e(ftime($s['session_time'])) ?></div></td>
          <td><a class="cell-title" href="<?= e(url('pacientes/' . $s['patient_id'])) ?>"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></a>
            <div class="cell-sub"><?= e($s['file_number']) ?></div></td>
          <td><?= $s['session_number'] ? (int) $s['session_number'] : '—' ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(label('modality', $s['modality'])) ?></td>
          <td><?= badge('attendance', $s['attendance']) ?></td>
          <td class="d-none d-lg-table-cell"><?= $s['progress'] ? badge('progress', $s['progress']) : '—' ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($s['professional_name']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('sesiones/' . $s['id'])) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$sessions): ?>
        <tr><td colspan="8"><div class="empty"><i class="bi bi-journal-text"></i>No hay sesiones en el rango seleccionado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
