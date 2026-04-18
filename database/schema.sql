-- ============================================================
-- SGC — Sistema de Gestión Clínica Psicológica
-- Schema completo MySQL 8.x
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- MULTICLÍNICA
-- ============================================================

CREATE TABLE IF NOT EXISTS clinicas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    ruc             VARCHAR(20)  NOT NULL UNIQUE,
    direccion       VARCHAR(255),
    telefono        VARCHAR(20),
    email           VARCHAR(100),
    logo            VARCHAR(255),
    config_json     JSON,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USUARIOS
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cedula          VARCHAR(20)  NOT NULL UNIQUE,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    telefono        VARCHAR(20),
    avatar          VARCHAR(255),
    primer_login    TINYINT(1) NOT NULL DEFAULT 1,
    intentos_login  TINYINT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL DEFAULT NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RELACIÓN USUARIO ↔ CLÍNICA (roles)
-- ============================================================

CREATE TABLE IF NOT EXISTS usuario_clinica (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    clinica_id  INT UNSIGNED NOT NULL,
    rol         ENUM('superadmin','gerente','supervisor','operativo','representante') NOT NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_clinica (usuario_id, clinica_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PACIENTES
-- ============================================================

CREATE TABLE IF NOT EXISTS pacientes (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinica_id                  INT UNSIGNED NOT NULL,
    codigo_paciente             VARCHAR(30)  NOT NULL,
    -- Datos del paciente
    nombre                      VARCHAR(100) NOT NULL,
    apellido                    VARCHAR(100) NOT NULL,
    fecha_nacimiento            DATE         NOT NULL,
    sexo                        ENUM('M','F','otro') NOT NULL,
    cedula                      VARCHAR(20),
    -- Representante legal
    representante_nombre        VARCHAR(200) NOT NULL,
    representante_cedula        VARCHAR(20)  NOT NULL,
    representante_telefono      VARCHAR(20),
    representante_email         VARCHAR(150),
    representante_parentesco    VARCHAR(50),
    -- Clínico
    motivo_consulta             TEXT,
    fecha_ingreso               DATE NOT NULL,
    operativo_asignado_id       INT UNSIGNED NULL,
    estado                      ENUM('registrado','en_espera','activo','egresado','suspendido') NOT NULL DEFAULT 'registrado',
    estado_flujo                ENUM('registrado','anamnesis','evaluacion','diagnostico','plan','sesiones','seguimiento') NOT NULL DEFAULT 'registrado',
    consentimiento_archivo      VARCHAR(255),
    -- Usuario representante (acceso al portal)
    usuario_representante_id    INT UNSIGNED NULL,
    -- Auditoría
    created_by                  INT UNSIGNED NOT NULL,
    updated_by                  INT UNSIGNED NULL,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                  TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_codigo_clinica (codigo_paciente, clinica_id),
    FOREIGN KEY (clinica_id) REFERENCES clinicas(id),
    FOREIGN KEY (operativo_asignado_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_representante_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitudes de toma de paciente
CREATE TABLE IF NOT EXISTS solicitudes_paciente (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT UNSIGNED NOT NULL,
    clinica_id      INT UNSIGNED NOT NULL,
    solicitante_id  INT UNSIGNED NOT NULL,
    supervisor_id   INT UNSIGNED NULL,
    estado          ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    motivo          TEXT,
    respuesta       TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)    REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)     REFERENCES clinicas(id),
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (supervisor_id)  REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL CLÍNICO — PASO 1: ANAMNESIS
-- ============================================================

CREATE TABLE IF NOT EXISTS anamnesis (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id                 INT UNSIGNED NOT NULL,
    clinica_id                  INT UNSIGNED NOT NULL,
    operativo_id                INT UNSIGNED NOT NULL,
    fecha_anamnesis             DATE NOT NULL,
    -- Antecedentes heredofamiliares
    antec_linea_materna         TEXT,
    antec_linea_paterna         TEXT,
    retardo_mental              TINYINT(1) DEFAULT 0,
    trastornos_psiquiatricos    TINYINT(1) DEFAULT 0,
    epilepsia                   TINYINT(1) DEFAULT 0,
    sindromes                   TINYINT(1) DEFAULT 0,
    prob_aprendizaje            TINYINT(1) DEFAULT 0,
    -- Antecedentes prenatales
    embarazos                   INT DEFAULT 1,
    deseado                     TINYINT(1) DEFAULT 1,
    abortos                     INT DEFAULT 0,
    tiempo_gestacion            INT COMMENT 'semanas',
    complicaciones              TEXT,
    estado_emocional            TEXT,
    enfermedades_prenatales_json JSON,
    medicamentos                TEXT,
    -- Antecedentes perinatales
    tipo_parto                  ENUM('normal','cesarea','forceps') DEFAULT 'normal',
    sufrimiento_fetal           TINYINT(1) DEFAULT 0,
    estado_recien_nacido_json   JSON COMMENT 'coloracion, peso, talla, llanto, succion, incubadora',
    -- Antecedentes postnatales
    caidas                      TINYINT(1) DEFAULT 0,
    convulsiones                TINYINT(1) DEFAULT 0,
    vacunas                     TINYINT(1) DEFAULT 1,
    enfermedades_postnatales    TEXT,
    -- Desarrollo
    desarrollo_motriz_json      JSON COMMENT 'array {conducta, edad_meses}',
    desarrollo_lenguaje_json    JSON COMMENT 'array {habilidad, edad_meses}',
    comportamiento_json         JSON COMMENT 'array {conducta, valor: si/no, obs}',
    habilidades_sociales_json   JSON COMMENT 'array {habilidad, valor: si/no, obs}',
    historia_escolar_json       JSON,
    -- Familia
    estado_padres               ENUM('juntos','separados','divorciados','fallecido_padre','fallecida_madre','ambos_fallecidos') DEFAULT 'juntos',
    num_hermanos                INT DEFAULT 0,
    con_quien_vive              VARCHAR(255),
    observaciones               TEXT,
    archivos_adjuntos_json      JSON,
    completado                  TINYINT(1) NOT NULL DEFAULT 0,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_anamnesis_paciente (paciente_id),
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL — PASO 2: EVALUACIONES
-- ============================================================

CREATE TABLE IF NOT EXISTS evaluaciones (
    id                              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id                     INT UNSIGNED NOT NULL,
    clinica_id                      INT UNSIGNED NOT NULL,
    operativo_id                    INT UNSIGNED NOT NULL,
    nombre_test                     VARCHAR(150) NOT NULL,
    fecha_aplicacion                DATE NOT NULL,
    resultados_cuantitativos_json   JSON,
    interpretacion_cualitativa      TEXT,
    archivo_adjunto                 VARCHAR(255),
    created_at                      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL — PASO 3: DIAGNÓSTICOS
-- ============================================================

CREATE TABLE IF NOT EXISTS diagnosticos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id             INT UNSIGNED NOT NULL,
    clinica_id              INT UNSIGNED NOT NULL,
    operativo_id            INT UNSIGNED NOT NULL,
    impresion_diagnostica   TEXT NOT NULL,
    codigo_dsm5             VARCHAR(50),
    codigo_cie10            VARCHAR(20),
    codigo_cie11            VARCHAR(20),
    nivel_severidad         ENUM('leve','moderado','grave') DEFAULT 'leve',
    diagnostico_diferencial TEXT,
    observaciones           TEXT,
    fecha_diagnostico       DATE NOT NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL — PASO 4: PLANES DE INTERVENCIÓN
-- ============================================================

CREATE TABLE IF NOT EXISTS planes_intervencion (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id                 INT UNSIGNED NOT NULL,
    clinica_id                  INT UNSIGNED NOT NULL,
    operativo_id                INT UNSIGNED NOT NULL,
    diagnostico_id              INT UNSIGNED NULL,
    objetivos_generales         TEXT NOT NULL,
    objetivos_especificos_json  JSON,
    enfoque_terapeutico         VARCHAR(150),
    tecnicas_json               JSON,
    frecuencia_sesiones         INT NOT NULL DEFAULT 1 COMMENT 'sesiones por semana',
    duracion_estimada_semanas   INT NOT NULL DEFAULT 12,
    estado                      ENUM('borrador','activo','completado','suspendido') NOT NULL DEFAULT 'borrador',
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)   REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)    REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id)  REFERENCES usuarios(id),
    FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL — PASO 5: SESIONES / NOTAS DE EVOLUCIÓN
-- ============================================================

CREATE TABLE IF NOT EXISTS sesiones (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id             INT UNSIGNED NOT NULL,
    plan_id                 INT UNSIGNED NOT NULL,
    clinica_id              INT UNSIGNED NOT NULL,
    operativo_id            INT UNSIGNED NOT NULL,
    fecha_sesion            DATETIME NOT NULL,
    numero_sesion           INT NOT NULL DEFAULT 1,
    objetivo_sesion         TEXT,
    tecnicas_aplicadas      TEXT,
    respuesta_paciente      TEXT,
    observaciones_clinicas  TEXT,
    tareas_asignadas        TEXT,
    asistio                 ENUM('asistio','falto','cancelada','reprogramada') NOT NULL DEFAULT 'asistio',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (plan_id)      REFERENCES planes_intervencion(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- HISTORIAL — PASO 6: SEGUIMIENTOS
-- ============================================================

CREATE TABLE IF NOT EXISTS seguimientos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id             INT UNSIGNED NOT NULL,
    plan_id                 INT UNSIGNED NOT NULL,
    clinica_id              INT UNSIGNED NOT NULL,
    operativo_id            INT UNSIGNED NOT NULL,
    fecha_evaluacion        DATE NOT NULL,
    escala_avance           TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
    objetivos_cumplidos_json JSON,
    alertas_clinicas_json   JSON,
    observaciones           TEXT,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (plan_id)      REFERENCES planes_intervencion(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CRONOGRAMAS Y TAREAS
-- ============================================================

CREATE TABLE IF NOT EXISTS cronogramas (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id             INT UNSIGNED NOT NULL,
    plan_id                 INT UNSIGNED NOT NULL,
    clinica_id              INT UNSIGNED NOT NULL,
    operativo_id            INT UNSIGNED NOT NULL,
    fecha_inicio            DATE NOT NULL,
    fecha_fin_estimada      DATE NOT NULL,
    total_sesiones          INT NOT NULL DEFAULT 0,
    sesiones_completadas    INT NOT NULL DEFAULT 0,
    estado                  ENUM('borrador_auto','activo','completado','suspendido') NOT NULL DEFAULT 'borrador_auto',
    auto_generado           TINYINT(1) NOT NULL DEFAULT 1,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (plan_id)      REFERENCES planes_intervencion(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tareas_cronograma (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cronograma_id       INT UNSIGNED NOT NULL,
    paciente_id         INT UNSIGNED NOT NULL,
    titulo              VARCHAR(200) NOT NULL,
    descripcion         TEXT,
    tipo                ENUM('sesion','evaluacion','tarea_hogar','seguimiento') NOT NULL DEFAULT 'sesion',
    fecha_programada    DATETIME NOT NULL,
    fecha_completada    DATETIME NULL,
    responsable_id      INT UNSIGNED NULL,
    estado              ENUM('pendiente','en_progreso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    orden               INT NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cronograma_id)    REFERENCES cronogramas(id),
    FOREIGN KEY (paciente_id)      REFERENCES pacientes(id),
    FOREIGN KEY (responsable_id)   REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CITAS / AGENDA
-- ============================================================

CREATE TABLE IF NOT EXISTS citas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id         INT UNSIGNED NOT NULL,
    clinica_id          INT UNSIGNED NOT NULL,
    operativo_id        INT UNSIGNED NOT NULL,
    fecha_hora          DATETIME NOT NULL,
    duracion_minutos    INT NOT NULL DEFAULT 60,
    tipo                ENUM('valoracion','sesion','seguimiento') NOT NULL DEFAULT 'sesion',
    estado              ENUM('programada','confirmada','realizada','cancelada','no_asistio') NOT NULL DEFAULT 'programada',
    notas               TEXT,
    created_by          INT UNSIGNED NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)  REFERENCES pacientes(id),
    FOREIGN KEY (clinica_id)   REFERENCES clinicas(id),
    FOREIGN KEY (operativo_id) REFERENCES usuarios(id),
    FOREIGN KEY (created_by)   REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDITORÍA DE ACCESOS AL HISTORIAL
-- ============================================================

CREATE TABLE IF NOT EXISTS auditoria_accesos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    clinica_id      INT UNSIGNED NOT NULL,
    paciente_id     INT UNSIGNED NULL,
    accion          VARCHAR(100) NOT NULL,
    modulo          VARCHAR(50)  NOT NULL,
    detalle         TEXT,
    ip              VARCHAR(45),
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
    FOREIGN KEY (clinica_id)  REFERENCES clinicas(id),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TOKENS DE RECUPERACIÓN / CSRF
-- ============================================================

CREATE TABLE IF NOT EXISTS tokens_recuperacion (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    token       VARCHAR(255) NOT NULL UNIQUE,
    expira_en   DATETIME NOT NULL,
    usado       TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
