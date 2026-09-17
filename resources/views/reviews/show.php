<div class="page-head">
  <div>
    <h1>Evaluación de progreso</h1>
    <p class="lead-sm">
      <a href="<?= e(url('pacientes/' . $review['patient_id'])) ?>"><?= e($review['first_name'] . ' ' . $review['last_name']) ?></a>
      · <?= e($review['file_number']) ?> · <?= e(fdate($review['review_date'])) ?> · <?= (int) $review['sessions_at_review'] ?> sesiones
    </p>
  </div>
  <a class="btn btn-light" href="<?= e(url('pacientes/' . $review['patient_id'], ['tab' => 'sesiones'])) ?>">Volver</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <?php
        $blocks = [
            'Objetivos iniciales' => $review['objectives_initial'],
            'Objetivos logrados' => $review['objectives_achieved'],
            'Objetivos en proceso' => $review['objectives_in_progress'],
            'Objetivos sin avance' => $review['objectives_no_progress'],
            'Reporte del paciente' => $review['patient_report'],
            'Reporte familiar' => $review['family_report'],
            'Reporte escolar' => $review['school_report'],
            'Observación clínica' => $review['clinical_observation'],
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
    <div class="card">
      <div class="card-header"><h2 class="card-title">Conclusión</h2></div>
      <div class="card-body">
        <dl class="kv">
          <dt>Estado general</dt><dd><?= badge('general_status', $review['general_status']) ?></dd>
          <dt>Decisión clínica</dt><dd><?= e(label('clinical_decision', $review['clinical_decision'])) ?></dd>
          <dt>Registrada por</dt><dd><?= e($review['created_by_name']) ?></dd>
          <dt>Fecha de registro</dt><dd><?= e(fdate($review['created_at'], true)) ?></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
