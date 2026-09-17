<div class="page-head">
  <div><h1>Respaldos de la base de datos</h1>
    <p class="lead-sm">Exportación SQL comprimida, almacenada fuera del directorio público y disponible solo para el administrador.</p></div>
  <form method="post" action="<?= e(url('respaldos')) ?>" data-confirm="¿Generar un respaldo completo de la base de datos ahora?">
    <?= csrf_field() ?><button class="btn btn-primary"><i class="bi bi-database-down me-1"></i> Generar respaldo</button>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Fecha</th><th>Archivo</th><th>Tipo</th><th>Tamaño</th><th>Resultado</th><th>Usuario</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($backups as $b): ?>
        <tr>
          <td class="cell-sub"><?= e(fdate($b['created_at'], true)) ?></td>
          <td class="cell-sub"><?= e($b['file_name'] ?: '—') ?><div class="cell-sub"><?= e($b['message']) ?></div></td>
          <td class="cell-sub"><?= e(ucfirst($b['type'])) ?></td>
          <td class="cell-sub"><?= $b['size_bytes'] ? e(number_format($b['size_bytes'] / 1024, 0)) . ' KB' : '—' ?></td>
          <td><?= $b['result'] === 'exito' ? '<span class="badge-soft badge-success">Éxito</span>' : '<span class="badge-soft badge-danger">Error</span>' ?></td>
          <td class="cell-sub"><?= e($b['user_name'] ?: 'Sistema') ?></td>
          <td class="text-end">
            <?php if ($b['result'] === 'exito'): ?>
              <a class="btn btn-sm btn-light" href="<?= e(url('respaldos/' . $b['id'] . '/descargar')) ?>"><i class="bi bi-download"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$backups): ?><tr><td colspan="7"><div class="empty"><i class="bi bi-database"></i>Aún no se han generado respaldos.</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
