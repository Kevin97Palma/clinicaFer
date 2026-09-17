<?php
$byStatus = [];
foreach ($counts as $c) {
    $byStatus[$c['status']] = (int) $c['n'];
}
?>
<div class="page-head">
  <div>
    <h1>Pacientes</h1>
    <p class="lead-sm"><?= (int) $total ?> paciente<?= $total == 1 ? '' : 's' ?> <?= $q !== '' || $status !== '' ? 'que coinciden con el filtro' : 'en el sistema' ?></p>
  </div>
  <?php if (can('patients.manage')): ?>
    <a href="<?= e(url('pacientes/nuevo')) ?>" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Nuevo paciente</a>
  <?php endif; ?>
</div>

<div class="card mb-3">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('pacientes')) ?>">
    <div class="col-md-6">
      <label class="form-label" for="q">Buscar por nombre, apellido, identificación o expediente</label>
      <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Ej.: Martínez, 1712345678, PSI-2026-0001">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="estado">Estado</label>
      <select class="form-select" id="estado" name="estado">
        <option value="">Todos</option>
        <?php foreach (options('patient_status') as $v => $l): ?>
          <option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?> (<?= $byStatus[$v] ?? 0 ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Filtrar</button>
      <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-light" href="<?= e(url('pacientes')) ?>">Limpiar</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Paciente</th><th>Expediente</th><th>Edad</th><th>Estado</th>
          <th class="d-none d-lg-table-cell">Profesional</th><th class="d-none d-md-table-cell">Última sesión</th>
          <th class="d-none d-md-table-cell">Próxima cita</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($patients as $p): ?>
        <tr>
          <td>
            <a class="cell-title" href="<?= e(url('pacientes/' . $p['id'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a>
            <div class="cell-sub"><?= e($p['identification'] ?: 'Sin identificación') ?><?= $p['school'] ? ' · ' . e($p['school']) : '' ?></div>
          </td>
          <td><span class="cell-sub"><?= e($p['file_number']) ?></span></td>
          <td><?= e(age($p['birth_date'])) ?></td>
          <td><?= badge('patient_status', $p['status']) ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($p['professional_name'] ?: '—') ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($p['last_session_at'])) ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e($p['next_appointment'] ? fdate($p['next_appointment'], true) : '—') ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-soft" href="<?= e(url('pacientes/' . $p['id'])) ?>">Abrir expediente</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$patients): ?>
        <tr><td colspan="8"><div class="empty"><i class="bi bi-people"></i>No hay pacientes que coincidan con la búsqueda.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="card-header justify-content-center">
      <nav aria-label="Paginación"><ul class="pagination pagination-sm mb-0">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= e(url('pacientes', ['q' => $q, 'estado' => $status, 'pagina' => $i])) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul></nav>
    </div>
  <?php endif; ?>
</div>
