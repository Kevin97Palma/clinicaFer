<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use RuntimeException;

/**
 * Archivos sensibles fuera del directorio público, con nombre aleatorio.
 * En la BD solo se guardan metadatos y la ruta relativa segura.
 */
final class FileStorage
{
    private const ALLOWED = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    public static function root(): string
    {
        return Config::get('storage_path');
    }

    /** Valida extensión, MIME real y tamaño; guarda y devuelve [stored_name, mime, size, original]. */
    public static function store(array $upload, string $folder = 'uploads', ?array $allowedExt = null): array
    {
        if (!isset($upload['error']) || is_array($upload['error'])) {
            throw new RuntimeException('Archivo no válido.');
        }
        if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El archivo supera el tamaño permitido.');
        }
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('No se pudo recibir el archivo.');
        }
        $maxBytes = Config::get('max_upload_mb') * 1024 * 1024;
        if ($upload['size'] > $maxBytes) {
            throw new RuntimeException('El archivo supera ' . Config::get('max_upload_mb') . ' MB.');
        }

        $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
        $allowed = $allowedExt ? array_intersect_key(self::ALLOWED, array_flip($allowedExt)) : self::ALLOWED;
        if (!isset($allowed[$ext])) {
            throw new RuntimeException('Tipo de archivo no permitido. Formatos: ' . strtoupper(implode(', ', array_keys($allowed))) . '.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']) ?: '';
        if (!in_array($mime, $allowed[$ext], true)) {
            throw new RuntimeException('El contenido del archivo no coincide con su extensión.');
        }
        if ($ext === 'docx' && !self::looksLikeDocx($upload['tmp_name'])) {
            throw new RuntimeException('El documento DOCX no es válido.');
        }

        $sub = $folder . '/' . date('Y/m');
        $dir = self::root() . '/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento.');
        }
        $stored = $sub . '/' . bin2hex(random_bytes(20)) . '.' . $ext;
        if (!move_uploaded_file($upload['tmp_name'], self::root() . '/' . $stored)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }
        @chmod(self::root() . '/' . $stored, 0640);

        return [
            'stored_name' => $stored,
            'mime_type' => $ext === 'docx' ? self::ALLOWED['docx'][0] : $mime,
            'size_bytes' => (int) $upload['size'],
            'original_name' => mb_substr(basename($upload['name']), 0, 255),
        ];
    }

    private static function looksLikeDocx(string $path): bool
    {
        $h = fopen($path, 'rb');
        $sig = $h ? fread($h, 4) : '';
        if ($h) {
            fclose($h);
        }
        return $sig === "PK\x03\x04";
    }

    /** Guarda un archivo del expediente y registra sus metadatos. */
    public static function storePatientFile(int $patientId, array $upload, string $category, ?string $description = null): int
    {
        $meta = self::store($upload, 'uploads');
        $id = Database::insert('patient_files', $meta + [
            'patient_id' => $patientId, 'category' => $category,
            'description' => $description, 'uploaded_by' => Auth::id(),
        ]);
        AuditService::log('CREAR', 'archivos', $id, 'Archivo adjuntado al expediente');
        return $id;
    }

    public static function path(string $stored): string
    {
        // Impide rutas fuera del almacenamiento
        if (str_contains($stored, '..') || str_starts_with($stored, '/')) {
            throw new RuntimeException('Ruta no válida.');
        }
        return self::root() . '/' . $stored;
    }

    public static function send(string $stored, string $mime, string $downloadName, bool $inline = false): void
    {
        $path = self::path($stored);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Archivo no disponible.');
        }
        $safeName = preg_replace('/[^\w.\- ]+/u', '_', $downloadName);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safeName . '"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
