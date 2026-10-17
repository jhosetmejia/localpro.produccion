<?php

namespace App\Support;

use App\Models\ProductoVariante;

/**
 * Código de barras de cada talla/color, basado en el código del producto para que sea el mismo
 * que ve el dueño y el cliente: P-0113 → "P0113" (si el producto tiene una sola opción) o
 * "P011301", "P011302"… (una por talla/color). Solo letras y números: así lo lee bien cualquier
 * lector o la cámara, sin importar el idioma del teclado.
 * Es único dentro de la tienda; para el marketplace, la tienda + este código identifican la prenda.
 */
class CodigoBarras
{
    // Formato anterior generado por el sistema: 3 dígitos de la tienda + 7 del id de la talla/color
    public static function esViejo(?string $c, ?int $tiendaId = null): bool
    {
        $tiendaId ??= TiendaActual::id();
        return $c !== null && preg_match('/^(\d{3})(\d{7})$/', $c, $m) === 1 && (int) $m[1] === (int) $tiendaId;
    }

    public static function base(ProductoVariante $v): string
    {
        $cod = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($v->producto->codigo ?? '')));
        return $cod !== '' ? substr($cod, 0, 40) : 'P' . $v->producto_id;
    }

    // Asigna el código si no tiene (o si tiene el formato viejo automático) y lo devuelve
    public static function asegurar(ProductoVariante $v): string
    {
        if ($v->codigo_barras && !self::esViejo($v->codigo_barras, $v->tienda_id)) return $v->codigo_barras;
        $v->loadMissing('producto');
        $base = self::base($v);
        $hermanos = ProductoVariante::where('producto_id', $v->producto_id)->where('id', '<>', $v->id)->get(['id', 'codigo_barras', 'estado']);
        $usados = ProductoVariante::where('codigo_barras', 'like', $base . '%')->pluck('codigo_barras')->all();

        if ($hermanos->isEmpty() && !in_array($base, $usados, true)) {
            $codigo = $base;
        } else {
            $n = 0;
            foreach ($usados as $u) if (preg_match('/^' . preg_quote($base, '/') . '(\d{2,3})$/', $u, $m)) $n = max($n, (int) $m[1]);
            do { $codigo = $base . str_pad((string) ++$n, 2, '0', STR_PAD_LEFT); } while (in_array($codigo, $usados, true));
        }
        $v->codigo_barras = $codigo;
        $v->save();
        return $codigo;
    }

    // Lo que se escanea: si es una etiqueta vieja, se convierte al código actual de esa talla/color
    public static function resolver(string $q): string
    {
        if (!self::esViejo($q)) return $q;
        $v = ProductoVariante::find((int) substr($q, 3));
        return $v && $v->codigo_barras ? $v->codigo_barras : $q;
    }
}
