<div class="page-head">
  <div>
    <h1><?= e($document['title']) ?></h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $document['patient_id'])) ?>"><?= e($document['first_name'] . ' ' . $document['last_name']) ?></a>
      · <?= e($document['file_number']) ?> · <?= e(label('document_type', $document['type'])) ?> · v<?= (int) $document['current_version'] ?>
      <?= badge('document_status', $document['status']) ?>
    </p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-soft" href="<?= e(url('documentos/' . $document['id'] . '/imprimir')) ?>" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i> Imprimir / PDF</a>
    <?php if (can('documents.manage') && $document['status'] !== 'anulado'): ?>
      <a class="btn btn-light" href="<?= e(url('documentos/' . $document['id'] . '/editar')) ?>"><i class="bi bi-pencil me-1"></i> Editar</a>
      <?php if ($document['status'] === 'borrador'): ?>
        <form method="post" action="<?= e(url('documentos/' . $document['id'] . '/estado')) ?>">
          <?= csrf_field() ?><input type="hidden" name="status" value="emitido">
          <button class="btn btn-primary">Emitir</button>
        </form>
      <?php endif; ?>
      <form method="post" action="<?= e(url('documentos/' . $document['id'] . '/estado')) ?>" data-confirm="¿Anular este documento? No se elimina: queda registrado como anulado.">
        <?= csrf_field() ?><input type="hidden" name="status" value="anulado">
        <button class="btn btn-light text-danger">Anular</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-9">
    <div class="card"><div class="card-body" style="background: var(--c-surface-2)">
      <article class="doc-paper"><?= $version['content'] ?></article>
    </div></div>
  </div>
  <div class="col-lg-3">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Datos</h2></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Creado</dt><dd><?= e(fdate($document['created_at'], true)) ?></dd>
          <dt>Por</dt><dd><?= e($document['created_by_name']) ?></dd>
          <dt>Emitido</dt><dd><?= e(fdate($document['issued_at'], true)) ?></dd>
        </dl>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h2 class="card-title">Versiones</h2></div>
      <div class="card-body">
        <p class="cell-sub">Cada emisión guarda una copia inmutable.</p>
        <ul class="list-unstyled mb-0">
          <?php foreach ($versions as $v): ?>
            <li class="d-flex justify-content-between align-items-center py-1">
              <a href="<?= e(url('documentos/' . $document['id'] . '/imprimir', ['v' => $v['version']])) ?>" target="_blank" rel="noopener">v<?= (int) $v['version'] ?></a>
              <span class="cell-sub"><?= e(fdate($v['created_at'], true)) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
