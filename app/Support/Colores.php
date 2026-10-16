<?php

namespace App\Support;

/**
 * Paleta estándar de colores para tallas/colores de productos.
 * Así todos escriben igual ("NEGRA", "negro", "Negro" → NEGRO) y el inventario, el POS,
 * los reportes y el catálogo no se llenan de colores repetidos con distinto nombre.
 * Si alguien escribe un color que no está en la paleta, se respeta (en mayúsculas).
 */
class Colores
{
    // Nombre => color para la bolita
    public const PALETA = [
        'NEGRO' => '#111111',
        'BLANCO' => '#ffffff',
        'GRIS' => '#9ca3af',
        'GRIS OSCURO' => '#4b5563',
        'AZUL' => '#2563eb',
        'AZUL OSCURO' => '#1e3a8a',
        'AZUL CLARO' => '#93c5fd',
        'ROJO' => '#dc2626',
        'VINOTINTO' => '#7f1d1d',
        'ROSADO' => '#f9a8d4',
        'FUCSIA' => '#db2777',
        'MORADO' => '#7c3aed',
        'LILA' => '#c4b5fd',
        'VERDE' => '#16a34a',
        'VERDE OLIVA' => '#6b7c32',
        'AMARILLO' => '#facc15',
        'NARANJA' => '#f97316',
        'CAFÉ' => '#7c4a1e',
        'BEIGE' => '#e3d3b4',
        'HUESO' => '#f1ece0',
        'DORADO' => '#d4a017',
        'PLATEADO' => '#c0c0c0',
        'ESTAMPADO' => 'linear-gradient(135deg,#f97316,#db2777,#2563eb,#16a34a)',
    ];

    // Formas comunes de escribir lo mismo (sin tildes, en mayúsculas)
    public const SINONIMOS = [
        'NEGRA' => 'NEGRO', 'BLANCA' => 'BLANCO', 'ROJA' => 'ROJO', 'AMARILLA' => 'AMARILLO',
        'MORADA' => 'MORADO', 'ROSADA' => 'ROSADO', 'ROSA' => 'ROSADO', 'PALO DE ROSA' => 'ROSADO',
        'CAFE' => 'CAFÉ', 'MARRON' => 'CAFÉ', 'CHOCOLATE' => 'CAFÉ', 'CAMEL' => 'BEIGE', 'KAKI' => 'BEIGE', 'CAQUI' => 'BEIGE', 'KHAKI' => 'BEIGE',
        'VINO' => 'VINOTINTO', 'VINO TINTO' => 'VINOTINTO', 'BORDO' => 'VINOTINTO', 'BURDEOS' => 'VINOTINTO', 'GUINDA' => 'VINOTINTO',
        'AZUL MARINO' => 'AZUL OSCURO', 'AZUL OSCURA' => 'AZUL OSCURO', 'NAVY' => 'AZUL OSCURO', 'AZUL CLARA' => 'AZUL CLARO', 'CELESTE' => 'AZUL CLARO',
        'GRIS OSCURA' => 'GRIS OSCURO', 'GRAFITO' => 'GRIS OSCURO', 'GRIS CLARO' => 'GRIS', 'GRIS CLARA' => 'GRIS',
        'VERDE MILITAR' => 'VERDE OLIVA', 'OLIVA' => 'VERDE OLIVA', 'CREMA' => 'HUESO', 'MARFIL' => 'HUESO', 'CRUDO' => 'HUESO',
        'DORADA' => 'DORADO', 'ORO' => 'DORADO', 'PLATEADA' => 'PLATEADO', 'PLATA' => 'PLATEADO',
        'NARANJADO' => 'NARANJA', 'NARANJADA' => 'NARANJA', 'MULTICOLOR' => 'ESTAMPADO', 'ESTAMPADA' => 'ESTAMPADO',
    ];

    public static function normalizar(?string $color): ?string
    {
        $c = preg_replace('/\s+/u', ' ', mb_strtoupper(trim((string) $color)));
        if ($c === '') return null;
        $clave = strtr($c, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);
        if (isset(self::SINONIMOS[$clave])) return self::SINONIMOS[$clave];
        foreach (array_keys(self::PALETA) as $p) {
            if (strtr($p, ['É' => 'E']) === $clave) return $p;
        }
        return $c;
    }
}
