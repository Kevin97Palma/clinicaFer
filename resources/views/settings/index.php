<?php $s = fn(string $k, $d = '') => e($settings[$k] ?? $d); ?>
<div class="page-head"><div><h1>Configuración</h1><p class="lead-sm">Datos de la profesional, valores predeterminados y reglas del sistema.</p></div></div>
<?= App\Core\View::fetch('partials/settings_nav') ?>

<form method="post" action="<?= e(url('configuracion')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Datos de la profesional</h2></div>
        <div class="card-body row g-3">
          <div class="col-md-6"><label class="form-label" for="brand_name">Nombre del sistema / consulta *</label>
            <input class="form-control" id="brand_name" name="brand_name" maxlength="120" required value="<?= $s('brand_name') ?>"></div>
          <div class="col-md-6"><label class="form-label" for="professional_name">Nombre de la profesional</label>
            <input class="form-control" id="professional_name" name="professional_name" maxlength="120" value="<?= $s('professional_name') ?>"></div>
          <div class="col-md-6"><label class="form-label" for="professional_title">Título profesional</label>
            <input class="form-control" id="professional_title" name="professional_title" maxlength="120" value="<?= $s('professional_title') ?>"></div>
          <div class="col-md-6"><label class="form-label" for="registration_number">Número de registro</label>
            <input class="form-control" id="registration_number" name="registration_number" maxlength="60" value="<?= $s('registration_number') ?>"></div>
          <div class="col-md-6"><label class="form-label" for="phone">Teléfono</label>
            <input class="form-control" id="phone" name="phone" maxlength="30" value="<?= $s('phone') ?>"></div>
          <div class="col-md-6"><label class="form-label" for="email">Correo</label>
            <input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?= $s('email') ?>"></div>
          <div class="col-12"><label class="form-label" for="address">Dirección</label>
            <input class="form-control" id="address" name="address" maxlength="255" value="<?= $s('address') ?>"></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2 class="card-title">Logo y firma</h2></div>
        <div class="card-body row g-3">
          <div class="col-md-6">
            <label class="form-label" for="logo">Logotipo (PNG o JPG)</label>
            <input type="file" class="form-control" id="logo" name="logo" accept=".png,.jpg,.jpeg">
            <?php if ($settings['logo_file'] ?? ''): ?>
              <div class="mt-2 d-flex align-items-center gap-2">
                <img src="<?= e(url('marca/logo')) ?>" alt="Logo actual" style="max-height:48px">
                <div class="form-check"><input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                  <label class="form-check-label" for="remove_logo">Quitar</label></div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="signature">Firma escaneada (PNG o JPG)</label>
            <input type="file" class="form-control" id="signature" name="signature" accept=".png,.jpg,.jpeg">
            <?php if ($settings['signature_file'] ?? ''): ?>
              <div class="mt-2 d-flex align-items-center gap-2">
                <img src="<?= e(url('marca/firma')) ?>" alt="Firma actual" style="max-height:48px">
                <div class="form-check"><input class="form-check-input" type="checkbox" id="remove_signature" name="remove_signature" value="1">
                  <label class="form-check-label" for="remove_signature">Quitar</label></div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-12"><p class="cell-sub mb-0">Se usan en el membrete y la firma de los documentos impresos o exportados a PDF.</p></div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Valores predeterminados</h2></div>
        <div class="card-body row g-3">
          <div class="col-6"><label class="form-label" for="session_fee">Valor por sesión *</label>
            <input type="number" step="0.01" min="0" class="form-control" id="session_fee" name="session_fee" required value="<?= $s('session_fee', '0') ?>"></div>
          <div class="col-6"><label class="form-label" for="session_duration">Duración (min) *</label>
            <input type="number" min="10" max="480" step="5" class="form-control" id="session_duration" name="session_duration" required value="<?= $s('session_duration', '45') ?>"></div>
          <div class="col-6"><label class="form-label" for="review_every_sessions">Sesiones para revisión *</label>
            <input type="number" min="1" max="100" class="form-control" id="review_every_sessions" name="review_every_sessions" required value="<?= $s('review_every_sessions', '8') ?>">
            <div class="form-text">Alerta de evaluación de progreso.</div></div>
          <div class="col-6"><label class="form-label" for="inactivity_days">Días para inactividad *</label>
            <input type="number" min="1" max="365" class="form-control" id="inactivity_days" name="inactivity_days" required value="<?= $s('inactivity_days', '30') ?>"></div>
          <div class="col-6"><label class="form-label" for="currency">Moneda *</label>
            <select class="form-select" id="currency" name="currency" required><?= select_options('currency', $settings['currency'] ?? 'USD') ?></select></div>
          <div class="col-6"><label class="form-label" for="file_number_prefix">Prefijo de expediente *</label>
            <input class="form-control" id="file_number_prefix" name="file_number_prefix" maxlength="10" required value="<?= $s('file_number_prefix', 'PSI') ?>">
            <div class="form-text">Ej.: <?= e(($settings['file_number_prefix'] ?? 'PSI') . '-' . date('Y') . '-0001') ?></div></div>
          <div class="col-12"><label class="form-label" for="timezone">Zona horaria</label>
            <select class="form-select" id="timezone" name="timezone">
              <?php foreach (['America/Guayaquil', 'America/Bogota', 'America/Lima', 'America/Mexico_City', 'America/Santiago', 'America/Buenos_Aires', 'Europe/Madrid'] as $tz): ?>
                <option value="<?= e($tz) ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2 class="card-title">Reglas de paquetes</h2></div>
        <div class="card-body">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="consume_package_on_no_show" name="consume_package_on_no_show" value="1" <?= ($settings['consume_package_on_no_show'] ?? '0') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="consume_package_on_no_show">Descontar sesión del paquete cuando el paciente no asiste</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="consume_package_on_cancel" name="consume_package_on_cancel" value="1" <?= ($settings['consume_package_on_cancel'] ?? '0') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="consume_package_on_cancel">Descontar también en cancelaciones o reprogramaciones</label>
          </div>
          <p class="cell-sub mt-2 mb-0">De forma predeterminada solo se descuenta una sesión cuando la cita fue atendida.</p>
        </div>
        <div class="sticky-actions"><button class="btn btn-primary w-100">Guardar configuración</button></div>
      </div>
    </div>
  </div>
</form>
