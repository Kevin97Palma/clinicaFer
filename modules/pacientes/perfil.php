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
    "SELECT p.*, u.nombre as op_nombre, u.apellido as op_apellido, u.email as op_email
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

$pageTitle  = $paciente['nombre'] . ' ' . $paciente['apellido'];
$activeMenu = 'pacientes';
$breadcrumb = [
    ['label' => 'Mis pacientes', 'url' => APP_URL . '/modules/pacientes/index.php'],
    ['label' => $paciente['nombre'] . ' ' . $paciente['apellido']],
];

// Citas: próximas y recientes
$stmtCitasProx = $pdo->prepare(
    "SELECT fecha_hora, tipo, estado, duracion_minutos FROM citas
     WHERE paciente_id = :pid AND clinica_id = :cid
     AND fecha_hora >= NOW()
     ORDER BY fecha_hora ASC LIMIT 1"
);
$stmtCitasProx->execute([':pid' => $pacienteId, ':cid' => $clinicaId]);
$proximaCita = $stmtCitasProx->fetch();

$stmtCitas = $pdo->prepare(
    "SELECT fecha_hora, tipo, estado, duracion_minutos FROM citas
     WHERE paciente_id = :pid AND clinica_id = :cid
     ORDER BY fecha_hora DESC LIMIT 5"
);
$stmtCitas->execute([':pid' => $pacienteId, ':cid' => $clinicaId]);
$citas = $stmtCitas->fetchAll();

// Últimas sesiones
$stmtSes = $pdo->prepare(
    "SELECT numero_sesion, fecha_sesion, objetivo_sesion, asistio FROM sesiones
     WHERE paciente_id = :pid ORDER BY fecha_sesion DESC LIMIT 5"
);
$stmtSes->execute([':pid' => $pacienteId]);
$sesiones = $stmtSes->fetchAll();

// Plan activo
$stmtPlan = $pdo->prepare(
    "SELECT id, enfoque_terapeutico, frecuencia_sesiones, duracion_estimada_semanas, estado
     FROM planes_intervencion WHERE paciente_id = :pid AND estado = 'activo' LIMIT 1"
);
$stmtPlan->execute([':pid' => $pacienteId]);
$planActivo = $stmtPlan->fetch();

// Cronograma activo
$cronograma = null;
if ($planActivo) {
    $stmtCron = $pdo->prepare(
        "SELECT id, total_sesiones, sesiones_completadas, estado, fecha_fin_estimada
         FROM cronogramas WHERE plan_id = :plid AND estado = 'activo' LIMIT 1"
    );
    $stmtCron->execute([':plid' => $planActivo['id']]);
    $cronograma = $stmtCron->fetch();
}

// Consentimientos pendientes
$consPendientes = 0;
try {
    $stmtConsCnt = $pdo->prepare(
        "SELECT COUNT(*) FROM consentimientos WHERE paciente_id = :pid AND estado = 'pendiente'"
    );
    $stmtConsCnt->execute([':pid' => $pacienteId]);
    $consPendientes = (int) $stmtConsCnt->fetchColumn();
} catch (Throwable $e) { /* tabla puede no existir */ }

// Última sesión (para badge de estado)
$stmtUltSes = $pdo->prepare(
    "SELECT fecha_sesion FROM sesiones WHERE paciente_id = :pid ORDER BY fecha_sesion DESC LIMIT 1"
);
$stmtUltSes->execute([':pid' => $pacienteId]);
$ultimaSesion = $stmtUltSes->fetchColumn();
$diasSinSesion = $ultimaSesion
    ? (int)(new DateTime())->diff(new DateTime($ultimaSesion))->days
    : null;

// Pasos del flujo clínico
$pasos = [
    'registrado'  => ['num' => 1, 'label' => 'Registro',    'icon' => 'person-plus'],
    'anamnesis'   => ['num' => 2, 'label' => 'Anamnesis',   'icon' => 'clipboard2-pulse'],
    'evaluacion'  => ['num' => 3, 'label' => 'Evaluación',  'icon' => 'journal-check'],
    'diagnostico' => ['num' => 4, 'label' => 'Diagnóstico', 'icon' => 'search-heart'],
    'plan'        => ['num' => 5, 'label' => 'Plan',        'icon' => 'diagram-3'],
    'sesiones'    => ['num' => 6, 'label' => 'Sesiones',    'icon' => 'chat-heart'],
    'seguimiento' => ['num' => 7, 'label' => 'Seguimiento', 'icon' => 'graph-up'],
];
$pasoActual = $pasos[$paciente['estado_flujo']]['num'] ?? 1;

// Badge estado clínico
if ($diasSinSesion === null) {
    $estadoBadge = '<span class="sgc-badge sgc-badge--neutral">Sin sesiones</span>';
} elseif ($diasSinSesion <= 7) {
    $estadoBadge = '<span class="sgc-badge sgc-badge--success"><i class="bi bi-check-circle-fill"></i> Al día</span>';
} elseif ($diasSinSesion <= 21) {
    $estadoBadge = '<span class="sgc-badge sgc-badge--warning"><i class="bi bi-clock-history"></i> Sin actividad</span>';
} else {
    $estadoBadge = '<span class="sgc-badge sgc-badge--danger"><i class="bi bi-exclamation-triangle-fill"></i> Sesión vencida</span>';
}

auditarAcceso('VER_PERFIL', 'pacientes', $pacienteId);
include dirname(__DIR__, 2) . '/includes/layout/header.php';

// Calcular avance del cronograma
$avanceCron = $cronograma
    ? calcularAvanceCronograma($cronograma['sesiones_completadas'], $cronograma['total_sesiones'])
    : 0;
?>

<!-- ── Header del paciente ───────────────────────────────────── -->
<div class="sgc-card mb-3">
    <div class="sgc-patient-header">
        <!-- Avatar -->
        <div class="sgc-avatar sgc-avatar--lg" style="flex-shrink:0">
            <?= strtoupper(substr($paciente['nombre'], 0, 1) . substr($paciente['apellido'], 0, 1)) ?>
        </div>

        <!-- Info principal -->
        <div class="sgc-patient-header__info">
            <div class="sgc-patient-header__name">
                <?= e($paciente['nombre'] . ' ' . $paciente['apellido']) ?>
            </div>
            <div class="sgc-patient-header__meta">
                <code style="font-size:11px;background:var(--sgc-neutral-light);padding:1px 6px;border-radius:4px">
                    <?= e($paciente['codigo_paciente']) ?>
                </code>
                · <?= calcularEdad($paciente['fecha_nacimiento']) ?> años
                · <?= $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino' ?>
                · Ingreso: <?= formatearFecha($paciente['fecha_ingreso']) ?>
            </div>
            <?php if ($paciente['representante_nombre']): ?>
            <div class="sgc-patient-header__meta" style="margin-top:3px">
                <i class="bi bi-person-heart" style="color:var(--sgc-text-muted)"></i>
                Rep: <strong><?= e($paciente['representante_nombre']) ?></strong>
                <?php if ($paciente['representante_telefono']): ?>
                · <a href="tel:<?= e($paciente['representante_telefono']) ?>"
                     style="color:var(--sgc-primary);text-decoration:none">
                    <?= e($paciente['representante_telefono']) ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($paciente['op_nombre']): ?>
            <div class="sgc-patient-header__meta" style="margin-top:3px">
                <i class="bi bi-person-check" style="color:var(--sgc-text-muted)"></i>
                Psicólogo: <strong><?= e($paciente['op_nombre'] . ' ' . $paciente['op_apellido']) ?></strong>
            </div>
            <?php endif; ?>

            <!-- Badges de estado -->
            <div class="sgc-patient-header__badges" style="margin-top:8px">
                <?= badgeEstado($paciente['estado']) ?>
                <?= $estadoBadge ?>
                <?php if ($consPendientes > 0): ?>
                <a href="<?= APP_URL ?>/modules/consentimientos/index.php?paciente_id=<?= $pacienteId ?>"
                   class="sgc-badge sgc-badge--warning" style="text-decoration:none">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Consentimiento pendiente
                </a>
                <?php endif; ?>
                <?php if ($planActivo): ?>
                <span class="sgc-badge sgc-badge--success">
                    <i class="bi bi-diagram-3-fill"></i>
                    Plan activo · <?= e($planActivo['enfoque_terapeutico'] ?? '') ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="sgc-patient-header__actions">
            <a href="<?= APP_URL ?>/modules/historial/paciente.php?id=<?= $pacienteId ?>"
               class="sgc-btn-primary">
                <i class="bi bi-file-medical"></i>
                Historial clínico
            </a>
            <?php if (tienePermiso('citas.gestionar')): ?>
            <button class="sgc-btn-outline" onclick="agendarCita()">
                <i class="bi bi-calendar-plus"></i>
                Agendar cita
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Stepper del flujo clínico ─────────────────────────────── -->
<div class="sgc-card mb-3">
    <div class="sgc-card-body" style="padding:12px 16px">
        <div class="sgc-stepper-v2">
            <?php foreach ($pasos as $key => $paso):
                $num    = $paso['num'];
                $estado = $num < $pasoActual ? 'done' : ($num === $pasoActual ? 'active' : '');
                $tabsMap = [
                    'anamnesis'   => 'anamnesis',
                    'evaluacion'  => 'evaluacion',
                    'diagnostico' => 'diagnostico',
                    'plan'        => 'plan',
                    'sesiones'    => 'sesiones',
                    'seguimiento' => 'seguimiento',
                ];
                $urlPaso = isset($tabsMap[$key])
                    ? APP_URL . '/modules/historial/index.php?paciente_id=' . $pacienteId . '&tab=' . $tabsMap[$key]
                    : '#';
            ?>
            <div class="sgc-step-v2 sgc-step-v2--<?= $estado ?>">
                <div class="sgc-step-v2__circle">
                    <?php if ($estado === 'done'): ?>
                        <i class="bi bi-check-lg" style="font-size:12px"></i>
                    <?php else: ?>
                        <?= $num ?>
                    <?php endif; ?>
                </div>
                <div class="sgc-step-v2__label"><?= e($paso['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Barra de progreso del cronograma -->
        <?php if ($cronograma): ?>
        <div style="margin-top:10px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <span style="font-size:11px;color:var(--sgc-text-muted)">
                    Sesión <?= (int)$cronograma['sesiones_completadas'] ?> de <?= (int)$cronograma['total_sesiones'] ?>
                    <?php if ($cronograma['fecha_fin_estimada']): ?>
                    · Finaliza: <?= formatearFecha($cronograma['fecha_fin_estimada']) ?>
                    <?php endif; ?>
                </span>
                <span style="font-size:11px;font-weight:500;color:var(--sgc-success)"><?= $avanceCron ?>%</span>
            </div>
            <div class="sgc-progress">
                <div class="sgc-progress__bar" style="width:<?= $avanceCron ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Cuerpo: columna principal + panel lateral ─────────────── -->
<div style="display:flex;gap:12px;align-items:flex-start">

    <!-- Columna principal -->
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:12px">

        <!-- Timeline clínico (AJAX) -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-clock-history"></i>
                    Historial reciente
                    <span id="timeline-total" style="font-size:11px;font-weight:400;color:var(--sgc-text-muted)"></span>
                </h2>
                <a href="<?= APP_URL ?>/modules/historial/paciente.php?id=<?= $pacienteId ?>"
                   class="sgc-btn-ghost sgc-btn-ghost--primary" style="font-size:12px">
                    Ver todo <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div id="timeline-container" class="sgc-timeline">
                <!-- Skeleton loader -->
                <div id="timeline-skeleton">
                    <?php for ($s = 0; $s < 4; $s++): ?>
                    <div style="display:flex;gap:10px;padding:10px 16px;align-items:flex-start">
                        <div class="sgc-skeleton" style="width:28px;height:28px;border-radius:50%;flex-shrink:0"></div>
                        <div style="flex:1">
                            <div class="sgc-skeleton" style="height:12px;width:55%;margin-bottom:5px"></div>
                            <div class="sgc-skeleton" style="height:10px;width:35%"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div id="timeline-list"></div>
            </div>
            <div class="sgc-card-footer d-none" id="btn-ver-mas-wrap" style="text-align:center">
                <button class="sgc-btn-ghost" id="btn-ver-mas">
                    <i class="bi bi-chevron-down"></i> Ver más eventos
                </button>
            </div>
        </div>

        <!-- Motivo de consulta + Plan activo -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-chat-left-text"></i>
                    Motivo de consulta
                </h2>
            </div>
            <div class="sgc-card-body">
                <p style="font-size:13px;color:var(--sgc-text-muted);line-height:1.5;margin:0">
                    <?= nl2br(e($paciente['motivo_consulta'] ?? '—')) ?>
                </p>
                <?php if ($planActivo): ?>
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--sgc-border)">
                    <div style="font-size:12px;font-weight:500;color:var(--sgc-text);margin-bottom:4px">
                        <i class="bi bi-diagram-3" style="color:var(--sgc-success)"></i>
                        Plan de intervención activo
                    </div>
                    <div style="font-size:12px;color:var(--sgc-text-muted)">
                        Enfoque: <?= e($planActivo['enfoque_terapeutico'] ?? '—') ?>
                        · <?= (int)$planActivo['frecuencia_sesiones'] ?> ses/sem
                        · <?= (int)$planActivo['duracion_estimada_semanas'] ?> semanas
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Citas recientes -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-calendar-check"></i>
                    Citas recientes
                </h2>
                <a href="<?= APP_URL ?>/modules/citas/index.php?paciente_id=<?= $pacienteId ?>"
                   class="sgc-btn-ghost sgc-btn-ghost--primary" style="font-size:12px">
                    Ver agenda <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($citas)): ?>
            <div class="sgc-empty-state" style="padding:20px">
                <i class="bi bi-calendar-x sgc-empty-state__icon" style="font-size:24px"></i>
                <div class="sgc-empty-state__desc">Sin citas registradas</div>
            </div>
            <?php else: ?>
            <div>
                <?php
                $bColor = [
                    'programada' => 'neutral', 'confirmada' => 'success',
                    'realizada'  => 'primary',  'cancelada'  => 'danger',
                    'no_asistio' => 'warning',
                ];
                foreach ($citas as $c):
                    $cls = $bColor[$c['estado']] ?? 'neutral';
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:9px 14px;border-bottom:1px solid var(--sgc-border)">
                    <div>
                        <div style="font-size:12px;font-weight:500">
                            <?= formatearFechaHora($c['fecha_hora']) ?>
                        </div>
                        <div style="font-size:11px;color:var(--sgc-text-muted)">
                            <?= ucfirst($c['tipo']) ?> · <?= $c['duracion_minutos'] ?> min
                        </div>
                    </div>
                    <span class="sgc-badge sgc-badge--<?= $cls ?>">
                        <?= ucfirst(str_replace('_', ' ', $c['estado'])) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Panel lateral sticky ───────────────────────────── -->
    <div class="sgc-side-panel">

        <!-- Nota rápida de sesión -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-pencil-square"></i>
                    Registrar sesión
                </h2>
            </div>
            <div class="sgc-card-body">
                <!-- Selector de plantilla -->
                <div class="sgc-template-tabs" id="nota-tabs">
                    <button class="sgc-template-tab active" data-tpl="soap">SOAP</button>
                    <button class="sgc-template-tab" data-tpl="dap">DAP</button>
                    <button class="sgc-template-tab" data-tpl="libre">Libre</button>
                </div>

                <!-- Contenido dinámico según plantilla -->
                <div id="nota-fields">
                    <!-- SOAP (default) -->
                    <div id="tpl-soap">
                        <label class="sgc-nota-label">Subjetivo</label>
                        <textarea class="sgc-nota-textarea" id="soap-s" placeholder="Lo que el paciente reporta..."></textarea>
                        <label class="sgc-nota-label">Objetivo</label>
                        <textarea class="sgc-nota-textarea" id="soap-o" placeholder="Observaciones clínicas..."></textarea>
                        <label class="sgc-nota-label">Análisis</label>
                        <textarea class="sgc-nota-textarea" id="soap-a" placeholder="Interpretación..."></textarea>
                        <label class="sgc-nota-label">Plan</label>
                        <textarea class="sgc-nota-textarea" id="soap-p" placeholder="Siguiente sesión..."></textarea>
                    </div>
                    <!-- DAP (oculto) -->
                    <div id="tpl-dap" style="display:none">
                        <label class="sgc-nota-label">Descripción</label>
                        <textarea class="sgc-nota-textarea" id="dap-d" placeholder="Descripción de la sesión..."></textarea>
                        <label class="sgc-nota-label">Análisis</label>
                        <textarea class="sgc-nota-textarea" id="dap-a" placeholder="Análisis clínico..."></textarea>
                        <label class="sgc-nota-label">Plan</label>
                        <textarea class="sgc-nota-textarea" id="dap-p" placeholder="Plan siguiente..."></textarea>
                    </div>
                    <!-- Libre (oculto) -->
                    <div id="tpl-libre" style="display:none">
                        <label class="sgc-nota-label">Notas de la sesión</label>
                        <textarea class="sgc-nota-textarea" id="libre-n"
                                  style="height:120px" placeholder="Escriba las notas de esta sesión..."></textarea>
                    </div>
                </div>

                <!-- Asistencia + botón guardar -->
                <div class="sgc-form-group" style="margin-bottom:10px">
                    <label>¿Asistió el paciente?</label>
                    <select class="form-select form-select-sm" id="nota-asistio">
                        <option value="asistio">✓ Sí asistió</option>
                        <option value="falto">✗ No asistió</option>
                        <option value="cancelada">○ Cancelada</option>
                    </select>
                </div>

                <button class="sgc-btn-primary w-100" id="btn-guardar-sesion"
                        style="width:100%;justify-content:center">
                    <i class="bi bi-check-lg"></i>
                    Guardar sesión
                </button>

                <div id="nota-feedback" style="display:none;margin-top:8px"></div>
            </div>
        </div>

        <!-- Próxima cita -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-calendar-event"></i>
                    Próxima cita
                </h2>
            </div>
            <div class="sgc-card-body">
                <?php if ($proximaCita): ?>
                <div style="font-size:13px;font-weight:500;color:var(--sgc-primary);margin-bottom:4px">
                    <?= formatearFechaHora($proximaCita['fecha_hora']) ?>
                </div>
                <div style="font-size:12px;color:var(--sgc-text-muted);margin-bottom:10px">
                    <?= ucfirst($proximaCita['tipo']) ?> · <?= $proximaCita['duracion_minutos'] ?> min
                </div>
                <button class="sgc-btn-ghost sgc-btn-ghost--primary" style="width:100%;justify-content:center"
                        onclick="agendarCita()">
                    <i class="bi bi-pencil"></i> Cambiar
                </button>
                <?php else: ?>
                <div style="text-align:center;padding:8px 0;color:var(--sgc-text-muted);font-size:12px;margin-bottom:10px">
                    Sin cita próxima agendada
                </div>
                <button class="sgc-btn-primary" style="width:100%;justify-content:center"
                        onclick="agendarCita()">
                    <i class="bi bi-calendar-plus"></i> Agendar cita
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Accesos directos al historial -->
        <div class="sgc-card">
            <div class="sgc-card-header">
                <h2 class="sgc-card-title">
                    <i class="bi bi-folder2-open"></i>
                    Historial clínico
                </h2>
            </div>
            <div class="sgc-quick-access" style="padding:8px 10px">
                <?php
                $linksHistorial = [
                    'anamnesis'   => ['Anamnesis',   'clipboard2-pulse'],
                    'evaluacion'  => ['Evaluación',  'journal-check'],
                    'diagnostico' => ['Diagnóstico', 'search-heart'],
                    'plan'        => ['Plan',        'diagram-3'],
                    'sesiones'    => ['Sesiones',    'chat-heart'],
                    'seguimiento' => ['Seguimiento', 'graph-up'],
                ];
                foreach ($linksHistorial as $tab => [$label, $icon]):
                ?>
                <a href="<?= APP_URL ?>/modules/historial/index.php?paciente_id=<?= $pacienteId ?>&tab=<?= $tab ?>"
                   class="sgc-btn-ghost">
                    <i class="bi bi-<?= $icon ?>"></i> <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal agendar cita -->
<div class="modal fade" id="modal-cita-rapida" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-plus me-2"></i>
                    Nueva cita — <?= e($paciente['nombre']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label" style="font-size:12px;font-weight:500">Fecha y hora *</label>
                        <input type="datetime-local" class="form-control form-control-sm" id="cr-fecha">
                    </div>
                    <div class="col-5">
                        <label class="form-label" style="font-size:12px;font-weight:500">Duración</label>
                        <select class="form-select form-select-sm" id="cr-duracion">
                            <option value="30">30 min</option>
                            <option value="45">45 min</option>
                            <option value="60" selected>60 min</option>
                            <option value="90">90 min</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-size:12px;font-weight:500">Tipo</label>
                        <select class="form-select form-select-sm" id="cr-tipo">
                            <option value="sesion">Sesión terapéutica</option>
                            <option value="valoracion">Valoración</option>
                            <option value="seguimiento">Seguimiento</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-size:12px;font-weight:500">Notas</label>
                        <textarea class="form-control form-control-sm" id="cr-notas" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="sgc-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="sgc-btn-primary" id="btn-guardar-cita-rapida">
                    <i class="bi bi-check-lg"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
const PACIENTE_ID = ' . $pacienteId . ';
const APP_URL = "' . APP_URL . '";

// ── Timeline ──────────────────────────────────────────────────
let tlOffset = 0;
const TL_LIMIT = 8;

const tlColors = {
    sesion:      "#3B6D11",
    cita:        "#185FA5",
    evaluacion:  "#7C3AED",
    diagnostico: "#A32D2D",
    plan:        "#854F0B",
    anamnesis:   "#0891B2",
    seguimiento: "#3B6D11",
};

async function loadTimeline(append) {
    if (!append) tlOffset = 0;
    const url = `${APP_URL}/api/pacientes/timeline.php?id=${PACIENTE_ID}&offset=${tlOffset}&limit=${TL_LIMIT}`;

    const res = await SGC.apiFetch(url);
    if (!res?.success) return;

    document.getElementById("timeline-skeleton").style.display = "none";
    const list = document.getElementById("timeline-list");

    if (!append) list.innerHTML = "";

    const totalEl = document.getElementById("timeline-total");
    if (totalEl) totalEl.textContent = res.data.total + " eventos";

    res.data.eventos.forEach(function(ev) {
        const color = tlColors[ev.tipo] || "#6b6b6b";
        const item = document.createElement("div");
        item.className = "sgc-timeline__item";
        item.innerHTML = `
            <div class="sgc-timeline__dot" style="background:${color}">
                <i class="bi bi-${ev.icono}"></i>
            </div>
            <div class="sgc-timeline__body">
                <div class="sgc-timeline__title">
                    ${ev.tipo_label}
                    <span style="font-weight:400;color:var(--sgc-text-muted)">— ${ev.descripcion_short}</span>
                </div>
                <div class="sgc-timeline__detail">
                    ${ev.fecha_fmt}${ev.operativo ? " · " + ev.operativo : ""}
                </div>
            </div>
            <div class="sgc-timeline__date">${ev.fecha_fmt}</div>`;
        list.appendChild(item);
    });

    tlOffset += res.data.eventos.length;
    const hasMore = tlOffset < res.data.total;
    document.getElementById("btn-ver-mas-wrap").classList.toggle("d-none", !hasMore);
}

// ── Selector de plantilla ─────────────────────────────────────
document.addEventListener("DOMContentLoaded", function() {
    loadTimeline(false);

    document.getElementById("btn-ver-mas").addEventListener("click", function() {
        loadTimeline(true);
    });

    // Tabs de plantilla
    document.querySelectorAll("#nota-tabs .sgc-template-tab").forEach(function(tab) {
        tab.addEventListener("click", function() {
            const tpl = this.dataset.tpl;
            document.querySelectorAll("#nota-tabs .sgc-template-tab").forEach(t => t.classList.remove("active"));
            this.classList.add("active");
            document.querySelectorAll("#nota-fields > div").forEach(d => d.style.display = "none");
            document.getElementById("tpl-" + tpl).style.display = "";
        });
    });

    // Guardar sesión rápida
    document.getElementById("btn-guardar-sesion").addEventListener("click", async function() {
        const btn = this;
        const tpl = document.querySelector("#nota-tabs .sgc-template-tab.active")?.dataset.tpl || "soap";

        let contenido = {};
        if (tpl === "soap") {
            contenido = {
                subjetivo: document.getElementById("soap-s").value,
                objetivo:  document.getElementById("soap-o").value,
                analisis:  document.getElementById("soap-a").value,
                plan:      document.getElementById("soap-p").value,
            };
        } else if (tpl === "dap") {
            contenido = {
                descripcion: document.getElementById("dap-d").value,
                analisis:    document.getElementById("dap-a").value,
                plan:        document.getElementById("dap-p").value,
            };
        } else {
            contenido = { notas: document.getElementById("libre-n").value };
        }

        // Validar que haya algo escrito
        const hasContent = Object.values(contenido).some(v => v.trim().length > 0);
        if (!hasContent) {
            SGC.toast("Escribe al menos una nota antes de guardar.", "warning");
            return;
        }

        SGC.btnLoading(btn, true, "Guardando...");

        const res = await SGC.apiFetch(APP_URL + "/api/sesiones/guardar-rapida.php", {
            method: "POST",
            body: {
                paciente_id:   PACIENTE_ID,
                plantilla:     tpl,
                contenido_json: contenido,
                asistio:       document.getElementById("nota-asistio").value,
                fecha:         new Date().toISOString().slice(0, 10),
            }
        });

        SGC.btnLoading(btn, false);

        if (res?.success) {
            SGC.toast("Sesión #" + res.data.numero_sesion + " guardada correctamente.", "success");

            // Limpiar campos
            document.querySelectorAll(".sgc-nota-textarea").forEach(t => t.value = "");

            // Recargar timeline con highlight
            tlOffset = 0;
            await loadTimeline(false);
            const firstItem = document.querySelector("#timeline-list .sgc-timeline__item");
            if (firstItem && window.SGC?.highlight) SGC.highlight(firstItem);
        } else {
            SGC.toast(res?.message || "Error al guardar la sesión.", "danger");
        }
    });
});

// ── Modal cita rápida ─────────────────────────────────────────
let modalCitaR;
document.addEventListener("DOMContentLoaded", function() {
    const el = document.getElementById("modal-cita-rapida");
    if (el && window.bootstrap) modalCitaR = new bootstrap.Modal(el);
});

function agendarCita() {
    if (!modalCitaR) return;
    const hoy = new Date();
    hoy.setHours(9, 0, 0, 0);
    document.getElementById("cr-fecha").value = hoy.toISOString().slice(0, 16);
    modalCitaR.show();
}

document.addEventListener("DOMContentLoaded", function() {
    var btnCita = document.getElementById("btn-guardar-cita-rapida");
    if (!btnCita) return;

    btnCita.addEventListener("click", async function() {
        SGC.btnLoading(this, true, "Agendando...");
        const res = await SGC.apiFetch(APP_URL + "/api/citas/guardar.php", {
            method: "POST",
            body: {
                paciente_id:      PACIENTE_ID,
                fecha_hora:       document.getElementById("cr-fecha").value.replace("T", " ") + ":00",
                duracion_minutos: document.getElementById("cr-duracion").value,
                tipo:             document.getElementById("cr-tipo").value,
                notas:            document.getElementById("cr-notas").value,
            }
        });
        SGC.btnLoading(this, false);
        if (res?.success) {
            SGC.toast("Cita agendada correctamente.", "success");
            modalCitaR?.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            SGC.toast(res?.message || "Error al agendar.", "danger");
        }
    });
});
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
