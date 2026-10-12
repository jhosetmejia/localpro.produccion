<?php

namespace App\Support\Dian;

// Revisa un documento antes de enviarlo, con las mismas reglas básicas que aplica la DIAN,
// para que los errores se vean en LocalPro y no como rechazos.
class Validador
{
    public static function errores(array $p): array
    {
        $e = [];
        $em = $p['emisor'] ?? [];
        if (empty($em['nit'])) $e[] = 'Falta el NIT del negocio';
        if (($em['dv'] ?? null) === null || $em['dv'] === '') $e[] = 'Falta el dígito de verificación (DV) del NIT';
        if (empty($em['razon_social'])) $e[] = 'Falta la razón social o nombre del titular';
        if (empty($em['responsabilidades'])) $e[] = 'Faltan las responsabilidades fiscales del RUT';
        if (empty($em['direccion']) || empty($em['municipio'])) $e[] = 'Falta la dirección o el código de municipio del negocio';

        if (empty($p['numero'])) $e[] = 'El documento no tiene número';
        if ($p['tipo'] !== 'nc') {
            $r = $p['resolucion'] ?? null;
            if (!$r || empty($r['numero'])) {
                $e[] = 'No hay resolución de numeración de la DIAN';
            } else {
                if (!empty($r['vigencia']) && $r['vigencia'] < $p['fecha']) $e[] = 'La resolución de numeración está vencida';
                if (!empty($r['hasta']) && (int) $p['consecutivo'] > (int) $r['hasta']) $e[] = 'El número está fuera del rango autorizado';
                if (!empty($r['desde']) && (int) $p['consecutivo'] < (int) $r['desde']) $e[] = 'El número está fuera del rango autorizado';
            }
        } elseif (empty($p['referencia']['numero'])) {
            $e[] = 'La nota crédito no dice a qué factura corrige';
        }

        $ad = $p['adquiriente'] ?? [];
        if ($p['tipo'] === 'fe') {
            if (($ad['numero'] ?? '') === Catalogos::CONSUMIDOR_FINAL['numero'] || empty($ad['numero'])) $e[] = 'La factura electrónica necesita la identificación del cliente';
            if (empty($ad['nombre'])) $e[] = 'Falta el nombre del cliente';
            if (empty($ad['email'])) $e[] = 'Falta el correo del cliente (allí le llega la factura)';
            if (($ad['tipo_doc'] ?? '') === '31' && ($ad['dv'] ?? null) === null) $e[] = 'Falta el DV del NIT del cliente';
        }

        if (empty($p['items'])) $e[] = 'El documento no tiene productos';
        foreach ($p['items'] ?? [] as $i => $it) {
            if ((float) $it['cantidad'] <= 0) $e[] = 'Cantidad inválida en la línea ' . ($i + 1);
            if (empty($it['descripcion'])) $e[] = 'Falta la descripción en la línea ' . ($i + 1);
        }

        $t = $p['totales'] ?? [];
        $suma = round(array_sum(array_map(fn ($it) => (float) $it['total'], $p['items'] ?? [])), 2);
        if (abs($suma - (float) ($t['total'] ?? 0)) > 0.05) $e[] = 'La suma de las líneas no coincide con el total';
        if (abs((float) ($t['base'] ?? 0) + (float) ($t['impuestos'] ?? 0) - (float) ($t['total'] ?? 0)) > 0.05) $e[] = 'Base más impuestos no coincide con el total';
        if ($p['tipo'] !== 'nc') {
            $pagos = round(array_sum(array_column($p['medios_pago'] ?? [], 'valor')), 2);
            if ($pagos > 0 && abs($pagos - (float) $t['total']) > 0.05) $e[] = 'Los pagos no coinciden con el total';
        }
        return $e;
    }
}
