<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa productos desde un Excel/CSV: reconoce las columnas solo, agrupa tallas/colores
 * del mismo producto y crea (o actualiza) productos, categorías, variantes y stock.
 */
class ImportarProductos
{
    public const CAMPOS = [
        'codigo' => 'Código / referencia',
        'nombre' => 'Nombre del producto',
        'categoria' => 'Categoría',
        'precio_venta' => 'Precio de venta',
        'precio_costo' => 'Costo',
        'cantidad' => 'Cantidad / stock',
        'talla' => 'Talla',
        'color' => 'Color',
        'codigo_barras' => 'Código de barras',
        'descripcion' => 'Descripción',
    ];

    // Palabras con las que se reconoce cada columna (sin tildes, en minúscula)
    private const SINONIMOS = [
        'codigo_barras' => ['codigo de barras', 'cod barras', 'codigo barras', 'barras', 'ean', 'upc', 'barcode', 'gtin'],
        'codigo' => ['codigo', 'cod', 'referencia', 'ref', 'sku', 'item', 'id producto', 'codigo interno', 'plu'],
        'nombre' => ['nombre', 'producto', 'nombre producto', 'articulo', 'item nombre', 'descripcion corta', 'nombre del producto', 'detalle'],
        'categoria' => ['categoria', 'linea', 'grupo', 'familia', 'departamento', 'seccion', 'tipo', 'subcategoria', 'clase'],
        'precio_venta' => ['p v p', 'pv', 'precio venta', 'precio de venta', 'precio', 'pvp', 'valor venta', 'venta', 'precio publico', 'precio al publico', 'valor', 'precio unitario', 'p venta'],
        'precio_costo' => ['costo', 'precio costo', 'precio de costo', 'costo unitario', 'valor costo', 'precio compra', 'precio de compra', 'compra', 'p costo'],
        'cantidad' => ['cantidad', 'stock', 'existencia', 'existencias', 'inventario', 'unidades', 'cant', 'saldo', 'disponible', 'stock actual'],
        'talla' => ['talla', 'tallas', 'size', 'medida', 'numero'],
        'color' => ['color', 'colores', 'colour'],
        'descripcion' => ['descripcion', 'detalle largo', 'observaciones', 'notas', 'caracteristicas'],
    ];

    public static function plano(string $s): string
    {
        $s = Str::lower(Str::ascii($s));
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim($s);
    }

    /** Sugiere qué campo es cada columna según el encabezado */
    public static function adivinar(array $encabezados): array
    {
        $mapa = [];
        $usados = [];
        // Primero coincidencias exactas, luego parciales
        foreach ([true, false] as $exacto) {
            foreach ($encabezados as $i => $h) {
                if (isset($mapa[$i])) continue;
                $p = self::plano((string) $h);
                if ($p === '') continue;
                foreach (self::SINONIMOS as $campo => $palabras) {
                    if (isset($usados[$campo])) continue;
                    foreach ($palabras as $w) {
                        $ok = $exacto ? $p === $w : (str_contains(' ' . $p . ' ', ' ' . $w . ' ') || str_starts_with($p, $w));
                        if ($ok) { $mapa[$i] = $campo; $usados[$campo] = true; break 2; }
                    }
                }
            }
        }
        // "Descripción" sin columna de nombre = es el nombre
        if (!isset($usados['nombre'])) {
            $k = array_search('descripcion', $mapa, true);
            if ($k !== false) $mapa[$k] = 'nombre';
        }
        return $mapa;
    }

    /** ¿La primera fila son encabezados? (si casi todo es texto y no hay precios) */
    public static function tieneEncabezados(array $filas): bool
    {
        $f = $filas[0] ?? [];
        $textos = count(array_filter($f, fn ($v) => is_string($v) && $v !== '' && !is_numeric(str_replace(['.', ',', '$', ' '], '', $v))));
        return $textos >= max(1, (int) floor(count(array_filter($f, fn ($v) => $v !== '' && $v !== null)) * 0.6));
    }

    /** "$12.500" "12,500.00" "12.500,50" 12500 → número */
    public static function numero($v): ?float
    {
        if (is_int($v) || is_float($v)) return (float) $v;
        $s = trim((string) $v);
        if ($s === '') return null;
        $s = preg_replace('/[^0-9,.\-]/', '', $s);
        if ($s === '' || $s === '-') return null;
        $p = strrpos($s, '.');
        $c = strrpos($s, ',');
        if ($p !== false && $c !== false) {
            $dec = $p > $c ? '.' : ',';
            $mil = $dec === '.' ? ',' : '.';
            $s = str_replace($mil, '', $s);
            $s = str_replace($dec, '.', $s);
        } elseif ($p !== false || $c !== false) {
            $ch = $p !== false ? '.' : ',';
            $partes = explode($ch, $s);
            // Una sola marca seguida de 3 dígitos (o varias) = separador de miles
            if (count($partes) > 2 || strlen(end($partes)) === 3) $s = str_replace($ch, '', $s);
            else $s = str_replace($ch, '.', $s);
        }
        return is_numeric($s) ? (float) $s : null;
    }

    private static function texto($v, int $max): ?string
    {
        if ($v === null) return null;
        if (is_float($v) && floor($v) == $v) $v = (int) $v;
        $s = trim(preg_replace('/\s+/u', ' ', (string) $v));
        return $s === '' ? null : mb_substr($s, 0, $max);
    }

    /** Convierte las filas del archivo en filas con campos según el mapa columna→campo */
    public static function normalizar(array $filas, array $mapa): array
    {
        $r = [];
        foreach ($filas as $n => $f) {
            $x = ['_fila' => $n];
            foreach ($mapa as $col => $campo) {
                if (!$campo || !isset(self::CAMPOS[$campo])) continue;
                $x[$campo] = $f[$col] ?? null;
            }
            $r[] = $x;
        }
        return $r;
    }

    /**
     * Importa. $opc: ['actualizar' => bool, 'controla' => bool, 'fila_inicial' => int]
     * Devuelve ['creados','actualizados','variantes','unidades','omitidos','errores'=>[...]]
     */
    public static function importar(array $filas, array $mapa, array $opc = []): array
    {
        @set_time_limit(600);
        $actualizar = $opc['actualizar'] ?? true;
        $controla = $opc['controla'] ?? true;
        $base = $opc['fila_inicial'] ?? 1;
        $res = ['creados' => 0, 'actualizados' => 0, 'variantes' => 0, 'unidades' => 0, 'omitidos' => 0, 'errores' => []];
        $err = function ($fila, $msg) use (&$res) { if (count($res['errores']) < 200) $res['errores'][] = 'Fila ' . $fila . ': ' . $msg; $res['omitidos']++; };

        // Agrupar filas por producto (código o nombre)
        $grupos = [];
        foreach (self::normalizar($filas, $mapa) as $x) {
            $num = $x['_fila'] + $base;
            $nombre = self::texto($x['nombre'] ?? null, 120);
            $codigo = self::texto($x['codigo'] ?? null, 50);
            if (!$nombre && !$codigo) continue; // fila vacía
            if (!$nombre) { $err($num, 'no tiene nombre.'); continue; }
            $clave = $codigo ? 'c:' . mb_strtolower($codigo) : 'n:' . self::plano($nombre);
            $grupos[$clave][] = $x + ['_num' => $num, '_nombre' => $nombre, '_codigo' => $codigo];
        }

        // Cupo del plan
        $plan = TiendaActual::tienda()?->plan;
        $cupo = $plan?->max_productos ? max(0, $plan->max_productos - Producto::count()) : PHP_INT_MAX;

        $categorias = [];
        $categoria = function (?string $nombre) use (&$categorias) {
            $nombre = $nombre ? mb_substr(Str::title(mb_strtolower($nombre)), 0, 60) : 'General';
            return $categorias[$nombre] ??= CrearTienda::categoria($nombre)->id;
        };

        foreach (array_chunk($grupos, 100, true) as $lote) {
            DB::transaction(function () use ($lote, &$res, $err, $actualizar, $controla, &$cupo, $categoria, $plan) {
                foreach ($lote as $filasProd) {
                    $p0 = $filasProd[0];
                    $precio = null; $costo = null;
                    foreach ($filasProd as $x) {
                        $precio ??= self::numero($x['precio_venta'] ?? null);
                        $costo ??= self::numero($x['precio_costo'] ?? null);
                    }

                    $producto = $p0['_codigo']
                        ? Producto::where('codigo', $p0['_codigo'])->first()
                        : Producto::where('nombre', $p0['_nombre'])->first();
                    $nuevo = !$producto;

                    if (!$nuevo && !$actualizar) { $err($p0['_num'], '"' . $p0['_nombre'] . '" ya existe (se dejó como estaba).'); continue; }
                    if ($nuevo) {
                        if ($cupo <= 0) { $err($p0['_num'], 'llegaste al límite de ' . (int) $plan?->max_productos . ' productos de tu plan. Comunícate con soporte para ampliar la cantidad de productos.'); continue; }
                        if ($precio === null) { $err($p0['_num'], '"' . $p0['_nombre'] . '" no tiene precio de venta.'); continue; }
                        $producto = Producto::create([
                            'codigo' => $p0['_codigo'] ?: CrearTienda::siguienteCodigo(),
                            'nombre' => $p0['_nombre'],
                            'precio_venta' => max(0, $precio),
                            'precio_costo' => max(0, $costo ?? 0),
                            'controla_inventario' => $controla,
                            'descripcion' => self::texto($p0['descripcion'] ?? null, 255),
                        ]);
                        $producto->categorias()->attach($categoria(self::texto($p0['categoria'] ?? null, 60)));
                        $cupo--;
                        $res['creados']++;
                    } else {
                        $cambios = [];
                        if ($precio !== null) $cambios['precio_venta'] = max(0, $precio);
                        if ($costo !== null) $cambios['precio_costo'] = max(0, $costo);
                        if (!empty($p0['descripcion'])) $cambios['descripcion'] = self::texto($p0['descripcion'], 255);
                        if ($cambios) $producto->update($cambios);
                        if (!empty($p0['categoria'])) $producto->categorias()->syncWithoutDetaching([$categoria(self::texto($p0['categoria'], 60))]);
                        $res['actualizados']++;
                    }

                    // Variantes (talla/color) o una sola
                    $vistas = [];
                    foreach ($filasProd as $x) {
                        $talla = ($t = self::texto($x['talla'] ?? null, 20)) ? mb_strtoupper($t) : null;
                        $color = ($c = self::texto($x['color'] ?? null, 30)) ? mb_strtoupper($c) : null;
                        $k = $talla . '|' . $color;
                        if (isset($vistas[$k])) { $err($x['_num'], 'fila repetida de "' . $x['_nombre'] . '"' . ($k !== '|' ? ' (' . trim($talla . ' ' . $color) . ')' : '') . '.'); continue; }
                        $vistas[$k] = true;

                        $v = ProductoVariante::where('producto_id', $producto->id)
                            ->where(fn ($q) => $talla ? $q->where('talla', $talla) : $q->whereNull('talla'))
                            ->where(fn ($q) => $color ? $q->where('color', $color) : $q->whereNull('color'))
                            ->first();
                        $pv = self::numero($x['precio_venta'] ?? null);
                        $datos = ['codigo_barras' => self::texto($x['codigo_barras'] ?? null, 50)];
                        if ($pv !== null && $precio !== null && abs($pv - $precio) > 0.001) $datos['precio'] = $pv;
                        $datos = array_filter($datos, fn ($d) => $d !== null);
                        if (!$v) {
                            $v = $producto->variantes()->create(['talla' => $talla, 'color' => $color, 'stock' => 0] + $datos);
                            $res['variantes']++;
                        } elseif ($datos) {
                            $v->update($datos);
                        }

                        $cant = self::numero($x['cantidad'] ?? null);
                        if ($cant !== null && $producto->controla_inventario) {
                            $cant = $producto->es_fraccion ? round(max(0, $cant), 3) : (int) round(max(0, $cant));
                            $delta = round($cant - (float) $v->stock, 3);
                            if (abs($delta) > 0.0005) $v->moverStock($delta, $nuevo || $v->wasRecentlyCreated ? 'entrada' : 'ajuste', null, 'Importación desde Excel');
                            $res['unidades'] += $cant;
                        }
                    }
                }
            });
        }
        return $res;
    }

    /** Plantilla de ejemplo */
    public static function plantilla(bool $tallas): array
    {
        $cab = ['Código', 'Nombre', 'Categoría', 'Precio venta', 'Costo', 'Cantidad'];
        if ($tallas) { $cab[] = 'Talla'; $cab[] = 'Color'; }
        $cab[] = 'Código de barras';
        $f = [$cab];
        if ($tallas) {
            $f[] = ['CAM-001', 'Camiseta básica', 'Camisetas', 45000, 22000, 5, 'S', 'NEGRO', '7701234567890'];
            $f[] = ['CAM-001', 'Camiseta básica', 'Camisetas', 45000, 22000, 8, 'M', 'NEGRO', '7701234567891'];
            $f[] = ['CAM-001', 'Camiseta básica', 'Camisetas', 45000, 22000, 3, 'L', 'BLANCO', ''];
            $f[] = ['JEA-010', 'Jean clásico', 'Pantalones', 120000, 60000, 10, '32', 'AZUL', ''];
        } else {
            $f[] = ['P-0001', 'Gaseosa 400 ml', 'Bebidas', 3000, 1800, 48, '7702004003508'];
            $f[] = ['P-0002', 'Pan tajado', 'Panadería', 6500, 4200, 20, ''];
            $f[] = ['', 'Arroz libra', 'Granos', 2800, 2000, 100, ''];
        }
        return $f;
    }
}
