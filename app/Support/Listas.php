<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class Listas
{
    /**
     * Convierte "S, M, L" o "azul y rojo" en una lista limpia en mayúsculas.
     * Avisa con un mensaje claro si algún valor es demasiado largo.
     */
    public static function valores(?string $texto, int $max, string $que): array
    {
        $lista = collect(preg_split('/\s*(?:[,;\n\/]|\s+y\s+)\s*/iu', (string) $texto))
            ->map(fn ($x) => $que === 'colores' ? (string) Colores::normalizar($x) : mb_strtoupper(trim($x)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($lista as $v) {
            if (mb_strlen($v) > $max) {
                throw ValidationException::withMessages([
                    $que => "\"$v\" es muy largo (máximo $max letras). Separe cada " . ($que === 'colores' ? 'color' : 'talla') . ' con coma, por ejemplo: Azul, Rojo.',
                ]);
            }
        }
        return $lista;
    }
}
