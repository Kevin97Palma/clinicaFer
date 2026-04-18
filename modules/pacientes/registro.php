<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('pacientes.registrar');

$pageTitle  = 'Nuevo Paciente';
$activeMenu = 'pacientes';

$pdo       = db();
$clinicaId = clinicaId();

// Operativos disponibles para asignar (supervisor+)
$operativos = [];
if (tieneNivel(ROL_SUPERVISOR)) {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido FROM usuarios u
         JOIN usuario_clinica uc ON uc.usuario_id = u.id
         WHERE uc.clinica_id = :cid AND uc.rol IN ('operativo','supervisor') AND u.activo = 1
         ORDER BY u.nombre"
    );
    $stmt->execute([':cid' => $clinicaId]);
    $operativos = $stmt->fetchAll();
}

$esSupervisorPlus = tieneNivel(ROL_SUPERVISOR);

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-9">

<!-- ── WIZARD CARD ─────────────────────────────────── -->
<div class="card border-0 shadow-sm">

    <!-- Card header: step indicators -->
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <div class="wiz-steps mb-3">

            <!-- Step 1 -->
            <div class="wiz-step-item">
                <div class="wiz-step-circle active" id="wiz-circle-1" data-step="1">
                    <span class="wiz-step-num">1</span>
                    <i class="bi bi-check-lg wiz-step-check d-none"></i>
                </div>
                <div class="wiz-step-label" id="wiz-label-1">Datos del paciente</div>
            </div>

            <!-- Connector 1-2 -->
            <div class="wiz-step-connector" id="wiz-conn-1"></div>

            <!-- Step 2 -->
            <div class="wiz-step-item">
                <div class="wiz-step-circle" id="wiz-circle-2" data-step="2">
                    <span class="wiz-step-num">2</span>
                    <i class="bi bi-check-lg wiz-step-check d-none"></i>
                </div>
                <div class="wiz-step-label" id="wiz-label-2">Representante</div>
            </div>

            <!-- Connector 2-3 -->
            <div class="wiz-step-connector" id="wiz-conn-2"></div>

            <!-- Step 3 -->
            <div class="wiz-step-item">
                <div class="wiz-step-circle" id="wiz-circle-3" data-step="3">
                    <span class="wiz-step-num">3</span>
                    <i class="bi bi-check-lg wiz-step-check d-none"></i>
                </div>
                <div class="wiz-step-label" id="wiz-label-3">Revisión y asignación</div>
            </div>

        </div>

        <!-- Progress bar -->
        <div class="progress" style="height:4px">
            <div id="wiz-bar" class="progress-bar bg-success" role="progressbar"
                 style="width:33%;transition:width .35s ease"></div>
        </div>
    </div>

    <!-- Card body: panels -->
    <div class="card-body p-4">

        <!-- ── PANEL 1: Datos del paciente ─────────── -->
        <div id="wiz-step-1" class="wiz-panel">
            <h6 class="fw-semibold mb-3 text-primary">
                <i class="bi bi-person-vcard me-2"></i>Datos del Paciente
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-nombre">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="f-nombre" name="nombre" required
                           placeholder="Ej: Sebastián">
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-apellido">Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="f-apellido" name="apellido" required
                           placeholder="Ej: Alvarado">
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-cedula">Cédula <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="f-cedula" name="cedula"
                           placeholder="0000000000" maxlength="13">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-fecha-nac">Fecha de nacimiento <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="f-fecha-nac" name="fecha_nacimiento"
                           required max="<?= date('Y-m-d') ?>">
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-sexo">Sexo <span class="text-danger">*</span></label>
                    <select class="form-select" id="f-sexo" name="sexo" required>
                        <option value="">Seleccionar...</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="otro">Otro</option>
                    </select>
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-fecha-ing">Fecha de ingreso <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="f-fecha-ing" name="fecha_ingreso"
                           value="<?= date('Y-m-d') ?>" required>
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="f-motivo">Motivo de consulta <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="f-motivo" name="motivo_consulta" rows="3" required
                              placeholder="Describa el motivo principal de consulta..."></textarea>
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
            </div>
        </div>

        <!-- ── PANEL 2: Representante ───────────────── -->
        <div id="wiz-step-2" class="wiz-panel d-none">
            <h6 class="fw-semibold mb-3 text-primary">
                <i class="bi bi-person-lines-fill me-2"></i>Representante Legal
                <small class="text-muted fw-normal ms-1">(padre, madre o tutor legal)</small>
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="f-rep-nombre">Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="f-rep-nombre" name="representante_nombre" required
                           placeholder="Nombre y apellido del representante">
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="f-rep-cedula">Cédula <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="f-rep-cedula" name="representante_cedula" required
                           placeholder="0000000000" maxlength="13">
                    <div class="invalid-feedback">Campo obligatorio.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="f-rep-parentesco">Parentesco</label>
                    <select class="form-select" id="f-rep-parentesco" name="representante_parentesco">
                        <option value="madre">Madre</option>
                        <option value="padre">Padre</option>
                        <option value="tutor">Tutor legal</option>
                        <option value="abuelo">Abuelo/a</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="f-rep-tel">Teléfono</label>
                    <input type="tel" class="form-control" id="f-rep-tel" name="representante_telefono"
                           placeholder="09XXXXXXXX">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold" for="f-rep-email">Email</label>
                    <input type="email" class="form-control" id="f-rep-email" name="representante_email"
                           placeholder="correo@ejemplo.com">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="alert alert-warning py-2 px-3 small mb-0 w-100">
                        <i class="bi bi-key me-1"></i>
                        La contraseña inicial del portal del representante será su número de cédula.
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="f-consentimiento">
                        <i class="bi bi-file-earmark-check text-primary me-1"></i>Consentimiento informado
                        <span class="text-muted fw-normal">(opcional en este paso)</span>
                    </label>
                    <input type="file" class="form-control" id="f-consentimiento" name="consentimiento"
                           accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-text">PDF o imagen (máx. 20 MB). También puede subirse desde el perfil del paciente.</div>
                </div>
            </div>
        </div>

        <!-- ── PANEL 3: Revisión y asignación ─────── -->
        <div id="wiz-step-3" class="wiz-panel d-none">
            <h6 class="fw-semibold mb-3 text-primary">
                <i class="bi bi-clipboard2-check me-2"></i>Revisión y asignación
            </h6>

            <!-- Resumen de datos -->
            <div class="bg-light rounded-3 p-3 mb-3">
                <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em">Resumen del expediente</p>
                <dl class="row small mb-0" id="wiz-summary">
                    <dt class="col-sm-4">Paciente</dt>
                    <dd class="col-sm-8" id="sum-paciente">—</dd>

                    <dt class="col-sm-4">Fecha de nacimiento</dt>
                    <dd class="col-sm-8" id="sum-fecha-nac">—</dd>

                    <dt class="col-sm-4">Sexo</dt>
                    <dd class="col-sm-8" id="sum-sexo">—</dd>

                    <dt class="col-sm-4">Fecha de ingreso</dt>
                    <dd class="col-sm-8" id="sum-fecha-ing">—</dd>

                    <dt class="col-sm-4">Representante</dt>
                    <dd class="col-sm-8" id="sum-representante">—</dd>

                    <dt class="col-sm-4">Parentesco</dt>
                    <dd class="col-sm-8" id="sum-parentesco">—</dd>

                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8" id="sum-telefono">—</dd>

                    <dt class="col-sm-4">Motivo de consulta</dt>
                    <dd class="col-sm-8" id="sum-motivo">—</dd>
                </dl>
            </div>

            <!-- Asignación de psicólogo (solo supervisor+) -->
            <?php if ($esSupervisorPlus && !empty($operativos)): ?>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="f-operativo">Psicólogo asignado</label>
                    <select class="form-select" id="f-operativo" name="operativo_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($operativos as $op): ?>
                        <option value="<?= (int)$op['id'] ?>"><?= e($op['nombre'] . ' ' . $op['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="f-nota-derivacion">Nota de derivación <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea class="form-control" id="f-nota-derivacion" name="nota_derivacion" rows="2"
                              placeholder="Observaciones para el psicólogo asignado..."></textarea>
                </div>
            </div>
            <?php elseif ($esSupervisorPlus): ?>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="f-nota-derivacion">Nota de derivación <span class="text-muted fw-normal">(opcional)</span></label>
                <textarea class="form-control" id="f-nota-derivacion" name="nota_derivacion" rows="2"
                          placeholder="Observaciones adicionales..."></textarea>
            </div>
            <?php endif; ?>

            <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i>
                Al finalizar se creará el expediente del paciente y el acceso del representante al portal.
            </div>
        </div>

    </div><!-- /card-body -->

    <!-- Card footer: navigation buttons -->
    <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-4">
        <a href="<?= APP_URL ?>/modules/pacientes/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Cancelar
        </a>
        <div class="d-flex gap-2">
            <button type="button" id="wiz-prev" class="btn btn-secondary btn-sm d-none">
                <i class="bi bi-chevron-left me-1"></i>Anterior
            </button>
            <button type="button" id="wiz-next" class="btn btn-primary btn-sm">
                Siguiente <i class="bi bi-chevron-right ms-1"></i>
            </button>
            <button type="button" id="wiz-finish" class="btn btn-success btn-sm d-none">
                <i class="bi bi-check-lg me-1"></i>Registrar paciente
            </button>
        </div>
    </div>

</div><!-- /card -->
</div>
</div>

<?php
// Emit APP_URL into a JS variable, then inject the self-contained wizard script.
$appUrl  = APP_URL;
$extraJs = <<<EXTRAJS
<script>
/* SGC — URL base inyectada desde PHP */
var SGC_APP_URL = '{$appUrl}';
</script>
<script>
(function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────
    function el(id) { return document.getElementById(id); }
    function val(id) { return (el(id) ? el(id).value.trim() : ''); }

    var TOTAL = 3;
    var step  = 1;

    var panels = {
        1: el('wiz-step-1'),
        2: el('wiz-step-2'),
        3: el('wiz-step-3')
    };

    var circles = {
        1: el('wiz-circle-1'),
        2: el('wiz-circle-2'),
        3: el('wiz-circle-3')
    };

    var labels = {
        1: el('wiz-label-1'),
        2: el('wiz-label-2'),
        3: el('wiz-label-3')
    };

    var connectors = {
        1: el('wiz-conn-1'),
        2: el('wiz-conn-2')
    };

    var bar       = el('wiz-bar');
    var btnPrev   = el('wiz-prev');
    var btnNext   = el('wiz-next');
    var btnFinish = el('wiz-finish');

    // ── Step validation ───────────────────────────────────────
    function validateStep(s) {
        var panel    = panels[s];
        var required = panel.querySelectorAll('[required]');
        var ok       = true;

        required.forEach(function (field) {
            field.classList.remove('is-invalid');
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                ok = false;
            }
        });

        return ok;
    }

    // ── UI update ─────────────────────────────────────────────
    function updateUI() {
        // Progress bar: 33% / 66% / 100%
        bar.style.width = Math.round((step / TOTAL) * 100) + '%';

        // Circles and labels
        for (var i = 1; i <= TOTAL; i++) {
            var circle = circles[i];
            var label  = labels[i];
            var numEl  = circle.querySelector('.wiz-step-num');
            var chkEl  = circle.querySelector('.wiz-step-check');

            circle.classList.remove('active', 'done');
            label.classList.remove('active', 'done');

            if (i < step) {
                // Completed
                circle.classList.add('done');
                label.classList.add('done');
                numEl.classList.add('d-none');
                chkEl.classList.remove('d-none');
            } else if (i === step) {
                // Active
                circle.classList.add('active');
                label.classList.add('active');
                numEl.classList.remove('d-none');
                chkEl.classList.add('d-none');
            } else {
                // Upcoming
                numEl.classList.remove('d-none');
                chkEl.classList.add('d-none');
            }
        }

        // Connectors: turn green when the preceding step is done
        for (var c = 1; c <= TOTAL - 1; c++) {
            if (connectors[c]) {
                connectors[c].classList.toggle('done', c < step);
            }
        }

        // Footer buttons
        btnPrev.classList.toggle('d-none',   step === 1);
        btnNext.classList.toggle('d-none',   step === TOTAL);
        btnFinish.classList.toggle('d-none', step !== TOTAL);
    }

    // ── Navigate to step ─────────────────────────────────────
    function goTo(n) {
        panels[step].classList.add('d-none');
        step = n;
        panels[step].classList.remove('d-none');
        // Re-trigger animation via reflow
        panels[step].classList.remove('wiz-panel');
        void panels[step].offsetWidth;
        panels[step].classList.add('wiz-panel');
        updateUI();
        if (step === TOTAL) { fillSummary(); }
    }

    // ── Fill summary panel ────────────────────────────────────
    function fillSummary() {
        var sexoMap = { M: 'Masculino', F: 'Femenino', otro: 'Otro' };

        var nombre        = val('f-nombre');
        var apellido      = val('f-apellido');
        var fechaNac      = val('f-fecha-nac');
        var sexo          = val('f-sexo');
        var fechaIng      = val('f-fecha-ing');
        var motivo        = val('f-motivo');
        var repNombre     = val('f-rep-nombre');
        var repCedula     = val('f-rep-cedula');
        var repParentesco = val('f-rep-parentesco');
        var repTel        = val('f-rep-tel');

        function fmt(d) {
            if (!d) return '\u2014';
            var parts = d.split('-');
            if (parts.length !== 3) return d;
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function truncate(str, n) {
            return str.length > n ? str.substring(0, n) + '\u2026' : str;
        }

        el('sum-paciente').textContent      = (nombre + ' ' + apellido).trim() || '\u2014';
        el('sum-fecha-nac').textContent     = fmt(fechaNac);
        el('sum-sexo').textContent          = sexoMap[sexo] || sexo || '\u2014';
        el('sum-fecha-ing').textContent     = fmt(fechaIng);
        el('sum-representante').textContent = repNombre ? repNombre + ' (' + repCedula + ')' : '\u2014';
        el('sum-parentesco').textContent    = repParentesco || '\u2014';
        el('sum-telefono').textContent      = repTel || '\u2014';
        el('sum-motivo').textContent        = motivo ? truncate(motivo, 120) : '\u2014';
    }

    // ── Submit ────────────────────────────────────────────────
    async function submit() {
        SGC.btnLoading(btnFinish);

        var body = {
            nombre:                   val('f-nombre'),
            apellido:                 val('f-apellido'),
            cedula:                   val('f-cedula'),
            fecha_nacimiento:         val('f-fecha-nac'),
            sexo:                     val('f-sexo'),
            fecha_ingreso:            val('f-fecha-ing'),
            motivo_consulta:          val('f-motivo'),
            representante_nombre:     val('f-rep-nombre'),
            representante_cedula:     val('f-rep-cedula'),
            representante_email:      val('f-rep-email'),
            representante_telefono:   val('f-rep-tel'),
            representante_parentesco: val('f-rep-parentesco')
        };

        var opEl = el('f-operativo');
        if (opEl && opEl.value) { body.operativo_id = opEl.value; }

        try {
            var json = await SGC.apiFetch(
                SGC_APP_URL + '/api/pacientes/registro.php',
                { method: 'POST', body: body }
            );

            if (json.success) {
                SGC.toast(
                    'Paciente <strong>' + json.data.codigo + '</strong> registrado. ' +
                    '<a href="' + json.data.redirect + '" class="alert-link">Ver perfil \u2192</a>',
                    'success'
                );
                setTimeout(function () {
                    window.location.href = json.data.redirect;
                }, 2000);
            } else {
                var msg = json.message || 'Error al registrar.';
                if (json.errors && json.errors.length) {
                    msg += ' ' + json.errors.join(' ');
                }
                SGC.toast(msg, 'danger');
                SGC.btnLoading(btnFinish, false);
            }
        } catch (err) {
            SGC.toast('Error de conexión. Intente nuevamente.', 'danger');
            SGC.btnLoading(btnFinish, false);
        }
    }

    // ── Event listeners ───────────────────────────────────────
    btnNext.addEventListener('click', function () {
        if (!validateStep(step)) {
            SGC.toast('Complete los campos obligatorios antes de continuar.', 'warning');
            return;
        }
        if (step < TOTAL) { goTo(step + 1); }
    });

    btnPrev.addEventListener('click', function () {
        if (step > 1) { goTo(step - 1); }
    });

    btnFinish.addEventListener('click', function () {
        submit();
    });

    // Clear invalid state on user interaction
    document.querySelectorAll('#wiz-step-1 input, #wiz-step-1 select, #wiz-step-1 textarea,' +
                              '#wiz-step-2 input, #wiz-step-2 select, #wiz-step-2 textarea')
        .forEach(function (field) {
            field.addEventListener('input', function () {
                this.classList.remove('is-invalid');
            });
            field.addEventListener('change', function () {
                this.classList.remove('is-invalid');
            });
        });

    // ── Init ──────────────────────────────────────────────────
    updateUI();

})();
</script>
EXTRAJS;

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
