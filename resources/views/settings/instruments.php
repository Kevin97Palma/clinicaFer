<div class="page-head">
  <div><h1>Catálogo de instrumentos</h1><p class="lead-sm">Pruebas y escalas disponibles al registrar una evaluación.</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-instrument"><i class="bi bi-plus-lg me-1"></i> Nuevo instrumento</button>
</div>
<?= App\Core\View::fetch('partials/settings_nav') ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Instrumento</th><th>Descripción</th><th>Usos</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($instruments as $i): ?>
        <tr>
          <td class="cell-title"><?= e($i['name']) ?></td>
          <td class="cell-sub"><?= e($i['description'] ?: '—') ?></td>
          <td><?= (int) $i['uses'] ?></td>
          <td><?= $i['is_active'] ? '<span class="badge-soft badge-success">Activo</span>' : '<span class="badge-soft badge-neutral">Inactivo</span>' ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-instrument-edit"
                    data-action="<?= e(url('configuracion/instrumentos/' . $i['id'])) ?>"
                    data-fill='<?= e(json_encode(['name' => $i['name'], 'description' => $i['description'], 'is_active' => $i['is_active']])) ?>'><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modal-instrument" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= e(url('configuracion/instrumentos')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nuevo instrumento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label" for="ni-name">Nombre *</label>
            <input class="form-control" id="ni-name" name="name" maxlength="120" required></div>
          <div><label class="form-label" for="ni-desc">Descripción</label>
            <input class="form-control" id="ni-desc" name="description" maxlength="255"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Agregar</button></div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-instrument-edit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Editar instrumento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label" for="ei-name">Nombre *</label>
            <input class="form-control" id="ei-name" name="name" maxlength="120" required></div>
          <div class="mb-3"><label class="form-label" for="ei-desc">Descripción</label>
            <input class="form-control" id="ei-desc" name="description" maxlength="255"></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" id="ei-active" name="is_active" value="1">
            <label class="form-check-label" for="ei-active">Activo</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>
