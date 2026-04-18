<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('reportes.generar');

$pageTitle  = 'Reportes';
$activeMenu = 'reportes';

$pdo       = db();
$clinicaId = clinicaId();

// Estadísticas generales
$stmt = $pdo->prepare(
    "SELECT
        SUM(estado='activo') as activos,
        SUM(estado='egresado') as egresados,
        SUM(estado='en_espera') as en_espera,
        SUM(MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())) as nuevos_mes,
        COUNT(*) as total
     FROM pacientes WHERE clinica_id=:cid AND deleted_at IS NULL"
);
$stmt->execute([':cid' => $clinicaId]);
$stats = $stmt->fetch();

// Pacientes por flujo
$stmtFlujo = $pdo->prepare(
    "SELECT estado_flujo, COUNT(*) as total FROM pacientes
     WHERE clinica_id=:cid AND deleted_at IS NULL GROUP BY estado_flujo"
);
$stmtFlujo->execute([':cid' => $clinicaId]);
$porFlujo = $stmtFlujo->fetchAll(PDO::FETCH_KEY_PAIR);

// Sesiones del mes
$stmtSes = $pdo->prepare(
    "SELECT COUNT(*) as total,
            SUM(asistio='asistio') as asistidas,
            SUM(asistio='falto') as faltas
     FROM sesiones WHERE clinica_id=:cid
     AND MONTH(fecha_sesion)=MONTH(CURDATE()) AND YEAR(fecha_sesion)=YEAR(CURDATE())"
);
$stmtSes->execute([':cid' => $clinicaId]);
$sesiones = $stmtSes->fetch();

// Top operativos
$stmtOp = $pdo->prepare(
    "SELECT u.nombre, u.apellido, COUNT(p.id) as num_pacientes
     FROM usuarios u
     JOIN pacientes p ON p.operativo_asignado_id = u.id
     WHERE p.clinica_id=:cid AND p.deleted_at IS NULL AND p.estado='activo'
     GROUP BY u.id ORDER BY num_pacientes DESC LIMIT 5"
);
$stmtOp->execute([':cid' => $clinicaId]);
$topOperativos = $stmtOp->fetchAll();

// Nuevos pacientes últimos 6 meses (para gráfico)
$stmtChart = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') as mes, COUNT(*) as total
     FROM pacientes WHERE clinica_id=:cid AND deleted_at IS NULL
     AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes"
);
$stmtChart->execute([':cid' => $clinicaId]);
$chartData = $stmtChart->fetchAll();

$tasaAsistencia = $sesiones['total'] > 0
    ? round(($sesiones['asistidas'] / $sesiones['total']) * 100)
    : 0;

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- KPIs del mes -->
<div class="row g-3 mb-4">
    <?php $items = [
        ['label'=>'Total pacientes',    'val'=>$stats['total'],        'icon'=>'people-fill',       'color'=>'primary'],
        ['label'=>'Activos',            'val'=>$stats['activos'],      'icon'=>'person-check-fill', 'color'=>'success'],
        ['label'=>'Nuevos este mes',    'val'=>$stats['nuevos_mes'],   'icon'=>'person-plus-fill',  'color'=>'info'],
        ['label'=>'Tasa de asistencia', 'val'=>$tasaAsistencia.'%',    'icon'=>'calendar-check-fill','color'=>'warning'],
    ];
    foreach ($items as $item): ?>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="sgc-kpi-icon bg-<?= $item['color'] ?>-subtle text-<?= $item['color'] ?>">
                    <i class="bi bi-<?= $item['icon'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $item['label'] ?></div>
                    <div class="fw-bold fs-4"><?= $item['val'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <!-- Gráfico nuevos pacientes -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-graph-up-arrow text-primary me-2"></i>Nuevos pacientes (últimos 6 meses)
            </div>
            <div class="card-body"><canvas id="chart-nuevos" height="180"></canvas></div>
        </div>
    </div>

    <!-- Distribución por flujo -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-pie-chart text-primary me-2"></i>Estado del flujo
            </div>
            <div class="card-body">
                <?php
                $flujosLabel = ['registrado'=>'Registrado','anamnesis'=>'Anamnesis','evaluacion'=>'Evaluación',
                                'diagnostico'=>'Diagnóstico','plan'=>'Plan','sesiones'=>'Sesiones','seguimiento'=>'Seguimiento'];
                $coloresFlujo = ['registrado'=>'secondary','anamnesis'=>'info','evaluacion'=>'primary',
                                 'diagnostico'=>'warning','plan'=>'success','sesiones'=>'success','seguimiento'=>'dark'];
                foreach ($flujosLabel as $k => $label):
                    $val = $porFlujo[$k] ?? 0;
                    if (!$val) continue;
                    $pct = $stats['total'] > 0 ? round(($val/$stats['total'])*100) : 0;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <span><?= $label ?></span>
                    <span class="fw-semibold"><?= $val ?> (<?= $pct ?>%)</span>
                </div>
                <div class="progress mb-2" style="height:5px">
                    <div class="progress-bar bg-<?= $coloresFlujo[$k] ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sesiones del mes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-journal-medical text-primary me-2"></i>Sesiones del mes actual
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-primary"><?= $sesiones['total'] ?></div>
                        <div class="small text-muted">Total</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-success"><?= $sesiones['asistidas'] ?></div>
                        <div class="small text-muted">Asistidas</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-danger"><?= $sesiones['faltas'] ?></div>
                        <div class="small text-muted">Faltas</div>
                    </div>
                </div>
                <div class="progress mt-3" style="height:10px">
                    <div class="progress-bar bg-success" style="width:<?= $tasaAsistencia ?>%"></div>
                    <div class="progress-bar bg-danger" style="width:<?= 100-$tasaAsistencia ?>%"></div>
                </div>
                <p class="text-center small text-muted mt-2">Tasa de asistencia: <strong><?= $tasaAsistencia ?>%</strong></p>
            </div>
        </div>
    </div>

    <!-- Top psicólogos -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-person-badge text-primary me-2"></i>Carga por psicólogo (pacientes activos)
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php foreach ($topOperativos as $op): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                    <span><?= e($op['nombre'] . ' ' . $op['apellido']) ?></span>
                    <span class="badge bg-primary rounded-pill"><?= $op['num_pacientes'] ?> pac.</span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($topOperativos)): ?>
                <li class="list-group-item text-muted text-center">Sin datos</li>
                <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$labels = array_column($chartData, 'mes');
$values = array_column($chartData, 'total');
$extraJs = '<script>
new Chart(document.getElementById("chart-nuevos").getContext("2d"), {
    type: "line",
    data: {
        labels: ' . json_encode($labels) . ',
        datasets: [{
            label: "Nuevos pacientes",
            data: ' . json_encode($values) . ',
            borderColor: "#1a3a5c",
            backgroundColor: "rgba(30,90,168,.1)",
            borderWidth: 2,
            tension: .4,
            fill: true,
            pointBackgroundColor: "#1a3a5c"
        }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
