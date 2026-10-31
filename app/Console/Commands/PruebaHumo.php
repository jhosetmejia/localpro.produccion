<?php

namespace App\Console\Commands;

use App\Models\Tienda;
use App\Models\User;
use App\Support\TiendaActual;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Prueba de humo después de cada actualización (la corre deploy/actualizar.sh):
 *  1. Revisa que todas las plantillas compiladas sean PHP válido (un @if mal escrito rompe páginas enteras).
 *  2. Abre por dentro las páginas principales: públicas, del panel (como dueño de un negocio) y de administración.
 * Si alguna da error 500, termina con código 1 y la actualización se devuelve sola a la versión anterior.
 */
class PruebaHumo extends Command
{
    protected $signature = 'localpro:prueba-humo {--sin-vistas : No revisar las plantillas compiladas}';
    protected $description = 'Revisa que las páginas principales abran sin error 500 (se usa al actualizar)';

    private array $fallas = [];

    public function handle(): int
    {
        if (!$this->option('sin-vistas')) $this->revisarVistas();

        $dueno = User::where('es_superadmin', false)->whereNotNull('tienda_id')
            ->whereHas('tienda', fn ($q) => $q->where('estado', 'activa')->where('onboarding_completo', true))
            ->whereHas('roles', fn ($q) => $q->where('name', 'dueno'))
            ->orderBy('id')->first();
        $admin = User::where('es_superadmin', true)->orderBy('id')->first();
        $tienda = Tienda::where('catalogo_activo', true)->where('estado', 'activa')->where('onboarding_completo', true)->whereNotNull('slug')->orderBy('id')->first();

        // Públicas
        $publicas = ['login', 'registro', 'marketplace', 'giro360', 'giro360.audio', 'legal.terminos', 'password.request'];
        foreach ($publicas as $r) $this->abrir($r);
        if ($tienda) {
            $this->abrir('catalogo.show', [$tienda->slug]);
            if ($tienda->usa_carta) { $this->abrir('catalogo.carta', [$tienda->slug]); $this->abrir('catalogo.carta.imprimir', [$tienda->slug]); }
        }

        // Panel de un negocio
        if ($dueno) {
            foreach (['panel', 'primeros.pasos', 'negocio.edit', 'productos.index', 'clientes.index', 'pos.index', 'plan.index', 'plan.vencido', 'beneficios.index', 'carta.index', 'enlaces.index', 'menu.edit'] as $r) {
                $this->abrir($r, [], $dueno);
            }
        } else {
            $this->warn('No hay un dueño de negocio para probar el panel.');
        }

        // Administración
        if ($admin) {
            foreach (['admin.inicio', 'admin.usuarios', 'admin.tiendas.index', 'admin.pagos', 'admin.giro360', 'admin.eventos', 'admin.donaciones'] as $r) {
                $this->abrir($r, [], $admin);
            }
        }

        if ($this->fallas) {
            $this->error('FALLARON ' . count($this->fallas) . ' página(s):');
            foreach ($this->fallas as $f) $this->error('  - ' . $f);
            return self::FAILURE;
        }
        $this->info('Todo bien: las páginas principales abren sin error.');
        return self::SUCCESS;
    }

    // Cada plantilla compilada debe ser PHP válido
    private function revisarVistas(): void
    {
        $archivos = glob(storage_path('framework/views/*.php')) ?: [];
        $malas = 0;
        foreach ($archivos as $f) {
            $salida = [];
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $salida, $codigo);
            if ($codigo !== 0) {
                $malas++;
                $this->fallas[] = 'Plantilla con error: ' . trim(implode(' ', $salida));
            }
        }
        $this->line('Plantillas revisadas: ' . count($archivos) . ($malas ? " ({$malas} con error)" : ' (todas bien)'));
    }

    private function abrir(string $ruta, array $param = [], ?User $como = null): void
    {
        if (!Route::has($ruta)) return;
        $url = route($ruta, $param);
        TiendaActual::set(null);
        Auth::guard('web')->forgetUser();
        if ($como) Auth::guard('web')->setUser($como);
        try {
            $kernel = app(Kernel::class);
            $req = Request::create($url, 'GET', [], [], [], ['HTTP_USER_AGENT' => 'LocalProo prueba de humo', 'HTTP_ACCEPT' => 'text/html']);
            $res = $kernel->handle($req);
            $codigo = $res->getStatusCode();
            $kernel->terminate($req, $res);
            if ($codigo >= 500) {
                $detalle = $res->exception ?? null;
                $this->fallas[] = "{$ruta} ({$url}) respondió {$codigo}" . ($detalle ? ': ' . mb_substr($detalle->getMessage(), 0, 220) . ' en ' . basename($detalle->getFile()) . ':' . $detalle->getLine() : '');
                $this->line("  ✗ {$ruta} → {$codigo}");
            } else {
                $this->line("  ✓ {$ruta} → {$codigo}" . ($como ? ' (como ' . ($como->es_superadmin ? 'admin' : 'dueño') . ')' : ''));
            }
        } catch (\Throwable $e) {
            $this->fallas[] = "{$ruta}: " . mb_substr($e->getMessage(), 0, 220) . ' en ' . basename($e->getFile()) . ':' . $e->getLine();
            $this->line("  ✗ {$ruta} → excepción");
        } finally {
            Auth::guard('web')->forgetUser();
            TiendaActual::set(null);
        }
    }
}
