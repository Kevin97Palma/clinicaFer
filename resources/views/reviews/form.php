<div class="page-head">
  <div>
    <h1>Evaluación de progreso</h1>
    <p class="lead-sm">
      <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> · <?= e($patient['file_number']) ?>
      · <?= (int) $sessions_since ?> sesiones desde la última revisión
      · revisión sugerida cada <?= (int) setting('review_every_sessions', 8) ?> sesiones
    </p>
  </div>
  <a class="btn btn-light" href="<?= e(url('pacientes/' . $patient['id'], ['tab' => 'sesiones'])) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e(url('pacientes/' . $patient['id'] . '/revisiones')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <?php if ($plan): ?><input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>"><?php endif; ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="form-section">
            <div class="form-section-title"><span class="num">1</span> Objetivos</div>
            <p class="cell-sub">Precargados desde el plan activo; puede ajustarlos.</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="objectives_initial">Objetivos iniciales</label>
                <textarea class="form-control" id="objectives_initial" name="objectives_initial" rows="5"><?= e($prefill['objectives_initial']) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="objectives_achieved">Objetivos logrados</label>
                <textarea class="form-control" id="objectives_achieved" name="objectives_achieved" rows="5"><?= e($prefill['objectives_achieved']) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="objectives_in_progress">Objetivos en proceso</label>
                <textarea class="form-control" id="objectives_in_progress" name="objectives_in_progress" rows="4"><?= e($prefill['objectives_in_progress']) ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="objectives_no_progress">Objetivos sin avance</label>
                <textarea class="form-control" id="objectives_no_progress" name="objectives_no_progress" rows="4"><?= e($prefill['objectives_no_progress']) ?></textarea>
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title"><span class="num">2</span> Reportes</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="patient_report">Reporte del paciente</label>
                <textarea class="form-control" id="patient_report" name="patient_report" rows="3"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="family_report">Reporte familiar</label>
                <textarea class="form-control" id="family_report" name="family_report" rows="3"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="school_report">Reporte escolar</label>
                <textarea class="form-control" id="school_report" name="school_report" rows="3"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="clinical_observation">Observación clínica</label>
                <textarea class="form-control" id="clinical_observation" name="clinical_observation" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2 class="card-title">Conclusión</h2></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="review_date">Fecha *</label>
            <input type="date" class="form-control" id="review_date" name="review_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="general_status">Estado general *</label>
            <select class="form-select" id="general_status" name="general_status" required><?= select_options('general_status', 'mejor', true) ?></select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="clinical_decision">Decisión clínica *</label>
            <select class="form-select" id="clinical_decision" name="clinical_decision" required><?= select_options('clinical_decision', 'mantener_plan', true) ?></select>
          </div>
          <?php if ($progress): ?>
            <div class="clinical-box small" style="white-space:normal">
              Cumplimiento actual del plan: <strong><?= (int) $progress['achieved'] ?>/<?= (int) $progress['total'] ?></strong> objetivos.
              Este dato es administrativo y no sustituye el juicio clínico.
            </div>
          <?php endif; ?>
        </div>
        <div class="sticky-actions"><button class="btn btn-primary w-100">Guardar evaluación</button></div>
      </div>
    </div>
  </div>
</form>
