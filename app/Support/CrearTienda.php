<?php

namespace App\Support;

use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crea una tienda lista para arrancar: sus métodos de pago, una categoría inicial y el usuario dueño.
 */
class CrearTienda
{
    public static function crear(array $tienda, array $dueno): array
    {
        return DB::transaction(function () use ($tienda, $dueno) {
            $tipo = $tienda['tipo_negocio'] ?? 'moda';
            $t = Tienda::create([
                'nombre' => $tienda['nombre'],
                'pais' => $tienda['pais'] ?? 'CO',
                'tipo_negocio' => $tipo,
                'usa_tallas' => Tienda::TIPOS[$tipo][2] ?? false,
                'telefono' => $tienda['telefono'] ?? null,
                'departamento' => $tienda['departamento'] ?? null,
                'ciudad' => $tienda['ciudad'] ?? null,
                'email' => $dueno['email'],
                'mensaje_tiquete' => '¡Gracias por su compra!',
                'estado' => 'activa',
                'onboarding_paso' => 2,
                'onboarding_completo' => false,
            ]);
            // Plan Gratis: 2 meses sin costo (usuarios ilimitados); al terminar debe pagar para seguir
            $t->forceFill(['plan_id' => \App\Models\Plan::gratis()?->id, 'plan_vence' => today()->addDays(59)])->save();

            TiendaActual::como($t->id, function () use ($t) {
                foreach (self::metodosPorPais($t->pais) as $i => $m) {
                    MetodoPago::create(['nombre' => $m[0], 'es_efectivo' => $m[1], 'moneda' => $m[2] ?? null, 'activo' => true, 'orden' => $i + 1]);
                }
                self::categoria('General');
            });

            $user = User::create([
                'name' => $dueno['name'],
                'email' => $dueno['email'],
                'telefono' => $dueno['telefono'] ?? null,
                'password' => Hash::make($dueno['password']),
                'tienda_id' => $t->id,
            ]);
            $user->assignRole('dueno');

            return [$t, $user];
        });
    }

    // Formas de pago más comunes en cada país (la tienda las puede editar)
    public static function metodosPorPais(string $pais): array
    {
        return match ($pais) {
            'CO' => [['Efectivo', true], ['Nequi', false], ['Daviplata', false], ['Transferencia', false], ['Tarjeta', false]],
            'PE' => [['Efectivo', true], ['Yape', false], ['Plin', false], ['Transferencia', false], ['Tarjeta', false]],
            'MX' => [['Efectivo', true], ['Transferencia SPEI', false], ['Tarjeta', false]],
            'AR' => [['Efectivo', true], ['Mercado Pago', false], ['Transferencia', false], ['Tarjeta', false]],
            'CL' => [['Efectivo', true], ['Transferencia', false], ['Tarjeta', false]],
            'EC' => [['Efectivo', true], ['DeUna', false], ['Transferencia', false], ['Tarjeta', false]],
            // Venezuela: los marcados 'VES' reciben bolívares y se pasan a dólares con la tasa del día
            'VE' => [['Efectivo (dólares)', true], ['Efectivo (bolívares)', true, 'VES'], ['Pago Móvil', false, 'VES'], ['Punto de venta (Bs)', false, 'VES'], ['Zelle', false], ['Binance', false], ['Transferencia (Bs)', false, 'VES']],
            default => [['Efectivo', true], ['Transferencia', false], ['Tarjeta', false]],
        };
    }

    // Categoría de la tienda actual (la crea si no existe)
    public static function categoria(string $nombre): Categoria
    {
        $nombre = Textos::titulo($nombre) ?: 'General';
        // Si ya hay una igual o parecida (Chaquetas = CHAQUETA), se usa esa
        $c = self::categoriaParecida($nombre);
        if ($c) {
            return $c;
        }
        $car = Caracteristica::create(['nombre' => $nombre, 'descripcion' => null, 'estado' => 1]);
        return Categoria::create(['caracteristica_id' => $car->id]);
    }

    // Categoría ya existente con el mismo nombre, sin importar mayúsculas, tildes ni plural
    public static function categoriaParecida(string $nombre, ?int $ignorar = null): ?Categoria
    {
        $clave = Textos::clave($nombre);
        if ($clave === '') return null;
        return Categoria::with('caracteristica')->when($ignorar, fn ($q) => $q->where('id', '<>', $ignorar))->get()
            ->first(fn ($c) => $c->caracteristica && Textos::clave($c->caracteristica->nombre) === $clave);
    }

    // Código consecutivo para productos creados sin código (P-0001, P-0002…)
    public static function siguienteCodigo(): string
    {
        $n = Producto::count() + 1;
        do {
            $codigo = 'P-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Producto::where('codigo', $codigo)->exists());
        return $codigo;
    }
}
