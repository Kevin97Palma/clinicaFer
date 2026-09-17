-- =====================================================================
-- SGC Psicología — Estructura de base de datos
-- MySQL 8.0+ · utf8mb4
-- Importar:  mysql -u USER -p NOMBRE_BD < database/database.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS backup_logs, audit_logs, alerts, consents, patient_files,
  package_movements, session_packages, payments, document_versions, documents,
  document_templates, evaluation_instruments, instrument_catalog, psychological_evaluations,
  progress_reviews, therapy_session_objectives, therapy_sessions, treatment_objectives,
  treatment_areas, treatment_plans, appointments, diagnoses, anamnesis, patient_guardians,
  patients, settings, users, role_permissions, permissions, roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Seguridad: roles, permisos, usuarios
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(40)  NOT NULL UNIQUE,
  name        VARCHAR(80)  NOT NULL,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug   VARCHAR(60)  NOT NULL UNIQUE,
  name   VARCHAR(120) NOT NULL,
  module VARCHAR(40)  NOT NULL,
  INDEX idx_perm_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id               INT UNSIGNED NOT NULL,
  name                  VARCHAR(120) NOT NULL,
  username              VARCHAR(60)  NOT NULL UNIQUE,
  email                 VARCHAR(150) NOT NULL UNIQUE,
  password_hash         VARCHAR(255) NOT NULL,
  professional_title    VARCHAR(120) NULL,
  registration_number   VARCHAR(60)  NULL,
  phone                 VARCHAR(30)  NULL,
  is_active             TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_password  TINYINT(1)   NOT NULL DEFAULT 0,
  failed_attempts       INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until          DATETIME     NULL,
  last_login_at         DATETIME     NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  `key`      VARCHAR(60) PRIMARY KEY,
  value      TEXT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pacientes y representantes
-- ---------------------------------------------------------------------
CREATE TABLE patients (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_number      VARCHAR(20)  NOT NULL UNIQUE,          -- PSI-2026-0001
  first_name       VARCHAR(100) NOT NULL,
  last_name        VARCHAR(100) NOT NULL,
  birth_date       DATE         NOT NULL,                  -- la edad se calcula, no se guarda
  sex              ENUM('femenino','masculino','otro') NOT NULL,
  identification   VARCHAR(30)  NULL,
  phone            VARCHAR(30)  NULL,
  email            VARCHAR(150) NULL,
  address          VARCHAR(255) NULL,
  school           VARCHAR(150) NULL,
  grade            VARCHAR(60)  NULL,
  intake_date      DATE         NOT NULL,
  status           ENUM('activo','pausa','alta','derivado','inactivo') NOT NULL DEFAULT 'activo',
  consultation_reason TEXT      NULL,
  general_notes    TEXT         NULL,
  professional_id  INT UNSIGNED NULL,
  sessions_count   INT UNSIGNED NOT NULL DEFAULT 0,        -- sesiones atendidas
  last_session_at  DATE         NULL,
  created_by       INT UNSIGNED NULL,
  updated_by       INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pat_names (last_name, first_name),
  INDEX idx_pat_first (first_name),
  INDEX idx_pat_ident (identification),
  INDEX idx_pat_status (status),
  CONSTRAINT fk_pat_prof FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_guardians (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id          INT UNSIGNED NOT NULL,
  first_name          VARCHAR(100) NOT NULL,
  last_name           VARCHAR(100) NOT NULL,
  relationship        ENUM('madre','padre','tutor','otro') NOT NULL,
  identification      VARCHAR(30)  NULL,
  phone               VARCHAR(30)  NULL,
  email               VARCHAR(150) NULL,
  is_primary          TINYINT(1) NOT NULL DEFAULT 0,
  authorized_info     TINYINT(1) NOT NULL DEFAULT 1,
  notes               TEXT NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_guard_ident (identification),
  CONSTRAINT fk_guard_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Expediente clínico
-- ---------------------------------------------------------------------
CREATE TABLE anamnesis (
  id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id                INT UNSIGNED NOT NULL UNIQUE,
  family_data               TEXT NULL,
  consultation_reason       TEXT NULL,
  prenatal_history          TEXT NULL,
  perinatal_history         TEXT NULL,
  motor_development         TEXT NULL,
  language_development      TEXT NULL,
  socioemotional_development TEXT NULL,
  medical_history           TEXT NULL,
  medications               TEXT NULL,
  external_professionals    TEXT NULL,
  neurological_history      TEXT NULL,
  psychiatric_history       TEXT NULL,
  school_history            TEXT NULL,
  family_dynamics           TEXT NULL,
  behavior                  TEXT NULL,
  sleep                     TEXT NULL,
  feeding                   TEXT NULL,
  screen_use                TEXT NULL,
  social_relations          TEXT NULL,
  clinical_observations     TEXT NULL,
  status                    ENUM('borrador','completa') NOT NULL DEFAULT 'borrador',
  created_by                INT UNSIGNED NULL,
  updated_by                INT UNSIGNED NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_anam_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE diagnoses (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id            INT UNSIGNED NOT NULL,
  diagnosis             VARCHAR(255) NOT NULL,
  code                  VARCHAR(30)  NULL,
  classification_system ENUM('DSM-5-TR','CIE-10','CIE-11','Otro') NOT NULL,
  type                  ENUM('presuntivo','diferencial','confirmado','descartado') NOT NULL,
  diagnosed_at          DATE NOT NULL,
  status                ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  notes                 TEXT NULL,
  created_by            INT UNSIGNED NULL,
  updated_by            INT UNSIGNED NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_diag_pat (patient_id, status),
  CONSTRAINT fk_diag_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Planes terapéuticos
-- ---------------------------------------------------------------------
CREATE TABLE treatment_plans (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id        INT UNSIGNED NOT NULL,
  start_date        DATE NOT NULL,
  review_date       DATE NULL,
  general_objective TEXT NOT NULL,
  status            ENUM('activo','finalizado','suspendido') NOT NULL DEFAULT 'activo',
  notes             TEXT NULL,
  created_by        INT UNSIGNED NULL,
  updated_by        INT UNSIGNED NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_plan_pat (patient_id, status),
  CONSTRAINT fk_plan_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE treatment_areas (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_id    INT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_area_plan FOREIGN KEY (plan_id) REFERENCES treatment_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE treatment_objectives (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  area_id     INT UNSIGNED NOT NULL,
  plan_id     INT UNSIGNED NOT NULL,
  objective   VARCHAR(255) NOT NULL,
  indicator   VARCHAR(255) NULL,
  status      ENUM('pendiente','en_proceso','logrado') NOT NULL DEFAULT 'pendiente',
  achieved_at DATE NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_obj_plan (plan_id, status),
  CONSTRAINT fk_obj_area FOREIGN KEY (area_id) REFERENCES treatment_areas(id) ON DELETE CASCADE,
  CONSTRAINT fk_obj_plan FOREIGN KEY (plan_id) REFERENCES treatment_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Paquetes de sesiones
-- ---------------------------------------------------------------------
CREATE TABLE session_packages (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id         INT UNSIGNED NOT NULL,
  name               VARCHAR(120) NOT NULL,
  purchased_at       DATE NOT NULL,
  expires_at         DATE NULL,
  sessions_total     INT UNSIGNED NOT NULL,
  sessions_used      INT UNSIGNED NOT NULL DEFAULT 0,
  sessions_remaining INT AS (CAST(sessions_total AS SIGNED) - CAST(sessions_used AS SIGNED)) STORED,
  price_regular      DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount           DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_paid         DECIMAL(10,2) NOT NULL DEFAULT 0,
  status             ENUM('activo','finalizado','vencido','cancelado') NOT NULL DEFAULT 'activo',
  notes              TEXT NULL,
  created_by         INT UNSIGNED NULL,
  updated_by         INT UNSIGNED NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pkg_pat (patient_id, status),
  CONSTRAINT fk_pkg_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Agenda y sesiones
-- ---------------------------------------------------------------------
CREATE TABLE appointments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT UNSIGNED NOT NULL,
  professional_id INT UNSIGNED NOT NULL,
  starts_at       DATETIME NOT NULL,
  ends_at         DATETIME NOT NULL,
  duration_min    SMALLINT UNSIGNED NOT NULL,
  type            ENUM('primera_consulta','sesion','evaluacion','devolucion','seguimiento','padres','otra') NOT NULL DEFAULT 'sesion',
  reason          VARCHAR(255) NULL,
  notes           TEXT NULL,
  status          ENUM('programada','confirmada','atendida','cancelada','no_asistio','reprogramada') NOT NULL DEFAULT 'programada',
  created_by      INT UNSIGNED NULL,
  updated_by      INT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_appt_prof_time (professional_id, starts_at, ends_at),
  INDEX idx_appt_pat (patient_id, starts_at),
  INDEX idx_appt_status (status),
  CONSTRAINT fk_appt_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_prof FOREIGN KEY (professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE therapy_sessions (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id       INT UNSIGNED NOT NULL,
  appointment_id   INT UNSIGNED NULL UNIQUE,
  plan_id          INT UNSIGNED NULL,
  package_id       INT UNSIGNED NULL,                 -- paquete del que se descontó
  professional_id  INT UNSIGNED NOT NULL,
  session_number   INT UNSIGNED NULL,                 -- solo sesiones atendidas
  session_date     DATE NOT NULL,
  session_time     TIME NULL,
  duration_min     SMALLINT UNSIGNED NOT NULL DEFAULT 45,
  modality         ENUM('presencial','virtual','familiar','padres','escolar','otra') NOT NULL DEFAULT 'presencial',
  objective_worked TEXT NULL,
  emotional_state  ENUM('regulado','neutral','ansioso','irritable','triste','otro') NULL,
  intervention     TEXT NULL,
  patient_response TEXT NULL,
  participation    ENUM('alta','media','baja') NULL,
  progress         ENUM('logrado','en_proceso','requiere_intervencion') NULL,
  clinical_notes   TEXT NULL,
  homework         TEXT NULL,
  recommendations  TEXT NULL,
  next_objective   TEXT NULL,
  attendance       ENUM('atendido','cancelado','no_asistio','reprogramado') NOT NULL DEFAULT 'atendido',
  fee              DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_by       INT UNSIGNED NULL,
  updated_by       INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ses_pat_date (patient_id, session_date),
  INDEX idx_ses_date (session_date, attendance),
  CONSTRAINT fk_ses_pat  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_ses_appt FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  CONSTRAINT fk_ses_plan FOREIGN KEY (plan_id) REFERENCES treatment_plans(id) ON DELETE SET NULL,
  CONSTRAINT fk_ses_pkg  FOREIGN KEY (package_id) REFERENCES session_packages(id) ON DELETE SET NULL,
  CONSTRAINT fk_ses_prof FOREIGN KEY (professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE therapy_session_objectives (
  session_id   INT UNSIGNED NOT NULL,
  objective_id INT UNSIGNED NOT NULL,
  status_after ENUM('pendiente','en_proceso','logrado') NULL,
  PRIMARY KEY (session_id, objective_id),
  CONSTRAINT fk_tso_ses FOREIGN KEY (session_id) REFERENCES therapy_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_tso_obj FOREIGN KEY (objective_id) REFERENCES treatment_objectives(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_movements (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NULL,
  type       ENUM('consumo','reversion','ajuste_manual') NOT NULL,
  quantity   INT NOT NULL,                 -- +1 consume, -1 revierte
  reason     VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mov_pkg (package_id),
  CONSTRAINT fk_mov_pkg FOREIGN KEY (package_id) REFERENCES session_packages(id) ON DELETE CASCADE,
  CONSTRAINT fk_mov_ses FOREIGN KEY (session_id) REFERENCES therapy_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE progress_reviews (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id             INT UNSIGNED NOT NULL,
  plan_id                INT UNSIGNED NULL,
  review_date            DATE NOT NULL,
  sessions_at_review     INT UNSIGNED NOT NULL DEFAULT 0,
  objectives_initial     TEXT NULL,
  objectives_achieved    TEXT NULL,
  objectives_in_progress TEXT NULL,
  objectives_no_progress TEXT NULL,
  patient_report         TEXT NULL,
  family_report          TEXT NULL,
  school_report          TEXT NULL,
  clinical_observation   TEXT NULL,
  general_status         ENUM('mejor','sin_cambios','retroceso','info_insuficiente') NOT NULL,
  clinical_decision      ENUM('mantener_plan','modificar_objetivos','cambiar_frecuencia','derivar','preparar_alta','continuar_evaluacion') NOT NULL,
  created_by             INT UNSIGNED NULL,
  updated_by             INT UNSIGNED NULL,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rev_pat (patient_id, review_date),
  CONSTRAINT fk_rev_pat  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_plan FOREIGN KEY (plan_id) REFERENCES treatment_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Archivos (antes que evaluaciones y consentimientos, que los referencian)
-- ---------------------------------------------------------------------
CREATE TABLE patient_files (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id    INT UNSIGNED NOT NULL,
  category      ENUM('informe_externo','evaluacion','documento_escolar','documento_medico','consentimiento','otro') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name   VARCHAR(120) NOT NULL UNIQUE,   -- nombre aleatorio, ruta relativa segura
  mime_type     VARCHAR(100) NOT NULL,
  size_bytes    INT UNSIGNED NOT NULL,
  description   VARCHAR(255) NULL,
  uploaded_by   INT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  INDEX idx_file_pat (patient_id, deleted_at),
  CONSTRAINT fk_file_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Evaluaciones psicológicas
-- ---------------------------------------------------------------------
CREATE TABLE instrument_catalog (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE psychological_evaluations (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id           INT UNSIGNED NOT NULL,
  professional_id      INT UNSIGNED NOT NULL,
  start_date           DATE NOT NULL,
  end_date             DATE NULL,
  reason               TEXT NOT NULL,
  areas                SET('cognitiva','conductual','emocional','neurodesarrollo','personalidad','adaptativa','parental','academica','otra') NOT NULL,
  status               ENUM('en_proceso','finalizada','informe_pendiente','informe_entregado') NOT NULL DEFAULT 'en_proceso',
  clinical_integration TEXT NULL,
  conclusions          TEXT NULL,
  recommendations      TEXT NULL,
  report_delivered_at  DATE NULL,
  created_by           INT UNSIGNED NULL,
  updated_by           INT UNSIGNED NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_eval_pat (patient_id),
  INDEX idx_eval_status (status),
  CONSTRAINT fk_eval_pat  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_eval_prof FOREIGN KEY (professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluation_instruments (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evaluation_id  INT UNSIGNED NOT NULL,
  instrument_id  INT UNSIGNED NOT NULL,
  applied_at     DATE NOT NULL,
  scores         TEXT NULL,
  interpretation TEXT NULL,       -- redactada y validada por la profesional
  notes          TEXT NULL,
  file_id        INT UNSIGNED NULL,
  created_by     INT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ei_eval FOREIGN KEY (evaluation_id) REFERENCES psychological_evaluations(id) ON DELETE CASCADE,
  CONSTRAINT fk_ei_inst FOREIGN KEY (instrument_id) REFERENCES instrument_catalog(id),
  CONSTRAINT fk_ei_file FOREIGN KEY (file_id) REFERENCES patient_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Documentos clínicos
-- ---------------------------------------------------------------------
CREATE TABLE document_templates (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  type       ENUM('informe_psicologico','informe_evaluacion','informe_seguimiento','certificado_asistencia','certificado_psicologico','informe_institucion','derivacion','plan_intervencion','desglose_sesiones','personalizado') NOT NULL,
  content    LONGTEXT NOT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tpl_type (type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id      INT UNSIGNED NOT NULL,
  template_id     INT UNSIGNED NULL,
  evaluation_id   INT UNSIGNED NULL,
  type            ENUM('informe_psicologico','informe_evaluacion','informe_seguimiento','certificado_asistencia','certificado_psicologico','informe_institucion','derivacion','plan_intervencion','desglose_sesiones','personalizado') NOT NULL,
  title           VARCHAR(200) NOT NULL,
  status          ENUM('borrador','emitido','anulado') NOT NULL DEFAULT 'borrador',
  current_version INT UNSIGNED NOT NULL DEFAULT 1,
  issued_at       DATETIME NULL,
  created_by      INT UNSIGNED NULL,
  updated_by      INT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_doc_pat (patient_id),
  INDEX idx_doc_status (status),
  CONSTRAINT fk_doc_pat  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_tpl  FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE SET NULL,
  CONSTRAINT fk_doc_eval FOREIGN KEY (evaluation_id) REFERENCES psychological_evaluations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada versión guarda el contenido ya resuelto (instantánea inmutable)
CREATE TABLE document_versions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_id INT UNSIGNED NOT NULL,
  version     INT UNSIGNED NOT NULL,
  content     LONGTEXT NOT NULL,
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_doc_version (document_id, version),
  CONSTRAINT fk_dv_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pagos
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id  INT UNSIGNED NOT NULL,
  package_id  INT UNSIGNED NULL,
  session_id  INT UNSIGNED NULL,
  paid_at     DATE NOT NULL,
  concept     VARCHAR(200) NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  method      ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
  status      ENUM('pagado','pendiente','anulado') NOT NULL DEFAULT 'pagado',
  reference   VARCHAR(100) NULL,
  notes       TEXT NULL,
  void_reason VARCHAR(255) NULL,
  voided_by   INT UNSIGNED NULL,
  voided_at   DATETIME NULL,
  created_by  INT UNSIGNED NULL,
  updated_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pay_pat (patient_id, paid_at),
  INDEX idx_pay_status (status, paid_at),
  CONSTRAINT fk_pay_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_pkg FOREIGN KEY (package_id) REFERENCES session_packages(id) ON DELETE SET NULL,
  CONSTRAINT fk_pay_ses FOREIGN KEY (session_id) REFERENCES therapy_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Consentimientos
-- ---------------------------------------------------------------------
CREATE TABLE consents (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id   INT UNSIGNED NOT NULL,
  guardian_id  INT UNSIGNED NULL,
  type         ENUM('consentimiento_informado','autorizacion_evaluacion','autorizacion_institucion','autorizacion_intercambio','otro') NOT NULL,
  consent_date DATE NOT NULL,
  status       ENUM('vigente','pendiente','revocado') NOT NULL DEFAULT 'vigente',
  file_id      INT UNSIGNED NULL,
  notes        TEXT NULL,
  created_by   INT UNSIGNED NULL,
  updated_by   INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cons_pat (patient_id),
  CONSTRAINT fk_cons_pat   FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_cons_guard FOREIGN KEY (guardian_id) REFERENCES patient_guardians(id) ON DELETE SET NULL,
  CONSTRAINT fk_cons_file  FOREIGN KEY (file_id) REFERENCES patient_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Alertas, auditoría y respaldos
-- ---------------------------------------------------------------------
CREATE TABLE alerts (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NULL,          -- NULL = visible para todo el equipo con permiso
  patient_id     INT UNSIGNED NULL,
  type           ENUM('INACTIVIDAD_PACIENTE','PAQUETE_POR_FINALIZAR','EVALUACION_PENDIENTE','INFORME_PENDIENTE','REVISION_TERAPEUTICA','PAGO_PENDIENTE','OTRA') NOT NULL,
  message        VARCHAR(255) NOT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_date       DATE NULL,
  status         ENUM('pendiente','leida','resuelta','descartada') NOT NULL DEFAULT 'pendiente',
  reference_type VARCHAR(40) NULL,
  reference_id   INT UNSIGNED NULL,
  resolved_at    DATETIME NULL,
  INDEX idx_alert_status (status, created_at),
  INDEX idx_alert_ref (type, reference_type, reference_id, status),
  INDEX idx_alert_pat (patient_id),
  CONSTRAINT fk_alert_pat FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NULL,
  action         ENUM('LOGIN','LOGIN_FALLIDO','LOGOUT','CREAR','EDITAR','ANULAR','ELIMINAR','CAMBIAR_ESTADO','GENERAR_DOCUMENTO','DESCARGAR_DOCUMENTO','VER_EXPEDIENTE_CLINICO','EXPORTAR','RESPALDO','AJUSTE_PAQUETE') NOT NULL,
  module         VARCHAR(40) NOT NULL,
  record_id      INT UNSIGNED NULL,
  description    VARCHAR(255) NULL,
  changed_fields VARCHAR(1000) NULL,        -- solo nombres de campos, nunca el contenido clínico
  ip_address     VARCHAR(45) NULL,
  user_agent     VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id, created_at),
  INDEX idx_audit_module (module, record_id),
  INDEX idx_audit_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE backup_logs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  type       ENUM('manual','automatico') NOT NULL DEFAULT 'manual',
  file_name  VARCHAR(150) NULL,
  size_bytes BIGINT UNSIGNED NULL,
  result     ENUM('exito','error') NOT NULL,
  message    VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Datos base obligatorios (roles, permisos, configuración, catálogo)
-- ---------------------------------------------------------------------
INSERT INTO roles (id, slug, name, description) VALUES
 (1,'administrador','Administrador','Acceso completo al sistema'),
 (2,'psicologo','Psicólogo','Información clínica y terapéutica'),
 (3,'asistente','Asistente','Agenda, pacientes y pagos, sin notas clínicas');

INSERT INTO permissions (slug, name, module) VALUES
 ('dashboard.view','Ver dashboard','dashboard'),
 ('agenda.view','Ver agenda','agenda'),
 ('agenda.manage','Crear y modificar citas','agenda'),
 ('patients.view','Ver pacientes (datos administrativos)','pacientes'),
 ('patients.manage','Crear y editar pacientes y representantes','pacientes'),
 ('clinical.view','Ver información clínica confidencial','clinico'),
 ('clinical.manage','Registrar anamnesis, diagnósticos, sesiones, planes y revisiones','clinico'),
 ('evaluations.view','Ver evaluaciones psicológicas','evaluaciones'),
 ('evaluations.manage','Gestionar evaluaciones e instrumentos','evaluaciones'),
 ('documents.view','Ver documentos clínicos','documentos'),
 ('documents.manage','Generar y editar documentos','documentos'),
 ('templates.manage','Gestionar plantillas de documentos','documentos'),
 ('payments.view','Ver pagos y paquetes','pagos'),
 ('payments.manage','Registrar pagos y paquetes','pagos'),
 ('payments.void','Anular pagos','pagos'),
 ('packages.adjust','Corregir consumo de paquetes','pagos'),
 ('files.view','Ver y descargar archivos del expediente','archivos'),
 ('files.manage','Subir archivos y consentimientos','archivos'),
 ('alerts.view','Ver y gestionar alertas','alertas'),
 ('reports.view','Ver reportes','reportes'),
 ('audit.view','Ver auditoría','configuracion'),
 ('backups.manage','Realizar respaldos','configuracion'),
 ('settings.manage','Configuración general, roles y catálogos','configuracion'),
 ('users.manage','Gestionar usuarios','configuracion');

-- Administrador: todo
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions;
-- Psicólogo: todo excepto administración del sistema
INSERT INTO role_permissions (role_id, permission_id)
  SELECT 2, id FROM permissions
  WHERE slug NOT IN ('audit.view','backups.manage','settings.manage','users.manage','packages.adjust');
-- Asistente: agenda, pacientes, pagos (sin información clínica)
INSERT INTO role_permissions (role_id, permission_id)
  SELECT 3, id FROM permissions
  WHERE slug IN ('dashboard.view','agenda.view','agenda.manage','patients.view','patients.manage',
                 'payments.view','payments.manage','alerts.view','files.manage','reports.view');

INSERT INTO settings (`key`, value) VALUES
 ('professional_name',''),
 ('professional_title','Psicóloga Clínica'),
 ('registration_number',''),
 ('phone',''),
 ('email',''),
 ('address',''),
 ('logo_file',''),
 ('signature_file',''),
 ('brand_name','Consulta Psicológica'),
 ('session_fee','25.00'),
 ('session_duration','45'),
 ('review_every_sessions','8'),
 ('inactivity_days','30'),
 ('currency','USD'),
 ('timezone','America/Guayaquil'),
 ('consume_package_on_no_show','0'),
 ('consume_package_on_cancel','0'),
 ('file_number_prefix','PSI'),
 ('alerts_last_run','');

INSERT INTO instrument_catalog (name, description) VALUES
 ('WISC-V','Escala de inteligencia de Wechsler para niños'),
 ('TONI-2','Test de inteligencia no verbal'),
 ('CARAS-R','Test de percepción de diferencias'),
 ('TDAH-5','Escala de valoración de síntomas'),
 ('E2P','Escala de estilos parentales'),
 ('Test de la Familia','Técnica proyectiva gráfica'),
 ('Vineland-3','Conducta adaptativa'),
 ('Battelle','Inventario de desarrollo'),
 ('CDI','Inventario de depresión infantil'),
 ('CPQ','Cuestionario de personalidad para niños'),
 ('MACI','Inventario clínico para adolescentes'),
 ('PHQ-9','Cuestionario de salud del paciente'),
 ('Zung','Escala de autoevaluación'),
 ('Otro','Instrumento no catalogado');
