<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="page-head"><div><h1>Cambiar contraseña</h1>
      <?php if ($forced): ?><p class="lead-sm">Por seguridad, establezca una contraseña personal antes de continuar.</p><?php endif; ?></div></div>
    <div class="card">
      <form method="post" action="<?= e(url('perfil/password')) ?>" class="card-body needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="current">Contraseña actual</label>
          <input type="password" class="form-control" id="current" name="current" required autocomplete="current-password">
          <?= field_error('current') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Nueva contraseña</label>
          <input type="password" class="form-control" id="password" name="password" required minlength="10" autocomplete="new-password">
          <div class="form-text">Mínimo 10 caracteres, con letras y números.</div>
          <?= field_error('password') ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
          <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
          <?= field_error('password_confirmation') ?>
        </div>
        <button class="btn btn-primary w-100">Guardar contraseña</button>
      </form>
    </div>
  </div>
</div>
