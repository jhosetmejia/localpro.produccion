<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // 2026-11-17: LP Chat — límites por persona de LP Chat (no por IP: muchos comparten el wifi o la red del celular)
        foreach (['lpchat' => 240, 'lpchat-60' => 60, 'lpchat-30' => 30, 'lpchat-20' => 20, 'lpchat-10' => 10] as $nombreLim => $porMinuto) {
            RateLimiter::for($nombreLim, fn (Request $r) => Limit::perMinute($porMinuto)
                ->by($nombreLim.'|'.(\Illuminate\Support\Facades\Auth::guard('lpchat')->id() ?: $r->ip()).'|'.($r->route()?->getName() ?? '')));
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
