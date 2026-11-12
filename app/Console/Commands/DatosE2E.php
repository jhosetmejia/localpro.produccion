<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\TiendaActual;
use Illuminate\Console\Command;

/**
 * Datos para las pruebas automáticas del navegador (tests/e2e, Playwright en GitHub).
 * Crea un negocio listo para vender, igual que el registro: dueño, métodos de pago, categoría y 2 productos con existencia.
 * Solo corre en la base de las pruebas de GitHub: nunca en producción ni en el sitio de pruebas.
 */
class DatosE2E extends Command
{
    protected $signature = 'localpro:datos-e2e';
    protected $description = 'Crea el negocio de prueba para las pruebas automáticas del navegador (solo en GitHub)';

    public const CORREO = 'dueno-e2e@prueba.localproo.com';
    public const CLAVE = 'PruebaE2E-2026';

    public function handle(): int
    {
        if (app()->environment('production') || env('LOCALPRO_E2E') !== '1') {
            $this->error('Esto solo corre en las pruebas automáticas de GitHub (LOCALPRO_E2E=1).');
            return self::FAILURE;
        }
        if (User::where('email', self::CORREO)->exists()) {
            $this->info('Ya estaban los datos de prueba.');
            return self::SUCCESS;
        }

        [$t] = CrearTienda::crear(
            ['nombre' => 'Tienda E2E', 'pais' => 'CO', 'departamento' => 'Antioquia', 'ciudad' => 'Medellín', 'telefono' => '3000000000', 'tipo_negocio' => 'moda'],
            ['name' => 'Dueño E2E', 'email' => self::CORREO, 'password' => self::CLAVE, 'telefono' => '3000000000']
        );
        $t->forceFill(['onboarding_completo' => true, 'onboarding_paso' => 5, 'plan_vence' => today()->addYear()])->save();
        User::where('email', self::CORREO)->update(['terminos_aceptados_at' => now()]);

        TiendaActual::como($t->id, function () {
            $cat = CrearTienda::categoria('General');
            foreach ([['Camiseta E2E', 35000, 20], ['Gorra E2E', 20000, 10]] as [$nombre, $precio, $stock]) {
                $p = Producto::create(['codigo' => CrearTienda::siguienteCodigo(), 'nombre' => $nombre, 'precio_venta' => $precio, 'controla_inventario' => true]);
                $p->categorias()->attach($cat->id);
                $p->variantes()->create(['talla' => null, 'color' => null, 'stock' => $stock]);
            }
        });

        $this->info('Negocio de prueba listo: ' . self::CORREO);
        return self::SUCCESS;
    }
}
