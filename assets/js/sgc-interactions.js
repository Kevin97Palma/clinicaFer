/**
 * SGC — Micro-interacciones y feedback visual
 * Versión 2.0 — Complementa app.js
 * Incluir DESPUÉS de app.js en footer.php
 */

(function () {
  'use strict';

  /* ── Toast container ─────────────────────────────── */
  let _toastContainer = null;
  function getToastContainer() {
    if (!_toastContainer) {
      _toastContainer = document.createElement('div');
      _toastContainer.className = 'sgc-toast-container';
      document.body.appendChild(_toastContainer);
    }
    return _toastContainer;
  }

  const TOAST_ICONS = {
    success: 'check-circle-fill',
    danger:  'x-circle-fill',
    warning: 'exclamation-triangle-fill',
    info:    'info-circle-fill',
  };

  /**
   * Muestra un toast de notificación.
   * @param {string} msg  - Mensaje a mostrar
   * @param {string} type - success|danger|warning|info
   * @param {number} ms   - Duración en ms (default 3500)
   */
  function toast(msg, type = 'success', ms = 3500) {
    const container = getToastContainer();

    // Máximo 3 toasts apilados
    const existing = container.querySelectorAll('.sgc-toast');
    if (existing.length >= 3) {
      existing[0].remove();
    }

    const icon = TOAST_ICONS[type] || TOAST_ICONS.info;
    const el = document.createElement('div');
    el.className = `sgc-toast sgc-toast--${type}`;
    el.innerHTML = `
      <i class="bi bi-${icon} sgc-toast__icon sgc-toast__icon--${type}"></i>
      <div class="sgc-toast__body">${msg}</div>
      <button class="sgc-toast__close" aria-label="Cerrar">
        <i class="bi bi-x"></i>
      </button>`;

    container.appendChild(el);

    // Slide-up animation
    requestAnimationFrame(() => {
      requestAnimationFrame(() => el.classList.add('show'));
    });

    const dismiss = () => {
      el.classList.add('hide');
      el.classList.remove('show');
      setTimeout(() => el.remove(), 220);
    };

    el.querySelector('.sgc-toast__close').addEventListener('click', dismiss);
    setTimeout(dismiss, ms);
  }

  /* ── Confirm Modal ───────────────────────────────── */
  /**
   * Muestra un modal de confirmación y retorna una Promise<boolean>.
   * @param {Object} opts - { title, message, confirmText, cancelText, danger }
   */
  function confirm(opts = {}) {
    return new Promise((resolve) => {
      const {
        title       = '¿Confirmar acción?',
        message     = 'Esta acción no se puede deshacer.',
        confirmText = 'Confirmar',
        cancelText  = 'Cancelar',
        danger      = false,
      } = opts;

      const backdrop = document.createElement('div');
      backdrop.className = 'sgc-confirm-backdrop';
      backdrop.innerHTML = `
        <div class="sgc-confirm-modal">
          <div class="sgc-confirm-modal__title">${title}</div>
          <div class="sgc-confirm-modal__msg">${message}</div>
          <div class="sgc-confirm-modal__btns">
            <button class="sgc-btn-outline" data-action="cancel">${cancelText}</button>
            <button class="${danger ? 'btn btn-danger btn-sm' : 'sgc-btn-primary'}" data-action="confirm">
              ${confirmText}
            </button>
          </div>
        </div>`;

      document.body.appendChild(backdrop);
      document.body.style.overflow = 'hidden';

      function close(result) {
        document.body.removeChild(backdrop);
        document.body.style.overflow = '';
        resolve(result);
      }

      backdrop.querySelector('[data-action="confirm"]').addEventListener('click', () => close(true));
      backdrop.querySelector('[data-action="cancel"]').addEventListener('click',  () => close(false));
      backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) close(false);
      });
      document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') { close(false); document.removeEventListener('keydown', esc); }
      });
    });
  }

  /* ── Highlight elemento nuevo ────────────────────── */
  /**
   * Resalta brevemente un elemento recién añadido (fondo verde → blanco).
   * @param {HTMLElement} el
   */
  function highlight(el) {
    if (!el) return;
    el.classList.add('highlight-new');
    setTimeout(() => {
      el.style.background = '';
      el.classList.remove('highlight-new');
    }, 1800);
  }

  /* ── Skeleton rows ───────────────────────────────── */
  /**
   * Genera N filas skeleton para una tabla con C columnas.
   * @param {number} rows    - Número de filas
   * @param {number} cols    - Número de columnas
   * @returns {string}       - HTML string
   */
  function skeletonRows(rows = 5, cols = 4) {
    const widths = ['60%', '40%', '70%', '50%', '55%', '45%', '65%'];
    let html = '';
    for (let r = 0; r < rows; r++) {
      html += '<tr>';
      for (let c = 0; c < cols; c++) {
        const w = widths[(r + c) % widths.length];
        html += `<td><span class="skeleton d-block" style="height:14px;width:${w}"></span></td>`;
      }
      html += '</tr>';
    }
    return html;
  }

  /* ── Botón loading (versión mejorada) ────────────── */
  /**
   * Pone un botón en estado de carga o lo restaura.
   * @param {HTMLElement} btn
   * @param {boolean} loading
   * @param {string} loadingText
   */
  function btnLoading(btn, loading = true, loadingText = 'Guardando...') {
    if (!btn) return;
    if (loading) {
      btn._sgcOriginal = btn.innerHTML;
      btn._sgcDisabled = btn.disabled;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"
        style="width:12px;height:12px;border-width:1.5px"></span> ${loadingText}`;
      btn.disabled = true;
    } else {
      if (btn._sgcOriginal !== undefined) {
        btn.innerHTML = btn._sgcOriginal;
      }
      btn.disabled = btn._sgcDisabled || false;
    }
  }

  /* ── data-confirm-* handler ──────────────────────── */
  /**
   * Vincula elementos con data-confirm-title / data-confirm-msg / data-confirm-action.
   * Al hacer click muestra el modal de confirmación y si se acepta ejecuta
   * la URL en data-confirm-action (POST) o simplemente dispara un evento personalizado.
   */
  function initConfirmButtons() {
    document.addEventListener('click', async function (e) {
      const btn = e.target.closest('[data-confirm-title]');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      const title   = btn.dataset.confirmTitle || '¿Confirmar?';
      const message = btn.dataset.confirmMsg   || 'Esta acción no se puede deshacer.';
      const action  = btn.dataset.confirmAction;
      const danger  = btn.dataset.confirmDanger !== undefined;

      const ok = await confirm({ title, message, danger, confirmText: 'Confirmar' });
      if (!ok) return;

      if (action) {
        // Si hay una URL de acción: POST con fetch
        btnLoading(btn, true, 'Procesando...');
        try {
          const res  = await fetch(action, {
            method: 'POST',
            headers: {
              'Content-Type':    'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ id: btn.dataset.confirmId || null }),
          });
          const json = await res.json();
          btnLoading(btn, false);
          if (json.success) {
            toast(json.message || 'Acción completada.', 'success');
            setTimeout(() => location.reload(), 700);
          } else {
            toast(json.message || 'Error al realizar la acción.', 'danger');
          }
        } catch (err) {
          btnLoading(btn, false);
          toast('Error de conexión.', 'danger');
        }
      } else {
        // Disparar evento personalizado para que la página lo maneje
        btn.dispatchEvent(new CustomEvent('sgc:confirmed', { bubbles: true }));
      }
    });
  }

  /* ── Sidebar toggle móvil (nuevo sidebar) ────────── */
  function initSidebarToggle() {
    const sidebar  = document.getElementById('sgc-sidebar');
    const overlay  = document.getElementById('sgc-overlay');
    const btnToggle = document.getElementById('sgc-sidebar-toggle');

    if (!btnToggle || !sidebar) return;

    btnToggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      overlay?.classList.toggle('show');
    });
    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  /* ── Init ────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initConfirmButtons();

    // Inicializar tooltips Bootstrap donde existan
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => {
      if (window.bootstrap?.Tooltip) new bootstrap.Tooltip(el);
    });
  });

  /* ── Sobreescribir window.SGC con versión mejorada ── */
  // Preservar apiFetch del app.js original
  const _apiFetch = window.SGC?.apiFetch;

  window.SGC = Object.assign(window.SGC || {}, {
    toast,
    confirm,
    highlight,
    skeletonRows,
    btnLoading,
    apiFetch: _apiFetch || async function(url, opts = {}) {
      const res = await fetch(url, {
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(opts.headers || {}),
        },
        ...opts,
        body: opts.body && typeof opts.body === 'object'
          ? JSON.stringify(opts.body)
          : opts.body,
      });
      return res.json();
    },
  });

})();
