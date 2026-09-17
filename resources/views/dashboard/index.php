<?php
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
// [clave, etiqueta, icono, tono, enlace, visible, es_monto]
$cards = [
    ['active_patients', 'Pacientes activos', 'people', 'primary', url('pacientes', ['estado' => 'activo']), can('patients.view')],
    ['sessions_today', 'Citas de hoy', 'calendar-check', 'info', url('agenda'), can('agenda.view')],
    ['evaluations_pending', 'Evaluaciones en proceso', 'clipboard2-pulse', 'accent', url('evaluaciones', ['estado' => 'en_proceso']), can('evaluations.view')],
    ['reports_pending', 'Informes pendientes', 'file-earmark-text', 'warning', url('documentos', ['estado' => 'borrador']), can('documents.view')],
    ['payments_pending', 'Pagos pendientes', 'exclamation-circle', 'danger', url('pagos', ['estado' => 'pendiente']), can('payments.view'), true],
    ['income_month', 'Ingresos del mes', 'graph-up-arrow', 'success', url('reportes'), can('payments.view'), true],
];
?>
<div class="page-head">
  <div>
    <h1><?= e($greeting) ?>, <?= e(explode(' ', auth_user()['name'])[0]) ?></h1>
    <p class="lead-sm"><?= e(ucfirst(strftime_es(date('Y-m-d')))) ?> · <?= count($appointments) ?> cita<?= count($appointments) == 1 ? '' : 's' ?> agendada<?= count($appointments) == 1 ? '' : 's' ?> para hoy</p>
  </div>
  <div class="d-flex gap-2">
    <?php if (can('clinical.manage')): ?><a class="btn btn-soft" href="<?= e(url('sesiones/nueva')) ?>"><i class="bi bi-journal-plus me-1"></i> Nueva sesión</a><?php endif; ?>
    <?php if (can('patients.manage')): ?><a class="btn btn-primary" href="<?= e(url('pacientes/nuevo')) ?>"><i class="bi bi-person-plus me-1"></i> Nuevo paciente</a><?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($cards as $c): ?>
    <?php if (isset($c[5]) && $c[5] === false) continue; ?>
    <div class="col-6 col-lg-4 col-xl-2">
      <a class="card stat h-100" href="<?= e($c[4]) ?>">
        <div class="stat-icon tone-<?= e($c[3]) ?>"><i class="bi bi-<?= e($c[2]) ?>"></i></div>
        <div>
          <div class="stat-value"><?= !empty($c[6]) ? e(money($stats[$c[0]])) : (int) $stats[$c[0]] ?></div>
          <div class="stat-label"><?= e($c[1]) ?></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card mb-3">
      <div class="card-header">
        <h2 class="card-title">Pacientes de hoy</h2>
        <a class="btn btn-sm btn-light" href="<?= e(url('agenda')) ?>">Ver agenda</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Hora</th><th>Paciente</th><th class="d-none d-md-table-cell">Objetivo / tipo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($appointments as $a): ?>
            <tr>
              <td class="cell-title"><?= e(date('H:i', strtotime($a['starts_at']))) ?><div class="cell-sub"><?= (int) $a['duration_min'] ?> min</div></td>
              <td>
                <a class="cell-title" href="<?= e(url('pacientes/' . $a['patient_id'])) ?>"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></a>
                <div class="cell-sub"><?= e(age($a['birth_date'])) ?> · <?= e($a['file_number']) ?></div>
              </td>
              <td class="d-none d-md-table-cell cell-sub"><?= e($a['reason'] ?: label('appointment_type', $a['type'])) ?></td>
              <td><?= badge('appointment_status', $a['status']) ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <?php if ($a['session_id']): ?>
                    <a class="btn btn-sm btn-light" href="<?= e(url('sesiones/' . $a['session_id'])) ?>">Ver sesión</a>
                  <?php else: ?>
                    <?php if (can('agenda.manage') && in_array($a['status'], ['programada', 'confirmada'], true)): ?>
                      <form method="post" action="<?= e(url('agenda/citas/' . $a['id'] . '/estado')) ?>" class="d-inline">
                        <?= csrf_field() ?><input type="hidden" name="status" value="atendida">
                        <button class="btn btn-sm btn-soft" title="Marcar atendido"><i class="bi bi-check2"></i></button>
                      </form>
                      <form method="post" action="<?= e(url('agenda/citas/' . $a['id'] . '/estado')) ?>" class="d-inline" data-confirm="¿Marcar la cita como no asistida?">
                        <?= csrf_field() ?><input type="hidden" name="status" value="no_asistio">
                        <button class="btn btn-sm btn-light" title="No asistió"><i class="bi bi-person-x"></i></button>
                      </form>
                    <?php endif; ?>
                    <?php if (can('clinical.manage')): ?>
                      <a class="btn btn-sm btn-primary" href="<?= e(url('sesiones/nueva', ['appointment_id' => $a['id']])) ?>">Registrar sesión</a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$appointments): ?>
            <tr><td colspan="5"><div class="empty"><i class="bi bi-cup-hot"></i>No hay citas programadas para hoy.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($recent_sessions): ?>
      <div class="card">
        <div class="card-header"><h2 class="card-title">Últimas sesiones registradas</h2>
          <a class="btn btn-sm btn-light" href="<?= e(url('sesiones')) ?>">Ver todas</a></div>
        <div class="card-body">
          <div class="timeline">
            <?php foreach ($recent_sessions as $s): ?>
              <div class="timeline-item <?= $s['attendance'] === 'atendido' ? '' : 'muted' ?>">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                  <div>
                    <a class="cell-title" href="<?= e(url('sesiones/' . $s['id'])) ?>"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></a>
                    <span class="cell-sub">· <?= e(fdate($s['session_date'])) ?><?= $s['session_number'] ? ' · sesión N.º ' . (int) $s['session_number'] : '' ?></span>
                  </div>
                  <?= badge('attendance', $s['attendance']) ?>
                </div>
                <?php if ($s['objective_worked']): ?><div class="cell-sub mt-1"><?= e(mb_strimwidth((string) $s['objective_worked'], 0, 120, '…')) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-xl-4">
    <?php if (can('alerts.view')): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h2 class="card-title">Alertas</h2>
          <a class="btn btn-sm btn-light" href="<?= e(url('alertas')) ?>">Ver todas</a>
        </div>
        <div>
          <?php foreach ($alerts as $a): ?>
            <div class="alert-row <?= $a['status'] === 'pendiente' ? 'is-new' : '' ?>">
              <div class="dot tone-<?= e(['INACTIVIDAD_PACIENTE' => 'warning', 'PAQUETE_POR_FINALIZAR' => 'accent', 'EVALUACION_PENDIENTE' => 'info',
                  'INFORME_PENDIENTE' => 'primary', 'REVISION_TERAPEUTICA' => 'success', 'PAGO_PENDIENTE' => 'danger'][$a['type']] ?? 'info') ?>">
                <i class="bi bi-<?= e(['INACTIVIDAD_PACIENTE' => 'hourglass-split', 'PAQUETE_POR_FINALIZAR' => 'box-seam', 'EVALUACION_PENDIENTE' => 'clipboard2-pulse',
                  'INFORME_PENDIENTE' => 'file-earmark-text', 'REVISION_TERAPEUTICA' => 'graph-up', 'PAGO_PENDIENTE' => 'cash-coin'][$a['type']] ?? 'bell') ?>"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="small"><?= e($a['message']) ?></div>
                <div class="cell-sub">
                  <?= e(label('alert_type', $a['type'])) ?> · <?= e(fdate($a['created_at'])) ?>
                  <?php if ($a['patient_id']): ?> · <a href="<?= e(url('pacientes/' . $a['patient_id'])) ?>">abrir expediente</a><?php endif; ?>
                </div>
              </div>
              <form method="post" action="<?= e(url('alertas/' . $a['id'] . '/estado')) ?>">
                <?= csrf_field() ?><input type="hidden" name="status" value="resuelta">
                <button class="btn btn-sm btn-light" title="Marcar resuelta"><i class="bi bi-check2"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
          <?php if (!$alerts): ?>
            <div class="empty py-4"><i class="bi bi-check2-circle"></i>No hay alertas pendientes.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h2 class="card-title">Próximos 7 días</h2></div>
      <div class="card-body">
        <?php if ($week): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($week as $d): ?>
              <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><?= e(strftime_es($d['d'])) ?></span>
                <span class="badge-soft badge-primary"><?= (int) $d['n'] ?> cita<?= $d['n'] == 1 ? '' : 's' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="empty py-3"><i class="bi bi-calendar3"></i>Sin citas programadas esta semana.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
