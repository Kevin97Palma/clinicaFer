<?php
/**
 * SGC — Manejo de subida de archivos
 */

require_once dirname(__DIR__) . '/config/app.php';

define('ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/webp',
    'application/pdf',
]);

/**
 * Sube un archivo al storage protegido.
 *
 * @param  array  $file      Elemento de $_FILES
 * @param  string $subdir    Subdirectorio dentro de /storage/ (ej: 'consentimientos')
 * @return string            Ruta relativa guardada (ej: 'consentimientos/file.pdf')
 */
function subirArchivo(array $file, string $subdir): string
{
    // Validar que llegó archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al recibir el archivo: código ' . $file['error']);
    }

    // Validar tamaño
    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException("El archivo supera el tamaño máximo de " . UPLOAD_MAX_MB . " MB.");
    }

    // Validar tipo MIME real (no confiar solo en la extensión)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);
    if (!in_array($mimeReal, ALLOWED_TYPES)) {
        throw new RuntimeException("Tipo de archivo no permitido: {$mimeReal}. Solo PDF e imágenes.");
    }

    // Generar nombre único
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);

    // Crear directorio si no existe
    $dir = STORAGE_PATH . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $destino = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo mover el archivo al storage.');
    }

    return $subdir . '/' . $filename;
}

/**
 * Elimina un archivo del storage (soft: mueve a /storage/papelera/).
 */
function eliminarArchivo(string $rutaRelativa): void
{
    $origen  = STORAGE_PATH . '/' . $rutaRelativa;
    $papelera = STORAGE_PATH . '/papelera';

    if (file_exists($origen)) {
        if (!is_dir($papelera)) mkdir($papelera, 0750, true);
        rename($origen, $papelera . '/' . basename($rutaRelativa));
    }
}

/**
 * Devuelve la URL de descarga segura para un archivo del storage.
 */
function urlArchivo(string $rutaRelativa): string
{
    return APP_URL . '/api/archivos/descargar.php?ruta=' . urlencode($rutaRelativa);
}
