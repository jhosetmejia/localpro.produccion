<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Giro 360 (localproo.com/360): página pública para las máquinas de video 360.
 * Graba con la cámara del celular, edita en el mismo celular (velocidad curva, logo, texto, canción)
 * y guarda nombre + WhatsApp del cliente. El video nunca sube al servidor.
 */
class giro360Controller extends Controller
{
    public function index()
    {
        return view('giro360.index');
    }

    public function guardar(Request $request)
    {
        $d = $request->validate([
            'nombre' => 'required|string|min:2|max:80',
            'whatsapp' => 'required|string|max:25',
            'evento' => 'nullable|string|max:60',
            'efecto' => 'nullable|string|max:20',
            'acepta' => 'accepted',
        ], [
            'nombre.required' => 'Escriba el nombre.',
            'nombre.min' => 'Escriba el nombre.',
            'whatsapp.required' => 'Escriba el WhatsApp.',
            'acepta.accepted' => 'Debe aceptar el tratamiento de datos para guardar.',
        ]);
        $wa = preg_replace('/\D/', '', $d['whatsapp']);
        if (strlen($wa) < 7 || strlen($wa) > 15) {
            return response()->json(['message' => 'Escriba un WhatsApp válido.'], 422);
        }
        DB::table('giro360_clientes')->insert([
            'nombre' => strip_tags($d['nombre']),
            'whatsapp' => $wa,
            'evento' => isset($d['evento']) ? strip_tags($d['evento']) : null,
            'efecto' => $d['efecto'] ?? null,
            'acepta' => true,
            'ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }

    // ===== Superadministrador =====
    public function admin(Request $request)
    {
        $q = DB::table('giro360_clientes')->orderByDesc('id');
        if ($ev = trim((string) $request->query('evento'))) {
            $q->where('evento', $ev);
        }
        return view('admin.giro360', [
            'clientes' => $q->paginate(100)->withQueryString(),
            'eventos' => DB::table('giro360_clientes')->whereNotNull('evento')->where('evento', '!=', '')
                ->select('evento', DB::raw('count(*) as n'))->groupBy('evento')->orderByDesc(DB::raw('max(id)'))->limit(50)->get(),
            'total' => DB::table('giro360_clientes')->count(),
            'hoy' => DB::table('giro360_clientes')->where('created_at', '>=', today())->count(),
            'evento' => $ev,
        ]);
    }

    public function exportar(Request $request)
    {
        $q = DB::table('giro360_clientes')->orderBy('id');
        if ($ev = trim((string) $request->query('evento'))) {
            $q->where('evento', $ev);
        }
        $filas = $q->get();
        return response()->streamDownload(function () use ($filas) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, ['Nombre', 'WhatsApp', 'Evento', 'Efecto', 'Fecha'], ';');
            foreach ($filas as $c) {
                fputcsv($o, [$c->nombre, $c->whatsapp, $c->evento, $c->efecto, \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y H:i')], ';');
            }
            fclose($o);
        }, 'clientes-360-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
