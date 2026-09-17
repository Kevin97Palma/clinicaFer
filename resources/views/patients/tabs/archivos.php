<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Archivos del expediente</h2>
        <?php if (can('files.manage')): ?>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-file"><i class="bi bi-upload me-1"></i> Subir archivo</button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Archivo</th><th>Clasificación</th><th class="d-none d-md-table-cell">Subido</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($files as $f): ?>
            <tr>
              <td>
                <span class="cell-title"><?= e($f['original_name']) ?></span>
                <div class="cell-sub"><?= e(number_format($f['size_bytes'] / 1024, 0)) ?> KB<?= $f['description'] ? ' · ' . e($f['description']) : '' ?></div>
              </td>
              <td><span class="badge-soft badge-neutral"><?= e(label('file_category', $f['category'])) ?></span></td>
              <td class="d-none d-md-table-cell cell-sub"><?= e(fdate($f['created_at'])) ?><div class="cell-sub"><?= e($f['uploaded_by_name']) ?></div></td>
              <td class="text-end">
                <?php if (can('files.view')): ?>
                  <a class="btn btn-sm btn-light" href="<?= e(url('archivos/' . $f['id'] . '/descargar')) ?>"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <?php if (can('files.manage')): ?>
                  <form method="post" action="<?= e(url('archivos/' . $f['id'] . '/eliminar')) ?>" class="d-inline" data-confirm="¿Eliminar este archivo del expediente? Queda registrado en la auditoría.">
                    <?= csrf_field() ?><button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$files): ?>
            <tr><td colspan="4"><div class="empty"><i class="bi bi-paperclip"></i>Sin archivos adjuntos.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-header"><span class="cell-sub">Formatos permitidos: PDF, JPG, PNG, DOCX · máximo <?= (int) App\Core\Config::get('max_upload_mb') ?> MB. Los archivos se guardan fuera del directorio público.</span></div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Consentimientos y autorizaciones</h2>
        <?php if (can('patients.manage')): ?>
          <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#modal-consent"><i class="bi bi-plus-lg"></i></button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($consents as $c): ?>
          <div class="d-flex gap-2 align-items-start mb-3">
            <div class="stat-icon tone-<?= $c['status'] === 'vigente' ? 'success' : ($c['status'] === 'pendiente' ? 'warning' : 'neutral') ?>" style="width:36px;height:36px"><i class="bi bi-file-earmark-check"></i></div>
            <div class="flex-grow-1">
              <div class="cell-title"><?= e(label('consent_type', $c['type'])) ?></div>
              <div class="cell-sub"><?= e(fdate($c['consent_date'])) ?><?= $c['g_first'] ? ' · ' . e($c['g_first'] . ' ' . $c['g_last']) : '' ?></div>
              <?php if ($c['notes']): ?><div class="cell-sub"><?= e($c['notes']) ?></div><?php endif; ?>
            </div>
            <div class="text-end">
              <?= badge('consent_status', $c['status']) ?>
              <?php if ($c['file_id'] && can('files.view')): ?>
                <div><a class="cell-sub" href="<?= e(url('archivos/' . $c['file_id'] . '/descargar')) ?>"><i class="bi bi-download"></i> archivo</a></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$consents): ?>
          <div class="empty py-3"><i class="bi bi-file-earmark-check"></i>Sin consentimientos registrados.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
