<?php
$isNew = $patient === null;
$val = fn(string $k, $d = '') => e(old($k, $patient[$k] ?? $d));
?>
<div class="page-head">
  <div>
    <h1><?= $isNew ? 'Nuevo paciente' : 'Editar paciente' ?></h1>
    <p class="lead-sm">Expediente <strong><?= e($file_number) ?></strong><?= $isNew ? ' (se asigna automáticamente)' : '' ?></p>
  </div>
  <a class="btn btn-light" href="<?= e($isNew ? url('pacientes') : url('pacientes/' . $patient['id'])) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e($isNew ? url('pacientes') : url('pacientes/' . $patient['id'])) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="form-section">
            <div class="form-section-title"><span class="num">1</span> Datos personales</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="first_name">Nombres *</label>
                <input class="form-control" id="first_name" name="first_name" required maxlength="100" value="<?= $val('first_name') ?>">
                <?= field_error('first_name') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="last_name">Apellidos *</label>
                <input class="form-control" id="last_name" name="last_name" required maxlength="100" value="<?= $val('last_name') ?>">
                <?= field_error('last_name') ?>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="birth_date">Fecha de nacimiento *</label>
                <input type="date" class="form-control" id="birth_date" name="birth_date" required max="<?= date('Y-m-d') ?>" value="<?= $val('birth_date') ?>">
                <div class="form-text" id="age-hint">La edad se calcula automáticamente.</div>
                <?= field_error('birth_date') ?>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="sex">Sexo *</label>
                <select class="form-select" id="sex" name="sex" required><?= select_options('sex', old('sex', $patient['sex'] ?? ''), true) ?></select>
                <?= field_error('sex') ?>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="identification">Identificación</label>
                <input class="form-control" id="identification" name="identification" maxlength="30" value="<?= $val('identification') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="phone">Teléfono</label>
                <input class="form-control" id="phone" name="phone" maxlength="30" value="<?= $val('phone') ?>">
              </div>
              <div class="col-md-8">
                <label class="form-label" for="email">Correo</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?= $val('email') ?>">
                <?= field_error('email') ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="address">Dirección</label>
                <input class="form-control" id="address" name="address" maxlength="255" value="<?= $val('address') ?>">
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title"><span class="num">2</span> Datos escolares y de ingreso</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="school">Institución educativa</label>
                <input class="form-control" id="school" name="school" maxlength="150" value="<?= $val('school') ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="grade">Grado / curso</label>
                <input class="form-control" id="grade" name="grade" maxlength="60" value="<?= $val('grade') ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="intake_date">Fecha de ingreso *</label>
                <input type="date" class="form-control" id="intake_date" name="intake_date" required value="<?= $val('intake_date', date('Y-m-d')) ?>">
                <?= field_error('intake_date') ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="consultation_reason">Motivo de consulta</label>
                <textarea class="form-control" id="consultation_reason" name="consultation_reason" rows="3"><?= $val('consultation_reason') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="general_notes">Observaciones generales</label>
                <textarea class="form-control" id="general_notes" name="general_notes" rows="2"><?= $val('general_notes') ?></textarea>
              </div>
            </div>
          </div>

          <?php if ($isNew): ?>
            <div class="form-section">
              <div class="form-section-title"><span class="num">3</span> Representante principal <span class="text-muted fw-normal small">(opcional, puede agregar más después)</span></div>
              <div class="row g-3">
                <div class="col-md-5">
                  <label class="form-label" for="guardian_first_name">Nombres</label>
                  <input class="form-control" id="guardian_first_name" name="guardian_first_name" maxlength="100" value="<?= e(old('guardian_first_name')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="guardian_last_name">Apellidos</label>
                  <input class="form-control" id="guardian_last_name" name="guardian_last_name" maxlength="100" value="<?= e(old('guardian_last_name')) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="guardian_relationship">Parentesco</label>
                  <select class="form-select" id="guardian_relationship" name="guardian_relationship"><?= select_options('relationship', old('guardian_relationship', 'madre')) ?></select>
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="guardian_identification">Identificación</label>
                  <input class="form-control" id="guardian_identification" name="guardian_identification" maxlength="30" value="<?= e(old('guardian_identification')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="guardian_phone">Teléfono</label>
                  <input class="form-control" id="guardian_phone" name="guardian_phone" maxlength="30" value="<?= e(old('guardian_phone')) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label" for="guardian_email">Correo</label>
                  <input type="email" class="form-control" id="guardian_email" name="guardian_email" maxlength="150" value="<?= e(old('guardian_email')) ?>">
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2 class="card-title">Seguimiento</h2></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="status">Estado *</label>
            <select class="form-select" id="status" name="status" required><?= select_options('patient_status', old('status', $patient['status'] ?? 'activo')) ?></select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="professional_id">Profesional responsable</label>
            <select class="form-select" id="professional_id" name="professional_id">
              <option value="">Sin asignar</option>
              <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int) $pr['id'] ?>" <?= (string) old('professional_id', $patient['professional_id'] ?? '') === (string) $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="clinical-box small text-muted">
            El número de expediente se genera automáticamente y la edad se calcula desde la fecha de nacimiento, para que nunca quede desactualizada.
          </div>
        </div>
        <div class="sticky-actions">
          <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> <?= $isNew ? 'Registrar paciente' : 'Guardar cambios' ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php App\Core\View::start('scripts'); ?>
<script>
  const bd = document.getElementById('birth_date'), hint = document.getElementById('age-hint');
  function showAge() {
    if (!bd.value) { hint.textContent = 'La edad se calcula automáticamente.'; return; }
    const b = new Date(bd.value), t = new Date();
    let y = t.getFullYear() - b.getFullYear(), m = t.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && t.getDate() < b.getDate())) { y--; m += 12; }
    hint.textContent = y >= 0 ? `Edad actual: ${y} años ${m} meses` : 'Fecha no válida';
  }
  bd.addEventListener('change', showAge); showAge();
</script>
<?php App\Core\View::stop(); ?>
