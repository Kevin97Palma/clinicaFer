<?php
$logo = setting('logo_file', '') !== '';
$signature = setting('signature_file', '') !== '';
?>
<article class="doc-paper">
  <header class="doc-letterhead">
    <?php if ($logo): ?><img src="<?= e(url('marca/logo')) ?>" alt=""><?php endif; ?>
    <div>
      <div style="font-size:15pt; font-weight:600"><?= e(setting('professional_name', setting('brand_name', ''))) ?></div>
      <div style="font-size:10pt"><?= e(setting('professional_title', '')) ?><?= setting('registration_number', '') ? ' · Reg. ' . e(setting('registration_number')) : '' ?></div>
      <div style="font-size:9pt; color:#555">
        <?= e(setting('address', '')) ?><?= setting('phone', '') ? ' · ' . e(setting('phone')) : '' ?><?= setting('email', '') ? ' · ' . e(setting('email')) : '' ?>
      </div>
    </div>
  </header>

  <h2 style="font-size:14pt; text-align:center; margin-bottom:6px"><?= e(mb_strtoupper($document['title'])) ?></h2>
  <p style="text-align:center; font-size:10pt; color:#555; margin-bottom:24px">
    Expediente <?= e($document['file_number']) ?> · <?= e(fdate($document['issued_at'] ?: $version['created_at'])) ?> · versión <?= (int) $version['version'] ?>
  </p>

  <?= $version['content'] ?>

  <div class="doc-sign">
    <?php if ($signature): ?><img src="<?= e(url('marca/firma')) ?>" alt=""><?php endif; ?>
    <div class="line">
      <div style="font-size:11pt; font-weight:600"><?= e(setting('professional_name', '')) ?></div>
      <div style="font-size:10pt"><?= e(setting('professional_title', '')) ?></div>
      <?php if (setting('registration_number', '')): ?><div style="font-size:10pt">Registro profesional <?= e(setting('registration_number')) ?></div><?php endif; ?>
    </div>
  </div>

  <?php if ($document['status'] === 'anulado'): ?>
    <p style="text-align:center; color:#a8443c; font-weight:700; margin-top:24px">DOCUMENTO ANULADO</p>
  <?php elseif ($document['status'] === 'borrador'): ?>
    <p style="text-align:center; color:#9a6a12; font-weight:700; margin-top:24px">BORRADOR — NO EMITIDO</p>
  <?php endif; ?>
</article>
