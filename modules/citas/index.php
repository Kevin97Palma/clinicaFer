<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requireAuth();

$pageTitle  = 'Agenda de Citas';
$activeMenu = 'citas';
$pageActions = tienePermiso('citas.gestionar')
    ? '<button class="btn btn-primary btn-sm" onclick="abrirModalCita()"><i class="bi bi-plus-lg me-1"></i>Nueva cita</button>'
    : '';

$pdo       = db();
$clinicaId = clinicaId();
$userId    = usuarioId();

// Fecha seleccionada (default: hoy)
$fechaSel = $_GET['fecha'] ?? date('Y-m-d');
$vista    = $_GET['vista'] ?? 'semana'; // dia | semana | mes

// Citas de la semana actual
$inicioSemana = date('Y-m-d', strtotime('monday this week', strtotime($fechaSel)));
$finSemana    = date('Y-m-d', strtotime('sunday this week', strtotime($fechaSel)));

$where  = "WHERE c.clinica_id = :cid AND DATE(c.fecha_hora) BETWEEN :ini AND :fin";
$params = [':cid' => $clinicaId, ':ini' => $inicioSemana, ':fin' => $finSemana];

if (rolActivo() === ROL_OPERATIVO) {
    $where .= " AND c.operativo_id = :uid";
    $params[':uid'] = $userId;
} elseif (rolActivo() === ROL_REPRESENTANTE) {
    $where .= " AND p.usuario_representante_id = :uid";
    $params[':uid'] = $userId;
}

$stmt = $pdo->prepare(
    "SELECT c.id, c.fecha_hora, c.duracion_minutos, c.tipo, c.estado, c.notas,
            p.nombre as pac_nombre, p.apellido as pac_apellido, p.codigo_paciente, p.id as paciente_id,
            u.nombre as op_nombre, u.apellido as op_apellido
     FROM citas c
     JOIN pacientes p ON p.id = c.paciente_id
     JOIN usuarios u ON u.id = c.operativo_id
     $where ORDER BY c.fecha_hora ASC"
);
$stmt->execute($params);
$citas = $stmt->fetchAll();

// Operativos para el selector (supervisor+)
$operativos = [];
if (tieneNivel(ROL_SUPERVISOR)) {
    $stmtOp = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido FROM usuarios u
         JOIN usuario_clinica uc ON uc.usuario_id = u.id
         WHERE uc.clinica_id = :cid AND uc.rol IN ('operativo','supervisor') AND u.activo = 1"
    );
    $stmtOp->execute([':cid' => $clinicaId]);
    $operativos = $stmtOp->fetchAll();
}

// Pacientes activos para el modal
$stmtPac = $pdo->prepare(
    "SELECT id, nombre, apellido, codigo_paciente FROM pacientes
     WHERE clinica_id = :cid AND deleted_at IS NULL AND estado != 'egresado'
     ORDER BY nombre"
);
$stmtPac->execute([':cid' => $clinicaId]);
$pacientes = $stmtPac->fetchAll();

// Agrupar citas por día
$citasPorDia = [];
foreach ($citas as $c) {
    $dia = date('Y-m-d', strtotime($c['fecha_hora']));
    $citasPorDia[$dia][] = $c;
}

$diasSemana = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
$coloresTipo = ['valoracion'=>'primary','sesion'=>'success','seguimiento'=>'warning'];
$coloresEstado = ['programada'=>'secondary','confirmada'=>'success','realizada'=>'dark','cancelada'=>'danger','no_asistio'=>'warning'];

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<!-- Navegación de semana -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="?fecha=<?= date('Y-m-d', strtotime($inicioSemana . ' -7 days')) ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <span class="fw-semibold">
                    <?= date('d M', strtotime($inicioSemana)) ?> — <?= date('d M Y', strtotime($finSemana)) ?>
                </span>
                <a href="?fecha=<?= date('Y-m-d', strtotime($inicioSemana . ' +7 days')) ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <a href="?fecha=<?= date('Y-m-d') ?>" class="btn btn-outline-primary btn-sm">Hoy</a>
            </div>
            <div class="text-muted small">
                <i class="bi bi-calendar-week me-1"></i>
                <?= count($citas) ?> cita(s) esta semana
            </div>
        </div>
    </div>
</div>

<!-- Calendario semanal -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 sgc-agenda">
                <thead class="table-light">
                    <tr>
                        <?php
                        for ($i = 0; $i < 7; $i++):
                            $fecha = date('Y-m-d', strtotime($inicioSemana . " +$i days"));
                            $esHoy = $fecha === date('Y-m-d');
                        ?>
                        <th class="text-center py-2 <?= $esHoy ? 'bg-primary text-white' : '' ?>" style="min-width:130px">
                            <div class="fw-semibold"><?= $diasSemana[$i] ?></div>
                            <div class="<?= $esHoy ? 'fs-5 fw-bold' : 'text-muted' ?>">
                                <?= date('d', strtotime($fecha)) ?>
                            </div>
                        </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr style="vertical-align:top">
                        <?php for ($i = 0; $i < 7; $i++):
                            $fecha = date('Y-m-d', strtotime($inicioSemana . " +$i days"));
                            $citasDelDia = $citasPorDia[$fecha] ?? [];
                        ?>
                        <td class="p-1" style="min-height:120px">
                            <?php foreach ($citasDelDia as $cita):
                                $color = $coloresTipo[$cita['tipo']] ?? 'secondary';
                            ?>
                            <div class="sgc-cita-item bg-<?= $color ?>-subtle border border-<?= $color ?> rounded p-1 mb-1 cursor-pointer"
                                 onclick="verCita(<?= $cita['id'] ?>)"
                                 title="<?= e($cita['pac_nombre'] . ' ' . $cita['pac_apellido']) ?>">
                                <div class="small fw-semibold text-<?= $color ?>">
                                    <?= date('H:i', strtotime($cita['fecha_hora'])) ?>
                                    <span class="badge bg-<?= $coloresEstado[$cita['estado']] ?? 'secondary' ?> ms-1" style="font-size:.6rem">
                                        <?= $cita['estado'] ?>
                                    </span>
                                </div>
                                <div class="small text-truncate">
                                    <?= e($cita['pac_nombre'] . ' ' . $cita['pac_apellido']) ?>
                                </div>
                                <div class="fs-7 text-muted"><?= $cita['tipo'] ?> · <?= $cita['duracion_minutos'] ?>min</div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (tienePermiso('citas.gestionar')): ?>
                            <button class="btn btn-link btn-sm text-muted p-0 mt-1 w-100"
                                    onclick="abrirModalCita('<?= $fecha ?>')" title="Agregar cita">
                                <i class="bi bi-plus-circle"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Nueva / Ver Cita -->
<div class="modal fade" id="modal-cita" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cita-titulo"><i class="bi bi-calendar-plus me-2"></i>Nueva Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-cita">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Paciente *</label>
                        <select class="form-select" id="cita-paciente" required>
                            <option value="">Seleccionar paciente...</option>
                            <?php foreach ($pacientes as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                [<?= e($p['codigo_paciente']) ?>] <?= e($p['nombre'] . ' ' . $p['apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($operativos)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Psicólogo *</label>
                        <select class="form-select" id="cita-operativo">
                            <?php foreach ($operativos as $op): ?>
                            <option value="<?= $op['id'] ?>" <?= $op['id'] == $userId ? 'selected' : '' ?>>
                                <?= e($op['nombre'] . ' ' . $op['apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Fecha y hora *</label>
                            <input type="datetime-local" class="form-control" id="cita-fecha" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Duración (min)</label>
                            <select class="form-select" id="cita-duracion">
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60" selected>60 min</option>
                                <option value="90">90 min</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select class="form-select" id="cita-tipo">
                            <option value="sesion">Sesión</option>
                            <option value="valoracion">Valoración</option>
                            <option value="seguimiento">Seguimiento</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Notas</label>
                        <textarea class="form-control" id="cita-notas" rows="2" placeholder="Notas opcionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-cita">
                        <i class="bi bi-check-lg me-1"></i>Guardar cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
const API_CITAS = "' . APP_URL . '/api/citas";
let modalCita;

document.addEventListener("DOMContentLoaded", function() {
    modalCita = new bootstrap.Modal(document.getElementById("modal-cita"));
});

function abrirModalCita(fecha = "") {
    document.getElementById("form-cita").reset();
    if (fecha) {
        document.getElementById("cita-fecha").value = fecha + "T09:00";
    }
    document.getElementById("modal-cita-titulo").innerHTML = "<i class=\"bi bi-calendar-plus me-2\"></i>Nueva Cita";
    modalCita.show();
}

function verCita(id) {
    // TODO: abrir modal de detalle/edición
    alert("Cita #" + id + " — próximamente detalle completo.");
}

document.getElementById("form-cita").addEventListener("submit", async function(e) {
    e.preventDefault();
    const btn = document.getElementById("btn-guardar-cita");
    SGC.btnLoading(btn);

    const data = {
        paciente_id:      document.getElementById("cita-paciente").value,
        fecha_hora:       document.getElementById("cita-fecha").value.replace("T"," ") + ":00",
        duracion_minutos: document.getElementById("cita-duracion").value,
        tipo:             document.getElementById("cita-tipo").value,
        notas:            document.getElementById("cita-notas").value,
    };
    const op = document.getElementById("cita-operativo");
    if (op) data.operativo_id = op.value;

    const json = await SGC.apiFetch(API_CITAS + "/guardar.php", { method:"POST", body: data });
    SGC.btnLoading(btn, false);

    if (json.success) {
        SGC.toast("Cita guardada correctamente.");
        modalCita.hide();
        setTimeout(() => location.reload(), 800);
    } else {
        SGC.toast(json.message || "Error al guardar.", "danger");
    }
});
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
