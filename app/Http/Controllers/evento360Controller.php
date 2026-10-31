<?php

namespace App\Http\Controllers;

use App\Models\Alquiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Evento 360 de un alquiler: el negocio sube el logo (o varios) y la canción del evento,
 * y LocalProo le da un enlace y un QR. Los invitados lo escanean y graban con eso ya puesto;
 * sus nombres y WhatsApp quedan en el negocio, marcados con el evento.
 */
class evento360Controller extends Controller
{
    const MAX_LOGOS = 5;

    // Canciones listas de la app (las mismas de /360)
    const CANCIONES_APP = [
        'hw-lp' => ['Halloween · LocalProo', '/audio/giro360/halloween.mp3'],
        'hw-bach' => ['Tocata de terror (Bach)', '/audio/giro360/tocata-bach.mp3'],
        'electro' => ['Electrónica · LocalProo', '/audio/giro360/electronica.mp3'],
    ];

    public function __construct()
    {
        $this->middleware('auth')->except('publico');
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('usar-alquileres') || $u->can('usar-pos')), 403);
            return $next($request);
        })->except('publico');
    }

    public function form(Alquiler $alquiler)
    {
        abort_unless(\App\Support\TiendaActual::tienda()?->mod_alquiler, 404);
        return view('alquiler.evento360', [
            'a' => $alquiler,
            'cfg' => $alquiler->e360 ?: [],
            'url' => $alquiler->e360_token ? route('giro360.evento', $alquiler->e360_token) : null,
            'canciones' => self::opcionesCancion(),
        ]);
    }

    public function guardar(Request $request, Alquiler $alquiler)
    {
        abort_unless(\App\Support\TiendaActual::tienda()?->mod_alquiler, 404);
        $d = $request->validate([
            'nombre' => 'required|string|max:60',
            'logos' => 'nullable|array|max:' . self::MAX_LOGOS,
            'logos.*' => 'image|mimes:png,jpg,jpeg,webp|max:5120',
            'quitar' => 'nullable|array',
            'quitar.*' => 'integer',
            'cancion_sel' => 'nullable|string|max:40',
            'cancion' => 'nullable|file|mimes:mp3,mpga,m4a,aac,wav,ogg,oga,webm|max:20480',
            'desde' => 'nullable|numeric|min:0|max:600',
        ], [
            'nombre.required' => 'Escriba el nombre del evento (ej. Boda Ana y Luis).',
            'logos.max' => 'Máximo ' . self::MAX_LOGOS . ' logos por evento.',
            'logos.*.image' => 'El logo debe ser una imagen.',
            'logos.*.mimes' => 'El logo debe ser PNG, JPG o WEBP.',
            'logos.*.max' => 'Un logo pesa demasiado (máximo 5 MB).',
            'cancion.mimes' => 'La canción debe ser MP3, M4A, WAV u OGG.',
            'cancion.max' => 'La canción pesa demasiado (máximo 20 MB).',
        ]);

        $cfg = $alquiler->e360 ?: [];
        $disco = Storage::disk('public');
        $carpeta = 'giro360/t' . $alquiler->tienda_id . '/eventos/' . $alquiler->id;

        // Logos: quitar los marcados y agregar los nuevos
        $logos = array_values($cfg['logos'] ?? []);
        foreach (array_map('intval', $d['quitar'] ?? []) as $i) {
            if (isset($logos[$i])) { $disco->delete($logos[$i]['archivo']); $logos[$i] = null; }
        }
        $logos = array_values(array_filter($logos));
        foreach ($request->file('logos', []) as $f) {
            if (count($logos) >= self::MAX_LOGOS) break;
            $nom = Str::limit(trim(preg_replace('/[-_]+/', ' ', pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME))) ?: 'Logo', 40, '');
            $logos[] = ['nombre' => strip_tags($nom), 'archivo' => $f->storeAs($carpeta, 'logo-' . Str::lower(Str::random(8)) . '.' . strtolower($f->getClientOriginalExtension() ?: 'png'), 'public')];
        }

        // Canción: una subida para el evento, una de la lista o ninguna
        $cancion = $cfg['cancion'] ?? null;
        if ($request->hasFile('cancion')) {
            if (!empty($cancion['archivo'])) $disco->delete($cancion['archivo']);
            $f = $request->file('cancion');
            $cancion = ['nombre' => strip_tags(Str::limit(pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME), 40, '')), 'archivo' => $f->storeAs($carpeta, 'cancion-' . Str::lower(Str::random(8)) . '.' . strtolower($f->getClientOriginalExtension() ?: 'mp3'), 'public')];
        } elseif (($sel = $d['cancion_sel'] ?? null) !== null && $sel !== 'propia') {
            if (!empty($cancion['archivo'])) $disco->delete($cancion['archivo']);
            $op = self::opcionesCancion()[$sel] ?? null;
            $cancion = $op ? ['nombre' => $op['nombre'], 'url' => $op['url']] : null;
        }

        $alquiler->forceFill([
            'e360_token' => $alquiler->e360_token ?: self::nuevoToken(),
            'e360' => ['nombre' => strip_tags($d['nombre']), 'logos' => $logos, 'cancion' => $cancion, 'desde' => (float) ($d['desde'] ?? 0)],
        ])->save();

        return redirect()->route('alquileres.e360', $alquiler)->with('success', 'Evento 360 guardado. Comparta el QR o el enlace con los invitados.');
    }

    // Página pública del evento: la app de invitados con el logo y la canción del evento ya puestos
    public function publico(string $token)
    {
        $a = Alquiler::withoutGlobalScope('tienda')->where('e360_token', $token)->firstOrFail();
        $cfg = $a->e360 ?: [];
        $evento = [
            'token' => $token,
            'nombre' => $cfg['nombre'] ?? $a->que_alquila,
            'negocio' => optional(\App\Models\Tienda::find($a->tienda_id))->nombre,
            'logos' => array_values(array_map(fn ($l, $i) => ['id' => 'ev' . $i, 'nombre' => $l['nombre'], 'url' => '/storage/' . $l['archivo']], $cfg['logos'] ?? [], array_keys($cfg['logos'] ?? []))),
            'cancion' => !empty($cfg['cancion']) ? ['id' => 'ev-cancion', 'nombre' => $cfg['cancion']['nombre'], 'url' => $cfg['cancion']['url'] ?? '/storage/' . $cfg['cancion']['archivo']] : null,
            'desde' => (float) ($cfg['desde'] ?? 0),
        ];
        return response()->view('giro360.index', ['evento360' => $evento])->header('X-Robots-Tag', 'noindex');
    }

    /** Alquiler del evento por su enlace (para guardar los invitados en el negocio) */
    public static function porToken(?string $token): ?Alquiler
    {
        if (!$token || !preg_match('/^[a-z0-9]{8,16}$/', $token)) return null;
        return Alquiler::withoutGlobalScope('tienda')->where('e360_token', $token)->first();
    }

    // Canciones para elegir: las de la app, las de LocalProo y las de la cuenta del negocio
    public static function opcionesCancion(): array
    {
        $o = [];
        foreach (self::CANCIONES_APP as $id => [$n, $u]) $o[$id] = ['nombre' => $n, 'url' => $u];
        foreach (giro360Controller::cancionesLista() as $c) $o['s' . $c['id']] = ['nombre' => $c['nombre'], 'url' => '/storage/' . $c['archivo']];
        return $o;
    }

    private static function nuevoToken(): string
    {
        do { $t = Str::lower(Str::random(10)); } while (Alquiler::withoutGlobalScope('tienda')->where('e360_token', $t)->exists());
        return $t;
    }
}
