<?php $isEdit = $template !== null; ?>
<div class="page-head">
  <div><h1><?= $isEdit ? 'Editar plantilla' : 'Nueva plantilla' ?></h1>
    <p class="lead-sm">Use las variables entre llaves dobles: se reemplazan con los datos del paciente al generar el documento.</p></div>
  <a class="btn btn-light" href="<?= e(url('plantillas')) ?>">Cancelar</a>
</div>

<form method="post" action="<?= e($isEdit ? url('plantillas/' . $template['id']) : url('plantillas')) ?>" class="needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="row g-3 mb-3">
            <div class="col-md-7">
              <label class="form-label" for="name">Nombre *</label>
              <input class="form-control" id="name" name="name" maxlength="150" required value="<?= e(old('name', $template['name'] ?? '')) ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label" for="type">Tipo *</label>
              <select class="form-select" id="type" name="type" required><?= select_options('document_type', old('type', $template['type'] ?? 'informe_psicologico')) ?></select>
            </div>
          </div>
          <label class="form-label" for="editor">Contenido</label>
          <div id="editor" style="min-height:420px; background:#fff"><?= $template['content'] ?? '' ?></div>
          <textarea name="content" id="content-field" class="d-none"></textarea>
        </div>
        <div class="sticky-actions">
          <div class="form-check me-auto">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= !$isEdit || $template['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Plantilla activa</label>
          </div>
          <button class="btn btn-primary">Guardar plantilla</button>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h2 class="card-title">Variables</h2></div>
        <div class="card-body">
          <div class="d-flex flex-wrap gap-1">
            <?php foreach (App\Services\DocumentService::VARIABLES as $key => $desc): ?>
              <button type="button" class="badge-soft badge-neutral border-0 var-btn" data-var="{{<?= e($key) ?>}}" title="<?= e($desc) ?>">{{<?= e($key) ?>}}</button>
            <?php endforeach; ?>
          </div>
          <p class="cell-sub mt-2 mb-0">Haga clic en una variable para insertarla en el punto del cursor.</p>
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
  document.querySelector('form').addEventListener('submit', () => { document.getElementById('content-field').value = quill.root.innerHTML; });
  document.querySelectorAll('.var-btn').forEach((b) => b.addEventListener('click', () => {
    const range = quill.getSelection(true);
    quill.insertText(range ? range.index : quill.getLength(), b.dataset.var);
  }));
})();
</script>
<?php App\Core\View::stop(); ?>
