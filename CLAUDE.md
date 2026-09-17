# CLAUDE.md — Sistema de gestión para consulta psicológica

> Contexto del proyecto para Claude Code. Leer antes de cualquier tarea.
> Detalle técnico completo en [docs/arquitectura.md](docs/arquitectura.md) y [README.md](README.md).

---

## 1. Identidad

**Qué es:** sistema web para la gestión diaria de una consulta psicológica infantil y adolescente
(agenda, expediente clínico, sesiones, planes terapéuticos, evaluaciones, documentos, pagos y paquetes).
**Producción:** https://socket-studio.com/demo-aplicaciones/SistemaClinico
**Repositorio:** github.com/Kevin97Palma/clinicaFer (rama `master`)
**Reescritura completa:** septiembre 2026, sobre el prompt maestro del cliente. La versión anterior
(SGC multiclínica, carpetas `api/`, `modules/`, `includes/`, `assets/`) quedó obsoleta.

## 2. Stack y restricciones del servidor

| Capa | Tecnología |
|---|---|
| Backend | PHP **8.0.30** (VPS 31.97.102.179, AlmaLinux 9) — sin framework, sin Composer |
| BD | MySQL 8.0, base `sgc_clinica`, usuario `psico_app@localhost` |
| Frontend | HTML5 + Bootstrap 5.3 + Bootstrap Icons + JS vanilla (fetch) |
| Librerías CDN | FullCalendar 6 (agenda), Quill 2 (editor de documentos), Chart.js 4 (reportes) |
| Servidor | nginx (443) → Apache 8080, `AllowOverride All` |

**No usar sintaxis posterior a PHP 8.0** (nada de `readonly`, enums, `never`, propiedades en constructor con promoción de readonly).
**PDF:** no hay Composer; los documentos se imprimen con `window.print()` (Guardar como PDF).
**PHP local es 7.1** (XAMPP): no sirve para probar; validar con `php -l` en el servidor.

## 3. Estructura

```
app/{bootstrap.php,Core,Controllers,Models,Services,Middleware,Helpers}
config/{app.php,database.php,catalogs.php}
public/{index.php,.htaccess,assets}       ← único directorio expuesto
resources/views/{layouts,partials,...}
routes/web.php                            ← ruta + permiso requerido
database/{database.sql,seed.sql,crear-usuario.php}
docs/arquitectura.md
```

Almacenamiento real en producción: `/var/www/psicologia-storage` (fuera del webroot, dueño `apache`).
Configuración sensible en `.env` del servidor (nunca en Git).

## 4. Reglas de código obligatorias

- **PDO con prepared statements siempre**; nada de concatenar SQL. Los nombres de columna en
  `Database::insert/update` provienen del código, nunca del usuario.
- **Toda salida se escapa con `e()`**; el HTML de documentos pasa por `DocumentService::sanitize()`.
- **Toda ruta declara su permiso** en `routes/web.php`; las vistas vuelven a comprobar con `can()`.
- **CSRF** automático en POST (middleware). Formularios con `csrf_field()`; AJAX con `X-CSRF-Token`.
- **Operaciones multitabla dentro de `Database::transaction()`** y en `app/Services`, no en controladores.
- **Auditar operaciones sensibles** con `AuditService::log()` (solo nombres de campos, nunca contenido clínico).
- **No borrar información con valor legal**: pagos → `anulado`; diagnósticos → `inactivo`/`descartado`;
  archivos → `deleted_at`; documentos → versión inmutable por emisión.
- Etiquetas de ENUM en `config/catalogs.php` (`label()`, `badge()`, `select_options()`), nunca escritas a mano en vistas.
- Comentarios en español para lógica de negocio; nombres de tablas/campos en inglés `snake_case`.
- Interfaz en español, con tono profesional y cálido (no infantil).

## 5. Roles

`administrador` (todo) · `psicologo` (clínico + documentos + pagos) · `asistente` (agenda, pacientes, pagos, sin notas clínicas).
Permisos editables en Configuración → Roles y permisos; el administrador siempre conserva todo.

## 6. Automatización central (no romper)

Al guardar una sesión atendida, dentro de una sola transacción: número correlativo → objetivos
del plan → descuento del paquete + movimiento → estado de la cita → `sessions_count` y
`last_session_at` del paciente → cobro pendiente si no hay paquete → próxima cita (valida cruce
de horarios) → alertas. Ver `app/Services/TherapySessionService.php`.

Las alertas se generan y **se resuelven solas** por reglas en `app/Services/AlertService.php`
(inactividad, paquete por finalizar, evaluación/informe pendiente, revisión terapéutica, pago pendiente).

## 7. Reglas clínicas (obligatorias en cualquier cambio)

- No generar diagnósticos ni interpretar puntajes automáticamente; la interpretación la redacta la profesional.
- No presentar el porcentaje del plan como medición psicométrica ni como mejoría clínica
  (es cumplimiento administrativo de objetivos y debe decirlo en pantalla).
- Diferenciar visiblemente información clínica, administrativa y financiera.
- Conservar trazabilidad de toda modificación relevante.

## 8. Despliegue

`git push` **no** despliega. Producción se actualiza por SSH/SFTP con la llave `~/.ssh/deploy_socketweb`:

```bash
# archivo suelto
scp -i ~/.ssh/deploy_socketweb <archivo> palma@31.97.102.179:/var/www/html/socket-studio/demo-aplicaciones/SistemaClinico/<ruta>
# verificar sintaxis en el servidor (PHP 8.0)
ssh -i ~/.ssh/deploy_socketweb root@31.97.102.179 'php -l /var/www/html/socket-studio/demo-aplicaciones/SistemaClinico/<ruta>'
```

Respaldos previos a cambios grandes en `/root/backups-sgc/` (código y dump de la BD).

---

*Socket Studio S.A.S. — última actualización: septiembre 2026*
