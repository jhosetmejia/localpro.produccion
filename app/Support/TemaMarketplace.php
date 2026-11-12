<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Tema visual del marketplace y de las tiendas en línea (catálogos).
 * Lo elige el súper administrador en Admin → Marketplace. Por defecto: Navidad.
 */
class TemaMarketplace
{
    public const TEMAS = [
        'normal' => ['Normal', 'El verde LocalPro de siempre', '#0b0d0c', '#a6f34a'],
        'navidad' => ['Navidad', 'Rojo, verde pino y dorado, con nieve, luces y cinta navideña', '#0f2a1d', '#e63946'],
    ];

    public const POR_DEFECTO = 'navidad';

    private static ?string $memo = null;

    public static function actual(): string
    {
        if (self::$memo !== null) return self::$memo;
        try {
            $v = (string) Ajuste::valor('marketplace_tema', self::POR_DEFECTO);
        } catch (\Throwable $e) {
            $v = self::POR_DEFECTO;
        }
        return self::$memo = isset(self::TEMAS[$v]) ? $v : self::POR_DEFECTO;
    }

    public static function navidad(): bool
    {
        return self::actual() === 'navidad';
    }

    public static function guardar(string $tema): void
    {
        Ajuste::guardar('marketplace_tema', isset(self::TEMAS[$tema]) ? $tema : self::POR_DEFECTO);
        self::$memo = null;
    }
}
