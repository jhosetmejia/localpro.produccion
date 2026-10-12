<?php

namespace App\Support;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\MetodoPago;
use App\Models\Persona;
use App\Models\PosVenta;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sube ventas pasadas de un negocio (de su Excel) como HISTORIAL:
 * crea o completa los clientes y registra cada venta con su fecha, sin mover inventario ni cajas abiertas.
 * Columnas (en cualquier orden): Fecha, Producto, Talla, Método de pago, Valor, Cliente, Celular, Cédula, Vendedor.
 * Las ventas quedan numeradas H-000001… y en una caja cerrada "Ventas históricas importadas".
 */
class ImportarVentasHistoricas
{
    private const COLS = [
        'fecha' => ['fecha'],
        'producto' => ['producto', 'descripcion', 'articulo', 'prenda'],
        'talla' => ['talla'],
        'metodo' => ['metodo de pago', 'medio de pago', 'metodo', 'pago', 'forma de pago'],
        'valor' => ['valor', 'valor venta', 'total', 'precio'],
        'cliente' => ['cliente', 'nombre'],
        'telefono' => ['celular', 'contacto', 'telefono', 'whatsapp'],
        'cedula' => ['cedula', 'documento', 'cc', 'nit'],
        'vendedor' => ['vendedor', 'asesor'],
    ];

    private static function limpio($s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::ascii(mb_strtolower(trim((string) $s)))));
    }

    public static function leer(array $filas): array
    {
        $filas = array_values(array_filter($filas, fn ($f) => is_array($f) && count(array_filter($f, fn ($v) => trim((string) $v) !== ''))));
        if (!$filas) throw new RuntimeException('El archivo está vacío.');
        $mapa = [];
        foreach ($filas[0] as $i => $t) {
            $t = self::limpio($t);
            foreach (self::COLS as $k => $lista) {
                if (!isset($mapa[$k]) && in_array($t, $lista, true)) { $mapa[$k] = $i; break; }
            }
        }
        foreach (['fecha', 'producto', 'valor'] as $k) {
            if (!isset($mapa[$k])) throw new RuntimeException('Falta la columna "' . ucfirst($k) . '". Use la plantilla: Fecha, Producto, Talla, Método de pago, Valor, Cliente, Celular, Cédula, Vendedor.');
        }
        $v = fn ($f, $k) => isset($mapa[$k]) ? trim((string) ($f[$mapa[$k]] ?? '')) : '';
        $ventas = []; $malas = 0;
        foreach (array_slice($filas, 1) as $f) {
            $fecha = self::fecha($v($f, 'fecha'));
            $rv = $v($f, 'valor');
            $valor = is_numeric($rv) ? (float) $rv : (float) preg_replace('/\D/', '', $rv); // "$ 120.000" → 120000
            $prod = $v($f, 'producto');
            if (!$fecha || $valor <= 0 || $prod === '') { $malas++; continue; }
            $tel = preg_replace('/\D/', '', $v($f, 'telefono'));
            if (strlen($tel) === 12 && str_starts_with($tel, '57')) $tel = substr($tel, 2);
            $ventas[] = [
                'fecha' => $fecha,
                'producto' => mb_substr($prod, 0, 120),
                'talla' => mb_substr($v($f, 'talla'), 0, 20),
                'metodo' => $v($f, 'metodo') ?: 'Efectivo',
                'valor' => round($valor, 2),
                'cliente' => mb_substr(Str::title(mb_strtolower($v($f, 'cliente'))), 0, 80),
                'telefono' => strlen($tel) === 10 ? $tel : '',
                'cedula' => mb_substr(preg_replace('/[^0-9A-Za-z\-]/', '', $v($f, 'cedula')), 0, 20),
                'vendedor' => mb_substr($v($f, 'vendedor'), 0, 40),
            ];
        }
        return ['ventas' => $ventas, 'malas' => $malas];
    }

    private static function fecha(string $s): ?Carbon
    {
        if ($s === '') return null;
        try {
            if (is_numeric($s) && (float) $s > 30000 && (float) $s < 60000) return Carbon::create(1899, 12, 30)->addDays((int) $s)->setTime(12, 0);
            if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $s, $m)) return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1], 12);
            $c = Carbon::parse($s);
            return $c->year >= 2000 && $c->lte(now()->addDay()) ? $c->setTime(12, 0) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function guardar(Tienda $tienda, array $ventas): array
    {
        TiendaActual::set($tienda->id);
        $duenio = User::role('dueno')->where('tienda_id', $tienda->id)->first()
            ?? User::where('tienda_id', $tienda->id)->first();
        if (!$duenio) throw new RuntimeException('La tienda no tiene usuarios.');

        $r = ['ventas' => 0, 'repetidas' => 0, 'clientes_nuevos' => 0, 'clientes_completados' => 0, 'total' => 0.0];
        DB::transaction(function () use ($tienda, $ventas, $duenio, &$r) {
            $docCC = Documento::orderByRaw("tipo_documento = 'Cédula de ciudadanía' desc")->value('id');
            $metodos = [];
            $caja = null;
            $n = (int) PosVenta::where('numero', 'like', 'H-%')->count();

            foreach ($ventas as $v) {
                // ¿Ya se subió esta misma venta?
                $ya = PosVenta::where('numero', 'like', 'H-%')->whereDate('created_at', $v['fecha']->toDateString())
                    ->where('total', $v['valor'])->whereHas('items', fn ($q) => $q->where('descripcion', $v['producto']))->exists();
                if ($ya) { $r['repetidas']++; continue; }

                $caja ??= Caja::create([
                    'user_id' => $duenio->id, 'abierta_en' => $v['fecha'], 'cerrada_en' => now(), 'monto_inicial' => 0,
                    'total_ventas' => 0, 'estado' => 'cerrada', 'nota_apertura' => 'Ventas históricas importadas (no afectan inventario)',
                    'nota_cierre' => 'Importadas por LocalPro el ' . now()->format('d/m/Y'),
                ]);

                $clienteId = self::cliente($v, $docCC, $r);

                $mk = mb_strtolower(Str::ascii($v['metodo']));
                $metodos[$mk] ??= MetodoPago::get()->first(fn ($m) => mb_strtolower(Str::ascii($m->nombre)) === $mk)
                    ?? MetodoPago::create(['nombre' => mb_substr($v['metodo'], 0, 40), 'es_efectivo' => $mk === 'efectivo', 'activo' => true, 'orden' => 50]);

                $n++;
                $venta = PosVenta::create([
                    'numero' => 'H-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'caja_id' => $caja->id,
                    'user_id' => $duenio->id,
                    'cliente_id' => $clienteId,
                    'cliente_nombre' => $v['cliente'] ?: null,
                    'cliente_telefono' => $v['telefono'] ?: null,
                    'subtotal' => $v['valor'], 'descuento' => 0, 'total' => $v['valor'], 'cambio' => 0, 'estado' => 'pagada',
                ]);
                $venta->forceFill(['created_at' => $v['fecha'], 'updated_at' => $v['fecha']])->saveQuietly();
                $venta->items()->create([
                    'producto_variante_id' => null,
                    'descripcion' => $v['producto'] . ($v['vendedor'] ? ' · vendió ' . Str::title(mb_strtolower($v['vendedor'])) : ''),
                    'talla' => $v['talla'] ?: null, 'cantidad' => 1, 'precio' => $v['valor'], 'costo' => 0, 'subtotal' => $v['valor'],
                ]);
                $venta->items()->update(['created_at' => $v['fecha'], 'updated_at' => $v['fecha']]);
                DB::table('pos_venta_pagos')->insert([
                    'pos_venta_id' => $venta->id, 'metodo_pago_id' => $metodos[$mk]->id, 'monto' => $v['valor'],
                    'created_at' => $v['fecha'], 'updated_at' => $v['fecha'],
                ] + (\Illuminate\Support\Facades\Schema::hasColumn('pos_venta_pagos', 'tienda_id') ? ['tienda_id' => $tienda->id] : []));

                $r['ventas']++;
                $r['total'] += $v['valor'];
            }
            if ($caja) $caja->update(['total_ventas' => PosVenta::where('caja_id', $caja->id)->sum('total')]);
        });
        return $r;
    }

    // Busca el cliente por cédula o celular; si no existe lo crea; si existe completa lo que le falte
    private static function cliente(array $v, $docCC, array &$r): ?int
    {
        if ($v['cliente'] === '' && $v['telefono'] === '' && $v['cedula'] === '') return null;
        $p = null;
        if ($v['cedula'] !== '') $p = Persona::whereHas('cliente')->where('numero_documento', $v['cedula'])->first();
        if (!$p && $v['telefono'] !== '') $p = Persona::whereHas('cliente')->where('telefono', $v['telefono'])->first();

        if ($p) {
            $cambios = [];
            if ($v['cedula'] !== '' && preg_match('/^(TEL-|IMP-|C-)/', (string) $p->numero_documento) && !Persona::where('numero_documento', $v['cedula'])->exists()) $cambios['numero_documento'] = $v['cedula'];
            if ($v['telefono'] !== '' && !$p->telefono) $cambios['telefono'] = $v['telefono'];
            if ($v['cliente'] !== '' && mb_strlen($p->razon_social) < 4) $cambios['razon_social'] = $v['cliente'];
            if ($cambios) { $p->update($cambios); $r['clientes_completados']++; }
            return $p->cliente->id;
        }
        if ($v['cliente'] === '' || ($v['telefono'] === '' && $v['cedula'] === '')) return null; // sin forma de identificarlo: queda solo el nombre en la venta

        $numero = $v['cedula'] ?: 'TEL-' . $v['telefono'];
        if (Persona::where('numero_documento', $numero)->exists()) $numero = 'IMP-' . Str::upper(Str::random(8));
        $p = Persona::create([
            'razon_social' => $v['cliente'], 'direccion' => '', 'telefono' => $v['telefono'] ?: null,
            'tipo_persona' => 'natural', 'documento_id' => $docCC, 'numero_documento' => $numero,
        ]);
        $r['clientes_nuevos']++;
        return $p->cliente()->create(['persona_id' => $p->id])->id;
    }
}
