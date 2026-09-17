<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? 'Documento') ?></title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Newsreader:opsz,wght@6..72,400;6..72,600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body style="background: var(--c-bg)">
  <div class="no-print border-bottom bg-white">
    <div class="d-flex justify-content-between align-items-center gap-2 p-3">
      <a class="btn btn-light btn-sm" href="javascript:history.back()"><i class="bi bi-arrow-left me-1"></i> Volver</a>
      <div class="cell-sub d-none d-md-block">Use «Imprimir» y elija <strong>Guardar como PDF</strong> para obtener el archivo.</div>
      <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir / PDF</button>
    </div>
  </div>
  <div class="py-4 px-2">
    <?= $content ?>
  </div>
</body>
</html>
