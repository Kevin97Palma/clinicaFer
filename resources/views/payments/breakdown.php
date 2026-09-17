<div class="page-head">
  <div><h1>Desglose de sesiones</h1><p class="lead-sm">Documento para reembolso o justificación de gastos.</p></div>
  <a class="btn btn-light" href="<?= e(url('pagos')) ?>">Volver a pagos</a>
</div>

<div class="card mb-3 no-print">
  <form class="card-body row g-2 align-items-end" method="get" action="<?= e(url('pagos/desglose')) ?>">
    <div class="col-md-5" data-patient-picker>
      <label class="form-label" for="bd-patient">Paciente *</label>
      <input type="hidden" name="patient_id" value="<?= $patient ? (int) $patient['id'] : '' ?>" required>
      <div class="position-relative">
        <input type="search" class="form-control" id="bd-patient" placeholder="Buscar paciente" autocomplete="off"
               value="<?= $patient ? e($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['file_number'] . ')') : '' ?>">
        <div class="search-results" hidden></div>
      </div>
    </div>
    <div class="col-md-3"><label class="form-label" for="desde">Desde</label><input type="date" class="form-control" id="desde" name="desde" value="<?= e($from) ?>"></div>
    <div class="col-md-2"><label class="form-label" for="hasta">Hasta</label><input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($to) ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Generar</button></div>
  </form>
</div>

<?php if ($patient): ?>
  <div class="d-flex justify-content-end gap-2 mb-2 no-print">
    <button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir / PDF</button>
    <?php if (can('documents.manage')): ?>
      <form method="post" action="<?= e(url('pagos/desglose/documento')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="patient_id" value="<?= (int) $patient['id'] ?>">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <input type="hidden" name="content" id="bd-content">
        <button class="btn btn-soft"><i class="bi bi-file-earmark-plus me-1"></i> Guardar como documento</button>
      </form>
    <?php endif; ?>
  </div>

  <article class="doc-paper" id="bd-paper">
    <header class="doc-letterhead">
      <?php if (setting('logo_file', '')): ?><img src="<?= e(url('marca/logo')) ?>" alt=""><?php endif; ?>
      <div>
        <div style="font-size:15pt; font-weight:600"><?= e(setting('professional_name', setting('brand_name', ''))) ?></div>
        <div style="font-size:10pt"><?= e(setting('professional_title', '')) ?><?= setting('registration_number', '') ? ' · Reg. ' . e(setting('registration_number')) : '' ?></div>
      </div>
    </header>

    <h2 style="font-size:13pt; text-align:center">DESGLOSE DE SESIONES</h2>
    <p style="font-size:10.5pt">
      <strong>Paciente:</strong> <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?> ·
      <strong>Identificación:</strong> <?= e($patient['identification'] ?: '—') ?> ·
      <strong>Expediente:</strong> <?= e($patient['file_number']) ?><br>
      <strong>Periodo:</strong> <?= e(fdate($from)) ?> al <?= e(fdate($to)) ?>
    </p>

    <table>
      <thead><tr><th>Fecha</th><th>N.º sesión</th><th>Tipo</th><th style="text-align:right">Valor individual</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(fdate($r['session_date'])) ?></td>
          <td><?= $r['session_number'] ? (int) $r['session_number'] : '—' ?></td>
          <td><?= e(label('modality', $r['modality'])) ?><?= $r['package_name'] ? ' · ' . e($r['package_name']) : '' ?></td>
          <td style="text-align:right"><?= e(money($r['regular_value'])) ?></td>
          <td><?= e(label('attendance', $r['attendance'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5">Sin sesiones atendidas en el periodo.</td></tr><?php endif; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="3" style="text-align:right">Subtotal</th><th style="text-align:right"><?= e(money($subtotal)) ?></th><th></th></tr>
        <tr><th colspan="3" style="text-align:right">Descuento</th><th style="text-align:right">-<?= e(money($discount)) ?></th><th></th></tr>
        <tr><th colspan="3" style="text-align:right">Total pagado</th><th style="text-align:right"><?= e(money($total)) ?></th><th></th></tr>
      </tfoot>
    </table>

    <?php if ($payments): ?>
      <h3 style="font-size:11pt; margin-top:20px">Pagos registrados en el periodo</h3>
      <table>
        <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th style="text-align:right">Valor</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
          <tr><td><?= e(fdate($p['paid_at'])) ?></td><td><?= e($p['concept']) ?></td>
            <td><?= e(label('payment_method', $p['method'])) ?></td><td style="text-align:right"><?= e(money($p['amount'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <div class="doc-sign">
      <?php if (setting('signature_file', '')): ?><img src="<?= e(url('marca/firma')) ?>" alt=""><?php endif; ?>
      <div class="line">
        <div style="font-size:11pt; font-weight:600"><?= e(setting('professional_name', '')) ?></div>
        <div style="font-size:10pt"><?= e(setting('professional_title', '')) ?></div>
      </div>
    </div>
  </article>

  <?php App\Core\View::start('scripts'); ?>
  <script>
    document.querySelector('form[action$="desglose/documento"]')?.addEventListener('submit', function () {
      const paper = document.getElementById('bd-paper').cloneNode(true);
      paper.querySelectorAll('img').forEach((i) => i.remove());
      document.getElementById('bd-content').value = paper.innerHTML;
    });
  </script>
  <?php App\Core\View::stop(); ?>
<?php else: ?>
  <div class="card"><div class="empty"><i class="bi bi-receipt"></i>Seleccione un paciente y un rango de fechas para generar el desglose.</div></div>
<?php endif; ?>
