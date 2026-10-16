<?php

namespace App\Support;

/**
 * Tallas estándar: todos escriben igual ("s", "small" → S; "unica", "talla única" → ÚNICA).
 * Las tallas que no estén en la lista se respetan (en mayúsculas).
 */
class Tallas
{
    // Grupos para los botones del formulario, en su orden natural
    public const GRUPOS = [
        'Letras' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'Niños' => ['2', '4', '6', '8', '10', '12', '14', '16'],
        'Pantalón' => ['26', '28', '30', '32', '34', '36', '38', '40'],
        'Calzado' => ['34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44'],
        'Otras' => ['ÚNICA'],
    ];

    public const SINONIMOS = [
        'EXTRA SMALL' => 'XS', 'EXTRA PEQUENA' => 'XS', 'XSMALL' => 'XS', 'X-SMALL' => 'XS',
        'SMALL' => 'S', 'PEQUENA' => 'S', 'PEQUENO' => 'S', 'CH' => 'S',
        'MEDIUM' => 'M', 'MEDIANA' => 'M', 'MEDIANO' => 'M', 'MED' => 'M',
        'LARGE' => 'L', 'GRANDE' => 'L', 'G' => 'L',
        'EXTRA LARGE' => 'XL', 'XLARGE' => 'XL', 'X-LARGE' => 'XL', 'EXTRA GRANDE' => 'XL', 'XG' => 'XL',
        '2XL' => 'XXL', 'XX-LARGE' => 'XXL', 'XXG' => 'XXL', '3XL' => 'XXXL', 'XXX-LARGE' => 'XXXL',
        'UNICA' => 'ÚNICA', 'U' => 'ÚNICA', 'UNI' => 'ÚNICA', 'TALLA UNICA' => 'ÚNICA', 'ESTANDAR' => 'ÚNICA',
        'STANDARD' => 'ÚNICA', 'ONE SIZE' => 'ÚNICA', 'TU' => 'ÚNICA', 'T/U' => 'ÚNICA',
    ];

    public static function normalizar(?string $talla): ?string
    {
        $t = preg_replace('/\s+/u', ' ', mb_strtoupper(trim((string) $talla)));
        $t = preg_replace('/^(TALLA|TALL|T\.)\s*(?=\S)/u', '', $t);
        if ($t === '' || $t === null) return null;
        if (preg_match('/^(TALLA )?UNICA$/', self::sinTildes($t))) return 'ÚNICA';
        $clave = self::sinTildes($t);
        return self::SINONIMOS[$clave] ?? $t;
    }

    private static function sinTildes(string $s): string
    {
        return strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    }
}
