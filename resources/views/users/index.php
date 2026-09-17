<div class="page-head">
  <div><h1>Usuarios</h1><p class="lead-sm">Acceso al sistema y rol asignado.</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-user"><i class="bi bi-person-plus me-1"></i> Nuevo usuario</button>
</div>
<?= App\Core\View::fetch('partials/settings_nav') ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th class="d-none d-md-table-cell">Último acceso</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="cell-title"><?= e($u['name']) ?></span><div class="cell-sub">@<?= e($u['username']) ?><?= $u['professional_title'] ? ' · ' . e($u['professional_title']) : '' ?></div></td>
          <td class="cell-sub"><?= e($u['email']) ?></td>
          <td><span class="badge-soft badge-<?= ['administrador' => 'primary', 'psicologo' => 'success', 'asistente' => 'info'][$u['role_slug']] ?? 'neutral' ?>"><?= e($u['role_name']) ?></span></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($u['last_login_at'], true)) ?></td>
          <td>
            <?= $u['is_active'] ? '<span class="badge-soft badge-success">Activo</span>' : '<span class="badge-soft badge-neutral">Inactivo</span>' ?>
            <?php if ($u['must_change_password']): ?><div class="cell-sub">Debe cambiar contraseña</div><?php endif; ?>
            <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?><div class="cell-sub text-danger">Bloqueado temporalmente</div><?php endif; ?>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-user-edit"
                    data-action="<?= e(url('usuarios/' . $u['id'])) ?>"
                    data-fill='<?= e(json_encode(['name' => $u['name'], 'email' => $u['email'], 'role_id' => $u['role_id'],
                        'professional_title' => $u['professional_title'], 'registration_number' => $u['registration_number'],
                        'phone' => $u['phone'], 'is_active' => $u['is_active']])) ?>'><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modal-user" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('usuarios')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nuevo usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-7"><label class="form-label" for="nu-name">Nombre completo *</label>
            <input class="form-control" id="nu-name" name="name" maxlength="120" required></div>
          <div class="col-md-5"><label class="form-label" for="nu-username">Usuario *</label>
            <input class="form-control" id="nu-username" name="username" maxlength="60" pattern="[a-zA-Z0-9._-]+" required></div>
          <div class="col-md-7"><label class="form-label" for="nu-email">Correo *</label>
            <input type="email" class="form-control" id="nu-email" name="email" maxlength="150" required></div>
          <div class="col-md-5"><label class="form-label" for="nu-role">Rol *</label>
            <select class="form-select" id="nu-role" name="role_id" required>
              <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-md-6"><label class="form-label" for="nu-title">Título profesional</label>
            <input class="form-control" id="nu-title" name="professional_title" maxlength="120"></div>
          <div class="col-md-6"><label class="form-label" for="nu-reg">Registro profesional</label>
            <input class="form-control" id="nu-reg" name="registration_number" maxlength="60"></div>
          <div class="col-md-6"><label class="form-label" for="nu-phone">Teléfono</label>
            <input class="form-control" id="nu-phone" name="phone" maxlength="30"></div>
          <div class="col-md-6"><label class="form-label" for="nu-pass">Contraseña temporal *</label>
            <div class="input-group">
              <input class="form-control" id="nu-pass" name="password" minlength="10" required>
              <button class="btn btn-light border" type="button" id="gen-pass">Generar</button>
            </div>
            <div class="form-text">Mínimo 10 caracteres. Entréguela por un canal seguro.</div></div>
          <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" id="nu-change" name="must_change_password" value="1" checked>
            <label class="form-check-label" for="nu-change">Obligar a cambiar la contraseña en el primer acceso</label></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Crear usuario</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-user-edit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Editar usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-7"><label class="form-label" for="eu-name">Nombre completo *</label>
            <input class="form-control" id="eu-name" name="name" maxlength="120" required></div>
          <div class="col-md-5"><label class="form-label" for="eu-role">Rol *</label>
            <select class="form-select" id="eu-role" name="role_id" required>
              <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-md-7"><label class="form-label" for="eu-email">Correo *</label>
            <input type="email" class="form-control" id="eu-email" name="email" maxlength="150" required></div>
          <div class="col-md-5"><label class="form-label" for="eu-phone">Teléfono</label>
            <input class="form-control" id="eu-phone" name="phone" maxlength="30"></div>
          <div class="col-md-6"><label class="form-label" for="eu-title">Título profesional</label>
            <input class="form-control" id="eu-title" name="professional_title" maxlength="120"></div>
          <div class="col-md-6"><label class="form-label" for="eu-reg">Registro profesional</label>
            <input class="form-control" id="eu-reg" name="registration_number" maxlength="60"></div>
          <div class="col-12"><label class="form-label" for="eu-pass">Nueva contraseña <span class="cell-sub">(dejar vacío para no cambiarla)</span></label>
            <input class="form-control" id="eu-pass" name="new_password" minlength="10"></div>
          <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" id="eu-active" name="is_active" value="1">
            <label class="form-check-label" for="eu-active">Usuario activo</label></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<?php App\Core\View::start('scripts'); ?>
<script>
  document.getElementById('gen-pass').addEventListener('click', () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    const values = crypto.getRandomValues(new Uint32Array(14));
    const pass = Array.from(values, (v) => chars[v % chars.length]).join('');
    const field = document.getElementById('nu-pass');
    field.type = 'text'; field.value = pass;
    SGC.toast('Contraseña generada. Cópiela antes de guardar.', 'info');
  });
</script>
<?php App\Core\View::stop(); ?>
