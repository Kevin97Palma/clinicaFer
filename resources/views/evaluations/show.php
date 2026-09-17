<?php $ev = $evaluation; $canEdit = can('evaluations.manage'); $areas = array_filter(explode(',', (string) $ev['areas'])); ?>
<div class="page-head">
  <div>
    <h1>Evaluación psicológica</h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $ev['patient_id'])) ?>"><?= e($ev['first_name'] . ' ' . $ev['last_name']) ?></a>
      · <?= e($ev['file_number']) ?> · <?= e(age($ev['birth_date'])) ?> · inicio <?= e(fdate($ev['start_date'])) ?> <?= badge('evaluation_status', $ev['status']) ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if (can('documents.manage')): ?>
      <a class="btn btn-soft" href="<?= e(url('documentos/nuevo', ['patient_id' => $ev['patient_id'], 'evaluation_id' => $ev['id'], 'type' => 'informe_evaluacion'])) ?>">
        <i class="bi bi-file-earmark-text me-1"></i> Generar informe</a>
    <?php endif; ?>
    <a class="btn btn-light" href="<?= e(url('pacientes/' . $ev['patient_id'], ['tab' => 'evaluaciones'])) ?>">Volver</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">
        <h2 class="card-title">Instrumentos aplicados (<?= count($instruments) ?>)</h2>
        <?php if ($canEdit): ?>
          <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#modal-instrument"><i class="bi bi-plus-lg me-1"></i> Agregar</button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($instruments as $i): ?>
          <div class="form-section">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="form-section-title mb-1"><?= e($i['instrument_name']) ?></div>
                <div class="cell-sub">Aplicado el <?= e(fdate($i['applied_at'])) ?></div>
              </div>
              <div class="d-flex gap-1">
                <?php if ($i['file_id'] && can('files.view')): ?>
                  <a class="btn btn-sm btn-light" href="<?= e(url('archivos/' . $i['file_id'] . '/descargar')) ?>" title="<?= e($i['original_name']) ?>"><i class="bi bi-paperclip"></i></a>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                  <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-instrument-edit"
                          data-action="<?= e(url('instrumentos-aplicados/' . $i['id'])) ?>"
                          data-fill='<?= e(json_encode(['applied_at' => $i['applied_at'], 'scores' => $i['scores'], 'interpretation' => $i['interpretation'], 'notes' => $i['notes']])) ?>'><i class="bi bi-pencil"></i></button>
                  <form method="post" action="<?= e(url('instrumentos-aplicados/' . $i['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar este instrumento de la evaluación?">
                    <?= csrf_field() ?><button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col-md-5">
                <div class="small-caps">Puntajes / resultados</div>
                <div class="clinical-box"><?= $i['scores'] ? nl2br_e($i['scores']) : '<span class="text-muted">Sin registro</span>' ?></div>
              </div>
              <div class="col-md-7">
                <div class="small-caps">Interpretación profesional</div>
                <div class="clinical-box"><?= $i['interpretation'] ? nl2br_e($i['interpretation']) : '<span class="text-muted">Pendiente de redacción</span>' ?></div>
              </div>
              <?php if ($i['notes']): ?>
                <div class="col-12"><div class="cell-sub">Observaciones: <?= e($i['notes']) ?></div></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$instruments): ?>
          <div class="empty"><i class="bi bi-rulers"></i>Aún no se han registrado instrumentos.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2 class="card-title">Integración clínica y conclusiones</h2></div>
      <?php if ($canEdit): ?>
        <form method="post" action="<?= e(url('evaluaciones/' . $ev['id'])) ?>" class="card-body needs-validation" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="reason">Motivo de la evaluación *</label>
            <textarea class="form-control" id="reason" name="reason" rows="2" required><?= e($ev['reason']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="clinical_integration">Integración clínica</label>
            <textarea class="form-control" id="clinical_integration" name="clinical_integration" rows="4"><?= e($ev['clinical_integration']) ?></textarea>
            <div class="form-text">Integración de resultados con la anamnesis, la observación y los reportes recibidos.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="conclusions">Conclusiones</label>
            <textarea class="form-control" id="conclusions" name="conclusions" rows="4"><?= e($ev['conclusions']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label" for="recommendations">Recomendaciones</label>
            <textarea class="form-control" id="recommendations" name="recommendations" rows="3"><?= e($ev['recommendations']) ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="end_date">Fecha de finalización</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($ev['end_date']) ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label" for="status">Estado *</label>
              <select class="form-select" id="status" name="status" required><?= select_options('evaluation_status', $ev['status']) ?></select>
            </div>
            <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Guardar</button></div>
          </div>
        </form>
      <?php else: ?>
        <div class="card-body">
          <div class="mb-3"><div class="small-caps">Integración clínica</div><div class="clinical-box"><?= nl2br_e($ev['clinical_integration']) ?: '—' ?></div></div>
          <div class="mb-3"><div class="small-caps">Conclusiones</div><div class="clinical-box"><?= nl2br_e($ev['conclusions']) ?: '—' ?></div></div>
          <div><div class="small-caps">Recomendaciones</div><div class="clinical-box"><?= nl2br_e($ev['recommendations']) ?: '—' ?></div></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Datos</h2></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Áreas</dt><dd><?= e(implode(', ', array_map(fn($a) => label('evaluation_area', $a), $areas))) ?></dd>
          <dt>Motivo</dt><dd><?= nl2br_e($ev['reason']) ?></dd>
          <dt>Profesional</dt><dd><?= e($ev['professional_name']) ?></dd>
          <dt>Informe entregado</dt><dd><?= e(fdate($ev['report_delivered_at'])) ?></dd>
        </dl>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Informes generados</h2></div>
      <div class="card-body">
        <?php foreach ($documents as $d): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="<?= e(url('documentos/' . $d['id'])) ?>"><?= e($d['title']) ?></a>
            <?= badge('document_status', $d['status']) ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$documents): ?><div class="cell-sub">Sin informes generados.</div><?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="clinical-box small" style="white-space:normal">
          <i class="bi bi-shield-check me-1"></i> Los resultados psicométricos no se convierten automáticamente en diagnósticos. La interpretación y cualquier conclusión son responsabilidad de la profesional.
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="modal-instrument" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="<?= e(url('evaluaciones/' . $ev['id'] . '/instrumentos')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Agregar instrumento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-8">
            <label class="form-label" for="in-id">Instrumento *</label>
            <select class="form-select" id="in-id" name="instrument_id" required>
              <?php foreach ($catalog as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
            <div class="form-text"><a href="<?= e(url('configuracion/instrumentos')) ?>">Administrar catálogo de instrumentos</a></div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="in-date">Fecha de aplicación *</label>
            <input type="date" class="form-control" id="in-date" name="applied_at" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="in-scores">Puntajes / resultados</label>
            <textarea class="form-control" id="in-scores" name="scores" rows="3" placeholder="Ej.: CI total 98; ICV 102; IRF 95"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="in-interp">Interpretación profesional</label>
            <textarea class="form-control" id="in-interp" name="interpretation" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="in-notes">Observaciones</label>
            <input class="form-control" id="in-notes" name="notes" maxlength="255">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="in-file">Archivo adjunto</label>
            <input type="file" class="form-control" id="in-file" name="file" accept=".pdf,.jpg,.jpeg,.png,.docx">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary">Guardar instrumento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-instrument-edit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="post" action="" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Editar instrumento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-4">
            <label class="form-label" for="ine-date">Fecha de aplicación *</label>
            <input type="date" class="form-control" id="ine-date" name="applied_at" required>
          </div>
          <div class="col-12">
            <label class="form-label" for="ine-scores">Puntajes / resultados</label>
            <textarea class="form-control" id="ine-scores" name="scores" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="ine-interp">Interpretación profesional</label>
            <textarea class="form-control" id="ine-interp" name="interpretation" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="ine-notes">Observaciones</label>
            <input class="form-control" id="ine-notes" name="notes" maxlength="255">
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
