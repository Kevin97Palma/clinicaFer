<?php
$isEdit = $session !== null;
$v = fn(string $k, $d = '') => e(old($k, $session[$k] ?? $d));
$defaultDate = $appointment ? substr($appointment['starts_at'], 0, 10) : date('Y-m-d');
$defaultTime = $appointment ? substr($appointment['starts_at'], 11, 5) : date('H:i');
$defaultDuration = $appointment ? (int) $appointment['duration_min'] : (int) setting('session_duration', 45);
?>
<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar sesión' : 'Nueva sesión' ?></h1>
    <p class="lead-sm">
      <?php if ($patient): ?>
        <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> · <?= e($patient['file_number']) ?> · <?= e(age($patient['birth_date'])) ?>
        <?php if ($next_number): ?> · sesión N.º <strong><?= (int) $next_number ?></strong><?php endif; ?>
      <?php else: ?>
        Seleccione el paciente para completar automáticamente sus datos.
      <?php endif; ?>
    </p>
  </div>
  <a class="btn btn-light" href="<?= e($patient ? url('pacientes/' . $patient['id'], ['tab' => 'sesiones']) : url('sesiones')) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e($isEdit ? url('sesiones/' . $session['id']) : url('sesiones')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <?php if ($appointment): ?><input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>"><?php endif; ?>

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
                <?= field_error('patient_id') ?>
              </div>
            </div>
          <?php else: ?>
            <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
          <?php endif; ?>

          <div class="form-section">
            <div class="form-section-title"><span class="num"><?= $patient ? 1 : 2 ?></span> Datos de la sesión</div>
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label" for="session_date">Fecha *</label>
                <input type="date" class="form-control" id="session_date" name="session_date" required value="<?= $v('session_date', $defaultDate) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="session_time">Hora</label>
                <input type="time" class="form-control" id="session_time" name="session_time" value="<?= $v('session_time', $defaultTime) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="duration_min">Duración (min) *</label>
                <input type="number" class="form-control" id="duration_min" name="duration_min" min="5" max="480" step="5" required value="<?= $v('duration_min', (string) $defaultDuration) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="modality">Modalidad *</label>
                <select class="form-select" id="modality" name="modality" required><?= select_options('modality', old('modality', $session['modality'] ?? 'presencial')) ?></select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="attendance">Estado de asistencia *</label>
                <select class="form-select" id="attendance" name="attendance" required><?= select_options('attendance', old('attendance', $session['attendance'] ?? 'atendido')) ?></select>
                <div class="form-text">Solo las sesiones atendidas descuentan del paquete y suman al conteo del paciente.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="professional_id">Profesional</label>
                <select class="form-select" id="professional_id" name="professional_id">
                  <?php foreach ($professionals as $pr): ?>
                    <option value="<?= (int) $pr['id'] ?>" <?= (int) $pr['id'] === (int) ($session['professional_id'] ?? auth_user()['id']) ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-section" id="clinical-fields">
            <div class="form-section-title"><span class="num"><?= $patient ? 2 : 3 ?></span> Registro clínico <span class="confidential ms-1"><i class="bi bi-lock-fill"></i> confidencial</span></div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="objective_worked">Objetivo trabajado</label>
                <textarea class="form-control" id="objective_worked" name="objective_worked" rows="2"><?= $v('objective_worked') ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="emotional_state">Estado emocional inicial</label>
                <select class="form-select" id="emotional_state" name="emotional_state"><?= select_options('emotional_state', old('emotional_state', $session['emotional_state'] ?? ''), true) ?></select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="participation">Nivel de participación</label>
                <select class="form-select" id="participation" name="participation"><?= select_options('participation', old('participation', $session['participation'] ?? ''), true) ?></select>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="progress">Avance observado</label>
                <select class="form-select" id="progress" name="progress"><?= select_options('progress', old('progress', $session['progress'] ?? ''), true) ?></select>
              </div>
              <div class="col-12">
                <label class="form-label" for="intervention">Actividad / intervención realizada</label>
                <textarea class="form-control" id="intervention" name="intervention" rows="3"><?= $v('intervention') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="patient_response">Respuesta del paciente</label>
                <textarea class="form-control" id="patient_response" name="patient_response" rows="3"><?= $v('patient_response') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="clinical_notes">Observaciones clínicas</label>
                <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="3"><?= $v('clinical_notes') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="homework">Tarea para casa</label>
                <textarea class="form-control" id="homework" name="homework" rows="2"><?= $v('homework') ?></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="recommendations">Recomendaciones</label>
                <textarea class="form-control" id="recommendations" name="recommendations" rows="2"><?= $v('recommendations') ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="next_objective">Próximo objetivo</label>
                <textarea class="form-control" id="next_objective" name="next_objective" rows="2"><?= $v('next_objective') ?></textarea>
              </div>
            </div>
          </div>

          <?php if ($progress && $progress['objectives']): ?>
            <div class="form-section">
              <div class="form-section-title"><span class="num"><?= $patient ? 3 : 4 ?></span> Objetivos del plan trabajados</div>
              <p class="cell-sub">Al marcar un objetivo puede actualizar su estado en el plan terapéutico.</p>
              <?php foreach ($progress['areas'] as $area): ?>
                <div class="mb-3">
                  <div class="small-caps mb-2"><?= e($area['name']) ?></div>
                  <?php foreach ($progress['by_area'][(int) $area['id']] ?? [] as $o): $oid = (int) $o['id']; $checked = array_key_exists($oid, $selected_objectives); ?>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                      <div class="form-check flex-grow-1">
                        <input class="form-check-input" type="checkbox" name="objectives[]" value="<?= $oid ?>" id="obj-<?= $oid ?>" <?= $checked ? 'checked' : '' ?>>
                        <label class="form-check-label" for="obj-<?= $oid ?>"><?= e($o['objective']) ?>
                          <?php if ($o['indicator']): ?><span class="cell-sub d-block"><?= e($o['indicator']) ?></span><?php endif; ?>
                        </label>
                      </div>
                      <select class="form-select form-select-sm" name="objective_status[<?= $oid ?>]" style="max-width:170px">
                        <?php $cur = $selected_objectives[$oid] ?? $o['status']; ?>
                        <option value="">Sin cambio</option>
                        <?php foreach (options('objective_status') as $val => $lab): ?>
                          <option value="<?= e($val) ?>" <?= (string) $cur === (string) $val ? 'selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!$isEdit): ?>
            <div class="form-section">
              <div class="form-section-title"><span class="num"><?= $progress && $progress['objectives'] ? 4 : 3 ?></span> Próxima sesión <span class="text-muted fw-normal small">(opcional)</span></div>
              <div class="row g-3 align-items-end">
                <div class="col-md-5">
                  <label class="form-label" for="next_starts_at">Fecha y hora</label>
                  <input type="datetime-local" class="form-control" id="next_starts_at" name="next_starts_at">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="next_duration">Duración (min)</label>
                  <input type="number" class="form-control" id="next_duration" name="next_duration" min="10" max="480" step="5" value="<?= (int) setting('session_duration', 45) ?>">
                </div>
                <div class="col-md-4">
                  <button type="button" class="btn btn-light w-100" id="btn-next-week"><i class="bi bi-calendar-plus me-1"></i> En 7 días, misma hora</button>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Al guardar</h2></div>
        <div class="card-body">
          <ul class="list-unstyled small mb-0">
            <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success"></i> Se registra la evolución en el expediente</li>
            <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success"></i> Se actualiza la última sesión y el conteo</li>
            <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success"></i> Se descuenta del paquete activo (solo si asistió)</li>
            <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success"></i> Se actualizan los objetivos marcados</li>
            <li class="d-flex gap-2"><i class="bi bi-check2-circle text-success"></i> Se generan alertas si corresponde</li>
          </ul>
        </div>
      </div>

      <?php if ($package): ?>
        <div class="card mb-3">
          <div class="card-header"><h2 class="card-title">Paquete activo</h2></div>
          <div class="card-body">
            <div class="d-flex justify-content-between"><span><?= e($package['name']) ?></span>
              <strong><?= (int) $package['sessions_used'] ?>/<?= (int) $package['sessions_total'] ?></strong></div>
            <div class="pkg-dots mt-2">
              <?php for ($i = 0; $i < (int) $package['sessions_total']; $i++): ?>
                <span class="<?= $i < (int) $package['sessions_used'] ? 'used' : '' ?>"></span>
              <?php endfor; ?>
            </div>
            <p class="cell-sub mt-2 mb-0">Quedan <?= (int) $package['sessions_remaining'] ?> sesiones. Esta sesión se descontará automáticamente.</p>
          </div>
        </div>
      <?php elseif (!$isEdit && can('payments.manage')): ?>
        <div class="card mb-3">
          <div class="card-header"><h2 class="card-title">Cobro</h2></div>
          <div class="card-body">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="charge_pending" name="charge_pending" value="1" checked>
              <label class="form-check-label" for="charge_pending">Registrar cobro pendiente de <?= e(money($session_fee)) ?></label>
            </div>
            <p class="cell-sub mb-0 mt-2">El paciente no tiene paquete activo. Puede desmarcar si ya pagó o si no corresponde cobro.</p>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="sticky-actions">
          <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar sesión' ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php App\Core\View::start('scripts'); ?>
<script>
  const attendance = document.getElementById('attendance');
  const clinical = document.getElementById('clinical-fields');
  function toggleClinical() {
    const attended = attendance.value === 'atendido';
    clinical.style.opacity = attended ? '1' : '.6';
    const charge = document.getElementById('charge_pending');
    if (charge && !attended) charge.checked = false;
  }
  attendance.addEventListener('change', toggleClinical); toggleClinical();

  document.getElementById('btn-next-week')?.addEventListener('click', () => {
    const d = document.getElementById('session_date').value, t = document.getElementById('session_time').value || '09:00';
    if (!d) return;
    const next = new Date(d + 'T' + t); next.setDate(next.getDate() + 7);
    const p = (n) => String(n).padStart(2, '0');
    document.getElementById('next_starts_at').value = `${next.getFullYear()}-${p(next.getMonth() + 1)}-${p(next.getDate())}T${p(next.getHours())}:${p(next.getMinutes())}`;
  });
</script>
<?php App\Core\View::stop(); ?>
