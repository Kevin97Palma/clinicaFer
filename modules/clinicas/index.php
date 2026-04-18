<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('clinicas.ver');

$pageTitle  = 'Clínicas';
$activeMenu = 'clinicas';
$pageActions = tienePermiso('clinicas.crear')
    ? '<button class="btn btn-primary btn-sm" onclick="abrirModalClinica()"><i class="bi bi-hospital me-1"></i>Nueva clínica</button>'
    : '';

$pdo = db();

$stmt = $pdo->query(
    "SELECT c.id, c.nombre, c.ruc, c.direccion, c.telefono, c.email, c.activo, c.created_at,
            COUNT(DISTINCT uc.usuario_id) as num_usuarios,
            COUNT(DISTINCT p.id) as num_pacientes
     FROM clinicas c
     LEFT JOIN usuario_clinica uc ON uc.clinica_id = c.id
     LEFT JOIN pacientes p ON p.clinica_id = c.id AND p.deleted_at IS NULL
     WHERE c.deleted_at IS NULL
     GROUP BY c.id ORDER BY c.nombre"
);
$clinicas = $stmt->fetchAll();

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<div class="row g-3">
<?php foreach ($clinicas as $c): ?>
<div class="col-12 col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold"><?= e($c['nombre']) ?></div>
                <small class="text-muted">RUC: <?= e($c['ruc']) ?></small>
            </div>
            <span class="badge bg-<?= $c['activo'] ? 'success' : 'danger' ?>">
                <?= $c['activo'] ? 'Activa' : 'Inactiva' ?>
            </span>
        </div>
        <div class="card-body small text-muted">
            <?php if ($c['direccion']): ?>
            <div class="mb-1"><i class="bi bi-geo-alt me-2"></i><?= e($c['direccion']) ?></div>
            <?php endif; ?>
            <?php if ($c['telefono']): ?>
            <div class="mb-1"><i class="bi bi-telephone me-2"></i><?= e($c['telefono']) ?></div>
            <?php endif; ?>
            <?php if ($c['email']): ?>
            <div class="mb-1"><i class="bi bi-envelope me-2"></i><?= e($c['email']) ?></div>
            <?php endif; ?>
            <hr class="my-2">
            <div class="d-flex gap-3">
                <span><i class="bi bi-people me-1"></i><strong><?= $c['num_usuarios'] ?></strong> usuarios</span>
                <span><i class="bi bi-person-heart me-1"></i><strong><?= $c['num_pacientes'] ?></strong> pacientes</span>
            </div>
        </div>
        <?php if (tienePermiso('clinicas.editar')): ?>
        <div class="card-footer bg-white border-0">
            <button class="btn btn-sm btn-outline-primary w-100" onclick="editarClinica(<?= $c['id'] ?>)">
                <i class="bi bi-pencil me-1"></i>Editar
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($clinicas)): ?>
<div class="col-12 text-center py-5 text-muted">
    <i class="bi bi-hospital display-3 d-block mb-3"></i>No hay clínicas registradas.
</div>
<?php endif; ?>
</div>

<!-- Modal nueva clínica -->
<div class="modal fade" id="modal-clinica" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-hospital me-2"></i>Nueva clínica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Nombre *</label>
                        <input type="text" class="form-control" id="cl-nombre" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">RUC *</label>
                        <input type="text" class="form-control" id="cl-ruc" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" class="form-control" id="cl-telefono"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="cl-email"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Dirección</label>
                        <input type="text" class="form-control" id="cl-direccion"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarClinica()">
                    <i class="bi bi-check-lg me-1"></i>Crear clínica
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function abrirModalClinica() { new bootstrap.Modal(document.getElementById("modal-clinica")).show(); }
function editarClinica(id)   { SGC.toast("Edicion de clinica #"+id+" — proximamente.", "info"); }
async function guardarClinica() {
    const btn = document.querySelector("#modal-clinica .btn-primary");
    SGC.btnLoading(btn);
    const json = await SGC.apiFetch("' . APP_URL . '/api/clinicas/guardar.php", {
        method: "POST",
        body: {
            nombre:    document.getElementById("cl-nombre").value,
            ruc:       document.getElementById("cl-ruc").value,
            telefono:  document.getElementById("cl-telefono").value,
            email:     document.getElementById("cl-email").value,
            direccion: document.getElementById("cl-direccion").value,
        }
    });
    SGC.btnLoading(btn, false);
    if (json.success) {
        SGC.toast("Clinica creada correctamente.");
        bootstrap.Modal.getInstance(document.getElementById("modal-clinica")).hide();
        setTimeout(() => location.reload(), 800);
    } else {
        SGC.toast(json.message || "Error al crear clinica.", "danger");
    }
}
</script>';
include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
