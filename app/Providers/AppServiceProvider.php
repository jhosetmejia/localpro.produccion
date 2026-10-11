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

        // En producción todo enlace (asesores, catálogos, correos) sale con el dominio oficial, entren por donde entren
        if ($this->app->environment('production') && config('app.dominio')) {
            \Illuminate\Support\Facades\URL::forceRootUrl('https://' . config('app.dominio'));
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Correo de recuperación de contraseña en español
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Cree su nueva contraseña de LocalPro')
                ->greeting('Hola ' . strtok($user->name, ' ') . ',')
                ->line('Recibimos una solicitud para cambiar la contraseña de su cuenta en LocalPro.')
                ->action('Crear contraseña nueva', $url)
                ->line('El enlace vence en 60 minutos. Si usted no lo pidió, ignore este correo.')
                ->salutation('Equipo LocalPro');
        });

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
