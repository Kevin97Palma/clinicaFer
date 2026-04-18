<?php
/**
 * SGC — Centro de notificaciones
 * Lista todas las notificaciones del usuario, paginadas.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/roles.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_once dirname(__DIR__, 2) . '/includes/auth_check.php';
require_once dirname(__DIR__, 2) . '/includes/role_check.php';

requireAuth();

$pdo    = db();
$userId = usuarioId();

// Paginación
$perPage = 20;
$pagina  = max(1, (int) ($_GET['p'] ?? 1));
$offset  = ($pagina - 1) * $perPage;

// Total de notificaciones del usuario
$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :uid"
);
$stmtTotal->execute([':uid' => $userId]);
$total      = (int) $stmtTotal->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

// Notificaciones paginadas
$stmtList = $pdo->prepare(
    "SELECT * FROM notificaciones
     WHERE usuario_id = :uid
     ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmtList->bindValue(':uid',    $userId, PDO::PARAM_INT);
$stmtList->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmtList->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmtList->execute();
$notificaciones = $stmtList->fetchAll();

// Total no leídas (para el encabezado)
$stmtNoLeidas = $pdo->prepare(
    "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :uid AND leida = 0"
);
$stmtNoLeidas->execute([':uid' => $userId]);
$noLeidas = (int) $stmtNoLeidas->fetchColumn();

// Mapa de iconos y colores por tipo
$iconoMap = [
    'tarea_vencida' => ['icono' => 'exclamation-triangle-fill', 'color' => 'danger'],
    'cita_proxima'  => ['icono' => 'calendar-check-fill',       'color' => 'primary'],
    'sin_actividad' => ['icono' => 'person-x-fill',             'color' => 'warning'],
    'sistema'       => ['icono' => 'info-circle-fill',          'color' => 'info'],
];

$pageTitle  = 'Notificaciones';
$activeMenu = 'notificaciones';

if ($noLeidas > 0) {
    $pageActions = '<button class="btn btn-sm btn-outline-secondary" id="btn-marcar-todas-page">
        <i class="bi bi-check2-all me-1"></i>Marcar todas como leídas
    </button>';
}

include dirname(__DIR__, 2) . '/includes/layout/header.php';
?>

<?php if ($noLeidas > 0): ?>
<div class="alert alert-info alert-dismissible py-2 d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-bell-fill"></i>
    <span>Tienes <strong><?= $noLeidas ?></strong> notificación<?= $noLeidas !== 1 ? 'es' : '' ?> sin leer.</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($notificaciones)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
            Sin notificaciones todavía.
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($notificaciones as $n):
                $meta    = $iconoMap[$n['tipo']] ?? $iconoMap['sistema'];
                $noLeida = !(bool)$n['leida'];
            ?>
            <li class="list-group-item px-4 py-3 <?= $noLeida ? 'sgc-notif--unread' : '' ?>"
                id="notif-row-<?= $n['id'] ?>">
                <div class="d-flex gap-3 align-items-start">
                    <!-- Icono -->
                    <div class="mt-1 flex-shrink-0">
                        <i class="bi bi-<?= $meta['icono'] ?> fs-5 text-<?= $meta['color'] ?>"></i>
                    </div>

                    <!-- Contenido -->
                    <div class="flex-grow-1">
                        <div class="<?= $noLeida ? 'fw-semibold' : 'text-muted' ?>">
                            <?= e($n['mensaje']) ?>
                        </div>
                        <div class="small text-muted mt-1">
                            <?= formatearFechaHora($n['created_at']) ?>
                            &nbsp;·&nbsp;
                            <?php
                            $tipoLabel = [
                                'tarea_vencida' => 'Tarea vencida',
                                'cita_proxima'  => 'Cita próxima',
                                'sin_actividad' => 'Sin actividad',
                                'sistema'       => 'Sistema',
                            ][$n['tipo']] ?? $n['tipo'];
                            ?>
                            <span class="badge bg-<?= $meta['color'] ?> bg-opacity-25 text-<?= $meta['color'] ?> border border-<?= $meta['color'] ?>">
                                <?= $tipoLabel ?>
                            </span>
                        </div>
                    </div>

                    <!-- Acción marcar leída -->
                    <div class="flex-shrink-0">
                        <?php if ($noLeida): ?>
                        <button class="btn btn-sm btn-outline-secondary btn-marcar-leida"
                                data-id="<?= $n['id'] ?>"
                                title="Marcar como leída">
                            <i class="bi bi-check2"></i>
                        </button>
                        <?php else: ?>
                        <span class="text-success small"><i class="bi bi-check2-all"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?p=<?= $pagina - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($totalPages, $pagina + 2); $i++): ?>
                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pagina < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?p=<?= $pagina + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$extraJs = '<script>
const API_URL = "' . APP_URL . '/api/notificaciones/marcar-leida.php";

// Marcar una notificación como leída
document.querySelectorAll(".btn-marcar-leida").forEach(function(btn) {
    btn.addEventListener("click", async function() {
        const id = parseInt(this.dataset.id, 10);
        await fetch(API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });
        // Actualizar UI sin recargar
        const row = document.getElementById("notif-row-" + id);
        if (row) {
            row.classList.remove("sgc-notif--unread");
            row.querySelector(".fw-semibold")?.classList.replace("fw-semibold", "text-muted");
            this.replaceWith(\'<span class="text-success small"><i class="bi bi-check2-all"></i></span>\');
        }
    });
});

// Marcar todas como leídas (botón del encabezado de página)
document.getElementById("btn-marcar-todas-page")?.addEventListener("click", async function() {
    SGC.btnLoading(this);
    await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ all: true })
    });
    SGC.btnLoading(this, false);
    SGC.toast("Todas las notificaciones marcadas como leídas.", "success");
    setTimeout(() => location.reload(), 600);
});
</script>';

include dirname(__DIR__, 2) . '/includes/layout/footer.php';
?>
