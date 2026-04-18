-- ============================================================
-- SGC — Seed Data (datos falsos para desarrollo)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- CLÍNICAS
-- ============================================================
INSERT INTO clinicas (id, nombre, ruc, direccion, telefono, email, activo) VALUES
(1, 'CATPI S.A.S.',            '2300123456001', 'Av. Quito 345 y Latacunga, Santo Domingo', '0999123456', 'info@catpi-sas.com', 1),
(2, 'Centro Psicológico Mente Sana', '1756789012001', 'Calle Flores 12, Quito',               '0987654321', 'info@mentesana.ec', 1);

-- ============================================================
-- USUARIOS
-- password = "Admin1234!" → bcrypt hash
-- ============================================================
INSERT INTO usuarios (id, cedula, nombre, apellido, email, password_hash, telefono, primer_login, activo) VALUES
-- superadmin
(1, '1234567890', 'Kevin',    'Palma',      'kevin@socket-studio.ec',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0991000001', 0, 1),
-- gerente CATPI
(2, '1712345678', 'Ana',      'Torres',     'ana.torres@catpi-sas.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0992000002', 0, 1),
-- supervisor CATPI
(3, '1756781234', 'Carlos',   'Vásquez',   'carlos.vasquez@catpi-sas.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0993000003', 0, 1),
-- operativos CATPI
(4, '1701234567', 'María',    'Gutiérrez', 'maria.gutierrez@catpi-sas.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0994000004', 0, 1),
(5, '1798765432', 'Luis',     'Medina',    'luis.medina@catpi-sas.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0995000005', 0, 1),
-- representante (padre de paciente)
(6, '0923456789', 'Roberto',  'Alvarado',  'roberto.alvarado@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0996000006', 1, 1),
(7, '0934567890', 'Patricia', 'Molina',    'patricia.molina@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0997000007', 1, 1),
-- gerente clínica 2
(8, '1766543210', 'Sofía',    'Ramos',     'sofia.ramos@mentesana.ec', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','0998000008', 0, 1);

-- Nota: password = 'password' (hash de Laravel/PHP por defecto para pruebas)
-- Hash real de "Admin1234!": usar script de setup para regenerar

-- ============================================================
-- USUARIO ↔ CLÍNICA
-- ============================================================
INSERT INTO usuario_clinica (usuario_id, clinica_id, rol) VALUES
(1, 1, 'superadmin'),
(1, 2, 'superadmin'),
(2, 1, 'gerente'),
(3, 1, 'supervisor'),
(4, 1, 'operativo'),
(5, 1, 'operativo'),
(6, 1, 'representante'),
(7, 1, 'representante'),
(8, 2, 'gerente');

-- ============================================================
-- PACIENTES
-- ============================================================
INSERT INTO pacientes (
    id, clinica_id, codigo_paciente, nombre, apellido, fecha_nacimiento, sexo, cedula,
    representante_nombre, representante_cedula, representante_telefono, representante_email, representante_parentesco,
    motivo_consulta, fecha_ingreso, operativo_asignado_id, estado, estado_flujo,
    usuario_representante_id, created_by
) VALUES
(1, 1, 'CATPI-2025-001', 'Sebastián',  'Alvarado', '2018-03-15', 'M', NULL,
    'Roberto Alvarado', '0923456789', '0996000006', 'roberto.alvarado@gmail.com', 'padre',
    'Dificultades de atención y conducta impulsiva en el aula escolar.', '2025-01-10',
    4, 'activo', 'sesiones', 6, 4),
(2, 1, 'CATPI-2025-002', 'Valentina', 'Molina',   '2017-07-22', 'F', NULL,
    'Patricia Molina', '0934567890', '0997000007', 'patricia.molina@gmail.com', 'madre',
    'Ansiedad de separación, llanto persistente al inicio del día escolar.', '2025-02-03',
    4, 'activo', 'plan', 7, 4),
(3, 1, 'CATPI-2025-003', 'Mateo',     'Cevallos', '2016-11-08', 'M', NULL,
    'Jorge Cevallos', '0945678901', '0993123456', 'jorge.cevallos@hotmail.com', 'padre',
    'Retraso en el desarrollo del lenguaje, vocabulario reducido para su edad.', '2025-02-20',
    5, 'activo', 'diagnostico', NULL, 5),
(4, 1, 'CATPI-2025-004', 'Lucía',     'Herrera',  '2019-05-30', 'F', NULL,
    'Carmen Herrera', '0956789012', '0994234567', 'carmen.herrera@gmail.com', 'madre',
    'Rabietas frecuentes, dificultad para controlar emociones.', '2025-03-01',
    5, 'en_espera', 'anamnesis', NULL, 3),
(5, 1, 'CATPI-2025-005', 'Emilio',    'Sandoval', '2015-09-14', 'M', NULL,
    'Sandra Sandoval', '0967890123', '0995345678', 'sandra.sandoval@gmail.com', 'madre',
    'Posible trastorno del espectro autista, dificultades de socialización.', '2025-03-15',
    4, 'activo', 'evaluacion', NULL, 4),
(6, 1, 'CATPI-2025-006', 'Isabella',  'Ríos',    '2020-01-25', 'F', NULL,
    'Manuel Ríos', '0978901234', '0996456789', 'manuel.rios@gmail.com', 'padre',
    'Enuresis nocturna persistente, 6 años.', '2025-04-01',
    5, 'registrado', 'registrado', NULL, 3);

-- ============================================================
-- ANAMNESIS (pacientes 1 y 2 completas, 4 parcial)
-- ============================================================
INSERT INTO anamnesis (
    paciente_id, clinica_id, operativo_id, fecha_anamnesis,
    antec_linea_materna, antec_linea_paterna,
    retardo_mental, trastornos_psiquiatricos, epilepsia, prob_aprendizaje,
    embarazos, deseado, abortos, tiempo_gestacion, tipo_parto, sufrimiento_fetal,
    estado_padres, num_hermanos, con_quien_vive, observaciones,
    comportamiento_json, habilidades_sociales_json,
    desarrollo_motriz_json, desarrollo_lenguaje_json,
    historia_escolar_json, completado
) VALUES
(1, 1, 4, '2025-01-15',
    'Abuela materna con depresión tratada.', 'Sin antecedentes conocidos.',
    0, 1, 0, 1,
    2, 1, 0, 39, 'cesarea', 0,
    'juntos', 1, 'padre, madre y hermano',
    'Paciente inquieto durante la entrevista, dificultad para permanecer sentado.',
    '[{"conducta":"Agresividad física","valor":"si","obs":"Con compañeros de escuela"},{"conducta":"Hiperactividad","valor":"si","obs":"Permanente"},{"conducta":"Atención sostenida","valor":"no","obs":"Máximo 5 minutos"}]',
    '[{"habilidad":"Saluda espontáneamente","valor":"si","obs":""},{"habilidad":"Comparte juguetes","valor":"no","obs":"Se niega con frecuencia"},{"habilidad":"Espera su turno","valor":"no","obs":"Interrumpe constantemente"}]',
    '[{"conducta":"Sostiene cabeza","edad_meses":3},{"conducta":"Se sienta solo","edad_meses":7},{"conducta":"Camina solo","edad_meses":13}]',
    '[{"habilidad":"Primeras palabras","edad_meses":14},{"habilidad":"Frases de 2 palabras","edad_meses":22},{"habilidad":"Oraciones completas","edad_meses":36}]',
    '{"inicio_escolar":4,"institucion":"Escuela Fiscal Amazonas","repitencia":false,"desempeno":"regular","disciplina":"bajo"}',
    1),
(2, 1, 4, '2025-02-10',
    'Sin antecedentes.', 'Tío paterno con ansiedad generalizada.',
    0, 1, 0, 0,
    1, 1, 0, 38, 'normal', 0,
    'separados', 0, 'solo con madre',
    'Separación de los padres hace 1 año coincide con inicio de síntomas.',
    '[{"conducta":"Ansiedad","valor":"si","obs":"Especialmente al separarse de la madre"},{"conducta":"Llanto fácil","valor":"si","obs":""},{"conducta":"Miedos","valor":"si","obs":"Oscuridad, quedarse sola"}]',
    '[{"habilidad":"Interactúa con pares","valor":"si","obs":"Con dificultad"},{"habilidad":"Expresa sentimientos","valor":"si","obs":"Llora, no verbaliza"},{"habilidad":"Tolera frustraciones","valor":"no","obs":""}]',
    '[{"conducta":"Sostiene cabeza","edad_meses":3},{"conducta":"Se sienta solo","edad_meses":6},{"conducta":"Camina solo","edad_meses":12}]',
    '[{"habilidad":"Primeras palabras","edad_meses":11},{"habilidad":"Frases de 2 palabras","edad_meses":18},{"habilidad":"Oraciones completas","edad_meses":30}]',
    '{"inicio_escolar":3,"institucion":"Jardín de Infantes La Esperanza","repitencia":false,"desempeno":"bueno","disciplina":"bueno"}',
    1),
(4, 1, 5, '2025-03-05',
    NULL, NULL, 0, 0, 0, 0,
    1, 1, 0, 40, 'normal', 0,
    'juntos', 2, 'familia completa', NULL,
    NULL, NULL, NULL, NULL, NULL, 0);

-- ============================================================
-- EVALUACIONES
-- ============================================================
INSERT INTO evaluaciones (paciente_id, clinica_id, operativo_id, nombre_test, fecha_aplicacion, resultados_cuantitativos_json, interpretacion_cualitativa) VALUES
(1, 1, 4, 'Test de Conners (padres)', '2025-01-22',
    '{"indice_hiperactividad": 78, "indice_inatención": 82, "indice_impulsividad": 71, "indice_global": 77}',
    'Puntuaciones en percentil 95+ para hiperactividad e inatención. Perfil consistente con TDAH tipo combinado.'),
(1, 1, 4, 'WISC-V (Escala de Inteligencia Wechsler)', '2025-01-29',
    '{"CIT": 92, "ICV": 98, "IVP": 85, "IMT": 79, "IRP": 94}',
    'Capacidad intelectual en rango Promedio. Se observa perfil heterogéneo con debilidad en velocidad de procesamiento y memoria de trabajo.'),
(5, 1, 4, 'ADOS-2 (Autism Diagnostic Observation)', '2025-03-25',
    '{"comunicacion": 6, "interaccion_social": 8, "juego": 3, "total": 14}',
    'Puntuación total superior al punto de corte para TEA. Se recomienda evaluación complementaria con ADI-R.');

-- ============================================================
-- DIAGNÓSTICOS
-- ============================================================
INSERT INTO diagnosticos (paciente_id, clinica_id, operativo_id, impresion_diagnostica, codigo_dsm5, codigo_cie10, codigo_cie11, nivel_severidad, fecha_diagnostico) VALUES
(1, 1, 4,
    'Trastorno por Déficit de Atención con Hiperactividad, presentación combinada.',
    'F90.2', 'F90.0', '6A05.2', 'moderado', '2025-02-05'),
(3, 1, 5,
    'Trastorno del Lenguaje con predominio expresivo.',
    'F80.1', 'F80.1', '6A01.1', 'leve', '2025-03-10');

-- ============================================================
-- PLANES DE INTERVENCIÓN
-- ============================================================
INSERT INTO planes_intervencion (
    paciente_id, clinica_id, operativo_id, diagnostico_id,
    objetivos_generales, objetivos_especificos_json,
    enfoque_terapeutico, tecnicas_json,
    frecuencia_sesiones, duracion_estimada_semanas, estado
) VALUES
(1, 1, 4, 1,
    'Reducir los síntomas de inatención e hiperactividad que interfieren con el funcionamiento escolar y familiar.',
    '[{"objetivo":"Mejorar la atención sostenida a 15 minutos en tareas estructuradas"},{"objetivo":"Disminuir conductas impulsivas en un 50%"},{"objetivo":"Desarrollar estrategias de autorregulación emocional"}]',
    'Cognitivo-Conductual + Mindfulness adaptado',
    '[{"tecnica":"Economía de fichas"},{"tecnica":"Tiempo fuera"},{"tecnica":"Respiración consciente"},{"tecnica":"Atención plena para niños"},{"tecnica":"Resolución de problemas"}]',
    2, 16, 'activo'),
(2, 1, 4, NULL,
    'Reducir la ansiedad de separación y fortalecer la autonomía de la niña.',
    '[{"objetivo":"Tolerar la separación de la figura materna por períodos progresivos"},{"objetivo":"Identificar y expresar emociones verbalmente"},{"objetivo":"Fortalecer la seguridad en entornos escolares"}]',
    'Terapia de Juego + Cognitivo-Conductual',
    '[{"tecnica":"Desensibilización sistemática"},{"tecnica":"Terapia de juego no directiva"},{"tecnica":"Psicoeducación para padres"},{"tecnica":"Técnicas de relajación infantil"}]',
    1, 12, 'borrador');

-- ============================================================
-- CRONOGRAMAS
-- ============================================================
INSERT INTO cronogramas (paciente_id, plan_id, clinica_id, operativo_id, fecha_inicio, fecha_fin_estimada, total_sesiones, sesiones_completadas, estado) VALUES
(1, 1, 1, 4, '2025-02-10', '2025-06-02', 32, 14, 'activo');

-- ============================================================
-- TAREAS DEL CRONOGRAMA (primeras 8 sesiones del paciente 1)
-- ============================================================
INSERT INTO tareas_cronograma (cronograma_id, paciente_id, titulo, tipo, fecha_programada, fecha_completada, responsable_id, estado, orden) VALUES
(1, 1, 'Sesión 1 — Evaluación inicial y rapport',         'sesion', '2025-02-10 09:00:00', '2025-02-10 10:00:00', 4, 'completada', 1),
(1, 1, 'Sesión 2 — Psicoeducación TDAH (niño)',            'sesion', '2025-02-13 09:00:00', '2025-02-13 10:00:00', 4, 'completada', 2),
(1, 1, 'Sesión 3 — Economía de fichas: diseño',            'sesion', '2025-02-17 09:00:00', '2025-02-17 10:00:00', 4, 'completada', 3),
(1, 1, 'Sesión 4 — Técnica de respiración',                'sesion', '2025-02-20 09:00:00', '2025-02-20 10:00:00', 4, 'completada', 4),
(1, 1, 'Sesión 5 — Resolución de conflictos',              'sesion', '2025-02-24 09:00:00', '2025-02-24 10:00:00', 4, 'completada', 5),
(1, 1, 'Sesión 6 — Mindfulness: atención al momento',      'sesion', '2025-02-27 09:00:00', '2025-02-27 10:00:00', 4, 'completada', 6),
(1, 1, 'Sesión 7 — Autocontrol e impulsividad',            'sesion', '2025-03-03 09:00:00', '2025-03-03 10:00:00', 4, 'completada', 7),
(1, 1, 'Sesión 8 — Revisión con padres',                   'sesion', '2025-03-06 09:00:00', '2025-03-06 10:00:00', 4, 'completada', 8),
(1, 1, 'Sesión 9 — Habilidades sociales',                  'sesion', '2025-03-10 09:00:00', NULL,                   4, 'completada', 9),
(1, 1, 'Sesión 10 — Regulación emocional avanzada',        'sesion', '2025-03-13 09:00:00', NULL,                   4, 'completada', 10),
(1, 1, 'Seguimiento intermedio (6 semanas)',               'seguimiento', '2025-03-20 09:00:00', NULL,               4, 'completada', 11),
(1, 1, 'Sesión 11 — Técnicas de estudio',                  'sesion', '2025-03-24 09:00:00', NULL,                   4, 'completada', 12),
(1, 1, 'Sesión 12 — Taller con docentes',                  'sesion', '2025-03-27 09:00:00', NULL,                   4, 'completada', 13),
(1, 1, 'Sesión 13 — Consolidación de logros',              'sesion', '2025-03-31 09:00:00', NULL,                   4, 'completada', 14),
(1, 1, 'Sesión 14 — Planificación autonomía',              'sesion', '2025-04-03 09:00:00', NULL,                   4, 'pendiente',  15),
(1, 1, 'Sesión 15 — Trabajo con pares',                    'sesion', '2025-04-07 09:00:00', NULL,                   4, 'pendiente',  16),
(1, 1, 'Sesión 16 — Autoestima y confianza',               'sesion', '2025-04-10 09:00:00', NULL,                   4, 'pendiente',  17);

-- ============================================================
-- SESIONES (notas clínicas de las primeras sesiones del paciente 1)
-- ============================================================
INSERT INTO sesiones (paciente_id, plan_id, clinica_id, operativo_id, fecha_sesion, numero_sesion, objetivo_sesion, tecnicas_aplicadas, respuesta_paciente, observaciones_clinicas, asistio) VALUES
(1, 1, 1, 4, '2025-02-10 09:00:00', 1,
    'Establecer rapport y explicar el proceso terapéutico.',
    'Entrevista lúdica, juego libre.',
    'Paciente se mostró curioso y participativo. Dificultad para mantener atención en explicaciones.',
    'Nivel de energía muy alto. Se establece alianza terapéutica básica.', 'asistio'),
(1, 1, 1, 4, '2025-02-13 09:00:00', 2,
    'Psicoeducación sobre el TDAH adaptada al niño.',
    'Cuento terapéutico "El niño cohete", preguntas guiadas.',
    'Se identificó con el personaje del cuento. Preguntó "¿por qué mi cerebro va tan rápido?".',
    'Excelente conexión con el material. Madre reporta que habló del cuento en casa.', 'asistio'),
(1, 1, 1, 4, '2025-02-17 09:00:00', 3,
    'Diseñar e implementar sistema de economía de fichas.',
    'Economía de fichas, contrato conductual.',
    'Eligió stickers de cohetes como reforzadores. Entusiasta al diseñar el tablero.',
    'Se establecen 3 conductas objetivo: sentarse, escuchar, no interrumpir.', 'asistio'),
(1, 1, 1, 4, '2025-02-20 09:00:00', 4,
    'Aprender tecnica de respiracion diafragmatica.',
    'Respiracion de globo, visualizacion guiada.',
    'Dificultad inicial, logra 3 ciclos completos al final de la sesion.',
    'Madre entrenada en la tecnica para refuerzo en casa.', 'asistio');

-- ============================================================
-- SEGUIMIENTOS
-- ============================================================
INSERT INTO seguimientos (paciente_id, plan_id, clinica_id, operativo_id, fecha_evaluacion, escala_avance, objetivos_cumplidos_json, alertas_clinicas_json, observaciones) VALUES
(1, 1, 1, 4, '2025-03-20', 45,
    '[{"objetivo":"Atención sostenida 15min","cumplido":false,"avance":40},{"objetivo":"Reducir impulsividad 50%","cumplido":false,"avance":35},{"objetivo":"Estrategias autorregulación","cumplido":false,"avance":60}]',
    '[]',
    'Avance significativo en autorregulación. Docentes reportan mejora en el aula. Continuar con frecuencia actual.');

-- ============================================================
-- CITAS
-- ============================================================
INSERT INTO citas (paciente_id, clinica_id, operativo_id, fecha_hora, duracion_minutos, tipo, estado, notas, created_by) VALUES
(1, 1, 4, '2025-04-07 09:00:00', 60, 'sesion',      'programada', 'Sesión 15 — Trabajo con pares', 4),
(1, 1, 4, '2025-04-10 09:00:00', 60, 'sesion',      'programada', 'Sesión 16 — Autoestima', 4),
(2, 1, 4, '2025-04-08 10:00:00', 60, 'valoracion',  'programada', 'Primera sesión de valoración', 4),
(3, 1, 5, '2025-04-09 11:00:00', 45, 'sesion',      'programada', NULL, 5),
(5, 1, 4, '2025-04-11 09:00:00', 60, 'seguimiento', 'programada', 'Seguimiento post-evaluación ADOS-2', 4),
(1, 1, 4, '2025-03-31 09:00:00', 60, 'sesion',      'realizada',  'Sesión 13', 4),
(1, 1, 4, '2025-04-03 09:00:00', 60, 'sesion',      'realizada',  'Sesión 14', 4);

-- ============================================================
-- AUDITORÍA (muestra)
-- ============================================================
INSERT INTO auditoria_accesos (usuario_id, clinica_id, paciente_id, accion, modulo, ip) VALUES
(4, 1, 1, 'VER_HISTORIAL',   'historial', '192.168.1.10'),
(4, 1, 1, 'EDITAR_ANAMNESIS','anamnesis',  '192.168.1.10'),
(3, 1, 1, 'VER_HISTORIAL',   'historial', '192.168.1.15'),
(2, 1, NULL,'VER_DASHBOARD', 'dashboard', '192.168.1.5');

SET FOREIGN_KEY_CHECKS = 1;
