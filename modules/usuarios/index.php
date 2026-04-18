<?php
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requirePermiso('usuarios.ver');

$pageTitle  = 'Usuarios';
$activeMenu = 'usuarios';
$pageActions = tienePermiso('usuarios.crear')
    ? '<button class="btn btn-primary btn-sm" onclick="abrirModalUsuario()"><i class="bi bi-person-plus me-1"></i>Nuevo usuario</button>'
    : '';

$pdo       = db();
$clinicaId = clinicaId();

$stmt = $pdo->prepare(
    "SELECT u.id, u.cedula, u.nombre, u.apellido, u.email, u.telefono,
            u.activo, u.primer_login, u.created_at,
            GROUP_CONCAT(DISTINCT uc2.rol ORDER BY uc2.rol SEPARATOR ', ') as roles_clinica
     FROM usuarios u
     JOIN usuario_clinica uc ON uc.usuario_id = u.id AND uc.clinica_id = :cid
     LEFT JOIN usuario_clinica uc2 ON uc2.usuario_id = u.id AND uc2.clinica_id = :cid2
     WHERE u.deleted_at IS NULL
     GROUP BY u.id
     ORDER BY u.nombre"
);
$stmt->execute([':cid' => $clinicaId, ':cid2' => $clinicaId]);
$usuarios = $stmt->fetchAll();

$coloresRol = ['superadmin'=>'danger','gerente'=>'warning','supervisor'=>'primary','operativo'=>'success','representante'=>'secondary'];

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Usuario</th>
                        <th>Cédula</th>
                        <th>Email</th>
                        <th>Rol en clínica</th>
                        <th>Estado</th>
                        <th>Ingreso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="sgc-avatar-sm bg-primary text-white" style="width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700">
                                <?= strtoupper(substr($u['nombre'],0,1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></div>
                                <?php if ($u['primer_login']): ?>
                                <small class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Nunca inició sesión</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted small"><?= e($u['cedula']) ?></td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td>
                        <?php foreach (explode(', ', $u['roles_clinica']) as $rol): ?>
                        <span class="badge bg-<?= $coloresRol[$rol] ?? 'secondary' ?> me-1"><?= $rol ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                        <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= formatearFecha($u['created_at']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editarUsuario(<?= $u['id'] ?>)" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal nuevo usuario (estructura base) -->
<div class="modal fade" id="modal-usuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Nuevo usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label fw-semibold">Nombre *</label>
                        <input type="text" class="form-control" id="u-nombre" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Apellido *</label>
                        <input type="text" class="form-control" id="u-apellido" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Cédula *</label>
                        <input type="text" class="form-control" id="u-cedula" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" class="form-control" id="u-telefono"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Email *</label>
                        <input type="email" class="form-control" id="u-email" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">Rol en esta clínica *</label>
                        <select class="form-select" id="u-rol">
                            <option value="operativo">Operativo / Psicólogo</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="gerente">Gerente</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle me-1"></i>
                            La contraseña inicial será la cédula del usuario. Se pedirá cambiarla en el primer ingreso.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarUsuario()">
                    <i class="bi bi-check-lg me-1"></i>Crear usuario
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script>
function abrirModalUsuario() { new bootstrap.Modal(document.getElementById("modal-usuario")).show(); }
function editarUsuario(id) { SGC.toast("Edicion de usuario #"+id+" — proximamente.", "info"); }
async function guardarUsuario() {
    const btn = document.querySelector("#modal-usuario .btn-primary");
    SGC.btnLoading(btn);
    const json = await SGC.apiFetch("' . APP_URL . '/api/usuarios/guardar.php", {
        method: "POST",
        body: {
            nombre:   document.getElementById("u-nombre").value,
            apellido: document.getElementById("u-apellido").value,
            cedula:   document.getElementById("u-cedula").value,
            telefono: document.getElementById("u-telefono").value,
            email:    document.getElementById("u-email").value,
            rol:      document.getElementById("u-rol").value,
        }
    });
    SGC.btnLoading(btn, false);
    if (json.success) {
        SGC.toast("Usuario creado correctamente.");
        bootstrap.Modal.getInstance(document.getElementById("modal-usuario")).hide();
        setTimeout(() => location.reload(), 800);
    } else {
        SGC.toast(json.message || "Error al crear usuario.", "danger");
    }
}
</script>';
include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
