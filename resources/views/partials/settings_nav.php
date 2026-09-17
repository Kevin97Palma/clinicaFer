<ul class="nav nav-pills gap-1 mb-3">
  <?php if (can('settings.manage')): ?>
    <li class="nav-item"><a class="nav-link <?= request_path() === '/configuracion' ? 'active' : '' ?>" href="<?= e(url('configuracion')) ?>"><i class="bi bi-sliders me-1"></i> General</a></li>
    <li class="nav-item"><a class="nav-link <?= request_path() === '/configuracion/roles' ? 'active' : '' ?>" href="<?= e(url('configuracion/roles')) ?>"><i class="bi bi-shield-check me-1"></i> Roles y permisos</a></li>
  <?php endif; ?>
  <?php if (can('evaluations.manage')): ?>
    <li class="nav-item"><a class="nav-link <?= request_path() === '/configuracion/instrumentos' ? 'active' : '' ?>" href="<?= e(url('configuracion/instrumentos')) ?>"><i class="bi bi-rulers me-1"></i> Instrumentos</a></li>
  <?php endif; ?>
  <?php if (can('templates.manage')): ?>
    <li class="nav-item"><a class="nav-link <?= str_starts_with(request_path(), '/plantillas') ? 'active' : '' ?>" href="<?= e(url('plantillas')) ?>"><i class="bi bi-layout-text-window me-1"></i> Plantillas</a></li>
  <?php endif; ?>
  <?php if (can('users.manage')): ?>
    <li class="nav-item"><a class="nav-link <?= str_starts_with(request_path(), '/usuarios') ? 'active' : '' ?>" href="<?= e(url('usuarios')) ?>"><i class="bi bi-people me-1"></i> Usuarios</a></li>
  <?php endif; ?>
  <?php if (can('audit.view')): ?>
    <li class="nav-item"><a class="nav-link <?= str_starts_with(request_path(), '/auditoria') ? 'active' : '' ?>" href="<?= e(url('auditoria')) ?>"><i class="bi bi-clipboard-check me-1"></i> Auditoría</a></li>
  <?php endif; ?>
  <?php if (can('backups.manage')): ?>
    <li class="nav-item"><a class="nav-link <?= str_starts_with(request_path(), '/respaldos') ? 'active' : '' ?>" href="<?= e(url('respaldos')) ?>"><i class="bi bi-database me-1"></i> Respaldos</a></li>
  <?php endif; ?>
</ul>
