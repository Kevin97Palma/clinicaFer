<?php
$map = [];
foreach ($assigned as $a) {
    $map[(int) $a['role_id']][(int) $a['permission_id']] = true;
}
$byModule = [];
foreach ($permissions as $p) {
    $byModule[$p['module']][] = $p;
}
?>
<div class="page-head"><div><h1>Roles y permisos</h1>
  <p class="lead-sm">Defina qué puede hacer cada rol. El rol Administrador siempre tiene acceso completo.</p></div></div>
<?= App\Core\View::fetch('partials/settings_nav') ?>

<form method="post" action="<?= e(url('configuracion/roles')) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="min-width:280px">Permiso</th>
            <?php foreach ($roles as $r): ?>
              <th class="text-center"><?= e($r['name']) ?><div class="cell-sub fw-normal"><?= e($r['description']) ?></div></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($byModule as $module => $perms): ?>
          <tr><td colspan="<?= count($roles) + 1 ?>" class="small-caps" style="background: var(--c-surface-2)"><?= e(ucfirst($module)) ?></td></tr>
          <?php foreach ($perms as $p): ?>
            <tr>
              <td><span class="cell-title"><?= e($p['name']) ?></span><div class="cell-sub"><?= e($p['slug']) ?></div></td>
              <?php foreach ($roles as $r): $isAdmin = $r['slug'] === 'administrador'; ?>
                <td class="text-center">
                  <input class="form-check-input" type="checkbox" name="permissions[<?= (int) $r['id'] ?>][]" value="<?= (int) $p['id'] ?>"
                         <?= $isAdmin || isset($map[(int) $r['id']][(int) $p['id']]) ? 'checked' : '' ?> <?= $isAdmin ? 'disabled' : '' ?>
                         aria-label="<?= e($p['name'] . ' para ' . $r['name']) ?>">
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="sticky-actions"><button class="btn btn-primary">Guardar permisos</button></div>
  </div>
</form>
