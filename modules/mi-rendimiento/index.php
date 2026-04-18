<?php
/**
 * SGC — Mi Rendimiento
 * KPIs personales del operativo: sesiones, asistencia, tareas vencidas.
 * Supervisores y superiores pueden consultar cualquier operativo.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('pacientes.ver_lista');

$pdo       = db();
$clinicaId = clinicaId();

// Determinar qué operativo consultar
$esSupervisorPlus = tieneNivel(ROL_SUPERVISOR);
$opId = $esSupervisorPlus && isset($_GET['op_id']) ? (int)$_GET['op_id'] : usuarioId();

// Nombre del operativo consultado
$stmtOp = $pdo->prepare("SELECT nombre, apellido FROM usuarios WHERE id = :id");
$stmtOp->execute([':id' => $opId]);
$operativoInfo = $stmtOp->fetch();
$opNombre = $operativoInfo ? ($operativoInfo['nombre'] . ' ' . $operativoInfo['apellido']) : 'Desconocido';

// Lista de operativos de la clínica (solo para supervisores+, para el selector)
$operativos = [];
if ($esSupervisorPlus) {
    $stmtOps = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido
         FROM usuarios u
         INNER JOIN usuario_clinica uc ON uc.usuario_id = u.id
         WHERE uc.clinica_id = :cid AND uc.rol = :rol AND uc.activo = 1 AND u.activo = 1
         ORDER BY u.apellido, u.nombre"
    );
    $stmtOps->execute([':cid' => $clinicaId, ':rol' => ROL_OPERATIVO]);
    $operativos = $stmtOps->fetchAll();
}

// ── KPI 1: Pacientes activos asignados ──────────────────────────────────────
$stmtPac = $pdo->prepare(
    "SELECT COUNT(*) FROM pacientes
     WHERE operativo_asignado_id = :oid AND clinica_id = :cid
       AND estado = 'activo' AND deleted_at IS NULL"
);
$stmtPac->execute([':oid' => $opId, ':cid' => $clinicaId]);
$pacientesActivos = (int) $stmtPac->fetchColumn();

// ── KPI 2: Sesiones esta semana ─────────────────────────────────────────────
$stmtSemana = $pdo->prepare(
    "SELECT COUNT(*) FROM sesiones
     WHERE operativo_id = :oid AND clinica_id = :cid
       AND YEARWEEK(fecha_sesion, 1) = YEARWEEK(CURDATE(), 1)
       AND asistio = 'asistio'"
);
$stmtSemana->execute([':oid' => $opId, ':cid' => $clinicaId]);
$sesionesSemana = (int) $stmtSemana->fetchColumn();

// ── KPI 3: Sesiones este mes ────────────────────────────────────────────────
$stmtMes = $pdo->prepare(
    "SELECT COUNT(*) FROM sesiones
     WHERE operativo_id = :oid AND clinica_id = :cid
       AND MONTH(fecha_sesion) = MONTH(CURDATE())
       AND YEAR(fecha_sesion)  = YEAR(CURDATE())
       AND asistio = 'asistio'"
);
$stmtMes->execute([':oid' => $opId, ':cid' => $clinicaId]);
$sesionesMes = (int) $stmtMes->fetchColumn();

// ── KPI 4: Tasa de asistencia (mes actual) ──────────────────────────────────
$stmtAsist = $pdo->prepare(
    "SELECT
         SUM(asistio = 'asistio') AS asistidas,
         COUNT(*) AS total
     FROM sesiones
     WHERE operativo_id = :oid AND clinica_id = :cid
       AND MONTH(fecha_sesion) = MONTH(CURDATE())
       AND YEAR(fecha_sesion)  = YEAR(CURDATE())"
);
$stmtAsist->execute([':oid' => $opId, ':cid' => $clinicaId]);
$asistRow  = $stmtAsist->fetch();
$totalSes  = (int) ($asistRow['total'] ?? 0);
$asistidas = (int) ($asistRow['asistidas'] ?? 0);
$tasaAsist = $totalSes > 0 ? round(($asistidas / $totalSes) * 100) : 0;

// ── KPI 5: Tareas vencidas del cronograma ───────────────────────────────────
$stmtVenc = $pdo->prepare(
    "SELECT COUNT(*) FROM tareas_cronograma tc
     INNER JOIN cronogramas cr ON cr.id = tc.cronograma_id
     WHERE cr.operativo_id = :oid AND cr.clinica_id = :cid
       AND tc.estado = 'pendiente'
       AND tc.fecha_programada < CURDATE()"
);
$stmtVenc->execute([':oid' => $opId, ':cid' => $clinicaId]);
$tareasVencidas = (int) $stmtVenc->fetchColumn();

// ── Gráfico: sesiones por semana (últimas 4 semanas) ────────────────────────
$stmtChart = $pdo->prepare(
    "SELECT
         YEARWEEK(fecha_sesion, 1) AS semana,
         COUNT(*) AS total
     FROM sesiones
     WHERE operativo_id = :oid AND clinica_id = :cid
       AND fecha_sesion >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
       AND asistio = 'asistio'
     GROUP BY semana
     ORDER BY semana"
);
$stmtChart->execute([':oid' => $opId, ':cid' => $clinicaId]);
$chartRows = $stmtChart->fetchAll();

// Construir etiquetas "Semana X" relativas
$chartLabels = [];
$chartData   = [];
$semanaHoy   = (int) date('W');
$anioHoy     = (int) date('Y');

// Generar las últimas 4 semanas como estructura base
$semanas4 = [];
for ($i = 3; $i >= 0; $i--) {
    $dt  = new DateTime();
    $dt->modify("-{$i} week");
    $yw  = (int) $dt->format('oW'); // año ISO + semana ISO sin separador
    $semanas4[$yw] = 0;
    $chartLabels[] = 'Semana ' . $dt->format('W') . ' (' . $dt->format('d/m') . ')';
}

foreach ($chartRows as $row) {
    $yw = (int) $row['semana'];
    if (array_key_exists($yw, $semanas4)) {
        $semanas4[$yw] = (int) $row['total'];
    }
}
$chartData = array_values($semanas4);

// ── Top 5 pacientes con más tareas vencidas ─────────────────────────────────
$stmtAlerta = $pdo->prepare(
    "SELECT p.nombre, p.apellido, p.id, COUNT(tc.id) AS vencidas
     FROM tareas_cronograma tc
     INNER JOIN cronogramas cr ON cr.id = tc.cronograma_id
     INNER JOIN pacientes p   ON p.id  = cr.paciente_id
     WHERE cr.operativo_id = :oid AND cr.clinica_id = :cid
       AND tc.estado = 'pendiente'
       AND tc.fecha_programada < CURDATE()
     GROUP BY p.id
     ORDER BY vencidas DESC
     LIMIT 5"
);
$stmtAlerta->execute([':oid' => $opId, ':cid' => $clinicaId]);
$pacientesAlerta = $stmtAlerta->fetchAll();

$pageTitle  = 'Mi rendimiento';
$activeMenu = 'mi-rendimiento';

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<?php if ($esSupervisorPlus): ?>
<!-- Selector de operativo (solo supervisores y superiores) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 fw-semibold small">Ver rendimiento de:</label>
            </div>
            <div class="col-sm-4">
                <select name="op_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="<?= usuarioId() ?>" <?= $opId === usuarioId() ? 'selected' : '' ?>>
                        Mi rendimiento (yo)
                    </option>
                    <?php foreach ($operativos as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= $opId === (int)$op['id'] ? 'selected' : '' ?>>
                        <?= e($op['nombre'] . ' ' . $op['apellido']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($opId !== usuarioId()): ?>
            <div class="col-auto">
                <span class="badge bg-info text-dark">
                    <i class="bi bi-person-fill me-1"></i>Viendo: <?= e($opNombre) ?>
                </span>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── Tarjetas KPI ─────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <!-- Pacientes activos -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="bi bi-people-fill fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $pacientesActivos ?></div>
                    <div class="small text-muted">Pacientes activos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sesiones esta semana -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="bi bi-calendar-week-fill fs-4 text-success"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $sesionesSemana ?></div>
                    <div class="small text-muted">Sesiones esta semana</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sesiones este mes -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="bi bi-calendar-month-fill fs-4 text-info"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $sesionesMes ?></div>
                    <div class="small text-muted">Sesiones este mes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasa de asistencia -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10"
                     style="width:52px;height:52px;flex-shrink:0">
                    <i class="bi bi-graph-up-arrow fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold lh-1"><?= $tasaAsist ?>%</div>
                    <div class="small text-muted">Tasa de asistencia</div>
                    <?php if ($tareasVencidas > 0): ?>
                    <div class="badge bg-danger mt-1 small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $tareasVencidas ?> tareas vencidas
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Gráfico + Alertas ─────────────────────────────────────────────────── -->
<div class="row g-3">
    <!-- Gráfico de barras: sesiones por semana -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>Sesiones realizadas — últimas 4 semanas
            </div>
            <div class="card-body">
                <canvas id="chart-sesiones" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>

    <!-- Top pacientes con tareas vencidas -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Pacientes con tareas vencidas
            </div>
            <div class="card-body p-0">
                <?php if (empty($pacientesAlerta)): ?>
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                    Sin tareas vencidas.
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($pacientesAlerta as $pa): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                        <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $pa['id'] ?>"
                           class="text-decoration-none small fw-semibold">
                            <?= e($pa['nombre'] . ' ' . $pa['apellido']) ?>
                        </a>
                        <span class="badge bg-danger rounded-pill"><?= $pa['vencidas'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php if ($tareasVencidas > 0): ?>
            <div class="card-footer bg-white border-0 text-center">
                <a href="<?= APP_URL ?>/modules/cronogramas/index.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-kanban me-1"></i>Ver cronogramas
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$chartLabelsJson = json_encode($chartLabels, JSON_UNESCAPED_UNICODE);
$chartDataJson   = json_encode($chartData);

$extraJs = '<script>
document.addEventListener("DOMContentLoaded", function () {
    new Chart(document.getElementById("chart-sesiones").getContext("2d"), {
        type: "bar",
        data: {
            labels: ' . $chartLabelsJson . ',
            datasets: [{
                label: "Sesiones realizadas",
                data: ' . $chartDataJson . ',
                backgroundColor: "rgba(26, 58, 92, 0.7)",
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 }
                }
            }
        }
    });
});
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
