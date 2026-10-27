<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Departamentos/provincias y ciudades de Colombia, Costa Rica y El Salvador
 * (resources/data/ubicaciones.json). En otros países se escriben a mano.
 */
class Ubicaciones
{
    private static ?array $datos = null;

    public static function todas(): array
    {
        return self::$datos ??= json_decode(file_get_contents(resource_path('data/ubicaciones.json')), true);
    }

    public static function tiene(string $pais): bool
    {
        return isset(self::todas()[$pais]);
    }

    /**
     * Reglas de validación de departamento y ciudad según el país elegido.
     */
    public static function reglas(?string $pais, ?string $departamento, bool $obligatorio = true): array
    {
        $req = $obligatorio ? 'required' : 'nullable';
        if (!$pais || !self::tiene($pais)) {
            return ['departamento' => $req . '|string|max:60', 'ciudad' => $req . '|string|max:60'];
        }
        $deps = self::todas()[$pais]['datos'];
        $ciudades = $deps[$departamento] ?? [];
        return [
            'departamento' => [$req, 'string', Rule::in(array_keys($deps))],
            'ciudad' => [$req, 'string', Rule::in($ciudades)],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'departamento.required' => 'Elija el departamento o provincia.',
            'departamento.in' => 'Elija un departamento o provincia de la lista.',
            'ciudad.required' => 'Elija la ciudad.',
            'ciudad.in' => 'Elija una ciudad de la lista.',
        ];
    }
}
