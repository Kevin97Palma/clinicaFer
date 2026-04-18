<?php
require_once dirname(__DIR__, 2) . '/config/app.php';

// Si ya tiene sesión, redirigir al dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['clinica_activa_id'])) {
    header('Location: ' . APP_URL . '/modules/dashboard/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — SGC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
    <style>
        body { background: linear-gradient(135deg, #1a3a5c 0%, #2e6da4 100%); min-height: 100vh; }
        .login-card { max-width: 420px; border: none; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .login-logo { width: 80px; height: 80px; object-fit: contain; }
        .btn-login { background: #1a3a5c; border: none; }
        .btn-login:hover { background: #2e6da4; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

<div class="card login-card w-100">
    <div class="card-body p-5">

        <div class="text-center mb-4">
            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                 style="width:80px;height:80px;">
                <i class="bi bi-heart-pulse-fill text-white fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">SGC Clínica</h4>
            <p class="text-muted small">Sistema de Gestión Clínica Psicológica</p>
        </div>

        <div id="alert-container"></div>

        <form id="form-login" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="usuario@clinica.com" required autocomplete="email">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" id="btn-toggle-pass"
                            title="Mostrar/ocultar contraseña">
                        <i class="bi bi-eye" id="icon-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-login btn-primary btn-lg fw-semibold" id="btn-submit">
                    <span id="btn-text">Ingresar</span>
                    <span id="btn-spinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </form>

    </div>
    <div class="card-footer text-center text-muted small py-3 bg-light rounded-bottom">
        &copy; <?= date('Y') ?> Socket Studio S.A.S. — v<?= APP_VERSION ?>
    </div>
</div>

<!-- Modal selección de clínica -->
<div class="modal fade" id="modal-clinica" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-building me-2"></i>Seleccionar clínica</h5>
            </div>
            <div class="modal-body" id="lista-clinicas"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_URL = '<?= APP_URL ?>/api/auth';

// Toggle mostrar contraseña
document.getElementById('btn-toggle-pass').addEventListener('click', function() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('icon-eye');
    inp.type   = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Mostrar alerta
function showAlert(msg, type = 'danger') {
    document.getElementById('alert-container').innerHTML =
        `<div class="alert alert-${type} alert-dismissible fade show py-2 small" role="alert">
            <i class="bi bi-${type === 'danger' ? 'exclamation-triangle' : 'check-circle'} me-1"></i>${msg}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
         </div>`;
}

// Submit login
document.getElementById('form-login').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn     = document.getElementById('btn-submit');
    const spinner = document.getElementById('btn-spinner');
    const text    = document.getElementById('btn-text');

    btn.disabled   = true;
    spinner.classList.remove('d-none');
    text.textContent = 'Verificando...';
    document.getElementById('alert-container').innerHTML = '';

    try {
        const res = await fetch(API_URL + '/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email:    document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
            })
        });
        const json = await res.json();

        if (json.success) {
            if (json.data.seleccion_requerida) {
                mostrarModalClinicas(json.data.clinicas);
            } else {
                showAlert('Acceso correcto. Redirigiendo...', 'success');
                setTimeout(() => window.location.href = json.data.redirect, 800);
            }
        } else {
            showAlert(json.message);
        }
    } catch (err) {
        showAlert('Error de conexión. Intente nuevamente.');
    } finally {
        btn.disabled   = false;
        spinner.classList.add('d-none');
        text.textContent = 'Ingresar';
    }
});

// Modal de selección de clínica
function mostrarModalClinicas(clinicas) {
    const contenedor = document.getElementById('lista-clinicas');
    contenedor.innerHTML = '<p class="text-muted small mb-3">Usted pertenece a más de una clínica. Seleccione a cuál desea ingresar:</p>';
    clinicas.forEach(c => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-primary w-100 mb-2 text-start';
        btn.innerHTML = `<i class="bi bi-building me-2"></i><strong>${c.clinica_nombre}</strong><br>
                         <small class="text-muted ms-4">Rol: ${c.rol}</small>`;
        btn.addEventListener('click', () => seleccionarClinica(c.clinica_id));
        contenedor.appendChild(btn);
    });
    new bootstrap.Modal(document.getElementById('modal-clinica')).show();
}

async function seleccionarClinica(clinicaId) {
    const res  = await fetch(API_URL + '/seleccionar-clinica.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ clinica_id: clinicaId })
    });
    const json = await res.json();
    if (json.success) window.location.href = json.data.redirect;
}
</script>
</body>
</html>
