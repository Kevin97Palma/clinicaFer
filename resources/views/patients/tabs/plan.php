<div class="row g-3">
  <div class="col-12">
    <?php if (can('clinical.manage')): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Nuevo plan terapéutico</h2>
          <button class="btn btn-sm btn-soft" type="button" data-bs-toggle="collapse" data-bs-target="#new-plan"><i class="bi bi-plus-lg me-1"></i> Crear plan</button>
        </div>
        <div class="collapse" id="new-plan">
          <form class="card-body needs-validation" method="post" action="<?= e(url('pacientes/' . $pid . '/planes')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label" for="start_date">Fecha de inicio *</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-3">
                <label class="form-label" for="review_date">Fecha de revisión</label>
                <input type="date" class="form-control" id="review_date" name="review_date" value="<?= date('Y-m-d', strtotime('+2 months')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="areas">Áreas iniciales</label>
                <select class="form-select" id="areas" name="areas[]" multiple size="4">
                  <?php foreach (options('treatment_area') as $v => $l): ?><option value="<?= e($v) ?>"><?= e($l) ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">Puede agregar o quitar áreas después.</div>
              </div>
              <div class="col-12">
                <label class="form-label" for="general_objective">Objetivo general *</label>
                <textarea class="form-control" id="general_objective" name="general_objective" rows="2" required></textarea>
              </div>
              <div class="col-12 text-end"><button class="btn btn-primary">Crear plan</button></div>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$plans): ?>
      <div class="card"><div class="empty"><i class="bi bi-bullseye"></i>Este paciente aún no tiene planes terapéuticos.</div></div>
    <?php endif; ?>

    <?php foreach ($plans as $plan): $pg = $progress[(int) $plan['id']]; ?>
      <div class="card mb-3">
        <div class="card-header">
          <div>
            <h2 class="card-title">Plan desde <?= e(fdate($plan['start_date'])) ?> <?= badge('plan_status', $plan['status']) ?></h2>
            <div class="cell-sub">Revisión: <?= e(fdate($plan['review_date'])) ?> · <?= (int) $pg['achieved'] ?>/<?= (int) $pg['total'] ?> objetivos logrados</div>
          </div>
          <a class="btn btn-sm btn-soft" href="<?= e(url('planes/' . $plan['id'])) ?>">Abrir plan</a>
        </div>
        <div class="card-body">
          <p class="mb-2"><strong>Objetivo general:</strong> <?= nl2br_e($plan['general_objective']) ?></p>
          <div class="progress mb-1"><div class="progress-bar" style="width: <?= (int) $pg['percent'] ?>%"></div></div>
          <p class="cell-sub mb-3"><?= (int) $pg['percent'] ?>% de cumplimiento administrativo de objetivos registrados (no es una medición clínica).</p>
          <div class="row g-3">
            <?php foreach ($pg['areas'] as $area): ?>
              <div class="col-md-6">
                <div class="clinical-box" style="white-space:normal">
                  <div class="cell-title mb-2"><?= e($area['name']) ?></div>
                  <ul class="list-unstyled mb-0 small">
                    <?php foreach ($pg['by_area'][(int) $area['id']] ?? [] as $o): ?>
                      <li class="d-flex justify-content-between gap-2 mb-1">
                        <span><?= e($o['objective']) ?><?php if ($o['indicator']): ?><div class="cell-sub"><?= e($o['indicator']) ?></div><?php endif; ?></span>
                        <?= badge('objective_status', $o['status']) ?>
                      </li>
                    <?php endforeach; ?>
                    <?php if (empty($pg['by_area'][(int) $area['id']])): ?><li class="text-muted">Sin objetivos aún.</li><?php endif; ?>
                  </ul>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
