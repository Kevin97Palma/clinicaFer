<?php $isEdit = $document !== null; ?>
<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Editar documento' : 'Nuevo documento' ?></h1>
    <p class="lead-sm"><?= $patient ? e($patient['first_name'] . ' ' . $patient['last_name'] . ' · ' . $patient['file_number']) : 'Seleccione el paciente.' ?></p>
  </div>
  <a class="btn btn-light" href="<?= e($isEdit ? url('documentos/' . $document['id']) : ($patient ? url('pacientes/' . $patient['id'], ['tab' => 'documentos']) : url('documentos'))) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e($isEdit ? url('documentos/' . $document['id']) : url('documentos')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <?php if ($evaluation_id): ?><input type="hidden" name="evaluation_id" value="<?= (int) $evaluation_id ?>"><?php endif; ?>
  <?php if ($template): ?><input type="hidden" name="template_id" value="<?= (int) $template['id'] ?>"><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <?php if (!$patient): ?>
            <div class="mb-3" data-patient-picker>
              <label class="form-label">Paciente *</label>
              <input type="hidden" name="patient_id" required>
              <div class="position-relative">
                <input type="search" class="form-control" placeholder="Buscar por nombre, cédula o expediente" autocomplete="off">
                <div class="search-results" hidden></div>
              </div>
              <div class="form-text">Elija primero el paciente y luego una plantilla para completar los datos automáticamente.</div>
            </div>
          <?php else: ?>
            <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
          <?php endif; ?>

          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label class="form-label" for="title">Título *</label>
              <input class="form-control" id="title" name="title" maxlength="200" required
                     value="<?= e(old('title', $document['title'] ?? ($template['name'] ?? ''))) ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label" for="type">Tipo *</label>
              <select class="form-select" id="type" name="type" required <?= $isEdit ? 'disabled' : '' ?>>
                <?= select_options('document_type', $type) ?>
              </select>
              <?php if ($isEdit): ?><input type="hidden" name="type" value="<?= e($document['type']) ?>"><?php endif; ?>
            </div>
          </div>

          <label class="form-label" for="editor">Contenido</label>
          <div id="editor" style="min-height:460px; background:#fff"><?= $content ?></div>
          <textarea name="content" id="content-field" class="d-none"></textarea>
          <p class="cell-sub mt-2">Al guardar se almacena una copia de esta versión: si luego cambian los datos del paciente, el documento histórico no se modifica.</p>
        </div>
        <div class="sticky-actions">
          <button class="btn btn-light" name="action" value="borrador">Guardar borrador</button>
          <button class="btn btn-primary" name="action" value="emitir"><i class="bi bi-check2 me-1"></i> Guardar y emitir</button>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <?php if (!$isEdit): ?>
        <div class="card mb-3">
          <div class="card-header"><h2 class="card-title">Plantilla</h2></div>
          <div class="card-body">
            <select class="form-select" id="template-select">
              <option value="">— Sin plantilla —</option>
              <?php foreach ($templates as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= $template && (int) $template['id'] === (int) $t['id'] ? 'selected' : '' ?>>
                  <?= e($t['name']) ?> (<?= e(label('document_type', $t['type'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <p class="cell-sub mt-2 mb-0">Al cambiar de plantilla se recarga el contenido con los datos del paciente.</p>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><h2 class="card-title">Variables disponibles</h2></div>
        <div class="card-body">
          <p class="cell-sub">Se reemplazan al generar el documento desde una plantilla.</p>
          <div class="d-flex flex-wrap gap-1">
            <?php foreach (App\Services\DocumentService::VARIABLES as $key => $desc): ?>
              <span class="badge-soft badge-neutral" title="<?= e($desc) ?>">{{<?= e($key) ?>}}</span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php App\Core\View::start('head'); ?>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<?php App\Core\View::stop(); ?>

<?php App\Core\View::start('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
(function () {
  const quill = new Quill('#editor', {
    theme: 'snow',
    modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote'], ['clean']] },
  });
  const form = document.querySelector('form');
  form.addEventListener('submit', () => { document.getElementById('content-field').value = quill.root.innerHTML; });

  const select = document.getElementById('template-select');
  select?.addEventListener('change', () => {
    const patientId = form.querySelector('[name="patient_id"]').value;
    if (!patientId) { SGC.toast('Seleccione primero el paciente.', 'warning'); select.value = ''; return; }
    const params = new URLSearchParams({ patient_id: patientId });
    if (select.value) params.set('template_id', select.value);
    window.location = SGC.url('documentos/nuevo?' + params.toString());
  });
})();
</script>
<?php App\Core\View::stop(); ?>
