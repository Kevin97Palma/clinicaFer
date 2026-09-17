# Arquitectura — Sistema de gestión para consulta psicológica

> Documento de referencia: módulos, modelo de datos, flujos y reglas de automatización.

---

## 1. Arquitectura general

```
Navegador (HTML + Bootstrap 5 + JS vanilla + fetch)
        │
        ▼
public/index.php ── front controller
        │
        ├── app/Core/Router  ──► middleware (Auth, permisos, CSRF)
        │                          │
        │                          ▼
        ├── app/Controllers  ──► app/Services (lógica de negocio y transacciones)
        │                          │
        │                          ├── app/Models (consultas de dominio)
        │                          └── app/Core/Database (PDO + prepared statements)
        │
        └── resources/views  ──► layouts + vistas + parciales
```

- **Sin framework externo ni Composer**: PHP 8 puro, compatible con hosting compartido.
- **Separación**: los controladores no escriben SQL complejo; las operaciones que tocan varias tablas viven en `app/Services` y se ejecutan dentro de una transacción.
- **Todo dato que sale a pantalla pasa por `e()`**; todo dato que entra pasa por `Validator`.

## 2. Módulos

| Módulo | Controlador | Permisos |
|---|---|---|
| Dashboard | `DashboardController` | `dashboard.view` |
| Agenda | `AgendaController` | `agenda.view`, `agenda.manage` |
| Pacientes y expediente | `PatientController` | `patients.*`, `clinical.*` |
| Sesiones | `SessionController` | `clinical.view`, `clinical.manage` |
| Planes y revisiones | `PlanController` | `clinical.*` |
| Evaluaciones | `EvaluationController` | `evaluations.*` |
| Documentos y plantillas | `DocumentController`, `TemplateController` | `documents.*`, `templates.manage` |
| Pagos, paquetes, desglose | `PaymentController`, `PackageController` | `payments.*`, `packages.adjust` |
| Archivos y consentimientos | `FileController`, `PatientController` | `files.*` |
| Alertas | `AlertController` | `alerts.view` |
| Reportes | `ReportController` | `reports.view` |
| Auditoría / respaldos / config / usuarios | `AuditController`, `BackupController`, `SettingsController`, `UserController` | `audit.view`, `backups.manage`, `settings.manage`, `users.manage` |

## 3. Modelo de datos (30 tablas)

**Seguridad:** `roles`, `permissions`, `role_permissions`, `users`, `settings`
**Pacientes:** `patients`, `patient_guardians`, `anamnesis`, `diagnoses`
**Terapia:** `treatment_plans` → `treatment_areas` → `treatment_objectives`, `progress_reviews`
**Atención:** `appointments`, `therapy_sessions`, `therapy_session_objectives`
**Evaluación:** `instrument_catalog`, `psychological_evaluations`, `evaluation_instruments`
**Documentos:** `document_templates`, `documents`, `document_versions`
**Finanzas:** `payments`, `session_packages`, `package_movements`
**Expediente:** `patient_files`, `consents`
**Sistema:** `alerts`, `audit_logs`, `backup_logs`

Relaciones principales:

```
users ──< patients >── patient_guardians
   │         │
   │         ├──1:1── anamnesis
   │         ├──< diagnoses
   │         ├──< treatment_plans ──< treatment_areas ──< treatment_objectives
   │         │                                    ▲
   │         ├──< appointments ──1:1── therapy_sessions ──< therapy_session_objectives
   │         │                              │
   │         ├──< session_packages ──< package_movements
   │         ├──< payments ─────────────────┘
   │         ├──< psychological_evaluations ──< evaluation_instruments ──> patient_files
   │         ├──< documents ──< document_versions
   │         ├──< patient_files, consents, progress_reviews, alerts
   └──< audit_logs, backup_logs
```

Convenciones: claves foráneas con `ON DELETE CASCADE` hacia el paciente, índices en campos de búsqueda (`file_number`, `identification`, apellidos, fechas), `created_at` / `updated_at`, auditoría con `created_by` / `updated_by`, `sessions_remaining` como columna generada.

## 4. Flujo principal (una sola captura de datos)

```
AGENDA → paciente asiste → marcar atendida → REGISTRAR SESIÓN
   │
   └─ BEGIN TRANSACTION
        guardar sesión (número correlativo automático)
        relacionar objetivos trabajados y actualizar su estado
        descontar del paquete activo + registrar movimiento
        actualizar la cita (atendida / no asistió / cancelada)
        actualizar last_session_at y sessions_count del paciente
        registrar cobro pendiente si no hay paquete
        programar próxima cita (valida cruce de horarios)
        generar/resolver alertas
      COMMIT   (cualquier fallo → ROLLBACK completo)
```

## 5. Reglas de automatización de alertas

| Tipo | Se crea cuando | Se resuelve sola cuando |
|---|---|---|
| `INACTIVIDAD_PACIENTE` | Paciente activo sin sesión ni cita futura en N días (configurable) | Se registra sesión o se agenda cita |
| `PAQUETE_POR_FINALIZAR` | Paquete activo con ≤ 1 sesión restante | Se compra otro paquete o cambia el estado |
| `EVALUACION_PENDIENTE` | Evaluación en proceso con más de 21 días | La evaluación se finaliza |
| `INFORME_PENDIENTE` | Evaluación finalizada sin entregar, o documento en borrador > 3 días | Se emite el informe/documento |
| `REVISION_TERAPEUTICA` | N sesiones desde la última revisión (configurable), o plan con fecha de revisión vencida | Se registra la evaluación de progreso |
| `PAGO_PENDIENTE` | Pago pendiente con más de 7 días | Se cobra o se anula |

Las reglas se ejecutan como máximo cada 10 minutos por visita y nunca duplican una alerta abierta para la misma referencia.

## 6. Estrategia de seguridad

- PDO con **consultas preparadas** en el 100 % de las operaciones.
- **CSRF** obligatorio en todo POST (formulario o cabecera `X-CSRF-Token`).
- `password_hash()` / `password_verify()`, `session_regenerate_id(true)` en login y cambio de contraseña.
- Bloqueo temporal tras 5 intentos fallidos; cierre de sesión por inactividad.
- Permisos verificados **en la ruta** y nuevamente en las vistas (`can()`).
- Escape de salida con `e()`; el HTML de documentos se sanea por lista blanca (`DocumentService::sanitize`).
- Archivos fuera del webroot, nombre aleatorio, validación de extensión + MIME real + tamaño; descarga solo por controlador con auditoría.
- `.env` fuera de Git; `app/`, `config/`, `database/`, `resources/`, `storage/` no accesibles por URL.
- Auditoría de operaciones sensibles registrando **solo nombres de campos**, nunca contenido clínico.

## 7. Reglas clínicas incorporadas

- El sistema **no genera diagnósticos** ni interpreta puntajes: la interpretación se redacta y valida profesionalmente.
- El porcentaje del plan es **cumplimiento administrativo de objetivos**, no una medición psicométrica ni de recuperación; el texto aparece junto al indicador en la interfaz.
- Los diagnósticos históricos no se eliminan: se marcan inactivos o descartados.
- Los pagos no se eliminan: se anulan con motivo y auditoría.
- Los documentos emitidos guardan una copia inmutable por versión.

## 8. Fases de desarrollo (estado)

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Arquitectura, BD, configuración, login, usuarios, roles, seguridad | ✅ |
| 2 | Pacientes, representantes, expediente, anamnesis, diagnósticos | ✅ |
| 3 | Agenda, sesiones, planes, objetivos, evaluación de progreso | ✅ |
| 4 | Evaluaciones psicológicas, instrumentos, resultados | ✅ |
| 5 | Pagos, paquetes, movimientos, alertas | ✅ |
| 6 | Documentos, plantillas, impresión/PDF, archivos, consentimientos | ✅ |
| 7 | Dashboard, reportes, auditoría, respaldos, configuración | ✅ |
| 8 | Pruebas, responsive, ajustes de seguridad | ✅ (revisión continua) |

Pendiente opcional: envío de correos (PHPMailer), recordatorios por WhatsApp y generación de PDF en servidor (hoy se usa *imprimir → guardar como PDF*, que no requiere Composer).
