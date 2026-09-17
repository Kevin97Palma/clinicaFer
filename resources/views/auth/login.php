<?php
$brand = setting('brand_name', 'Consulta Psicológica');
$hasLogo = setting('logo_file', '') !== '';
?>
<div class="auth-wrap">
  <section class="auth-art">
    <div class="d-flex align-items-center gap-2">
      <div class="brand-mark"><?php if ($hasLogo): ?><img src="<?= e(url('marca/logo')) ?>" alt=""><?php else: ?><?= e(initials($brand)) ?><?php endif; ?></div>
      <div>
        <div class="brand-name"><?= e($brand) ?></div>
        <div class="brand-sub"><?= e(setting('professional_name', 'Gestión clínica')) ?></div>
      </div>
    </div>
    <div>
      <h1>Registrar una sola vez, acompañar mejor.</h1>
      <p class="mt-3 mb-0 text-muted" style="max-width:46ch">Agenda, expediente, sesiones, planes terapéuticos y pagos en un mismo lugar, pensados para la consulta infantil y adolescente.</p>
    </div>
    <div class="small text-muted"><i class="bi bi-shield-lock me-1"></i> Acceso restringido · información clínica confidencial</div>
  </section>

  <section class="auth-panel">
    <div class="auth-card">
      <h2 class="h3 mb-1">Iniciar sesión</h2>
      <p class="text-muted mb-4">Ingrese con su correo o usuario.</p>

      <?php foreach (take_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> py-2 small" role="alert"><?= e($f['message']) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(url('login')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="login">Correo o usuario</label>
          <input class="form-control form-control-lg" id="login" name="login" value="<?= e(old('login')) ?>" required autocomplete="username" autofocus maxlength="150">
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Contraseña</label>
          <div class="input-group">
            <input class="form-control form-control-lg" id="password" name="password" type="password" required autocomplete="current-password" maxlength="200">
            <button class="btn btn-light border" type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password'" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button class="btn btn-primary btn-lg w-100">Entrar</button>
      </form>
    </div>
  </section>
</div>
<script>
document.querySelector('form.needs-validation').addEventListener('submit', function (e) {
  if (!this.checkValidity()) { e.preventDefault(); this.classList.add('was-validated'); }
});
</script>
