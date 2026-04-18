<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('historial.ver');

$pageTitle  = 'Historial Clínico';
$activeMenu = 'historial';

$pdo       = db();
$clinicaId = clinicaId();

// Lista de pacientes con progreso de flujo — conteos via JOIN para evitar N+1
$stmt = $pdo->prepare(
    "SELECT p.id, p.codigo_paciente, p.nombre, p.apellido, p.fecha_nacimiento,
            p.estado, p.estado_flujo, p.fecha_ingreso,
            u.nombre as op_nombre, u.apellido as op_apellido,
            COALESCE(ev.total, 0) as num_evaluaciones,
            COALESCE(ses.total, 0) as num_sesiones
     FROM pacientes p
     LEFT JOIN usuarios u ON u.id = p.operativo_asignado_id
     LEFT JOIN (
         SELECT paciente_id, COUNT(*) as total FROM evaluaciones GROUP BY paciente_id
     ) ev ON ev.paciente_id = p.id
     LEFT JOIN (
         SELECT paciente_id, COUNT(*) as total FROM sesiones GROUP BY paciente_id
     ) ses ON ses.paciente_id = p.id
     WHERE p.clinica_id = :cid AND p.deleted_at IS NULL
     ORDER BY p.estado_flujo DESC, p.created_at DESC"
);
$stmt->execute([':cid' => $clinicaId]);
$pacientes = $stmt->fetchAll();

$pasosFlujo = ['registrado'=>1,'anamnesis'=>2,'evaluacion'=>3,'diagnostico'=>4,'plan'=>5,'sesiones'=>6,'seguimiento'=>7];

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-file-medical text-primary me-2"></i>Expedientes Clínicos — <?= count($pacientes) ?> paciente(s)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Psicólogo</th>
                        <th>Progreso del flujo</th>
                        <th class="text-center">Eval.</th>
                        <th class="text-center">Sesiones</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pacientes as $p):
                    $paso = $pasosFlujo[$p['estado_flujo']] ?? 1;
                    $pct  = round(($paso / 7) * 100);
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></div>
                        <small class="text-muted"><code><?= e($p['codigo_paciente']) ?></code> · <?= calcularEdad($p['fecha_nacimiento']) ?> años</small>
                    </td>
                    <td class="small text-muted"><?= $p['op_nombre'] ? e($p['op_nombre'].' '.$p['op_apellido']) : '<span class="text-warning">Sin asignar</span>' ?></td>
                    <td style="min-width:160px">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span><?= ucfirst($p['estado_flujo']) ?></span>
                            <span><?= $pct ?>%</span>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge bg-info"><?= $p['num_evaluaciones'] ?></span></td>
                    <td class="text-center"><span class="badge bg-success"><?= $p['num_sesiones'] ?></span></td>
                    <td><?= badgeEstado($p['estado']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/modules/historial/paciente.php?id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-folder2-open me-1"></i>Ver historial
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pacientes)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">No hay pacientes registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/layout/footer.php'; ?>
