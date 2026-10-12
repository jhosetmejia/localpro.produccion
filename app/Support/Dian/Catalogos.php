<?php

namespace App\Support\Dian;

// Tablas de la DIAN (Anexo técnico de factura electrónica) que usa LocalPro
class Catalogos
{
    // Impuesto de cada producto: [nombre, tasa %, código DIAN del tributo, letra para el tiquete]
    public const IMPUESTOS = [
        'ninguno'  => ['Sin impuesto (no responsable de IVA)', 0, null, ''],
        'iva19'    => ['IVA 19 %', 19, '01', 'A'],
        'iva5'     => ['IVA 5 %', 5, '01', 'B'],
        'exento'   => ['Exento de IVA (0 %)', 0, '01', 'E'],
        'excluido' => ['Excluido de IVA', 0, null, 'X'],
        'inc8'     => ['Impoconsumo 8 % (restaurantes y bares)', 8, '04', 'I'],
    ];

    // Responsabilidades fiscales del RUT (casilla 53)
    public const RESPONSABILIDADES = [
        'R-99-PN' => 'No aplica – otros (persona natural no responsable de IVA)',
        'O-48' => 'Responsable del impuesto sobre las ventas – IVA',
        'O-49' => 'No responsable de IVA',
        'O-47' => 'Régimen simple de tributación – SIMPLE',
        'O-13' => 'Gran contribuyente',
        'O-15' => 'Autorretenedor',
        'O-23' => 'Agente de retención IVA',
    ];

    // Tipos de documento de identificación (nombre que usa LocalPro => código DIAN)
    public const TIPOS_DOC = [
        'Cédula de ciudadanía' => '13',
        'NIT' => '31',
        'Cédula de extranjería' => '22',
        'Tarjeta de identidad' => '12',
        'Pasaporte' => '41',
        'Registro civil' => '11',
        'Documento extranjero' => '42',
        'PEP' => '47',
        // Nombres heredados del sistema anterior
        'DNI' => '13', 'RUC' => '31', 'Carnet Extranjería' => '22',
    ];

    // Unidades de LocalPro => código UN/ECE que pide la DIAN
    public const UNIDADES = ['und' => '94', 'm' => 'MTR', 'cm' => 'CMT', 'kg' => 'KGM', 'g' => 'GRM', 'lb' => 'LBR', 'l' => 'LTR', 'ml' => 'MLT'];

    // Medios de pago
    public const MEDIOS_PAGO = [
        '10' => 'Efectivo', '47' => 'Transferencia (Nequi, Daviplata, PSE…)', '48' => 'Tarjeta crédito',
        '49' => 'Tarjeta débito', '42' => 'Consignación bancaria', '20' => 'Cheque', 'ZZZ' => 'Otro',
    ];

    // Consumidor final (cliente sin identificar)
    public const CONSUMIDOR_FINAL = ['tipo_doc' => '13', 'numero' => '222222222222', 'dv' => null, 'nombre' => 'Consumidor final', 'tipo_persona' => 'natural', 'responsabilidad' => 'R-99-PN', 'email' => null, 'direccion' => null, 'municipio' => null, 'telefono' => null];

    public static function impuesto(?string $k): array
    {
        return self::IMPUESTOS[$k ?: 'ninguno'] ?? self::IMPUESTOS['ninguno'];
    }

    public static function tipoDoc(?string $nombre): string
    {
        return self::TIPOS_DOC[$nombre ?? ''] ?? '13';
    }

    // Dígito de verificación del NIT (algoritmo oficial DIAN, módulo 11)
    public static function dv(?string $nit): ?string
    {
        $n = preg_replace('/\D/', '', (string) $nit);
        if ($n === '' || strlen($n) > 15) return null;
        $pesos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $suma = 0;
        foreach (array_reverse(str_split($n)) as $i => $d) $suma += (int) $d * $pesos[$i];
        $r = $suma % 11;
        return (string) ($r > 1 ? 11 - $r : $r);
    }

    // NIT sin puntos, guiones ni dígito de verificación
    public static function nitLimpio(?string $nit): string
    {
        $n = trim((string) $nit);
        // "900123456-7" → quita el DV escrito después del guion
        if (preg_match('/^([\d\.\s]+)-\s*\d$/', $n, $m)) $n = $m[1];
        return preg_replace('/\D/', '', $n);
    }
}
