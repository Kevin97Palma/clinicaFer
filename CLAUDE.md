# CLAUDE.md — Sistema de Gestión Clínica Psicológica (SGC)
> Contexto completo del proyecto para Claude Code. Leer antes de cualquier tarea.

---

## 1. Identidad del Proyecto

**Nombre del sistema:** SGC — Sistema de Gestión Clínica  
**Cliente piloto:** CATPI S.A.S. (Santo Domingo de los Tsáchilas, Ecuador)  
**Propósito:** Plataforma multiclínica para gestión de atención psicológica infantil, con historial clínico estructurado, flujos de intervención, cronogramas terapéuticos y reportes.  
**URL cliente actual:** https://catpi-sas.com/Prueba/Clinic-1.0.0/ (sitio web institucional, NO el sistema)  
**Repositorio destino:** GitHub / SocketStudioec  
**Deploy:** Hostinger (PHP + MySQL, sin contenedores, ruta configurada, git pull directo)

---

## 2. Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.x vanilla (sin framework) |
| Base de datos | MySQL 8.x |
| Frontend | HTML5 + Bootstrap 5.3 + JavaScript vanilla |
| Autenticación | Sesiones PHP + JWT para API |
| Subida de archivos | PHP `move_uploaded_file`, almacenamiento en `/storage/` |
| Exportación PDF | mPDF o DOMPDF (PHP puro) |
| Gráficas/KPIs | Chart.js 4.x |
| Control de versiones | Git → GitHub → Hostinger |
| Notificaciones (futuro) | Email via PHPMailer |

### Reglas de código obligatorias
- **Siempre PDO con prepared statements** — nunca mysqli directo, nunca concatenación en SQL
- **Respuestas API siempre en JSON:** `{"success": true/false, "data": {}, "message": "", "errors": []}`
- **Autenticación en CADA endpoint:** verificar sesión/token antes de cualquier lógica
- **Separación de responsabilidades:** `/api/` solo lógica de datos, `/modules/` solo vistas HTML
- **Soft delete** en todas las tablas críticas: campo `deleted_at TIMESTAMP NULL`
- **Auditoría** en tablas críticas: `created_by`, `updated_by`, `created_at`, `updated_at`
- **Multiempresa siempre presente:** toda query con filtro `clinica_id` o `empresa_id`
- Comentarios en español para lógica de negocio, inglés para código técnico

---

## 3. Estructura del Proyecto

```
sgc/
├── CLAUDE.md                    ← este archivo
├── .claude/
│   └── agents/                  ← agentes de Claude Code
├── config/
│   ├── database.php             ← conexión PDO singleton
│   ├── app.php                  ← constantes globales
│   └── roles.php                ← definición de roles y permisos
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── verify.php
│   ├── clinicas/
│   ├── usuarios/
│   ├── pacientes/
│   ├── anamnesis/
│   ├── evaluaciones/
│   ├── diagnosticos/
│   ├── planes/
│   ├── sesiones/
│   ├── cronogramas/
│   ├── citas/
│   └── reportes/
├── modules/
│   ├── auth/                    ← login, recuperar contraseña
│   ├── dashboard/               ← dashboard por rol
│   ├── pacientes/               ← registro, listado, perfil
│   ├── historial/               ← historial clínico completo
│   ├── citas/                   ← agenda, calendario
│   ├── cronogramas/             ← plan terapéutico + tareas
│   ├── reportes/                ← informes, certificados
│   ├── usuarios/                ← gestión de usuarios (admin)
│   └── clinicas/                ← gestión de clínicas (superadmin)
├── includes/
│   ├── auth_check.php           ← middleware de sesión
│   ├── role_check.php           ← verificación de rol
│   ├── helpers.php              ← funciones utilitarias
│   ├── response.php             ← helper para JSON responses
│   └── upload.php               ← manejo de archivos
├── storage/
│   ├── consentimientos/
│   ├── anamnesis/
│   ├── evaluaciones/
│   └── documentos/
├── assets/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   └── charts.js
│   └── img/
├── vendor/                      ← composer (mPDF/DOMPDF, PHPMailer)
├── composer.json
└── index.php                    ← router principal + login redirect
```

---

## 4. Modelo de Roles y Permisos

### Jerarquía de roles (de mayor a menor)

```
SUPERADMIN
    └── GERENTE (por clínica)
            └── SUPERVISOR (por clínica)
                    └── OPERATIVO / PSICÓLOGO (por clínica)
                            └── REPRESENTANTE / PACIENTE (por paciente)
```

### Permisos detallados por rol

| Funcionalidad | SUPERADMIN | GERENTE | SUPERVISOR | OPERATIVO | REPRESENTANTE |
|---------------|:---:|:---:|:---:|:---:|:---:|
| Crear/editar clínicas | ✅ | ❌ | ❌ | ❌ | ❌ |
| Crear usuarios globales | ✅ | ❌ | ❌ | ❌ | ❌ |
| Asignar usuarios a clínica | ✅ | ❌ | ❌ | ❌ | ❌ |
| Dashboard KPIs gerencial | ✅ | ✅ | ❌ | ❌ | ❌ |
| Ver pacientes de la clínica | ✅ | ✅ | ✅ | ✅ | ❌ |
| Registrar nuevo paciente | ✅ | ✅ | ✅ | ✅ | ❌ |
| Tomar paciente sin autorización | ✅ | ✅ | ✅ | ❌ | ❌ |
| Tomar paciente (requiere auth) | ✅ | ✅ | ✅ | ✅* | ❌ |
| Completar historial clínico | ✅ | ✅ | ✅ | ✅ | ❌ |
| Ver cronogramas propios | ✅ | ✅ | ✅ | ✅ | ❌ |
| Ver cronogramas de otros | ✅ | ✅ | ✅ | ❌ | ❌ |
| Gestionar agenda/citas | ✅ | ✅ | ✅ | ✅ | ✅** |
| Ver propio historial | ✅ | ✅ | ✅ | ✅ | ✅ |
| Firmar consentimientos | ❌ | ❌ | ❌ | ❌ | ✅ |

> `*` OPERATIVO puede tomar un paciente pero requiere aprobación del SUPERVISOR  
> `**` REPRESENTANTE puede ver citas y confirmar/cancelar solo las propias

### Lógica de "tomar paciente"
```
1. Operativo solicita tomar paciente → estado: PENDIENTE_APROBACION
2. Sistema notifica al supervisor de la clínica
3. Supervisor aprueba → paciente asignado al operativo
4. Si rechaza → paciente queda libre dentro de la clínica
5. Supervisor y Gerente pueden tomar directamente sin aprobación
```

---

## 5. Modelo de Datos (Tablas Principales)

### Multiempresa / Clínicas
```sql
clinicas (id, nombre, ruc, direccion, telefono, email, logo, config_json, activo, created_at)
usuarios (id, cedula, nombre, apellido, email, password_hash, telefono, activo, created_at)
usuario_clinica (id, usuario_id, clinica_id, rol, activo, created_at)
-- rol: ENUM('superadmin','gerente','supervisor','operativo','representante')
```

### Pacientes
```sql
pacientes (
  id, clinica_id, codigo_paciente,  -- código autoincremental por clínica: CATPI-001
  nombre, apellido, fecha_nacimiento, sexo, cedula,
  representante_nombre, representante_cedula, representante_telefono,
  representante_email, representante_parentesco,
  motivo_consulta, fecha_ingreso,
  operativo_asignado_id, estado,  -- ENUM: activo, en_espera, egresado, suspendido
  consentimiento_archivo,
  usuario_representante_id,  -- FK a usuarios (auto-creado con cédula)
  created_by, created_at, updated_at, deleted_at
)
```

### Historial Clínico (flujo secuencial)
```sql
-- Paso 1: Anamnesis
anamnesis (id, paciente_id, clinica_id, operativo_id,
  -- Datos del paciente
  fecha_anamnesis,
  -- Antecedentes heredofamiliares
  antec_linea_materna, antec_linea_paterna,
  retardo_mental, trastornos_psiquiatricos, epilepsia, sindromes, prob_aprendizaje,
  -- Antecedentes prenatales
  embarazos, deseado, abortos, tiempo_gestacion, complicaciones,
  estado_emocional, enfermedades_prenatales_json, medicamentos,
  -- Antecedentes perinatales
  tipo_parto, sufrimiento_fetal, estado_recien_nacido_json,
  -- Antecedentes postnatales
  caidas, convulsiones, vacunas, enfermedades_postnatales,
  -- Desarrollo motriz (JSON con conductas + edades)
  desarrollo_motriz_json,
  -- Desarrollo del lenguaje (JSON)
  desarrollo_lenguaje_json,
  -- Desempeño comportamental (JSON checkboxes)
  comportamiento_json,
  -- Habilidades sociales (JSON checkboxes)
  habilidades_sociales_json,
  -- Historia escolar
  historia_escolar_json,
  -- Conformación familiar
  estado_padres, num_hermanos, con_quien_vive,
  observaciones, archivos_adjuntos_json,
  completado, created_at, updated_at
)

-- Paso 2: Evaluación psicológica
evaluaciones (id, paciente_id, clinica_id, operativo_id,
  nombre_test, fecha_aplicacion, resultados_cuantitativos_json,
  interpretacion_cualitativa, archivo_adjunto, created_at
)

-- Paso 3: Diagnóstico
diagnosticos (id, paciente_id, clinica_id, operativo_id,
  impresion_diagnostica, codigo_dsm5, codigo_cie10, codigo_cie11,
  nivel_severidad, diagnostico_diferencial, observaciones,
  fecha_diagnostico, created_at
)

-- Paso 4: Plan de intervención
planes_intervencion (id, paciente_id, clinica_id, operativo_id,
  objetivos_generales, objetivos_especificos_json,
  enfoque_terapeutico, tecnicas_json,
  frecuencia_sesiones, duracion_estimada_semanas,
  estado, -- ENUM: borrador, activo, completado, suspendido
  created_at, updated_at
)

-- Paso 5: Sesiones / Notas de evolución
sesiones (id, paciente_id, plan_id, clinica_id, operativo_id,
  fecha_sesion, numero_sesion,
  objetivo_sesion, tecnicas_aplicadas, respuesta_paciente,
  observaciones_clinicas, tareas_asignadas,
  asistio, -- ENUM: asistio, falto, cancelada, reprogramada
  created_at
)

-- Paso 6: Seguimiento
seguimientos (id, paciente_id, plan_id, clinica_id, operativo_id,
  fecha_evaluacion, escala_avance, -- 0-100
  objetivos_cumplidos_json, alertas_clinicas_json,
  observaciones, created_at
)
```

### Cronogramas y Tareas
```sql
cronogramas (id, paciente_id, plan_id, clinica_id, operativo_id,
  fecha_inicio, fecha_fin_estimada,
  total_sesiones, sesiones_completadas,
  estado, auto_generado, -- si fue generado por IA/sistema
  created_at, updated_at
)

tareas_cronograma (id, cronograma_id, paciente_id,
  titulo, descripcion, tipo, -- ENUM: sesion, evaluacion, tarea_hogar, seguimiento
  fecha_programada, fecha_completada,
  responsable_id, estado, -- ENUM: pendiente, en_progreso, completada, cancelada
  orden, created_at
)
```

### Citas / Agenda
```sql
citas (id, paciente_id, clinica_id, operativo_id,
  fecha_hora, duracion_minutos, tipo, -- ENUM: valoracion, sesion, seguimiento
  estado, -- ENUM: programada, confirmada, realizada, cancelada, no_asistio
  notas, created_by, created_at
)
```

---

## 6. Flujo Principal del Sistema (por módulo)

### Flujo de registro de paciente (OPERATIVO)
```
1. Operativo crea paciente → ingresa datos personales + representante
2. Sistema genera: código_paciente (ej: CATPI-2025-001)
3. Sistema crea usuario representante: usuario=cédula, pass=cédula (cambio obligatorio)
4. Operativo sube consentimiento informado firmado (PDF/imagen)
5. Estado: REGISTRADO → flujo habilitado
```

### Flujo clínico secuencial
```
REGISTRADO → ANAMNESIS → EVALUACIÓN → DIAGNÓSTICO → PLAN → SESIONES → SEGUIMIENTO
                ↑                           ↑
         (obligatorio)          (puede saltar si tiene diagnóstico previo)
```

Cada paso tiene: indicador visual de completado, fecha de última actualización, operativo responsable.

### Generación automática de cronograma
```
Cuando el plan de intervención se marca como ACTIVO:
1. Sistema lee: frecuencia_sesiones + duracion_estimada_semanas
2. Genera automáticamente N tareas del tipo "sesion" espaciadas
3. Operativo puede editar/ajustar las tareas generadas
4. Cronograma queda en estado: borrador_auto → operativo lo confirma
```

---

## 7. Dashboard por Rol

### Dashboard GERENTE / SUPERADMIN — KPIs
- Total pacientes activos / nuevos este mes
- Tasa de asistencia a sesiones (%)
- Pacientes con cronograma al día vs retrasados
- Operativos con más/menos carga de pacientes
- Porcentaje de objetivos cumplidos por plan
- Alertas clínicas activas
- Ingresos del mes (si aplica facturación)
- Gráfico: evolución de pacientes por mes (Chart.js línea)
- Gráfico: distribución por diagnóstico DSM-5/CIE (donut)
- Gráfico: avance promedio de cronogramas (barras)

### Dashboard SUPERVISOR
- Lista de operativos a su cargo
- Pacientes por operativo + estado de cronograma
- Solicitudes de toma de paciente pendientes de aprobar
- Pacientes con alertas clínicas
- Citas del día

### Dashboard OPERATIVO
- Mis pacientes + estado del flujo de cada uno
- Citas del día / semana
- Tareas pendientes del cronograma
- Pacientes con cronograma retrasado

### Portal REPRESENTANTE
- Ver citas programadas
- Ver tareas asignadas al paciente (para casa)
- Descargar reportes autorizados
- Confirmar/cancelar citas

---

## 8. Formularios Clave

### Anamnesis (formulario principal — CATPI)
Basado en el documento `Anamnesis_PSICOLOGÍA_CATPI.docx`:
- Sección 1: Datos generales (paciente, madre, padre, dirección)
- Sección 2: Antecedentes heredofamiliares (línea materna/paterna: retardo mental, psiquiátrico, epilepsia, síndromes, problemas de aprendizaje)
- Sección 3: Antecedentes prenatales (embarazos, estado emocional, enfermedades, medicamentos)
- Sección 4: Antecedentes perinatales (tipo de parto, estado del RN: coloración, peso, talla, llanto, succión, incubadora)
- Sección 5: Antecedentes postnatales (caídas, convulsiones, vacunas, enfermedades)
- Sección 6: Conformación familiar (estado padres, hermanos, con quién vive)
- Sección 7: Desarrollo motriz (tabla: conductas + edad — motricidad gruesa y fina)
- Sección 8: Desarrollo del lenguaje (tabla: habilidades + edad)
- Sección 9: Desempeño comportamental (20 conductas: SI/NO/Observaciones)
- Sección 10: Desempeño en habilidades sociales (20 habilidades: SI/NO/Observaciones)
- Sección 11: Historia escolar (inicio, desempeño, disciplina, horarios, repitencia, institución, tratamientos previos)

### Formularios opcionales adicionales
- Evaluación de pruebas (WISC-V, Test CARAS-R, TONI-2, etc.)
- Escala de valoración conductual
- Cuestionario de hábitos de sueño
- Inventario de síntomas para padres
- (Diseñados como plantillas configurables por clínica)

---

## 9. Reportes y Exportación

Todo reporte exportable en **PDF via mPDF o DOMPDF**:

| Reporte | Generado por | Acceso |
|---------|-------------|--------|
| Historia clínica completa | Sistema | Operativo+ |
| Informe psicológico | Operativo | Operativo+ |
| Certificado de atención | Operativo | Operativo+ |
| Informe para escuela/médico | Operativo | Operativo+ |
| Cronograma de sesiones | Sistema | Operativo+, Representante |
| KPI dashboard | Sistema | Gerente+ |

Plantillas de reportes en `/templates/reportes/` como HTML+CSS → convertidas a PDF.

---

## 10. Autenticación y Seguridad

```php
// Estructura de sesión
$_SESSION['user_id']
$_SESSION['clinica_activa_id']   // clinica seleccionada (puede cambiar)
$_SESSION['rol_activo']           // rol en esa clinica
$_SESSION['clinicas_disponibles'] // array de clinicas donde tiene acceso
```

- Login único → si usuario tiene múltiples clínicas, selecciona a cuál ingresar
- Contraseña: `password_hash()` con `PASSWORD_BCRYPT`
- Primer login de representante fuerza cambio de contraseña
- CSRF token en todos los formularios POST
- Rate limiting básico en login (bloqueo tras 5 intentos fallidos)
- Archivos sensibles (`/storage/`) fuera del webroot o con `.htaccess`

---

## 11. Convenciones de Nombrado

```
Tablas DB:          snake_case plural (pacientes, planes_intervencion)
Campos DB:          snake_case (fecha_nacimiento, operativo_id)
Archivos PHP:       kebab-case (registro-paciente.php, lista-citas.php)
Clases PHP:         PascalCase (PacienteModel, CitaController)
Funciones:          camelCase (getPacienteById, calcularAvanceCronograma)
Variables JS:       camelCase (pacienteData, citasFiltradas)
IDs HTML:           kebab-case (btn-guardar, form-anamnesis)
Clases CSS:         BEM o Bootstrap (sgc-card, sgc-badge--activo)
Constantes:         UPPER_SNAKE (ROL_OPERATIVO, ESTADO_ACTIVO)
```

---

## 12. APIs Externas (futuro)

| Integración | Propósito | Prioridad |
|-------------|-----------|-----------|
| WhatsApp Business API / Twilio | Recordatorios de citas | Media |
| PHPMailer + SMTP | Notificaciones email | Alta |
| Google Calendar API | Sincronizar agenda | Baja |

---

## 13. Sistemas de Referencia Estudiados

El sistema se basa en las mejores prácticas de:
- **OpenMRS** (estructura de historial clínico por encuentros)
- **SimplePractice** (flujo de sesiones + notas de evolución)
- **TherapyNotes** (plantillas de documentación clínica)
- **Doctoralia** (agenda y gestión de citas)
- **ClinicCloud** (multiclínica, roles y permisos)
- **ClinicalKey** (DSM-5/CIE-11 como referencia diagnóstica)

---

## 14. Roadmap de Desarrollo

### Fase 1 — Core (MVP)
- [ ] Auth: login, sesiones, multiclínica, roles
- [ ] Superadmin: CRUD clínicas y usuarios
- [ ] Pacientes: registro, consentimiento, usuario representante
- [ ] Anamnesis: formulario completo con todas las secciones
- [ ] Historial clínico: flujo secuencial (pasos 1-6)
- [ ] Citas: agenda básica con calendario

### Fase 2 — Gestión
- [ ] Cronogramas: generación automática + editor
- [ ] Dashboard gerente con KPIs y gráficas
- [ ] Dashboard supervisor con vista de operativos
- [ ] Flujo de aprobación para toma de paciente
- [ ] Exportación PDF de reportes

### Fase 3 — Optimización
- [ ] Portal representante (ver citas, tareas, reportes)
- [ ] Alertas clínicas automáticas
- [ ] Formularios opcionales configurables por clínica
- [ ] Notificaciones por email
- [ ] API pública documentada

---

## 15. Comandos Frecuentes de Desarrollo

```bash
# Deploy a Hostinger
git add . && git commit -m "feat: descripción" && git push origin main
# En servidor: git pull origin main

# Verificar sintaxis PHP
php -l api/pacientes/registro.php

# Crear estructura de BD
mysql -u root -p < database/schema.sql

# Composer (dependencias PHP)
composer install
composer require mpdf/mpdf
composer require phpmailer/phpmailer
```

---

## 16. Variables de Entorno (.env)

```env
DB_HOST=localhost
DB_NAME=sgc_clinica
DB_USER=sgc_user
DB_PASS=SECRETO

APP_ENV=production
APP_URL=https://tudominio.com/sgc
APP_KEY=SECRETO_32_CHARS

MAIL_HOST=smtp.hostinger.com
MAIL_USER=sistema@tudominio.com
MAIL_PASS=SECRETO
MAIL_FROM=noreply@tudominio.com

STORAGE_PATH=/home/user/storage_sgc  # fuera del webroot
MAX_UPLOAD_MB=20
```

> `.env` NUNCA se sube a Git. Usar `.env.example` como plantilla.

---

## 17. Notas Clínicas Importantes

- Los pacientes son principalmente **niños (NNA)** → siempre se requiere representante legal
- El consentimiento informado es **obligatorio** antes de iniciar el flujo clínico
- El código de diagnóstico debe referenciar **DSM-5, CIE-10 o CIE-11** (los tres coexisten)
- Los archivos médicos (anamnesis escaneada, evaluaciones) tienen retención mínima de **7 años**
- El acceso al historial clínico debe quedar **auditado** (quién vio qué y cuándo)
- La asignación de operativo ≠ alta exclusiva del paciente: todos los operativos de la clínica pueden ver el historial

---

*Última actualización: Abril 2025 — Kevin / Socket Studio S.A.S.*
