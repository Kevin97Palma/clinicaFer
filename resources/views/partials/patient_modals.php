<?php
/** Modales reutilizables del expediente. Reciben $pid, $patient, $guardians, $professionals. */
$defaultDuration = (int) setting('session_duration', 45);
$defaultFee = (float) setting('session_fee', 0);
?>

<?php if (can('agenda.manage')): ?>
<div class="modal fade" id="modal-appointment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('agenda/citas')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="patient_id" value="<?= $pid ?>">
        <div class="modal-header"><h5 class="modal-title" data-modal-title>Nueva cita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12"><div class="clinical-box small"><i class="bi bi-person me-1"></i> <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> · <?= e($patient['file_number']) ?></div></div>
          <div class="col-7">
            <label class="form-label" for="ap-date">Fecha y hora *</label>
            <input type="datetime-local" class="form-control" id="ap-date" name="starts_at" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 09:00')) ?>">
          </div>
          <div class="col-5">
            <label class="form-label" for="ap-dur">Duración (min) *</label>
            <input type="number" class="form-control" id="ap-dur" name="duration_min" min="10" max="480" step="5" value="<?= $defaultDuration ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="ap-type">Tipo</label>
            <select class="form-select" id="ap-type" name="type"><?= select_options('appointment_type', 'sesion') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="ap-prof">Profesional *</label>
            <select class="form-select" id="ap-prof" name="professional_id" required>
              <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int) $pr['id'] ?>" <?= (int) $pr['id'] === (int) ($patient['professional_id'] ?: auth_user()['id']) ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="ap-reason">Motivo</label>
            <input class="form-control" id="ap-reason" name="reason" maxlength="255">
          </div>
          <div class="col-12">
            <label class="form-label" for="ap-notes">Observaciones</label>
            <textarea class="form-control" id="ap-notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar cita</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('payments.manage')): ?>
<div class="modal fade" id="modal-payment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pagos')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="patient_id" value="<?= $pid ?>">
        <div class="modal-header"><h5 class="modal-title">Registrar pago</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-6">
            <label class="form-label" for="pay-date">Fecha *</label>
            <input type="date" class="form-control" id="pay-date" name="paid_at" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="pay-amount">Valor *</label>
            <input type="number" class="form-control" id="pay-amount" name="amount" step="0.01" min="0" value="<?= e(number_format($defaultFee, 2, '.', '')) ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="pay-concept">Concepto *</label>
            <input class="form-control" id="pay-concept" name="concept" maxlength="200" value="Sesión terapéutica" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="pay-method">Método</label>
            <select class="form-select" id="pay-method" name="method"><?= select_options('payment_method', 'efectivo') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="pay-status">Estado</label>
            <select class="form-select" id="pay-status" name="status"><?= select_options('payment_status', 'pagado') ?></select>
          </div>
          <div class="col-12">
            <label class="form-label" for="pay-ref">Referencia</label>
            <input class="form-control" id="pay-ref" name="reference" maxlength="100">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar pago</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-package" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('paquetes')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="patient_id" value="<?= $pid ?>">
        <div class="modal-header"><h5 class="modal-title">Nuevo paquete de sesiones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label" for="pk-name">Nombre *</label>
            <input class="form-control" id="pk-name" name="name" value="Paquete de 8 sesiones" maxlength="120" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="pk-date">Fecha de compra *</label>
            <input type="date" class="form-control" id="pk-date" name="purchased_at" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="pk-exp">Vence</label>
            <input type="date" class="form-control" id="pk-exp" name="expires_at">
          </div>
          <div class="col-4">
            <label class="form-label" for="pk-total">Sesiones *</label>
            <input type="number" class="form-control" id="pk-total" name="sessions_total" min="1" max="200" value="8" required>
          </div>
          <div class="col-4">
            <label class="form-label" for="pk-price">Precio regular *</label>
            <input type="number" class="form-control" id="pk-price" name="price_regular" step="0.01" min="0" value="160.00" required>
          </div>
          <div class="col-4">
            <label class="form-label" for="pk-disc">Descuento</label>
            <input type="number" class="form-control" id="pk-disc" name="discount" step="0.01" min="0" value="30.00">
          </div>
          <div class="col-6">
            <label class="form-label" for="pk-paid">Total pagado *</label>
            <input type="number" class="form-control" id="pk-paid" name="total_paid" step="0.01" min="0" value="130.00" required>
          </div>
          <div class="col-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="pk-payment" name="register_payment" value="1" checked>
              <label class="form-check-label" for="pk-payment">Registrar el pago del paquete</label>
            </div>
          </div>
          <div class="col-12"><div class="cell-sub">El sistema descuenta una sesión por cada sesión atendida y avisa cuando quede 1 disponible.</div></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Crear paquete</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('payments.void')): ?>
<div class="modal fade" id="modal-void" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="post" action="" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Anular pago</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <p class="cell-sub">El pago no se elimina: queda como anulado con registro de auditoría.</p>
          <label class="form-label" for="void-reason">Motivo *</label>
          <input class="form-control" id="void-reason" name="void_reason" maxlength="255" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-danger">Anular</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('clinical.manage')): ?>
<div class="modal fade" id="modal-diagnosis" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pacientes/' . $pid . '/diagnosticos')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title" data-modal-title>Nuevo diagnóstico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label" for="dx-name">Diagnóstico *</label>
            <input class="form-control" id="dx-name" name="diagnosis" maxlength="255" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="dx-system">Sistema *</label>
            <select class="form-select" id="dx-system" name="classification_system" required><?= select_options('classification_system', 'DSM-5-TR') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="dx-code">Código</label>
            <input class="form-control" id="dx-code" name="code" maxlength="30" placeholder="Ej.: F90.0">
          </div>
          <div class="col-6">
            <label class="form-label" for="dx-type">Tipo *</label>
            <select class="form-select" id="dx-type" name="type" required><?= select_options('diagnosis_type', 'presuntivo') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="dx-date">Fecha *</label>
            <input type="date" class="form-control" id="dx-date" name="diagnosed_at" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="dx-status">Vigencia</label>
            <select class="form-select" id="dx-status" name="status"><?= select_options('diagnosis_status', 'activo') ?></select>
          </div>
          <div class="col-12">
            <label class="form-label" for="dx-notes">Observaciones</label>
            <textarea class="form-control" id="dx-notes" name="notes" rows="2"></textarea>
          </div>
          <div class="col-12"><div class="cell-sub">El sistema no genera diagnósticos automáticamente: el registro es responsabilidad profesional.</div></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('patients.manage')): ?>
<div class="modal fade" id="modal-guardian" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pacientes/' . $pid . '/representantes')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title" data-modal-title>Nuevo representante</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-6">
            <label class="form-label" for="g-first">Nombres *</label>
            <input class="form-control" id="g-first" name="first_name" maxlength="100" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="g-last">Apellidos *</label>
            <input class="form-control" id="g-last" name="last_name" maxlength="100" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="g-rel">Parentesco *</label>
            <select class="form-select" id="g-rel" name="relationship" required><?= select_options('relationship', 'madre') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="g-ident">Identificación</label>
            <input class="form-control" id="g-ident" name="identification" maxlength="30">
          </div>
          <div class="col-6">
            <label class="form-label" for="g-phone">Teléfono</label>
            <input class="form-control" id="g-phone" name="phone" maxlength="30">
          </div>
          <div class="col-6">
            <label class="form-label" for="g-email">Correo</label>
            <input type="email" class="form-control" id="g-email" name="email" maxlength="150">
          </div>
          <div class="col-12">
            <label class="form-label" for="g-notes">Observaciones</label>
            <input class="form-control" id="g-notes" name="notes" maxlength="255">
          </div>
          <div class="col-12 d-flex gap-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="g-primary" name="is_primary" value="1">
              <label class="form-check-label" for="g-primary">Contacto principal</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="g-auth" name="authorized_info" value="1" checked>
              <label class="form-check-label" for="g-auth">Autorizado para recibir información</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-consent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pacientes/' . $pid . '/consentimientos')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Registrar consentimiento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label" for="cs-type">Tipo *</label>
            <select class="form-select" id="cs-type" name="type" required><?= select_options('consent_type', 'consentimiento_informado') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="cs-date">Fecha *</label>
            <input type="date" class="form-control" id="cs-date" name="consent_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="cs-status">Estado *</label>
            <select class="form-select" id="cs-status" name="status" required><?= select_options('consent_status', 'vigente') ?></select>
          </div>
          <div class="col-12">
            <label class="form-label" for="cs-guardian">Representante</label>
            <select class="form-select" id="cs-guardian" name="guardian_id">
              <option value="">—</option>
              <?php foreach ($guardians as $g): ?>
                <option value="<?= (int) $g['id'] ?>"><?= e($g['first_name'] . ' ' . $g['last_name']) ?> (<?= e(label('relationship', $g['relationship'])) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="cs-file">Documento firmado (PDF o imagen)</label>
            <input type="file" class="form-control" id="cs-file" name="file" accept=".pdf,.jpg,.jpeg,.png">
          </div>
          <div class="col-12">
            <label class="form-label" for="cs-notes">Observaciones</label>
            <textarea class="form-control" id="cs-notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (can('files.manage')): ?>
<div class="modal fade" id="modal-file" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('pacientes/' . $pid . '/archivos')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Subir archivo al expediente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12">
            <label class="form-label" for="fl-file">Archivo * <span class="cell-sub">(PDF, JPG, PNG, DOCX)</span></label>
            <input type="file" class="form-control" id="fl-file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="fl-cat">Clasificación *</label>
            <select class="form-select" id="fl-cat" name="category" required><?= select_options('file_category', 'informe_externo') ?></select>
          </div>
          <div class="col-12">
            <label class="form-label" for="fl-desc">Descripción</label>
            <input class="form-control" id="fl-desc" name="description" maxlength="255">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Subir</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
