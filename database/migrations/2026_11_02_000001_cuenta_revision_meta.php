<?php

use App\Models\Caja;
use App\Models\Plan;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\TiendaActual;
use Illuminate\Database\Migrations\Migration;

// Cuenta de prueba para los revisores de Meta (revisión de la app "Mis redes").
// Negocio de ejemplo con productos, plan anual por un año largo y la caja abierta. No sale en el Marketplace
// (tienda en línea apagada). Si ya existe, no hace nada (nunca le cambia la clave).
return new class extends Migration
{
    private const CORREO = 'revision.meta@localproo.com';

    public function up(): void
    {
        if (User::where('email', self::CORREO)->exists()) return;

        [$t, $user] = CrearTienda::crear(
            ['nombre' => 'Tienda Demo LocalProo', 'pais' => 'CO', 'departamento' => 'Nariño', 'ciudad' => 'Pasto', 'tipo_negocio' => 'moda'],
            ['name' => 'Revisión Meta', 'email' => self::CORREO, 'password' => 'temporal-' . bin2hex(random_bytes(8))]
        );
        // Clave entregada a Meta (se guarda ya cifrada)
        User::whereKey($user->id)->update(['password' => '$2y$12$3UK5mVqgiQaVWwJVlPm4He.KgmDjc5q1tgnwkU5Vb7S.3qOL2kt5a', 'email_verified_at' => now()]);

        $anual = Plan::where('activo', true)->whereBetween('dias', [360, 400])->where('precio', '>', 0)->orderBy('orden')->first();
        $t->forceFill([
            'plan_id' => $anual?->id ?? $t->plan_id,
            'plan_vence' => today()->addDays(450),
            'onboarding_paso' => 5,
            'onboarding_completo' => true,
            'catalogo_activo' => false,
            'descripcion' => 'Cuenta de prueba para la revisión de Meta',
        ])->save();

        TiendaActual::como($t->id, function () use ($user) {
            $cat = CrearTienda::categoria('Ropa');
            foreach ([
                ['Camiseta básica algodón', 35000, ['S', 'M', 'L'], ['Negro', 'Blanco']],
                ['Jean clásico dama', 89000, ['8', '10', '12'], ['Azul']],
                ['Chaqueta impermeable', 130000, ['M', 'L'], ['Negro', 'Rojo']],
                ['Gorra bordada', 25000, [null], ['Negro']],
            ] as [$nombre, $precio, $tallas, $colores]) {
                $p = Producto::create(['codigo' => CrearTienda::siguienteCodigo(), 'nombre' => $nombre, 'precio_venta' => $precio, 'controla_inventario' => true]);
                $p->categorias()->attach($cat->id);
                foreach ($tallas as $ta) foreach ($colores as $co) {
                    $p->variantes()->create(['talla' => $ta, 'color' => $co, 'stock' => 5]);
                }
            }
            if (!Caja::where('estado', 'abierta')->exists()) {
                Caja::create(['user_id' => $user->id, 'abierta_en' => now(), 'monto_inicial' => 0, 'nota_apertura' => 'Primera caja', 'estado' => 'abierta']);
            }
        });
    }

    public function down(): void
    {
        // No se borra: la cuenta la usa Meta durante la revisión
    }
};
