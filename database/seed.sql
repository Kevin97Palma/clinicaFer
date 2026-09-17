-- =====================================================================
-- SGC Psicología — Datos de prueba FICTICIOS
-- Ningún dato corresponde a personas reales.
-- Importar después de database.sql:
--   mysql -u USER -p NOMBRE_BD < database/seed.sql
-- Las contraseñas se asignan aparte:  php database/crear-usuario.php ...
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Usuarios (sin contraseña utilizable: el hash '*' nunca valida)
-- ---------------------------------------------------------------------
INSERT INTO users (id, role_id, name, username, email, password_hash, professional_title, registration_number, phone, is_active, must_change_password) VALUES
 (1, 1, 'Kevin Palma', 'kevin', 'kapalmaq@socket-studio.com', '*', 'Administrador del sistema', NULL, NULL, 1, 0),
 (2, 2, 'Daniela Andrade', 'psicologa', 'daniela.andrade@ejemplo.com', '*', 'Psicóloga Clínica', 'PSC-2026-1184', '0998765432', 1, 0),
 (3, 3, 'Paula Mera', 'asistente', 'paula.mera@ejemplo.com', '*', 'Asistente administrativa', NULL, '0987654321', 1, 0);

UPDATE settings SET value = 'Consulta Psicológica Andrade' WHERE `key` = 'brand_name';
UPDATE settings SET value = 'Psic. Daniela Andrade' WHERE `key` = 'professional_name';
UPDATE settings SET value = 'Psicóloga Clínica Infantil' WHERE `key` = 'professional_title';
UPDATE settings SET value = 'PSC-2026-1184' WHERE `key` = 'registration_number';
UPDATE settings SET value = '0998765432' WHERE `key` = 'phone';
UPDATE settings SET value = 'contacto@ejemplo.com' WHERE `key` = 'email';
UPDATE settings SET value = 'Av. Quito 123 y Los Cedros, Santo Domingo' WHERE `key` = 'address';
UPDATE settings SET value = '25.00' WHERE `key` = 'session_fee';

-- ---------------------------------------------------------------------
-- Pacientes
-- ---------------------------------------------------------------------
INSERT INTO patients (id, file_number, first_name, last_name, birth_date, sex, identification, phone, email, address, school, grade, intake_date, status, consultation_reason, professional_id, created_by) VALUES
 (1, CONCAT('PSI-', YEAR(CURDATE()), '-0001'), 'Mateo', 'Salgado Ríos', '2016-04-12', 'masculino', '1723456701', '0991111001', NULL, 'Ciudadela Los Rosales, calle 4', 'Unidad Educativa Los Andes', '3.º EGB', DATE_SUB(CURDATE(), INTERVAL 8 MONTH), 'activo', 'Dificultades atencionales y bajo rendimiento escolar reportadas por la institución educativa.', 2, 1),
 (2, CONCAT('PSI-', YEAR(CURDATE()), '-0002'), 'Valentina', 'Cordero Peña', '2014-09-03', 'femenino', '1723456702', '0991111002', 'familia.cordero@ejemplo.com', 'Av. Central 455', 'Colegio San Marcos', '6.º EGB', DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 'activo', 'Ansiedad ante evaluaciones escolares, malestar físico antes de exámenes.', 2, 1),
 (3, CONCAT('PSI-', YEAR(CURDATE()), '-0003'), 'Emilio', 'Tapia Núñez', '2018-01-22', 'masculino', NULL, '0991111003', NULL, 'Barrio El Mirador', 'Centro Infantil Semillitas', 'Inicial 2', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'activo', 'Retraso en lenguaje expresivo referido por pediatra.', 2, 1),
 (4, CONCAT('PSI-', YEAR(CURDATE()), '-0004'), 'Isabella', 'Moreno Cruz', '2012-11-30', 'femenino', '1723456704', '0991111004', NULL, 'Urbanización Las Palmas', 'Colegio San Marcos', '8.º EGB', DATE_SUB(CURDATE(), INTERVAL 11 MONTH), 'pausa', 'Dificultades de regulación emocional y conflictos con pares.', 2, 1),
 (5, CONCAT('PSI-', YEAR(CURDATE()), '-0005'), 'Tomás', 'Aguirre Vélez', '2015-06-18', 'masculino', '1723456705', '0991111005', NULL, 'Calle Bolívar 890', 'Unidad Educativa Los Andes', '4.º EGB', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 'activo', 'Conductas oposicionistas en casa y en el aula.', 2, 1),
 (6, CONCAT('PSI-', YEAR(CURDATE()), '-0006'), 'Renata', 'Villacís Mora', '2017-02-08', 'femenino', NULL, '0991111006', NULL, 'Conjunto Los Álamos', 'Centro Infantil Semillitas', '1.º EGB', DATE_SUB(CURDATE(), INTERVAL 14 MONTH), 'alta', 'Dificultades de adaptación escolar y ansiedad de separación.', 2, 1),
 (7, CONCAT('PSI-', YEAR(CURDATE()), '-0007'), 'Joaquín', 'Benítez Lara', '2013-07-25', 'masculino', '1723456707', '0991111007', NULL, 'Av. Los Cedros 77', 'Colegio Nueva Aurora', '7.º EGB', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'activo', 'Solicitud de evaluación de neurodesarrollo por sospecha de dificultades de aprendizaje.', 2, 1),
 (8, CONCAT('PSI-', YEAR(CURDATE()), '-0008'), 'Camila', 'Ortega Ruiz', '2019-05-14', 'femenino', NULL, '0991111008', NULL, 'Sector La Floresta', 'Centro Infantil Girasoles', 'Inicial 1', DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'activo', 'Berrinches intensos y dificultad para dormir.', 2, 1);

INSERT INTO patient_guardians (patient_id, first_name, last_name, relationship, identification, phone, email, is_primary, authorized_info, notes) VALUES
 (1, 'Carla', 'Ríos Andrade', 'madre', '1712345601', '0991111001', 'carla.rios@ejemplo.com', 1, 1, 'Contacto preferente en la tarde.'),
 (1, 'Andrés', 'Salgado Pino', 'padre', '1712345602', '0992222001', NULL, 0, 1, NULL),
 (2, 'Mónica', 'Peña Salas', 'madre', '1712345603', '0991111002', 'familia.cordero@ejemplo.com', 1, 1, NULL),
 (3, 'Lucía', 'Núñez Vera', 'madre', '1712345604', '0991111003', NULL, 1, 1, NULL),
 (4, 'Gabriel', 'Moreno Lima', 'padre', '1712345605', '0991111004', NULL, 1, 1, 'Custodia compartida.'),
 (4, 'Sofía', 'Cruz Ponce', 'madre', '1712345606', '0993333004', NULL, 0, 1, NULL),
 (5, 'Verónica', 'Vélez Cabrera', 'madre', '1712345607', '0991111005', NULL, 1, 1, NULL),
 (6, 'Patricia', 'Mora Jaramillo', 'madre', '1712345608', '0991111006', NULL, 1, 1, NULL),
 (7, 'Diego', 'Benítez Solís', 'padre', '1712345609', '0991111007', NULL, 1, 1, NULL),
 (8, 'Andrea', 'Ruiz Calderón', 'madre', '1712345610', '0991111008', NULL, 1, 0, 'Solicita que la información se entregue en persona.');

-- ---------------------------------------------------------------------
-- Anamnesis
-- ---------------------------------------------------------------------
INSERT INTO anamnesis (patient_id, family_data, consultation_reason, prenatal_history, perinatal_history, motor_development,
  language_development, socioemotional_development, medical_history, medications, external_professionals, neurological_history,
  psychiatric_history, school_history, family_dynamics, behavior, sleep, feeding, screen_use, social_relations, clinical_observations, status, created_by, updated_by) VALUES
 (1, 'Vive con ambos padres y una hermana menor de 4 años. Padre trabaja en horario rotativo.',
     'La institución educativa reporta dificultad para mantener la atención en clase y tareas incompletas.',
     'Embarazo planificado, controles completos, sin complicaciones.',
     'Parto por cesárea a las 39 semanas. Peso 3.200 g, talla 50 cm. Llanto inmediato.',
     'Sostén cefálico a los 3 meses, marcha independiente a los 13 meses.',
     'Primeras palabras a los 12 meses; frases de dos palabras a los 22 meses.',
     'Apego seguro con la madre. Tolerancia a la frustración disminuida ante tareas largas.',
     'Sin hospitalizaciones. Controles pediátricos al día.', 'Ninguno.',
     'Pediatra Dr. Lucio Paredes; refuerzo pedagógico en la institución.',
     'Sin antecedentes de convulsiones ni traumatismos.',
     'Sin atención psicológica previa. Tío materno con diagnóstico de TDAH.',
     'Ingresó a inicial a los 4 años. Adaptación adecuada. Desde 2.º EGB se reportan dificultades atencionales.',
     'Normas poco consistentes entre ambos padres; rutina de tareas irregular.',
     'Se levanta del puesto con frecuencia, olvida materiales, interrumpe conversaciones.',
     'Duerme 8 horas; se resiste a acostarse.',
     'Apetito adecuado, selectivo con verduras.',
     'Aproximadamente 3 horas diarias entre tablet y televisión.',
     'Juega con pares, discusiones ocasionales por turnos.',
     'Niño colaborador, requiere consignas cortas y refuerzo frecuente.', 'completa', 2, 2),
 (2, 'Vive con la madre y la abuela materna. Padre reside en otra ciudad, contacto quincenal.',
     'Malestar físico y llanto antes de evaluaciones escolares.',
     'Embarazo sin complicaciones.', 'Parto normal a término, sin incidencias.',
     'Hitos motores dentro de lo esperado.', 'Desarrollo del lenguaje adecuado.',
     'Niña reservada, alta autoexigencia.', 'Gastritis en seguimiento por pediatría.', 'Ninguno actualmente.',
     'Pediatra Dra. Ana Cueva.', 'Sin antecedentes.', 'Madre con antecedente de trastorno de ansiedad.',
     'Buen rendimiento académico. Evita exposiciones orales.',
     'Vínculo cercano con la madre; exigencia académica elevada en el hogar.',
     'Revisa tareas repetidamente, busca aprobación constante.',
     'Dificultad para conciliar el sueño la noche previa a exámenes.',
     'Disminución del apetito en periodos de evaluación.', 'Uso moderado, principalmente académico.',
     'Dos amigas cercanas; evita grupos grandes.',
     'Presenta síntomas ansiosos focalizados en el contexto evaluativo.', 'completa', 2, 2),
 (3, 'Vive con la madre y los abuelos maternos.',
     'Pediatra refiere por lenguaje expresivo limitado para su edad.',
     'Embarazo con amenaza de parto prematuro a las 30 semanas, reposo indicado.',
     'Parto a las 37 semanas, peso 2.700 g. Requirió observación 24 horas.',
     'Marcha a los 15 meses.', 'Primeras palabras a los 20 meses; actualmente usa frases de dos palabras.',
     'Busca contacto con adultos conocidos; juego paralelo con pares.',
     'Otitis a repetición durante el primer año.', 'Ninguno.',
     'Terapia de lenguaje una vez por semana.', 'Sin antecedentes.', 'Sin antecedentes.',
     'Asiste a centro infantil desde los 3 años.', 'Cuidado compartido con los abuelos; rutinas estables.',
     'Se frustra cuando no logra hacerse entender.', 'Duerme toda la noche.', 'Adecuada.',
     'Menos de 1 hora diaria.', 'Interés por otros niños, poca interacción verbal.',
     'Se sugiere trabajo coordinado con terapia de lenguaje.', 'borrador', 2, 2);

-- ---------------------------------------------------------------------
-- Diagnósticos
-- ---------------------------------------------------------------------
INSERT INTO diagnoses (patient_id, diagnosis, code, classification_system, type, diagnosed_at, status, notes, created_by) VALUES
 (1, 'Trastorno por déficit de atención con hiperactividad, presentación inatenta', 'F90.0', 'CIE-10', 'presuntivo', DATE_SUB(CURDATE(), INTERVAL 7 MONTH), 'activo', 'Impresión diagnóstica inicial; requiere confirmación con evaluación.', 2),
 (1, 'Trastorno por déficit de atención con hiperactividad, presentación inatenta', '314.00', 'DSM-5-TR', 'confirmado', DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 'activo', 'Confirmado tras evaluación e informes escolares.', 2),
 (2, 'Trastorno de ansiedad ante evaluaciones', '6B00', 'CIE-11', 'presuntivo', DATE_SUB(CURDATE(), INTERVAL 4 MONTH), 'activo', NULL, 2),
 (3, 'Retraso del lenguaje expresivo', 'F80.1', 'CIE-10', 'presuntivo', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 'activo', 'En seguimiento conjunto con terapia de lenguaje.', 2),
 (4, 'Desregulación emocional', NULL, 'Otro', 'diferencial', DATE_SUB(CURDATE(), INTERVAL 9 MONTH), 'activo', 'Se descarta trastorno del estado de ánimo.', 2),
 (4, 'Episodio depresivo', 'F32', 'CIE-10', 'descartado', DATE_SUB(CURDATE(), INTERVAL 8 MONTH), 'inactivo', 'Descartado tras valoración y entrevista familiar.', 2);

-- ---------------------------------------------------------------------
-- Planes terapéuticos, áreas y objetivos
-- ---------------------------------------------------------------------
INSERT INTO treatment_plans (id, patient_id, start_date, review_date, general_objective, status, created_by) VALUES
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 5 MONTH), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Mejorar la autorregulación atencional y la organización de tareas escolares con apoyo familiar y escolar.', 'activo', 2),
 (2, 2, DATE_SUB(CURDATE(), INTERVAL 4 MONTH), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'Reducir la respuesta ansiosa ante situaciones de evaluación mediante estrategias de afrontamiento.', 'activo', 2),
 (3, 4, DATE_SUB(CURDATE(), INTERVAL 10 MONTH), DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 'Fortalecer la identificación y regulación de emociones en contextos de conflicto.', 'suspendido', 2),
 (4, 5, DATE_SUB(CURDATE(), INTERVAL 6 WEEK), DATE_ADD(CURDATE(), INTERVAL 6 WEEK), 'Disminuir conductas oposicionistas mediante pautas parentales consistentes.', 'activo', 2);

INSERT INTO treatment_areas (id, plan_id, name, sort_order) VALUES
 (1, 1, 'Funciones ejecutivas', 1), (2, 1, 'Habilidades parentales', 2), (3, 1, 'Autonomía', 3),
 (4, 2, 'Ansiedad', 1), (5, 2, 'Autoestima', 2),
 (6, 3, 'Regulación emocional', 1), (7, 3, 'Habilidades sociales', 2),
 (8, 4, 'Conducta', 1), (9, 4, 'Habilidades parentales', 2);

INSERT INTO treatment_objectives (area_id, plan_id, objective, indicator, status, achieved_at) VALUES
 (1, 1, 'Utilizar una agenda diaria de tareas', 'Registra sus tareas en la agenda 4 de 5 días', 'logrado', DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
 (1, 1, 'Sostener la atención en tareas de 15 minutos', 'Completa una tarea de 15 minutos con un solo recordatorio', 'en_proceso', NULL),
 (1, 1, 'Organizar los materiales escolares antes de dormir', 'Prepara la mochila sin ayuda 3 de 5 noches', 'en_proceso', NULL),
 (2, 1, 'Aplicar instrucciones cortas y refuerzo positivo en casa', 'Los padres reportan uso diario de la estrategia', 'logrado', DATE_SUB(CURDATE(), INTERVAL 3 MONTH)),
 (2, 1, 'Establecer una rutina de estudio consistente', 'Rutina cumplida 4 días por semana', 'pendiente', NULL),
 (3, 1, 'Realizar la rutina de la mañana con apoyo visual', 'Completa 4 de 5 pasos de forma autónoma', 'pendiente', NULL),
 (4, 2, 'Identificar señales corporales de ansiedad', 'Nombra tres señales propias', 'logrado', DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
 (4, 2, 'Aplicar respiración diafragmática antes de una evaluación', 'Usa la técnica en dos evaluaciones consecutivas', 'en_proceso', NULL),
 (4, 2, 'Reestructurar pensamientos anticipatorios', 'Registra y cuestiona dos pensamientos por semana', 'en_proceso', NULL),
 (5, 2, 'Reconocer logros propios sin compararse', 'Registra un logro diario durante dos semanas', 'pendiente', NULL),
 (6, 3, 'Nombrar la emoción antes de reaccionar', 'Verbaliza la emoción en 3 de 5 situaciones', 'en_proceso', NULL),
 (7, 3, 'Resolver un conflicto con pares usando el diálogo', 'Reporta una resolución por semana', 'pendiente', NULL),
 (8, 4, 'Cumplir una instrucción al primer pedido', 'Cumple en 3 de 5 oportunidades', 'en_proceso', NULL),
 (9, 4, 'Aplicar consecuencias consistentes entre ambos cuidadores', 'Ambos cuidadores reportan la misma pauta', 'pendiente', NULL);

-- ---------------------------------------------------------------------
-- Paquetes de sesiones
-- ---------------------------------------------------------------------
INSERT INTO session_packages (id, patient_id, name, purchased_at, expires_at, sessions_total, price_regular, discount, total_paid, status, created_by) VALUES
 (1, 1, 'Paquete de 8 sesiones', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 8, 200.00, 40.00, 160.00, 'activo', 2),
 (2, 2, 'Paquete de 8 sesiones', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), DATE_ADD(CURDATE(), INTERVAL 4 MONTH), 8, 200.00, 30.00, 170.00, 'activo', 2),
 (3, 6, 'Paquete de 12 sesiones', DATE_SUB(CURDATE(), INTERVAL 12 MONTH), DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 12, 300.00, 60.00, 240.00, 'finalizado', 2);

-- ---------------------------------------------------------------------
-- Citas
-- ---------------------------------------------------------------------
INSERT INTO appointments (id, patient_id, professional_id, starts_at, ends_at, duration_min, type, reason, status, created_by) VALUES
 -- Hoy
 (1, 1, 2, CONCAT(CURDATE(), ' 09:00:00'), CONCAT(CURDATE(), ' 09:45:00'), 45, 'sesion', 'Seguimiento de estrategias atencionales', 'confirmada', 3),
 (2, 2, 2, CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 10:45:00'), 45, 'sesion', 'Exposición graduada ante evaluaciones', 'programada', 3),
 (3, 5, 2, CONCAT(CURDATE(), ' 11:00:00'), CONCAT(CURDATE(), ' 11:45:00'), 45, 'padres', 'Pautas parentales', 'programada', 3),
 (4, 7, 2, CONCAT(CURDATE(), ' 15:00:00'), CONCAT(CURDATE(), ' 16:00:00'), 60, 'evaluacion', 'Aplicación de pruebas', 'programada', 3),
 -- Próximos días
 (5, 3, 2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:45:00'), 45, 'sesion', 'Estimulación del lenguaje', 'programada', 3),
 (6, 1, 2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 09:45:00'), 45, 'sesion', NULL, 'programada', 3),
 (7, 2, 2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 10:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 10:45:00'), 45, 'sesion', NULL, 'programada', 3),
 (8, 8, 2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 16:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 16:45:00'), 45, 'primera_consulta', 'Entrevista inicial con la madre', 'programada', 3),
 -- Pasadas
 (9, 1, 2, CONCAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), ' 09:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), ' 09:45:00'), 45, 'sesion', NULL, 'atendida', 3),
 (10, 2, 2, CONCAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), ' 10:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 7 DAY), ' 10:45:00'), 45, 'sesion', NULL, 'atendida', 3),
 (11, 4, 2, CONCAT(DATE_SUB(CURDATE(), INTERVAL 40 DAY), ' 11:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 40 DAY), ' 11:45:00'), 45, 'sesion', NULL, 'no_asistio', 3),
 (12, 5, 2, CONCAT(DATE_SUB(CURDATE(), INTERVAL 14 DAY), ' 11:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 14 DAY), ' 11:45:00'), 45, 'sesion', NULL, 'atendida', 3);

-- ---------------------------------------------------------------------
-- Sesiones terapéuticas
-- ---------------------------------------------------------------------
INSERT INTO therapy_sessions (id, patient_id, appointment_id, plan_id, package_id, professional_id, session_number, session_date, session_time,
  duration_min, modality, objective_worked, emotional_state, intervention, patient_response, participation, progress, clinical_notes,
  homework, recommendations, next_objective, attendance, fee, created_by) VALUES
 (1, 1, NULL, 1, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 150 DAY), '09:00:00', 45, 'presencial', 'Encuadre e identificación de dificultades atencionales', 'neutral', 'Entrevista lúdica y aplicación de escala conductual a los padres.', 'Colaborador, responde consignas breves.', 'media', 'en_proceso', 'Se establece encuadre y objetivos iniciales con la familia.', 'Registro semanal de tareas', 'Rutina de estudio en horario fijo', 'Introducir agenda visual', 'atendido', 25.00, 2),
 (2, 1, NULL, 1, NULL, 2, 2, DATE_SUB(CURDATE(), INTERVAL 135 DAY), '09:00:00', 45, 'presencial', 'Uso de agenda visual', 'regulado', 'Elaboración conjunta de agenda con apoyos visuales.', 'Participa activamente, propone recordatorios.', 'alta', 'en_proceso', 'Buena disposición al material visual.', 'Usar la agenda 5 días', NULL, 'Sostener atención en tareas de 15 minutos', 'atendido', 25.00, 2),
 (3, 1, NULL, 1, 1, 2, 3, DATE_SUB(CURDATE(), INTERVAL 80 DAY), '09:00:00', 45, 'presencial', 'Atención sostenida en tareas cortas', 'regulado', 'Tareas de atención con temporizador y refuerzo diferencial.', 'Completa dos tareas de 10 minutos.', 'alta', 'logrado', 'Responde bien al refuerzo inmediato.', 'Tarea de 15 minutos diaria', 'Reducir estímulos en el área de estudio', 'Autonomía en rutina matutina', 'atendido', 20.00, 2),
 (4, 1, NULL, 1, 1, 2, 4, DATE_SUB(CURDATE(), INTERVAL 66 DAY), '09:00:00', 45, 'presencial', 'Organización de materiales', 'neutral', 'Entrenamiento en checklist nocturno.', 'Requiere apoyo verbal.', 'media', 'en_proceso', NULL, 'Checklist nocturno', NULL, NULL, 'atendido', 20.00, 2),
 (5, 1, NULL, 1, 1, 2, 5, DATE_SUB(CURDATE(), INTERVAL 52 DAY), '09:00:00', 45, 'familiar', 'Pautas parentales consistentes', 'regulado', 'Sesión con ambos padres: instrucciones cortas y refuerzo positivo.', 'Padres reportan mejoría en cumplimiento.', 'alta', 'logrado', 'Se acuerdan pautas comunes entre ambos cuidadores.', NULL, 'Mantener refuerzo positivo diario', NULL, 'atendido', 20.00, 2),
 (6, 1, NULL, 1, 1, 2, 6, DATE_SUB(CURDATE(), INTERVAL 38 DAY), '09:00:00', 45, 'presencial', 'Atención sostenida', 'ansioso', 'Actividades de atención alternante.', 'Se distrae ante ruidos externos.', 'media', 'requiere_intervencion', 'Semana con cambios en la rutina familiar.', NULL, NULL, NULL, 'atendido', 20.00, 2),
 (7, 1, NULL, 1, 1, 2, 7, DATE_SUB(CURDATE(), INTERVAL 21 DAY), '09:00:00', 45, 'presencial', 'Repaso de estrategias y organización', 'regulado', 'Revisión de agenda y ajuste de apoyos visuales.', 'Muestra uso autónomo de la agenda.', 'alta', 'logrado', NULL, 'Continuar agenda', NULL, NULL, 'atendido', 20.00, 2),
 (8, 1, 9, 1, 1, 2, 8, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 45, 'presencial', 'Autonomía en rutina matutina', 'regulado', 'Secuenciación con apoyo visual y ensayo conductual.', 'Completa 3 de 5 pasos sin ayuda.', 'media', 'en_proceso', NULL, 'Rutina matutina con apoyo visual', NULL, 'Reforzar pasos 4 y 5', 'atendido', 20.00, 2),

 (9, 2, NULL, 2, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 120 DAY), '10:00:00', 45, 'presencial', 'Encuadre e identificación de síntomas ansiosos', 'ansioso', 'Entrevista y psicoeducación sobre ansiedad.', 'Verbaliza malestar ante exámenes.', 'media', 'en_proceso', NULL, 'Registro de situaciones ansiógenas', NULL, 'Identificar señales corporales', 'atendido', 25.00, 2),
 (10, 2, NULL, 2, NULL, 2, 2, DATE_SUB(CURDATE(), INTERVAL 100 DAY), '10:00:00', 45, 'presencial', 'Señales corporales de ansiedad', 'ansioso', 'Mapa corporal y escala subjetiva de malestar.', 'Identifica tensión y dolor abdominal.', 'alta', 'logrado', NULL, NULL, NULL, 'Respiración diafragmática', 'atendido', 25.00, 2),
 (11, 2, NULL, 2, 2, 2, 3, DATE_SUB(CURDATE(), INTERVAL 56 DAY), '10:00:00', 45, 'presencial', 'Respiración diafragmática', 'neutral', 'Entrenamiento en respiración y relajación breve.', 'Aplica la técnica con guía.', 'alta', 'en_proceso', NULL, 'Practicar 5 minutos diarios', NULL, NULL, 'atendido', 21.25, 2),
 (12, 2, NULL, 2, 2, 2, 4, DATE_SUB(CURDATE(), INTERVAL 35 DAY), '10:00:00', 45, 'presencial', 'Pensamientos anticipatorios', 'ansioso', 'Registro y cuestionamiento de pensamientos.', 'Reconoce anticipaciones catastróficas.', 'media', 'en_proceso', NULL, 'Registro de dos pensamientos semanales', NULL, NULL, 'atendido', 21.25, 2),
 (13, 2, 10, 2, 2, 2, 5, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '10:00:00', 45, 'presencial', 'Exposición graduada a situación evaluativa', 'neutral', 'Jerarquía de exposición y ensayo de exposición oral breve.', 'Completa el primer nivel de la jerarquía.', 'alta', 'en_proceso', 'Buena adherencia a las tareas entre sesiones.', 'Exposición nivel 2', 'Evitar reasegurar en exceso antes de exámenes', 'Continuar jerarquía', 'atendido', 21.25, 2),

 (14, 3, NULL, NULL, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 75 DAY), '09:00:00', 45, 'presencial', 'Encuadre y observación de juego', 'regulado', 'Observación de juego libre y entrevista a la madre.', 'Interacción por gestos y palabras sueltas.', 'media', 'en_proceso', 'Se coordina con terapia de lenguaje.', NULL, NULL, NULL, 'atendido', 25.00, 2),
 (15, 3, NULL, NULL, NULL, 2, 2, DATE_SUB(CURDATE(), INTERVAL 50 DAY), '09:00:00', 45, 'presencial', 'Estimulación de intención comunicativa', 'regulado', 'Juego estructurado con demanda comunicativa.', 'Imita palabras nuevas.', 'alta', 'en_proceso', NULL, 'Rutinas de juego en casa', NULL, NULL, 'atendido', 25.00, 2),
 (16, 3, NULL, NULL, NULL, 2, 3, DATE_SUB(CURDATE(), INTERVAL 25 DAY), '09:00:00', 45, 'presencial', 'Ampliación de vocabulario funcional', 'neutral', 'Apoyos visuales y modelado.', 'Usa cinco palabras nuevas.', 'media', 'en_proceso', NULL, NULL, NULL, NULL, 'atendido', 25.00, 2),

 (17, 4, NULL, 3, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 300 DAY), '11:00:00', 45, 'presencial', 'Encuadre y evaluación inicial', 'irritable', 'Entrevista con la adolescente y los cuidadores.', 'Responde de forma breve.', 'baja', 'en_proceso', NULL, NULL, NULL, NULL, 'atendido', 25.00, 2),
 (18, 4, NULL, 3, NULL, 2, 2, DATE_SUB(CURDATE(), INTERVAL 280 DAY), '11:00:00', 45, 'presencial', 'Identificación de emociones', 'neutral', 'Uso de termómetro emocional.', 'Participa con apoyo.', 'media', 'en_proceso', NULL, NULL, NULL, NULL, 'atendido', 25.00, 2),
 (19, 4, 11, 3, NULL, 2, NULL, DATE_SUB(CURDATE(), INTERVAL 40 DAY), '11:00:00', 45, 'presencial', NULL, NULL, NULL, NULL, NULL, NULL, 'La familia no asistió ni avisó previamente.', NULL, NULL, NULL, 'no_asistio', 0.00, 2),

 (20, 5, NULL, 4, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), '11:00:00', 45, 'presencial', 'Encuadre y análisis funcional de la conducta', 'irritable', 'Entrevista con la madre y observación conductual.', 'Se muestra desafiante ante límites.', 'media', 'en_proceso', NULL, 'Registro ABC de conductas', NULL, 'Pautas parentales', 'atendido', 25.00, 2),
 (21, 5, 12, 4, NULL, 2, 2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '11:00:00', 45, 'padres', 'Pautas parentales consistentes', 'neutral', 'Entrenamiento en instrucciones efectivas y consecuencias.', 'La madre practica en sesión.', 'alta', 'en_proceso', NULL, 'Aplicar la pauta durante dos semanas', 'Acordar pautas con el padre', NULL, 'atendido', 25.00, 2),

 (22, 7, NULL, NULL, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), '15:00:00', 60, 'presencial', 'Entrevista de evaluación', 'neutral', 'Entrevista inicial y recolección de antecedentes escolares.', 'Colaborador durante la entrevista.', 'alta', 'en_proceso', 'Se inicia proceso de evaluación de neurodesarrollo.', NULL, NULL, 'Aplicación de pruebas', 'atendido', 30.00, 2),

 (23, 8, NULL, NULL, NULL, 2, 1, DATE_SUB(CURDATE(), INTERVAL 65 DAY), '16:00:00', 45, 'presencial', 'Entrevista inicial con la madre', 'neutral', 'Entrevista y psicoeducación sobre rutinas de sueño.', 'La madre reporta agotamiento.', 'media', 'en_proceso', NULL, 'Rutina de sueño estructurada', NULL, NULL, 'atendido', 25.00, 2);

INSERT INTO therapy_session_objectives (session_id, objective_id, status_after) VALUES
 (3, 2, 'en_proceso'), (4, 3, 'en_proceso'), (5, 4, 'logrado'), (7, 1, 'logrado'), (8, 6, 'en_proceso'),
 (10, 7, 'logrado'), (11, 8, 'en_proceso'), (12, 9, 'en_proceso'), (13, 8, 'en_proceso'),
 (20, 13, 'en_proceso'), (21, 14, 'pendiente');

INSERT INTO package_movements (package_id, session_id, type, quantity, reason, created_by) VALUES
 (1, 3, 'consumo', 1, 'Sesión registrada', 2), (1, 4, 'consumo', 1, 'Sesión registrada', 2),
 (1, 5, 'consumo', 1, 'Sesión registrada', 2), (1, 6, 'consumo', 1, 'Sesión registrada', 2),
 (1, 7, 'consumo', 1, 'Sesión registrada', 2), (1, 8, 'consumo', 1, 'Sesión registrada', 2),
 (2, 11, 'consumo', 1, 'Sesión registrada', 2), (2, 12, 'consumo', 1, 'Sesión registrada', 2),
 (2, 13, 'consumo', 1, 'Sesión registrada', 2);

-- ---------------------------------------------------------------------
-- Evaluación de progreso
-- ---------------------------------------------------------------------
INSERT INTO progress_reviews (patient_id, plan_id, review_date, sessions_at_review, objectives_initial, objectives_achieved,
  objectives_in_progress, objectives_no_progress, patient_report, family_report, school_report, clinical_observation,
  general_status, clinical_decision, created_by) VALUES
 (1, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 7,
  CONCAT('• Utilizar una agenda diaria de tareas', CHAR(10), '• Sostener la atención en tareas de 15 minutos', CHAR(10), '• Organizar los materiales escolares'),
  CONCAT('• Utilizar una agenda diaria de tareas', CHAR(10), '• Aplicar instrucciones cortas y refuerzo positivo en casa'),
  CONCAT('• Sostener la atención en tareas de 15 minutos', CHAR(10), '• Organizar los materiales escolares'),
  '• Establecer una rutina de estudio consistente',
  'Refiere que le resulta más fácil terminar las tareas cuando usa la agenda.',
  'Los padres reportan menos conflictos en el horario de tareas.',
  'La institución informa mejoría en entrega de trabajos, persisten distracciones en clase.',
  'Se observa mayor uso de estrategias de organización con apoyo decreciente.',
  'mejor', 'mantener_plan', 2);

-- ---------------------------------------------------------------------
-- Evaluaciones psicológicas
-- ---------------------------------------------------------------------
INSERT INTO psychological_evaluations (id, patient_id, professional_id, start_date, end_date, reason, areas, status,
  clinical_integration, conclusions, recommendations, report_delivered_at, created_by) VALUES
 (1, 1, 2, DATE_SUB(CURDATE(), INTERVAL 6 MONTH), DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
  'Evaluación de atención y funciones ejecutivas por solicitud escolar.', 'cognitiva,conductual,academica', 'informe_entregado',
  'Los resultados son consistentes con los reportes escolares y la anamnesis: dificultades atencionales sin compromiso cognitivo general.',
  'Perfil cognitivo dentro del rango promedio con dificultades específicas en atención sostenida y control inhibitorio.',
  'Adaptaciones metodológicas en el aula, rutinas estructuradas en casa y continuidad del acompañamiento terapéutico.',
  DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 2),
 (2, 7, 2, DATE_SUB(CURDATE(), INTERVAL 28 DAY), NULL,
  'Evaluación de neurodesarrollo por sospecha de dificultades de aprendizaje.', 'cognitiva,neurodesarrollo,academica', 'en_proceso',
  NULL, NULL, NULL, NULL, 2),
 (3, 4, 2, DATE_SUB(CURDATE(), INTERVAL 7 MONTH), DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
  'Evaluación emocional y de personalidad.', 'emocional,personalidad', 'informe_pendiente',
  'Se integran resultados con la información familiar y escolar.', NULL, NULL, NULL, 2);

INSERT INTO evaluation_instruments (evaluation_id, instrument_id, applied_at, scores, interpretation, notes, created_by) VALUES
 (1, (SELECT id FROM instrument_catalog WHERE name = 'WISC-V'), DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
  'CI Total 98; ICV 104; IVE 101; IRF 95; IMT 88; IVP 91',
  'Rendimiento cognitivo general dentro del promedio, con menor desempeño relativo en memoria de trabajo y velocidad de procesamiento.',
  'Aplicación en dos sesiones por fatiga.', 2),
 (1, (SELECT id FROM instrument_catalog WHERE name = 'CARAS-R'), DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
  'Aciertos 32; Errores 9; ICI 58',
  'Desempeño atencional por debajo de lo esperado para su edad, con impulsividad moderada en la respuesta.', NULL, 2),
 (1, (SELECT id FROM instrument_catalog WHERE name = 'TDAH-5'), DATE_SUB(CURDATE(), INTERVAL 6 MONTH),
  'Escala padres: inatención 18/27; hiperactividad 9/27. Escala docentes: inatención 20/27',
  'Ambos informantes coinciden en síntomas de inatención clínicamente significativos.',
  'Los puntajes se interpretan junto con la observación clínica, nunca de forma aislada.', 2),
 (3, (SELECT id FROM instrument_catalog WHERE name = 'Test de la Familia'), DATE_SUB(CURDATE(), INTERVAL 7 MONTH),
  'Técnica proyectiva gráfica', 'Se registran elementos para explorar en entrevista; no se utilizan como evidencia diagnóstica.', NULL, 2);

-- ---------------------------------------------------------------------
-- Pagos
-- ---------------------------------------------------------------------
INSERT INTO payments (patient_id, package_id, session_id, paid_at, concept, amount, method, status, reference, created_by) VALUES
 (1, 1, NULL, DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'Paquete: Paquete de 8 sesiones', 160.00, 'transferencia', 'pagado', 'TRF-00121', 3),
 (2, 2, NULL, DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 'Paquete: Paquete de 8 sesiones', 170.00, 'transferencia', 'pagado', 'TRF-00148', 3),
 (6, 3, NULL, DATE_SUB(CURDATE(), INTERVAL 12 MONTH), 'Paquete: Paquete de 12 sesiones', 240.00, 'efectivo', 'pagado', NULL, 3),
 (1, NULL, 1, DATE_SUB(CURDATE(), INTERVAL 150 DAY), 'Sesión N.º 1', 25.00, 'efectivo', 'pagado', NULL, 3),
 (1, NULL, 2, DATE_SUB(CURDATE(), INTERVAL 135 DAY), 'Sesión N.º 2', 25.00, 'efectivo', 'pagado', NULL, 3),
 (2, NULL, 9, DATE_SUB(CURDATE(), INTERVAL 120 DAY), 'Sesión N.º 1', 25.00, 'efectivo', 'pagado', NULL, 3),
 (2, NULL, 10, DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'Sesión N.º 2', 25.00, 'tarjeta', 'pagado', 'POS-8841', 3),
 (3, NULL, 14, DATE_SUB(CURDATE(), INTERVAL 75 DAY), 'Sesión N.º 1', 25.00, 'efectivo', 'pagado', NULL, 3),
 (3, NULL, 15, DATE_SUB(CURDATE(), INTERVAL 50 DAY), 'Sesión N.º 2', 25.00, 'efectivo', 'pagado', NULL, 3),
 (3, NULL, 16, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Sesión N.º 3', 25.00, 'efectivo', 'pendiente', NULL, 3),
 (5, NULL, 20, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Sesión N.º 1', 25.00, 'efectivo', 'pagado', NULL, 3),
 (5, NULL, 21, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'Sesión N.º 2', 25.00, 'efectivo', 'pendiente', NULL, 3),
 (7, NULL, 22, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Entrevista de evaluación', 30.00, 'transferencia', 'pagado', 'TRF-00190', 3),
 (7, NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Proceso de evaluación de neurodesarrollo', 180.00, 'transferencia', 'pendiente', NULL, 3),
 (8, NULL, 23, DATE_SUB(CURDATE(), INTERVAL 65 DAY), 'Entrevista inicial', 25.00, 'efectivo', 'pagado', NULL, 3),
 (4, NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'Sesión reprogramada', 25.00, 'efectivo', 'anulado', NULL, 3);

UPDATE payments SET void_reason = 'Cobro duplicado por error de registro', voided_by = 1, voided_at = NOW() WHERE status = 'anulado';

-- ---------------------------------------------------------------------
-- Consentimientos
-- ---------------------------------------------------------------------
INSERT INTO consents (patient_id, guardian_id, type, consent_date, status, notes, created_by) VALUES
 (1, 1, 'consentimiento_informado', DATE_SUB(CURDATE(), INTERVAL 8 MONTH), 'vigente', 'Firmado por la madre en la primera sesión.', 3),
 (1, 1, 'autorizacion_institucion', DATE_SUB(CURDATE(), INTERVAL 7 MONTH), 'vigente', 'Autoriza comunicación con la unidad educativa.', 3),
 (2, 3, 'consentimiento_informado', DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 'vigente', NULL, 3),
 (3, 4, 'consentimiento_informado', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'vigente', NULL, 3),
 (3, 4, 'autorizacion_intercambio', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'vigente', 'Intercambio con terapia de lenguaje.', 3),
 (7, 9, 'autorizacion_evaluacion', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'vigente', NULL, 3),
 (8, 10, 'consentimiento_informado', DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'pendiente', 'Pendiente de firma presencial.', 3);

-- ---------------------------------------------------------------------
-- Plantillas de documentos
-- ---------------------------------------------------------------------
INSERT INTO document_templates (name, type, content, is_active, created_by) VALUES
 ('Informe psicológico', 'informe_psicologico',
  '<h3>1. Datos de identificación</h3><p><strong>Nombre:</strong> {{paciente_nombre}}<br><strong>Edad:</strong> {{paciente_edad}}<br><strong>Identificación:</strong> {{identificacion}}<br><strong>Expediente:</strong> {{expediente}}<br><strong>Institución educativa:</strong> {{institucion_educativa}}<br><strong>Fecha de ingreso:</strong> {{fecha_ingreso}}<br><strong>Fecha del informe:</strong> {{fecha}}</p><h3>2. Motivo de consulta</h3><p>{{motivo_consulta}}</p><h3>3. Antecedentes relevantes</h3><p>Describa los antecedentes obtenidos en la anamnesis.</p><h3>4. Proceso de intervención</h3><p>Se han realizado {{sesiones_realizadas}} sesiones terapéuticas. Objetivos trabajados:</p><p>{{objetivos_plan}}</p><h3>5. Impresión diagnóstica</h3><p>{{diagnostico}}</p><h3>6. Conclusiones</h3><p>Redacte las conclusiones profesionales.</p><h3>7. Recomendaciones</h3><ul><li>Recomendación para la familia.</li><li>Recomendación para la institución educativa.</li></ul>', 1, 1),
 ('Informe de evaluación psicológica', 'informe_evaluacion',
  '<h3>1. Datos de identificación</h3><p><strong>Nombre:</strong> {{paciente_nombre}}<br><strong>Edad:</strong> {{paciente_edad}}<br><strong>Expediente:</strong> {{expediente}}<br><strong>Fecha:</strong> {{fecha}}</p><h3>2. Motivo de evaluación</h3><p>{{motivo_consulta}}</p><h3>3. Procedimiento</h3><p>Entrevista clínica, observación conductual y aplicación de instrumentos psicométricos.</p>', 1, 1),
 ('Certificado de asistencia', 'certificado_asistencia',
  '<p>Quien suscribe, {{profesional}}, con registro profesional {{registro_profesional}}, certifica que <strong>{{paciente_nombre}}</strong>, con identificación {{identificacion}} y expediente {{expediente}}, asiste a atención psicológica en esta consulta desde el {{fecha_ingreso}}, habiendo completado {{sesiones_realizadas}} sesiones a la fecha.</p><p>Se expide el presente certificado a solicitud del representante, el {{fecha}}.</p>', 1, 1),
 ('Informe para institución educativa', 'informe_institucion',
  '<p><strong>Estudiante:</strong> {{paciente_nombre}} · <strong>Edad:</strong> {{paciente_edad}}<br><strong>Institución:</strong> {{institucion_educativa}}<br><strong>Fecha:</strong> {{fecha}}</p><h3>Situación actual</h3><p>Describa el motivo del acompañamiento y los avances observados, con autorización del representante {{representante}}.</p><h3>Sugerencias para el aula</h3><ul><li>Ubicación preferencial y consignas cortas.</li><li>Tiempo adicional en evaluaciones cuando corresponda.</li><li>Refuerzo positivo ante logros parciales.</li></ul>', 1, 1),
 ('Derivación a otro profesional', 'derivacion',
  '<p>{{fecha}}</p><p>Estimado/a colega:</p><p>Derivo al paciente <strong>{{paciente_nombre}}</strong> ({{paciente_edad}}, expediente {{expediente}}) para valoración en su especialidad. Motivo de consulta inicial: {{motivo_consulta}}. Impresión diagnóstica actual: {{diagnostico}}.</p><p>Quedo atenta a la contrarreferencia.</p>', 1, 1),
 ('Plan de intervención', 'plan_intervencion',
  '<p><strong>Paciente:</strong> {{paciente_nombre}} · <strong>Expediente:</strong> {{expediente}} · <strong>Fecha:</strong> {{fecha}}</p><h3>Objetivos del plan</h3><p>{{objetivos_plan}}</p><h3>Frecuencia sugerida</h3><p>Una sesión semanal de 45 minutos, con revisión de progreso periódica.</p>', 1, 1);

-- ---------------------------------------------------------------------
-- Documentos emitidos
-- ---------------------------------------------------------------------
INSERT INTO documents (id, patient_id, template_id, evaluation_id, type, title, status, current_version, issued_at, created_by) VALUES
 (1, 1, 2, 1, 'informe_evaluacion', 'Informe de evaluación psicológica — Mateo Salgado', 'emitido', 1, DATE_SUB(NOW(), INTERVAL 5 MONTH), 2),
 (2, 1, 3, NULL, 'certificado_asistencia', 'Certificado de asistencia — Mateo Salgado', 'emitido', 1, DATE_SUB(NOW(), INTERVAL 2 MONTH), 2),
 (3, 4, 2, 3, 'informe_evaluacion', 'Informe de evaluación — Isabella Moreno', 'borrador', 1, NULL, 2);

INSERT INTO document_versions (document_id, version, content, created_by) VALUES
 (1, 1, '<h3>1. Datos de identificación</h3><p>Paciente evaluado en el área cognitiva, conductual y académica.</p><h3>2. Resultados</h3><p>Perfil cognitivo dentro del promedio con dificultades en atención sostenida y memoria de trabajo.</p><h3>3. Conclusiones</h3><p>Los hallazgos son consistentes con dificultades atencionales; la interpretación fue realizada y validada por la profesional tratante.</p><h3>4. Recomendaciones</h3><ul><li>Adaptaciones metodológicas en el aula.</li><li>Rutinas estructuradas en el hogar.</li><li>Continuidad del acompañamiento terapéutico.</li></ul>', 2),
 (2, 1, '<p>Se certifica que el paciente asiste regularmente a atención psicológica en esta consulta.</p>', 2),
 (3, 1, '<h3>1. Datos de identificación</h3><p>Documento en elaboración.</p>', 2);

-- ---------------------------------------------------------------------
-- Consistencia: contadores derivados de las sesiones registradas
-- ---------------------------------------------------------------------
UPDATE patients p SET
  p.sessions_count = (SELECT COUNT(*) FROM therapy_sessions s WHERE s.patient_id = p.id AND s.attendance = 'atendido'),
  p.last_session_at = (SELECT MAX(s.session_date) FROM therapy_sessions s WHERE s.patient_id = p.id AND s.attendance = 'atendido');

UPDATE session_packages sp SET
  sp.sessions_used = (SELECT COUNT(*) FROM therapy_sessions s WHERE s.package_id = sp.id);

UPDATE session_packages SET status = 'finalizado' WHERE sessions_remaining <= 0;

-- Paciente 6 dado de alta: paquete consumido en su totalidad
UPDATE session_packages SET sessions_used = sessions_total, status = 'finalizado' WHERE id = 3;
UPDATE patients SET sessions_count = 16, last_session_at = DATE_SUB(CURDATE(), INTERVAL 2 MONTH) WHERE id = 6;
UPDATE patients SET last_session_at = DATE_SUB(CURDATE(), INTERVAL 40 DAY) WHERE id = 4;
