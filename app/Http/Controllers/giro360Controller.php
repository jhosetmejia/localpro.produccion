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
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'precision' => 'nullable|numeric|min:0',
        ], [
            'nombre.required' => 'Escriba el nombre.',
            'nombre.min' => 'Escriba el nombre.',
            'whatsapp.required' => 'Escriba el WhatsApp.',
            'acepta.accepted' => 'Debe aceptar el tratamiento de datos para guardar.',
        ]);
        $wa = self::celular($d['whatsapp']);
        if (!$wa) {
            return response()->json(['message' => 'Escriba un celular válido: 10 números que empiecen por 3 (ej: 300 123 4567).'], 422);
        }
        DB::table('giro360_clientes')->insert([
            'nombre' => strip_tags($d['nombre']),
            'whatsapp' => $wa,
            'evento' => isset($d['evento']) ? strip_tags($d['evento']) : null,
            'efecto' => $d['efecto'] ?? null,
            'acepta' => true,
            'ip' => $request->ip(),
            'latitud' => isset($d['latitud'], $d['longitud']) ? round((float) $d['latitud'], 7) : null,
            'longitud' => isset($d['latitud'], $d['longitud']) ? round((float) $d['longitud'], 7) : null,
            'precision_m' => isset($d['latitud'], $d['precision']) ? (int) min(4000000000, round((float) $d['precision'])) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }

    /**
     * Celular válido: Colombia = 10 dígitos que empiezan por 3 (se guarda como 57XXXXXXXXXX);
     * otro país = con indicativo (+), 8 a 15 dígitos. Devuelve null si no sirve.
     */
    public static function celular(string $t): ?string
    {
        $intl = str_starts_with(trim($t), '+') || str_starts_with(trim($t), '00');
        $n = preg_replace('/\D/', '', $t);
        if (str_starts_with($n, '00')) $n = substr($n, 2);
        if (strlen($n) === 10 && $n[0] === '3' && !$intl) return '57' . $n;
        if (strlen($n) === 12 && str_starts_with($n, '573')) return $n;
        if (str_starts_with($n, '57')) return null;
        if ($intl && strlen($n) >= 8 && strlen($n) <= 15) return $n;
        return null;
    }

    // ===== Logos del 360 (los sube el súper administrador; salen en las opciones de todos los celulares) =====
    private const LOGOS_INDICE = 'giro360/logos.json';

    public static function logosLista(): array
    {
        $d = \Illuminate\Support\Facades\Storage::disk('public');
        $l = $d->exists(self::LOGOS_INDICE) ? json_decode($d->get(self::LOGOS_INDICE), true) : [];
        return array_values(array_filter(is_array($l) ? $l : [], fn ($x) => !empty($x['archivo']) && $d->exists($x['archivo'])));
    }

    public function logos()
    {
        $d = \Illuminate\Support\Facades\Storage::disk('public');
        $out = array_map(fn ($x) => ['id' => $x['id'], 'nombre' => $x['nombre'], 'url' => '/storage/' . $x['archivo']], self::logosLista());
        return response()->json($out)->header('Cache-Control', 'public, max-age=60');
    }

    public function subirLogo(Request $request)
    {
        $d = $request->validate([
            'nombre' => 'required|string|max:40',
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
        ], [
            'nombre.required' => 'Escriba el nombre del logo (ej: Boda Ana y Luis).',
            'logo.required' => 'Elija la imagen del logo.',
            'logo.image' => 'El archivo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser PNG, JPG o WEBP.',
            'logo.max' => 'El logo pesa demasiado (máximo 5 MB).',
        ]);
        $id = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
        $ext = strtolower($request->file('logo')->getClientOriginalExtension() ?: 'png');
        $archivo = $request->file('logo')->storeAs('giro360/logos', \Illuminate\Support\Str::slug($d['nombre']) . '-' . $id . '.' . $ext, 'public');
        $lista = self::logosLista();
        array_unshift($lista, ['id' => $id, 'nombre' => strip_tags($d['nombre']), 'archivo' => $archivo, 'creado' => now()->toDateTimeString()]);
        \Illuminate\Support\Facades\Storage::disk('public')->put(self::LOGOS_INDICE, json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if ($request->expectsJson()) {
            return response()->json(['id' => $id, 'nombre' => strip_tags($d['nombre']), 'url' => '/storage/' . $archivo]);
        }
        return redirect()->route('giro360.avanzado')->with('ok', 'Logo "' . $d['nombre'] . '" subido. Ya sale en las opciones de Logo de todos los celulares.');
    }

    public function borrarLogo(string $id)
    {
        $lista = self::logosLista();
        $disco = \Illuminate\Support\Facades\Storage::disk('public');
        foreach ($lista as $i => $x) {
            if ($x['id'] === $id) { $disco->delete($x['archivo']); unset($lista[$i]); }
        }
        $disco->put(self::LOGOS_INDICE, json_encode(array_values($lista), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return redirect()->route('giro360.avanzado')->with('ok', 'Logo quitado de las opciones.');
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
            fputcsv($o, ['Nombre', 'WhatsApp', 'Evento', 'Efecto', 'Fecha', 'Latitud', 'Longitud', 'Mapa'], ';');
            foreach ($filas as $c) {
                $lat = $c->latitud ?? null; $lng = $c->longitud ?? null;
                fputcsv($o, [$c->nombre, $c->whatsapp, $c->evento, $c->efecto, \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y H:i'), $lat, $lng, $lat !== null ? 'https://www.google.com/maps?q=' . $lat . ',' . $lng : ''], ';');
            }
            fclose($o);
        }, 'clientes-360-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
