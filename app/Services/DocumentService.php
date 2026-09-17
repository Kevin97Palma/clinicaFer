<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DOMDocument;
use DOMElement;

/**
 * Plantillas con variables {{...}} y saneamiento del HTML emitido.
 * El contenido se resuelve al generar y se guarda como versión inmutable.
 */
final class DocumentService
{
    public const VARIABLES = [
        'paciente_nombre' => 'Nombre completo del paciente',
        'paciente_edad' => 'Edad actual',
        'identificacion' => 'Identificación del paciente',
        'expediente' => 'Número de expediente',
        'fecha' => 'Fecha de emisión',
        'representante' => 'Representante principal',
        'diagnostico' => 'Diagnósticos activos',
        'fecha_ingreso' => 'Fecha de ingreso',
        'sesiones_realizadas' => 'Sesiones atendidas',
        'profesional' => 'Nombre del profesional',
        'registro_profesional' => 'Registro profesional',
        'institucion_educativa' => 'Institución educativa y grado',
        'motivo_consulta' => 'Motivo de consulta',
        'objetivos_plan' => 'Objetivos del plan activo',
    ];

    public static function variables(int $patientId): array
    {
        $p = Database::one('SELECT * FROM patients WHERE id = ?', [$patientId]);
        $g = Database::one('SELECT * FROM patient_guardians WHERE patient_id = ? ORDER BY is_primary DESC, id LIMIT 1', [$patientId]);
        $diag = Database::all(
            "SELECT diagnosis, code, classification_system FROM diagnoses WHERE patient_id = ? AND status = 'activo' AND type <> 'descartado' ORDER BY diagnosed_at DESC",
            [$patientId]
        );
        $objectives = Database::all(
            "SELECT a.name, o.objective FROM treatment_plans tp JOIN treatment_areas a ON a.plan_id = tp.id
               JOIN treatment_objectives o ON o.area_id = a.id
              WHERE tp.patient_id = ? AND tp.status = 'activo' ORDER BY a.sort_order, o.id",
            [$patientId]
        );
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return [
            'paciente_nombre' => $p['first_name'] . ' ' . $p['last_name'],
            'paciente_edad' => age($p['birth_date']),
            'identificacion' => $p['identification'] ?: '—',
            'expediente' => $p['file_number'],
            'fecha' => (int) date('j') . ' de ' . $months[(int) date('n') - 1] . ' de ' . date('Y'),
            'representante' => $g ? $g['first_name'] . ' ' . $g['last_name'] . ' (' . label('relationship', $g['relationship']) . ')' : '—',
            'diagnostico' => $diag ? implode('; ', array_map(fn($d) => $d['diagnosis'] . ($d['code'] ? " ({$d['classification_system']} {$d['code']})" : ''), $diag)) : 'Sin diagnóstico registrado',
            'fecha_ingreso' => fdate($p['intake_date']),
            'sesiones_realizadas' => (string) $p['sessions_count'],
            'profesional' => SettingsService::get('professional_name', auth_user()['name'] ?? ''),
            'registro_profesional' => SettingsService::get('registration_number', '—'),
            'institucion_educativa' => trim(($p['school'] ?? '') . ($p['grade'] ? ' — ' . $p['grade'] : '')) ?: '—',
            'motivo_consulta' => $p['consultation_reason'] ?: '—',
            'objetivos_plan' => $objectives ? implode('<br>', array_map(fn($o) => '• ' . $o['name'] . ': ' . $o['objective'], $objectives)) : '—',
        ];
    }

    /** Reemplaza {{variable}} escapando los valores (salvo objetivos, que ya se construyen seguros). */
    public static function render(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($vars) {
            if (!array_key_exists($m[1], $vars)) {
                return $m[0];
            }
            if ($m[1] === 'objetivos_plan') {
                return implode('<br>', array_map('e', explode('<br>', $vars[$m[1]])));
            }
            return nl2br(e($vars[$m[1]]));
        }, $template);
    }

    /** Saneamiento por lista blanca de etiquetas y atributos. */
    public static function sanitize(string $html): string
    {
        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote',
            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div', 'hr', 'sub', 'sup'];
        $allowedAttrs = ['class', 'style', 'colspan', 'rowspan'];

        if (trim($html) === '') {
            return '';
        }
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementById('__root');
        if (!$root) {
            return e(strip_tags($html));
        }
        self::cleanNode($root, $allowedTags, $allowedAttrs);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private static function cleanNode(DOMElement $node, array $tags, array $attrs): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                if (!in_array($tag, $tags, true)) {
                    // Conserva el texto interno, elimina la etiqueta
                    self::cleanNode($child, $tags, $attrs);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    $value = $attr->value;
                    if (!in_array($name, $attrs, true)
                        || ($name === 'style' && preg_match('/expression|url\s*\(|javascript:|@import|behavior/i', $value))) {
                        $child->removeAttribute($attr->name);
                    }
                }
                self::cleanNode($child, $tags, $attrs);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
