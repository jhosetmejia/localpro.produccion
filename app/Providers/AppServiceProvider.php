<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Carbon\Carbon::setLocale('es');

        // La tienda se resuelve antes de cargar modelos desde la URL (aislamiento entre tiendas)
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        if (method_exists($kernel, 'addToMiddlewarePriorityBefore')) {
            $kernel->addToMiddlewarePriorityBefore(
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                \App\Http\Middleware\ResolverTienda::class
            );
        }

        // @pesos($valor) -> valor en la moneda del país de la tienda ($59.900, $59.90, S/59.90…)
        Blade::directive('pesos', fn ($e) => "<?php echo e(dinero($e)); ?>");

        //
    }
}
