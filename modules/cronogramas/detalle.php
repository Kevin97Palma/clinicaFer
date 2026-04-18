<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('pacientes.ver_lista');

$cronogramaId = (int)($_GET['id'] ?? 0);
if (!$cronogramaId) {
    header('Location: ' . APP_URL . '/modules/cronogramas/index.php');
    exit;
}

$pdo       = db();
$clinicaId = clinicaId();

// Cargar cronograma + plan + paciente
$stmt = $pdo->prepare(
    "SELECT cr.*, p.nombre, p.apellido, p.codigo_paciente, p.id as paciente_id,
            pi.enfoque_terapeutico, pi.frecuencia_sesiones, pi.duracion_estimada_semanas
     FROM cronogramas cr
     JOIN pacientes p ON p.id = cr.paciente_id
     LEFT JOIN planes_intervencion pi ON pi.id = cr.plan_id
     WHERE cr.id = :id AND cr.clinica_id = :cid"
);
$stmt->execute([':id' => $cronogramaId, ':cid' => $clinicaId]);
$cronograma = $stmt->fetch();

if (!$cronograma) {
    header('Location: ' . APP_URL . '/modules/cronogramas/index.php');
    exit;
}

$pageTitle  = 'Cronograma — ' . $cronograma['nombre'] . ' ' . $cronograma['apellido'];
$activeMenu = 'cronogramas';

// Tareas del cronograma
$stmtTareas = $pdo->prepare(
    "SELECT t.*, u.nombre as resp_nombre, u.apellido as resp_apellido
     FROM tareas_cronograma t
     LEFT JOIN usuarios u ON u.id = t.responsable_id
     WHERE t.cronograma_id = :cid
     ORDER BY t.orden ASC, t.fecha_programada ASC"
);
$stmtTareas->execute([':cid' => $cronogramaId]);
$tareas = $stmtTareas->fetchAll();

$avance = calcularAvanceCronograma($cronograma['sesiones_completadas'], $cronograma['total_sesiones']);

// Agrupar por tipo para stats
$porTipo = [];
$porEstado = [];
foreach ($tareas as $t) {
    $porTipo[$t['tipo']] = ($porTipo[$t['tipo']] ?? 0) + 1;
    $porEstado[$t['estado']] = ($porEstado[$t['estado']] ?? 0) + 1;
}

auditarAcceso('VER_CRONOGRAMA', 'cronogramas', $cronogramaId);
include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- Encabezado -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h5 class="mb-0 fw-bold">
                        <?= e($cronograma['nombre'] . ' ' . $cronograma['apellido']) ?>
                    </h5>
                    <code class="small"><?= e($cronograma['codigo_paciente']) ?></code>
                    <?php
                    $cronColor = ['activo'=>'success','completado'=>'dark','suspendido'=>'danger','borrador_auto'=>'warning'];
                    ?>
                    <span class="badge bg-<?= $cronColor[$cronograma['estado']] ?? 'secondary' ?>">
                        <?= ucfirst($cronograma['estado']) ?>
                    </span>
                </div>
                <?php if (!empty($cronograma['enfoque_terapeutico'])): ?>
                <div class="text-muted small">
                    <?= e($cronograma['enfoque_terapeutico']) ?> ·
                    <?= $cronograma['frecuencia_sesiones'] ?> ses/semana ·
                    <?= $cronograma['duracion_estimada_semanas'] ?> semanas estimadas
                </div>
                <?php endif; ?>
                <div class="text-muted small mt-1">
                    Fin estimado: <strong><?= formatearFecha($cronograma['fecha_fin_estimada']) ?></strong>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $cronograma['paciente_id'] ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1"></i>Perfil
                </a>
                <a href="<?= APP_URL ?>/modules/cronogramas/index.php"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Cronogramas
                </a>
            </div>
        </div>

        <!-- Barra de progreso -->
        <div class="mt-3">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted"><?= $cronograma['sesiones_completadas'] ?>/<?= $cronograma['total_sesiones'] ?> sesiones completadas</span>
                <span class="fw-bold"><?= $avance ?>%</span>
            </div>
            <div class="progress" style="height:10px">
                <div class="progress-bar bg-success" style="width:<?= $avance ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Stats rápidas -->
<div class="row g-3 mb-3">
    <?php
    $stats = [
        ['label'=>'Total tareas', 'val'=>count($tareas), 'icon'=>'list-check', 'color'=>'primary'],
        ['label'=>'Completadas', 'val'=>$porEstado['completada'] ?? 0, 'icon'=>'check-circle', 'color'=>'success'],
        ['label'=>'Pendientes', 'val'=>$porEstado['pendiente'] ?? 0, 'icon'=>'clock', 'color'=>'warning'],
        ['label'=>'Canceladas', 'val'=>$porEstado['cancelada'] ?? 0, 'icon'=>'x-circle', 'color'=>'danger'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-<?= $s['color'] ?>-subtle text-<?= $s['color'] ?>"
                     style="width:42px;height:42px;flex-shrink:0">
                    <i class="bi bi-<?= $s['icon'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $s['label'] ?></div>
                    <div class="fw-bold fs-5"><?= $s['val'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Lista de tareas -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check text-primary me-2"></i>Tareas del cronograma</span>
        <?php if (tienePermiso('pacientes.ver_lista')): ?>
        <button class="btn btn-sm btn-primary" onclick="abrirModalTarea()">
            <i class="bi bi-plus-circle me-1"></i>Nueva tarea
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tareas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar2-x display-3 d-block mb-3 opacity-25"></i>
            <p>No hay tareas programadas en este cronograma.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tarea</th>
                    <th>Tipo</th>
                    <th>Fecha programada</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $tipoIcon  = ['sesion'=>'chat-heart','evaluacion'=>'journal-check','tarea_hogar'=>'house-heart','seguimiento'=>'graph-up'];
            $tipoColor = ['sesion'=>'primary','evaluacion'=>'info','tarea_hogar'=>'success','seguimiento'=>'warning'];
            $estadoColor = ['pendiente'=>'secondary','en_progreso'=>'primary','completada'=>'success','cancelada'=>'danger'];
            $hoy = date('Y-m-d');
            foreach ($tareas as $i => $t):
                $fechaStr = substr($t['fecha_programada'], 0, 10);
                $vencida  = ($t['estado'] === 'pendiente' && $fechaStr < $hoy);
            ?>
            <tr class="<?= $vencida ? 'table-danger' : '' ?>">
                <td>
                    <span class="badge bg-light text-dark border"><?= $i+1 ?></span>
                </td>
                <td>
                    <div class="fw-semibold"><?= e($t['titulo']) ?></div>
                    <?php if (!empty($t['descripcion'])): ?>
                    <div class="text-muted"><?= e(truncar($t['descripcion'], 60)) ?></div>
                    <?php endif; ?>
                    <?php if ($vencida): ?>
                    <small class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Vencida</small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $tipoColor[$t['tipo']] ?? 'secondary' ?>-subtle text-<?= $tipoColor[$t['tipo']] ?? 'secondary' ?> border">
                        <i class="bi bi-<?= $tipoIcon[$t['tipo']] ?? 'check' ?> me-1"></i>
                        <?= ucfirst(str_replace('_', ' ', $t['tipo'])) ?>
                    </span>
                </td>
                <td><?= formatearFecha($t['fecha_programada']) ?></td>
                <td>
                    <?= !empty($t['resp_nombre'])
                        ? e($t['resp_nombre'] . ' ' . $t['resp_apellido'])
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <span class="badge bg-<?= $estadoColor[$t['estado']] ?? 'secondary' ?>">
                        <?= ucfirst(str_replace('_', ' ', $t['estado'])) ?>
                    </span>
                </td>
                <td>
                    <?php if ($t['estado'] === 'pendiente' || $t['estado'] === 'en_progreso'): ?>
                    <button class="btn btn-sm btn-outline-success"
                            onclick="completarTarea(<?= $t['id'] ?>, this)"
                            title="Marcar como completada">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal nueva tarea -->
<div class="modal fade" id="modal-tarea" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nueva tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Título *</label>
                        <input type="text" class="form-control" id="tarea-titulo"
                               placeholder="Ej: Sesión 12 — Exposición gradual">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tipo *</label>
                        <select class="form-select" id="tarea-tipo">
                            <option value="sesion">Sesión terapéutica</option>
                            <option value="evaluacion">Evaluación</option>
                            <option value="tarea_hogar">Tarea en casa</option>
                            <option value="seguimiento">Seguimiento</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha programada *</label>
                        <input type="date" class="form-control" id="tarea-fecha"
                               value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="tarea-descripcion" rows="2"
                                  placeholder="Detalles de la tarea..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-tarea">
                    <i class="bi bi-check-lg me-1"></i>Agregar tarea
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
const CRONOGRAMA_ID = ' . $cronogramaId . ';

function abrirModalTarea() { new bootstrap.Modal(document.getElementById("modal-tarea")).show(); }

async function completarTarea(tareaId, btn) {
    if (!await SGC.confirmar("¿Marcar esta tarea como completada?")) return;
    SGC.btnLoading(btn);
    const json = await SGC.apiFetch("' . APP_URL . '/api/cronogramas/completar-tarea.php", {
        method: "POST",
        body: { tarea_id: tareaId }
    });
    SGC.btnLoading(btn, false);
    if (json.success) { SGC.toast("Tarea completada."); setTimeout(() => location.reload(), 600); }
    else SGC.toast(json.message || "Error.", "danger");
}

document.getElementById("btn-guardar-tarea").addEventListener("click", async function() {
    SGC.btnLoading(this);
    const json = await SGC.apiFetch("' . APP_URL . '/api/cronogramas/guardar-tarea.php", {
        method: "POST",
        body: {
            cronograma_id:    CRONOGRAMA_ID,
            titulo:           document.getElementById("tarea-titulo").value,
            tipo:             document.getElementById("tarea-tipo").value,
            fecha_programada: document.getElementById("tarea-fecha").value,
            descripcion:      document.getElementById("tarea-descripcion").value
        }
    });
    SGC.btnLoading(this, false);
    if (json.success) { SGC.toast("Tarea agregada."); setTimeout(() => location.reload(), 600); }
    else SGC.toast(json.message || "Error.", "danger");
});
</script>';
include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
