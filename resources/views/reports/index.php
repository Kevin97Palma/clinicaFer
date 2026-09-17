<div class="page-head">
  <div><h1>Reportes</h1><p class="lead-sm">Indicadores de atención, evaluaciones y finanzas del periodo seleccionado.</p></div>
  <a class="btn btn-light" href="<?= e(url('reportes/exportar', ['reporte' => $report, 'desde' => $from, 'hasta' => $to, 'patient_id' => $patient_id ?: null, 'professional_id' => $professional_id ?: null])) ?>">
    <i class="bi bi-download me-1"></i> Exportar CSV</a>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('reportes')) ?>">
    <div class="col-md-2"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($from) ?>"></div>
    <div class="col-md-2"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
    <div class="col-md-3" data-patient-picker>
      <label class="form-label" for="rp-patient">Paciente</label>
      <input type="hidden" name="patient_id" value="<?= $patient_id ?: '' ?>">
      <div class="position-relative">
        <input type="search" class="form-control" id="rp-patient" placeholder="Todos" autocomplete="off"
               value="<?= $patient ? e($patient['first_name'] . ' ' . $patient['last_name']) : '' ?>">
        <div class="search-results" hidden></div>
      </div>
    </div>
    <div class="col-md-2">
      <label class="form-label" for="professional_id">Profesional</label>
      <select class="form-select" id="professional_id" name="professional_id">
        <option value="">Todos</option>
        <?php foreach ($professionals as $pr): ?>
          <option value="<?= (int) $pr['id'] ?>" <?= (int) $professional_id === (int) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Aplicar</button>
      <a class="btn btn-light" href="<?= e(url('reportes')) ?>">Limpiar</a>
    </div>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-primary"><i class="bi bi-journal-check"></i></div>
    <div><div class="stat-value"><?= (int) $summary['attended'] ?></div><div class="stat-label">Sesiones atendidas</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-info"><i class="bi bi-percent"></i></div>
    <div><div class="stat-value"><?= (int) $summary['attendance_rate'] ?>%</div><div class="stat-label">Tasa de asistencia</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-danger"><i class="bi bi-person-x"></i></div>
    <div><div class="stat-value"><?= (int) $summary['no_show'] ?></div><div class="stat-label">Inasistencias</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-accent"><i class="bi bi-person-plus"></i></div>
    <div><div class="stat-value"><?= (int) $summary['new_patients'] ?></div><div class="stat-label">Pacientes nuevos</div></div></div></div>
  <?php if (can('payments.view')): ?>
    <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-success"><i class="bi bi-cash-stack"></i></div>
      <div><div class="stat-value"><?= e(money($summary['income'])) ?></div><div class="stat-label">Ingresos</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-warning"><i class="bi bi-hourglass"></i></div>
      <div><div class="stat-value"><?= e(money($summary['pending'])) ?></div><div class="stat-label">Pendiente de cobro</div></div></div></div>
  <?php endif; ?>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-info"><i class="bi bi-clipboard2-pulse"></i></div>
    <div><div class="stat-value"><?= (int) $summary['evaluations'] ?></div><div class="stat-label">Evaluaciones iniciadas</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="stat-icon tone-neutral"><i class="bi bi-people"></i></div>
    <div><div class="stat-value"><?= (int) $summary['active_patients'] ?></div><div class="stat-label">Pacientes activos</div></div></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-<?= can('payments.view') ? 7 : 12 ?>">
    <div class="card h-100"><div class="card-header"><h2 class="card-title">Sesiones por mes</h2></div>
      <div class="card-body"><canvas id="chart-sessions" height="120"></canvas></div></div>
  </div>
  <?php if (can('payments.view')): ?>
    <div class="col-lg-5">
      <div class="card h-100"><div class="card-header"><h2 class="card-title">Ingresos por mes</h2></div>
        <div class="card-body"><canvas id="chart-income" height="150"></canvas></div></div>
    </div>
  <?php endif; ?>
  <?php if ($charts['diagnoses']): ?>
    <div class="col-lg-5">
      <div class="card h-100"><div class="card-header"><h2 class="card-title">Diagnósticos más frecuentes</h2></div>
        <div class="card-body"><canvas id="chart-dx" height="180"></canvas></div></div>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2 class="card-title">Detalle</h2>
    <form method="get" action="<?= e(url('reportes')) ?>" class="d-flex gap-2">
      <input type="hidden" name="desde" value="<?= e($from) ?>"><input type="hidden" name="hasta" value="<?= e($to) ?>">
      <input type="hidden" name="patient_id" value="<?= $patient_id ?: '' ?>"><input type="hidden" name="professional_id" value="<?= $professional_id ?: '' ?>">
      <select class="form-select form-select-sm" name="reporte" onchange="this.form.submit()">
        <?php foreach ($definitions as $key => [$label, $perm]): ?>
          <?php if (!can($perm)) continue; ?>
          <option value="<?= e($key) ?>" <?= $report === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><?php foreach (array_keys($rows[0] ?? ['Dato' => '']) as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><?php foreach ($r as $k => $v): ?>
          <td class="<?= is_numeric($v) ? '' : 'cell-sub' ?>"><?= e(preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $v) ? fdate($v) : $v) ?></td>
        <?php endforeach; ?></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td><div class="empty"><i class="bi bi-inbox"></i>Sin datos para este reporte en el periodo.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php App\Core\View::start('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  const monthly = <?= json_encode($charts['monthly'], JSON_UNESCAPED_UNICODE) ?>;
  const income = <?= json_encode($charts['income'], JSON_UNESCAPED_UNICODE) ?>;
  const dx = <?= json_encode($charts['diagnoses'], JSON_UNESCAPED_UNICODE) ?>;
  const grid = { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { color: '#6b7471' } };
  const base = { responsive: true, plugins: { legend: { labels: { color: '#1f2a2a', boxWidth: 12 } } }, scales: { x: grid, y: { ...grid, beginAtZero: true } } };

  new Chart(document.getElementById('chart-sessions'), {
    type: 'bar',
    data: { labels: monthly.map((m) => m.mes), datasets: [
      { label: 'Atendidas', data: monthly.map((m) => +m.atendidas), backgroundColor: '#2f6f66' },
      { label: 'Canceladas', data: monthly.map((m) => +m.canceladas), backgroundColor: '#c3c9c7' },
      { label: 'No asistió', data: monthly.map((m) => +m.inasistencias), backgroundColor: '#c47a53' },
    ] },
    options: { ...base, scales: { x: { ...grid, stacked: true }, y: { ...grid, stacked: true, beginAtZero: true } } },
  });

  const incomeEl = document.getElementById('chart-income');
  if (incomeEl) new Chart(incomeEl, {
    type: 'line',
    data: { labels: income.map((i) => i.mes), datasets: [{ label: 'Ingresos', data: income.map((i) => +i.total), borderColor: '#3d7a4a', backgroundColor: 'rgba(61,122,74,.12)', fill: true, tension: .3 }] },
    options: base,
  });

  const dxEl = document.getElementById('chart-dx');
  if (dxEl) new Chart(dxEl, {
    type: 'doughnut',
    data: { labels: dx.map((d) => d.diagnosis), datasets: [{ data: dx.map((d) => +d.n), backgroundColor: ['#2f6f66', '#c47a53', '#3b6c93', '#9a6a12', '#3d7a4a', '#8a8f8d'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#1f2a2a', boxWidth: 12 } } } },
  });
})();
</script>
<?php App\Core\View::stop(); ?>
