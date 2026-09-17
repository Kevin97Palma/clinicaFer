<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validación backend. Reglas: required, email, date, time, numeric, int, min:n, max:n, in:a,b, in_catalog:grupo
 */
final class Validator
{
    public static function make(array $input, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $input[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $empty = $value === null || $value === '' || $value === [];

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'required') {
                    if ($empty) {
                        $errors[$field] = 'Este campo es obligatorio.';
                        break;
                    }
                    continue;
                }
                if ($empty) {
                    continue;
                }
                $msg = self::check($name, $arg, $value);
                if ($msg !== null) {
                    $errors[$field] = $msg;
                    break;
                }
            }
        }
        return $errors;
    }

    private static function check(string $name, ?string $arg, $value): ?string
    {
        switch ($name) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Correo electrónico no válido.';
            case 'date':
                $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
                return $d && $d->format('Y-m-d') === $value ? null : 'Fecha no válida.';
            case 'time':
                return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value) ? null : 'Hora no válida.';
            case 'numeric':
                return is_numeric($value) ? null : 'Debe ser un número.';
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT) !== false ? null : 'Debe ser un número entero.';
            case 'min':
                if (is_numeric($value)) {
                    return (float) $value >= (float) $arg ? null : "El valor mínimo es $arg.";
                }
                return mb_strlen((string) $value) >= (int) $arg ? null : "Mínimo $arg caracteres.";
            case 'max':
                if (is_numeric($value) && !is_string($value)) {
                    return (float) $value <= (float) $arg ? null : "El valor máximo es $arg.";
                }
                return mb_strlen((string) $value) <= (int) $arg ? null : "Máximo $arg caracteres.";
            case 'maxnum':
                return (float) $value <= (float) $arg ? null : "El valor máximo es $arg.";
            case 'in':
                return in_array((string) $value, explode(',', (string) $arg), true) ? null : 'Opción no válida.';
            case 'in_catalog':
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $v) {
                    if (!array_key_exists((string) $v, options((string) $arg))) {
                        return 'Opción no válida.';
                    }
                }
                return null;
            case 'before_or_today':
                return (string) $value <= date('Y-m-d') ? null : 'La fecha no puede ser futura.';
        }
        return null;
    }
}
