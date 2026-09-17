<?php
$sections = [
    ['family_data', 'Datos familiares', 'Composición del hogar, edades, ocupación de los padres o cuidadores.'],
    ['consultation_reason', 'Motivo de consulta', 'Descripción del motivo referido por la familia y por el paciente.'],
    ['prenatal_history', 'Antecedentes prenatales', 'Embarazo deseado, controles, enfermedades, medicación, estado emocional materno.'],
    ['perinatal_history', 'Antecedentes perinatales', 'Tipo de parto, semanas de gestación, peso, talla, llanto, incubadora.'],
    ['motor_development', 'Desarrollo motor', 'Sostén cefálico, sedestación, gateo, marcha, motricidad fina.'],
    ['language_development', 'Desarrollo del lenguaje', 'Balbuceo, primeras palabras, frases, comprensión, articulación.'],
    ['socioemotional_development', 'Desarrollo socioemocional', 'Apego, autorregulación, juego, autonomía.'],
    ['medical_history', 'Antecedentes médicos', 'Enfermedades, hospitalizaciones, cirugías, alergias, controles.'],
    ['medications', 'Medicación actual', 'Nombre, dosis y profesional que la indicó.'],
    ['external_professionals', 'Profesionales externos', 'Pediatra, neurólogo, terapista de lenguaje, institución educativa.'],
    ['neurological_history', 'Antecedentes neurológicos', 'Convulsiones, traumatismos, estudios (EEG, imágenes).'],
    ['psychiatric_history', 'Antecedentes psicológicos / psiquiátricos', 'Atenciones previas, diagnósticos, tratamientos, antecedentes familiares.'],
    ['school_history', 'Historia escolar', 'Ingreso, adaptación, rendimiento, apoyos, reportes docentes.'],
    ['family_dynamics', 'Dinámica familiar', 'Estilo de crianza, normas, rutinas, conflictos, red de apoyo.'],
    ['behavior', 'Conducta', 'Conductas que preocupan, frecuencia, contextos, manejo actual.'],
    ['sleep', 'Sueño', 'Horario, rutina, despertares, pesadillas, colecho.'],
    ['feeding', 'Alimentación', 'Apetito, selectividad, horarios, hábitos.'],
    ['screen_use', 'Uso de pantallas', 'Tiempo diario, contenidos, reglas, impacto observado.'],
    ['social_relations', 'Relaciones sociales', 'Pares, juego compartido, habilidades sociales, aislamiento.'],
    ['clinical_observations', 'Observaciones clínicas', 'Impresión general de la profesional durante la entrevista.'],
];
$canEdit = can('clinical.manage');
?>
<div class="row g-3">
  <div class="col-lg-3 d-none d-lg-block">
    <div class="card position-sticky" style="top: 84px">
      <div class="card-header"><h2 class="card-title">Secciones</h2></div>
      <div class="card-body anam-nav py-2">
        <?php foreach ($sections as $i => [$field, $label]): ?>
          <a href="#sec-<?= e($field) ?>"><?= ($i + 1) . '. ' . e($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Anamnesis <span class="confidential ms-2"><i class="bi bi-lock-fill"></i> confidencial</span></h2>
          <div class="cell-sub">
            <?php if ($anamnesis): ?>
              <?= badge('anamnesis_status', $anamnesis['status']) ?>
              · Creada <?= e(fdate($anamnesis['created_at'], true)) ?>
              · Última modificación <?= e(fdate($anamnesis['updated_at'], true)) ?><?= $anamnesis['updated_by_name'] ? ' por ' . e($anamnesis['updated_by_name']) : '' ?>
            <?php else: ?>
              Aún no se ha iniciado. Puede guardar por partes y continuar después.
            <?php endif; ?>
          </div>
        </div>
        <span class="cell-sub" data-autosave-status></span>
      </div>

      <?php if ($canEdit): ?>
        <form method="post" action="<?= e(url('pacientes/' . $pid . '/anamnesis')) ?>" data-autosave>
          <?= csrf_field() ?>
          <div class="card-body">
            <?php foreach ($sections as $i => [$field, $label, $hint]): ?>
              <div class="form-section" id="sec-<?= e($field) ?>">
                <div class="form-section-title"><span class="num"><?= $i + 1 ?></span> <?= e($label) ?></div>
                <p class="cell-sub mb-2"><?= e($hint) ?></p>
                <textarea class="form-control" name="<?= e($field) ?>" rows="<?= in_array($field, ['medications', 'external_professionals'], true) ? 2 : 4 ?>"><?= e($anamnesis[$field] ?? '') ?></textarea>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="sticky-actions">
            <div class="form-check me-auto">
              <input class="form-check-input" type="checkbox" id="status" name="status" value="completa" <?= ($anamnesis['status'] ?? '') === 'completa' ? 'checked' : '' ?>>
              <label class="form-check-label" for="status">Marcar anamnesis como completa</label>
            </div>
            <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Guardar</button>
          </div>
        </form>
      <?php else: ?>
        <div class="card-body">
          <?php foreach ($sections as $i => [$field, $label]): ?>
            <div class="form-section" id="sec-<?= e($field) ?>">
              <div class="form-section-title"><span class="num"><?= $i + 1 ?></span> <?= e($label) ?></div>
              <div class="clinical-box"><?= nl2br_e($anamnesis[$field] ?? '') ?: '<span class="text-muted">Sin registro</span>' ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
