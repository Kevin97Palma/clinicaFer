-- SGC — Mejoras 6, 7 y 9: tablas nuevas
-- Ejecutar contra sgc_clinica en 31.97.102.179

CREATE TABLE IF NOT EXISTS consentimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT UNSIGNED NOT NULL,
    clinica_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(100) NOT NULL DEFAULT 'Consentimiento informado',
    fecha_firma DATE NULL,
    fecha_vencimiento DATE NULL,
    archivo_path VARCHAR(500) NULL,
    estado ENUM('pendiente','firmado','vencido') NOT NULL DEFAULT 'pendiente',
    creado_por INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id),
    INDEX idx_paciente (paciente_id),
    INDEX idx_clinica_estado (clinica_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('tarea_vencida','cita_proxima','sin_actividad','sistema') NOT NULL DEFAULT 'sistema',
    mensaje TEXT NOT NULL,
    referencia_id INT UNSIGNED NULL,
    referencia_tipo VARCHAR(50) NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_leida (usuario_id, leida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
