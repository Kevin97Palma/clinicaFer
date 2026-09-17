<?php $canEdit = can('clinical.manage'); ?>
<div class="page-head">
  <div>
    <h1>Plan terapéutico</h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $plan['patient_id'])) ?>"><?= e($plan['first_name'] . ' ' . $plan['last_name']) ?></a>
      · <?= e($plan['file_number']) ?> · desde <?= e(fdate($plan['start_date'])) ?> <?= badge('plan_status', $plan['status']) ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($canEdit): ?>
      <a class="btn btn-soft" href="<?= e(url('pacientes/' . $plan['patient_id'] . '/revisiones/nueva')) ?>"><i class="bi bi-graph-up me-1"></i> Evaluar progreso</a>
    <?php endif; ?>
    <a class="btn btn-light" href="<?= e(url('pacientes/' . $plan['patient_id'], ['tab' => 'plan'])) ?>">Volver</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Áreas y objetivos</h2>
        <?php if ($canEdit): ?>
          <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#modal-area"><i class="bi bi-plus-lg me-1"></i> Área</button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Objetivos logrados</span>
          <strong><?= (int) $progress['achieved'] ?> de <?= (int) $progress['total'] ?> (<?= (int) $progress['percent'] ?>%)</strong>
        </div>
        <div class="progress mb-2"><div class="progress-bar" style="width: <?= (int) $progress['percent'] ?>%"></div></div>
        <p class="cell-sub">Este porcentaje refleja el cumplimiento administrativo de los objetivos registrados. No es una medición psicométrica ni un porcentaje clínico de recuperación.</p>

        <?php foreach ($progress['areas'] as $area): ?>
          <div class="form-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="form-section-title mb-0"><i class="bi bi-diagram-3 text-muted"></i> <?= e($area['name']) ?></div>
              <?php if ($canEdit): ?>
                <form method="post" action="<?= e(url('areas/' . $area['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar el área «<?= e($area['name']) ?>» y sus objetivos?">
                  <?= csrf_field() ?><button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </div>
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                <?php foreach ($progress['by_area'][(int) $area['id']] ?? [] as $o): ?>
                  <tr>
                    <td>
                      <div class="cell-title"><?= e($o['objective']) ?></div>
                      <?php if ($o['indicator']): ?><div class="cell-sub">Indicador: <?= e($o['indicator']) ?></div><?php endif; ?>
                      <?php if ($o['achieved_at']): ?><div class="cell-sub">Logrado el <?= e(fdate($o['achieved_at'])) ?></div><?php endif; ?>
                    </td>
                    <td class="text-end" style="width:200px">
                      <?php if ($canEdit): ?>
                        <form method="post" action="<?= e(url('objetivos/' . $o['id'])) ?>" class="d-inline-flex gap-1">
                          <?= csrf_field() ?>
                          <input type="hidden" name="objective" value="<?= e($o['objective']) ?>">
                          <input type="hidden" name="indicator" value="<?= e($o['indicator']) ?>">
                          <select class="form-select form-select-sm" name="status" onchange="this.form.submit()" aria-label="Estado del objetivo">
                            <?= select_options('objective_status', $o['status']) ?>
                          </select>
                        </form>
                        <form method="post" action="<?= e(url('objetivos/' . $o['id'] . '/eliminar')) ?>" class="d-inline" data-confirm="¿Eliminar este objetivo?">
                          <?= csrf_field() ?><button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      <?php else: ?>
                        <?= badge('objective_status', $o['status']) ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($canEdit): ?>
              <form method="post" action="<?= e(url('areas/' . $area['id'] . '/objetivos')) ?>" class="row g-2 mt-1 needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="col-md-5"><input class="form-control form-control-sm" name="objective" placeholder="Nuevo objetivo" maxlength="255" required></div>
                <div class="col-md-5"><input class="form-control form-control-sm" name="indicator" placeholder="Indicador observable" maxlength="255"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-soft w-100">Agregar</button></div>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$progress['areas']): ?>
          <div class="empty"><i class="bi bi-diagram-3"></i>Agregue al menos un área de trabajo para registrar objetivos.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Datos del plan</h2></div>
      <?php if ($canEdit): ?>
        <form method="post" action="<?= e(url('planes/' . $plan['id'])) ?>" class="card-body needs-validation" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label" for="start_date">Inicio *</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($plan['start_date']) ?>" required></div>
          <div class="mb-3"><label class="form-label" for="review_date">Próxima revisión</label>
            <input type="date" class="form-control" id="review_date" name="review_date" value="<?= e($plan['review_date']) ?>"></div>
          <div class="mb-3"><label class="form-label" for="status">Estado *</label>
            <select class="form-select" id="status" name="status" required><?= select_options('plan_status', $plan['status']) ?></select></div>
          <div class="mb-3"><label class="form-label" for="general_objective">Objetivo general *</label>
            <textarea class="form-control" id="general_objective" name="general_objective" rows="3" required><?= e($plan['general_objective']) ?></textarea></div>
          <div class="mb-3"><label class="form-label" for="notes">Observaciones</label>
            <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($plan['notes']) ?></textarea></div>
          <button class="btn btn-primary w-100">Guardar plan</button>
        </form>
      <?php else: ?>
        <div class="card-body">
          <dl class="kv">
            <dt>Inicio</dt><dd><?= e(fdate($plan['start_date'])) ?></dd>
            <dt>Revisión</dt><dd><?= e(fdate($plan['review_date'])) ?></dd>
            <dt>Objetivo general</dt><dd><?= nl2br_e($plan['general_objective']) ?></dd>
          </dl>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><h2 class="card-title">Evaluaciones de progreso</h2></div>
      <div class="card-body">
        <?php if ($reviews): ?>
          <div class="timeline">
            <?php foreach ($reviews as $r): ?>
              <div class="timeline-item">
                <a class="cell-title" href="<?= e(url('revisiones/' . $r['id'])) ?>"><?= e(fdate($r['review_date'])) ?></a>
                <div class="mt-1"><?= badge('general_status', $r['general_status']) ?></div>
                <div class="cell-sub mt-1"><?= e(label('clinical_decision', $r['clinical_decision'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty py-3"><i class="bi bi-graph-up"></i>Sin revisiones registradas.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="modal-area" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('planes/' . $plan['id'] . '/areas')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nueva área de trabajo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <label class="form-label" for="area-name">Área *</label>
          <input class="form-control" id="area-name" name="name" list="area-suggestions" maxlength="120" required>
          <datalist id="area-suggestions">
            <?php foreach (options('treatment_area') as $v => $l): ?><option value="<?= e($v) ?>"></option><?php endforeach; ?>
          </datalist>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Agregar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
