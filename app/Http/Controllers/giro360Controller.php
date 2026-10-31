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
            'tienda_id' => self::tienda360()?->id,
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

    // Código del operador (después de cobrar el video): del tercer video en adelante
    public function codigo(Request $request)
    {
        $c = mb_strtolower(preg_replace('/\s+/', '', (string) $request->input('codigo')));
        $bueno = mb_strtolower((string) config('services.giro360.codigo', '360pasto'));
        if ($c === '' || !hash_equals($bueno, $c)) {
            return response()->json(['message' => 'Código incorrecto. Pídalo al operador de la máquina.'], 422);
        }
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

    // ===== Quién guarda logos y canciones =====
    // Súper administrador: los de LocalProo, que salen para todos.
    // Negocio que alquila plataforma 360 (con sesión iniciada): los suyos, en su cuenta, en todos sus celulares.
    public static function tienda360(): ?\App\Models\Tienda
    {
        if (!auth()->check() || auth()->user()->es_superadmin) return null;
        $t = \App\Support\TiendaActual::tienda();
        return $t && $t->mod_360 ? $t : null;
    }

    public static function puedeSubir(): bool
    {
        return (bool) (auth()->user()?->es_superadmin || self::tienda360());
    }

    // Carpeta de la cuenta: la general de LocalProo o la del negocio
    private static function carpeta(?\App\Models\Tienda $t = null): string
    {
        return $t ? 'giro360/t' . $t->id : 'giro360';
    }

    private static function leerIndice(string $archivo): array
    {
        $d = \Illuminate\Support\Facades\Storage::disk('public');
        $l = $d->exists($archivo) ? json_decode($d->get($archivo), true) : [];
        return array_values(array_filter(is_array($l) ? $l : [], fn ($x) => !empty($x['archivo']) && $d->exists($x['archivo'])));
    }

    // ===== Logos del 360 =====
    /** Logos para elegir: los del negocio (si es un negocio 360) y los de LocalProo. $soloPropios = solo los que esta cuenta puede quitar */
    public static function logosLista(bool $soloPropios = false): array
    {
        $t = self::tienda360();
        $propios = self::leerIndice(self::carpeta($t) . '/logos.json');
        return $soloPropios || !$t ? $propios : array_merge($propios, self::leerIndice('giro360/logos.json'));
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
        $base = self::carpeta(self::tienda360());
        $archivo = $request->file('logo')->storeAs($base . '/logos', \Illuminate\Support\Str::slug($d['nombre']) . '-' . $id . '.' . $ext, 'public');
        $lista = self::logosLista(true);
        array_unshift($lista, ['id' => $id, 'nombre' => strip_tags($d['nombre']), 'archivo' => $archivo, 'creado' => now()->toDateTimeString()]);
        \Illuminate\Support\Facades\Storage::disk('public')->put($base . '/logos.json', json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        if ($request->expectsJson()) {
            return response()->json(['id' => $id, 'nombre' => strip_tags($d['nombre']), 'url' => '/storage/' . $archivo]);
        }
        return redirect()->route('giro360.avanzado')->with('ok', 'Logo "' . $d['nombre'] . '" subido. Ya sale en las opciones de Logo.');
    }

    public function borrarLogo(string $id)
    {
        $lista = self::logosLista(true);
        $disco = \Illuminate\Support\Facades\Storage::disk('public');
        foreach ($lista as $i => $x) {
            if ($x['id'] === $id) { $disco->delete($x['archivo']); unset($lista[$i]); }
        }
        $disco->put(self::carpeta(self::tienda360()) . '/logos.json', json_encode(array_values($lista), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return redirect()->route('giro360.avanzado')->with('ok', 'Logo quitado de las opciones.');
    }

    // ===== Canciones del 360 (las sube el súper administrador; salen para todos). Lo que sube la gente queda solo en su celular =====
    public static function cancionesLista(bool $soloPropios = false): array
    {
        $t = self::tienda360();
        $propias = self::leerIndice(self::carpeta($t) . '/canciones.json');
        return $soloPropios || !$t ? $propias : array_merge($propias, self::leerIndice('giro360/canciones.json'));
    }

    public static function cancionesJs(): array
    {
        return array_map(fn ($x) => ['id' => 's' . $x['id'], 'nombre' => $x['nombre'], 'url' => '/storage/' . $x['archivo']], self::cancionesLista());
    }

    public function subirCancion(Request $request)
    {
        $d = $request->validate([
            'nombre' => 'required|string|max:40',
            'cancion' => 'required|file|mimes:mp3,mpga,m4a,aac,wav,ogg,oga,webm|max:20480',
        ], [
            'nombre.required' => 'Escriba el nombre de la canción.',
            'cancion.required' => 'Elija el audio.',
            'cancion.mimes' => 'El audio debe ser MP3, M4A, WAV u OGG.',
            'cancion.max' => 'El audio pesa demasiado (máximo 20 MB).',
        ]);
        $id = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
        $ext = strtolower($request->file('cancion')->getClientOriginalExtension() ?: 'mp3');
        $base = self::carpeta(self::tienda360());
        $archivo = $request->file('cancion')->storeAs($base . '/canciones', \Illuminate\Support\Str::slug($d['nombre']) . '-' . $id . '.' . $ext, 'public');
        $lista = self::cancionesLista(true);
        array_unshift($lista, ['id' => $id, 'nombre' => strip_tags($d['nombre']), 'archivo' => $archivo, 'creado' => now()->toDateTimeString()]);
        \Illuminate\Support\Facades\Storage::disk('public')->put($base . '/canciones.json', json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return response()->json(['id' => 's' . $id, 'nombre' => strip_tags($d['nombre']), 'url' => '/storage/' . $archivo]);
    }

    public function borrarCancion(string $id)
    {
        $lista = self::cancionesLista(true);
        $disco = \Illuminate\Support\Facades\Storage::disk('public');
        foreach ($lista as $i => $x) {
            if ($x['id'] === $id) { $disco->delete($x['archivo']); unset($lista[$i]); }
        }
        $disco->put(self::carpeta(self::tienda360()) . '/canciones.json', json_encode(array_values($lista), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return redirect()->route('giro360.avanzado')->with('ok', 'Canción quitada de las opciones.');
    }

    // ===== Superadministrador =====
    public function admin(Request $request)
    {
        return $this->listado($request, null);
    }

    // Plataforma 360 del negocio (menú del panel): sus invitados, sus logos y el acceso a la app
    public function mis(Request $request)
    {
        $t = self::tienda360();
        abort_unless($t, 403, 'Active "Alquilo plataforma 360" en Datos del negocio.');
        return $this->listado($request, $t);
    }

    public function misExportar(Request $request)
    {
        $t = self::tienda360();
        abort_unless($t, 403);
        return $this->exportarDe($request, $t);
    }

    private function listado(Request $request, ?\App\Models\Tienda $t)
    {
        $base = fn () => DB::table('giro360_clientes')->when($t, fn ($q) => $q->where('tienda_id', $t->id));
        $q = $base()->orderByDesc('id');
        if ($ev = trim((string) $request->query('evento'))) {
            $q->where('evento', $ev);
        }
        return view('admin.giro360', [
            'negocio' => $t,
            'rutaLista' => $t ? 'giro360.mis' : 'admin.giro360',
            'rutaExcel' => $t ? 'giro360.mis.exportar' : 'admin.giro360.exportar',
            'clientes' => $q->paginate(100)->withQueryString(),
            'eventos' => $base()->whereNotNull('evento')->where('evento', '!=', '')
                ->select('evento', DB::raw('count(*) as n'))->groupBy('evento')->orderByDesc(DB::raw('max(id)'))->limit(50)->get(),
            'total' => $base()->count(),
            'hoy' => $base()->where('created_at', '>=', today())->count(),
            'evento' => $ev,
        ]);
    }

    public function exportar(Request $request)
    {
        return $this->exportarDe($request, null);
    }

    private function exportarDe(Request $request, ?\App\Models\Tienda $t)
    {
        $q = DB::table('giro360_clientes')->when($t, fn ($w) => $w->where('tienda_id', $t->id))->orderBy('id');
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
