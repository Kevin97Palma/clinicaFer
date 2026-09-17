<?php $s = $session; ?>
<div class="page-head">
  <div>
    <h1><?= $s['session_number'] ? 'Sesión N.º ' . (int) $s['session_number'] : 'Registro de cita' ?></h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $s['patient_id'])) ?>"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></a>
      · <?= e($s['file_number']) ?> · <?= e(fdate($s['session_date'])) ?> <?= e(ftime($s['session_time'])) ?> · <?= (int) $s['duration_min'] ?> min
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if (can('clinical.manage')): ?><a class="btn btn-soft" href="<?= e(url('sesiones/' . $s['id'] . '/editar')) ?>"><i class="bi bi-pencil me-1"></i> Editar</a><?php endif; ?>
    <a class="btn btn-light" href="<?= e(url('pacientes/' . $s['patient_id'], ['tab' => 'sesiones'])) ?>">Volver al expediente</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Registro clínico <span class="confidential ms-2"><i class="bi bi-lock-fill"></i> confidencial</span></h2>
        <div class="d-flex gap-1"><?= badge('attendance', $s['attendance']) ?><?= badge('modality', $s['modality']) ?></div>
      </div>
      <div class="card-body">
        <?php
        $blocks = [
            'Objetivo trabajado' => $s['objective_worked'],
            'Actividad / intervención realizada' => $s['intervention'],
            'Respuesta del paciente' => $s['patient_response'],
            'Observaciones clínicas' => $s['clinical_notes'],
            'Tarea para casa' => $s['homework'],
            'Recomendaciones' => $s['recommendations'],
            'Próximo objetivo' => $s['next_objective'],
        ];
        foreach ($blocks as $label => $value): ?>
          <div class="mb-3">
            <div class="small-caps mb-1"><?= e($label) ?></div>
            <div class="clinical-box"><?= $value ? nl2br_e($value) : '<span class="text-muted">Sin registro</span>' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Indicadores</h2></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Estado emocional</dt><dd><?= $s['emotional_state'] ? badge('emotional_state', $s['emotional_state']) : '—' ?></dd>
          <dt>Participación</dt><dd><?= $s['participation'] ? badge('participation', $s['participation']) : '—' ?></dd>
          <dt>Avance</dt><dd><?= $s['progress'] ? badge('progress', $s['progress']) : '—' ?></dd>
          <dt>Profesional</dt><dd><?= e($s['professional_name']) ?></dd>
          <dt>Registrada</dt><dd><?= e(fdate($s['created_at'], true)) ?><?= $s['created_by_name'] ? ' · ' . e($s['created_by_name']) : '' ?></dd>
          <?php if ($s['updated_at'] !== $s['created_at']): ?>
            <dt>Última edición</dt><dd><?= e(fdate($s['updated_at'], true)) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <?php if ($objectives): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Objetivos trabajados</h2></div>
        <div class="card-body">
          <?php foreach ($objectives as $o): ?>
            <div class="mb-2">
              <div class="cell-sub"><?= e($o['area_name']) ?></div>
              <div class="d-flex justify-content-between gap-2">
                <span><?= e($o['objective']) ?></span>
                <?= badge('objective_status', $o['status_after'] ?: $o['status']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (can('payments.view') && ($package || $payment)): ?>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Administrativo</h2></div>
        <div class="card-body">
          <dl class="kv">
            <?php if ($package): ?>
              <dt>Paquete</dt><dd><a href="<?= e(url('paquetes/' . $package['id'])) ?>"><?= e($package['name']) ?></a>
                <div class="cell-sub"><?= (int) $package['sessions_used'] ?>/<?= (int) $package['sessions_total'] ?> utilizadas</div></dd>
            <?php endif; ?>
            <dt>Valor de la sesión</dt><dd><?= e(money($s['fee'])) ?></dd>
            <?php if ($payment): ?>
              <dt>Pago</dt><dd><?= badge('payment_status', $payment['status']) ?> <?= e(money($payment['amount'])) ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
