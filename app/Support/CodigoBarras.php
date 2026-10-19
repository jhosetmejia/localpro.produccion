<?php

namespace App\Support;

use App\Models\CodigoSuelto;
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
        $usados = array_merge(
            ProductoVariante::where('codigo_barras', 'like', $base . '%')->pluck('codigo_barras')->all(),
            CodigoSuelto::where('codigo', 'like', $base . '%')->pluck('codigo')->all() // etiquetas en blanco ya impresas
        );

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

    // Lo que se escanea: si es una etiqueta vieja, se convierte al código actual de esa talla/color;
    // si es una etiqueta extra (de mercancía nueva) ya asignada, también
    public static function resolver(string $q): string
    {
        if ($q === '') return $q;
        if (self::esViejo($q)) {
            $v = ProductoVariante::find((int) substr($q, 3));
            return $v && $v->codigo_barras ? $v->codigo_barras : $q;
        }
        $suelto = CodigoSuelto::with('variante:id,codigo_barras')->where('codigo', $q)->whereNotNull('producto_variante_id')->first();
        if ($suelto && $suelto->variante && $suelto->variante->codigo_barras && $suelto->variante->codigo_barras !== $q) {
            return $suelto->variante->codigo_barras;
        }
        return $q;
    }

    /* ---------- Etiquetas en blanco para mercancía nueva ---------- */

    // Genera un lote de etiquetas sin producto: N + 8 números (ej. N48201937). Únicas en la tienda.
    public static function generarLote(int $cantidad): string
    {
        $cantidad = max(1, min(1000, $cantidad));
        $lote = now()->format('ymdHis');
        $tienda = TiendaActual::id();
        $nuevos = [];
        $intentos = 0;
        while (count($nuevos) < $cantidad && $intentos++ < $cantidad * 20) {
            $faltan = $cantidad - count($nuevos);
            $cand = [];
            for ($i = 0; $i < $faltan * 2; $i++) $cand['N' . random_int(10000000, 99999999)] = true;
            $cand = array_diff(array_keys($cand), array_keys($nuevos));
            $usados = array_merge(
                CodigoSuelto::whereIn('codigo', $cand)->pluck('codigo')->all(),
                ProductoVariante::whereIn('codigo_barras', $cand)->pluck('codigo_barras')->all()
            );
            foreach (array_diff($cand, $usados) as $c) {
                if (count($nuevos) >= $cantidad) break;
                $nuevos[$c] = true;
            }
        }
        $ahora = now();
        $filas = array_map(fn ($c) => [
            'tienda_id' => $tienda, 'codigo' => $c, 'lote' => $lote, 'user_id' => auth()->id(),
            'created_at' => $ahora, 'updated_at' => $ahora,
        ], array_keys($nuevos));
        foreach (array_chunk($filas, 500) as $parte) CodigoSuelto::insert($parte);
        return $lote;
    }

    // Separa lo escaneado/escrito: una o varias etiquetas (por espacio, coma o renglón)
    public static function lista(?string $texto): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $texto))
            ->map(fn ($c) => mb_strtoupper(trim($c)))
            ->filter()->unique()->values()->all();
    }

    // Revisa que las etiquetas se puedan usar en esa talla/color. Devuelve el error o null.
    public static function validar(array $codigos, ?int $varianteId = null): ?string
    {
        foreach ($codigos as $c) {
            if (!preg_match('/^[A-Z0-9][A-Z0-9._\-]{0,59}$/', $c)) {
                return 'El código de barras "' . $c . '" no es válido: use solo letras, números o guion (máx. 60).';
            }
        }
        if (!$codigos) return null;
        $otra = ProductoVariante::with('producto:id,nombre')->whereIn('codigo_barras', $codigos)
            ->when($varianteId, fn ($q) => $q->where('id', '<>', $varianteId))->first();
        if ($otra) {
            return 'La etiqueta ' . $otra->codigo_barras . ' ya es de "' . ($otra->producto->nombre ?? 'otro producto') . '"' . ($otra->etiqueta !== 'Única' ? ' (' . $otra->etiqueta . ')' : '') . '.';
        }
        $suelto = CodigoSuelto::with('variante.producto:id,nombre')->whereIn('codigo', $codigos)->whereNotNull('producto_variante_id')
            ->when($varianteId, fn ($q) => $q->where('producto_variante_id', '<>', $varianteId))->first();
        if ($suelto && $suelto->variante) {
            return 'La etiqueta ' . $suelto->codigo . ' ya es de "' . ($suelto->variante->producto->nombre ?? 'otro producto') . '"' . ($suelto->variante->etiqueta !== 'Única' ? ' (' . $suelto->variante->etiqueta . ')' : '') . '.';
        }
        return null;
    }

    // Revisa las etiquetas de varias tallas/colores de un formulario (sin repetidas entre filas)
    public static function validarVarias(array $grupos): ?string
    {
        $vistos = [];
        foreach ($grupos as $codigos) {
            foreach ($codigos as $c) {
                if (isset($vistos[$c])) return 'La etiqueta ' . $c . ' está repetida en dos tallas/colores.';
                $vistos[$c] = true;
            }
            if ($e = self::validar($codigos)) return $e;
        }
        return null;
    }

    // Deja la talla/color con esas etiquetas: la primera es su código principal y las demás quedan
    // como etiquetas extra (sirven igual en la caja y en el conteo). Llamar después de validar().
    public static function asignar(ProductoVariante $v, array $codigos): void
    {
        if (!$codigos) return;
        $principal = $v->codigo_barras && !self::esViejo($v->codigo_barras, $v->tienda_id) ? $v->codigo_barras : null;
        if (!$principal) {
            $principal = array_shift($codigos);
            $v->codigo_barras = $principal;
            $v->save();
        }
        // La etiqueta principal, si venía de un lote, queda marcada como usada
        CodigoSuelto::where('codigo', $principal)->update(['producto_variante_id' => $v->id, 'usado_at' => now()]);
        foreach ($codigos as $c) {
            if ($c === $principal) continue;
            $s = CodigoSuelto::firstOrNew(['codigo' => $c]);
            $s->producto_variante_id = $v->id;
            $s->usado_at = now();
            $s->user_id ??= auth()->id();
            $s->save();
        }
    }

    // Etiquetas extra de una talla/color (además de su código principal)
    public static function extras(ProductoVariante $v): array
    {
        return CodigoSuelto::where('producto_variante_id', $v->id)
            ->where('codigo', '<>', (string) $v->codigo_barras)->orderBy('codigo')->pluck('codigo')->all();
    }

    // ¿Es una etiqueta en blanco que todavía no tiene producto?
    public static function libre(string $codigo): bool
    {
        return CodigoSuelto::where('codigo', $codigo)->whereNull('producto_variante_id')->exists();
    }
}
