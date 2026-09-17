<?php
/** @var string $content */
$user = auth_user();
$brand = setting('brand_name', 'Consulta Psicológica');
$profName = setting('professional_name', '');
$hasLogo = setting('logo_file', '') !== '';
\App\Services\AlertService::runIfDue();
$alertCount = can('alerts.view') ? count(\App\Services\AlertService::openForUser(99)) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="app-url" content="<?= e(url()) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e(($title ?? 'Inicio') . ' · ' . $brand) ?></title>
  <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
  <?= App\Core\View::section('head') ?>
</head>
<body>
<a class="visually-hidden-focusable position-absolute p-2 bg-white" href="#main">Saltar al contenido</a>
<div class="app-shell">
  <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menú principal">
    <div class="brand">
      <div class="brand-mark">
        <?php if ($hasLogo): ?><img src="<?= e(url('marca/logo')) ?>" alt=""><?php else: ?><?= e(initials($brand)) ?><?php endif; ?>
      </div>
      <div class="min-w-0">
        <div class="brand-name text-truncate"><?= e($brand) ?></div>
        <div class="brand-sub text-truncate"><?= e($profName ?: 'Gestión clínica') ?></div>
      </div>
      <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar menú"></button>
    </div>
    <nav class="nav-side">
      <?php if (can('dashboard.view')): ?><a class="<?= nav_active('/') ?>" href="<?= e(url('/')) ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a><?php endif; ?>
      <?php if (can('agenda.view')): ?><a class="<?= nav_active('/agenda') ?>" href="<?= e(url('agenda')) ?>"><i class="bi bi-calendar3"></i> Agenda</a><?php endif; ?>
      <?php if (can('patients.view')): ?><a class="<?= nav_active('/pacientes') ?>" href="<?= e(url('pacientes')) ?>"><i class="bi bi-people"></i> Pacientes</a><?php endif; ?>
      <?php if (can('clinical.view')): ?>
        <div class="nav-group">Clínico</div>
        <a class="<?= nav_active('/sesiones') ?>" href="<?= e(url('sesiones')) ?>"><i class="bi bi-journal-text"></i> Sesiones</a>
      <?php endif; ?>
      <?php if (can('evaluations.view')): ?><a class="<?= nav_active('/evaluaciones') ?>" href="<?= e(url('evaluaciones')) ?>"><i class="bi bi-clipboard2-pulse"></i> Evaluaciones</a><?php endif; ?>
      <?php if (can('clinical.view')): ?><a class="<?= nav_active('/planes') ?>" href="<?= e(url('planes')) ?>"><i class="bi bi-bullseye"></i> Planes terapéuticos</a><?php endif; ?>
      <?php if (can('documents.view')): ?><a class="<?= nav_active('/documentos') . nav_active('/plantillas') ?>" href="<?= e(url('documentos')) ?>"><i class="bi bi-file-earmark-text"></i> Documentos</a><?php endif; ?>
      <?php if (can('payments.view')): ?>
        <div class="nav-group">Administración</div>
        <a class="<?= nav_active('/pagos') . nav_active('/paquetes') ?>" href="<?= e(url('pagos')) ?>"><i class="bi bi-wallet2"></i> Pagos</a>
      <?php endif; ?>
      <?php if (can('reports.view')): ?><a class="<?= nav_active('/reportes') ?>" href="<?= e(url('reportes')) ?>"><i class="bi bi-bar-chart-line"></i> Reportes</a><?php endif; ?>
      <?php if (can('alerts.view')): ?><a class="<?= nav_active('/alertas') ?>" href="<?= e(url('alertas')) ?>"><i class="bi bi-bell"></i> Alertas <?php if ($alertCount): ?><span class="ms-auto badge rounded-pill" style="background:#c47a53"><?= $alertCount ?></span><?php endif; ?></a><?php endif; ?>
      <?php if (can('settings.manage') || can('users.manage') || can('evaluations.manage') || can('templates.manage')): ?>
        <a class="<?= nav_active('/configuracion') . nav_active('/usuarios') . nav_active('/auditoria') . nav_active('/respaldos') ?>"
           href="<?= e(url(can('settings.manage') ? 'configuracion' : (can('users.manage') ? 'usuarios' : 'configuracion/instrumentos'))) ?>"><i class="bi bi-gear"></i> Configuración</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-foot">
      <i class="bi bi-shield-lock me-1"></i> Información clínica confidencial
    </div>
  </aside>

  <div class="app-main">
    <header class="topbar">
      <div class="topbar-inner">
        <button class="btn btn-icon btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Abrir menú"><i class="bi bi-list fs-5"></i></button>
        <?php if (can('patients.view')): ?>
          <div class="quick-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="quick-search" type="search" class="form-control" placeholder="Buscar paciente, cédula o expediente…" autocomplete="off" aria-label="Buscar paciente">
            <div id="quick-search-results" class="search-results" hidden></div>
          </div>
        <?php endif; ?>
        <div class="ms-auto d-flex align-items-center gap-2">
          <?php if (can('agenda.manage')): ?>
            <a href="<?= e(url('agenda', ['nueva' => 1])) ?>" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg"></i> Nueva cita</a>
          <?php endif; ?>
          <?php if (can('alerts.view')): ?>
            <a href="<?= e(url('alertas')) ?>" class="btn btn-icon btn-light position-relative" aria-label="Alertas (<?= $alertCount ?>)">
              <i class="bi bi-bell"></i><?php if ($alertCount): ?><span class="bell-count"><?= $alertCount > 99 ? '99+' : $alertCount ?></span><?php endif; ?>
            </a>
          <?php endif; ?>
          <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 px-2" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="avatar"><?= e(initials($user['name'])) ?></span>
              <span class="d-none d-sm-block text-start lh-sm">
                <span class="d-block fw-semibold small"><?= e($user['name']) ?></span>
                <span class="d-block cell-sub"><?= e($user['role_name']) ?></span>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><a class="dropdown-item" href="<?= e(url('perfil/password')) ?>"><i class="bi bi-key me-2"></i>Cambiar contraseña</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?>
                  <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </header>

    <main id="main" class="content">
      <?php foreach (take_flashes() as $f): ?>
        <span hidden data-flash="<?= e($f['type']) ?>" data-message="<?= e($f['message']) ?>"></span>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>

<div class="modal fade" id="confirm-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body p-4 text-center">
        <div class="stat-icon tone-warning mx-auto mb-3"><i class="bi bi-exclamation-lg"></i></div>
        <p class="mb-0" data-confirm-text></p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" data-confirm-ok>Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?= App\Core\View::section('scripts') ?>
</body>
</html>
