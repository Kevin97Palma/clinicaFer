<?php
$presumptive = null;
$current = [];
foreach ($diagnoses as $d) {
    if ($d['type'] === 'presuntivo' && !$presumptive) {
        $presumptive = $d;
    }
    if ($d['type'] === 'confirmado') {
        $current[] = $d;
    }
}
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Datos clínicos y administrativos</h2>
        <?php if (can('patients.manage')): ?><a class="btn btn-sm btn-light" href="<?= e(url('pacientes/' . $pid . '/editar')) ?>">Editar</a><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-6">
            <dl class="kv">
              <dt>Motivo de consulta</dt><dd><?= e($patient['consultation_reason'] ?: '—') ?></dd>
              <dt>Identificación</dt><dd><?= e($patient['identification'] ?: '—') ?></dd>
              <dt>Institución</dt><dd><?= e(trim(($patient['school'] ?? '') . ' ' . ($patient['grade'] ? '· ' . $patient['grade'] : '')) ?: '—') ?></dd>
              <dt>Teléfono</dt><dd><?= e($patient['phone'] ?: '—') ?></dd>
              <dt>Correo</dt><dd><?= e($patient['email'] ?: '—') ?></dd>
            </dl>
          </div>
          <div class="col-md-6">
            <dl class="kv">
              <dt>Profesional</dt><dd><?= e($patient['professional_name'] ?: 'Sin asignar') ?></dd>
              <dt>Sesiones atendidas</dt><dd><?= (int) $patient['sessions_count'] ?></dd>
              <?php if (can('clinical.view')): ?>
                <dt>Medicamentos</dt><dd><?= e(($anamnesis['medications'] ?? '') ?: 'Sin registro') ?></dd>
                <dt>Profesionales externos</dt><dd><?= e(($anamnesis['external_professionals'] ?? '') ?: 'Sin registro') ?></dd>
              <?php endif; ?>
              <?php if (can('payments.view')): ?>
                <dt>Saldo pendiente</dt><dd><?= $header['balance'] > 0 ? '<span class="badge-soft badge-warning">' . e(money($header['balance'])) . '</span>' : money(0) ?></dd>
              <?php endif; ?>
            </dl>
          </div>
        </div>
        <?php if ($patient['general_notes']): ?>
          <div class="clinical-box mt-3"><?= nl2br_e($patient['general_notes']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (can('clinical.view')): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h2 class="card-title">Diagnósticos <span class="confidential ms-2"><i class="bi bi-lock-fill"></i> confidencial</span></h2>
          <?php if (can('clinical.manage')): ?>
            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#modal-diagnosis" data-reset
                    data-title="Nuevo diagnóstico" data-action="<?= e(url('pacientes/' . $pid . '/diagnosticos')) ?>"
                    data-fill='<?= e(json_encode(['diagnosed_at' => date('Y-m-d')])) ?>'><i class="bi bi-plus-lg"></i> Agregar</button>
          <?php endif; ?>
        </div>
        <div class="card-body pt-0">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="small-caps">Diagnóstico presuntivo</div>
              <div><?= $presumptive ? e($presumptive['diagnosis']) . ' <span class="cell-sub">' . e($presumptive['classification_system'] . ' ' . $presumptive['code']) . '</span>' : '<span class="text-muted">Sin registro</span>' ?></div>
            </div>
            <div class="col-md-6">
              <div class="small-caps">Diagnóstico actual</div>
              <div><?= $current ? implode('<br>', array_map(fn($d) => e($d['diagnosis']) . ' <span class="cell-sub">' . e($d['classification_system'] . ' ' . $d['code']) . '</span>', $current)) : '<span class="text-muted">Sin diagnóstico confirmado</span>' ?></div>
            </div>
          </div>
          <?php if ($diagnoses): ?>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead><tr><th>Diagnóstico</th><th>Tipo</th><th>Sistema</th><th>Fecha</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($diagnoses as $d): ?>
                  <tr>
                    <td><span class="cell-title"><?= e($d['diagnosis']) ?></span><?php if ($d['notes']): ?><div class="cell-sub"><?= e($d['notes']) ?></div><?php endif; ?></td>
                    <td><?= badge('diagnosis_type', $d['type']) ?></td>
                    <td class="cell-sub"><?= e($d['classification_system'] . ' ' . $d['code']) ?></td>
                    <td class="cell-sub"><?= e(fdate($d['diagnosed_at'])) ?></td>
                    <td class="text-end">
                      <?php if (can('clinical.manage')): ?>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-diagnosis"
                                data-title="Editar diagnóstico" data-action="<?= e(url('diagnosticos/' . $d['id'])) ?>"
                                data-fill='<?= e(json_encode(['diagnosis' => $d['diagnosis'], 'code' => $d['code'], 'classification_system' => $d['classification_system'], 'type' => $d['type'], 'diagnosed_at' => $d['diagnosed_at'], 'status' => $d['status'], 'notes' => $d['notes']])) ?>'><i class="bi bi-pencil"></i></button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="cell-sub mb-0">Los diagnósticos históricos no se eliminan: se marcan como inactivos o descartados para conservar la trazabilidad.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Últimas sesiones y evolución</h2>
          <a class="btn btn-sm btn-light" href="<?= e(url('pacientes/' . $pid, ['tab' => 'sesiones'])) ?>">Ver todas</a>
        </div>
        <div class="card-body">
          <?php if ($recent_sessions): ?>
            <div class="timeline">
              <?php foreach ($recent_sessions as $s): ?>
                <div class="timeline-item <?= $s['attendance'] === 'atendido' ? '' : 'muted' ?>">
                  <div class="d-flex justify-content-between gap-2 flex-wrap">
                    <div>
                      <a class="cell-title" href="<?= e(url('sesiones/' . $s['id'])) ?>">
                        <?= $s['session_number'] ? 'Sesión N.º ' . (int) $s['session_number'] : 'Cita' ?>
                      </a>
                      <span class="cell-sub">· <?= e(fdate($s['session_date'])) ?> · <?= e(label('modality', $s['modality'])) ?></span>
                    </div>
                    <div class="d-flex gap-1">
                      <?= badge('attendance', $s['attendance']) ?>
                      <?= $s['progress'] ? badge('progress', $s['progress']) : '' ?>
                    </div>
                  </div>
                  <?php if ($s['objective_worked']): ?><div class="cell-sub mt-1"><?= e(mb_strimwidth((string) $s['objective_worked'], 0, 160, '…')) ?></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty"><i class="bi bi-journal"></i>Aún no hay sesiones registradas.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <?php if ($alerts): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Alertas del paciente</h2></div>
        <div>
          <?php foreach ($alerts as $a): ?>
            <div class="alert-row">
              <div class="dot tone-warning"><i class="bi bi-exclamation"></i></div>
              <div class="flex-grow-1"><div class="small"><?= e($a['message']) ?></div><div class="cell-sub"><?= e(fdate($a['created_at'])) ?></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (can('clinical.view')): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Objetivos terapéuticos</h2>
          <a class="btn btn-sm btn-light" href="<?= e(url('pacientes/' . $pid, ['tab' => 'plan'])) ?>">Plan</a>
        </div>
        <div class="card-body">
          <?php if ($plan && $progress): ?>
            <div class="d-flex justify-content-between small mb-1">
              <span class="text-muted">Objetivos cumplidos</span>
              <strong><?= (int) $progress['achieved'] ?>/<?= (int) $progress['total'] ?></strong>
            </div>
            <div class="progress mb-2"><div class="progress-bar" style="width: <?= (int) $progress['percent'] ?>%"></div></div>
            <p class="cell-sub">Cumplimiento administrativo de objetivos registrados; no es una medición psicométrica ni un porcentaje clínico de recuperación.</p>
            <ul class="list-unstyled mb-0 small">
              <?php foreach (array_slice($progress['objectives'], 0, 6) as $o): ?>
                <li class="d-flex gap-2 mb-2">
                  <i class="bi bi-<?= $o['status'] === 'logrado' ? 'check-circle-fill text-success' : ($o['status'] === 'en_proceso' ? 'circle-half' : 'circle') ?>"></i>
                  <span><?= e($o['objective']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="empty py-3"><i class="bi bi-bullseye"></i>Sin plan terapéutico activo.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($pkg && can('payments.view')): ?>
      <div class="card mb-3">
        <div class="card-header"><h2 class="card-title">Paquete activo</h2></div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span><strong><?= (int) $pkg['sessions_used'] ?> / <?= (int) $pkg['sessions_total'] ?></strong> utilizadas</span>
            <span class="badge-soft badge-<?= (int) $pkg['sessions_remaining'] <= 1 ? 'warning' : 'success' ?>"><?= (int) $pkg['sessions_remaining'] ?> restantes</span>
          </div>
          <div class="pkg-dots">
            <?php for ($i = 0; $i < (int) $pkg['sessions_total']; $i++): ?>
              <span class="<?= $i < (int) $pkg['sessions_used'] ? 'used' : '' ?>"></span>
            <?php endfor; ?>
          </div>
          <div class="cell-sub mt-2"><?= e($pkg['name']) ?> · <?= e(money($pkg['total_paid'])) ?> · desde <?= e(fdate($pkg['purchased_at'])) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header"><h2 class="card-title">Representantes</h2>
        <?php if (can('patients.manage')): ?>
          <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#modal-guardian" data-reset
                  data-title="Nuevo representante" data-action="<?= e(url('pacientes/' . $pid . '/representantes')) ?>"><i class="bi bi-plus-lg"></i></button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$guardians): ?>
          <div class="empty py-3"><i class="bi bi-person-badge"></i>Sin representantes registrados.</div>
        <?php endif; ?>
        <?php foreach ($guardians as $g): ?>
          <div class="d-flex gap-2 align-items-start mb-3">
            <div class="avatar"><?= e(initials($g['first_name'] . ' ' . $g['last_name'])) ?></div>
            <div class="flex-grow-1 min-w-0">
              <div class="cell-title"><?= e($g['first_name'] . ' ' . $g['last_name']) ?>
                <?php if ($g['is_primary']): ?><span class="badge-soft badge-primary ms-1">Principal</span><?php endif; ?>
              </div>
              <div class="cell-sub"><?= e(label('relationship', $g['relationship'])) ?><?= $g['phone'] ? ' · ' . e($g['phone']) : '' ?></div>
              <div class="cell-sub"><?= e($g['email'] ?: '') ?></div>
              <?php if (!$g['authorized_info']): ?><div class="cell-sub text-danger">No autorizado para recibir información</div><?php endif; ?>
            </div>
            <?php if (can('patients.manage')): ?>
              <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#modal-guardian"
                      data-title="Editar representante" data-action="<?= e(url('representantes/' . $g['id'])) ?>"
                      data-fill='<?= e(json_encode(['first_name' => $g['first_name'], 'last_name' => $g['last_name'], 'relationship' => $g['relationship'], 'identification' => $g['identification'], 'phone' => $g['phone'], 'email' => $g['email'], 'notes' => $g['notes'], 'is_primary' => $g['is_primary'], 'authorized_info' => $g['authorized_info']])) ?>'><i class="bi bi-pencil"></i></button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (can('clinical.view')): ?>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Estado del expediente</h2></div>
        <div class="card-body">
          <ul class="list-unstyled mb-0 small">
            <li class="d-flex justify-content-between py-1">
              <span>Anamnesis</span>
              <span><?= $anamnesis ? badge('anamnesis_status', $anamnesis['status']) : '<span class="badge-soft badge-neutral">Pendiente</span>' ?></span>
            </li>
            <li class="d-flex justify-content-between py-1"><span>Diagnóstico</span>
              <span><?= $diagnoses ? '<span class="badge-soft badge-success">Registrado</span>' : '<span class="badge-soft badge-neutral">Pendiente</span>' ?></span></li>
            <li class="d-flex justify-content-between py-1"><span>Plan terapéutico</span>
              <span><?= $plan ? '<span class="badge-soft badge-success">Activo</span>' : '<span class="badge-soft badge-neutral">Sin plan</span>' ?></span></li>
            <li class="d-flex justify-content-between py-1"><span>Evaluaciones</span>
              <span class="cell-sub"><?= count($evaluations) ?> registradas</span></li>
          </ul>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
