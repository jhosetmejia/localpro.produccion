<?php

namespace App\Support;

class Textos
{
    /**
     * Nombre parejo para categorías: "CHAQUETA NACIONAL" o "chaqueta nacional" → "Chaqueta nacional".
     * Si la persona ya escribió mayúsculas y minúsculas (ej. "Jeans Levi's"), solo se pone la primera en mayúscula.
     * En los nombres en mayúsculas se respetan siglas cortas (XL, TV, LG).
     */
    public static function nombre(?string $texto): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', (string) $texto));
        if ($t === '') return '';
        $todoMayus = $t === mb_strtoupper($t);
        if ($todoMayus || $t === mb_strtolower($t)) {
            $t = implode(' ', array_map(function ($p) use ($todoMayus) {
                $conector = in_array(mb_strtolower($p), ['y', 'o', 'e', 'u', 'a', 'de', 'la', 'el', 'en', 'al', 'lo', 'su'], true);
                return $todoMayus && !$conector && mb_strlen($p) <= 2 && preg_match('/\p{L}/u', $p) ? $p : mb_strtolower($p);
            }, explode(' ', $t)));
        }
        return mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }

    /**
     * Nombre estándar para productos y categorías (como los marketplaces):
     * "CHAQUETA CASUAL DE CUERO PARA DAMA" → "Chaqueta Casual de Cuero para Dama".
     * Se respetan siglas cortas en mayúscula (XL, OBM), marcas con mayúsculas internas (iPhone, McDonald's)
     * y las palabras pequeñas (de, para, con…) van en minúscula salvo al inicio.
     */
    public static function titulo(?string $texto): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', (string) $texto));
        if ($t === '') return '';
        $menores = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'o', 'u', 'a', 'al', 'en', 'para', 'por', 'con', 'sin', 'su', 'sus', 'tu', 'un', 'una', 'x'];
        $palabras = explode(' ', $t);
        foreach ($palabras as $i => $p) {
            $min = mb_strtolower($p);
            $soloLetras = preg_replace('/[^\p{L}]/u', '', $p);
            if ($i > 0 && in_array($min, $menores, true)) { $palabras[$i] = $min; continue; }
            // Sigla corta en mayúscula (XL, XXL, OBM, TV): se deja igual
            if ($soloLetras !== '' && mb_strlen($soloLetras) <= 3 && $soloLetras === mb_strtoupper($soloLetras) && !in_array($min, $menores, true)) continue;
            // Mayúscula en medio de la palabra (iPhone, McDonald's): marca, se deja igual
            if (preg_match('/^\p{Ll}+\p{Lu}|\p{Ll}\p{Lu}/u', $p) && $p !== mb_strtoupper($p)) continue;
            // Cada parte después de guion o barra también en mayúscula (Azul-Rojo, Dama/Caballero)
            $palabras[$i] = preg_replace_callback('/(^|[-\/(])(\p{L})/u', fn ($m) => $m[1] . mb_strtoupper($m[2]), $min);
        }
        return implode(' ', $palabras);
    }

    // Para comparar si dos nombres son "el mismo": sin tildes, sin mayúsculas y sin plurales (Chaquetas = chaqueta)
    public static function clave(?string $texto): string
    {
        $t = mb_strtolower(trim((string) $texto));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/[^a-z0-9 ]+/', ' ', $t);
        $palabras = array_filter(explode(' ', $t), 'strlen');
        return implode(' ', array_map(function ($p) {
            if (strlen($p) > 4 && preg_match('/[^aeiou]es$/', $p)) return substr($p, 0, -2); // pantalones → pantalon
            if (strlen($p) > 3 && str_ends_with($p, 's')) return substr($p, 0, -1);           // chaquetas → chaqueta
            return $p;
        }, $palabras));
    }
}
