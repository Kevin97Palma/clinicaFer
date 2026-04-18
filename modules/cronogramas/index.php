<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('cronogramas.ver_propio');

$pageTitle  = 'Cronogramas Terapéuticos';
$activeMenu = 'cronogramas';

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

$where  = "WHERE cr.clinica_id = :cid";
$params = [':cid' => $clinicaId];

if (!tieneNivel(ROL_SUPERVISOR)) {
    $where .= " AND cr.operativo_id = :uid";
    $params[':uid'] = $userId;
}

$stmt = $pdo->prepare(
    "SELECT cr.id, cr.estado, cr.fecha_inicio, cr.fecha_fin_estimada,
            cr.total_sesiones, cr.sesiones_completadas, cr.auto_generado,
            p.id as paciente_id, p.nombre as pac_nombre, p.apellido as pac_apellido,
            p.codigo_paciente,
            pl.enfoque_terapeutico,
            u.nombre as op_nombre, u.apellido as op_apellido,
            -- tareas pendientes
            (SELECT COUNT(*) FROM tareas_cronograma t WHERE t.cronograma_id = cr.id AND t.estado = 'pendiente') as tareas_pendientes,
            (SELECT MIN(fecha_programada) FROM tareas_cronograma t WHERE t.cronograma_id = cr.id AND t.estado = 'pendiente') as proxima_tarea
     FROM cronogramas cr
     JOIN pacientes p ON p.id = cr.paciente_id
     JOIN planes_intervencion pl ON pl.id = cr.plan_id
     JOIN usuarios u ON u.id = cr.operativo_id
     $where
     ORDER BY cr.estado ASC, cr.fecha_inicio DESC"
);
$stmt->execute($params);
$cronogramas = $stmt->fetchAll();

$coloresEstado = ['borrador_auto'=>'warning','activo'=>'success','completado'=>'secondary','suspendido'=>'danger'];

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<?php if (empty($cronogramas)): ?>
<div class="text-center py-5">
    <i class="bi bi-kanban display-3 text-muted d-block mb-3"></i>
    <h5 class="text-muted">No hay cronogramas registrados</h5>
    <p class="text-muted small">Los cronogramas se generan automáticamente al activar un Plan de Intervención.</p>
</div>
<?php else: ?>

<div class="row g-3">
<?php foreach ($cronogramas as $cr):
    $avance = calcularAvanceCronograma($cr['sesiones_completadas'], $cr['total_sesiones']);
    $colorEst = $coloresEstado[$cr['estado']] ?? 'secondary';
    $retrasado = $cr['proxima_tarea'] && strtotime($cr['proxima_tarea']) < time() && $cr['estado'] === 'activo';
?>
<div class="col-12 col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100 <?= $retrasado ? 'border-warning border-2' : '' ?>">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <span class="fw-semibold"><?= e($cr['pac_nombre'] . ' ' . $cr['pac_apellido']) ?></span>
                <br><small class="text-muted"><code><?= e($cr['codigo_paciente']) ?></code></small>
            </div>
            <span class="badge bg-<?= $colorEst ?>">
                <?= str_replace('_',' ', $cr['estado']) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($retrasado): ?>
            <div class="alert alert-warning py-1 px-2 small mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>Tiene tareas pendientes vencidas
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span><?= $cr['sesiones_completadas'] ?> / <?= $cr['total_sesiones'] ?> sesiones</span>
                    <span class="fw-semibold <?= $avance >= 80 ? 'text-success' : '' ?>"><?= $avance ?>%</span>
                </div>
                <div class="progress" style="height:8px;border-radius:4px">
                    <div class="progress-bar <?= $avance >= 100 ? 'bg-success' : 'bg-primary' ?>"
                         style="width:<?= $avance ?>%;transition:width .5s"></div>
                </div>
            </div>

            <div class="small text-muted">
                <div><i class="bi bi-calendar3 me-2"></i>
                    <?= formatearFecha($cr['fecha_inicio']) ?> → <?= formatearFecha($cr['fecha_fin_estimada']) ?>
                </div>
                <?php if ($cr['enfoque_terapeutico']): ?>
                <div class="mt-1"><i class="bi bi-journal-medical me-2"></i><?= e(truncar($cr['enfoque_terapeutico'], 50)) ?></div>
                <?php endif; ?>
                <?php if ($cr['proxima_tarea'] && $cr['estado'] === 'activo'): ?>
                <div class="mt-1 <?= $retrasado ? 'text-danger' : 'text-primary' ?>">
                    <i class="bi bi-clock me-2"></i>Próxima: <?= formatearFechaHora($cr['proxima_tarea']) ?>
                </div>
                <?php endif; ?>
                <div class="mt-1"><i class="bi bi-person me-2"></i><?= e($cr['op_nombre'].' '.$cr['op_apellido']) ?></div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 d-flex gap-2">
            <a href="<?= APP_URL ?>/modules/cronogramas/detalle.php?id=<?= $cr['id'] ?>"
               class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-list-task me-1"></i>Ver tareas (<?= $cr['tareas_pendientes'] ?> pend.)
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include dirname(__DIR__, 2) . '/includes/layout/footer.php'; ?>
