<div class="card">
  <div class="card-header">
    <h2 class="card-title">Citas del paciente</h2>
    <?php if (can('agenda.manage')): ?>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-appointment" data-reset
              data-fill='<?= e(json_encode(['patient_id' => $pid])) ?>'><i class="bi bi-calendar-plus me-1"></i> Nueva cita</button>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Fecha y hora</th><th>Tipo</th><th class="d-none d-md-table-cell">Motivo</th><th>Estado</th><th class="d-none d-lg-table-cell">Profesional</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
        <tr>
          <td><span class="cell-title"><?= e(fdate($a['starts_at'], true)) ?></span><div class="cell-sub"><?= (int) $a['duration_min'] ?> min</div></td>
          <td class="cell-sub"><?= e(label('appointment_type', $a['type'])) ?></td>
          <td class="d-none d-md-table-cell cell-sub"><?= e($a['reason'] ?: '—') ?></td>
          <td><?= badge('appointment_status', $a['status']) ?></td>
          <td class="d-none d-lg-table-cell cell-sub"><?= e($a['professional_name']) ?></td>
          <td class="text-end">
            <?php if ($a['session_id']): ?>
              <a class="btn btn-sm btn-light" href="<?= e(url('sesiones/' . $a['session_id'])) ?>">Ver sesión</a>
            <?php elseif (can('clinical.manage') && strtotime($a['starts_at']) <= time() + 3600 && in_array($a['status'], ['programada', 'confirmada', 'atendida'], true)): ?>
              <a class="btn btn-sm btn-soft" href="<?= e(url('sesiones/nueva', ['appointment_id' => $a['id']])) ?>">Registrar sesión</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$appointments): ?>
        <tr><td colspan="6"><div class="empty"><i class="bi bi-calendar3"></i>Sin citas registradas.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
