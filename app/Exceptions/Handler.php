<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 419 "La página expiró": pasa cuando el formulario se abrió antes de cerrar sesión, de una actualización
        // o hace mucho rato. En vez de la pantalla de error, se vuelve al formulario con un token nuevo para reintentar.
        // Ojo: Laravel convierte TokenMismatchException en HttpException(419) ANTES de llamar a estos callbacks,
        // por eso se atrapa la HttpException con código 419 (si se pide el tipo TokenMismatchException nunca entra).
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La página expiró. Recargue e intente de nuevo.'], 419);
            }
            $destino = $request->is('login') || $request->is('logout') ? route('login') : url()->previous();
            return redirect()->to($destino)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('info', 'Pasó mucho tiempo con la página abierta. Intente de nuevo.');
        });
    }
}
