<?php
declare(strict_types=1);

/**
 * Catálogos de valores (ENUM de la BD) con etiqueta visible y tono del badge.
 * Tonos: success, info, warning, danger, neutral, primary, accent
 */
$c = fn(string $label, string $tone = 'neutral') => ['label' => $label, 'tone' => $tone];

return [
    'patient_status' => [
        'activo' => $c('Activo', 'success'), 'pausa' => $c('Pausa', 'warning'), 'alta' => $c('Alta', 'info'),
        'derivado' => $c('Derivado', 'accent'), 'inactivo' => $c('Inactivo', 'neutral'),
    ],
    'sex' => ['femenino' => $c('Femenino'), 'masculino' => $c('Masculino'), 'otro' => $c('Otro')],
    'relationship' => ['madre' => $c('Madre'), 'padre' => $c('Padre'), 'tutor' => $c('Tutor'), 'otro' => $c('Otro')],
    'anamnesis_status' => ['borrador' => $c('En progreso', 'warning'), 'completa' => $c('Completa', 'success')],
    'classification_system' => ['DSM-5-TR' => $c('DSM-5-TR'), 'CIE-10' => $c('CIE-10'), 'CIE-11' => $c('CIE-11'), 'Otro' => $c('Otro')],
    'diagnosis_type' => [
        'presuntivo' => $c('Presuntivo', 'warning'), 'diferencial' => $c('Diferencial', 'info'),
        'confirmado' => $c('Confirmado', 'primary'), 'descartado' => $c('Descartado', 'neutral'),
    ],
    'diagnosis_status' => ['activo' => $c('Activo', 'success'), 'inactivo' => $c('Inactivo', 'neutral')],
    'appointment_type' => [
        'primera_consulta' => $c('Primera consulta'), 'sesion' => $c('Sesión'), 'evaluacion' => $c('Evaluación'),
        'devolucion' => $c('Devolución de resultados'), 'seguimiento' => $c('Seguimiento'), 'padres' => $c('Sesión con padres'), 'otra' => $c('Otra'),
    ],
    'appointment_status' => [
        'programada' => $c('Programada', 'info'), 'confirmada' => $c('Confirmada', 'primary'), 'atendida' => $c('Atendida', 'success'),
        'cancelada' => $c('Cancelada', 'neutral'), 'no_asistio' => $c('No asistió', 'danger'), 'reprogramada' => $c('Reprogramada', 'warning'),
    ],
    'modality' => [
        'presencial' => $c('Presencial'), 'virtual' => $c('Virtual'), 'familiar' => $c('Familiar'),
        'padres' => $c('Padres'), 'escolar' => $c('Escolar'), 'otra' => $c('Otra'),
    ],
    'emotional_state' => [
        'regulado' => $c('Regulado', 'success'), 'neutral' => $c('Neutral'), 'ansioso' => $c('Ansioso', 'warning'),
        'irritable' => $c('Irritable', 'danger'), 'triste' => $c('Triste', 'info'), 'otro' => $c('Otro'),
    ],
    'participation' => ['alta' => $c('Alta', 'success'), 'media' => $c('Media', 'warning'), 'baja' => $c('Baja', 'danger')],
    'progress' => [
        'logrado' => $c('Logrado', 'success'), 'en_proceso' => $c('En proceso', 'info'), 'requiere_intervencion' => $c('Requiere intervención', 'warning'),
    ],
    'attendance' => [
        'atendido' => $c('Atendido', 'success'), 'cancelado' => $c('Cancelado', 'neutral'),
        'no_asistio' => $c('No asistió', 'danger'), 'reprogramado' => $c('Reprogramado', 'warning'),
    ],
    'plan_status' => ['activo' => $c('Activo', 'success'), 'finalizado' => $c('Finalizado', 'info'), 'suspendido' => $c('Suspendido', 'neutral')],
    'objective_status' => ['pendiente' => $c('Pendiente', 'neutral'), 'en_proceso' => $c('En proceso', 'info'), 'logrado' => $c('Logrado', 'success')],
    'treatment_area' => [
        'Regulación emocional' => $c('Regulación emocional'), 'Control inhibitorio' => $c('Control inhibitorio'),
        'Habilidades sociales' => $c('Habilidades sociales'), 'Ansiedad' => $c('Ansiedad'), 'Autoestima' => $c('Autoestima'),
        'Funciones ejecutivas' => $c('Funciones ejecutivas'), 'Comunicación' => $c('Comunicación'), 'Conducta' => $c('Conducta'),
        'Habilidades parentales' => $c('Habilidades parentales'), 'Autonomía' => $c('Autonomía'),
    ],
    'general_status' => [
        'mejor' => $c('Mejor', 'success'), 'sin_cambios' => $c('Sin cambios significativos', 'neutral'),
        'retroceso' => $c('Retroceso', 'danger'), 'info_insuficiente' => $c('Información insuficiente', 'warning'),
    ],
    'clinical_decision' => [
        'mantener_plan' => $c('Mantener plan'), 'modificar_objetivos' => $c('Modificar objetivos'), 'cambiar_frecuencia' => $c('Cambiar frecuencia'),
        'derivar' => $c('Derivar'), 'preparar_alta' => $c('Preparar alta'), 'continuar_evaluacion' => $c('Continuar evaluación'),
    ],
    'evaluation_area' => [
        'cognitiva' => $c('Cognitiva'), 'conductual' => $c('Conductual'), 'emocional' => $c('Emocional'),
        'neurodesarrollo' => $c('Neurodesarrollo'), 'personalidad' => $c('Personalidad'), 'adaptativa' => $c('Adaptativa'),
        'parental' => $c('Parental'), 'academica' => $c('Académica'), 'otra' => $c('Otra'),
    ],
    'evaluation_status' => [
        'en_proceso' => $c('En proceso', 'info'), 'finalizada' => $c('Finalizada', 'primary'),
        'informe_pendiente' => $c('Informe pendiente', 'warning'), 'informe_entregado' => $c('Informe entregado', 'success'),
    ],
    'document_type' => [
        'informe_psicologico' => $c('Informe psicológico'), 'informe_evaluacion' => $c('Informe de evaluación'),
        'informe_seguimiento' => $c('Informe de seguimiento'), 'certificado_asistencia' => $c('Certificado de asistencia'),
        'certificado_psicologico' => $c('Certificado psicológico'), 'informe_institucion' => $c('Informe para institución educativa'),
        'derivacion' => $c('Derivación'), 'plan_intervencion' => $c('Plan de intervención'),
        'desglose_sesiones' => $c('Desglose de sesiones'), 'personalizado' => $c('Documento personalizado'),
    ],
    'document_status' => ['borrador' => $c('Borrador', 'warning'), 'emitido' => $c('Emitido', 'success'), 'anulado' => $c('Anulado', 'neutral')],
    'payment_method' => ['efectivo' => $c('Efectivo'), 'transferencia' => $c('Transferencia'), 'tarjeta' => $c('Tarjeta'), 'otro' => $c('Otro')],
    'payment_status' => ['pagado' => $c('Pagado', 'success'), 'pendiente' => $c('Pendiente', 'warning'), 'anulado' => $c('Anulado', 'neutral')],
    'package_status' => [
        'activo' => $c('Activo', 'success'), 'finalizado' => $c('Finalizado', 'info'),
        'vencido' => $c('Vencido', 'warning'), 'cancelado' => $c('Cancelado', 'neutral'),
    ],
    'movement_type' => ['consumo' => $c('Consumo', 'primary'), 'reversion' => $c('Reversión', 'warning'), 'ajuste_manual' => $c('Ajuste manual', 'accent')],
    'file_category' => [
        'informe_externo' => $c('Informe externo'), 'evaluacion' => $c('Evaluación'), 'documento_escolar' => $c('Documento escolar'),
        'documento_medico' => $c('Documento médico'), 'consentimiento' => $c('Consentimiento'), 'otro' => $c('Otro'),
    ],
    'consent_type' => [
        'consentimiento_informado' => $c('Consentimiento informado'), 'autorizacion_evaluacion' => $c('Autorización de evaluación'),
        'autorizacion_institucion' => $c('Autorización de comunicación con institución educativa'),
        'autorizacion_intercambio' => $c('Autorización para intercambio de información con otro profesional'), 'otro' => $c('Otro'),
    ],
    'consent_status' => ['vigente' => $c('Vigente', 'success'), 'pendiente' => $c('Pendiente', 'warning'), 'revocado' => $c('Revocado', 'neutral')],
    'alert_type' => [
        'INACTIVIDAD_PACIENTE' => $c('Inactividad', 'warning'), 'PAQUETE_POR_FINALIZAR' => $c('Paquete', 'accent'),
        'EVALUACION_PENDIENTE' => $c('Evaluación', 'info'), 'INFORME_PENDIENTE' => $c('Informe', 'primary'),
        'REVISION_TERAPEUTICA' => $c('Revisión', 'success'), 'PAGO_PENDIENTE' => $c('Pago', 'danger'), 'OTRA' => $c('Otra'),
    ],
    'alert_status' => ['pendiente' => $c('Pendiente', 'warning'), 'leida' => $c('Leída', 'info'), 'resuelta' => $c('Resuelta', 'success'), 'descartada' => $c('Descartada', 'neutral')],
    'audit_action' => [
        'LOGIN' => $c('Inicio de sesión', 'info'), 'LOGIN_FALLIDO' => $c('Login fallido', 'danger'), 'LOGOUT' => $c('Cierre de sesión'),
        'CREAR' => $c('Crear', 'success'), 'EDITAR' => $c('Editar', 'primary'), 'ANULAR' => $c('Anular', 'danger'), 'ELIMINAR' => $c('Eliminar', 'danger'),
        'CAMBIAR_ESTADO' => $c('Cambiar estado', 'warning'), 'GENERAR_DOCUMENTO' => $c('Generar documento', 'accent'),
        'DESCARGAR_DOCUMENTO' => $c('Descargar documento', 'accent'), 'VER_EXPEDIENTE_CLINICO' => $c('Ver expediente', 'info'),
        'EXPORTAR' => $c('Exportar', 'warning'), 'RESPALDO' => $c('Respaldo', 'primary'), 'AJUSTE_PAQUETE' => $c('Ajuste de paquete', 'accent'),
    ],
    'currency' => ['USD' => $c('USD — Dólar estadounidense'), 'EUR' => $c('EUR — Euro'), 'COP' => $c('COP — Peso colombiano'), 'PEN' => $c('PEN — Sol peruano'), 'MXN' => $c('MXN — Peso mexicano')],
];
