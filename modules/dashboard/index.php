<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
requireAuth();

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();
$rol       = rolActivo();

// ── KPIs básicos ──────────────────────────────────────────────────────────
$kpiWhere = "clinica_id = :cid AND deleted_at IS NULL";
$kpiParams = [':cid' => $clinicaId];

// Para operativo: solo sus pacientes
if ($rol === ROL_OPERATIVO) {
    $kpiWhere .= " AND operativo_asignado_id = :uid";
    $kpiParams[':uid'] = $userId;
}

$stmt = $pdo->prepare(
    "SELECT
        COUNT(*) as total,
        SUM(estado = 'activo') as activos,
        SUM(estado = 'en_espera') as en_espera,
        SUM(MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) as nuevos_mes
     FROM pacientes WHERE $kpiWhere"
);
$stmt->execute($kpiParams);
$kpis = $stmt->fetch();

// Citas de hoy
$stmtCitas = $pdo->prepare(
    "SELECT COUNT(*) FROM citas
     WHERE clinica_id = :cid AND DATE(fecha_hora) = CURDATE()
     AND estado IN ('programada','confirmada')"
    . (tieneNivel(ROL_SUPERVISOR) ? '' : ' AND operativo_id = :uid')
);
$pCitas = [':cid' => $clinicaId];
if (!tieneNivel(ROL_SUPERVISOR)) $pCitas[':uid'] = $userId;
$stmtCitas->execute($pCitas);
$citasHoy = (int) $stmtCitas->fetchColumn();

// Citas completadas hoy
$stmtCitasComp = $pdo->prepare(
    "SELECT COUNT(*) FROM citas
     WHERE clinica_id = :cid AND DATE(fecha_hora) = CURDATE()
     AND estado = 'realizada'"
    . (tieneNivel(ROL_SUPERVISOR) ? '' : ' AND operativo_id = :uid')
);
$stmtCitasComp->execute($pCitas);
$citasCompletadas = (int) $stmtCitasComp->fetchColumn();

// Tasa de asistencia semanal
$stmtAsist = $pdo->prepare(
    "SELECT
        SUM(asistio = 'asistio') as asistidas,
        COUNT(*) as total
     FROM sesiones s
     JOIN pacientes p ON p.id = s.paciente_id
     WHERE p.clinica_id = :cid
     AND s.fecha_sesion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    . (tieneNivel(ROL_SUPERVISOR) ? '' : ' AND s.operativo_id = :uid')
);
$stmtAsist->execute($pCitas);
$asistData = $stmtAsist->fetch();
$tasaAsist = $asistData['total'] > 0
    ? round(($asistData['asistidas'] / $asistData['total']) * 100)
    : 0;

// Tareas vencidas propias
$stmtTareas = $pdo->prepare(
    "SELECT COUNT(*) FROM tareas_cronograma tc
     JOIN cronogramas cr ON cr.id = tc.cronograma_id
     WHERE cr.clinica_id = :cid
     AND tc.estado = 'pendiente'
     AND tc.fecha_programada < CURDATE()"
    . ($rol === ROL_OPERATIVO ? ' AND tc.responsable_id = :uid' : '')
);
$pT = [':cid' => $clinicaId];
if ($rol === ROL_OPERATIVO) $pT[':uid'] = $userId;
$stmtTareas->execute($pT);
$tareasVencidas = (int) $stmtTareas->fetchColumn();

// Citas del día con detalle
$stmtProximas = $pdo->prepare(
    "SELECT c.id, c.fecha_hora, c.tipo, c.estado, c.duracion_minutos,
            p.id as pac_id, p.nombre as pac_nombre, p.apellido as pac_apellido,
            p.codigo_paciente,
            u.nombre as op_nombre
     FROM citas c
     JOIN pacientes p ON p.id = c.paciente_id
     JOIN usuarios u ON u.id = c.operativo_id
     WHERE c.clinica_id = :cid AND DATE(c.fecha_hora) = CURDATE()
     AND c.estado IN ('programada','confirmada')"
    . (tieneNivel(ROL_SUPERVISOR) ? '' : ' AND c.operativo_id = :uid')
    . " ORDER BY c.fecha_hora ASC LIMIT 8"
);
$stmtProximas->execute($pCitas);
$citasProximas = $stmtProximas->fetchAll();

// Verificar qué citas ya tienen sesión registrada hoy
$sesionesHoy = [];
if (!empty($citasProximas)) {
    $pacIds = array_unique(array_column($citasProximas, 'pac_id'));
    $in = implode(',', array_fill(0, count($pacIds), '?'));
    $stmtSesHoy = $pdo->prepare(
        "SELECT paciente_id FROM sesiones
         WHERE paciente_id IN ($in) AND DATE(fecha_sesion) = CURDATE()"
    );
    $stmtSesHoy->execute($pacIds);
    $sesionesHoy = array_column($stmtSesHoy->fetchAll(), 'paciente_id');
}

// Alertas clínicas: pacientes sin actividad > 21 días
$stmtAlertas = $pdo->prepare(
    "SELECT p.id, p.nombre, p.apellido,
            DATEDIFF(CURDATE(), MAX(s.fecha_sesion)) as dias_sin_sesion,
            p.estado_flujo
     FROM pacientes p
     LEFT JOIN sesiones s ON s.paciente_id = p.id
     WHERE p.clinica_id = :cid AND p.estado = 'activo' AND p.deleted_at IS NULL"
    . ($rol === ROL_OPERATIVO ? ' AND p.operativo_asignado_id = :uid' : '')
    . " GROUP BY p.id
     HAVING dias_sin_sesion > 21 OR dias_sin_sesion IS NULL
     ORDER BY dias_sin_sesion DESC LIMIT 4"
);
$pAl = [':cid' => $clinicaId];
if ($rol === ROL_OPERATIVO) $pAl[':uid'] = $userId;
$stmtAlertas->execute($pAl);
$alertas = $stmtAlertas->fetchAll();

// Pacientes recientes (para gráfico / tabla gerente)
$stmtRecientes = $pdo->prepare(
    "SELECT p.id, p.codigo_paciente, p.nombre, p.apellido, p.estado, p.estado_flujo,
            p.fecha_ingreso, u.nombre as operativo_nombre
     FROM pacientes p
     LEFT JOIN usuarios u ON u.id = p.operativo_asignado_id
     WHERE p.clinica_id = :cid AND p.deleted_at IS NULL
     ORDER BY p.created_at DESC LIMIT 6"
);
$stmtRecientes->execute([':cid' => $clinicaId]);
$pacientesRecientes = $stmtRecientes->fetchAll();

// Chart data
$stmtChart = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total
     FROM pacientes WHERE clinica_id = :cid AND deleted_at IS NULL
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes"
);
$stmtChart->execute([':cid' => $clinicaId]);
$chartData   = $stmtChart->fetchAll();
$chartLabels = array_column($chartData, 'mes');
$chartValues = array_column($chartData, 'total');

// Saludo por hora
$hora          = (int) date('H');
$saludo        = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
$nombreUsuario = $_SESSION['user_nombre'] ?? 'usuario';

// Colores por tipo de cita (avatar background)
function colorCita(string $tipo): string {
    $map = [
        'sesion'      => 'sgc-avatar--success',
        'valoracion'  => 'sgc-avatar--primary',
        'seguimiento' => 'sgc-avatar--warning',
    ];
    return $map[$tipo] ?? 'sgc-avatar--neutral';
}

// Badge tipo de cita
function badgeTipoCita(string $tipo): string {
    $map = [
        'sesion'      => ['success', 'Sesión'],
        'valoracion'  => ['primary', 'Valoración'],
        'seguimiento' => ['warning', 'Seguimiento'],
    ];
    [$cls, $label] = $map[$tipo] ?? ['neutral', ucfirst($tipo)];
    return "<span class='sgc-badge sgc-badge--{$cls}'>{$label}</span>";
}

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- ── Barra de bienvenida ──────────────────────────────────── -->
<div class="sgc-welcome-bar mb-3">
    <div>
        <div class="sgc-welcome-bar__text">
            <?= e($saludo) ?>, <?= e($nombreUsuario) ?>.
            <?php if ($citasHoy > 0): ?>
            Tienes <strong><?= $citasHoy ?></strong> cita<?= $citasHoy > 1 ? 's' : '' ?> programada<?= $citasHoy > 1 ? 's' : '' ?> hoy.
            <?php else: ?>
            No tienes citas programadas para hoy.
            <?php endif; ?>
        </div>
        <div class="sgc-welcome-bar__sub">
            <?= date('l, d \d\e F \d\e Y', strtotime('today')) ?>
            <?php if ($tareasVencidas > 0): ?>
            · <strong><?= $tareasVencidas ?> tarea<?= $tareasVencidas > 1 ? 's' : '' ?> vencida<?= $tareasVencidas > 1 ? 's' : '' ?> en cronogramas</strong>
            <?php endif; ?>
            <?php if ($citasCompletadas > 0): ?>
            · <?= $citasCompletadas ?> sesión<?= $citasCompletadas > 1 ? 'es' : '' ?> completada<?= $citasCompletadas > 1 ? 's' : '' ?> hoy
            <?php endif; ?>
        </div>
    </div>
    <?php if ($tareasVencidas > 0): ?>
    <a href="<?= APP_URL ?>/modules/cronogramas/index.php"
       class="sgc-badge sgc-badge--danger" style="flex-shrink:0;text-decoration:none">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= $tareasVencidas ?> alerta<?= $tareasVencidas > 1 ? 's' : '' ?>
    </a>
    <?php endif; ?>
</div>

<!-- ── KPIs ─────────────────────────────────────────────────── -->
<div class="sgc-kpi-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px">

    <div class="sgc-metric-card">
        <div class="sgc-metric-card__value"><?= (int)($kpis['activos'] ?? 0) ?></div>
        <div class="sgc-metric-card__label">Pacientes activos</div>
        <div class="sgc-metric-card__change sgc-metric-card__change--up">
            <i class="bi bi-plus-circle-fill"></i>
            <?= (int)($kpis['nuevos_mes'] ?? 0) ?> este mes
        </div>
    </div>

    <div class="sgc-metric-card">
        <div class="sgc-metric-card__value"><?= $citasHoy ?></div>
        <div class="sgc-metric-card__label">Citas de hoy</div>
        <div class="sgc-metric-card__change sgc-metric-card__change--neutral">
            <i class="bi bi-check2"></i>
            <?= $citasCompletadas ?> completada<?= $citasCompletadas !== 1 ? 's' : '' ?>
        </div>
    </div>

    <div class="sgc-metric-card">
        <div class="sgc-metric-card__value"><?= $tasaAsist ?>%</div>
        <div class="sgc-metric-card__label">Asistencia semanal</div>
        <div class="sgc-metric-card__change sgc-metric-card__change--neutral">
            <i class="bi bi-calendar-week"></i>
            Últimos 7 días
        </div>
    </div>

    <div class="sgc-metric-card">
        <div class="sgc-metric-card__value" style="color:<?= $tareasVencidas > 0 ? 'var(--sgc-danger)' : 'var(--sgc-text)' ?>">
            <?= $tareasVencidas ?>
        </div>
        <div class="sgc-metric-card__label">Tareas vencidas</div>
        <div class="sgc-metric-card__change <?= $tareasVencidas > 0 ? 'sgc-metric-card__change--down' : 'sgc-metric-card__change--up' ?>">
            <?php if ($tareasVencidas > 0): ?>
            <i class="bi bi-exclamation-circle"></i> Requieren atención
            <?php else: ?>
            <i class="bi bi-check-circle-fill"></i> Al día
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ── Cuerpo principal: citas + panel derecho ───────────────── -->
<div style="display:grid;grid-template-columns:1fr 280px;gap:12px;align-items:start">

    <!-- ── Citas del día ──────────────────────────────── -->
    <div class="sgc-card">
        <div class="sgc-card-header">
            <h2 class="sgc-card-title">
                <i class="bi bi-calendar-day"></i>
                Citas de hoy
                <?php if ($citasHoy > 0): ?>
                <span class="sgc-badge sgc-badge--primary"><?= $citasHoy ?></span>
                <?php endif; ?>
            </h2>
            <a href="<?= APP_URL ?>/modules/citas/index.php"
               class="sgc-btn-ghost sgc-btn-ghost--primary" style="font-size:12px">
                Ver agenda completa <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($citasProximas)): ?>
        <div class="sgc-empty-state">
            <i class="bi bi-calendar-x sgc-empty-state__icon"></i>
            <div class="sgc-empty-state__title">Sin citas para hoy</div>
            <div class="sgc-empty-state__desc">No hay citas programadas ni confirmadas.</div>
            <a href="<?= APP_URL ?>/modules/citas/index.php"
               class="sgc-btn-outline" style="font-size:12px">
                <i class="bi bi-plus-lg"></i> Agendar cita
            </a>
        </div>
        <?php else: ?>
        <div>
            <?php foreach ($citasProximas as $cita):
                $hora12   = date('g:i', strtotime($cita['fecha_hora']));
                $ampm     = date('a', strtotime($cita['fecha_hora']));
                $iniciales = strtoupper(
                    substr($cita['pac_nombre'], 0, 1) . substr($cita['pac_apellido'], 0, 1)
                );
                $yaRegistrada = in_array($cita['pac_id'], $sesionesHoy);
                $colorAvatar  = colorCita($cita['tipo']);
            ?>
            <div class="sgc-appointment-row">
                <!-- Hora -->
                <div class="sgc-appointment-time">
                    <div class="sgc-appointment-time__hour"><?= $hora12 ?></div>
                    <div class="sgc-appointment-time__ampm"><?= $ampm ?></div>
                </div>

                <!-- Avatar -->
                <div class="sgc-avatar sgc-avatar--sm <?= $colorAvatar ?>">
                    <?= e($iniciales) ?>
                </div>

                <!-- Info -->
                <div class="sgc-appointment-info">
                    <div class="sgc-appointment-name">
                        <?= e($cita['pac_nombre'] . ' ' . $cita['pac_apellido']) ?>
                    </div>
                    <div class="sgc-appointment-sub">
                        <?= badgeTipoCita($cita['tipo']) ?>
                        · <?= $cita['duracion_minutos'] ?> min
                        · <code style="font-size:10px"><?= e($cita['codigo_paciente']) ?></code>
                    </div>
                </div>

                <!-- Acción -->
                <div style="flex-shrink:0">
                    <?php if ($yaRegistrada): ?>
                    <span class="sgc-badge sgc-badge--success">
                        <i class="bi bi-check2-circle"></i> Completada
                    </span>
                    <?php else: ?>
                    <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $cita['pac_id'] ?>"
                       class="sgc-btn-primary" style="font-size:11px;padding:5px 10px"
                       title="Ir al perfil para registrar sesión">
                        <i class="bi bi-pencil-square"></i>
                        Registrar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Panel derecho ─────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:10px">

        <!-- Alertas clínicas -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-exclamation-triangle" style="color:var(--sgc-warning)"></i>
                    Alertas
                </h2>
            </div>
            <?php if (empty($alertas)): ?>
            <div style="padding:16px;text-align:center;color:var(--sgc-text-muted)">
                <i class="bi bi-check-circle" style="font-size:22px;color:var(--sgc-success);display:block;margin-bottom:6px"></i>
                <span style="font-size:12px">Sin alertas activas</span>
            </div>
            <?php else: ?>
            <?php foreach ($alertas as $al):
                $dias = $al['dias_sin_sesion'];
                $esCritico = $dias === null || $dias > 30;
            ?>
            <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $al['id'] ?>"
               class="sgc-alert-item">
                <div class="sgc-alert-dot <?= $esCritico ? 'sgc-alert-dot--danger' : 'sgc-alert-dot--warning' ?>">
                    <i class="bi bi-<?= $esCritico ? 'exclamation' : 'clock' ?>"></i>
                </div>
                <div>
                    <div class="sgc-alert-body__name">
                        <?= e($al['nombre'] . ' ' . $al['apellido']) ?>
                    </div>
                    <div class="sgc-alert-body__desc">
                        <?php if ($dias === null): ?>
                        Sin sesiones registradas
                        <?php else: ?>
                        <?= $dias ?> días sin sesión
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Acceso rápido -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-lightning-charge"></i>
                    Acceso rápido
                </h2>
            </div>
            <div class="sgc-quick-access" style="padding:8px 10px">
                <a href="<?= APP_URL ?>/modules/pacientes/index.php"
                   class="sgc-btn-ghost sgc-btn-ghost--primary">
                    <i class="bi bi-people"></i> Mis pacientes
                </a>
                <a href="<?= APP_URL ?>/modules/citas/index.php"
                   class="sgc-btn-ghost sgc-btn-ghost--primary">
                    <i class="bi bi-calendar-plus"></i> Agendar cita
                </a>
                <?php if (tienePermiso('pacientes.registrar')): ?>
                <a href="<?= APP_URL ?>/modules/pacientes/registro.php"
                   class="sgc-btn-ghost sgc-btn-ghost--primary">
                    <i class="bi bi-person-plus"></i> Nuevo paciente
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/modules/cronogramas/index.php"
                   class="sgc-btn-ghost" style="<?= $tareasVencidas > 0 ? 'color:var(--sgc-danger)' : '' ?>">
                    <i class="bi bi-kanban"></i> Cronogramas
                    <?php if ($tareasVencidas > 0): ?>
                    <span class="sgc-badge sgc-badge--danger" style="margin-left:auto">
                        <?= $tareasVencidas ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- KPI extra para gerente+ -->
        <?php if (tieneNivel(ROL_SUPERVISOR)): ?>
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-graph-up"></i>
                    Resumen clínica
                </h2>
            </div>
            <div class="sgc-card-body" style="padding:12px 14px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:center">
                    <div>
                        <div style="font-size:20px;font-weight:500;color:var(--sgc-text)"><?= (int)($kpis['total'] ?? 0) ?></div>
                        <div style="font-size:10px;color:var(--sgc-text-muted)">Total pacientes</div>
                    </div>
                    <div>
                        <div style="font-size:20px;font-weight:500;color:var(--sgc-text)"><?= (int)($kpis['en_espera'] ?? 0) ?></div>
                        <div style="font-size:10px;color:var(--sgc-text-muted)">En espera</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ── Pacientes recientes (solo gerente+) ────────────────────── -->
<?php if (tieneNivel(ROL_SUPERVISOR) && !empty($pacientesRecientes)): ?>
<div class="sgc-card mt-3">
    <div class="sgc-card-header">
        <h2 class="sgc-card-title">
            <i class="bi bi-people"></i>
            Pacientes recientes
        </h2>
        <a href="<?= APP_URL ?>/modules/pacientes/index.php"
           class="sgc-btn-ghost sgc-btn-ghost--primary" style="font-size:12px">
            Ver todos <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="sgc-table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Código</th>
                    <th>Estado</th>
                    <th>Flujo</th>
                    <th>Psicólogo</th>
                    <th>Ingreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pacientesRecientes as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="sgc-avatar sgc-avatar--xs">
                                <?= strtoupper(substr($p['nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:500">
                                    <?= e($p['nombre'] . ' ' . $p['apellido']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><code style="font-size:11px"><?= e($p['codigo_paciente']) ?></code></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td><?= badgeFlujo($p['estado_flujo']) ?></td>
                    <td style="color:var(--sgc-text-muted)">
                        <?= e($p['operativo_nombre'] ?? '—') ?>
                    </td>
                    <td style="color:var(--sgc-text-muted)">
                        <?= formatearFecha($p['fecha_ingreso']) ?>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $p['id'] ?>"
                           class="sgc-btn-ghost" style="padding:4px 8px">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$extraJs = '';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
