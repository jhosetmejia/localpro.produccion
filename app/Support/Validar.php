<?php

namespace App\Support;

// Reglas de validación de celular y cédula (las mismas que revisa el navegador en public/js/lp-campos.js)
class Validar
{
    // Dígitos del número nacional por país [mínimo, máximo, indicativo]
    public const TEL = ['CO' => [10, 10, '57'], 'CR' => [8, 8, '506'], 'SV' => [8, 8, '503'], 'EC' => [9, 10, '593'], 'PE' => [9, 9, '51'], 'MX' => [10, 10, '52'], 'CL' => [9, 9, '56'], 'AR' => [10, 11, '54'], 'VE' => [10, 11, '58'], 'BO' => [8, 8, '591'], 'PY' => [9, 10, '595'], 'UY' => [8, 9, '598'], 'PA' => [8, 8, '507'], 'GT' => [8, 8, '502'], 'HN' => [8, 8, '504'], 'NI' => [8, 8, '505'], 'DO' => [10, 10, '1'], 'ES' => [9, 9, '34'], 'US' => [10, 10, '1']];

    public static function pais(): string
    {
        return strtoupper(TiendaActual::tienda()?->pais ?: 'CO');
    }

    // Número limpio sin indicativo
    public static function limpiarTel(?string $v, ?string $pais = null): string
    {
        $r = self::TEL[$pais ?? self::pais()] ?? [7, 15, ''];
        $n = preg_replace('/\D/', '', (string) $v);
        if ($r[2] && strlen($n) > $r[1] && str_starts_with($n, $r[2])) $n = substr($n, strlen($r[2]));
        return $n;
    }

    public static function celular(?string $pais = null): \Closure
    {
        return function ($atr, $valor, $falla) use ($pais) {
            if ($valor === null || $valor === '') return;
            $p = $pais ?? self::pais();
            $r = self::TEL[$p] ?? [7, 15, ''];
            $n = self::limpiarTel($valor, $p);
            if (strlen($n) < $r[0] || strlen($n) > $r[1]) {
                $falla('El número debe tener ' . ($r[0] === $r[1] ? $r[0] : $r[0] . ' a ' . $r[1]) . ' dígitos (tiene ' . strlen($n) . ').');
            } elseif ($p === 'CO' && !preg_match('/^(3|60)/', $n)) {
                $falla('En Colombia el celular empieza por 3 (o 60 si es fijo).');
            }
        };
    }

    // Cédula: en Colombia de 6 a 10 números; pasaporte/extranjería de 5 a 15 letras o números; NIT 9 o 10 números
    public static function documento(?string $tipo = null): \Closure
    {
        return function ($atr, $valor, $falla) use ($tipo) {
            $v = trim((string) $valor);
            if ($v === '' || preg_match('/^(TEL-|IMP-|C-)/i', $v)) return;
            $t = mb_strtolower((string) $tipo);
            if (preg_match('/pasaporte|extranj|pep|ppt/', $t) || self::pais() !== 'CO') {
                if (!preg_match('/^[0-9A-Za-z]{5,15}$/', $v)) $falla('El documento debe tener de 5 a 15 letras o números, sin puntos ni espacios.');
            } elseif (preg_match('/nit|ruc/', $t)) {
                if (!preg_match('/^\d{9,10}$/', $v)) $falla('El NIT debe tener 9 o 10 números, sin puntos ni dígito de verificación.');
            } elseif (!preg_match('/^[1-9]\d{5,9}$/', $v)) {
                $falla('La cédula debe tener de 6 a 10 números, sin puntos ni espacios.');
            }
        };
    }
}
