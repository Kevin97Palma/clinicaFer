<?php
/**
 * SGC — Definición de roles y permisos
 */

/**
 * Jerarquía numérica: mayor = más permisos.
 * Útil para comparaciones del tipo "¿tiene al menos este nivel?".
 */
define('JERARQUIA_ROLES', [
    ROL_REPRESENTANTE => 1,
    ROL_OPERATIVO     => 2,
    ROL_SUPERVISOR    => 3,
    ROL_GERENTE       => 4,
    ROL_SUPERADMIN    => 5,
]);

/**
 * Permisos por módulo y acción.
 * Clave: 'modulo.accion'
 * Valor: array de roles con acceso.
 */
define('PERMISOS', [
    // Clínicas
    'clinicas.crear'       => [ROL_SUPERADMIN],
    'clinicas.editar'      => [ROL_SUPERADMIN],
    'clinicas.ver'         => [ROL_SUPERADMIN],

    // Usuarios globales
    'usuarios.crear'       => [ROL_SUPERADMIN],
    'usuarios.asignar'     => [ROL_SUPERADMIN],
    'usuarios.ver'         => [ROL_SUPERADMIN, ROL_GERENTE],

    // Dashboard KPIs
    'dashboard.kpis'       => [ROL_SUPERADMIN, ROL_GERENTE],
    'dashboard.supervisor' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR],
    'dashboard.operativo'  => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],

    // Pacientes
    'pacientes.ver_lista'  => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'pacientes.registrar'  => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'pacientes.tomar_directo' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR],
    'pacientes.tomar_solicitud' => [ROL_OPERATIVO],

    // Historial clínico
    'historial.ver'        => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'historial.editar'     => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'anamnesis.completar'  => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'evaluaciones.gestionar' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'diagnosticos.gestionar' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'planes.gestionar'     => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'sesiones.gestionar'   => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'seguimientos.gestionar' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],

    // Cronogramas
    'cronogramas.ver_propio' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'cronogramas.ver_todos' => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR],

    // Citas
    'citas.gestionar'      => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'citas.ver_propias'    => [ROL_REPRESENTANTE],
    'citas.confirmar'      => [ROL_REPRESENTANTE],

    // Solicitudes de aprobación
    'solicitudes.aprobar'  => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR],

    // Portal representante
    'portal.ver_historial' => [ROL_REPRESENTANTE],
    'portal.firmar_consentimiento' => [ROL_REPRESENTANTE],
    'portal.ver_tareas'    => [ROL_REPRESENTANTE],
    'portal.descargar_reportes' => [ROL_REPRESENTANTE],

    // Reportes
    'reportes.generar'     => [ROL_SUPERADMIN, ROL_GERENTE, ROL_SUPERVISOR, ROL_OPERATIVO],
    'reportes.kpi'         => [ROL_SUPERADMIN, ROL_GERENTE],
]);

/**
 * Verifica si el rol activo tiene permiso para una acción.
 */
function tienePermiso(string $accion): bool
{
    $rol = $_SESSION['rol_activo'] ?? null;
    if (!$rol) return false;
    $roles = PERMISOS[$accion] ?? [];
    return in_array($rol, $roles);
}

/**
 * Verifica que el rol activo tenga al menos el nivel indicado.
 */
function tieneNivel(string $rol_minimo): bool
{
    $rol_actual = $_SESSION['rol_activo'] ?? null;
    if (!$rol_actual) return false;
    $jerarquia = JERARQUIA_ROLES;
    return ($jerarquia[$rol_actual] ?? 0) >= ($jerarquia[$rol_minimo] ?? 999);
}
