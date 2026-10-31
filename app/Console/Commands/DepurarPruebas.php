<?php

namespace App\Console\Commands;

use App\Models\Ajuste;
use App\Models\Tienda;
use App\Support\CrearTienda;
use App\Support\AvisosPlan;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Negocios que terminaron su prueba gratis (1 mes, 3 meses o lo que diga la promoción) y no pasaron a un plan pago:
 * se borran productos, imágenes, ventas, clientes, inventario… para no llenar el servidor.
 * Se conservan los datos de contacto: el negocio (nombre, NIT, teléfono, correo, ciudad, tipo) y sus usuarios.
 * Así lo dicen las Condiciones de uso que aceptan al registrarse.
 *
 * Uso: php artisan localpro:depurar-pruebas            (corre solo cada día)
 *      php artisan localpro:depurar-pruebas --simular  (muestra qué borraría, sin borrar)
 *      php artisan localpro:depurar-pruebas --tienda=25
 */
class DepurarPruebas extends Command
{
    protected $signature = 'localpro:depurar-pruebas {--simular : Solo muestra qué negocios se depurarían} {--tienda= : Depurar solo este negocio (id)}';
    protected $description = 'Borra los datos de los negocios cuya prueba gratis terminó sin pasar a un plan pago (conserva los datos de contacto)';

    // Tablas que NO se tocan: datos del negocio, usuarios, pagos, red comercial y configuración básica
    // Historia clínica (consultorios): la ley obliga a conservarla (Res. 839 de 2017, 15 años); nunca se borra con la depuración
    private const CONSERVAR = ['tiendas', 'users', 'avisos_plan', 'pagos_suscripcion', 'comisiones', 'asesores', 'planes', 'ajustes', 'metodos_pago',
        'pacientes', 'citas', 'atenciones', 'atencion_notas', 'clinica_accesos'];

    // Días para pagar, contados desde que se le notificó que la prueba terminó (se puede cambiar en ajustes: dias_gracia_depuracion)
    public const DIAS_GRACIA = 15;

    // Por ahora LocalProo no borra la información de nadie (estamos creciendo): se enciende con el ajuste depurar_activo = 1
    public static function activo(): bool
    {
        return (bool) (int) Ajuste::valor('depurar_activo', 0);
    }

    public static function diasGracia(): int
    {
        return max(0, (int) Ajuste::valor('dias_gracia_depuracion', self::DIAS_GRACIA));
    }

    public function handle(): int
    {
        if (!self::activo()) {
            $this->info('La depuración está apagada (ajuste depurar_activo = 0): no se borra nada.');
            return self::SUCCESS;
        }
        $q = Tienda::query()
            ->whereNull('datos_depurados_at')
            ->whereNotNull('plan_vence')
            ->whereDate('plan_vence', '<', today()->subDays(self::diasGracia()))
            // Sigue en un plan gratis (prueba) o sin plan
            ->where(fn ($w) => $w->whereNull('plan_id')->orWhereHas('plan', fn ($p) => $p->where('precio', '<=', 0)))
            // Negocios del súper administrador nunca se depuran
            ->whereDoesntHave('usuarios', fn ($u) => $u->where('es_superadmin', true))
            // LocalPro gratis: si tiene emprendedores invitados en los últimos 30 días, se espera a que cumplan los pasos
            ->whereNotExists(fn ($w) => $w->selectRaw('1')->from('tiendas as inv')->whereColumn('inv.referida_por_tienda_id', 'tiendas.id')
                ->whereColumn('inv.id', '!=', 'tiendas.id')->where('inv.created_at', '>=', now()->subDays(30)));

        if ($this->option('tienda')) {
            $q->whereKey((int) $this->option('tienda'));
        }

        // Solo si nunca pagó, ya se le notificó (correo enviado o pantalla vista) y pasaron los días avisados
        $tiendas = $q->get()->filter(function (Tienda $t) {
            if (!AvisosPlan::esPrueba($t) || !AvisosPlan::primeraNotificacion($t)) {
                return false;
            }
            $f = AvisosPlan::fechaBorrado($t);
            return $f && $f->lte(today());
        });
        if ($tiendas->isEmpty()) {
            $this->info('No hay negocios para depurar.');
            return self::SUCCESS;
        }

        $tablas = $this->tablasDeTienda();
        foreach ($tiendas as $t) {
            $linea = "$t->codigo $t->nombre (prueba venció " . $t->plan_vence->format('d/m/Y') . ')';
            if ($this->option('simular')) {
                $this->line('Se depuraría: ' . $linea . ' · productos: ' . DB::table('productos')->where('tienda_id', $t->id)->count());
                continue;
            }
            try {
                $fecha = AvisosPlan::fechaBorrado($t);
                $this->depurar($t, $tablas);
                AvisosPlan::enviar($t->fresh(), 'datos_eliminados', $fecha);
                $this->info('Depurado: ' . $linea);
                Log::info('Depuración de prueba vencida', ['tienda' => $t->id, 'codigo' => $t->codigo]);
            } catch (\Throwable $e) {
                $this->error("No se pudo depurar $linea: " . $e->getMessage());
                Log::error('Depuración de prueba vencida falló', ['tienda' => $t->id, 'error' => $e->getMessage()]);
            }
        }
        return self::SUCCESS;
    }

    private function depurar(Tienda $t, array $tablas): void
    {
        // Archivos a borrar (se borran después de confirmar la base de datos)
        $imagenes = DB::table('productos')->where('tienda_id', $t->id)->whereNotNull('img_path')->pluck('img_path')->all();

        DB::transaction(function () use ($t, $tablas) {
            // Se borra en varias vueltas: las tablas que dependen de otras quedan para la siguiente vuelta
            $pendientes = $tablas;
            for ($vuelta = 0; $pendientes && $vuelta < 12; $vuelta++) {
                $siguen = [];
                foreach ($pendientes as $tabla) {
                    try {
                        DB::transaction(fn () => DB::table($tabla)->where('tienda_id', $t->id)->delete());
                    } catch (\Illuminate\Database\QueryException $e) {
                        $siguen[] = $tabla;
                    }
                }
                if (count($siguen) === count($pendientes)) {
                    throw new \RuntimeException('No se pudieron borrar: ' . implode(', ', $siguen));
                }
                $pendientes = $siguen;
            }
            if ($pendientes) {
                throw new \RuntimeException('Quedaron tablas sin borrar: ' . implode(', ', $pendientes));
            }

            $t->forceFill(['datos_depurados_at' => now(), 'catalogo_activo' => false])->save();
        });

        // Categoría inicial para que pueda volver a empezar si paga después
        TiendaActual::como($t->id, fn () => CrearTienda::categoria('General'));

        foreach ($imagenes as $img) {
            Storage::disk('public')->delete('productos/' . $img);
        }
        Storage::disk('public')->deleteDirectory('redes/' . $t->id);
        Storage::disk('local')->deleteDirectory('importaciones/' . $t->id);
        Storage::disk('local')->deleteDirectory('alquileres/' . $t->id); // fotos de documentos de respaldo
        Storage::disk('public')->deleteDirectory('obituarios/' . $t->id);
        Storage::disk('public')->deleteDirectory('eventos/' . $t->id); // afiches de eventos
        Storage::disk('local')->deleteDirectory('contabilidad/' . $t->id); // archivos de la DIAN y contables // fotos, fondos y videos de obituarios
    }

    // Todas las tablas que tienen tienda_id, menos las que se conservan
    private function tablasDeTienda(): array
    {
        $bd = DB::getDatabaseName();
        $tablas = DB::table('information_schema.columns')
            ->where('table_schema', $bd)->where('column_name', 'tienda_id')
            ->selectRaw('TABLE_NAME as t')->pluck('t')->map(fn ($n) => (string) $n)->unique()->all();
        return array_values(array_filter($tablas, fn ($n) => !in_array($n, self::CONSERVAR, true) && Schema::hasTable($n)));
    }
}
