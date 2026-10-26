<?php

namespace App\Http\Controllers;

use App\Mail\EventoEntradaMail;
use App\Models\Evento;
use App\Models\EventoEntrada;
use App\Models\Tienda;
use App\Support\Imagenes;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * LocalProo Eventos (panel del negocio).
 * - Eventos: página pública (landing) con afiche, fecha, lugar, precio de la etapa y datos de pago (Nequi).
 * - Compradores: se registran en la página; el negocio ve quién pagó (mandó el comprobante por WhatsApp)
 *   y aprueba el pago a mano. Al aprobar se genera la entrada con QR único.
 * - Puerta: escanea el QR (o busca por documento) y deja entrar una sola vez.
 */
class eventosController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $p = Evento::puede($request->user());
            $ok = match (true) {
                $request->routeIs('eventos.puerta', 'eventos.validar') => $p['admin'] || $p['puerta'],
                $request->routeIs('eventos.index') => in_array(true, $p, true),
                $request->routeIs('eventos.edit', 'eventos.update') => $p['admin'] || $p['info'] || $p['diseno'],
                $request->routeIs('eventos.show', 'eventos.exportar') => $p['admin'] || $p['pagos'] || $p['info'],
                $request->routeIs('eventos.aprobar', 'eventos.rechazar', 'eventos.pendiente', 'eventos.clave') => $p['admin'] || $p['pagos'],
                default => $p['admin'], // activar, crear, guardar nuevo y borrar
            };
            abort_unless($ok, 403);
            if (!TiendaActual::tienda()?->mod_eventos && !$request->routeIs('eventos.index', 'eventos.activar')) {
                return redirect()->route('eventos.index');
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

    private function esAdmin(): bool
    {
        return Evento::puede()['admin'];
    }

    // ===================== Eventos =====================

    public function index()
    {
        $tienda = $this->tienda();
        if (!$tienda->mod_eventos) {
            return view('eventos.activar', ['tienda' => $tienda, 'puedeActivar' => $this->esAdmin()]);
        }
        $eventos = Evento::withCount([
            'entradas as pendientes' => fn ($q) => $q->where('estado', 'pendiente'),
            'entradas as aprobadas' => fn ($q) => $q->where('estado', 'aprobada'),
            'entradas as ingresaron' => fn ($q) => $q->where('estado', 'aprobada')->whereNotNull('ingreso_at'),
        ])->orderByRaw("estado = 'cerrado'")->orderByDesc('fecha_evento')->orderByDesc('id')->get();

        return view('eventos.index', ['tienda' => $tienda, 'eventos' => $eventos, 'admin' => $this->esAdmin(), 'p' => Evento::puede()]);
    }

    public function activar()
    {
        abort_unless($this->esAdmin(), 403);
        $this->tienda()->forceFill(['mod_eventos' => true])->save();
        \App\Support\RolesNegocio::crearEventosPorDefecto($this->tienda());
        return redirect()->route('eventos.index')->with('success', 'Listo: ya puede crear su primer evento.');
    }

    public function create()
    {
        $tienda = $this->tienda();
        $evento = new Evento([
            'estado' => 'borrador', 'etapa' => 'Primera etapa', 'pago_metodo' => 'Nequi', 'pedir_colegio' => false, 'tema' => 'normal', 'color' => '#a855f7',
            'pago_numero' => $tienda->telefono, 'whatsapp' => $tienda->telefono, 'mensaje_whatsapp' => Evento::MENSAJE,
            'ciudad' => $tienda->ciudad ?? null,
            'condiciones' => "Reservamos el derecho de admisión y permanencia.\nLa entrada es personal e intransferible: presente su documento en la puerta.\nNo hay devolución del dinero una vez aprobado el pago.",
        ]);
        return view('eventos.form', ['tienda' => $tienda, 'evento' => $evento, 'p' => Evento::puede()]);
    }

    public function store(Request $request)
    {
        $evento = new Evento();
        $this->guardar($request, $evento);
        return redirect()->route('eventos.show', $evento)->with('success', $evento->estado === 'publicado'
            ? 'Evento creado y publicado. Comparta el enlace de la página.'
            : 'Evento creado como borrador. Cuando esté listo, cámbielo a "Publicado".');
    }

    public function edit(Evento $evento)
    {
        $this->mio($evento);
        return view('eventos.form', ['tienda' => $this->tienda(), 'evento' => $evento, 'p' => Evento::puede()]);
    }

    public function update(Request $request, Evento $evento)
    {
        $this->mio($evento);
        $this->guardar($request, $evento);
        return redirect()->route('eventos.show', $evento)->with('success', 'Evento guardado.');
    }

    private function guardar(Request $request, Evento $evento): void
    {
        $reglas = [
            'nombre' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('eventos', 'slug')->ignore($evento->id)],
            'subtitulo' => 'nullable|string|max:120',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_evento' => 'required|date',
            'lugar' => 'nullable|string|max:150',
            'ciudad' => 'nullable|string|max:80',
            // Solo enlaces de Google Maps (los que da el botón Compartir de Maps)
            'mapa_url' => ['nullable', 'url', 'max:500', 'regex:#^https://(maps\.app\.goo\.gl/|goo\.gl/maps/|(www\.)?google\.[a-z.]+/maps|maps\.google\.[a-z.]+/)#i'],
            'etapa' => 'nullable|string|max:60',
            'precio' => 'required|numeric|min:0|max:99999999',
            'venta_hasta' => 'nullable|date',
            'cupo' => 'nullable|integer|min:1|max:1000000',
            'pago_metodo' => 'required|string|max:40',
            'pago_numero' => 'required|string|max:40',
            'pago_titular' => 'nullable|string|max:80',
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+ ()-]{7,30}$/'],
            'mensaje_whatsapp' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:80',
            'tiktok' => 'nullable|string|max:80',
            'edad_minima' => 'nullable|integer|min:1|max:99',
            'condiciones' => 'nullable|string|max:3000',
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'estado' => ['required', Rule::in(array_keys(Evento::ESTADOS))],
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'imagen2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'tema' => ['nullable', Rule::in(array_keys(Evento::TEMAS))],
        ];
        // Cada rol solo cambia su parte: Logística los datos, Diseño la imagen; precio, pago y publicación solo el administrador
        $p = Evento::puede();
        if (!$p['admin']) {
            $campos = array_merge(
                $p['info'] ? ['nombre', 'subtitulo', 'descripcion', 'fecha_evento', 'lugar', 'ciudad', 'mapa_url', 'condiciones', 'instagram', 'tiktok'] : [],
                $p['diseno'] ? ['imagen', 'imagen2', 'logo', 'tema', 'color'] : [],
            );
            abort_if(!$campos || !$evento->exists, 403);
            $reglas = array_intersect_key($reglas, array_flip($campos));
        }
        $datos = $request->validate($reglas, [
            'slug.regex' => 'La dirección solo puede tener letras minúsculas, números y guiones (ej. el-ultimo-grito).',
            'slug.unique' => 'Esa dirección ya la tiene otro evento. Pruebe con otra.',
            'whatsapp.regex' => 'Escriba el número de WhatsApp solo con números (ej. 3218981691).',
            'mapa_url.url' => 'Pegue el enlace completo de Google Maps (empieza por https://).',
            'mapa_url.regex' => 'Ese enlace no es de Google Maps. En Google Maps busque el lugar, toque Compartir y copie el enlace.',
        ]);

        if ($p['admin']) {
            $datos['mostrar_visitas'] = $request->boolean('mostrar_visitas');
            $datos['pedir_nacimiento'] = $request->boolean('pedir_nacimiento');
            $datos['pedir_celular'] = $request->boolean('pedir_celular');
            $datos['campos'] = $this->camposDelFormulario((array) $request->input('campos', []));
            $datos['max_por_compra'] = max(1, min(20, (int) $request->input('max_por_compra', 1)));
            $datos['promos'] = $this->promosDelFormulario((array) $request->input('promos', []));
        }
        if ($p['admin'] || $p['diseno']) {
            $datos['color'] = $datos['color'] ?? '#a855f7';
            $datos['tema'] = $datos['tema'] ?? 'normal';
        }
        foreach (['instagram', 'tiktok'] as $r) {
            if (!array_key_exists($r, $datos)) continue;
            // Enlace corto de TikTok (vm.tiktok.com/…): se guarda completo; lo demás queda solo el usuario
            if ($r === 'tiktok' && preg_match('~^(https?://)?(vm|vt)\.tiktok\.com/~i', (string) ($datos[$r] ?? ''))) {
                $datos[$r] = preg_match('~^https?://~i', $datos[$r]) ? $datos[$r] : 'https://' . $datos[$r];
                continue;
            }
            $datos[$r] = Evento::usuarioRed($datos[$r] ?? null);
        }
        if ($p['admin']) {
            $datos['whatsapp'] = preg_replace('/\D/', '', $datos['whatsapp']);
            $datos['slug'] = $datos['slug'] ?: ($evento->slug ?: Evento::slugUnico($datos['nombre'], $evento->id));
        }
        unset($datos['imagen'], $datos['imagen2'], $datos['logo']);

        $evento->fill($datos);
        $evento->save();

        foreach (['imagen' => 'imagen_path', 'imagen2' => 'imagen2_path', 'logo' => 'logo_path'] as $campo => $col) {
            if (!$p['admin'] && !$p['diseno']) break;
            if ($request->hasFile($campo)) {
                if ($evento->$col) Storage::disk('public')->delete($evento->$col);
                $ruta = $request->file($campo)->store('eventos/' . TiendaActual::id(), 'public');
                // El logo puede venir en PNG con fondo transparente: no se convierte
                if ($campo !== 'logo') { try { Imagenes::optimizar(Storage::disk('public')->path($ruta), 1600); } catch (\Throwable $e) {} }
                $evento->forceFill([$col => $ruta])->save();
            } elseif ($request->boolean('quitar_' . $campo) && $evento->$col) {
                Storage::disk('public')->delete($evento->$col);
                $evento->forceFill([$col => null])->save();
            }
        }
    }

    // Promociones: "Compra X y la siguiente con Y % de descuento" (máx. 5)
    private function promosDelFormulario(array $entrada): array
    {
        $out = [];
        foreach (array_slice(array_values($entrada), 0, 5) as $p) {
            if (!is_array($p)) continue;
            $compra = (int) ($p['compra'] ?? 0);
            $desc = (int) ($p['descuento'] ?? 0);
            if ($compra < 1 || $compra > 19 || $desc < 1 || $desc > 100) continue;
            $hasta = !empty($p['hasta']) && strtotime((string) $p['hasta']) ? date('Y-m-d', strtotime((string) $p['hasta'])) : null;
            $out[] = ['compra' => $compra, 'descuento' => $desc, 'hasta' => $hasta, 'activa' => (bool) (int) ($p['activa'] ?? 1)];
        }
        return $out;
    }

    // Campos propios del formulario de registro que armó el organizador (máx. 15)
    private function camposDelFormulario(array $entrada): array
    {
        $out = [];
        $usados = [];
        foreach (array_slice(array_values($entrada), 0, 15) as $c) {
            if (!is_array($c)) continue;
            $etq = trim(mb_substr(strip_tags((string) ($c['etiqueta'] ?? '')), 0, 80));
            if ($etq === '') continue;
            $tipo = isset(Evento::TIPOS_CAMPO[$c['tipo'] ?? '']) ? $c['tipo'] : 'texto';
            $ops = $tipo === 'lista'
                ? array_values(array_unique(array_filter(array_map(fn ($o) => trim(mb_substr(strip_tags($o), 0, 60)), explode(',', (string) ($c['opciones'] ?? ''))), 'strlen')))
                : [];
            if ($tipo === 'lista' && !$ops) $tipo = 'texto';
            // El id se conserva para no perder las respuestas ya guardadas
            $id = preg_match('/^[a-z0-9_]{1,40}$/', (string) ($c['id'] ?? '')) ? $c['id'] : (\Illuminate\Support\Str::slug($etq, '_') ?: 'campo');
            $id = mb_substr($id, 0, 34);
            $base = $id; $i = 2;
            while (in_array($id, $usados, true)) $id = $base . '_' . $i++;
            $usados[] = $id;
            $out[] = ['id' => $id, 'etiqueta' => $etq, 'tipo' => $tipo, 'opciones' => $ops, 'obligatorio' => (bool) ($c['obligatorio'] ?? false)];
        }
        return $out;
    }

    public function show(Request $request, Evento $evento)
    {
        $this->mio($evento);
        $ver = $request->query('ver', 'pendiente');
        $q = EventoEntrada::with(['asistente', 'aprobador:id,name', 'portero:id,name'])->where('evento_id', $evento->id);
        match ($ver) {
            'aprobada' => $q->where('estado', 'aprobada'),
            'ingreso' => $q->where('estado', 'aprobada')->whereNotNull('ingreso_at'),
            'rechazada' => $q->whereIn('estado', ['rechazada', 'anulada']),
            'todas' => null,
            default => $q->where('estado', 'pendiente'),
        };
        if ($b = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($b) {
                $w->where('codigo', 'like', '%' . $b . '%')->orWhere('titular_nombre', 'like', '%' . $b . '%')->orWhere('titular_documento', 'like', '%' . $b . '%')->orWhereHas('asistente', fn ($a) => $a->where('nombre', 'like', '%' . $b . '%')
                    ->orWhere('documento', 'like', '%' . $b . '%')->orWhere('email', 'like', '%' . $b . '%')->orWhere('celular', 'like', '%' . $b . '%'));
            });
        }
        // Primero los que ya avisaron que mandaron el comprobante
        $entradas = $q->orderByRaw('aviso_pago_at IS NULL')->orderByDesc('aviso_pago_at')->orderByDesc('id')->paginate(40)->withQueryString();

        // Compras de varias entradas: cuántas y cuánto suman (para mostrarlo en la fila del comprador)
        $grupos = EventoEntrada::where('evento_id', $evento->id)->whereIn('grupo', $entradas->pluck('grupo')->filter()->unique())
            ->selectRaw("grupo, COUNT(*) n, SUM(CASE WHEN estado <> 'anulada' THEN precio ELSE 0 END) total")->groupBy('grupo')->get()->keyBy('grupo');

        $base = EventoEntrada::where('evento_id', $evento->id);
        $stats = [
            'pendiente' => (clone $base)->where('estado', 'pendiente')->count(),
            'aviso' => (clone $base)->where('estado', 'pendiente')->whereNotNull('aviso_pago_at')->count(),
            'aprobada' => (clone $base)->where('estado', 'aprobada')->count(),
            'ingreso' => (clone $base)->where('estado', 'aprobada')->whereNotNull('ingreso_at')->count(),
            'rechazada' => (clone $base)->whereIn('estado', ['rechazada', 'anulada'])->count(),
            'recaudo' => (float) (clone $base)->where('estado', 'aprobada')->sum('precio'),
        ];

        return view('eventos.show', ['tienda' => $this->tienda(), 'evento' => $evento, 'entradas' => $entradas, 'stats' => $stats, 'ver' => $ver, 'p' => Evento::puede(), 'grupos' => $grupos]);
    }

    public function destroy(Evento $evento)
    {
        $this->mio($evento);
        if ($evento->entradas()->where('estado', 'aprobada')->exists()) {
            return back()->with('error', 'Este evento ya tiene entradas aprobadas: no se puede borrar. Puede cerrar la venta.');
        }
        foreach (['imagen_path', 'imagen2_path'] as $c) if ($evento->$c) Storage::disk('public')->delete($evento->$c);
        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento borrado.');
    }

    // ===================== Pagos y entradas =====================

    private function entrada(Evento $evento, EventoEntrada $entrada): EventoEntrada
    {
        $this->mio($evento);
        $this->mio($entrada);
        abort_unless((int) $entrada->evento_id === (int) $evento->id, 404);
        return $entrada;
    }

    public function aprobar(Request $request, Evento $evento, EventoEntrada $entrada)
    {
        $this->entrada($evento, $entrada);
        $datos = $request->validate(['referencia_pago' => 'nullable|string|max:80']);
        if (!in_array($entrada->estado, ['pendiente', 'rechazada'], true)) {
            return back()->with('error', 'Esta entrada ya estaba aprobada o anulada.');
        }
        // Una compra de varias entradas se paga con un solo comprobante: se aprueban todas juntas
        $n = $entrada->delGrupo()->whereIn('estado', ['pendiente', 'rechazada'])->update([
            'estado' => 'aprobada', 'aprobada_at' => now(), 'aprobada_por' => auth()->id(),
            'referencia_pago' => $datos['referencia_pago'] ?? $entrada->referencia_pago, 'updated_at' => now(),
        ]);
        $entrada->refresh();

        $a = $entrada->asistente;
        $correo = false;
        try {
            Mail::to($a->email)->send(new EventoEntradaMail($entrada->fresh(['evento', 'asistente'])));
            $correo = true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de la entrada', ['entrada' => $entrada->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Pago aprobado: ' . $a->nombre . ($n > 1 ? " y sus acompañantes ya tienen sus {$n} entradas con QR." : ' ya tiene su entrada con QR.') . ($correo ? ' Le enviamos un correo.' : ''))
            ->with('avisar', $entrada->id);
    }

    public function rechazar(Request $request, Evento $evento, EventoEntrada $entrada)
    {
        $this->entrada($evento, $entrada);
        $datos = $request->validate(['nota' => 'nullable|string|max:255']);
        if ($entrada->estado === 'aprobada' && $entrada->ingreso_at) {
            return back()->with('error', 'Esta persona ya ingresó al evento.');
        }
        if ($entrada->estado === 'pendiente') {
            // El pago de una compra grupal es uno solo: se rechazan todas las entradas pendientes de esa compra
            $entrada->delGrupo()->where('estado', 'pendiente')->update(['estado' => 'rechazada', 'nota' => $datos['nota'] ?? $entrada->nota, 'updated_at' => now()]);
            $entrada->refresh();
        } else {
            $entrada->forceFill(['estado' => $entrada->estado === 'aprobada' ? 'anulada' : 'rechazada', 'nota' => $datos['nota'] ?? $entrada->nota])->save();
        }
        return back()->with('success', $entrada->estado === 'anulada' ? 'Entrada anulada: el QR ya no sirve.' : 'Pago rechazado.');
    }

    public function pendiente(Evento $evento, EventoEntrada $entrada)
    {
        $this->entrada($evento, $entrada);
        if ($entrada->ingreso_at) return back()->with('error', 'Esta persona ya ingresó al evento.');
        $entrada->forceFill(['estado' => 'pendiente', 'aprobada_at' => null, 'aprobada_por' => null])->save();
        return back()->with('success', 'La entrada volvió a "Por aprobar".');
    }

    // Olvidó la clave: queda con su número de documento
    public function clave(Evento $evento, EventoEntrada $entrada)
    {
        $this->entrada($evento, $entrada);
        $a = $entrada->asistente;
        $a->forceFill(['password' => $a->documento])->save();
        return back()->with('success', 'Clave restablecida: ' . $a->nombre . ' ahora entra con su correo y su número de documento como clave.');
    }

    public function exportar(Evento $evento)
    {
        $this->mio($evento);
        $nombre = 'entradas-' . $evento->slug . '-' . now()->format('Y-m-d') . '.csv';
        $filas = EventoEntrada::with('asistente')->where('evento_id', $evento->id)->orderBy('id')->get();
        $campos = $evento->camposFormulario();
        return response()->streamDownload(function () use ($filas, $campos) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Titular de la entrada', 'Documento del titular', 'Comprador', 'Grupo', 'Promoción', 'Tipo doc.', 'Documento', 'Fecha de nacimiento', 'Edad', 'Correo', 'Celular', 'Estado', 'Precio', 'Avisó pago', 'Aprobada', 'Referencia', 'Ingresó', 'Código', ...array_column($campos, 'etiqueta')], ';');
            foreach ($filas as $e) {
                $a = $e->asistente;
                fputcsv($out, [$e->nombre_titular, $e->documento_titular, $a->nombre, $e->grupo ? substr($e->grupo, 0, 8) : '', $e->promo, $a->tipo_documento, $a->documento, $a->fecha_nacimiento?->format('d/m/Y'), $a->edad, $a->email, $a->celular,
                    $e->estado_texto, (float) $e->precio, $e->aviso_pago_at?->format('d/m/Y H:i'), $e->aprobada_at?->format('d/m/Y H:i'), $e->referencia_pago,
                    $e->ingreso_at?->format('d/m/Y H:i'), $e->estado === 'aprobada' ? $e->codigo : '',
                    ...array_map(fn ($c) => (string) (($e->respuestas ?? [])[$c['id']] ?? ''), $campos)], ';');
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ===================== Puerta (validar QR) =====================

    public function puerta(Evento $evento)
    {
        $this->mio($evento);
        return view('eventos.puerta', ['tienda' => $this->tienda(), 'evento' => $evento, 'stats' => $this->conteoPuerta($evento)]);
    }

    private function conteoPuerta(Evento $evento): array
    {
        $b = EventoEntrada::where('evento_id', $evento->id)->where('estado', 'aprobada');
        return ['aprobadas' => (clone $b)->count(), 'ingresaron' => (clone $b)->whereNotNull('ingreso_at')->count()];
    }

    public function validar(Request $request, Evento $evento)
    {
        $this->mio($evento);
        $codigo = strtoupper(trim((string) $request->validate(['codigo' => 'required|string|max:200'])['codigo']));
        // El QR puede traer el enlace completo: se toma el código del final
        if (preg_match('/LPE-[A-Z0-9]{10}/', $codigo, $m)) $codigo = $m[0];
        $soloRevisar = $request->boolean('revisar');

        $q = EventoEntrada::with('asistente')->where('evento_id', $evento->id);
        $entrada = str_starts_with($codigo, 'LPE-')
            ? (clone $q)->where('codigo', $codigo)->first()
            : (clone $q)->where(function ($w) use ($codigo) {
                $doc = preg_replace('/[^0-9A-Z]/', '', $codigo);
                $w->where('titular_documento', $doc)->orWhere(fn ($x) => $x->whereNull('titular_documento')->whereHas('asistente', fn ($a) => $a->where('documento', $doc)));
            })->orderByRaw("estado = 'aprobada' DESC")->first();

        $resp = fn (string $estado, string $msg, ?EventoEntrada $e = null, array $extra = []) => response()->json(array_merge([
            'estado' => $estado, 'mensaje' => $msg,
            'persona' => $e ? [
                'nombre' => $e->nombre_titular, 'documento' => $e->documento_titular,
                'edad' => $e->es_acompanante ? null : $e->asistente->edad, 'colegio' => $e->asistente->colegio, 'codigo' => $e->codigo,
            ] : null,
            'conteo' => $this->conteoPuerta($evento),
        ], $extra));

        if (!$entrada) return $resp('no_existe', 'No hay ninguna entrada con ese código o documento para este evento.');
        if ($entrada->estado === 'pendiente') return $resp('pendiente', 'El pago de esta persona todavía no está aprobado.', $entrada);
        if ($entrada->estado !== 'aprobada') return $resp('anulada', 'Esta entrada está ' . mb_strtolower($entrada->estado_texto) . ': no sirve para entrar.', $entrada);
        if ($entrada->ingreso_at) {
            return $resp('usada', 'Esta entrada YA SE USÓ el ' . $entrada->ingreso_at->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') . '.', $entrada,
                ['portero' => $entrada->portero?->name]);
        }
        if ($soloRevisar) return $resp('valida', 'Entrada válida. Revise el documento y toque "Dejar entrar".', $entrada);

        // Se marca en una sola operación: si dos porteros escanean a la vez, solo uno la deja entrar
        $ok = DB::table('evento_entradas')->where('id', $entrada->id)->where('estado', 'aprobada')->whereNull('ingreso_at')
            ->update(['ingreso_at' => now(), 'ingreso_por' => auth()->id(), 'updated_at' => now()]);
        if (!$ok) return $resp('usada', 'Esta entrada YA SE USÓ hace un momento.', $entrada->fresh('asistente'));

        return $resp('ok', '¡Bienvenido! Puede entrar.', $entrada);
    }
}
