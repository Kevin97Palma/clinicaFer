<?php
/**
 * SGC — Generador automático de notificaciones
 * Incluir en header.php después de requireAuth().
 * Solo ejecuta una vez por sesión cada 5 minutos.
 */

function checkNotificaciones(): void
{
    // Evitar ejecución más de 1 vez cada 5 minutos por sesión
    if (!empty($_SESSION['notif_last_check']) &&
        (time() - $_SESSION['notif_last_check']) < 300) {
        return;
    }
    $_SESSION['notif_last_check'] = time();

    $pdo      = db();
    $userId   = $_SESSION['user_id'] ?? 0;
    $clinicaId = $_SESSION['clinica_activa_id'] ?? null;

    if (!$clinicaId || !$userId) {
        return;
    }

    $hoy = date('Y-m-d');

    // Helper: inserta notificación solo si no existe ya hoy para el mismo tipo + referencia
    $insertarNotif = function (
        string $tipo,
        string $mensaje,
        ?int $referenciaId = null,
        ?string $referenciaTipo = null
    ) use ($pdo, $userId, $hoy): void {
        // Verificar duplicado del día
        $stmtCheck = $pdo->prepare(
            "SELECT COUNT(*) FROM notificaciones
             WHERE usuario_id = :uid
               AND tipo = :tipo
               AND referencia_id <=> :refid
               AND referencia_tipo <=> :reftype
               AND DATE(created_at) = :hoy"
        );
        $stmtCheck->execute([
            ':uid'     => $userId,
            ':tipo'    => $tipo,
            ':refid'   => $referenciaId,
            ':reftype' => $referenciaTipo,
            ':hoy'     => $hoy,
        ]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            return;
        }

        $stmtIns = $pdo->prepare(
            "INSERT INTO notificaciones (usuario_id, tipo, mensaje, referencia_id, referencia_tipo)
             VALUES (:uid, :tipo, :msg, :refid, :reftype)"
        );
        $stmtIns->execute([
            ':uid'     => $userId,
            ':tipo'    => $tipo,
            ':msg'     => $mensaje,
            ':refid'   => $referenciaId,
            ':reftype' => $referenciaTipo,
        ]);
    };

    try {
        // ── 1. Tareas vencidas hoy ─────────────────────────────────────────
        $stmtTareas = $pdo->prepare(
            "SELECT tc.id, tc.titulo, p.nombre, p.apellido
             FROM tareas_cronograma tc
             INNER JOIN cronogramas cr ON cr.id = tc.cronograma_id
             INNER JOIN pacientes p    ON p.id  = cr.paciente_id
             WHERE cr.operativo_id = :uid
               AND cr.clinica_id  = :cid
               AND tc.estado      = 'pendiente'
               AND tc.fecha_programada = :hoy"
        );
        $stmtTareas->execute([':uid' => $userId, ':cid' => $clinicaId, ':hoy' => $hoy]);
        $tareas = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tareas as $t) {
            $nombrePac = trim($t['nombre'] . ' ' . $t['apellido']);
            $insertarNotif(
                'tarea_vencida',
                "Tarea vencida hoy: \"{$t['titulo']}\" — Paciente: {$nombrePac}",
                (int)$t['id'],
                'tareas_cronograma'
            );
        }

        // ── 2. Citas en las próximas 2 horas ──────────────────────────────
        $stmtCitas = $pdo->prepare(
            "SELECT c.id, c.fecha_hora, p.nombre, p.apellido
             FROM citas c
             INNER JOIN pacientes p ON p.id = c.paciente_id
             WHERE c.operativo_id = :uid
               AND c.clinica_id  = :cid
               AND c.estado IN ('programada','confirmada')
               AND c.fecha_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)"
        );
        $stmtCitas->execute([':uid' => $userId, ':cid' => $clinicaId]);
        $citas = $stmtCitas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($citas as $c) {
            $nombrePac = trim($c['nombre'] . ' ' . $c['apellido']);
            $horaStr   = (new DateTime($c['fecha_hora']))->format('H:i');
            $insertarNotif(
                'cita_proxima',
                "Cita próxima a las {$horaStr} con {$nombrePac}",
                (int)$c['id'],
                'citas'
            );
        }

        // ── 3. Pacientes sin actividad > 21 días ──────────────────────────
        $stmtInact = $pdo->prepare(
            "SELECT p.id, p.nombre, p.apellido,
                    MAX(s.fecha_sesion) AS ultima_sesion
             FROM pacientes p
             LEFT JOIN sesiones s ON s.paciente_id = p.id
             WHERE p.operativo_asignado_id = :uid
               AND p.clinica_id = :cid
               AND p.estado = 'activo'
               AND p.deleted_at IS NULL
             GROUP BY p.id
             HAVING ultima_sesion IS NULL
                 OR ultima_sesion < DATE_SUB(CURDATE(), INTERVAL 21 DAY)"
        );
        $stmtInact->execute([':uid' => $userId, ':cid' => $clinicaId]);
        $inactivos = $stmtInact->fetchAll(PDO::FETCH_ASSOC);

        foreach ($inactivos as $pac) {
            $nombrePac = trim($pac['nombre'] . ' ' . $pac['apellido']);
            $diasStr   = $pac['ultima_sesion']
                ? 'Última sesión: ' . (new DateTime($pac['ultima_sesion']))->format('d/m/Y')
                : 'Sin sesiones registradas';
            $insertarNotif(
                'sin_actividad',
                "Paciente sin actividad: {$nombrePac}. {$diasStr}.",
                (int)$pac['id'],
                'pacientes'
            );
        }

    } catch (Throwable $e) {
        // Silenciar errores para no romper el header
        error_log('[SGC-NOTIF] ' . $e->getMessage());
    }
}
