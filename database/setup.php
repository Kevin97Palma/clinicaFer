<?php
/**
 * SGC — Script de configuración inicial de la base de datos
 * Ejecutar UNA SOLA VEZ: php database/setup.php
 */

define('DB_HOST', '31.97.102.179');
define('DB_USER', 'root');
define('DB_PASS', 'Soporte@Palm4');
define('DB_NAME', 'sgc_clinica');

echo "============================================================\n";
echo " SGC — Setup de Base de Datos\n";
echo "============================================================\n\n";

// 1. Conectar sin seleccionar base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );
    echo "[OK] Conexión al servidor MySQL exitosa.\n";
} catch (PDOException $e) {
    die("[ERROR] No se pudo conectar: " . $e->getMessage() . "\n");
}

// 2. Crear la base de datos si no existe
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "[OK] Base de datos '" . DB_NAME . "' lista.\n\n";
} catch (PDOException $e) {
    die("[ERROR] No se pudo crear la BD: " . $e->getMessage() . "\n");
}

// 3. Ejecutar schema.sql
echo "--- Ejecutando schema.sql ---\n";
ejecutarSQL($pdo, __DIR__ . '/schema.sql');

// 4. Ejecutar seed.sql
echo "\n--- Ejecutando seed.sql ---\n";
ejecutarSQL($pdo, __DIR__ . '/seed.sql');

// 5. Generar hash correcto para las contraseñas de prueba
echo "\n--- Actualizando contraseñas con hash real ---\n";
$hash = password_hash('Admin1234!', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?");
$stmt->execute([$hash]);
echo "[OK] Contraseña de todos los usuarios de prueba: Admin1234!\n";

// Contraseña especial para representantes: su cédula
$representantes = $pdo->query(
    "SELECT u.id, u.cedula FROM usuarios u
     JOIN usuario_clinica uc ON uc.usuario_id = u.id
     WHERE uc.rol = 'representante'"
)->fetchAll();

foreach ($representantes as $rep) {
    $hashRep = password_hash($rep['cedula'], PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")
        ->execute([$hashRep, $rep['id']]);
}
echo "[OK] Contraseñas de representantes actualizadas a su cédula.\n";

echo "\n============================================================\n";
echo " SETUP COMPLETADO EXITOSAMENTE\n";
echo "============================================================\n";
echo "\nUsuarios de prueba:\n";
echo "  superadmin  → kevin@socket-studio.ec     / Admin1234!\n";
echo "  gerente     → ana.torres@catpi-sas.com   / Admin1234!\n";
echo "  supervisor  → carlos.vasquez@catpi-sas.com / Admin1234!\n";
echo "  operativo   → maria.gutierrez@catpi-sas.com / Admin1234!\n";
echo "  operativo   → luis.medina@catpi-sas.com  / Admin1234!\n";
echo "  represent.  → roberto.alvarado@gmail.com / 0923456789 (cédula)\n";
echo "  represent.  → patricia.molina@gmail.com  / 0934567890 (cédula)\n\n";

// -------------------------------------------------------
function ejecutarSQL(PDO $pdo, string $filePath): void
{
    if (!file_exists($filePath)) {
        echo "[ERROR] Archivo no encontrado: $filePath\n";
        return;
    }

    $sql = file_get_contents($filePath);

    // Separar por statements (ignorar delimiters de comentarios)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($s) { return !empty($s) && !preg_match('/^--/', $s); }
    );

    $ok = 0;
    $err = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        try {
            $pdo->exec($statement);
            $ok++;
        } catch (PDOException $e) {
            // Ignorar errores de "already exists" en seeds duplicados
            if (strpos($e->getMessage(), 'Duplicate entry') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                continue;
            }
            echo "[WARN] " . $e->getMessage() . "\n";
            $err++;
        }
    }
    echo "[OK] Statements ejecutados: $ok | Errores: $err\n";
}
