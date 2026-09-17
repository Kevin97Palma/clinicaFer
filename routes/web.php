<?php
declare(strict_types=1);

/** @var App\Core\Router $router */

use App\Controllers\AgendaController;
use App\Controllers\AlertController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\BackupController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\EvaluationController;
use App\Controllers\FileController;
use App\Controllers\PackageController;
use App\Controllers\PatientController;
use App\Controllers\PaymentController;
use App\Controllers\PlanController;
use App\Controllers\ReportController;
use App\Controllers\SessionController;
use App\Controllers\SettingsController;
use App\Controllers\TemplateController;
use App\Controllers\UserController;

// ── Autenticación ─────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'showLogin'], ['public' => true]);
$router->post('/login', [AuthController::class, 'login'], ['public' => true]);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/perfil/password', [AuthController::class, 'showPassword']);
$router->post('/perfil/password', [AuthController::class, 'updatePassword']);
$router->get('/marca/logo', [SettingsController::class, 'logo'], ['public' => true]);
$router->get('/marca/firma', [SettingsController::class, 'signature']);

// ── Dashboard ─────────────────────────────────────────────────
$router->get('/', [DashboardController::class, 'index'], ['perm' => 'dashboard.view']);

// ── Pacientes y expediente ────────────────────────────────────
$router->get('/pacientes', [PatientController::class, 'index'], ['perm' => 'patients.view']);
$router->get('/pacientes/buscar', [PatientController::class, 'search'], ['perm' => 'patients.view']);
$router->get('/pacientes/nuevo', [PatientController::class, 'create'], ['perm' => 'patients.manage']);
$router->post('/pacientes', [PatientController::class, 'store'], ['perm' => 'patients.manage']);
$router->get('/pacientes/{id}', [PatientController::class, 'show'], ['perm' => 'patients.view']);
$router->get('/pacientes/{id}/editar', [PatientController::class, 'edit'], ['perm' => 'patients.manage']);
$router->post('/pacientes/{id}', [PatientController::class, 'update'], ['perm' => 'patients.manage']);
$router->post('/pacientes/{id}/representantes', [PatientController::class, 'storeGuardian'], ['perm' => 'patients.manage']);
$router->post('/representantes/{id}', [PatientController::class, 'updateGuardian'], ['perm' => 'patients.manage']);
$router->post('/representantes/{id}/eliminar', [PatientController::class, 'deleteGuardian'], ['perm' => 'patients.manage']);
$router->post('/pacientes/{id}/anamnesis', [PatientController::class, 'saveAnamnesis'], ['perm' => 'clinical.manage']);
$router->post('/pacientes/{id}/diagnosticos', [PatientController::class, 'storeDiagnosis'], ['perm' => 'clinical.manage']);
$router->post('/diagnosticos/{id}', [PatientController::class, 'updateDiagnosis'], ['perm' => 'clinical.manage']);
$router->post('/pacientes/{id}/consentimientos', [PatientController::class, 'storeConsent'], ['perm' => 'patients.manage']);
$router->post('/consentimientos/{id}', [PatientController::class, 'updateConsent'], ['perm' => 'patients.manage']);

// ── Archivos ──────────────────────────────────────────────────
$router->post('/pacientes/{id}/archivos', [FileController::class, 'store'], ['perm' => 'files.manage']);
$router->get('/archivos/{id}/descargar', [FileController::class, 'download'], ['perm' => 'files.view']);
$router->post('/archivos/{id}/eliminar', [FileController::class, 'delete'], ['perm' => 'files.manage']);

// ── Agenda ────────────────────────────────────────────────────
$router->get('/agenda', [AgendaController::class, 'index'], ['perm' => 'agenda.view']);
$router->get('/agenda/eventos', [AgendaController::class, 'events'], ['perm' => 'agenda.view']);
$router->get('/agenda/citas/{id}', [AgendaController::class, 'show'], ['perm' => 'agenda.view']);
$router->post('/agenda/citas', [AgendaController::class, 'store'], ['perm' => 'agenda.manage']);
$router->post('/agenda/citas/{id}', [AgendaController::class, 'update'], ['perm' => 'agenda.manage']);
$router->post('/agenda/citas/{id}/estado', [AgendaController::class, 'status'], ['perm' => 'agenda.manage']);

// ── Sesiones ──────────────────────────────────────────────────
$router->get('/sesiones', [SessionController::class, 'index'], ['perm' => 'clinical.view']);
$router->get('/sesiones/nueva', [SessionController::class, 'create'], ['perm' => 'clinical.manage']);
$router->post('/sesiones', [SessionController::class, 'store'], ['perm' => 'clinical.manage']);
$router->get('/sesiones/{id}', [SessionController::class, 'show'], ['perm' => 'clinical.view']);
$router->get('/sesiones/{id}/editar', [SessionController::class, 'edit'], ['perm' => 'clinical.manage']);
$router->post('/sesiones/{id}', [SessionController::class, 'update'], ['perm' => 'clinical.manage']);

// ── Planes terapéuticos y revisiones de progreso ──────────────
$router->get('/planes', [PlanController::class, 'index'], ['perm' => 'clinical.view']);
$router->post('/pacientes/{id}/planes', [PlanController::class, 'store'], ['perm' => 'clinical.manage']);
$router->get('/planes/{id}', [PlanController::class, 'show'], ['perm' => 'clinical.view']);
$router->post('/planes/{id}', [PlanController::class, 'update'], ['perm' => 'clinical.manage']);
$router->post('/planes/{id}/areas', [PlanController::class, 'storeArea'], ['perm' => 'clinical.manage']);
$router->post('/areas/{id}/eliminar', [PlanController::class, 'deleteArea'], ['perm' => 'clinical.manage']);
$router->post('/areas/{id}/objetivos', [PlanController::class, 'storeObjective'], ['perm' => 'clinical.manage']);
$router->post('/objetivos/{id}', [PlanController::class, 'updateObjective'], ['perm' => 'clinical.manage']);
$router->post('/objetivos/{id}/eliminar', [PlanController::class, 'deleteObjective'], ['perm' => 'clinical.manage']);
$router->get('/pacientes/{id}/revisiones/nueva', [PlanController::class, 'createReview'], ['perm' => 'clinical.manage']);
$router->post('/pacientes/{id}/revisiones', [PlanController::class, 'storeReview'], ['perm' => 'clinical.manage']);
$router->get('/revisiones/{id}', [PlanController::class, 'showReview'], ['perm' => 'clinical.view']);

// ── Evaluaciones psicológicas ─────────────────────────────────
$router->get('/evaluaciones', [EvaluationController::class, 'index'], ['perm' => 'evaluations.view']);
$router->get('/evaluaciones/nueva', [EvaluationController::class, 'create'], ['perm' => 'evaluations.manage']);
$router->post('/evaluaciones', [EvaluationController::class, 'store'], ['perm' => 'evaluations.manage']);
$router->get('/evaluaciones/{id}', [EvaluationController::class, 'show'], ['perm' => 'evaluations.view']);
$router->post('/evaluaciones/{id}', [EvaluationController::class, 'update'], ['perm' => 'evaluations.manage']);
$router->post('/evaluaciones/{id}/instrumentos', [EvaluationController::class, 'storeInstrument'], ['perm' => 'evaluations.manage']);
$router->post('/instrumentos-aplicados/{id}', [EvaluationController::class, 'updateInstrument'], ['perm' => 'evaluations.manage']);
$router->post('/instrumentos-aplicados/{id}/eliminar', [EvaluationController::class, 'deleteInstrument'], ['perm' => 'evaluations.manage']);

// ── Documentos y plantillas ───────────────────────────────────
$router->get('/documentos', [DocumentController::class, 'index'], ['perm' => 'documents.view']);
$router->get('/documentos/nuevo', [DocumentController::class, 'create'], ['perm' => 'documents.manage']);
$router->post('/documentos', [DocumentController::class, 'store'], ['perm' => 'documents.manage']);
$router->get('/documentos/{id}', [DocumentController::class, 'show'], ['perm' => 'documents.view']);
$router->get('/documentos/{id}/editar', [DocumentController::class, 'edit'], ['perm' => 'documents.manage']);
$router->post('/documentos/{id}', [DocumentController::class, 'update'], ['perm' => 'documents.manage']);
$router->post('/documentos/{id}/estado', [DocumentController::class, 'status'], ['perm' => 'documents.manage']);
$router->get('/documentos/{id}/imprimir', [DocumentController::class, 'printView'], ['perm' => 'documents.view']);
$router->get('/plantillas', [TemplateController::class, 'index'], ['perm' => 'templates.manage']);
$router->get('/plantillas/nueva', [TemplateController::class, 'create'], ['perm' => 'templates.manage']);
$router->post('/plantillas', [TemplateController::class, 'store'], ['perm' => 'templates.manage']);
$router->get('/plantillas/{id}/editar', [TemplateController::class, 'edit'], ['perm' => 'templates.manage']);
$router->post('/plantillas/{id}', [TemplateController::class, 'update'], ['perm' => 'templates.manage']);

// ── Pagos, paquetes y desglose ────────────────────────────────
$router->get('/pagos', [PaymentController::class, 'index'], ['perm' => 'payments.view']);
$router->post('/pagos', [PaymentController::class, 'store'], ['perm' => 'payments.manage']);
$router->post('/pagos/{id}', [PaymentController::class, 'update'], ['perm' => 'payments.manage']);
$router->post('/pagos/{id}/cobrar', [PaymentController::class, 'markPaid'], ['perm' => 'payments.manage']);
$router->post('/pagos/{id}/anular', [PaymentController::class, 'void'], ['perm' => 'payments.void']);
$router->get('/pagos/desglose', [PaymentController::class, 'breakdown'], ['perm' => 'payments.view']);
$router->post('/pagos/desglose/documento', [PaymentController::class, 'breakdownDocument'], ['perm' => 'documents.manage']);
$router->get('/paquetes', [PackageController::class, 'index'], ['perm' => 'payments.view']);
$router->post('/paquetes', [PackageController::class, 'store'], ['perm' => 'payments.manage']);
$router->get('/paquetes/{id}', [PackageController::class, 'show'], ['perm' => 'payments.view']);
$router->post('/paquetes/{id}/ajustar', [PackageController::class, 'adjust'], ['perm' => 'packages.adjust']);
$router->post('/paquetes/{id}/estado', [PackageController::class, 'status'], ['perm' => 'payments.manage']);

// ── Alertas, reportes, auditoría, respaldos ───────────────────
$router->get('/alertas', [AlertController::class, 'index'], ['perm' => 'alerts.view']);
$router->post('/alertas/recalcular', [AlertController::class, 'recalculate'], ['perm' => 'alerts.view']);
$router->post('/alertas/{id}/estado', [AlertController::class, 'status'], ['perm' => 'alerts.view']);
$router->get('/reportes', [ReportController::class, 'index'], ['perm' => 'reports.view']);
$router->get('/reportes/exportar', [ReportController::class, 'export'], ['perm' => 'reports.view']);
$router->get('/auditoria', [AuditController::class, 'index'], ['perm' => 'audit.view']);
$router->get('/respaldos', [BackupController::class, 'index'], ['perm' => 'backups.manage']);
$router->post('/respaldos', [BackupController::class, 'store'], ['perm' => 'backups.manage']);
$router->get('/respaldos/{id}/descargar', [BackupController::class, 'download'], ['perm' => 'backups.manage']);

// ── Configuración y usuarios ──────────────────────────────────
$router->get('/configuracion', [SettingsController::class, 'index'], ['perm' => 'settings.manage']);
$router->post('/configuracion', [SettingsController::class, 'update'], ['perm' => 'settings.manage']);
$router->get('/configuracion/roles', [SettingsController::class, 'roles'], ['perm' => 'settings.manage']);
$router->post('/configuracion/roles', [SettingsController::class, 'updateRoles'], ['perm' => 'settings.manage']);
$router->get('/configuracion/instrumentos', [SettingsController::class, 'instruments'], ['perm' => 'evaluations.manage']);
$router->post('/configuracion/instrumentos', [SettingsController::class, 'storeInstrument'], ['perm' => 'evaluations.manage']);
$router->post('/configuracion/instrumentos/{id}', [SettingsController::class, 'updateInstrument'], ['perm' => 'evaluations.manage']);
$router->get('/usuarios', [UserController::class, 'index'], ['perm' => 'users.manage']);
$router->post('/usuarios', [UserController::class, 'store'], ['perm' => 'users.manage']);
$router->post('/usuarios/{id}', [UserController::class, 'update'], ['perm' => 'users.manage']);
