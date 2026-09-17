<div class="page-head">
  <div>
    <h1>Nueva evaluación psicológica</h1>
    <p class="lead-sm"><?= $patient ? e($patient['first_name'] . ' ' . $patient['last_name'] . ' · ' . $patient['file_number']) : 'Seleccione el paciente.' ?></p>
  </div>
  <a class="btn btn-light" href="<?= e($patient ? url('pacientes/' . $patient['id'], ['tab' => 'evaluaciones']) : url('evaluaciones')) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e(url('evaluaciones')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <?php if (!$patient): ?>
            <div class="form-section">
              <div class="form-section-title"><span class="num">1</span> Paciente</div>
              <div data-patient-picker>
                <input type="hidden" name="patient_id" required>
                <div class="position-relative">
                  <input type="search" class="form-control" placeholder="Buscar por nombre, cédula o expediente" autocomplete="off">
                  <div class="search-results" hidden></div>
                </div>
              </div>
            </div>
          <?php else: ?>
            <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
          <?php endif; ?>

          <div class="form-section">
            <div class="form-section-title"><span class="num"><?= $patient ? 1 : 2 ?></span> Motivo y áreas</div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="start_date">Fecha de inicio *</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="professional_id">Profesional</label>
                <select class="form-select" id="professional_id" name="professional_id">
                  <?php foreach ($professionals as $pr): ?>
                    <option value="<?= (int) $pr['id'] ?>" <?= (int) $pr['id'] === (int) auth_user()['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="reason">Motivo de la evaluación *</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Áreas evaluadas *</label>
                <div class="choice-group">
                  <?php foreach (options('evaluation_area') as $v => $l): ?>
                    <input type="checkbox" class="btn-check" name="areas[]" value="<?= e($v) ?>" id="area-<?= e($v) ?>">
                    <label class="btn" for="area-<?= e($v) ?>"><?= e($l) ?></label>
                  <?php endforeach; ?>
                </div>
                <?= field_error('areas') ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2 class="card-title">Flujo de evaluación</h2></div>
        <div class="card-body">
          <ol class="small ps-3 mb-3">
            <li>Motivo y áreas</li><li>Instrumentos y resultados</li><li>Interpretación profesional</li>
            <li>Integración clínica</li><li>Conclusiones y recomendaciones</li><li>Informe y entrega</li>
          </ol>
          <div class="clinical-box small" style="white-space:normal">
            El sistema no interpreta puntajes ni genera diagnósticos automáticamente: toda interpretación la redacta y valida la profesional.
          </div>
        </div>
        <div class="sticky-actions"><button class="btn btn-primary w-100">Crear evaluación</button></div>
      </div>
    </div>
  </div>
</form>
