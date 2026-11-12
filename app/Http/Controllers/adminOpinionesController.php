<?php

namespace App\Http\Controllers;

use App\Models\ErrorCliente;
use App\Models\Opinion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Súper administrador: lo que la gente reporta (fallas, ideas) y los errores del navegador que llegan solos.
 */
class adminOpinionesController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'abiertas');
        $tipo = $request->query('tipo');
        $modulo = $request->query('modulo');

        $q = Opinion::with(['user:id,name,email', 'tienda:id,nombre,telefono'])->latest();
        if ($estado === 'abiertas') $q->whereIn('estado', ['nuevo', 'revisando']);
        elseif (isset(Opinion::ESTADOS[$estado])) $q->where('estado', $estado);
        if ($tipo && isset(Opinion::TIPOS[$tipo])) $q->where('tipo', $tipo);
        if ($modulo) $q->where('modulo', $modulo);

        $vista = $request->query('ver') === 'errores' ? 'errores' : 'opiniones';
        $errores = ErrorCliente::query()
            ->when($request->query('resueltos') !== '1', fn ($x) => $x->where('resuelto', false))
            ->when($modulo, fn ($x) => $x->where('modulo', $modulo))
            ->orderByDesc('ultima_vez')->limit(150)->get();

        return view('admin.opiniones', [
            'opiniones' => $q->paginate(30)->withQueryString(),
            'errores' => $errores,
            'vista' => $vista,
            'estado' => $estado,
            'tipo' => $tipo,
            'modulo' => $modulo,
            'modulos' => Opinion::query()->distinct()->orderBy('modulo')->pluck('modulo')
                ->merge(ErrorCliente::query()->distinct()->pluck('modulo'))->filter()->unique()->values(),
            'cuenta' => [
                'nuevas' => Opinion::where('estado', 'nuevo')->count(),
                'abiertas' => Opinion::whereIn('estado', ['nuevo', 'revisando'])->count(),
                'fallas' => Opinion::where('tipo', 'falla')->whereIn('estado', ['nuevo', 'revisando'])->count(),
                'ideas' => Opinion::where('tipo', 'idea')->whereIn('estado', ['nuevo', 'revisando'])->count(),
                'errores' => ErrorCliente::where('resuelto', false)->count(),
                'errores_hoy' => ErrorCliente::where('resuelto', false)->where('ultima_vez', '>=', now()->startOfDay())->count(),
            ],
        ]);
    }

    /** Fallas: los errores que les salen a las personas, explicados en palabras (qué intentaban hacer, qué pasó, en qué equipo) */
    public function fallas(Request $request)
    {
        $verResueltas = $request->query('resueltas') === '1';
        $modulo = $request->query('modulo');
        $errores = ErrorCliente::query()
            ->when(!$verResueltas, fn ($x) => $x->where('resuelto', false))
            ->when($modulo, fn ($x) => $x->where('modulo', $modulo))
            ->orderByDesc('ultima_vez')->limit(200)->get();
        $escritas = Opinion::with(['user:id,name,email', 'tienda:id,nombre,telefono'])
            ->where('tipo', 'falla')->whereIn('estado', ['nuevo', 'revisando'])->latest()->limit(30)->get();

        return view('admin.fallas', [
            'errores' => $errores->map(fn ($e) => ['e' => $e, 'x' => \App\Support\ExplicarFalla::de($e)]),
            'escritas' => $escritas,
            'verResueltas' => $verResueltas,
            'modulo' => $modulo,
            'cuenta' => [
                'abiertas' => ErrorCliente::where('resuelto', false)->count(),
                'hoy' => ErrorCliente::where('ultima_vez', '>=', now()->startOfDay())->count(),
                'veces_semana' => (int) ErrorCliente::where('ultima_vez', '>=', now()->subDays(7))->sum('veces'),
                'personas' => (int) ErrorCliente::where('resuelto', false)->sum('usuarios'),
                'escritas' => $escritas->count(),
            ],
        ]);
    }

    public function estado(Request $request, Opinion $opinion)
    {
        $d = $request->validate([
            'estado' => 'required|in:' . implode(',', array_keys(Opinion::ESTADOS)),
            'respuesta' => 'nullable|string|max:2000',
        ]);
        $opinion->estado = $d['estado'];
        $resp = trim((string) ($d['respuesta'] ?? ''));
        if ($resp !== '' && $resp !== (string) $opinion->respuesta) {
            $opinion->respuesta = $resp;
            $opinion->respondido_at = now();
        }
        $opinion->save();
        return back()->with('success', 'Reporte #' . $opinion->id . ' actualizado.');
    }

    // La captura se guarda en el disco privado: solo el administrador la puede ver
    public function foto(Opinion $opinion)
    {
        abort_unless($opinion->foto && Storage::disk('local')->exists($opinion->foto), 404);
        return response()->file(Storage::disk('local')->path($opinion->foto), ['Cache-Control' => 'private, max-age=3600']);
    }

    public function errorResuelto(Request $request, ErrorCliente $error)
    {
        $error->resuelto = !$error->resuelto;
        $error->save();
        return back()->with('success', $error->resuelto ? 'Error marcado como resuelto (si vuelve a pasar, se reabre solo).' : 'Error reabierto.');
    }

    public function erroresLimpiar()
    {
        $n = ErrorCliente::where('resuelto', true)->where('ultima_vez', '<', now()->subDays(30))->delete();
        return back()->with('success', $n . ' error(es) viejos resueltos borrados.');
    }
}
