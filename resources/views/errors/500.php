<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error del sistema</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width:640px">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4 text-center">
        <h1 class="h4">Ocurrió un error inesperado</h1>
        <p class="text-muted mb-3">La operación no se completó. Vuelva a intentarlo; si persiste, avise al administrador del sistema.</p>
        <?php if (!empty($debug)): ?>
          <pre class="text-start small bg-white border rounded p-3 mb-3" style="white-space:pre-wrap"><?= htmlspecialchars((string) $debug, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars(App\Core\Config::get('url'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a>
      </div>
    </div>
  </div>
</body>
</html>
