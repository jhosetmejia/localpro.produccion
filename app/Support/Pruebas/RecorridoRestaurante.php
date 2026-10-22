<?php

namespace App\Support\Pruebas;

use App\Models\Caja;
use App\Models\NegocioRol;
use App\Models\PedidoEstacion;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\RolesNegocio;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Restaurante de prueba que SÍ queda guardado, para hacer el recorrido a mano:
 * cada persona (mesero, caja, cocina, bar) entra con su usuario en otro celular o ventana de incógnito.
 */
class RecorridoRestaurante
{
    public const ORIGEN = 'qa-restaurante';
    public const DOMINIO = '@prueba.localproo.com';
    public const PERSONAL = [
        'admin' => ['Dueño (prueba)', null],
        'mesero' => ['Mesero (prueba)', 'Mesero'],
        'caja' => ['Caja (prueba)', 'Caja'],
        'cocina' => ['Cocina (prueba)', 'Cocina'],
        'bar' => ['Bar (prueba)', 'Bar y bebidas'],
    ];

    public static function tienda(): ?Tienda
    {
        return Tienda::where('origen', self::ORIGEN)->first();
    }

    // Crea (o deja al día) el restaurante de prueba y pone una clave nueva a todos. Devuelve la clave.
    public static function preparar(): string
    {
        $clave = 'Prueba' . random_int(1000, 9999);
        DB::transaction(function () use ($clave) {
            $t = self::tienda();
            if (!$t) {
                [$t] = CrearTienda::crear(
                    ['nombre' => 'Restaurante de prueba (QA)', 'pais' => 'CO', 'telefono' => '3000000000', 'tipo_negocio' => 'restaurante', 'ciudad' => 'Pasto'],
                    ['name' => self::PERSONAL['admin'][0], 'email' => 'qa.admin' . self::DOMINIO, 'password' => $clave, 'telefono' => '3000000000']
                );
            }
            // Nunca vence ni entra a la depuración de pruebas vencidas
            $t->forceFill(array_fill_keys(Tienda::MODULOS_POR_TIPO['restaurante'], true) + [
                'origen' => self::ORIGEN, 'plan_vence' => today()->addYears(10), 'onboarding_paso' => 5, 'onboarding_completo' => true,
            ])->save();
            RolesNegocio::crearPorDefecto($t->fresh());

            TiendaActual::como($t->id, function () use ($t, $clave) {
                $roles = NegocioRol::get()->keyBy('nombre');
                foreach (self::PERSONAL as $k => [$nombre, $rol]) {
                    $u = User::where('email', "qa.$k" . self::DOMINIO)->first()
                        ?? User::create(['name' => $nombre, 'email' => "qa.$k" . self::DOMINIO, 'password' => Hash::make($clave), 'tienda_id' => $t->id]);
                    $u->forceFill(['password' => Hash::make($clave), 'tienda_id' => $t->id])->save();
                    if ($k === 'admin') {
                        if (!$u->hasRole('dueno')) $u->assignRole('dueno');
                    } elseif (isset($roles[$rol])) {
                        RolesNegocio::asignar($u, $roles[$rol]);
                    }
                }
                // Carta de ejemplo con su estación
                if (!Producto::exists()) {
                    $est = PedidoEstacion::pluck('id', 'nombre');
                    $cat = CrearTienda::categoria('Platos');
                    $beb = CrearTienda::categoria('Bebidas');
                    foreach ([
                        ['Hamburguesa de la casa', 18000, 'Cocina', $cat], ['Salchipapa', 14000, 'Cocina', $cat], ['Bandeja paisa', 26000, 'Cocina', $cat],
                        ['Limonada natural', 6000, 'Bar y bebidas', $beb], ['Gaseosa', 4000, 'Bar y bebidas', $beb], ['Cerveza', 5000, 'Bar y bebidas', $beb],
                    ] as [$n, $precio, $e, $c]) {
                        $p = Producto::create(['codigo' => CrearTienda::siguienteCodigo(), 'nombre' => $n, 'precio_venta' => $precio, 'controla_inventario' => false]);
                        $p->forceFill(['estacion_id' => $est[$e] ?? null])->save();
                        $p->categorias()->attach($c->id);
                        $p->variantes()->create(['talla' => null, 'color' => null, 'stock' => 0]);
                    }
                }
                // Caja abierta para cobrar de una
                $cajero = User::where('email', 'qa.caja' . self::DOMINIO)->first();
                if ($cajero && !Caja::abiertaDe($cajero->id)) {
                    Caja::create(['user_id' => $cajero->id, 'abierta_en' => now(), 'monto_inicial' => 0, 'estado' => 'abierta']);
                }
            });
        });
        return $clave;
    }

    public static function usuarios(): array
    {
        $out = [];
        foreach (self::PERSONAL as $k => [$nombre, $rol]) {
            $out[] = ['rol' => $rol ?: 'Dueño / administrador', 'email' => "qa.$k" . self::DOMINIO, 'existe' => User::where('email', "qa.$k" . self::DOMINIO)->exists()];
        }
        return $out;
    }
}
