<?php
/**
 * SGC — Layout: Header + Sidebar rediseñados (v2)
 * Variables esperadas: $pageTitle, $activeMenu, $breadcrumb[], $pageActions
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

requireAuth();

// Generar notificaciones automáticas (una vez por sesión)
require_once dirname(__DIR__) . '/check_notifications.php';
checkNotificaciones();

// Cargar conteo y lista de notificaciones no leídas
$notifCount = 0;
$notifList  = [];
try {
    $pdo = db();
    $stN = $pdo->prepare(
        "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :uid AND leida = 0"
    );
    $stN->execute([':uid' => $_SESSION['user_id']]);
    $notifCount = (int) $stN->fetchColumn();

    $stNL = $pdo->prepare(
        "SELECT * FROM notificaciones WHERE usuario_id = :uid AND leida = 0
         ORDER BY created_at DESC LIMIT 5"
    );
    $stNL->execute([':uid' => $_SESSION['user_id']]);
    $notifList = $stNL->fetchAll();
} catch (Throwable $e) {
    error_log('[SGC-NOTIF] ' . $e->getMessage());
}

// Variables de sesión
$userName      = $_SESSION['user_nombre']  ?? '';
$userLastName  = $_SESSION['user_apellido'] ?? '';
$userEmail     = $_SESSION['user_email']   ?? '';
$clinicaNombre = $_SESSION['clinica_activa_nombre'] ?? 'Clínica';
$rolActivo     = $_SESSION['rol_activo'] ?? '';
$pageTitle     = $pageTitle ?? 'SGC';
$activeMenu    = $activeMenu ?? '';
$breadcrumb    = $breadcrumb ?? [];

// Iniciales del avatar
$iniciales = strtoupper(
    substr($userName, 0, 1) . substr($userLastName, 0, 1)
);

// Etiqueta del rol
$rolLabels = [
    'superadmin'    => 'Super Admin',
    'gerente'       => 'Gerente',
    'supervisor'    => 'Supervisor',
    'operativo'     => 'Psicólogo',
    'representante' => 'Representante',
];
$rolLabel = $rolLabels[$rolActivo] ?? ucfirst($rolActivo);

// Contar alertas para badges del sidebar (pacientes con tareas vencidas)
$alertasPacientes = 0;
$alertasCronogramas = 0;
try {
    if (tienePermiso('pacientes.ver_lista')) {
        $cid = clinicaId();
        $uid = usuarioId();

        // Tareas vencidas propias
        $stA = $pdo->prepare(
            "SELECT COUNT(DISTINCT tc.paciente_id)
             FROM tareas_cronograma tc
             JOIN cronogramas cr ON cr.id = tc.cronograma_id
             WHERE cr.clinica_id = :cid
             AND tc.estado = 'pendiente'
             AND tc.fecha_programada < CURDATE()"
             . ($rolActivo === ROL_OPERATIVO ? ' AND tc.responsable_id = :uid' : '')
        );
        $pA = [':cid' => $cid];
        if ($rolActivo === ROL_OPERATIVO) $pA[':uid'] = $uid;
        $stA->execute($pA);
        $alertasPacientes = (int) $stA->fetchColumn();

        // Cronogramas con tareas vencidas
        $stC = $pdo->prepare(
            "SELECT COUNT(DISTINCT cr.id)
             FROM cronogramas cr
             JOIN tareas_cronograma tc ON tc.cronograma_id = cr.id
             WHERE cr.clinica_id = :cid
             AND tc.estado = 'pendiente'
             AND tc.fecha_programada < CURDATE()"
             . ($rolActivo === ROL_OPERATIVO ? ' AND cr.operativo_id = :uid' : '')
        );
        $pC = [':cid' => $cid];
        if ($rolActivo === ROL_OPERATIVO) $pC[':uid'] = $uid;
        $stC->execute($pC);
        $alertasCronogramas = (int) $stC->fetchColumn();
    }
} catch (Throwable $e) {
    // Silenciar — las alertas son opcionales
}

// Mapa de íconos para notificaciones
$notifIconos = [
    'tarea_vencida' => 'exclamation-triangle-fill text-danger',
    'cita_proxima'  => 'calendar-check-fill text-primary',
    'sin_actividad' => 'person-x-fill text-warning',
    'sistema'       => 'info-circle-fill text-info',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — SGC</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg">
    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SGC Design System -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/sgc-ui.css">
    <!-- SGC App (legacy overrides) -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>

<!-- ══════════════════════════════════════════════════════ -->
<!-- SIDEBAR                                                -->
<!-- ══════════════════════════════════════════════════════ -->
<aside class="sgc-sidebar" id="sgc-sidebar">

    <!-- Brand -->
    <div class="sgc-sidebar__brand">
        <div class="sgc-sidebar__brand-icon">
            <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <div style="min-width:0">
            <span class="sgc-sidebar__brand-name">SGC Clínica</span>
            <span class="sgc-sidebar__brand-clinic"><?= e($clinicaNombre) ?></span>
        </div>
    </div>

    <!-- Usuario -->
    <div class="sgc-sidebar__user">
        <div class="sgc-avatar sgc-avatar--md" style="flex-shrink:0">
            <?= e($iniciales) ?>
        </div>
        <div style="min-width:0">
            <div class="sgc-sidebar__user-name">
                <?= e($userName . ' ' . $userLastName) ?>
            </div>
            <div class="sgc-sidebar__user-role"><?= e($rolLabel) ?></div>
        </div>
    </div>

    <!-- Navegación principal -->
    <nav class="sgc-sidebar__nav">

        <span class="sgc-sidebar__section">Principal</span>

        <!-- Mi día / Dashboard -->
        <a href="<?= APP_URL ?>/modules/dashboard/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 sgc-sidebar__icon"></i>
            <span>Mi día</span>
        </a>

        <!-- Pacientes -->
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <a href="<?= APP_URL ?>/modules/pacientes/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'pacientes' ? 'active' : '' ?>">
            <i class="bi bi-people sgc-sidebar__icon"></i>
            <span>Mis pacientes</span>
            <?php if ($alertasPacientes > 0): ?>
            <span class="sgc-sidebar__badge sgc-sidebar__badge--warning">
                <?= $alertasPacientes > 9 ? '9+' : $alertasPacientes ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- Agenda -->
        <?php if (tienePermiso('citas.gestionar') || tienePermiso('citas.ver_propias')): ?>
        <a href="<?= APP_URL ?>/modules/citas/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'citas' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check sgc-sidebar__icon"></i>
            <span>Agenda</span>
        </a>
        <?php endif; ?>

        <!-- Cronogramas -->
        <?php if (tienePermiso('cronogramas.ver_propio')): ?>
        <a href="<?= APP_URL ?>/modules/cronogramas/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'cronogramas' ? 'active' : '' ?>">
            <i class="bi bi-kanban sgc-sidebar__icon"></i>
            <span>Cronogramas</span>
            <?php if ($alertasCronogramas > 0): ?>
            <span class="sgc-sidebar__badge sgc-sidebar__badge--danger">
                <?= $alertasCronogramas > 9 ? '9+' : $alertasCronogramas ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- Historial -->
        <?php if (tienePermiso('historial.ver')): ?>
        <a href="<?= APP_URL ?>/modules/pacientes/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'historial' ? 'active' : '' ?>">
            <i class="bi bi-file-medical sgc-sidebar__icon"></i>
            <span>Historial clínico</span>
        </a>
        <?php endif; ?>

        <!-- Consentimientos -->
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <a href="<?= APP_URL ?>/modules/consentimientos/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'consentimientos' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-check sgc-sidebar__icon"></i>
            <span>Consentimientos</span>
        </a>
        <?php endif; ?>

        <div class="sgc-sidebar__divider"></div>
        <span class="sgc-sidebar__section">Personal</span>

        <!-- Mi rendimiento -->
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <a href="<?= APP_URL ?>/modules/mi-rendimiento/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'mi-rendimiento' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-steps sgc-sidebar__icon"></i>
            <span>Mi rendimiento</span>
        </a>
        <?php endif; ?>

        <!-- Notificaciones -->
        <a href="<?= APP_URL ?>/modules/notificaciones/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'notificaciones' ? 'active' : '' ?>">
            <i class="bi bi-bell sgc-sidebar__icon"></i>
            <span>Notificaciones</span>
            <?php if ($notifCount > 0): ?>
            <span class="sgc-sidebar__badge sgc-sidebar__badge--info">
                <?= $notifCount > 9 ? '9+' : $notifCount ?>
            </span>
            <?php endif; ?>
        </a>

        <!-- Reportes -->
        <?php if (tienePermiso('reportes.generar')): ?>
        <a href="<?= APP_URL ?>/modules/reportes/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'reportes' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line sgc-sidebar__icon"></i>
            <span>Reportes</span>
        </a>
        <?php endif; ?>

        <!-- Administración (solo gerente+) -->
        <?php if (tieneNivel(ROL_GERENTE)): ?>
        <div class="sgc-sidebar__divider"></div>
        <span class="sgc-sidebar__section">Administración</span>

        <?php if (tienePermiso('usuarios.ver')): ?>
        <a href="<?= APP_URL ?>/modules/usuarios/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'usuarios' ? 'active' : '' ?>">
            <i class="bi bi-person-gear sgc-sidebar__icon"></i>
            <span>Usuarios</span>
        </a>
        <?php endif; ?>

        <?php if (tienePermiso('clinicas.ver')): ?>
        <a href="<?= APP_URL ?>/modules/clinicas/index.php"
           class="sgc-sidebar__link <?= $activeMenu === 'clinicas' ? 'active' : '' ?>">
            <i class="bi bi-hospital sgc-sidebar__icon"></i>
            <span>Clínicas</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

    </nav>

    <!-- Footer del sidebar -->
    <div class="sgc-sidebar__footer">
        <?php if (count($_SESSION['clinicas_disponibles'] ?? []) > 1): ?>
        <a href="#" class="sgc-sidebar__link" id="btn-cambiar-clinica">
            <i class="bi bi-arrow-left-right sgc-sidebar__icon"></i>
            <span>Cambiar clínica</span>
        </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/api/auth/logout.php" class="sgc-sidebar__link"
           style="color: var(--sgc-danger)">
            <i class="bi bi-box-arrow-right" style="color:var(--sgc-danger);font-size:15px;flex-shrink:0"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>

</aside>

<!-- Overlay móvil -->
<div class="sgc-overlay" id="sgc-overlay"></div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- TOPBAR                                                 -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="sgc-topbar">

    <!-- Toggle sidebar (solo móvil) -->
    <button class="sgc-topbar__toggle" id="sgc-sidebar-toggle" aria-label="Menú">
        <i class="bi bi-list"></i>
    </button>

    <!-- Breadcrumb -->
    <div class="sgc-topbar__left">
        <nav class="sgc-breadcrumb" aria-label="Ruta">
            <a href="<?= APP_URL ?>/modules/dashboard/index.php"
               class="sgc-breadcrumb__item" title="Inicio">
                <i class="bi bi-house" style="font-size:13px"></i>
            </a>
            <?php foreach ($breadcrumb as $i => $crumb):
                $isLast = $i === count($breadcrumb) - 1;
                $label  = is_array($crumb) ? ($crumb['label'] ?? $crumb[0] ?? '') : $crumb;
                $url    = is_array($crumb) ? ($crumb['url']   ?? $crumb[1] ?? null) : null;
            ?>
            <span class="sgc-breadcrumb__sep">/</span>
            <?php if (!$isLast && $url): ?>
            <a href="<?= e($url) ?>" class="sgc-breadcrumb__item"><?= e($label) ?></a>
            <?php else: ?>
            <span class="sgc-breadcrumb__item sgc-breadcrumb__item--current"><?= e($label) ?></span>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php if (empty($breadcrumb) && $pageTitle): ?>
            <span class="sgc-breadcrumb__sep">/</span>
            <span class="sgc-breadcrumb__item sgc-breadcrumb__item--current"><?= e($pageTitle) ?></span>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Acciones topbar -->
    <div class="sgc-topbar__right">

        <!-- Campana de notificaciones -->
        <div class="dropdown">
            <button class="sgc-topbar__bell" data-bs-toggle="dropdown"
                    aria-label="Notificaciones" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <?php if ($notifCount > 0): ?>
                <span class="sgc-topbar__bell-badge">
                    <?= $notifCount > 9 ? '9+' : $notifCount ?>
                </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0"
                 style="width:320px;max-height:420px;overflow-y:auto;
                        border:1px solid var(--sgc-border);border-radius:10px;
                        box-shadow:0 6px 24px rgba(0,0,0,.1)">
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:12px 14px;border-bottom:1px solid var(--sgc-border)">
                    <span style="font-size:13px;font-weight:500">Notificaciones</span>
                    <?php if ($notifCount > 0): ?>
                    <button class="sgc-btn-ghost" style="font-size:11px;padding:3px 6px"
                            id="btn-marcar-todas">
                        Marcar todas leídas
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (empty($notifList)): ?>
                <div style="text-align:center;padding:24px 16px;color:var(--sgc-text-muted)">
                    <i class="bi bi-bell-slash" style="font-size:28px;display:block;margin-bottom:8px;
                       color:var(--sgc-text-light)"></i>
                    <span style="font-size:12px">Sin notificaciones nuevas</span>
                </div>
                <?php else: ?>
                <?php foreach ($notifList as $n):
                    $ico = $notifIconos[$n['tipo']] ?? 'bell-fill text-secondary';
                ?>
                <a href="<?= APP_URL ?>/modules/notificaciones/index.php"
                   class="dropdown-item d-flex gap-2 py-2 px-3"
                   style="border-bottom:1px solid var(--sgc-border);font-size:12px"
                   onclick="sgcMarcarLeida(<?= (int)$n['id'] ?>); return true;">
                    <i class="bi bi-<?= e($ico) ?>" style="margin-top:2px;flex-shrink:0"></i>
                    <div style="min-width:0">
                        <div style="white-space:normal;line-height:1.3">
                            <?= e(truncar($n['mensaje'], 75)) ?>
                        </div>
                        <div style="font-size:10px;color:var(--sgc-text-light);margin-top:2px">
                            <?= formatearFecha($n['created_at'], 'd/m/Y H:i') ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
                <a href="<?= APP_URL ?>/modules/notificaciones/index.php"
                   class="dropdown-item text-center"
                   style="font-size:12px;padding:10px;color:var(--sgc-primary)">
                    Ver todas las notificaciones
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botón nuevo paciente (supervisor+ y operativo) -->
        <?php if (tienePermiso('pacientes.registrar')): ?>
        <a href="<?= APP_URL ?>/modules/pacientes/registro.php"
           class="sgc-btn-primary" style="font-size:12px;padding:6px 12px">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-sm-inline">Nuevo paciente</span>
        </a>
        <?php endif; ?>

        <!-- Avatar usuario con dropdown -->
        <div class="dropdown">
            <button class="sgc-avatar sgc-avatar--sm"
                    style="cursor:pointer;border:none;font-size:11px"
                    data-bs-toggle="dropdown" title="<?= e($userName . ' ' . $userLastName) ?>">
                <?= e($iniciales) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end"
                style="font-size:13px;border:1px solid var(--sgc-border);
                       border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.1)">
                <li>
                    <div style="padding:10px 14px;border-bottom:1px solid var(--sgc-border)">
                        <div style="font-weight:500;font-size:13px">
                            <?= e($userName . ' ' . $userLastName) ?>
                        </div>
                        <div style="font-size:11px;color:var(--sgc-text-muted)">
                            <?= e($userEmail) ?> · <?= e($rolLabel) ?>
                        </div>
                    </div>
                </li>
                <?php if (count($_SESSION['clinicas_disponibles'] ?? []) > 1): ?>
                <li>
                    <a class="dropdown-item" href="#" id="btn-cambiar-clinica2">
                        <i class="bi bi-arrow-left-right me-2"></i>Cambiar clínica
                    </a>
                </li>
                <li><hr class="dropdown-divider" style="margin:4px 0"></li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/api/auth/logout.php"
                       style="color:var(--sgc-danger)">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                    </a>
                </li>
            </ul>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- CONTENIDO PRINCIPAL                                    -->
<!-- ══════════════════════════════════════════════════════ -->
<main class="sgc-main" id="main-content">
    <div class="container-fluid py-3 px-3 px-lg-4">

<?php if (!empty($pageActions)): ?>
<!-- Acciones de página (complemento al topbar) -->
<div class="d-flex justify-content-end mb-3">
    <?= $pageActions ?>
</div>
<?php endif; ?>

<script>
/* Sidebar toggle inline (antes de DOMContentLoaded para evitar flash) */
(function() {
    var sidebar  = document.getElementById('sgc-sidebar');
    var overlay  = document.getElementById('sgc-overlay');
    var btnToggle = document.getElementById('sgc-sidebar-toggle');

    if (btnToggle && sidebar) {
        btnToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
})();

/* Marcar notificación como leída */
function sgcMarcarLeida(id) {
    fetch('<?= APP_URL ?>/api/notificaciones/marcar-leida.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    }).catch(function() {});
}

/* Marcar todas como leídas */
document.addEventListener('DOMContentLoaded', function() {
    var btnTodas = document.getElementById('btn-marcar-todas');
    if (btnTodas) {
        btnTodas.addEventListener('click', function() {
            fetch('<?= APP_URL ?>/api/notificaciones/marcar-leida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ all: true })
            }).then(function() { location.reload(); }).catch(function() {});
        });
    }

    /* Compatibilidad: exponer función legacy para código existente */
    if (typeof marcarLeida === 'undefined') {
        window.marcarLeida = sgcMarcarLeida;
    }
});
</script>
