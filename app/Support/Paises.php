<?php

namespace App\Support;

/**
 * Países donde puede operar una tienda, con su moneda, indicativo y zona horaria.
 * [nombre, código moneda, símbolo, decimales, separador miles, separador decimal, indicativo, zona horaria]
 */
class Paises
{
    public const LISTA = [
        'CO' => ['Colombia', 'COP', '$', 0, '.', ',', '57', 'America/Bogota'],
        'CR' => ['Costa Rica', 'CRC', '₡', 0, '.', ',', '506', 'America/Costa_Rica'],
        'SV' => ['El Salvador', 'USD', '$', 2, ',', '.', '503', 'America/El_Salvador'],
        'EC' => ['Ecuador', 'USD', '$', 2, '.', ',', '593', 'America/Guayaquil'],
        'PE' => ['Perú', 'PEN', 'S/', 2, ',', '.', '51', 'America/Lima'],
        'MX' => ['México', 'MXN', '$', 2, ',', '.', '52', 'America/Mexico_City'],
        'CL' => ['Chile', 'CLP', '$', 0, '.', ',', '56', 'America/Santiago'],
        'AR' => ['Argentina', 'ARS', '$', 2, '.', ',', '54', 'America/Argentina/Buenos_Aires'],
        'VE' => ['Venezuela', 'VES', 'Bs', 2, '.', ',', '58', 'America/Caracas'],
        'BO' => ['Bolivia', 'BOB', 'Bs', 2, '.', ',', '591', 'America/La_Paz'],
        'PY' => ['Paraguay', 'PYG', '₲', 0, '.', ',', '595', 'America/Asuncion'],
        'UY' => ['Uruguay', 'UYU', '$', 2, '.', ',', '598', 'America/Montevideo'],
        'PA' => ['Panamá', 'USD', '$', 2, ',', '.', '507', 'America/Panama'],
        'GT' => ['Guatemala', 'GTQ', 'Q', 2, ',', '.', '502', 'America/Guatemala'],
        'HN' => ['Honduras', 'HNL', 'L', 2, ',', '.', '504', 'America/Tegucigalpa'],
        'NI' => ['Nicaragua', 'NIO', 'C$', 2, ',', '.', '505', 'America/Managua'],
        'DO' => ['República Dominicana', 'DOP', 'RD$', 2, ',', '.', '1', 'America/Santo_Domingo'],
        'ES' => ['España', 'EUR', '€', 2, '.', ',', '34', 'Europe/Madrid'],
        'US' => ['Estados Unidos', 'USD', '$', 2, ',', '.', '1', 'America/New_York'],
    ];

    public static function datos(?string $pais): array
    {
        $p = self::LISTA[$pais ?: 'CO'] ?? self::LISTA['CO'];
        return [
            'pais' => $pais ?: 'CO', 'nombre' => $p[0], 'moneda' => $p[1], 'simbolo' => $p[2],
            'decimales' => $p[3], 'miles' => $p[4], 'decimal' => $p[5], 'indicativo' => $p[6], 'zona' => $p[7],
        ];
    }
}
