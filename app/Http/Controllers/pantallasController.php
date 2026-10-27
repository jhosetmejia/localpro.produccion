<?php

namespace App\Http\Controllers;

use App\Models\Pantalla;
use App\Models\PantallaItem;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Pantallas del negocio (cartelera digital para cualquier negocio).
 * - Cada pantalla es un televisor: se abre una vez con un código (localproo.com/tv/1234) y se deja abierta.
 * - Al televisor se le suben imágenes y videos desde LocalProo; salen uno tras otro y se actualiza solo.
 * Sin cables ni USB: todo se maneja desde el computador o el celular.
 */
class pantallasController extends Controller
{
    public const MAX_PANTALLAS = 30;
    public const MAX_ITEMS = 60;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->routeIs('pantallas.ver', 'pantallas.version')) return $next($request);
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('editar-negocio')), 403);
            if (!TiendaActual::tienda()?->mod_pantallas && !$request->routeIs('pantallas.index', 'pantallas.activar')) {
                return redirect()->route('pantallas.index');
            }
            return $next($request);
        });
    }

    private function mio($modelo)
    {
        abort_unless($modelo && (int) $modelo->tienda_id === (int) TiendaActual::id(), 404);
        return $modelo;
    }

    private function carpeta(): string
    {
        return 'pantallas/' . TiendaActual::id();
    }

    // ===================== Administrar =====================

    public function index()
    {
        $tienda = TiendaActual::tienda();
        if (!$tienda->mod_pantallas) {
            return view('pantallas.activar', ['tienda' => $tienda]);
        }
        return view('pantallas.index', [
            'tienda' => $tienda,
            'pantallas' => Pantalla::withCount(['items', 'items as activos_count' => fn ($q) => $q->where('activo', true)])
                ->orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    public function activar()
    {
        TiendaActual::tienda()->forceFill(['mod_pantallas' => true])->save();
        if (!Pantalla::exists()) Pantalla::create(['nombre' => 'Pantalla 1', 'activa' => true, 'orden' => 1]);
        return redirect()->route('pantallas.index')->with('success', 'Pantallas activado. Abra la pantalla en su televisor y súbale imágenes o videos.');
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:60'], ['nombre.required' => 'Escriba el nombre de la pantalla.']);
        if (Pantalla::count() >= self::MAX_PANTALLAS) return back()->with('error', 'Máximo ' . self::MAX_PANTALLAS . ' pantallas.');
        $p = Pantalla::create(['nombre' => $request->input('nombre'), 'activa' => true, 'orden' => (int) Pantalla::max('orden') + 1]);
        return redirect()->route('pantallas.show', $p)->with('success', 'Pantalla creada. Súbale imágenes o videos y ábrala en el televisor.');
    }

    public function show(Pantalla $pantalla)
    {
        $this->mio($pantalla);
        return view('pantallas.show', ['tienda' => TiendaActual::tienda(), 'pantalla' => $pantalla, 'items' => $pantalla->items()->get()]);
    }

    public function update(Request $request, Pantalla $pantalla)
    {
        $this->mio($pantalla);
        $datos = $request->validate([
            'nombre' => 'required|string|max:60',
            'ajuste' => ['required', Rule::in(array_keys(Pantalla::AJUSTES))],
        ]);
        $pantalla->fill($datos + ['activa' => $request->boolean('activa')]);
        if ($request->boolean('nueva_direccion')) {
            $pantalla->token = Pantalla::nuevoToken();
            $pantalla->codigo = \App\Models\ObituarioSala::nuevoCodigo();
        }
        $pantalla->save();
        return back()->with('success', $request->boolean('nueva_direccion') ? 'La pantalla tiene un código nuevo. Ábralo otra vez en el televisor.' : 'Pantalla guardada.');
    }

    public function destroy(Pantalla $pantalla)
    {
        $this->mio($pantalla);
        foreach ($pantalla->items()->get() as $it) Storage::disk('public')->delete($it->path);
        $pantalla->delete();
        return redirect()->route('pantallas.index')->with('success', 'Pantalla eliminada.');
    }

    // Subir varias imágenes y videos de una vez
    public function itemStore(Request $request, Pantalla $pantalla)
    {
        $this->mio($pantalla);
        $request->validate([
            'archivos' => 'required|array|max:20',
            'archivos.*' => 'file|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime|max:51200',
            'segundos' => 'nullable|integer|min:3|max:600',
        ], [
            'archivos.required' => 'Elija al menos una imagen o un video.',
            'archivos.*.mimetypes' => 'Solo imágenes (JPG, PNG, WebP, GIF) o videos (MP4, WebM).',
            'archivos.*.max' => 'Cada archivo puede pesar máximo 50 MB. Use un video más corto o comprímalo.',
        ]);
        $hay = $pantalla->items()->count();
        $orden = (int) $pantalla->items()->max('orden');
        $subidos = 0;
        foreach ($request->file('archivos') as $f) {
            if ($hay + $subidos >= self::MAX_ITEMS) break;
            $tipo = str_starts_with((string) $f->getMimeType(), 'video/') ? 'video' : 'imagen';
            PantallaItem::create([
                'pantalla_id' => $pantalla->id,
                'tipo' => $tipo,
                'path' => $f->store($this->carpeta(), 'public'),
                'nombre' => mb_substr(pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME), 0, 120),
                'segundos' => (int) $request->input('segundos', 10),
                'activo' => true,
                'orden' => ++$orden,
            ]);
            $subidos++;
        }
        $pantalla->touch();
        $msg = $subidos === 1 ? 'Listo: 1 archivo agregado.' : "Listo: $subidos archivos agregados.";
        if ($subidos < count($request->file('archivos'))) $msg .= ' Algunos no se subieron: máximo ' . self::MAX_ITEMS . ' por pantalla.';
        return back()->with('success', $msg . ' En menos de un minuto salen en el televisor.');
    }

    public function itemUpdate(Request $request, PantallaItem $item)
    {
        $this->mio($item);
        $request->validate(['segundos' => 'required|integer|min:3|max:600']);
        $item->update(['segundos' => (int) $request->input('segundos'), 'activo' => $request->boolean('activo')]);
        $item->pantalla?->touch();
        return back()->with('success', 'Guardado.');
    }

    // Subir o bajar en el orden
    public function itemMover(Request $request, PantallaItem $item)
    {
        $this->mio($item);
        $lista = PantallaItem::where('pantalla_id', $item->pantalla_id)->orderBy('orden')->orderBy('id')->get()->values();
        $i = $lista->search(fn ($x) => $x->id === $item->id);
        $j = $request->input('a') === 'arriba' ? $i - 1 : $i + 1;
        if ($i !== false && isset($lista[$j])) {
            $lista[$i] = $lista[$j];
            $lista[$j] = $item;
            foreach ($lista as $n => $x) $x->forceFill(['orden' => $n + 1])->save();
            $item->pantalla?->touch();
        }
        return back();
    }

    public function itemDestroy(PantallaItem $item)
    {
        $this->mio($item);
        Storage::disk('public')->delete($item->path);
        $p = $item->pantalla;
        $item->delete();
        $p?->touch();
        return back()->with('success', 'Archivo quitado de la pantalla.');
    }

    // ===================== Televisor (sin iniciar sesión) =====================

    private function publica(string $token): Pantalla
    {
        $p = Pantalla::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        $tienda = Tienda::find($p->tienda_id);
        abort_unless($tienda && $tienda->activa && $tienda->mod_pantallas, 404);
        TiendaActual::set($tienda->id);
        return $p;
    }

    private function lista(Pantalla $p)
    {
        return $p->activa ? PantallaItem::where('pantalla_id', $p->id)->where('activo', true)->orderBy('orden')->orderBy('id')->get() : collect();
    }

    // Si cambia algo (archivos, orden, tiempos, ajuste), el televisor se recarga solo
    private static function huella(Pantalla $p, $items): string
    {
        return substr(md5($p->updated_at?->timestamp . '|' . $p->ajuste . '|' . $p->activa . '|' . $items->map(fn ($i) => $i->id . ':' . $i->updated_at?->timestamp . ':' . $i->orden)->implode(',')), 0, 16);
    }

    public function ver(string $token)
    {
        $p = $this->publica($token);
        $items = $this->lista($p);
        return response()->view('pantallas.ver', [
            'tienda' => TiendaActual::tienda(),
            'pantalla' => $p,
            'items' => $items->map(fn ($i) => ['tipo' => $i->tipo, 'url' => $i->url, 'seg' => max(3, (int) $i->segundos)])->values(),
            'version' => route('pantallas.version', $token),
            'huella' => self::huella($p, $items),
        ])->header('X-Robots-Tag', 'noindex');
    }

    public function version(string $token)
    {
        $p = $this->publica($token);
        return response()->json(['v' => self::huella($p, $this->lista($p))])->header('Cache-Control', 'no-store');
    }
}
