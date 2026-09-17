/* SGC Psicología — comportamiento común (sin datos simulados: todo viene del servidor) */
(function () {
  'use strict';

  const meta = (n) => document.querySelector(`meta[name="${n}"]`)?.content || '';
  const BASE = meta('app-url');
  const CSRF = meta('csrf-token');

  const SGC = {
    url: (p) => BASE + '/' + String(p).replace(/^\//, ''),

    async request(path, { method = 'GET', data = null } = {}) {
      const opts = { method, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin' };
      if (method !== 'GET') {
        opts.headers['X-CSRF-Token'] = CSRF;
        opts.body = data instanceof FormData ? data : new URLSearchParams(data || {});
      }
      const res = await fetch(path.startsWith('http') ? path : SGC.url(path), opts);
      let json = {};
      try { json = await res.json(); } catch (e) { json = { success: false, message: 'Respuesta no válida del servidor.' }; }
      if (res.status === 401) { window.location = SGC.url('login'); }
      return json;
    },

    toast(message, type = 'success') {
      let stack = document.querySelector('.toast-stack');
      if (!stack) { stack = document.createElement('div'); stack.className = 'toast-stack'; stack.setAttribute('aria-live', 'polite'); document.body.appendChild(stack); }
      const icons = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-octagon', info: 'info-circle' };
      const el = document.createElement('div');
      el.className = `toast-msg t-${type}`;
      el.setAttribute('role', 'status');
      const i = document.createElement('i'); i.className = `bi bi-${icons[type] || 'info-circle'} mt-1`;
      const span = document.createElement('div'); span.className = 'flex-grow-1'; span.textContent = message;
      const close = document.createElement('button'); close.type = 'button'; close.className = 'btn-close btn-sm'; close.setAttribute('aria-label', 'Cerrar');
      close.onclick = () => el.remove();
      el.append(i, span, close);
      stack.appendChild(el);
      setTimeout(() => el.remove(), type === 'danger' || type === 'warning' ? 9000 : 5000);
    },

    showErrors(form, errors) {
      form.querySelectorAll('.is-invalid').forEach((x) => x.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback.js').forEach((x) => x.remove());
      Object.entries(errors || {}).forEach(([name, msg]) => {
        const field = form.querySelector(`[name="${name}"], [name="${name}[]"]`);
        if (!field) return;
        field.classList.add('is-invalid');
        const fb = document.createElement('div'); fb.className = 'invalid-feedback js d-block'; fb.textContent = msg;
        field.insertAdjacentElement('afterend', fb);
      });
    },
  };
  window.SGC = SGC;

  document.addEventListener('DOMContentLoaded', () => {
    // Mensajes flash del servidor
    document.querySelectorAll('[data-flash]').forEach((n) => SGC.toast(n.dataset.message, n.dataset.flash));

    // Validación frontend (Bootstrap) antes de enviar
    document.querySelectorAll('form.needs-validation').forEach((form) => {
      form.addEventListener('submit', (ev) => {
        if (!form.checkValidity()) { ev.preventDefault(); ev.stopImmediatePropagation(); form.classList.add('was-validated'); SGC.toast('Revise los campos obligatorios.', 'warning'); }
      });
    });

    // Confirmación solo en operaciones sensibles
    document.addEventListener('submit', (ev) => {
      const form = ev.target;
      if (form.dataset.confirm && !form.dataset.confirmed) {
        ev.preventDefault();
        confirmDialog(form.dataset.confirm).then((ok) => { if (ok) { form.dataset.confirmed = '1'; form.requestSubmit ? form.requestSubmit() : form.submit(); } });
      }
    }, true);

    // Formularios AJAX: data-ajax  (recarga o redirige según respuesta)
    document.addEventListener('submit', async (ev) => {
      const form = ev.target;
      if (!form.matches('form[data-ajax]') || ev.defaultPrevented) return;
      ev.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      btn && (btn.disabled = true);
      try {
        const res = await SGC.request(form.action, { method: 'POST', data: new FormData(form) });
        if (res.success) {
          (res.messages || []).forEach(([t, m]) => sessionStorageSafe('push', { t, m }));
          if (res.message) sessionStorageSafe('push', { t: 'success', m: res.message });
          if (res.redirect) { window.location = res.redirect; } else { window.location.reload(); }
        } else {
          SGC.showErrors(form, res.errors);
          SGC.toast(res.message || 'No se pudo guardar.', 'danger');
          btn && (btn.disabled = false);
        }
      } catch (e) {
        SGC.toast('Error de conexión. Intente nuevamente.', 'danger');
        btn && (btn.disabled = false);
      }
    });
    sessionStorageSafe('flush');

    // Prefill de modales: <button data-bs-toggle="modal" data-bs-target="#m" data-fill='{"campo":"valor"}' data-action="url">
    document.addEventListener('show.bs.modal', (ev) => {
      const trigger = ev.relatedTarget; const modal = ev.target;
      if (!trigger) return;
      const form = modal.querySelector('form');
      if (form && trigger.dataset.action) form.action = trigger.dataset.action;
      if (form && trigger.dataset.fill) {
        if (trigger.dataset.reset !== undefined) form.reset();
        const data = JSON.parse(trigger.dataset.fill);
        Object.entries(data).forEach(([k, v]) => {
          const f = form.querySelector(`[name="${k}"]`);
          if (!f) return;
          if (f.type === 'checkbox') f.checked = !!Number(v); else f.value = v ?? '';
          f.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      const title = modal.querySelector('[data-modal-title]');
      if (title && trigger.dataset.title) title.textContent = trigger.dataset.title;
    });

    initQuickSearch();
    initPatientPickers();
    initAutosave();
  });

  // Mensajes que sobreviven a la recarga tras un guardado AJAX
  function sessionStorageSafe(action, item) {
    try {
      const key = 'sgc_toasts';
      if (action === 'push') { const l = JSON.parse(sessionStorage.getItem(key) || '[]'); l.push(item); sessionStorage.setItem(key, JSON.stringify(l)); }
      if (action === 'flush') { const l = JSON.parse(sessionStorage.getItem(key) || '[]'); sessionStorage.removeItem(key); l.forEach((x) => SGC.toast(x.m, x.t)); }
    } catch (e) { /* almacenamiento no disponible */ }
  }

  function confirmDialog(message) {
    return new Promise((resolve) => {
      let modal = document.getElementById('confirm-modal');
      if (!modal) return resolve(window.confirm(message));
      modal.querySelector('[data-confirm-text]').textContent = message;
      const bs = bootstrap.Modal.getOrCreateInstance(modal);
      const ok = modal.querySelector('[data-confirm-ok]');
      let answered = false;
      const onOk = () => { answered = true; bs.hide(); resolve(true); };
      ok.addEventListener('click', onOk, { once: true });
      modal.addEventListener('hidden.bs.modal', () => { ok.removeEventListener('click', onOk); if (!answered) resolve(false); }, { once: true });
      bs.show();
    });
  }
  SGC.confirm = confirmDialog;

  function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

  function renderResults(box, items, onPick) {
    box.innerHTML = '';
    if (!items.length) { const d = document.createElement('div'); d.className = 'p-3 text-muted small'; d.textContent = 'Sin resultados'; box.appendChild(d); }
    items.forEach((p) => {
      const a = document.createElement('a'); a.href = SGC.url('pacientes/' + p.id);
      const l = document.createElement('div'); const n = document.createElement('div'); n.className = 'cell-title'; n.textContent = p.name;
      const s = document.createElement('div'); s.className = 'cell-sub'; s.textContent = `${p.file_number} · ${p.age}${p.identification ? ' · ' + p.identification : ''}`;
      l.append(n, s);
      const st = document.createElement('span'); st.className = 'cell-sub'; st.textContent = p.status_label;
      a.append(l, st);
      if (onPick) a.addEventListener('click', (e) => { e.preventDefault(); onPick(p); });
      box.appendChild(a);
    });
    box.hidden = false;
  }

  function initQuickSearch() {
    const input = document.getElementById('quick-search');
    if (!input) return;
    const box = document.getElementById('quick-search-results');
    const run = debounce(async () => {
      const q = input.value.trim();
      if (q.length < 2) { box.hidden = true; return; }
      const res = await SGC.request('pacientes/buscar?q=' + encodeURIComponent(q));
      if (res.success) renderResults(box, res.data);
    }, 220);
    input.addEventListener('input', run);
    input.addEventListener('keydown', (e) => { if (e.key === 'Escape') { box.hidden = true; input.blur(); } if (e.key === 'Enter') { const f = box.querySelector('a'); if (f) { e.preventDefault(); f.click(); } } });
    document.addEventListener('click', (e) => { if (!e.target.closest('.quick-search')) box.hidden = true; });
    document.addEventListener('keydown', (e) => { if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { e.preventDefault(); input.focus(); } });
  }

  // Selector de paciente con búsqueda: <div data-patient-picker> <input type=hidden name=patient_id> <input type=search>
  function initPatientPickers() {
    document.querySelectorAll('[data-patient-picker]').forEach((wrap) => {
      const hidden = wrap.querySelector('input[type="hidden"]');
      const input = wrap.querySelector('input[type="search"]');
      const box = wrap.querySelector('.search-results');
      const run = debounce(async () => {
        const q = input.value.trim();
        if (q.length < 2) { box.hidden = true; return; }
        const res = await SGC.request('pacientes/buscar?q=' + encodeURIComponent(q));
        if (res.success) renderResults(box, res.data, (p) => { hidden.value = p.id; input.value = `${p.name} (${p.file_number})`; box.hidden = true; hidden.dispatchEvent(new Event('change', { bubbles: true })); });
      }, 220);
      input.addEventListener('input', () => { hidden.value = ''; run(); });
      document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) box.hidden = true; });
    });
  }

  // Guardado parcial automático (anamnesis): <form data-autosave>
  function initAutosave() {
    const form = document.querySelector('form[data-autosave]');
    if (!form) return;
    const status = document.querySelector('[data-autosave-status]');
    let dirty = false;
    const save = debounce(async () => {
      if (!dirty) return;
      dirty = false;
      status && (status.textContent = 'Guardando…');
      const fd = new FormData(form); fd.set('autosave', '1');
      const res = await SGC.request(form.action, { method: 'POST', data: fd });
      status && (status.textContent = res.success ? 'Guardado automáticamente ' + new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }) : 'No se pudo guardar automáticamente');
    }, 2500);
    form.addEventListener('input', () => { dirty = true; status && (status.textContent = 'Cambios sin guardar'); save(); });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => { dirty = false; });
  }
})();
