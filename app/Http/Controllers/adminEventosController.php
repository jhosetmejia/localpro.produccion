<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\EventoEntrada;
use Illuminate\Http\Request;

/**
 * Superadministrador: todos los eventos de todos los negocios en un solo lugar.
 * "Abrir" o "Editar" entra al negocio dueño del evento y lleva directo al evento.
 */
class adminEventosController extends Controller
{
    public function index(Request $request)
    {
        $q = Evento::withoutGlobalScope('tienda')->with('tienda:id,nombre,codigo');
        if ($b = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('nombre', 'like', '%' . $b . '%')->orWhere('slug', 'like', '%' . $b . '%')
                ->orWhereHas('tienda', fn ($t) => $t->where('nombre', 'like', '%' . $b . '%')));
        }
        $eventos = $q->orderByRaw("estado = 'cerrado'")->orderByDesc('fecha_evento')->orderByDesc('id')->paginate(30)->withQueryString();

        $ids = $eventos->pluck('id');
        $conteo = EventoEntrada::withoutGlobalScope('tienda')->whereIn('evento_id', $ids)
            ->selectRaw("evento_id, SUM(estado = 'pendiente') pendientes, SUM(estado = 'aprobada') aprobadas, SUM(estado = 'aprobada' AND ingreso_at IS NOT NULL) ingresaron, SUM(CASE WHEN estado = 'aprobada' THEN precio ELSE 0 END) recaudo")
            ->groupBy('evento_id')->get()->keyBy('evento_id');

        return view('admin.eventos', ['eventos' => $eventos, 'conteo' => $conteo]);
    }

    public function abrir(Request $request, int $evento)
    {
        $ev = Evento::withoutGlobalScope('tienda')->findOrFail($evento);
        $request->session()->put('tienda_visitada', $ev->tienda_id);
        $destino = match ($request->input('a')) {
            'editar' => 'eventos.edit',
            'puerta' => 'eventos.puerta',
            default => 'eventos.show',
        };
        return redirect()->route($destino, $ev->id);
    }
}
