<?php

namespace App\Http\Middleware;

use App\Support\Imagenes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Toda imagen que llega en un formulario se achica y comprime antes de que el controlador la guarde.
 * Así ninguna parte del sistema llena el servidor con fotos de 5 MB, aunque se suba desde el celular.
 */
class OptimizarImagenes
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            foreach ($request->allFiles() as $campo => $archivo) {
                $this->revisar($campo, $archivo);
            }
        }
        return $next($request);
    }

    private function revisar(string $campo, $archivo): void
    {
        if (is_array($archivo)) {
            foreach ($archivo as $k => $a) $this->revisar($campo . '.' . $k, $a);
            return;
        }
        if (!$archivo instanceof UploadedFile || !$archivo->isValid()) return;
        if (!in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) return;
        try {
            Imagenes::optimizar($archivo->getRealPath(), Imagenes::maxPara($campo));
        } catch (\Throwable $e) {
            // Si algo falla, se guarda la imagen como llegó
            report($e);
        }
    }
}
