<?php

namespace App\Support;

use App\Models\ContableDocumento;
use App\Models\ContablePeriodo;
use App\Models\ContableTarifa;
use App\Models\ContableTercero;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Retenciones en la fuente e IVA para analistas contables.
 *  1. Lee el listado de facturas que se descarga de la DIAN (emitidas, recibidas, notas y documentos soporte).
 *  2. Lee la base del programa contable (Contapyme, Siigo, World Office…): en columnas o como auxiliar por cuenta.
 *  3. Cruza las dos, calcula la retención que debió practicarse según la tabla vigente y arma el borrador del 350 y del IVA.
 * Es una ayuda para el analista: la revisión, la firma y la presentación en la DIAN siguen siendo del contador.
 */
class Retenciones
{
    // Valor de la UVT por año (Resolución DIAN 000238 de 2025 para 2026)
    public const UVT = [2024 => 47065, 2025 => 49799, 2026 => 52374];

    // Conceptos de retención: [nombre, cuentas PUC que lo identifican]
    public const CONCEPTOS = [
        'compras' => ['Compras generales', ['236540']],
        'servicios' => ['Servicios generales', ['236525']],
        'honorarios' => ['Honorarios', ['236515']],
        'comisiones' => ['Comisiones', ['236520']],
        'arrendamiento_inmuebles' => ['Arrendamiento de bienes raíces', ['236530']],
        'arrendamiento_muebles' => ['Arrendamiento de bienes muebles', []],
        'transporte_carga' => ['Transporte de carga', []],
        'hoteles_restaurantes' => ['Hoteles y restaurantes', []],
        'otros' => ['Otros pagos sujetos a retención', ['236550', '236570']],
        'no_aplica' => ['No sujeto a retención', []],
    ];

    /**
     * Tabla que se usa si el negocio aún no tiene la suya. Bases y tarifas generales vigentes desde el 8 de mayo de 2026,
     * cuando el Consejo de Estado suspendió los artículos 2 al 8 del Decreto 572 de 2025. Para los meses en que rigió
     * ese decreto, el analista agrega en "Tarifas" las bases y tarifas de esas fechas.
     * [concepto, nombre, base UVT, % declarante o persona jurídica, % no declarante o persona natural]
     */
    public const TARIFAS_BASE = [
        ['compras', 'Compras generales', 27, 2.5, 3.5],
        ['servicios', 'Servicios generales', 4, 4, 6],
        ['honorarios', 'Honorarios (jurídica / natural)', 0, 11, 10],
        ['comisiones', 'Comisiones (jurídica / natural)', 0, 11, 10],
        ['arrendamiento_inmuebles', 'Arrendamiento de bienes raíces', 27, 3.5, 3.5],
        ['arrendamiento_muebles', 'Arrendamiento de bienes muebles', 0, 4, 4],
        ['transporte_carga', 'Transporte de carga', 4, 1, 1],
        ['hoteles_restaurantes', 'Hoteles y restaurantes', 4, 3.5, 3.5],
        ['otros', 'Otros pagos', 27, 2.5, 3.5],
    ];

    // Retención de IVA: 15 % del IVA, en compras y servicios que superan la base mínima del concepto
    public const RETE_IVA = 15;

    // Diferencia (en pesos) a partir de la cual se marca para revisar
    public const TOLERANCIA = 1000;

    public static function uvt(int $anio): int
    {
        return self::UVT[$anio] ?? self::UVT[max(array_keys(self::UVT))];
    }

    // ===================== Tarifas =====================

    public static function asegurarTarifas(): void
    {
        if (ContableTarifa::exists()) return;
        foreach (self::TARIFAS_BASE as $i => [$c, $n, $b, $d, $nd]) {
            ContableTarifa::create(['concepto' => $c, 'nombre' => $n, 'base_uvt' => $b, 'tarifa_declarante' => $d, 'tarifa_no_declarante' => $nd, 'orden' => $i]);
        }
    }

    /** Tarifa vigente de un concepto en una fecha (la más específica: con fechas antes que sin fechas) */
    public static function tarifa(string $concepto, ?Carbon $fecha, ?Collection $tabla = null): ?ContableTarifa
    {
        $tabla ??= ContableTarifa::all();
        $f = $fecha ?? now();
        return $tabla->where('concepto', $concepto)
            ->filter(fn ($t) => (!$t->desde || $t->desde->lte($f)) && (!$t->hasta || $t->hasta->gte($f)))
            ->sortByDesc(fn ($t) => ($t->desde ? 2 : 0) + ($t->hasta ? 1 : 0))
            ->first();
    }

    // ===================== Utilidades =====================

    public static function nit(?string $v): string
    {
        $v = trim((string) $v);
        // "900123456-7" → "900123456"; deja solo dígitos
        if (preg_match('/^(\d[\d.\s]*)-\s*\d$/', $v, $m)) $v = $m[1];
        return preg_replace('/\D/', '', $v);
    }

    // Número del documento sin prefijo ni ceros a la izquierda: "FE-000123" → "123"
    public static function numero(?string $doc): string
    {
        preg_match_all('/\d+/', (string) $doc, $m);
        $n = end($m[0]) ?: '';
        return ltrim($n, '0') ?: ($n !== '' ? '0' : '');
    }

    public static function clave(?string $nit, ?string $doc): string
    {
        return self::nit($nit) . '|' . self::numero($doc);
    }

    // Persona jurídica si el NIT es de 9 dígitos y empieza por 8 o 9 (regla práctica; el analista lo puede cambiar)
    public static function tipoPorNit(string $nit): string
    {
        $n = self::nit($nit);
        return strlen($n) === 9 && in_array($n[0], ['8', '9'], true) ? 'juridica' : 'natural';
    }

    public static function dinero($v): float
    {
        if (is_int($v) || is_float($v)) return (float) $v;
        $s = trim((string) $v);
        if ($s === '') return 0;
        $neg = str_starts_with($s, '-') || (str_starts_with($s, '(') && str_ends_with($s, ')'));
        $s = preg_replace('/[^\d,.]/', '', $s);
        // Formato colombiano 1.234.567,89 o internacional 1,234,567.89
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = strrpos($s, ',') > strrpos($s, '.') ? str_replace(['.', ','], ['', '.'], $s) : str_replace(',', '', $s);
        } elseif (str_contains($s, ',')) {
            $s = preg_match('/,\d{1,2}$/', $s) ? str_replace(',', '.', $s) : str_replace(',', '', $s);
        } elseif (substr_count($s, '.') > 1 || preg_match('/\.\d{3}$/', $s)) {
            $s = str_replace('.', '', $s);
        }
        $n = (float) $s;
        return $neg ? -$n : $n;
    }

    public static function fecha($v): ?Carbon
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v) && $v > 20000 && $v < 80000) return Carbon::create(1899, 12, 30)->addDays((int) $v); // fecha de Excel
        $s = trim((string) $v);
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'd-m-y', 'd/m/y'] as $f) {
            try {
                $c = Carbon::createFromFormat($f, substr($s, 0, strlen(date($f))));
                if ($c && $c->year > 1990) return $c->startOfDay();
            } catch (\Throwable $e) {
            }
        }
        try { return Carbon::parse($s)->startOfDay(); } catch (\Throwable $e) { return null; }
    }

    private static function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        return preg_replace('/[^a-z0-9]+/', ' ', $s);
    }

    /** Busca la fila de títulos (puede no ser la primera) y devuelve [índice, títulos normalizados] */
    private static function titulos(array $filas, array $debeTener): array
    {
        foreach (array_slice($filas, 0, 15, true) as $i => $fila) {
            $t = array_map(fn ($c) => self::norm((string) $c), $fila);
            $junto = ' ' . implode(' | ', $t) . ' ';
            $ok = true;
            foreach ($debeTener as $palabra) if (!str_contains($junto, $palabra)) { $ok = false; break; }
            if ($ok) return [$i, $t];
        }
        return [null, []];
    }

    private static function col(array $titulos, array $opciones): ?int
    {
        foreach ($opciones as $o) {
            foreach ($titulos as $i => $t) if ($t === $o) return $i;
        }
        foreach ($opciones as $o) {
            foreach ($titulos as $i => $t) if ($o !== '' && str_contains($t, $o)) return $i;
        }
        return null;
    }

    // ===================== Lector de la DIAN =====================

    /**
     * Lee el listado de documentos que se descarga de la DIAN (Facturación electrónica → Consulta / Descargar listado).
     * Columnas que usa: Tipo de documento, CUFE/CUDE, Folio, Prefijo, Fecha Emisión, NIT/Nombre Emisor, NIT/Nombre Receptor,
     * IVA, ICA, INC…, Rete IVA, Rete Renta, Rete ICA, Total, Estado, Grupo.
     */
    public static function leerDian(string $ruta, string $ext, string $nitCliente): array
    {
        $filas = HojaCalculo::leer($ruta, $ext);
        [$fila, $t] = self::titulos($filas, ['nit emisor', 'nit receptor']);
        if ($fila === null) throw new RuntimeException('No parece el listado de la DIAN: no se encontraron las columnas "NIT Emisor" y "NIT Receptor". Descárguelo de nuevo desde el portal de facturación electrónica y súbalo sin cambiarle los títulos.');
        $c = [
            'tipo' => self::col($t, ['tipo de documento', 'tipo documento']),
            'cufe' => self::col($t, ['cufe cude', 'cufe', 'cude']),
            'folio' => self::col($t, ['folio', 'numero']),
            'prefijo' => self::col($t, ['prefijo']),
            'fecha' => self::col($t, ['fecha emision', 'fecha de emision', 'fecha']),
            'nit_e' => self::col($t, ['nit emisor']),
            'nom_e' => self::col($t, ['nombre emisor']),
            'nit_r' => self::col($t, ['nit receptor']),
            'nom_r' => self::col($t, ['nombre receptor']),
            'iva' => self::col($t, ['iva']),
            'rete_iva' => self::col($t, ['rete iva', 'reteiva']),
            'rete_renta' => self::col($t, ['rete renta', 'retefuente', 'rete fuente']),
            'rete_ica' => self::col($t, ['rete ica', 'reteica']),
            'total' => self::col($t, ['total']),
            'estado' => self::col($t, ['estado']),
            'grupo' => self::col($t, ['grupo']),
        ];
        // Otros impuestos que no son base (ICA, INC, consumo, bolsas, timbre…)
        $otros = [];
        foreach ($t as $i => $tt) {
            if (in_array($i, array_filter($c, fn ($v) => $v !== null), true)) continue;
            if (in_array($tt, ['ica', 'ic', 'inc', 'timbre', 'inc bolsas', 'in carbono', 'in combustibles', 'ic datos', 'icl', 'inpp', 'ibua', 'icui'], true)) $otros[] = $i;
        }
        $nitCli = self::nit($nitCliente);
        $out = [];
        foreach (array_slice($filas, $fila + 1) as $f) {
            $get = fn ($k) => $c[$k] !== null ? ($f[$c[$k]] ?? '') : '';
            $nitE = self::nit($get('nit_e'));
            $nitR = self::nit($get('nit_r'));
            if ($nitE === '' && $nitR === '') continue;
            $estado = self::norm((string) $get('estado'));
            if ($estado !== '' && (str_contains($estado, 'rechaz') || str_contains($estado, 'anulad'))) continue;
            $tipo = trim((string) $get('tipo'));
            $tipoN = self::norm($tipo);
            if (str_contains($tipoN, 'application') || str_contains($tipoN, 'evento') || str_contains($tipoN, 'nomina')) continue;

            // ¿Venta o compra? Documento soporte: lo emite el cliente pero es una compra a quien no factura
            $soporte = str_contains($tipoN, 'soporte');
            $emitida = $nitE === $nitCli && !$soporte;
            if ($nitE !== $nitCli && $nitR !== $nitCli) {
                $g = self::norm((string) $get('grupo'));
                $emitida = str_contains($g, 'emitid');
            }
            $tercNit = $soporte ? $nitR : ($emitida ? $nitR : $nitE);
            $tercNom = $soporte ? $get('nom_r') : ($emitida ? $get('nom_r') : $get('nom_e'));
            $signo = str_contains($tipoN, 'credito') ? -1 : 1;

            $iva = self::dinero($get('iva'));
            $total = self::dinero($get('total'));
            $otrosImp = 0;
            foreach ($otros as $i) $otrosImp += self::dinero($f[$i] ?? 0);
            $folio = trim((string) $get('folio'));
            $pref = trim((string) $get('prefijo'));
            $out[] = [
                'origen' => 'dian',
                'direccion' => $emitida ? 'emitida' : 'recibida',
                'tipo_doc' => mb_substr($tipo ?: 'Factura', 0, 60),
                'documento' => mb_substr(trim($pref . ($pref !== '' && !str_starts_with($folio, $pref) ? '-' : '') . $folio, '-'), 0, 60),
                'clave' => self::clave($tercNit, $folio),
                'cufe' => mb_substr((string) $get('cufe'), 0, 120),
                'fecha' => self::fecha($get('fecha'))?->toDateString(),
                'nit_tercero' => $tercNit,
                'nombre_tercero' => mb_substr(trim((string) $tercNom), 0, 150),
                'base' => $signo * max(0, round($total - $iva - $otrosImp, 2)),
                'iva' => $signo * $iva,
                'otros_impuestos' => $signo * $otrosImp,
                'total' => $signo * $total,
                'rete_fuente' => $signo * self::dinero($get('rete_renta')),
                'rete_iva' => $signo * self::dinero($get('rete_iva')),
                'rete_ica' => $signo * self::dinero($get('rete_ica')),
            ];
        }
        if (!$out) throw new RuntimeException('El listado de la DIAN no trae facturas válidas para este NIT.');
        return $out;
    }

    // ===================== Lector de la contabilidad =====================

    // Campos que se pueden leer del archivo contable y los títulos con que suelen venir
    public const CAMPOS_CONTABLES = [
        'nit' => ['NIT del tercero', ['nit', 'nit tercero', 'identificacion', 'documento tercero', 'cedula nit', 'tercero nit', 'id tercero']],
        'nombre' => ['Nombre del tercero', ['nombre tercero', 'tercero', 'razon social', 'nombre', 'proveedor', 'cliente']],
        'documento' => ['Número de factura / documento', ['factura', 'numero factura', 'documento referencia', 'documento cruce', 'doc referencia', 'documento', 'numero documento', 'comprobante', 'numero']],
        'fecha' => ['Fecha', ['fecha', 'fecha documento', 'fecha factura']],
        'cuenta' => ['Cuenta contable', ['cuenta', 'codigo cuenta', 'cuenta contable', 'cod cuenta']],
        'base' => ['Base / subtotal', ['base', 'base retencion', 'base gravable', 'subtotal', 'valor base']],
        'iva' => ['IVA', ['iva', 'valor iva', 'iva descontable']],
        'rete_fuente' => ['Retención en la fuente', ['retencion', 'retefuente', 'rete fuente', 'retencion fuente', 'retencion en la fuente', 'rete renta']],
        'rete_iva' => ['Retención de IVA', ['reteiva', 'rete iva', 'retencion iva']],
        'rete_ica' => ['Retención de ICA', ['reteica', 'rete ica', 'retencion ica']],
        'debito' => ['Débito', ['debito', 'debitos', 'debe']],
        'credito' => ['Crédito', ['credito', 'creditos', 'haber']],
        'valor' => ['Valor (si no hay débito/crédito)', ['valor', 'saldo', 'importe', 'monto']],
    ];

    /** Lee títulos y primeras filas del archivo contable, y propone qué columna es cada campo */
    public static function vistaContable(string $ruta, string $ext): array
    {
        $filas = HojaCalculo::leer($ruta, $ext);
        // La fila de títulos: la primera con al menos 3 celdas de texto entre las 15 primeras
        $fila = 0;
        foreach (array_slice($filas, 0, 15, true) as $i => $f) {
            $texto = count(array_filter($f, fn ($c) => is_string($c) && $c !== '' && !is_numeric(str_replace(['.', ','], '', $c))));
            if ($texto >= 3) { $fila = $i; break; }
        }
        $titulos = array_map(fn ($c) => trim((string) $c), $filas[$fila] ?? []);
        $norm = array_map(fn ($c) => self::norm($c), $titulos);
        $mapa = [];
        $usadas = [];
        foreach (self::CAMPOS_CONTABLES as $campo => [$nombre, $ops]) {
            $libres = array_diff_key($norm, array_flip($usadas));
            $i = self::col($libres, $ops);
            if ($i !== null) { $mapa[$campo] = $i; $usadas[] = $i; }
        }
        return ['fila' => $fila, 'titulos' => $titulos, 'mapa' => $mapa, 'muestra' => array_slice($filas, $fila + 1, 5)];
    }

    /**
     * Lee la base contable con el mapeo de columnas. Funciona con dos formas:
     *  - En columnas: una fila por factura con base, IVA y retenciones.
     *  - Auxiliar por cuenta: una fila por movimiento (cuenta + valor). Se agrupa por tercero y documento y
     *    se reparte por cuenta: 2365 retención en la fuente, 2367 retención de IVA, 2368 retención de ICA,
     *    2408 IVA descontable, 2408 en crédito / 2408 generado (ventas).
     */
    public static function leerContable(string $ruta, string $ext, array $mapa, int $filaTitulos): array
    {
        $filas = HojaCalculo::leer($ruta, $ext);
        $g = fn ($f, $k) => isset($mapa[$k]) && $mapa[$k] !== '' && $mapa[$k] !== null ? ($f[(int) $mapa[$k]] ?? '') : null;
        $porDoc = [];
        foreach (array_slice($filas, $filaTitulos + 1) as $f) {
            $nit = self::nit((string) $g($f, 'nit'));
            $doc = trim((string) $g($f, 'documento'));
            if ($nit === '' || $doc === '') continue;
            $k = self::clave($nit, $doc);
            $d = &$porDoc[$k];
            $d ??= ['origen' => 'contable', 'direccion' => 'recibida', 'documento' => mb_substr($doc, 0, 60), 'clave' => $k, 'nit_tercero' => $nit,
                'nombre_tercero' => mb_substr(trim((string) $g($f, 'nombre')), 0, 150), 'fecha' => self::fecha($g($f, 'fecha'))?->toDateString(),
                'base' => 0, 'iva' => 0, 'total' => 0, 'rete_fuente' => 0, 'rete_iva' => 0, 'rete_ica' => 0, 'cuenta' => null, 'concepto' => null, '_base_col' => 0, '_base_mov' => 0];

            $cuenta = preg_replace('/\D/', '', (string) $g($f, 'cuenta'));
            $deb = self::dinero($g($f, 'debito'));
            $cre = self::dinero($g($f, 'credito'));
            $val = $g($f, 'valor') !== null ? self::dinero($g($f, 'valor')) : ($cre ?: $deb);

            // Columnas directas
            foreach (['iva', 'rete_fuente', 'rete_iva', 'rete_ica'] as $campo) {
                if ($g($f, $campo) !== null) $d[$campo] += self::dinero($g($f, $campo));
            }
            // Base: la de su columna (en el auxiliar suele venir solo en la fila de la retención)
            if ($g($f, 'base') !== null) $d['_base_col'] += abs(self::dinero($g($f, 'base')));
            // Auxiliar por cuenta
            if ($cuenta !== '') {
                if (str_starts_with($cuenta, '2365')) {
                    $d['rete_fuente'] += abs($cre ?: $val);
                    $d['cuenta'] = $cuenta;
                    if ($d['concepto'] === null) $d['concepto'] = self::conceptoPorCuenta($cuenta);
                    // la base suele venir en su propia columna en el auxiliar de retenciones
                } elseif (str_starts_with($cuenta, '2367')) {
                    $d['rete_iva'] += abs($cre ?: $val);
                } elseif (str_starts_with($cuenta, '2368')) {
                    $d['rete_ica'] += abs($cre ?: $val);
                } elseif (str_starts_with($cuenta, '2408')) {
                    // IVA: en débito es descontable (compra); en crédito es generado (venta)
                    if ($deb > 0) $d['iva'] += $deb;
                    elseif ($cre > 0) { $d['iva'] += $cre; $d['direccion'] = 'emitida'; }
                    else $d['iva'] += abs($val);
                } elseif (str_starts_with($cuenta, '4')) {
                    $d['direccion'] = 'emitida';
                    $d['_base_mov'] += abs($cre ?: $val);
                } elseif (preg_match('/^(5|6|7|14|15|17)/', $cuenta)) {
                    $d['_base_mov'] += abs($deb ?: $val);
                }
            }
            unset($d);
        }
        foreach ($porDoc as &$d) {
            // Si hay columna de base se usa; si no, la suma de los movimientos de gasto, costo, inventario o ingreso
            $d['base'] = $d['_base_col'] > 0 ? $d['_base_col'] : $d['_base_mov'];
            unset($d['_base_col'], $d['_base_mov']);
            $d['total'] = round($d['base'] + $d['iva'], 2);
        }
        unset($d);
        return array_values($porDoc);
    }

    public static function conceptoPorCuenta(string $cuenta): ?string
    {
        foreach (self::CONCEPTOS as $k => [$n, $cuentas]) {
            foreach ($cuentas as $c) if (str_starts_with($cuenta, $c)) return $k;
        }
        return str_starts_with($cuenta, '2365') ? 'otros' : null;
    }

    // ===================== Cruce y cálculo =====================

    /** Une cada documento de la DIAN con el de la contabilidad (mismo tercero y número) */
    public static function cruzar(ContablePeriodo $p): void
    {
        DB::transaction(function () use ($p) {
            ContableDocumento::where('periodo_id', $p->id)->update(['par_id' => null]);
            $dian = ContableDocumento::where('periodo_id', $p->id)->where('origen', 'dian')->get();
            $cont = ContableDocumento::where('periodo_id', $p->id)->where('origen', 'contable')->get()->groupBy('clave');
            foreach ($dian as $d) {
                $c = $cont->get($d->clave)?->first(fn ($x) => !$x->par_id);
                if (!$c) continue;
                $d->par_id = $c->id; $d->save();
                $c->par_id = $d->id; $c->direccion = $d->direccion; $c->save();
            }
        });
    }

    /** Terceros del cliente indexados por NIT (se crean los que falten) */
    public static function terceros(ContablePeriodo $p): Collection
    {
        $ya = ContableTercero::where('cliente_id', $p->cliente_id)->get()->keyBy('nit');
        $docs = ContableDocumento::where('periodo_id', $p->id)->whereNotNull('nit_tercero')->get(['nit_tercero', 'nombre_tercero', 'concepto', 'origen']);
        foreach ($docs->groupBy('nit_tercero') as $nit => $grupo) {
            if ($nit === '' || $ya->has($nit)) continue;
            $tipo = self::tipoPorNit($nit);
            $ya[$nit] = ContableTercero::create([
                'cliente_id' => $p->cliente_id, 'nit' => $nit, 'nombre' => $grupo->first(fn ($g) => $g->nombre_tercero)?->nombre_tercero,
                'tipo_persona' => $tipo, 'declarante' => $tipo === 'juridica',
                'concepto' => $grupo->first(fn ($g) => $g->concepto)?->concepto,
            ]);
        }
        return $ya;
    }

    /**
     * Arma el cruce del periodo: una fila por factura (recibidas y emitidas) con lo que dice la DIAN, lo contabilizado,
     * la retención que debió practicarse y el estado.
     */
    public static function analisis(ContablePeriodo $p): array
    {
        $cliente = $p->cliente;
        $tabla = ContableTarifa::all();
        $uvt = self::uvt($p->anio);
        $terceros = self::terceros($p);
        $docs = ContableDocumento::where('periodo_id', $p->id)->orderBy('fecha')->orderBy('id')->get();
        $porId = $docs->keyBy('id');
        // Si no se cargó contabilidad (o solo trae compras), no se marca todo lo de la DIAN como "sin contabilizar"
        $contCompras = $docs->contains(fn ($x) => $x->origen === 'contable' && $x->direccion === 'recibida');
        $contVentas = $docs->contains(fn ($x) => $x->origen === 'contable' && $x->direccion === 'emitida');
        $hayDian = $docs->contains(fn ($x) => $x->origen === 'dian');
        $filas = [];
        foreach ($docs as $d) {
            if ($d->origen === 'contable' && $d->par_id) continue; // ya va junto a su par de la DIAN
            $dian = $d->origen === 'dian' ? $d : null;
            $cont = $d->origen === 'contable' ? $d : ($d->par_id ? $porId->get($d->par_id) : null);
            $ref = $cont ?? $dian;
            $t = $terceros->get($ref->nit_tercero);
            $concepto = $cont?->concepto ?: $dian?->concepto ?: $t?->concepto ?: ($ref->direccion === 'recibida' ? 'compras' : null);
            $base = $cont && $cont->base ? $cont->base : ($dian?->base ?? 0);
            $iva = $cont && $cont->iva ? $cont->iva : ($dian?->iva ?? 0);
            $practicada = $cont ? $cont->rete_fuente : ($dian?->rete_fuente ?? 0);
            $reteIvaPract = $cont ? $cont->rete_iva : ($dian?->rete_iva ?? 0);

            $esperada = 0; $reteIvaEsp = 0; $tarifaTxt = '';
            if ($ref->direccion === 'recibida' && $cliente->agente_retenedor && $concepto && $concepto !== 'no_aplica' && !($t?->autorretenedor)) {
                $tar = self::tarifa($concepto, $ref->fecha, $tabla);
                if ($tar) {
                    $juridica = ($t?->tipo_persona ?? self::tipoPorNit($ref->nit_tercero)) === 'juridica';
                    $declarante = in_array($concepto, ['honorarios', 'comisiones'], true) ? $juridica : ($t?->declarante ?? $juridica);
                    $pct = $declarante ? $tar->tarifa_declarante : $tar->tarifa_no_declarante;
                    $minimo = $tar->base_uvt * $uvt;
                    if (abs($base) >= $minimo) {
                        $esperada = round($base * $pct / 100);
                        $tarifaTxt = rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',') . '%';
                        // Rete IVA (art. 437-2 ET): la practica el agente de retención de IVA (gran contribuyente, entidad
                        // pública o designado por la DIAN), desde la misma base mínima; entre grandes contribuyentes no se practica
                        if ($iva > 0 && $cliente->agente_rete_iva && !($t?->gran_contribuyente)) $reteIvaEsp = round($iva * self::RETE_IVA / 100);
                    } else {
                        $tarifaTxt = 'Base menor a ' . rtrim(rtrim(number_format($tar->base_uvt, 2, ',', ''), '0'), ',') . ' UVT';
                    }
                }
            }

            // Estado del cruce
            $obs = [];
            if (!$cont && ($ref->direccion === 'recibida' ? $contCompras : $contVentas)) $obs[] = ['solo_dian', 'Está en la DIAN y no está contabilizada'];
            if (!$dian && $hayDian) $obs[] = ['solo_contable', 'Contabilizada pero no está en la DIAN'];
            if ($cont && $dian) {
                if (abs($cont->iva - $dian->iva) > self::TOLERANCIA) $obs[] = ['iva', 'El IVA no coincide con la factura de la DIAN'];
                if ($cont->base && $dian->base && abs($cont->base - $dian->base) > max(self::TOLERANCIA, abs($dian->base) * 0.01)) $obs[] = ['base', 'La base no coincide con la factura'];
            }
            if ($ref->direccion === 'recibida' && abs($esperada - $practicada) > self::TOLERANCIA) {
                $obs[] = [$practicada < $esperada ? 'falta_retencion' : 'sobra_retencion', $practicada < $esperada ? 'Faltó practicar retención' : 'Se retuvo más de lo que da la tabla'];
            }
            if ($ref->direccion === 'recibida' && $cont) {
                if ($reteIvaEsp && abs($reteIvaEsp - $reteIvaPract) > self::TOLERANCIA) {
                    $obs[] = [$reteIvaPract < $reteIvaEsp ? 'falta_rete_iva' : 'sobra_rete_iva', $reteIvaPract < $reteIvaEsp ? 'Faltó practicar retención de IVA (15 %)' : 'La retención de IVA no es el 15 % del IVA'];
                } elseif (!$reteIvaEsp && $reteIvaPract > self::TOLERANCIA) {
                    $obs[] = ['sobra_rete_iva', $cliente->agente_rete_iva ? 'Retención de IVA practicada sin que aplique (revise base o proveedor gran contribuyente)' : 'Practicó retención de IVA pero el cliente no está marcado como agente de retención de IVA'];
                }
            }
            $filas[] = [
                'id' => $ref->id, 'dian_id' => $dian?->id, 'cont_id' => $cont?->id,
                'direccion' => $ref->direccion, 'tipo' => $dian?->tipo_doc ?? 'Contable', 'documento' => $dian?->documento ?? $cont?->documento,
                'fecha' => $ref->fecha, 'nit' => $ref->nit_tercero, 'nombre' => $t?->nombre ?: $ref->nombre_tercero,
                'tipo_persona' => $t?->tipo_persona, 'declarante' => $t?->declarante, 'concepto' => $concepto,
                'base' => $base, 'iva' => $iva, 'total' => $dian?->total ?? $cont?->total,
                'iva_dian' => $dian?->iva, 'iva_cont' => $cont?->iva,
                'rete_dian' => $dian?->rete_fuente, 'rete_cont' => $cont?->rete_fuente,
                'practicada' => $practicada, 'esperada' => $esperada, 'tarifa' => $tarifaTxt,
                'rete_iva' => $reteIvaPract, 'rete_iva_esperada' => $reteIvaEsp,
                'rete_ica' => $cont ? $cont->rete_ica : ($dian?->rete_ica ?? 0),
                'obs' => $obs,
            ];
        }
        return $filas;
    }

    /** Borrador del 350: por concepto, base y retención para personas jurídicas y naturales */
    public static function borrador350(ContablePeriodo $p, array $filas): array
    {
        $r = [];
        foreach (self::CONCEPTOS as $k => [$nombre]) {
            if ($k === 'no_aplica') continue;
            $r[$k] = ['nombre' => $nombre, 'base_j' => 0, 'ret_j' => 0, 'base_n' => 0, 'ret_n' => 0];
        }
        $reteIva = 0; $reteIca = 0;
        foreach ($filas as $f) {
            if ($f['direccion'] !== 'recibida') continue;
            $reteIva += $f['rete_iva'];
            $reteIca += $f['rete_ica'];
            if (!$f['practicada'] || !$f['concepto'] || !isset($r[$f['concepto']])) continue;
            $j = ($f['tipo_persona'] ?? self::tipoPorNit($f['nit'])) === 'juridica';
            $r[$f['concepto']][$j ? 'base_j' : 'base_n'] += $f['base'];
            $r[$f['concepto']][$j ? 'ret_j' : 'ret_n'] += $f['practicada'];
        }
        $totalRet = array_sum(array_map(fn ($x) => $x['ret_j'] + $x['ret_n'], $r));
        $auto = (float) $p->autorretencion_valor;
        return [
            'conceptos' => array_filter($r, fn ($x) => $x['base_j'] || $x['base_n'] || $x['ret_j'] || $x['ret_n']),
            'todos' => $r,
            'rete_renta' => $totalRet,
            'autorretencion_base' => (float) $p->autorretencion_base,
            'autorretencion' => $auto,
            'rete_iva' => $reteIva,
            'total' => $totalRet + $auto + $reteIva,
            'rete_ica' => $reteIca, // va en la declaración del municipio, no en el 350
        ];
    }

    /** IVA del mes: generado en ventas menos descontable en compras */
    public static function iva(array $filas): array
    {
        $gen = 0; $desc = 0; $baseV = 0; $baseC = 0;
        foreach ($filas as $f) {
            if ($f['direccion'] === 'emitida') { $gen += $f['iva']; $baseV += $f['base']; }
            else { $desc += $f['iva']; $baseC += $f['base']; }
        }
        return ['base_ventas' => $baseV, 'generado' => $gen, 'base_compras' => $baseC, 'descontable' => $desc, 'saldo' => $gen - $desc];
    }

    /** Meses que forman el periodo de IVA del mes dado (bimestral o cuatrimestral) */
    public static function mesesIva(string $periodicidad, int $mes): array
    {
        $n = $periodicidad === 'cuatrimestral' ? 4 : 2;
        $ini = intdiv($mes - 1, $n) * $n + 1;
        return range($ini, $ini + $n - 1);
    }
}
