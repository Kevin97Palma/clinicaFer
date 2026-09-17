<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Sesiones registradas (<?= count($sessions) ?>)</h2>
        <?php if (can('clinical.manage')): ?>
          <a class="btn btn-sm btn-primary" href="<?= e(url('sesiones/nueva', ['patient_id' => $pid])) ?>"><i class="bi bi-plus-lg me-1"></i> Nueva sesión</a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>N.º</th><th>Fecha</th><th>Modalidad</th><th>Asistencia</th><th class="d-none d-md-table-cell">Avance</th><th class="d-none d-lg-table-cell">Objetivo trabajado</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td class="cell-title"><?= $s['session_number'] ? (int) $s['session_number'] : '—' ?></td>
              <td><?= e(fdate($s['session_date'])) ?><div class="cell-sub"><?= e(ftime($s['session_time'])) ?></div></td>
              <td class="cell-sub"><?= e(label('modality', $s['modality'])) ?></td>
              <td><?= badge('attendance', $s['attendance']) ?></td>
              <td class="d-none d-md-table-cell"><?= $s['progress'] ? badge('progress', $s['progress']) : '—' ?></td>
              <td class="d-none d-lg-table-cell cell-sub"><?= e(mb_strimwidth((string) $s['objective_worked'], 0, 70, '…')) ?></td>
              <td class="text-end"><a class="btn btn-sm btn-light" href="<?= e(url('sesiones/' . $s['id'])) ?>">Ver</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$sessions): ?>
            <tr><td colspan="7"><div class="empty"><i class="bi bi-journal-text"></i>Todavía no hay sesiones registradas para este paciente.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Evaluaciones de progreso</h2>
        <?php if (can('clinical.manage')): ?>
          <a class="btn btn-sm btn-soft" href="<?= e(url('pacientes/' . $pid . '/revisiones/nueva')) ?>"><i class="bi bi-plus-lg"></i></a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <p class="cell-sub">Revisión sugerida cada <?= (int) setting('review_every_sessions', 8) ?> sesiones (configurable).</p>
        <?php if ($reviews): ?>
          <div class="timeline">
            <?php foreach ($reviews as $r): ?>
              <div class="timeline-item">
                <a class="cell-title" href="<?= e(url('revisiones/' . $r['id'])) ?>"><?= e(fdate($r['review_date'])) ?></a>
                <div class="mt-1 d-flex gap-1 flex-wrap">
                  <?= badge('general_status', $r['general_status']) ?>
                  <span class="badge-soft badge-neutral"><?= e(label('clinical_decision', $r['clinical_decision'])) ?></span>
                </div>
                <div class="cell-sub mt-1"><?= (int) $r['sessions_at_review'] ?> sesiones a la fecha</div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty py-3"><i class="bi bi-graph-up"></i>Sin revisiones de progreso.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
