<?php

namespace App\Http\Controllers;

use App\Models\Enlace;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Mis enlaces: la página tipo Linktree de cada negocio (localproo.com/@negocio).
 * Un solo enlace para poner en la biografía de Instagram, TikTok o WhatsApp con todo lo del negocio.
 */
class enlacesController extends Controller
{
    private function tienda(): Tienda
    {
        abort_unless(auth()->user()->can('editar-negocio'), 403);
        return TiendaActual::tienda();
    }

    private function mio(Enlace $enlace): Enlace
    {
        abort_unless((int) $enlace->tienda_id === (int) TiendaActual::id(), 404);
        return $enlace;
    }

    // ===== Panel del negocio =====

    public function index()
    {
        $tienda = $this->tienda();
        $enlaces = Enlace::orderBy('orden')->orderBy('id')->get();
        $clics = (int) $enlaces->sum('clics');
        return view('enlaces.index', [
            'tienda' => $tienda,
            'enlaces' => $enlaces,
            'redes' => (array) ($tienda->enlaces_redes ?? []),
            'url' => self::urlPublica($tienda),
            'visitas' => (int) $tienda->enlaces_visitas,
            'clics' => $clics,
        ]);
    }

    public function perfil(Request $request)
    {
        $tienda = $this->tienda();
        $request->validate([
            'enlaces_bio' => 'nullable|string|max:200',
            'enlaces_tema' => ['required', Rule::in(array_keys(Enlace::TEMAS))],
            'enlaces_boton' => ['required', Rule::in(array_keys(Enlace::BOTONES))],
            'redes' => 'nullable|array',
            'redes.*' => 'nullable|string|max:200',
            'foto' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $redes = [];
        $malas = [];
        foreach (Enlace::REDES as $k => [$nombre]) {
            $v = trim((string) $request->input("redes.$k"));
            if ($v === '') continue;
            if (Enlace::urlRed($k, $v)) $redes[$k] = $v;
            else $malas[] = $nombre;
        }

        $datos = [
            'enlaces_activo' => $request->boolean('enlaces_activo'),
            'enlaces_catalogo' => $request->boolean('enlaces_catalogo'),
            'enlaces_bio' => $request->input('enlaces_bio'),
            'enlaces_tema' => $request->input('enlaces_tema'),
            'enlaces_boton' => $request->input('enlaces_boton'),
            'enlaces_redes' => $redes ?: null,
        ];
        if ($request->hasFile('foto')) {
            if ($tienda->enlaces_foto_path) Storage::disk('public')->delete($tienda->enlaces_foto_path);
            $datos['enlaces_foto_path'] = $request->file('foto')->store('enlaces', 'public');
        }
        if ($request->boolean('quitar_foto') && $tienda->enlaces_foto_path) {
            Storage::disk('public')->delete($tienda->enlaces_foto_path);
            $datos['enlaces_foto_path'] = null;
        }
        $tienda->forceFill($datos)->save();

        $msg = 'Perfil guardado.';
        if ($malas) $msg .= ' No se guardó: ' . implode(', ', $malas) . ' (revise cómo lo escribió).';
        return back()->with($malas ? 'error' : 'success', $msg);
    }

    private function datosEnlace(Request $request): array
    {
        $request->validate([
            'tipo' => 'required|in:enlace,titulo',
            'titulo' => 'required|string|max:80',
            'url' => 'nullable|required_if:tipo,enlace|string|max:500',
            'icono' => ['nullable', Rule::in(array_keys(Enlace::ICONOS))],
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
        ], [
            'url.required_if' => 'Escriba la dirección del enlace.',
            'hasta.after_or_equal' => 'La fecha "hasta" debe ser después de "desde".',
        ]);

        $url = null;
        if ($request->input('tipo') === 'enlace') {
            $url = Enlace::normalizarUrl($request->input('url'));
            if (!$url) {
                throw \Illuminate\Validation\ValidationException::withMessages(['url' => 'Esa dirección no es válida. Ejemplo: https://minegocio.com, un número de WhatsApp o un correo.']);
            }
        }

        return [
            'tipo' => $request->input('tipo'),
            'titulo' => $request->input('titulo'),
            'url' => $url,
            'icono' => $request->input('icono') ?: null,
            'destacado' => $request->boolean('destacado'),
            'desde' => $request->input('desde') ?: null,
            'hasta' => $request->input('hasta') ?: null,
        ];
    }

    public function store(Request $request)
    {
        $this->tienda();
        abort_if(Enlace::count() >= 50, 422, 'Máximo 50 enlaces.');
        $datos = $this->datosEnlace($request);
        // Los nuevos van de primeros, como en Linktree
        Enlace::query()->increment('orden');
        Enlace::create($datos + ['orden' => 0, 'activo' => true]);
        return back()->with('success', $datos['tipo'] === 'titulo' ? 'Título agregado.' : 'Enlace agregado. Ya sale de primero en su página.');
    }

    public function update(Request $request, Enlace $enlace)
    {
        $this->tienda();
        $this->mio($enlace)->update($this->datosEnlace($request));
        return back()->with('success', 'Enlace actualizado.');
    }

    public function alternar(Enlace $enlace)
    {
        $this->tienda();
        $e = $this->mio($enlace);
        $e->update(['activo' => !$e->activo]);
        return back()->with('success', $e->activo ? 'Enlace visible.' : 'Enlace oculto.');
    }

    public function mover(Enlace $enlace, string $dir)
    {
        $this->tienda();
        $this->mio($enlace);
        DB::transaction(function () use ($enlace, $dir) {
            $lista = Enlace::orderBy('orden')->orderBy('id')->lockForUpdate()->get()->values();
            $i = $lista->search(fn ($e) => $e->id === $enlace->id);
            $j = $dir === 'arriba' ? $i - 1 : $i + 1;
            if ($i === false || $j < 0 || $j >= $lista->count()) return;
            $ids = $lista->pluck('id')->all();
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $orden => $id) Enlace::whereKey($id)->update(['orden' => $orden]);
        });
        return back();
    }

    public function destroy(Enlace $enlace)
    {
        $this->tienda();
        $this->mio($enlace)->delete();
        return back()->with('success', 'Enlace eliminado.');
    }

    public function reiniciar()
    {
        $tienda = $this->tienda();
        $tienda->forceFill(['enlaces_visitas' => 0])->save();
        Enlace::query()->update(['clics' => 0]);
        return back()->with('success', 'Estadísticas en cero.');
    }

    // ===== Página pública =====

    public static function urlPublica(Tienda $t): string
    {
        return url('/@' . $t->slug);
    }

    private function publica(string $slug): array
    {
        $tienda = Tienda::with('plan')->where('slug', $slug)->firstOrFail();
        $user = auth()->user();
        $esDueno = $user && ((int) $user->tienda_id === (int) $tienda->id || $user->es_superadmin);
        abort_unless($tienda->activa && ($tienda->enlaces_activo || $esDueno), 404);
        TiendaActual::set($tienda->id);
        return [$tienda, $esDueno];
    }

    public function show(Request $request, string $slug)
    {
        [$tienda, $esDueno] = $this->publica($slug);

        // Las visitas del dueño, de la vista previa y de robots no cuentan
        $robot = preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|curl|wget/i', (string) $request->userAgent());
        if (!$esDueno && !$robot && !$request->boolean('vista')) {
            Tienda::whereKey($tienda->id)->increment('enlaces_visitas');
        }

        $enlaces = Enlace::orderBy('orden')->orderBy('id')->get()->filter->visible->values();
        $redes = collect((array) ($tienda->enlaces_redes ?? []))
            ->map(fn ($v, $k) => isset(Enlace::REDES[$k]) ? ['url' => Enlace::urlRed($k, $v), 'icono' => Enlace::REDES[$k][1], 'nombre' => Enlace::REDES[$k][0]] : null)
            ->filter(fn ($r) => $r && $r['url'])->values();

        return response()->view('enlaces.publico', [
            'tienda' => $tienda,
            'enlaces' => $enlaces,
            'redes' => $redes,
            'tema' => Enlace::TEMAS[$tienda->enlaces_tema] ?? Enlace::TEMAS['localproo'],
            'boton' => $tienda->enlaces_boton ?: 'pildora',
            'foto' => $tienda->enlaces_foto_path ? Storage::disk('public')->url($tienda->enlaces_foto_path) : $tienda->logo_url,
            'catalogo' => $tienda->enlaces_catalogo && $tienda->catalogo_activo && $tienda->onboarding_completo ? $tienda->url_catalogo : null,
            'url' => self::urlPublica($tienda),
            'borrador' => !$tienda->enlaces_activo,
        ]);
    }

    // Cuenta el clic y lleva al destino (solo direcciones guardadas por el negocio)
    public function ir(Request $request, string $slug, int $enlace)
    {
        [$tienda, $esDueno] = $this->publica($slug);
        $e = Enlace::whereKey($enlace)->where('tipo', 'enlace')->firstOrFail();
        abort_unless($e->visible || $esDueno, 404);
        $robot = preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', (string) $request->userAgent());
        if (!$esDueno && !$robot) $e->increment('clics');
        $url = Enlace::normalizarUrl($e->url);
        abort_unless($url, 404);
        return redirect()->away($url);
    }
}
