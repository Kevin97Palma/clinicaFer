<?php
/**
 * SGC — Módulo de Consentimientos
 * Lista todos los pacientes de la clínica con su estado de consentimiento.
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

// Filtro de búsqueda
$busqueda = trim($_GET['q'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';

// Cargar pacientes con LEFT JOIN a consentimientos (el más reciente por paciente)
$whereParts = ['p.clinica_id = :cid', 'p.deleted_at IS NULL'];
$params     = [':cid' => $clinicaId];

if ($busqueda !== '') {
    $whereParts[] = "(p.nombre LIKE :q OR p.apellido LIKE :q OR p.codigo_paciente LIKE :q)";
    $params[':q'] = "%{$busqueda}%";
}

$whereStr = implode(' AND ', $whereParts);

$sql = "SELECT
            p.id,
            p.nombre,
            p.apellido,
            p.codigo_paciente,
            p.estado AS estado_paciente,
            c.id        AS cons_id,
            c.tipo      AS cons_tipo,
            c.fecha_firma,
            c.fecha_vencimiento,
            c.estado    AS cons_estado,
            c.archivo_path
        FROM pacientes p
        LEFT JOIN consentimientos c
            ON c.id = (
                SELECT id FROM consentimientos
                WHERE paciente_id = p.id
                ORDER BY created_at DESC
                LIMIT 1
            )
        WHERE {$whereStr}
        ORDER BY p.apellido, p.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

// Aplicar filtro de estado en PHP (para incluir 'sin_registro')
if ($filtroEstado !== '') {
    $registros = array_filter($registros, function ($r) use ($filtroEstado) {
        $estado = $r['cons_estado'] ?? 'sin_registro';
        return $estado === $filtroEstado;
    });
    $registros = array_values($registros);
}

// Contadores resumen
$totalPendiente   = 0;
$totalFirmado     = 0;
$totalVencido     = 0;
$totalSinRegistro = 0;
foreach ($registros as $r) {
    $estado = $r['cons_estado'] ?? 'sin_registro';
    match ($estado) {
        'pendiente'    => $totalPendiente++,
        'firmado'      => $totalFirmado++,
        'vencido'      => $totalVencido++,
        default        => $totalSinRegistro++,
    };
}

$pageTitle  = 'Consentimientos';
$activeMenu = 'consentimientos';
$pageActions = '<a href="' . APP_URL . '/modules/pacientes/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Volver a Pacientes
</a>';

auditarAcceso('VER_LISTA', 'consentimientos');
include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning"><?= $totalPendiente ?></div>
            <div class="small text-muted">Pendientes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= $totalFirmado ?></div>
            <div class="small text-muted">Firmados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-danger"><?= $totalVencido ?></div>
            <div class="small text-muted">Vencidos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-secondary"><?= $totalSinRegistro ?></div>
            <div class="small text-muted">Sin registro</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Buscar paciente..." value="<?= e($busqueda) ?>">
            </div>
            <div class="col-sm-4">
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos los estados</option>
                    <option value="pendiente"    <?= $filtroEstado === 'pendiente'    ? 'selected' : '' ?>>Pendiente</option>
                    <option value="firmado"      <?= $filtroEstado === 'firmado'      ? 'selected' : '' ?>>Firmado</option>
                    <option value="vencido"      <?= $filtroEstado === 'vencido'      ? 'selected' : '' ?>>Vencido</option>
                    <option value="sin_registro" <?= $filtroEstado === 'sin_registro' ? 'selected' : '' ?>>Sin registro</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <?php if ($busqueda || $filtroEstado): ?>
                <a href="<?= APP_URL ?>/modules/consentimientos/index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de consentimientos -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Fecha firma</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-file-earmark-x fs-3 d-block mb-2"></i>
                        No se encontraron registros.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($registros as $r):
                    $consEstado = $r['cons_estado'] ?? 'sin_registro';
                    $badgeMap = [
                        'pendiente'    => ['warning', 'Pendiente'],
                        'firmado'      => ['success', 'Firmado'],
                        'vencido'      => ['danger',  'Vencido'],
                        'sin_registro' => ['secondary', 'Sin registro'],
                    ];
                    [$badgeColor, $badgeLabel] = $badgeMap[$consEstado] ?? ['secondary', 'Desconocido'];
                ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $r['id'] ?>"
                           class="fw-semibold text-decoration-none">
                            <?= e($r['nombre'] . ' ' . $r['apellido']) ?>
                        </a>
                    </td>
                    <td><code class="small"><?= e($r['codigo_paciente']) ?></code></td>
                    <td class="small text-muted"><?= $r['cons_tipo'] ? e($r['cons_tipo']) : '—' ?></td>
                    <td class="small"><?= $r['fecha_firma'] ? formatearFecha($r['fecha_firma']) : '—' ?></td>
                    <td class="small">
                        <?php if ($r['fecha_vencimiento']): ?>
                            <?php
                            $hoy = new DateTime();
                            $venc = new DateTime($r['fecha_vencimiento']);
                            $vencidoClass = $venc < $hoy ? 'text-danger fw-semibold' : '';
                            ?>
                            <span class="<?= $vencidoClass ?>"><?= formatearFecha($r['fecha_vencimiento']) ?></span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $badgeColor ?>"><?= $badgeLabel ?></span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <?php if (in_array($consEstado, ['pendiente', 'sin_registro'])): ?>
                            <button class="btn btn-sm btn-warning"
                                    onclick="abrirModalConsentimiento(<?= $r['id'] ?>, '<?= e($r['nombre'] . ' ' . $r['apellido']) ?>')"
                                    title="Subir consentimiento firmado">
                                <i class="bi bi-upload me-1"></i>Subir
                            </button>
                            <?php elseif ($consEstado === 'vencido'): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="abrirModalConsentimiento(<?= $r['id'] ?>, '<?= e($r['nombre'] . ' ' . $r['apellido']) ?>')"
                                    title="Renovar consentimiento">
                                <i class="bi bi-arrow-clockwise me-1"></i>Renovar
                            </button>
                            <?php endif; ?>

                            <?php if ($r['archivo_path']): ?>
                            <a href="<?= APP_URL ?>/<?= e($r['archivo_path']) ?>"
                               class="btn btn-sm btn-outline-primary" target="_blank"
                               title="Descargar archivo">
                                <i class="bi bi-download"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Subir consentimiento firmado -->
<div class="modal fade" id="modal-consentimiento" tabindex="-1" aria-labelledby="modal-cons-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cons-label">
                    <i class="bi bi-file-earmark-check me-2 text-success"></i>Registrar consentimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-consentimiento" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="paciente_id" id="cons-paciente-id">

                    <p class="text-muted small mb-3">
                        Paciente: <strong id="cons-paciente-nombre"></strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de consentimiento *</label>
                        <select name="tipo" id="cons-tipo" class="form-select" required>
                            <option value="Consentimiento informado">Consentimiento informado</option>
                            <option value="Autorización de tratamiento">Autorización de tratamiento</option>
                            <option value="Autorización de grabación">Autorización de grabación</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha de firma *</label>
                            <input type="date" name="fecha_firma" id="cons-fecha-firma"
                                   class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="cons-fecha-vencimiento"
                                   class="form-control">
                            <div class="form-text">Dejar vacío si no vence.</div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">Archivo firmado</label>
                        <input type="file" name="archivo" id="cons-archivo"
                               class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Formatos: PDF, JPG, PNG. Máx. <?= UPLOAD_MAX_MB ?>MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn-guardar-cons">
                        <i class="bi bi-check-lg me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
let modalCons;

document.addEventListener("DOMContentLoaded", function () {
    modalCons = new bootstrap.Modal(document.getElementById("modal-consentimiento"));

    // Fecha de firma por defecto = hoy
    const hoy = new Date().toISOString().split("T")[0];
    document.getElementById("cons-fecha-firma").value = hoy;

    document.getElementById("form-consentimiento").addEventListener("submit", async function (e) {
        e.preventDefault();
        const btn = document.getElementById("btn-guardar-cons");
        SGC.btnLoading(btn);

        const formData = new FormData(this);

        try {
            const resp = await fetch("' . APP_URL . '/api/consentimientos/guardar.php", {
                method: "POST",
                body: formData
            });
            const json = await resp.json();
            SGC.btnLoading(btn, false);
            if (json.success) {
                SGC.toast("Consentimiento guardado correctamente.", "success");
                modalCons.hide();
                setTimeout(() => location.reload(), 800);
            } else {
                SGC.toast(json.message || "Error al guardar.", "danger");
            }
        } catch (err) {
            SGC.btnLoading(btn, false);
            SGC.toast("Error de conexión.", "danger");
        }
    });
});

function abrirModalConsentimiento(pacienteId, pacienteNombre) {
    document.getElementById("cons-paciente-id").value = pacienteId;
    document.getElementById("cons-paciente-nombre").textContent = pacienteNombre;
    document.getElementById("form-consentimiento").reset();
    const hoy = new Date().toISOString().split("T")[0];
    document.getElementById("cons-fecha-firma").value = hoy;
    modalCons.show();
}
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
