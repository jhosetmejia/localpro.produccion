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
