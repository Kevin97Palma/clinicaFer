# Sistema de gestión para consulta psicológica

Plataforma web para la gestión diaria de una consulta psicológica infantil y adolescente:
agenda, expediente clínico, sesiones terapéuticas, planes de intervención, evaluaciones,
documentos, pagos y paquetes de sesiones, con alertas automáticas y auditoría.

**Filosofía:** registrar una sola vez y reutilizar la información. Desde una cita atendida se
registra la sesión y el sistema actualiza evolución, objetivos, paquete, pagos y alertas en una
sola operación.

---

## 1. Requisitos

- PHP 8.0 o superior (`pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zlib`)
- MySQL 8.0 o superior
- Apache con `mod_rewrite` y `AllowOverride All` (o Nginx apuntando a `public/`)
- No requiere Composer ni Node

## 2. Instalación

```bash
# 1. Clonar el repositorio
git clone <repo> sistema-psicologia && cd sistema-psicologia

# 2. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE psicologia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Importar estructura (y datos de prueba, opcional)
mysql -u root -p psicologia < database/database.sql
mysql -u root -p psicologia < database/seed.sql

# 4. Configurar el entorno
cp .env.example .env    # editar credenciales y APP_URL

# 5. Crear el almacenamiento FUERA del directorio público
sudo mkdir -p /var/www/psicologia-storage/{uploads,documents,backups,logs,branding}
sudo chown -R www-data:www-data /var/www/psicologia-storage   # en AlmaLinux/CentOS: apache:apache
sudo chmod -R 750 /var/www/psicologia-storage

# 6. Crear el usuario administrador (imprime una contraseña aleatoria)
php database/crear-usuario.php --usuario=admin --nombre="Nombre Apellido" --email=correo@dominio.com --rol=administrador
```

### Servidor web

- **Apache**: apunte el `DocumentRoot` a `public/`. Si apunta a la raíz del proyecto, el
  `.htaccess` de la raíz reescribe todo hacia `public/`, de modo que `app/`, `config/`,
  `database/` y `.env` nunca son accesibles por URL.
- **Nginx**:

```nginx
root /ruta/al/proyecto/public;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php-fpm/www.sock; include fastcgi_params;
                    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

### Recomendaciones para producción

- `APP_ENV=production` y `APP_DEBUG=false` (nunca se muestran SQL ni credenciales en pantalla).
- Usuario de MySQL exclusivo para la aplicación, con permisos solo sobre su base de datos.
- HTTPS obligatorio (las cookies de sesión se marcan `secure` automáticamente).
- Respaldos periódicos desde **Configuración → Respaldos** y copia externa del archivo.
- `storage` fuera del webroot y `.env` con permisos `640`.

## 3. Estructura del proyecto

```
├── app/
│   ├── bootstrap.php          Arranque: entorno, autoload, errores, sesión, cabeceras
│   ├── Core/                  Router, Database (PDO), Controller, View, Auth, Validator, Env, Config
│   ├── Controllers/           Un controlador por módulo
│   ├── Models/                Consultas de dominio (Patient)
│   ├── Services/              Reglas de negocio y transacciones
│   ├── Middleware/            Autenticación, permisos y CSRF
│   └── Helpers/functions.php  e(), url(), can(), money(), badge(), …
├── config/                    app.php, database.php, catalogs.php (etiquetas de los ENUM)
├── public/                    Único directorio expuesto: index.php + assets
├── resources/views/           Layouts, vistas y parciales
├── routes/web.php             Tabla de rutas con su permiso
├── database/                  database.sql, seed.sql, crear-usuario.php
├── storage/                   Solo si no se define STORAGE_PATH externo
└── docs/arquitectura.md       Módulos, modelo de datos, flujos, seguridad
```

## 4. Roles

| Rol | Alcance |
|---|---|
| **Administrador** | Acceso completo: configuración, usuarios, permisos, auditoría y respaldos |
| **Psicólogo** | Toda la información clínica y terapéutica, documentos, evaluaciones y pagos |
| **Asistente** | Agenda, pacientes, representantes y pagos. Sin acceso a notas clínicas |

Los permisos por rol se editan desde **Configuración → Roles y permisos** (el rol Administrador
siempre conserva acceso completo).

## 5. Flujo de trabajo diario

```
Iniciar sesión → Agenda del día → Abrir paciente → Atender → Registrar sesión
   → evolución, objetivos y paquete se actualizan solos
   → registrar o consultar pago → programar próxima cita → siguiente paciente
```

El expediente del paciente es el centro del sistema: desde un solo panel con pestañas
(Resumen, Anamnesis, Sesiones, Evaluaciones, Plan terapéutico, Documentos, Pagos, Agenda,
Archivos) se puede registrar una sesión, agendar, cobrar, subir archivos o emitir un documento
sin volver a buscar al paciente.

## 6. Documentos y PDF

Los documentos se generan desde plantillas con variables (`{{paciente_nombre}}`,
`{{expediente}}`, `{{diagnostico}}`, …), se pueden editar antes de guardar y cada emisión
almacena una **copia inmutable**: si luego cambian los datos del paciente, el documento
histórico no se modifica. Para obtener el PDF se usa *Imprimir → Guardar como PDF* del
navegador, con membrete y firma configurables (no requiere librerías adicionales).

## 7. Datos de prueba

`database/seed.sql` carga pacientes, representantes, citas, sesiones, planes, objetivos,
evaluaciones, pagos, paquetes y documentos **ficticios**. Los usuarios del seed no tienen
contraseña utilizable: se asigna con `database/crear-usuario.php`.

## 8. Comandos útiles

```bash
# Crear usuario o restablecer contraseña (imprime la clave una sola vez)
php database/crear-usuario.php --usuario=psicologa --nombre="Nombre" --email=correo@dominio.com --rol=psicologo
php database/crear-usuario.php --usuario=psicologa --forzar-cambio

# Verificar sintaxis
find app config public routes database -name "*.php" -print0 | xargs -0 -n1 php -l
```

## 9. Notas clínicas importantes

El software es una **herramienta de registro y gestión, no un sustituto del juicio clínico**:
no genera diagnósticos automáticos, no convierte puntajes en diagnósticos, no interpreta
técnicas proyectivas y el porcentaje del plan expresa cumplimiento administrativo de objetivos,
no mejoría clínica.
