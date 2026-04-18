/**
 * SGC — JavaScript principal
 */

const SGC = (() => {
    // ── Sidebar toggle (móvil) ────────────────────────────────
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const btnToggle = document.getElementById('btn-sidebar-toggle');

    if (btnToggle && sidebar) {
        btnToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay?.classList.toggle('show');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // ── Toast notifications ───────────────────────────────────
    function toast(msg, type = 'success', duration = 4000) {
        let container = document.querySelector('.sgc-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'sgc-toast-container';
            document.body.appendChild(container);
        }

        const icons = { success: 'check-circle-fill', danger: 'exclamation-triangle-fill',
                        warning: 'exclamation-circle-fill', info: 'info-circle-fill' };
        const toastEl = document.createElement('div');
        toastEl.className = `alert alert-${type} alert-dismissible shadow-sm mb-2 py-2 d-flex align-items-center gap-2`;
        toastEl.innerHTML = `
            <i class="bi bi-${icons[type] || 'info-circle-fill'}"></i>
            <span>${msg}</span>
            <button type="button" class="btn-close btn-sm ms-auto" data-bs-dismiss="alert"></button>`;
        container.appendChild(toastEl);

        setTimeout(() => {
            toastEl.classList.add('fade');
            setTimeout(() => toastEl.remove(), 300);
        }, duration);
    }

    // ── Fetch helper con JSON ─────────────────────────────────
    async function apiFetch(url, options = {}) {
        const defaults = {
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        };
        const merged = { ...defaults, ...options };
        if (merged.body && typeof merged.body === 'object') {
            merged.body = JSON.stringify(merged.body);
        }
        const res  = await fetch(url, merged);
        const json = await res.json();
        return json;
    }

    // ── Confirmar acción ──────────────────────────────────────
    function confirmar(msg = '¿Está seguro de realizar esta acción?') {
        return confirm(msg);
    }

    // ── Formatear fecha ───────────────────────────────────────
    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-EC', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // ── Spinner en botón ──────────────────────────────────────
    function btnLoading(btn, loading = true) {
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando...';
            btn.disabled = true;
        } else {
            btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
            btn.disabled = false;
        }
    }

    // ── Auto-hide alerts ──────────────────────────────────────
    document.querySelectorAll('.alert-auto-hide').forEach(el => {
        setTimeout(() => {
            el.classList.add('fade');
            setTimeout(() => el.remove(), 300);
        }, 5000);
    });

    return { toast, apiFetch, confirmar, formatDate, btnLoading };
})();

// Exponer globalmente
window.SGC = SGC;
