<div class="page-head">
  <div>
    <h1>Agenda</h1>
    <p class="lead-sm">Vista por día, semana o mes. El sistema evita reservar dos citas del mismo profesional a la misma hora.</p>
  </div>
  <div class="d-flex gap-2">
    <select class="form-select" id="filter-professional" style="max-width:220px" aria-label="Filtrar por profesional">
      <option value="">Todos los profesionales</option>
      <?php foreach ($professionals as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
    </select>
    <?php if (can('agenda.manage')): ?>
      <button class="btn btn-primary" id="btn-new"><i class="bi bi-plus-lg me-1"></i> Nueva cita</button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-9">
    <div class="card"><div class="card-body"><div id="calendar"></div></div></div>
  </div>
  <div class="col-xl-3">
    <div class="card">
      <div class="card-header"><h2 class="card-title">Hoy</h2></div>
      <div class="card-body">
        <?php foreach ($today as $t): ?>
          <div class="d-flex gap-2 align-items-start mb-3">
            <div class="text-center" style="min-width:48px">
              <div class="cell-title"><?= e(date('H:i', strtotime($t['starts_at']))) ?></div>
              <div class="cell-sub"><?= (int) $t['duration_min'] ?>′</div>
            </div>
            <div class="flex-grow-1 min-w-0">
              <a class="cell-title" href="<?= e(url('pacientes/' . $t['patient_id'])) ?>"><?= e($t['first_name'] . ' ' . $t['last_name']) ?></a>
              <div><?= badge('appointment_status', $t['status']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$today): ?><div class="empty py-3"><i class="bi bi-cup-hot"></i>Sin citas hoy.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (can('agenda.manage')): ?>
<div class="modal fade" id="modal-appointment" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="form-appointment" method="post" action="<?= e(url('agenda/citas')) ?>" class="needs-validation" data-ajax novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title" id="appointment-title">Nueva cita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12" id="picker-wrap" data-patient-picker>
            <label class="form-label" for="ap-patient">Paciente *</label>
            <input type="hidden" name="patient_id" id="ap-patient-id">
            <div class="position-relative">
              <input type="search" class="form-control" id="ap-patient" placeholder="Escriba nombre, cédula o expediente" autocomplete="off">
              <div class="search-results" hidden></div>
            </div>
          </div>
          <div class="col-7">
            <label class="form-label" for="ap-start">Fecha y hora *</label>
            <input type="datetime-local" class="form-control" id="ap-start" name="starts_at" required>
          </div>
          <div class="col-5">
            <label class="form-label" for="ap-duration">Duración (min) *</label>
            <input type="number" class="form-control" id="ap-duration" name="duration_min" min="10" max="480" step="5" value="<?= (int) $default_duration ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="ap-type">Tipo</label>
            <select class="form-select" id="ap-type" name="type"><?= select_options('appointment_type', 'sesion') ?></select>
          </div>
          <div class="col-6">
            <label class="form-label" for="ap-professional">Profesional *</label>
            <select class="form-select" id="ap-professional" name="professional_id" required>
              <?php foreach ($professionals as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) auth_user()['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="ap-reason">Motivo</label>
            <input class="form-control" id="ap-reason" name="reason" maxlength="255">
          </div>
          <div class="col-12">
            <label class="form-label" for="ap-notes">Observaciones</label>
            <textarea class="form-control" id="ap-notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modal-event" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="event-title">Cita</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body" id="event-body"></div>
      <div class="modal-footer flex-wrap gap-2" id="event-actions"></div>
    </div>
  </div>
</div>

<?php App\Core\View::start('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>
<script>
(function () {
  const canManage = <?= can('agenda.manage') ? 'true' : 'false' ?>;
  const canClinical = <?= can('clinical.manage') ? 'true' : 'false' ?>;
  const filter = document.getElementById('filter-professional');
  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale: 'es', initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
    headerToolbar: { left: 'prev,next hoy', center: 'title', right: 'timeGridDay,timeGridWeek,dayGridMonth' },
    customButtons: { hoy: { text: 'Hoy', click: () => calendar.today() } },
    buttonText: { day: 'Día', week: 'Semana', month: 'Mes' },
    slotMinTime: '07:00:00', slotMaxTime: '21:00:00', nowIndicator: true, expandRows: true,
    height: 'auto', allDaySlot: false, firstDay: 1,
    events: (info, success, failure) => {
      const q = new URLSearchParams({ start: info.startStr, end: info.endStr });
      if (filter.value) q.set('professional_id', filter.value);
      SGC.request('agenda/eventos?' + q.toString()).then((r) => r.success ? success(r.data) : failure(r)).catch(failure);
    },
    dateClick: (info) => { if (canManage) openNew(info.dateStr); },
    eventClick: (info) => openEvent(info.event),
  });
  calendar.render();
  filter.addEventListener('change', () => calendar.refetchEvents());

  function localValue(d) {
    const p = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
  }

  function openNew(dateStr) {
    const form = document.getElementById('form-appointment');
    form.reset(); form.action = <?= json_encode(url('agenda/citas')) ?>;
    document.getElementById('ap-patient-id').value = '';
    document.getElementById('appointment-title').textContent = 'Nueva cita';
    document.getElementById('picker-wrap').classList.remove('d-none');
    const d = dateStr ? new Date(dateStr) : new Date();
    if (!dateStr) d.setHours(d.getHours() + 1, 0, 0, 0);
    document.getElementById('ap-start').value = localValue(d);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-appointment')).show();
  }
  document.getElementById('btn-new')?.addEventListener('click', () => openNew(null));
  <?= $open_new ? 'openNew(null);' : '' ?>

  function openEvent(ev) {
    const p = ev.extendedProps;
    document.getElementById('event-title').textContent = ev.title;
    const body = document.getElementById('event-body');
    body.innerHTML = '';
    const rows = [
      ['Fecha', ev.start.toLocaleString('es', { dateStyle: 'full', timeStyle: 'short' })],
      ['Duración', p.duration + ' minutos'],
      ['Tipo', p.type_label], ['Estado', p.status_label],
      ['Expediente', p.file_number], ['Motivo', p.reason || '—'], ['Observaciones', p.notes || '—'],
    ];
    const dl = document.createElement('dl'); dl.className = 'kv';
    rows.forEach(([k, v]) => { const dt = document.createElement('dt'); dt.textContent = k; const dd = document.createElement('dd'); dd.textContent = v; dl.append(dt, dd); });
    body.appendChild(dl);

    const actions = document.getElementById('event-actions');
    actions.innerHTML = '';
    const add = (label, cls, fn) => { const b = document.createElement('button'); b.className = 'btn btn-sm ' + cls; b.textContent = label; b.onclick = fn; actions.appendChild(b); };
    const link = (label, cls, href) => { const a = document.createElement('a'); a.className = 'btn btn-sm ' + cls; a.textContent = label; a.href = href; actions.appendChild(a); };

    link('Abrir expediente', 'btn-light', SGC.url('pacientes/' + p.patient_id));
    if (p.session_id) {
      link('Ver sesión', 'btn-soft', SGC.url('sesiones/' + p.session_id));
    } else if (canClinical) {
      link('Registrar sesión', 'btn-primary', SGC.url('sesiones/nueva?appointment_id=' + ev.id));
    }
    if (canManage && ['programada', 'confirmada'].includes(p.status)) {
      add('Confirmar', 'btn-light', () => setStatus(ev.id, 'confirmada'));
      add('Atendida', 'btn-soft', () => setStatus(ev.id, 'atendida'));
      add('No asistió', 'btn-light', () => setStatus(ev.id, 'no_asistio'));
      add('Cancelar cita', 'btn-light text-danger', async () => { if (await SGC.confirm('¿Cancelar esta cita?')) setStatus(ev.id, 'cancelada'); });
      add('Reprogramar', 'btn-light', () => { bootstrap.Modal.getInstance(document.getElementById('modal-event')).hide(); editEvent(ev); });
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-event')).show();
  }

  function editEvent(ev) {
    const p = ev.extendedProps, form = document.getElementById('form-appointment');
    form.reset(); form.action = SGC.url('agenda/citas/' + ev.id);
    document.getElementById('appointment-title').textContent = 'Editar cita — ' + ev.title;
    document.getElementById('picker-wrap').classList.add('d-none');
    document.getElementById('ap-patient-id').value = p.patient_id;
    document.getElementById('ap-start').value = p.starts_at;
    document.getElementById('ap-duration').value = p.duration;
    document.getElementById('ap-type').value = p.type;
    document.getElementById('ap-professional').value = p.professional_id;
    document.getElementById('ap-reason').value = p.reason || '';
    document.getElementById('ap-notes').value = p.notes || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-appointment')).show();
  }

  async function setStatus(id, status) {
    const res = await SGC.request('agenda/citas/' + id + '/estado', { method: 'POST', data: { status } });
    if (res.redirect) { window.location = res.redirect; return; }
    if (res.success) { SGC.toast(res.message || 'Cita actualizada.'); bootstrap.Modal.getInstance(document.getElementById('modal-event'))?.hide(); calendar.refetchEvents(); }
    else SGC.toast(res.message || 'No se pudo actualizar.', 'danger');
  }
})();
</script>
<?php App\Core\View::stop(); ?>
