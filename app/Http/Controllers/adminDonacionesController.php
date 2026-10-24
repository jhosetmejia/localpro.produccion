<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\PagoSuscripcion;
use Illuminate\Http\Request;

/**
 * Superadministrador: campaña de donación al pagar el plan (Navidad).
 * Ver quién donó (para invitarlos a la entrega y ponerlos en los agradecimientos) y configurar la campaña.
 */
class adminDonacionesController extends Controller
{
    public function index()
    {
        $base = PagoSuscripcion::withoutGlobalScopes()->whereNotNull('donacion')->where('donacion', '>', 0);
        $donantes = (clone $base)->where('estado', 'aprobado')->with('tienda:id,nombre,codigo,telefono,email,ciudad')
            ->orderByDesc('updated_at')->get();
        return view('admin.donaciones', [
            'donantes' => $donantes,
            'total' => (float) $donantes->sum('donacion'),
            'pendientes' => (clone $base)->where('estado', 'pendiente')->count(),
            'activa' => (bool) (int) Ajuste::valor('donacion_activa', 0),
            'monto' => (float) Ajuste::valor('donacion_monto', 5000),
            'marcada' => (bool) (int) Ajuste::valor('donacion_marcada', 1),
        ]);
    }

    public function guardar(Request $request)
    {
        $d = $request->validate(['monto' => 'required|integer|min:1000|max:1000000']);
        foreach (['donacion_activa' => $request->boolean('activa') ? '1' : '0', 'donacion_monto' => (string) $d['monto'], 'donacion_marcada' => $request->boolean('marcada') ? '1' : '0'] as $k => $v) {
            Ajuste::updateOrCreate(['clave' => $k], ['valor' => $v]);
        }
        return back()->with('success', 'Campaña de donación guardada.');
    }

    public function exportar()
    {
        $filas = PagoSuscripcion::withoutGlobalScopes()->where('donacion', '>', 0)->where('estado', 'aprobado')->with('tienda')->orderBy('id')->get();
        return response()->streamDownload(function () use ($filas) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, ['Negocio', 'Código', 'Teléfono', 'Correo', 'Ciudad', 'Donación', 'Fecha'], ';');
            foreach ($filas as $p) {
                fputcsv($o, [$p->tienda?->nombre, $p->tienda?->codigo, $p->tienda?->telefono, $p->tienda?->email, $p->tienda?->ciudad, (float) $p->donacion, $p->updated_at?->format('d/m/Y')], ';');
            }
            fclose($o);
        }, 'donantes-navidad-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
