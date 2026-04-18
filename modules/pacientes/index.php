<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('pacientes.ver_lista');

$pageTitle  = 'Pacientes';
$activeMenu = 'pacientes';
$pageActions = tienePermiso('pacientes.registrar')
    ? '<a href="' . APP_URL . '/modules/pacientes/registro.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo paciente</a>'
    : '';

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

// Operativos disponibles para reasignación (solo para supervisores+)
$puedeReasignar = tieneNivel(ROL_SUPERVISOR);
$operativos = [];
if ($puedeReasignar) {
    $stmtOp = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido FROM usuarios u
         JOIN usuario_clinica uc ON uc.usuario_id = u.id
         WHERE uc.clinica_id = :cid AND uc.rol IN ('operativo','supervisor') AND u.activo = 1
         ORDER BY u.nombre"
    );
    $stmtOp->execute([':cid' => $clinicaId]);
    $operativos = $stmtOp->fetchAll();
}

// Filtros
$busqueda = trim($_GET['q'] ?? '');
$estado   = $_GET['estado'] ?? '';
$flujo    = $_GET['flujo'] ?? '';
$pagina   = max(1, (int)($_GET['p'] ?? 1));
$porPagina = 15;
$offset   = ($pagina - 1) * $porPagina;

$where  = "WHERE p.clinica_id = :cid AND p.deleted_at IS NULL";
$params = [':cid' => $clinicaId];

if ($busqueda) {
    $where .= " AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.codigo_paciente LIKE :q OR p.representante_nombre LIKE :q)";
    $params[':q'] = '%' . $busqueda . '%';
}
if ($estado) {
    $where .= " AND p.estado = :estado";
    $params[':estado'] = $estado;
}
if ($flujo) {
    $where .= " AND p.estado_flujo = :flujo";
    $params[':flujo'] = $flujo;
}

// Solo operativos ven sus propios pacientes
if ($rol = rolActivo() === ROL_OPERATIVO) {
    $where .= " AND p.operativo_asignado_id = :uid";
    $params[':uid'] = $userId;
}

// Total para paginación
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM pacientes p $where");
$stmtTotal->execute($params);
$total   = (int) $stmtTotal->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

// Datos
$stmtPac = $pdo->prepare(
    "SELECT p.id, p.codigo_paciente, p.nombre, p.apellido, p.fecha_nacimiento,
            p.sexo, p.estado, p.estado_flujo, p.fecha_ingreso,
            p.representante_nombre, p.operativo_asignado_id as operativo_id,
            u.nombre as operativo_nombre, u.apellido as operativo_apellido,
            ses_last.ultima_sesion,
            tc_venc.tarea_vencida
     FROM pacientes p
     LEFT JOIN usuarios u ON u.id = p.operativo_asignado_id
     LEFT JOIN (
         SELECT paciente_id, MAX(fecha_sesion) as ultima_sesion
         FROM sesiones GROUP BY paciente_id
     ) ses_last ON ses_last.paciente_id = p.id
     LEFT JOIN (
         SELECT paciente_id, MIN(fecha_programada) as tarea_vencida
         FROM tareas_cronograma
         WHERE estado = 'pendiente' AND fecha_programada < CURDATE()
         GROUP BY paciente_id
     ) tc_venc ON tc_venc.paciente_id = p.id
     $where
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmtPac->execute(array_merge($params, [':limit' => $porPagina, ':offset' => $offset]));
$pacientes = $stmtPac->fetchAll();

/**
 * Genera badge de estado clínico según última sesión y tareas vencidas.
 */
function badgeEstadoClinico(?string $ultimaSesion, ?string $tareaVencida): string {
    if ($tareaVencida) {
        $fecha = (new DateTime($tareaVencida))->format('d/m/Y');
        return "<span class='badge rounded-pill bg-danger sgc-badge-alerta'
                      data-bs-toggle='tooltip' title='Tarea vencida desde $fecha'>
                    <i class='bi bi-exclamation-triangle-fill me-1'></i>Vencida
                </span>";
    }
    if (!$ultimaSesion) {
        return "<span class='badge rounded-pill bg-secondary sgc-badge-alerta'
                      data-bs-toggle='tooltip' title='Sin sesiones registradas'>
                    <i class='bi bi-dash-circle me-1'></i>Sin iniciar
                </span>";
    }
    $dias = (new DateTime())->diff(new DateTime($ultimaSesion))->days;
    if ($dias <= 7) {
        $fecha = (new DateTime($ultimaSesion))->format('d/m/Y');
        return "<span class='badge rounded-pill bg-success sgc-badge-alerta'
                      data-bs-toggle='tooltip' title='Última sesión: $fecha'>
                    <i class='bi bi-check-circle-fill me-1'></i>Al día
                </span>";
    }
    if ($dias <= 21) {
        $fecha = (new DateTime($ultimaSesion))->format('d/m/Y');
        return "<span class='badge rounded-pill bg-warning text-dark sgc-badge-alerta'
                      data-bs-toggle='tooltip' title='Última sesión: $fecha'>
                    <i class='bi bi-clock-history me-1'></i>{$dias} días
                </span>";
    }
    $fecha = (new DateTime($ultimaSesion))->format('d/m/Y');
    return "<span class='badge rounded-pill bg-warning text-dark sgc-badge-alerta'
                  data-bs-toggle='tooltip' title='Última sesión: $fecha'>
                <i class='bi bi-exclamation-circle me-1'></i>Sin actividad
            </span>";
}

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?= e($busqueda) ?>"
                           placeholder="Buscar por nombre, código o representante...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach (['registrado','en_espera','activo','egresado','suspendido'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $estado === $opt ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $opt)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="flujo">
                    <option value="">Todos los flujos</option>
                    <?php foreach (['registrado','anamnesis','evaluacion','diagnostico','plan','sesiones','seguimiento'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $flujo === $opt ? 'selected' : '' ?>>
                        <?= ucfirst($opt) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="?" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de pacientes -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-people text-primary me-2"></i><?= $total ?> paciente(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Paciente</th>
                        <th>Edad</th>
                        <th>Representante</th>
                        <th>Psicólogo asignado</th>
                        <th>Estado</th>
                        <th>Flujo clínico</th>
                        <th>Estado clínico</th>
                        <th>Ingreso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td><code class="small"><?= e($p['codigo_paciente']) ?></code></td>
                        <td>
                            <div class="fw-semibold"><?= e($p['nombre'] . ' ' . $p['apellido']) ?></div>
                            <small class="text-muted"><?= $p['sexo'] === 'M' ? 'Masculino' : 'Femenino' ?></small>
                        </td>
                        <td><?= calcularEdad($p['fecha_nacimiento']) ?> años</td>
                        <td class="small text-muted"><?= e($p['representante_nombre']) ?></td>
                        <td class="small">
                            <div class="d-flex align-items-center gap-1">
                                <span class="<?= $p['operativo_nombre'] ? '' : 'text-warning' ?>">
                                    <?= $p['operativo_nombre'] ? e($p['operativo_nombre'] . ' ' . $p['operativo_apellido']) : 'Sin asignar' ?>
                                </span>
                                <?php if ($puedeReasignar): ?>
                                <button class="btn btn-link btn-sm p-0 ms-1 text-muted"
                                        onclick="abrirReasignar(<?= $p['id'] ?>, <?= (int)($p['operativo_id'] ?? 0) ?>, '<?= e($p['nombre'] . ' ' . $p['apellido']) ?>')"
                                        title="Cambiar psicólogo">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= badgeEstado($p['estado']) ?></td>
                        <td><?= badgeFlujo($p['estado_flujo']) ?></td>
                        <td><?= badgeEstadoClinico($p['ultima_sesion'] ?? null, $p['tarea_vencida'] ?? null) ?></td>
                        <td class="small text-muted"><?= formatearFecha($p['fecha_ingreso']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= APP_URL ?>/modules/pacientes/perfil.php?id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Ver perfil">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= APP_URL ?>/modules/historial/index.php?paciente_id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-outline-success" title="Historial clínico">
                                    <i class="bi bi-file-medical"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pacientes)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-people display-6 d-block mb-2"></i>
                        No se encontraron pacientes con los filtros seleccionados.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPaginas > 1): ?>
    <div class="card-footer bg-white border-0">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= $totalPaginas; $i++):
                    $q = http_build_query(array_merge($_GET, ['p' => $i]));
                ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $q ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php if ($puedeReasignar && !empty($operativos)): ?>
<!-- Modal reasignar psicólogo -->
<div class="modal fade" id="modal-reasignar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Cambiar psicólogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Paciente: <strong id="reasignar-nombre"></strong></p>
                <label class="form-label fw-semibold">Psicólogo asignado</label>
                <select class="form-select" id="reasignar-operativo">
                    <option value="">Sin asignar</option>
                    <?php foreach ($operativos as $op): ?>
                    <option value="<?= $op['id'] ?>"><?= e($op['nombre'] . ' ' . $op['apellido']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-confirmar-reasignar">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$extraJs = '
<script>
let modalReasignar;
let pacienteReasignarId = null;

document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("modal-reasignar");
    if (el) modalReasignar = new bootstrap.Modal(el);

    // Inicializar tooltips para badges de estado clínico
    document.querySelectorAll("[data-bs-toggle=\'tooltip\']").forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});

function abrirReasignar(pacienteId, operativoActualId, nombrePaciente) {
    pacienteReasignarId = pacienteId;
    document.getElementById("reasignar-nombre").textContent = nombrePaciente;
    const sel = document.getElementById("reasignar-operativo");
    if (sel) sel.value = operativoActualId || "";
    modalReasignar.show();
}

const btnReasignar = document.getElementById("btn-confirmar-reasignar");
if (btnReasignar) {
    btnReasignar.addEventListener("click", async function() {
        SGC.btnLoading(this);
        const json = await SGC.apiFetch("' . APP_URL . '/api/pacientes/asignar-operativo.php", {
            method: "POST",
            body: {
                paciente_id:  pacienteReasignarId,
                operativo_id: document.getElementById("reasignar-operativo").value
            }
        });
        SGC.btnLoading(this, false);
        if (json.success) {
            SGC.toast("Psicologo reasignado correctamente.");
            modalReasignar.hide();
            setTimeout(() => location.reload(), 700);
        } else {
            SGC.toast(json.message || "Error al reasignar.", "danger");
        }
    });
}
</script>';
include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
