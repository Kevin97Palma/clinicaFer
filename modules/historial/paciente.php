<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('pacientes.ver_lista');

$pacienteId = (int)($_GET['id'] ?? 0);
if (!$pacienteId) {
    header('Location: ' . APP_URL . '/modules/pacientes/index.php');
    exit;
}

$pdo       = db();
$clinicaId = clinicaId();

// Cargar paciente
$stmt = $pdo->prepare(
    "SELECT p.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM pacientes p
     LEFT JOIN usuarios u ON u.id = p.operativo_asignado_id
     WHERE p.id = :id AND p.clinica_id = :cid AND p.deleted_at IS NULL"
);
$stmt->execute([':id' => $pacienteId, ':cid' => $clinicaId]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: ' . APP_URL . '/modules/pacientes/index.php');
    exit;
}

$pageTitle  = 'Historial — ' . $paciente['nombre'] . ' ' . $paciente['apellido'];
$activeMenu = 'historial';

// Anamnesis
$stmtAn = $pdo->prepare("SELECT * FROM anamnesis WHERE paciente_id = :pid LIMIT 1");
$stmtAn->execute([':pid' => $pacienteId]);
$anamnesis = $stmtAn->fetch();

// Evaluaciones
$stmtEv = $pdo->prepare(
    "SELECT e.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM evaluaciones e
     LEFT JOIN usuarios u ON u.id = e.operativo_id
     WHERE e.paciente_id = :pid ORDER BY e.fecha_aplicacion DESC"
);
$stmtEv->execute([':pid' => $pacienteId]);
$evaluaciones = $stmtEv->fetchAll();

// Diagnósticos
$stmtDx = $pdo->prepare(
    "SELECT d.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM diagnosticos d
     LEFT JOIN usuarios u ON u.id = d.operativo_id
     WHERE d.paciente_id = :pid ORDER BY d.fecha_diagnostico DESC"
);
$stmtDx->execute([':pid' => $pacienteId]);
$diagnosticos = $stmtDx->fetchAll();

// Plan activo
$stmtPlan = $pdo->prepare(
    "SELECT pi.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM planes_intervencion pi
     LEFT JOIN usuarios u ON u.id = pi.operativo_id
     WHERE pi.paciente_id = :pid ORDER BY pi.created_at DESC"
);
$stmtPlan->execute([':pid' => $pacienteId]);
$planes = $stmtPlan->fetchAll();
$planActivo = null;
foreach ($planes as $p) {
    if ($p['estado'] === 'activo') { $planActivo = $p; break; }
}

// Sesiones
$stmtSes = $pdo->prepare(
    "SELECT s.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM sesiones s
     LEFT JOIN usuarios u ON u.id = s.operativo_id
     WHERE s.paciente_id = :pid ORDER BY s.fecha_sesion DESC"
);
$stmtSes->execute([':pid' => $pacienteId]);
$sesiones = $stmtSes->fetchAll();

// Seguimientos
$stmtSeg = $pdo->prepare(
    "SELECT sg.*, u.nombre as op_nombre, u.apellido as op_apellido
     FROM seguimientos sg
     LEFT JOIN usuarios u ON u.id = sg.operativo_id
     WHERE sg.paciente_id = :pid ORDER BY sg.fecha_evaluacion DESC"
);
$stmtSeg->execute([':pid' => $pacienteId]);
$seguimientos = $stmtSeg->fetchAll();

// Determinar tab activo
$tabActivo = $_GET['tab'] ?? 'anamnesis';

auditarAcceso('VER_HISTORIAL', 'pacientes', $pacienteId);
include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- Encabezado -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="sgc-avatar-lg d-flex align-items-center justify-content-center rounded-circle fw-bold text-white"
                 style="width:52px;height:52px;font-size:1.3rem;background:var(--sgc-primary);flex-shrink:0">
                <?= strtoupper(substr($paciente['nombre'], 0, 1)) ?>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-bold"><?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?></h5>
                <div class="text-muted small">
                    <?= e($paciente['codigo_paciente']) ?> ·
                    <?= calcularEdad($paciente['fecha_nacimiento']) ?> años ·
                    Ingreso: <?= formatearFecha($paciente['fecha_ingreso']) ?>
                    <?= badgeEstado($paciente['estado']) ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $pacienteId ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Perfil
                </a>
                <?php if (tienePermiso('citas.gestionar')): ?>
                <button class="btn btn-sm btn-outline-primary" onclick="nuevaSesion()">
                    <i class="bi bi-plus-circle me-1"></i>Nueva sesión
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de navegación -->
<ul class="nav nav-tabs mb-3" id="historialTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'anamnesis' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=anamnesis">
            <i class="bi bi-clipboard2-pulse me-1"></i>Anamnesis
            <?php if ($anamnesis && $anamnesis['completado']): ?>
            <span class="badge bg-success ms-1" style="font-size:.65rem">✓</span>
            <?php elseif (!$anamnesis): ?>
            <span class="badge bg-secondary ms-1" style="font-size:.65rem">Pendiente</span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'evaluaciones' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=evaluaciones">
            <i class="bi bi-journal-check me-1"></i>Evaluaciones
            <?php if (!empty($evaluaciones)): ?>
            <span class="badge bg-primary ms-1"><?= count($evaluaciones) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'diagnostico' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=diagnostico">
            <i class="bi bi-search-heart me-1"></i>Diagnóstico
            <?php if (!empty($diagnosticos)): ?>
            <span class="badge bg-warning text-dark ms-1"><?= count($diagnosticos) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'plan' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=plan">
            <i class="bi bi-diagram-3 me-1"></i>Plan
            <?php if ($planActivo): ?>
            <span class="badge bg-success ms-1">Activo</span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'sesiones' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=sesiones">
            <i class="bi bi-chat-heart me-1"></i>Sesiones
            <?php if (!empty($sesiones)): ?>
            <span class="badge bg-primary ms-1"><?= count($sesiones) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tabActivo === 'seguimiento' ? 'active' : '' ?>"
           href="?id=<?= $pacienteId ?>&tab=seguimiento">
            <i class="bi bi-graph-up me-1"></i>Seguimiento
        </a>
    </li>
</ul>

<!-- ═══════════════════════════════════════════ ANAMNESIS -->
<?php if ($tabActivo === 'anamnesis'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard2-pulse text-primary me-2"></i>Anamnesis psicológica</span>
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <a href="<?= APP_URL ?>/modules/historial/anamnesis.php?id=<?= $pacienteId ?>"
           class="btn btn-sm btn-<?= $anamnesis ? 'outline-primary' : 'primary' ?>">
            <i class="bi bi-<?= $anamnesis ? 'pencil' : 'plus-circle' ?> me-1"></i>
            <?= $anamnesis ? 'Editar anamnesis' : 'Completar anamnesis' ?>
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!$anamnesis): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard2 display-3 d-block mb-3 opacity-25"></i>
            <p class="mb-3">La anamnesis aún no ha sido completada.</p>
            <a href="<?= APP_URL ?>/modules/historial/anamnesis.php?id=<?= $pacienteId ?>"
               class="btn btn-primary">
                <i class="bi bi-clipboard2-plus me-1"></i>Iniciar anamnesis
            </a>
        </div>
        <?php else: ?>
        <!-- Datos guardados de anamnesis -->
        <div class="row g-4">
            <!-- Antecedentes heredofamiliares -->
            <div class="col-12">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-diagram-2 me-2"></i>Antecedentes heredofamiliares
                </h6>
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="text-muted fw-semibold mb-1">Línea materna</div>
                        <p class="mb-0"><?= nl2br(e($anamnesis['antec_linea_materna'] ?? '—')) ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted fw-semibold mb-1">Línea paterna</div>
                        <p class="mb-0"><?= nl2br(e($anamnesis['antec_linea_paterna'] ?? '—')) ?></p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php
                    $antecFlags = [
                        'retardo_mental' => 'Retardo mental',
                        'trastornos_psiquiatricos' => 'Trastornos psiquiátricos',
                        'epilepsia' => 'Epilepsia',
                        'prob_aprendizaje' => 'Prob. aprendizaje',
                    ];
                    foreach ($antecFlags as $campo => $label):
                        if (!empty($anamnesis[$campo])):
                    ?>
                    <span class="badge bg-warning text-dark"><?= $label ?></span>
                    <?php endif; endforeach; ?>
                </div>
            </div>

            <!-- Antecedentes prenatales -->
            <div class="col-md-6">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-heart-pulse me-2"></i>Antecedentes prenatales
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr><td class="text-muted">Embarazos:</td><td><?= e($anamnesis['embarazos'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Deseado:</td>
                        <td><?= isset($anamnesis['deseado']) ? ($anamnesis['deseado'] ? 'Sí' : 'No') : '—' ?></td></tr>
                    <tr><td class="text-muted">Abortos:</td><td><?= e($anamnesis['abortos'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Tiempo gestación:</td><td><?= e($anamnesis['tiempo_gestacion'] ?? '—') ?> semanas</td></tr>
                    <tr><td class="text-muted">Estado emocional:</td><td><?= e($anamnesis['estado_emocional'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Complicaciones:</td><td><?= e($anamnesis['complicaciones'] ?? '—') ?></td></tr>
                </table>
            </div>

            <!-- Antecedentes perinatales -->
            <div class="col-md-6">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-hospital me-2"></i>Antecedentes perinatales
                </h6>
                <table class="table table-sm table-borderless small mb-0">
                    <tr><td class="text-muted">Tipo de parto:</td><td><?= e($anamnesis['tipo_parto'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Sufrimiento fetal:</td>
                        <td><?= isset($anamnesis['sufrimiento_fetal']) ? ($anamnesis['sufrimiento_fetal'] ? 'Sí' : 'No') : '—' ?></td></tr>
                </table>
                <?php if (!empty($anamnesis['estado_recien_nacido_json'])): ?>
                <div class="mt-1 text-muted small"><?= e($anamnesis['estado_recien_nacido_json']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Desarrollo motriz -->
            <?php if (!empty($anamnesis['desarrollo_motriz_json'])): ?>
            <div class="col-md-6">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-person-walking me-2"></i>Desarrollo motriz
                </h6>
                <?php
                $motriz = json_decode($anamnesis['desarrollo_motriz_json'], true) ?? [];
                if (!empty($motriz)):
                ?>
                <table class="table table-sm small mb-0">
                    <thead class="table-light"><tr><th>Conducta</th><th>Edad</th></tr></thead>
                    <tbody>
                    <?php foreach ($motriz as $item): ?>
                    <tr>
                        <td><?= e($item['conducta'] ?? '') ?></td>
                        <td><?= e($item['edad'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Desarrollo del lenguaje -->
            <?php if (!empty($anamnesis['desarrollo_lenguaje_json'])): ?>
            <div class="col-md-6">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-chat-dots me-2"></i>Desarrollo del lenguaje
                </h6>
                <?php
                $lenguaje = json_decode($anamnesis['desarrollo_lenguaje_json'], true) ?? [];
                if (!empty($lenguaje)):
                ?>
                <table class="table table-sm small mb-0">
                    <thead class="table-light"><tr><th>Habilidad</th><th>Edad</th></tr></thead>
                    <tbody>
                    <?php foreach ($lenguaje as $item): ?>
                    <tr>
                        <td><?= e($item['habilidad'] ?? '') ?></td>
                        <td><?= e($item['edad'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Historia escolar -->
            <?php if (!empty($anamnesis['historia_escolar_json'])): ?>
            <div class="col-12">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-book me-2"></i>Historia escolar
                </h6>
                <p class="small mb-0"><?= e($anamnesis['historia_escolar_json']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Observaciones -->
            <?php if (!empty($anamnesis['observaciones'])): ?>
            <div class="col-12">
                <h6 class="fw-bold border-bottom pb-2 text-primary">
                    <i class="bi bi-chat-left-text me-2"></i>Observaciones generales
                </h6>
                <p class="small mb-0"><?= nl2br(e($anamnesis['observaciones'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════ EVALUACIONES -->
<?php elseif ($tabActivo === 'evaluaciones'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-check text-primary me-2"></i>Evaluaciones psicológicas</span>
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <button class="btn btn-sm btn-primary" onclick="abrirModalEvaluacion()">
            <i class="bi bi-plus-circle me-1"></i>Nueva evaluación
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($evaluaciones)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal display-3 d-block mb-3 opacity-25"></i>
            <p>No hay evaluaciones registradas para este paciente.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Test / Instrumento</th>
                    <th>Fecha</th>
                    <th>Aplicado por</th>
                    <th>Resultados</th>
                    <th>Archivo</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($evaluaciones as $ev): ?>
            <tr>
                <td class="fw-semibold"><?= e($ev['nombre_test']) ?></td>
                <td><?= formatearFecha($ev['fecha_aplicacion']) ?></td>
                <td><?= e($ev['op_nombre'] . ' ' . $ev['op_apellido']) ?></td>
                <td class="text-muted" style="max-width:300px">
                    <?php
                    $res = json_decode($ev['resultados_cuantitativos_json'] ?? '{}', true);
                    if (!empty($res)) {
                        foreach ($res as $k => $v) {
                            echo '<span class="badge bg-light text-dark border me-1">' . e($k) . ': ' . e($v) . '</span>';
                        }
                    } else {
                        echo '<span class="text-muted">—</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if (!empty($ev['archivo_adjunto'])): ?>
                    <a href="<?= APP_URL ?>/storage/evaluaciones/<?= e($ev['archivo_adjunto']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($ev['interpretacion_cualitativa'])): ?>
            <tr class="table-light">
                <td colspan="5" class="ps-4 text-muted fst-italic small">
                    <?= nl2br(e(truncar($ev['interpretacion_cualitativa'], 200))) ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal nueva evaluación -->
<div class="modal fade" id="modal-evaluacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Nueva evaluación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Instrumento / Test *</label>
                        <input type="text" class="form-control" id="ev-test"
                               placeholder="Ej: WISC-V, Test CARAS-R, TONI-2...">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha de aplicación *</label>
                        <input type="date" class="form-control" id="ev-fecha"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Interpretación cualitativa</label>
                        <textarea class="form-control" id="ev-interpretacion" rows="3"
                                  placeholder="Descripción de resultados..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-eval">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ DIAGNÓSTICO -->
<?php elseif ($tabActivo === 'diagnostico'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-search-heart text-primary me-2"></i>Diagnósticos</span>
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <button class="btn btn-sm btn-primary" onclick="abrirModalDiagnostico()">
            <i class="bi bi-plus-circle me-1"></i>Nuevo diagnóstico
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($diagnosticos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search display-3 d-block mb-3 opacity-25"></i>
            <p>No hay diagnósticos registrados.</p>
        </div>
        <?php else: ?>
        <?php foreach ($diagnosticos as $dx): ?>
        <div class="card border mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1"><?= e($dx['impresion_diagnostica']) ?></h6>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if (!empty($dx['codigo_dsm5'])): ?>
                            <span class="badge bg-primary">DSM-5: <?= e($dx['codigo_dsm5']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($dx['codigo_cie10'])): ?>
                            <span class="badge bg-info text-dark">CIE-10: <?= e($dx['codigo_cie10']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($dx['codigo_cie11'])): ?>
                            <span class="badge bg-secondary">CIE-11: <?= e($dx['codigo_cie11']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($dx['nivel_severidad'])): ?>
                            <?php
                            $sevColor = ['leve'=>'success','moderado'=>'warning','severo'=>'danger'];
                            ?>
                            <span class="badge bg-<?= $sevColor[$dx['nivel_severidad']] ?? 'secondary' ?>">
                                Severidad: <?= ucfirst($dx['nivel_severidad']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($dx['diagnostico_diferencial'])): ?>
                        <div class="small text-muted">
                            <strong>Diferencial:</strong> <?= e($dx['diagnostico_diferencial']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($dx['observaciones'])): ?>
                        <div class="small text-muted mt-1"><?= nl2br(e($dx['observaciones'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end text-muted small" style="flex-shrink:0">
                        <div><?= formatearFecha($dx['fecha_diagnostico']) ?></div>
                        <div><?= e($dx['op_nombre'] . ' ' . $dx['op_apellido']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal nuevo diagnóstico -->
<div class="modal fade" id="modal-diagnostico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-search-heart me-2"></i>Nuevo diagnóstico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Impresión diagnóstica *</label>
                        <textarea class="form-control" id="dx-impresion" rows="2"
                                  placeholder="Descripción del diagnóstico clínico..."></textarea>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Código DSM-5</label>
                        <input type="text" class="form-control" id="dx-dsm5" placeholder="Ej: F84.0">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Código CIE-10</label>
                        <input type="text" class="form-control" id="dx-cie10" placeholder="Ej: F84.0">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Severidad</label>
                        <select class="form-select" id="dx-severidad">
                            <option value="">Sin clasificar</option>
                            <option value="leve">Leve</option>
                            <option value="moderado">Moderado</option>
                            <option value="severo">Severo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Diagnóstico diferencial</label>
                        <input type="text" class="form-control" id="dx-diferencial"
                               placeholder="Otros diagnósticos considerados...">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha *</label>
                        <input type="date" class="form-control" id="dx-fecha"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="dx-observaciones" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-dx">
                    <i class="bi bi-check-lg me-1"></i>Guardar diagnóstico
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ PLAN -->
<?php elseif ($tabActivo === 'plan'): ?>
<div class="row g-3">
    <?php foreach ($planes as $plan): ?>
    <div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <i class="bi bi-diagram-3 text-primary me-2"></i>
                Plan de intervención
                <?php
                $planColor = ['borrador'=>'secondary','activo'=>'success','completado'=>'dark','suspendido'=>'danger'];
                ?>
                <span class="badge bg-<?= $planColor[$plan['estado']] ?? 'secondary' ?> ms-2">
                    <?= ucfirst($plan['estado']) ?>
                </span>
            </span>
            <small class="text-muted"><?= formatearFecha($plan['created_at']) ?></small>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small fw-semibold mb-1">Enfoque terapéutico</div>
                    <p class="mb-0"><?= e($plan['enfoque_terapeutico'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small fw-semibold mb-1">Frecuencia</div>
                    <p class="mb-0"><?= e($plan['frecuencia_sesiones']) ?> ses/semana</p>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small fw-semibold mb-1">Duración estimada</div>
                    <p class="mb-0"><?= e($plan['duracion_estimada_semanas']) ?> semanas</p>
                </div>
                <?php if (!empty($plan['objetivos_generales'])): ?>
                <div class="col-12">
                    <div class="text-muted small fw-semibold mb-1">Objetivos generales</div>
                    <p class="small mb-0"><?= nl2br(e($plan['objetivos_generales'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($plan['tecnicas_json'])): ?>
                <div class="col-12">
                    <div class="text-muted small fw-semibold mb-1">Técnicas terapéuticas</div>
                    <?php
                    $tecnicas = json_decode($plan['tecnicas_json'], true) ?? [];
                    if (is_array($tecnicas)):
                        foreach ($tecnicas as $t):
                    ?>
                    <span class="badge bg-light text-dark border me-1 mb-1"><?= e($t) ?></span>
                    <?php endforeach; else: ?>
                    <p class="small mb-0"><?= e($plan['tecnicas_json']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="text-muted small">
                        Responsable: <?= e($plan['op_nombre'] . ' ' . $plan['op_apellido']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($planes)): ?>
    <div class="col-12">
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-diagram-3 display-3 d-block mb-3 opacity-25"></i>
            <p class="mb-3">No hay plan de intervención registrado.</p>
            <?php if (tienePermiso('pacientes.ver_lista')): ?>
            <button class="btn btn-primary" onclick="abrirModalPlan()">
                <i class="bi bi-plus-circle me-1"></i>Crear plan
            </button>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <?php else: ?>
    <div class="col-12 d-flex justify-content-end">
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <button class="btn btn-primary btn-sm" onclick="abrirModalPlan()">
            <i class="bi bi-plus-circle me-1"></i>Nuevo plan
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal nuevo plan -->
<div class="modal fade" id="modal-plan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i>Plan de intervención</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Objetivos generales *</label>
                        <textarea class="form-control" id="plan-objetivos" rows="3"
                                  placeholder="Describe los objetivos terapéuticos principales..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Enfoque terapéutico</label>
                        <input type="text" class="form-control" id="plan-enfoque"
                               placeholder="Ej: Cognitivo-Conductual, Gestalt, ABA...">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Frecuencia (ses/semana)</label>
                        <select class="form-select" id="plan-frecuencia">
                            <option value="1">1 por semana</option>
                            <option value="2" selected>2 por semana</option>
                            <option value="3">3 por semana</option>
                            <option value="5">5 por semana</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Duración (semanas)</label>
                        <input type="number" class="form-control" id="plan-duracion"
                               value="12" min="1" max="104">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Estado inicial</label>
                        <select class="form-select" id="plan-estado">
                            <option value="borrador">Borrador</option>
                            <option value="activo">Activo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-plan">
                    <i class="bi bi-check-lg me-1"></i>Guardar plan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ SESIONES -->
<?php elseif ($tabActivo === 'sesiones'): ?>
<div class="row g-3">
    <!-- Formulario nueva sesión -->
    <?php if (tienePermiso('pacientes.ver_lista')): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 fw-semibold">
                <i class="bi bi-plus-circle text-primary me-2"></i>Registrar sesión
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fecha y hora *</label>
                    <input type="datetime-local" class="form-control" id="ses-fecha">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">N° de sesión</label>
                    <input type="number" class="form-control" id="ses-numero"
                           value="<?= count($sesiones) + 1 ?>" min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Objetivo de la sesión *</label>
                    <textarea class="form-control" id="ses-objetivo" rows="2"
                              placeholder="¿Qué se trabajó en esta sesión?"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Técnicas aplicadas</label>
                    <input type="text" class="form-control" id="ses-tecnicas"
                           placeholder="Ej: Role playing, exposición gradual...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Respuesta del paciente</label>
                    <textarea class="form-control" id="ses-respuesta" rows="2"
                              placeholder="Observaciones sobre la respuesta del paciente..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Asistencia</label>
                    <select class="form-select" id="ses-asistio">
                        <option value="asistio">Asistió</option>
                        <option value="falto">Faltó</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="reprogramada">Reprogramada</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tareas asignadas</label>
                    <textarea class="form-control" id="ses-tareas" rows="2"
                              placeholder="Actividades para realizar en casa..."></textarea>
                </div>
                <button class="btn btn-primary w-100" id="btn-guardar-sesion">
                    <i class="bi bi-check-lg me-1"></i>Guardar sesión
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lista de sesiones -->
    <div class="col-lg-<?= tienePermiso('pacientes.ver_lista') ? '8' : '12' ?>">
        <?php if (empty($sesiones)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-chat-heart display-3 d-block mb-3 opacity-25"></i>
                <p>No hay sesiones registradas aún.</p>
            </div>
        </div>
        <?php else: ?>
        <?php
        $bAs = ['asistio'=>'success','falto'=>'danger','cancelada'=>'secondary','reprogramada'=>'warning'];
        foreach ($sesiones as $s):
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span>
                    <span class="badge bg-secondary me-2">Ses. <?= $s['numero_sesion'] ?></span>
                    <strong><?= formatearFechaHora($s['fecha_sesion']) ?></strong>
                    <span class="badge bg-<?= $bAs[$s['asistio']] ?? 'secondary' ?> ms-2"><?= $s['asistio'] ?></span>
                </span>
                <small class="text-muted"><?= e($s['op_nombre'] . ' ' . $s['op_apellido']) ?></small>
            </div>
            <?php if ($s['asistio'] === 'asistio'): ?>
            <div class="card-body small">
                <?php if (!empty($s['objetivo_sesion'])): ?>
                <div class="mb-2">
                    <span class="text-muted fw-semibold">Objetivo:</span>
                    <?= nl2br(e($s['objetivo_sesion'])) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($s['tecnicas_aplicadas'])): ?>
                <div class="mb-2">
                    <span class="text-muted fw-semibold">Técnicas:</span>
                    <?= e($s['tecnicas_aplicadas']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($s['respuesta_paciente'])): ?>
                <div class="mb-2">
                    <span class="text-muted fw-semibold">Respuesta del paciente:</span>
                    <?= nl2br(e($s['respuesta_paciente'])) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($s['observaciones_clinicas'])): ?>
                <div class="mb-2">
                    <span class="text-muted fw-semibold">Observaciones:</span>
                    <?= nl2br(e($s['observaciones_clinicas'])) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($s['tareas_asignadas'])): ?>
                <div class="alert alert-light py-2 mb-0">
                    <i class="bi bi-house-heart me-1"></i>
                    <strong>Tarea para casa:</strong> <?= e($s['tareas_asignadas']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════ SEGUIMIENTO -->
<?php elseif ($tabActivo === 'seguimiento'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up text-primary me-2"></i>Seguimiento y avance</span>
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <button class="btn btn-sm btn-primary" onclick="abrirModalSeguimiento()">
            <i class="bi bi-plus-circle me-1"></i>Registrar seguimiento
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($seguimientos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-graph-up display-3 d-block mb-3 opacity-25"></i>
            <p>No hay registros de seguimiento aún.</p>
        </div>
        <?php else: ?>
        <?php foreach ($seguimientos as $sg): ?>
        <div class="card border mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong><?= formatearFecha($sg['fecha_evaluacion']) ?></strong>
                        <small class="text-muted ms-2"><?= e($sg['op_nombre'] . ' ' . $sg['op_apellido']) ?></small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold fs-4 text-<?= $sg['escala_avance'] >= 70 ? 'success' : ($sg['escala_avance'] >= 40 ? 'warning' : 'danger') ?>">
                            <?= $sg['escala_avance'] ?>%
                        </div>
                        <div class="text-muted small">Avance</div>
                    </div>
                </div>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-<?= $sg['escala_avance'] >= 70 ? 'success' : ($sg['escala_avance'] >= 40 ? 'warning' : 'danger') ?>"
                         style="width:<?= $sg['escala_avance'] ?>%"></div>
                </div>
                <?php if (!empty($sg['observaciones'])): ?>
                <p class="small text-muted mb-0"><?= nl2br(e($sg['observaciones'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal seguimiento -->
<div class="modal fade" id="modal-seguimiento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2"></i>Registrar seguimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha *</label>
                        <input type="date" class="form-control" id="seg-fecha"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Escala de avance (0-100) *</label>
                        <input type="number" class="form-control" id="seg-escala"
                               min="0" max="100" value="50">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="seg-observaciones" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-seg">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$pacienteIdJs = $pacienteId;
$extraJs = '
<script>
const PACIENTE_ID = ' . $pacienteIdJs . ';

// --- Abrir modales ---
function abrirModalEvaluacion() { new bootstrap.Modal(document.getElementById("modal-evaluacion")).show(); }
function abrirModalDiagnostico() { new bootstrap.Modal(document.getElementById("modal-diagnostico")).show(); }
function abrirModalPlan() { new bootstrap.Modal(document.getElementById("modal-plan")).show(); }
function abrirModalSeguimiento() { new bootstrap.Modal(document.getElementById("modal-seguimiento")).show(); }
function nuevaSesion() { window.location.href = "?id=" + PACIENTE_ID + "&tab=sesiones"; }

// --- Guardar evaluación ---
const btnEval = document.getElementById("btn-guardar-eval");
if (btnEval) {
    btnEval.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/evaluaciones/guardar.php", {
            method: "POST",
            body: { paciente_id: PACIENTE_ID, nombre_test: document.getElementById("ev-test").value,
                    fecha_aplicacion: document.getElementById("ev-fecha").value,
                    interpretacion_cualitativa: document.getElementById("ev-interpretacion").value }
        });
        SGC.btnLoading(this, false);
        if (json.success) { SGC.toast("Evaluación guardada."); setTimeout(() => location.reload(), 700); }
        else SGC.toast(json.message || "Error.", "danger");
    });
}

// --- Guardar diagnóstico ---
const btnDx = document.getElementById("btn-guardar-dx");
if (btnDx) {
    btnDx.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/diagnosticos/guardar.php", {
            method: "POST",
            body: {
                paciente_id:          PACIENTE_ID,
                impresion_diagnostica: document.getElementById("dx-impresion").value,
                codigo_dsm5:          document.getElementById("dx-dsm5").value,
                codigo_cie10:         document.getElementById("dx-cie10").value,
                nivel_severidad:      document.getElementById("dx-severidad").value,
                diagnostico_diferencial: document.getElementById("dx-diferencial").value,
                fecha_diagnostico:    document.getElementById("dx-fecha").value,
                observaciones:        document.getElementById("dx-observaciones").value
            }
        });
        SGC.btnLoading(this, false);
        if (json.success) { SGC.toast("Diagnóstico guardado."); setTimeout(() => location.reload(), 700); }
        else SGC.toast(json.message || "Error.", "danger");
    });
}

// --- Guardar plan ---
const btnPlan = document.getElementById("btn-guardar-plan");
if (btnPlan) {
    btnPlan.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/planes/guardar.php", {
            method: "POST",
            body: {
                paciente_id:             PACIENTE_ID,
                objetivos_generales:     document.getElementById("plan-objetivos").value,
                enfoque_terapeutico:     document.getElementById("plan-enfoque").value,
                frecuencia_sesiones:     document.getElementById("plan-frecuencia").value,
                duracion_estimada_semanas: document.getElementById("plan-duracion").value,
                estado:                  document.getElementById("plan-estado").value
            }
        });
        SGC.btnLoading(this, false);
        if (json.success) { SGC.toast("Plan guardado."); setTimeout(() => location.reload(), 700); }
        else SGC.toast(json.message || "Error.", "danger");
    });
}

// --- Guardar sesión ---
const btnSes = document.getElementById("btn-guardar-sesion");
if (btnSes) {
    // Establecer fecha/hora por defecto
    const ahora = new Date();
    ahora.setMinutes(0, 0, 0);
    document.getElementById("ses-fecha").value = ahora.toISOString().slice(0,16);

    btnSes.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/sesiones/guardar.php", {
            method: "POST",
            body: {
                paciente_id:           PACIENTE_ID,
                numero_sesion:         document.getElementById("ses-numero").value,
                fecha_sesion:          document.getElementById("ses-fecha").value.replace("T"," ") + ":00",
                objetivo_sesion:       document.getElementById("ses-objetivo").value,
                tecnicas_aplicadas:    document.getElementById("ses-tecnicas").value,
                respuesta_paciente:    document.getElementById("ses-respuesta").value,
                asistio:               document.getElementById("ses-asistio").value,
                tareas_asignadas:      document.getElementById("ses-tareas").value
            }
        });
        SGC.btnLoading(this, false);
        if (json.success) { SGC.toast("Sesión registrada correctamente."); setTimeout(() => location.reload(), 700); }
        else SGC.toast(json.message || "Error.", "danger");
    });
}

// --- Guardar seguimiento ---
const btnSeg = document.getElementById("btn-guardar-seg");
if (btnSeg) {
    btnSeg.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/seguimientos/guardar.php", {
            method: "POST",
            body: {
                paciente_id:    PACIENTE_ID,
                escala_avance:  document.getElementById("seg-escala").value,
                fecha_evaluacion: document.getElementById("seg-fecha").value,
                observaciones:  document.getElementById("seg-observaciones").value
            }
        });
        SGC.btnLoading(this, false);
        if (json.success) { SGC.toast("Seguimiento guardado."); setTimeout(() => location.reload(), 700); }
        else SGC.toast(json.message || "Error.", "danger");
    });
}
</script>';
include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
