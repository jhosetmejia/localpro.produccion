<?php

namespace App\Http\Controllers;

use App\Models\Obituario;
use App\Models\ObituarioPlantilla;
use App\Models\ObituarioSala;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Obituarios para funerarias.
 * - Salas de velación: cada una tiene su pantalla (televisor) con una dirección fija.
 * - Plantillas: diseño, medida de pantalla (4K, Full HD…), fondo (color, imagen o video) y animación.
 *   Se arman una vez y se reutilizan: para cada fallecido solo se cambian los datos.
 * - Obituarios: nombre, foto, sala, exequias e invitados. Se ven en la pantalla de la sala y se descargan como imagen.
 */
class obituariosController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->routeIs('obituarios.pantalla', 'obituarios.version', 'obituarios.general', 'obituarios.general.version')) {
                return $next($request);
            }
            $u = $request->user();
            abort_unless($u && ($u->es_superadmin || $u->can('gestionar-obituarios') || $u->can('editar-negocio')), 403);
            // Si el negocio no tiene el módulo, solo ve la pantalla para activarlo
            if (!TiendaActual::tienda()?->mod_obituarios && !$request->routeIs('obituarios.index', 'obituarios.activar')) {
                return redirect()->route('obituarios.index');
            }
            return $next($request);
        });
    }

    private function tienda(): Tienda
    {
        return TiendaActual::tienda();
    }

    private function mio($modelo)
    {
        abort_unless($modelo && (int) $modelo->tienda_id === (int) TiendaActual::id(), 404);
        return $modelo;
    }

    private function carpeta(string $sub): string
    {
        return 'obituarios/' . TiendaActual::id() . '/' . $sub;
    }

    // ===================== Obituarios =====================

    public function index(Request $request)
    {
        $tienda = $this->tienda();
        if (!$tienda->mod_obituarios) {
            return view('obituarios.activar', ['tienda' => $tienda]);
        }
        ObituarioPlantilla::crearPredisenadas();
        if (!$tienda->obituarios_token) {
            $tienda->forceFill(['obituarios_token' => ObituarioSala::nuevoToken()])->save();
        }
        $ver = $request->query('ver', 'activos');
        $q = Obituario::with(['sala', 'plantilla'])->latest('id');
        if ($ver === 'activos') $q->where('estado', 'activo');
        elseif ($ver === 'finalizados') $q->where('estado', 'finalizado');
        if ($b = trim((string) $request->query('q'))) $q->where('nombre', 'like', '%' . $b . '%');

        $salas = ObituarioSala::orderBy('orden')->orderBy('id')->get();
        $enSala = Obituario::enPantalla()->whereNotNull('sala_id')->latest('id')->get()->unique('sala_id')->keyBy('sala_id');

        return view('obituarios.index', [
            'tienda' => $tienda,
            'obituarios' => $q->paginate(24)->withQueryString(),
            'salas' => $salas,
            'enSala' => $enSala,
            'ver' => $ver,
            'plantillas' => ObituarioPlantilla::count(),
        ]);
    }

    public function activar()
    {
        abort_unless(auth()->user()->can('editar-negocio') || auth()->user()->es_superadmin, 403);
        $this->tienda()->forceFill(['mod_obituarios' => true])->save();
        return redirect()->route('obituarios.index')->with('success', 'Obituarios activado. Cree sus salas y su primer obituario.');
    }

    public function create(Request $request)
    {
        ObituarioPlantilla::crearPredisenadas();
        $plantilla = $request->query('plantilla')
            ? $this->mio(ObituarioPlantilla::find($request->query('plantilla')))
            : (ObituarioPlantilla::find(Obituario::latest('id')->value('plantilla_id')) ?? ObituarioPlantilla::orderBy('id')->first() ?? ObituarioPlantilla::porDefecto());
        return $this->formulario(new Obituario(['estado' => 'activo']), $plantilla);
    }

    public function edit(Request $request, Obituario $obituario)
    {
        $this->mio($obituario);
        $plantilla = $request->query('plantilla')
            ? $this->mio(ObituarioPlantilla::find($request->query('plantilla')))
            : ($obituario->plantilla ?? ObituarioPlantilla::porDefecto());
        return $this->formulario($obituario, $plantilla);
    }

    private function formulario(Obituario $obituario, ObituarioPlantilla $plantilla)
    {
        return view('obituarios.form', [
            'tienda' => $this->tienda(),
            'obituario' => $obituario,
            'plantilla' => $plantilla,
            'plantillas' => ObituarioPlantilla::orderBy('nombre')->get(),
            'salas' => ObituarioSala::where('activa', true)->orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    private function datos(Request $request): array
    {
        $datos = $request->validate([
            'plantilla_id' => ['required', Rule::exists('obituario_plantillas', 'id')->where('tienda_id', TiendaActual::id())],
            'sala_id' => ['nullable', Rule::exists('obituario_salas', 'id')->where('tienda_id', TiendaActual::id())],
            'nombre' => 'required|string|max:120',
            'foto' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
            'nacimiento' => 'nullable|date',
            'fallecimiento' => 'nullable|date|after_or_equal:nacimiento',
            'frase' => 'nullable|string|max:150',
            'fecha_exequias' => 'nullable|date',
            'hora_exequias' => 'nullable|date_format:H:i',
            'lugar_exequias' => 'nullable|string|max:150',
            'destino_final' => 'nullable|string|max:150',
            'invitan' => 'nullable|string|max:3000',
            'mostrar_hasta' => 'nullable|date',
            'foto_fondo' => ['nullable', Rule::in(array_keys(Obituario::FOTO_FONDOS))],
            'foto_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'foto_forma' => ['nullable', Rule::in(array_keys(Obituario::FOTO_FORMAS))],
        ], [
            'nombre.required' => 'Escriba el nombre del fallecido.',
            'foto.max' => 'La foto pesa mucho (máximo 8 MB).',
            'fallecimiento.after_or_equal' => 'La fecha de fallecimiento no puede ser antes del nacimiento.',
        ]);
        unset($datos['foto']);
        $datos['foto_fondo'] = $datos['foto_fondo'] ?? 'suave';
        $datos['foto_forma'] = $datos['foto_forma'] ?? 'circulo';
        return $datos;
    }

    private function guardarFoto(Request $request, Obituario $o): void
    {
        if ($request->hasFile('foto')) {
            if ($o->foto_path) Storage::disk('public')->delete($o->foto_path);
            $o->foto_path = $request->file('foto')->store($this->carpeta('fotos'), 'public');
        } elseif ($request->boolean('quitar_foto') && $o->foto_path) {
            Storage::disk('public')->delete($o->foto_path);
            $o->foto_path = null;
        }
    }

    public function store(Request $request)
    {
        $o = new Obituario($this->datos($request) + ['estado' => 'activo']);
        $this->guardarFoto($request, $o);
        $o->save();
        return redirect()->route('obituarios.show', $o)->with('success', 'Obituario creado.' . ($o->sala_id ? ' Ya se ve en la pantalla de ' . $o->sala->nombre . '.' : ''));
    }

    public function update(Request $request, Obituario $obituario)
    {
        $this->mio($obituario)->fill($this->datos($request));
        $this->guardarFoto($request, $obituario);
        $obituario->save();
        return redirect()->route('obituarios.show', $obituario)->with('success', 'Obituario actualizado. Las pantallas se actualizan solas en menos de un minuto.');
    }

    public function show(Obituario $obituario)
    {
        $this->mio($obituario);
        return view('obituarios.show', [
            'tienda' => $this->tienda(),
            'obituario' => $obituario->load('sala', 'plantilla'),
            'plantilla' => $obituario->plantilla ?? ObituarioPlantilla::porDefecto(),
        ]);
    }

    public function estado(Obituario $obituario)
    {
        $this->mio($obituario);
        $obituario->estado = $obituario->estado === 'activo' ? 'finalizado' : 'activo';
        if ($obituario->estado === 'activo' && $obituario->mostrar_hasta?->isPast()) $obituario->mostrar_hasta = null;
        $obituario->save();
        return back()->with('success', $obituario->estado === 'activo' ? 'El obituario volvió a la pantalla.' : 'Obituario finalizado: ya no sale en la pantalla.');
    }

    public function destroy(Obituario $obituario)
    {
        $this->mio($obituario);
        if ($obituario->foto_path) Storage::disk('public')->delete($obituario->foto_path);
        $obituario->delete();
        return redirect()->route('obituarios.index')->with('success', 'Obituario eliminado.');
    }

    // ===================== Salas de velación =====================

    public function salaStore(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:60']);
        abort_if(ObituarioSala::count() >= 40, 422, 'Máximo 40 salas.');
        ObituarioSala::create(['nombre' => $request->input('nombre'), 'activa' => true, 'orden' => (int) ObituarioSala::max('orden') + 1]);
        return back()->with('success', 'Sala creada. Abra su pantalla en el televisor de la sala.');
    }

    public function salaUpdate(Request $request, ObituarioSala $sala)
    {
        $this->mio($sala);
        $request->validate(['nombre' => 'required|string|max:60']);
        $sala->update(['nombre' => $request->input('nombre'), 'activa' => $request->boolean('activa', true)]);
        if ($request->boolean('nueva_direccion')) {
            $sala->forceFill(['token' => ObituarioSala::nuevoToken()])->save();
            return back()->with('success', 'La sala tiene una dirección de pantalla nueva. Ábrala otra vez en el televisor.');
        }
        return back()->with('success', 'Sala actualizada.');
    }

    public function salaDestroy(ObituarioSala $sala)
    {
        $this->mio($sala)->delete();
        return back()->with('success', 'Sala eliminada. Los obituarios que tenía quedan sin sala.');
    }

    public function generalConfig(Request $request)
    {
        $request->validate(['obituarios_rotar' => 'required|integer|min:5|max:120']);
        $t = $this->tienda();
        $t->forceFill(['obituarios_rotar' => (int) $request->input('obituarios_rotar')]);
        if ($request->boolean('nueva_direccion')) $t->obituarios_token = ObituarioSala::nuevoToken();
        $t->save();
        return back()->with('success', 'Pantalla general guardada.');
    }

    // ===================== Plantillas =====================

    public function plantillas()
    {
        ObituarioPlantilla::crearPredisenadas();
        if (!ObituarioPlantilla::exists()) ObituarioPlantilla::porDefecto();
        return view('obituarios.plantillas', [
            'tienda' => $this->tienda(),
            'plantillas' => ObituarioPlantilla::withCount('obituarios')->orderBy('nombre')->get(),
        ]);
    }

    public function plantillaForm(?ObituarioPlantilla $plantilla = null)
    {
        if ($plantilla && $plantilla->exists) {
            $this->mio($plantilla);
        } else {
            $plantilla = new ObituarioPlantilla([
                'nombre' => 'Plantilla ' . (ObituarioPlantilla::count() + 1), 'diseno' => 'clasico', 'medida' => 'fhd', 'fondo_tipo' => 'diseno',
                'animacion' => 'ninguna', 'mostrar_logo' => true, 'titulo' => 'Obituario', 'frase' => ObituarioPlantilla::FRASE, 'oscurecer' => 30, 'adornos' => 'muchos',
            ]);
        }
        return view('obituarios.plantilla-form', [
            'tienda' => $this->tienda(),
            'plantilla' => $plantilla,
            'ejemplo' => Obituario::latest('id')->first(),
        ]);
    }

    public function plantillaGuardar(Request $request, ?ObituarioPlantilla $plantilla = null)
    {
        $nueva = !($plantilla && $plantilla->exists);
        if ($nueva) $plantilla = new ObituarioPlantilla();
        else $this->mio($plantilla);

        $color = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $datos = $request->validate([
            'nombre' => 'required|string|max:60',
            'diseno' => ['required', Rule::in(array_keys(ObituarioPlantilla::DISENOS))],
            'medida' => ['required', Rule::in(array_keys(ObituarioPlantilla::MEDIDAS))],
            'fondo_tipo' => ['required', Rule::in(array_keys(ObituarioPlantilla::FONDOS))],
            'fondo_color' => $color,
            'fondo_color2' => $color,
            'color_acento' => $color,
            'color_texto' => $color,
            'oscurecer' => 'nullable|integer|min:0|max:80',
            'animacion' => ['required', Rule::in(array_keys(ObituarioPlantilla::ANIMACIONES))],
            'adornos' => ['nullable', Rule::in(array_keys(ObituarioPlantilla::ADORNOS))],
            'titulo' => 'nullable|string|max:60',
            'frase' => 'nullable|string|max:120',
            'pie' => 'nullable|string|max:150',
            'cita' => 'nullable|string|max:160',
            'flor_pos' => ['nullable', Rule::in(array_keys(ObituarioPlantilla::FLOR_POS))],
            'flor_x' => 'nullable|integer|min:-400|max:400',
            'flor_y' => 'nullable|integer|min:-400|max:400',
            'flor_tam' => 'nullable|integer|min:40|max:200',
            'flor_esq' => ['nullable', Rule::in(array_keys(ObituarioPlantilla::FLOR_ESQ))],
            'anim_cant' => 'nullable|integer|min:10|max:500',
            'anim_tam' => 'nullable|integer|min:10|max:500',
            'anim_vel' => 'nullable|integer|min:10|max:500',
            'imagen' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
            'video' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
            'logo_obi' => 'nullable|image|mimes:png,webp,jpg,jpeg|max:4096',
        ], [
            'video.max' => 'El video pesa mucho (máximo 50 MB). Use un video corto que se repita.',
            'video.mimetypes' => 'El video debe ser MP4 o WebM.',
            'imagen.max' => 'La imagen pesa mucho (máximo 8 MB).',
        ]);
        unset($datos['imagen'], $datos['video'], $datos['logo_obi']);
        // Colores en blanco = los del diseño
        if (!$request->boolean('colores_propios')) { $datos['color_acento'] = null; $datos['color_texto'] = null; }
        $datos['mostrar_logo'] = $request->boolean('mostrar_logo');
        $datos['oscurecer'] = (int) ($datos['oscurecer'] ?? 0);
        $datos['titulo'] = $datos['titulo'] ?? '';
        $datos['adornos'] = $datos['adornos'] ?? 'muchos';
        $datos['flores'] = ['pos' => $datos['flor_pos'] ?? 'abajo', 'x' => (int) ($datos['flor_x'] ?? 0), 'y' => (int) ($datos['flor_y'] ?? 0), 'tam' => (int) ($datos['flor_tam'] ?? 100), 'esq' => $datos['flor_esq'] ?? 'dos'];
        unset($datos['flor_pos'], $datos['flor_x'], $datos['flor_y'], $datos['flor_tam'], $datos['flor_esq']);
        $datos['anim_op'] = ['cant' => (int) ($datos['anim_cant'] ?? 100), 'tam' => (int) ($datos['anim_tam'] ?? 100), 'vel' => (int) ($datos['anim_vel'] ?? 100)];
        unset($datos['anim_cant'], $datos['anim_tam'], $datos['anim_vel']);
        $plantilla->fill($datos);

        if ($request->hasFile('imagen')) {
            if ($plantilla->fondo_path) Storage::disk('public')->delete($plantilla->fondo_path);
            $plantilla->fondo_path = $request->file('imagen')->store($this->carpeta('fondos'), 'public');
        }
        // Logo de los obituarios (sirve para todas las plantillas del negocio)
        $tienda = $this->tienda();
        if ($request->hasFile('logo_obi')) {
            if ($tienda->obituarios_logo_path) Storage::disk('public')->delete($tienda->obituarios_logo_path);
            $tienda->forceFill(['obituarios_logo_path' => $request->file('logo_obi')->store($this->carpeta('logo'), 'public')])->save();
        } elseif ($request->boolean('quitar_logo_obi') && $tienda->obituarios_logo_path) {
            Storage::disk('public')->delete($tienda->obituarios_logo_path);
            $tienda->forceFill(['obituarios_logo_path' => null])->save();
        }
        if ($request->hasFile('video')) {
            if ($plantilla->video_path) Storage::disk('public')->delete($plantilla->video_path);
            $plantilla->video_path = $request->file('video')->store($this->carpeta('videos'), 'public');
        }
        if ($plantilla->fondo_tipo === 'imagen' && !$plantilla->fondo_path) {
            return back()->withInput()->withErrors(['imagen' => 'Suba la imagen de fondo o elija otro tipo de fondo.']);
        }
        if ($plantilla->fondo_tipo === 'video' && !$plantilla->video_path) {
            return back()->withInput()->withErrors(['video' => 'Suba el video de fondo o elija otro tipo de fondo.']);
        }
        $plantilla->save();

        if ($request->input('volver') === 'nuevo') {
            return redirect()->route('obituarios.create', ['plantilla' => $plantilla->id])->with('success', 'Plantilla guardada. Ahora ponga los datos del fallecido.');
        }
        return redirect()->route('obituarios.plantillas.edit', $plantilla)->with('success', $nueva ? 'Plantilla creada. Úsela cada vez que haga un obituario.' : 'Plantilla guardada.');
    }

    public function plantillaDuplicar(ObituarioPlantilla $plantilla)
    {
        $this->mio($plantilla);
        $copia = $plantilla->replicate();
        $copia->nombre = mb_substr($plantilla->nombre . ' (copia)', 0, 60);
        // La copia tiene sus propios archivos para que borrar una no afecte la otra
        foreach (['fondo_path', 'video_path'] as $c) {
            if ($plantilla->$c && Storage::disk('public')->exists($plantilla->$c)) {
                $nuevo = dirname($plantilla->$c) . '/' . \Illuminate\Support\Str::random(20) . '.' . pathinfo($plantilla->$c, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($plantilla->$c, $nuevo);
                $copia->$c = $nuevo;
            }
        }
        $copia->save();
        return redirect()->route('obituarios.plantillas.edit', $copia)->with('success', 'Plantilla copiada. Cámbiele lo que quiera.');
    }

    public function plantillaBorrar(ObituarioPlantilla $plantilla)
    {
        $this->mio($plantilla);
        if (ObituarioPlantilla::count() <= 1) {
            return back()->with('error', 'Debe quedar al menos una plantilla.');
        }
        foreach (['fondo_path', 'video_path'] as $c) {
            if ($plantilla->$c) Storage::disk('public')->delete($plantilla->$c);
        }
        $plantilla->delete();
        return redirect()->route('obituarios.plantillas')->with('success', 'Plantilla eliminada. Los obituarios que la usaban salen con la plantilla Clásica.');
    }

    // ===================== Pantallas (televisores) =====================

    private function salaPublica(string $token): ObituarioSala
    {
        $sala = ObituarioSala::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        $tienda = Tienda::find($sala->tienda_id);
        abort_unless($tienda && $tienda->activa && $tienda->mod_obituarios, 404);
        TiendaActual::set($tienda->id);
        return $sala;
    }

    private function tiendaGeneral(string $token): Tienda
    {
        $tienda = Tienda::where('obituarios_token', $token)->firstOrFail();
        abort_unless($tienda->activa && $tienda->mod_obituarios, 404);
        TiendaActual::set($tienda->id);
        return $tienda;
    }

    // Huella de lo que se ve: si cambia, la pantalla se recarga sola
    private static function huella($obituarios, $extra = ''): string
    {
        $partes = [$extra];
        foreach ($obituarios as $o) {
            $partes[] = $o->id . ':' . $o->updated_at?->timestamp . ':' . $o->plantilla?->updated_at?->timestamp . ':' . $o->sala?->updated_at?->timestamp;
        }
        return substr(md5(implode('|', $partes)), 0, 16);
    }

    // Nombre y logo del negocio (no la fecha de la tienda: cambia con cada venta)
    private static function marca(Tienda $t): string
    {
        return md5($t->nombre . '|' . $t->logo_path . '|' . $t->obituarios_rotar);
    }

    private function obituariosSala(ObituarioSala $sala)
    {
        return Obituario::with('plantilla', 'sala')->where('sala_id', $sala->id)->enPantalla()->latest('id')->get();
    }

    public function pantalla(string $token)
    {
        $sala = $this->salaPublica($token);
        $tienda = TiendaActual::tienda();
        $obituarios = $sala->activa ? $this->obituariosSala($sala) : collect();
        return response()->view('obituarios.pantalla', [
            'tienda' => $tienda,
            'titulo' => $sala->nombre,
            'obituarios' => $obituarios,
            'rotar' => $tienda->obituarios_rotar ?: 12,
            'version' => route('obituarios.version', $token),
            'huella' => self::huella($obituarios, $sala->nombre . $sala->activa . self::marca($tienda)),
            'plantillaDefecto' => ObituarioPlantilla::orderBy('id')->first() ?? ObituarioPlantilla::porDefecto(),
        ])->header('X-Robots-Tag', 'noindex');
    }

    public function version(string $token)
    {
        $sala = $this->salaPublica($token);
        $obituarios = $sala->activa ? $this->obituariosSala($sala) : collect();
        return response()->json(['v' => self::huella($obituarios, $sala->nombre . $sala->activa . self::marca(TiendaActual::tienda()))])->header('Cache-Control', 'no-store');
    }

    private function obituariosGenerales()
    {
        return Obituario::with('plantilla', 'sala')->enPantalla()->orderByRaw('sala_id is null')->orderBy('sala_id')->latest('id')->get();
    }

    public function pantallaGeneral(string $token)
    {
        $tienda = $this->tiendaGeneral($token);
        $obituarios = $this->obituariosGenerales();
        return response()->view('obituarios.pantalla', [
            'tienda' => $tienda,
            'titulo' => 'Obituarios',
            'obituarios' => $obituarios,
            'rotar' => $tienda->obituarios_rotar ?: 12,
            'version' => route('obituarios.general.version', $token),
            'huella' => self::huella($obituarios, $tienda->obituarios_rotar . self::marca($tienda)),
            'plantillaDefecto' => ObituarioPlantilla::orderBy('id')->first() ?? ObituarioPlantilla::porDefecto(),
        ])->header('X-Robots-Tag', 'noindex');
    }

    public function versionGeneral(string $token)
    {
        $tienda = $this->tiendaGeneral($token);
        return response()->json(['v' => self::huella($this->obituariosGenerales(), $tienda->obituarios_rotar . self::marca($tienda))])->header('Cache-Control', 'no-store');
    }
}
