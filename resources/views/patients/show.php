<?php
$pid = (int) $patient['id'];
$name = $patient['first_name'] . ' ' . $patient['last_name'];
$next = $header['next_appointment'];
$pkg = $header['package'];

$tabs = [
    'resumen' => ['Resumen', 'clipboard-data', true],
    'anamnesis' => ['Anamnesis', 'file-earmark-medical', can('clinical.view')],
    'sesiones' => ['Sesiones', 'journal-text', can('clinical.view')],
    'evaluaciones' => ['Evaluaciones', 'clipboard2-pulse', can('evaluations.view')],
    'plan' => ['Plan terapéutico', 'bullseye', can('clinical.view')],
    'documentos' => ['Documentos', 'file-earmark-text', can('documents.view')],
    'pagos' => ['Pagos', 'wallet2', can('payments.view')],
    'agenda' => ['Agenda', 'calendar3', can('agenda.view')],
    'archivos' => ['Archivos', 'paperclip', can('files.view') || can('files.manage')],
];
?>
<div class="card mb-3">
  <div class="patient-hero">
    <div class="avatar avatar-lg"><?= e(initials($name)) ?></div>
    <div class="min-w-0">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h1 class="mb-0"><?= e($name) ?></h1>
        <?= badge('patient_status', $patient['status']) ?>
      </div>
      <div class="hero-meta">
        <span><?= e(age($patient['birth_date'])) ?> · <?= e(label('sex', $patient['sex'])) ?></span>
        <span>Expediente <strong><?= e($patient['file_number']) ?></strong></span>
        <span>Ingreso <strong><?= e(fdate($patient['intake_date'])) ?></strong></span>
        <span>Última sesión <strong><?= e(fdate($header['last_session'])) ?></strong></span>
        <span>Próxima cita <strong><?= $next ? e(fdate($next['starts_at'], true)) : '—' ?></strong></span>
        <?php if ($pkg): ?><span>Paquete <strong><?= (int) $pkg['sessions_used'] ?>/<?= (int) $pkg['sessions_total'] ?></strong></span><?php endif; ?>
      </div>
    </div>
    <div class="quick-actions">
      <?php if (can('clinical.manage')): ?>
        <a class="btn btn-primary btn-sm" href="<?= e(url('sesiones/nueva', ['patient_id' => $pid])) ?>"><i class="bi bi-journal-plus me-1"></i> Nueva sesión</a>
      <?php endif; ?>
      <?php if (can('agenda.manage')): ?>
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#modal-appointment" data-reset
                data-fill='<?= e(json_encode(['patient_id' => $pid])) ?>'><i class="bi bi-calendar-plus me-1"></i> Nueva cita</button>
      <?php endif; ?>
      <?php if (can('payments.manage')): ?>
        <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#modal-payment" data-reset
                data-fill='<?= e(json_encode(['patient_id' => $pid])) ?>'><i class="bi bi-cash-coin me-1"></i> Nuevo pago</button>
      <?php endif; ?>
      <?php if (can('evaluations.manage')): ?>
        <a class="btn btn-soft btn-sm" href="<?= e(url('evaluaciones/nueva', ['patient_id' => $pid])) ?>"><i class="bi bi-clipboard2-plus me-1"></i> Nueva evaluación</a>
      <?php endif; ?>
      <?php if (can('documents.manage')): ?>
        <a class="btn btn-soft btn-sm" href="<?= e(url('documentos/nuevo', ['patient_id' => $pid])) ?>"><i class="bi bi-file-earmark-plus me-1"></i> Documento</a>
      <?php endif; ?>
      <?php if (can('patients.manage')): ?>
        <a class="btn btn-light btn-sm" href="<?= e(url('pacientes/' . $pid . '/editar')) ?>" title="Editar datos"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
    </div>
  </div>
  <div class="tabs-scroll">
    <ul class="nav nav-tabs border-0">
      <?php foreach ($tabs as $key => [$label, $icon, $visible]): ?>
        <?php if (!$visible) continue; ?>
        <li class="nav-item">
          <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="<?= e(url('pacientes/' . $pid, $key === 'resumen' ? [] : ['tab' => $key])) ?>">
            <i class="bi bi-<?= e($icon) ?> me-1"></i><?= e($label) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php
$view = 'patients/tabs/' . (array_key_exists($tab, $tabs) ? $tab : 'resumen');
echo App\Core\View::fetch($view, get_defined_vars());
?>

<?= App\Core\View::fetch('partials/patient_modals', get_defined_vars()) ?>
