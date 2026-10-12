<?php

namespace App\Support\Dian;

// Código único de la factura (CUFE) o del documento equivalente / nota (CUDE), según el anexo técnico de la DIAN:
// SHA-384( NumFac + FecFac + HorFac + ValFac + 01 + ValIVA + 04 + ValINC + 03 + ValICA + ValTot + NitOFE + NumAdq + Clave + Ambiente )
// Para la factura la "Clave" es la clave técnica de la resolución; para POS electrónico y notas es el PIN del software.
// El proveedor tecnológico es quien lo calcula y firma en producción; aquí sirve para pruebas y para verificar lo que devuelve.
class Cufe
{
    public static function calcular(array $d): string
    {
        $f = fn ($v) => number_format((float) $v, 2, '.', '');
        $cadena = $d['numero']
            . $d['fecha'] . $d['hora']
            . $f($d['subtotal'])
            . '01' . $f($d['iva'] ?? 0)
            . '04' . $f($d['inc'] ?? 0)
            . '03' . $f($d['ica'] ?? 0)
            . $f($d['total'])
            . $d['nit_emisor']
            . $d['doc_adquiriente']
            . ($d['clave'] ?? '')
            . ($d['ambiente'] === 'produccion' ? '1' : '2');
        return hash('sha384', $cadena);
    }

    // Enlace del código QR para consultar el documento en el catálogo de la DIAN
    public static function urlConsulta(string $cufe, string $ambiente): string
    {
        $host = $ambiente === 'produccion' ? 'catalogo-vpfe.dian.gov.co' : 'catalogo-vpfe-hab.dian.gov.co';
        return "https://{$host}/document/searchqr?documentkey={$cufe}";
    }
}
