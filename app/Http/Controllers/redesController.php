<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Producto;
use App\Models\RedConversacion;
use App\Models\RedCuenta;
use App\Models\RedMensaje;
use App\Models\RedPublicacion;
use App\Support\Meta;
use App\Support\Publicador;
use App\Support\YouTube;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

// "Mis redes": conectar Facebook e Instagram, responder los chats y publicar
class redesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:responder-redes|publicar-redes|editar-negocio');
        $this->middleware('permission:editar-negocio', ['only' => ['conectar', 'callback', 'elegir', 'guardarPaginas', 'desconectar', 'conectarCorreo', 'youtubeConectar', 'youtubeRetorno']]);
        $this->middleware('permission:publicar-redes|editar-negocio', ['only' => ['publicar', 'guardarPublicacion', 'productos', 'reintentar', 'borrarPublicacion', 'subirVideo']]);
    }

    // Cuentas con bandeja de mensajes (Facebook/Instagram y correo; YouTube no tiene mensajes)
    private function cuentas()
    {
        return RedCuenta::conBandeja()->where('estado', '<>', 'desconectada')->orderBy('page_nombre')->get();
    }

    private function canalesYoutube()
    {
        return RedCuenta::youtube()->where('estado', '<>', 'desconectada')->orderBy('page_nombre')->get();
    }

    // ---------- Bandeja de mensajes ----------
    public function index(Request $request)
    {
        $cuentas = $this->cuentas();
        if ($cuentas->isEmpty()) return view('redes.conectar', ['activo' => Meta::activo(), 'ytActivo' => YouTube::activo(), 'canales' => $this->canalesYoutube()]);

        $filtro = $request->get('ver', 'todos');
        $q = trim((string) $request->get('q'));
        // Con varias cuentas conectadas, cada una tiene su pestaña y solo se ven sus chats
        $cuentaSel = $this->cuentaElegida($request, $cuentas);
        $convs = RedConversacion::with('cuenta')
            ->when($cuentaSel, fn ($w) => $w->where('cuenta_id', $cuentaSel))
            ->when(in_array($filtro, ['facebook', 'instagram', 'correo'], true), fn ($w) => $w->where('plataforma', $filtro))
            ->when($filtro === 'sin_leer', fn ($w) => $w->where('no_leidos', '>', 0))
            ->when(isset(RedConversacion::ETIQUETAS[$filtro]), fn ($w) => $w->where('etiqueta', $filtro))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('contacto_nombre', 'like', "%$q%")->orWhere('contacto_usuario', 'like', "%$q%")->orWhere('ultimo_texto', 'like', "%$q%")))
            ->orderByDesc('ultimo_en')->limit(150)->get();

        return view('redes.index', [
            'cuentas' => $cuentas, 'convs' => $convs, 'filtro' => $filtro, 'q' => $q,
            'actual' => $request->integer('c') ?: null,
            'sinLeer' => RedConversacion::where('no_leidos', '>', 0)->when($cuentaSel, fn ($w) => $w->where('cuenta_id', $cuentaSel))->count(),
            'cuentaSel' => $cuentaSel,
            'sinLeerPorCuenta' => RedConversacion::where('no_leidos', '>', 0)->selectRaw('cuenta_id, COUNT(*) n')->groupBy('cuenta_id')->pluck('n', 'cuenta_id'),
            'tienda' => TiendaActual::tienda(),
        ]);
    }

    // Lista de chats en JSON (se refresca sola cada pocos segundos)
    public function lista(Request $request)
    {
        $filtro = $request->get('ver', 'todos');
        $q = trim((string) $request->get('q'));
        $cuentaSel = $this->cuentaElegida($request, $this->cuentas());
        return response()->json(RedConversacion::query()
            ->when($cuentaSel, fn ($w) => $w->where('cuenta_id', $cuentaSel))
            ->when(in_array($filtro, ['facebook', 'instagram', 'correo'], true), fn ($w) => $w->where('plataforma', $filtro))
            ->when($filtro === 'sin_leer', fn ($w) => $w->where('no_leidos', '>', 0))
            ->when(isset(RedConversacion::ETIQUETAS[$filtro]), fn ($w) => $w->where('etiqueta', $filtro))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('contacto_nombre', 'like', "%$q%")->orWhere('contacto_usuario', 'like', "%$q%")->orWhere('ultimo_texto', 'like', "%$q%")))
            ->orderByDesc('ultimo_en')->limit(150)->get()->map(fn ($c) => $this->convJson($c)));
    }

    // Cuenta elegida en las pestañas (si hay más de una conectada); se recuerda en la sesión
    private function cuentaElegida(Request $request, $cuentas): ?int
    {
        if ($cuentas->count() < 2) return null;
        $id = $request->integer('cuenta') ?: (int) session('redes_cuenta');
        if (!$cuentas->contains('id', $id)) $id = (int) $cuentas->first()->id;
        session(['redes_cuenta' => $id]);
        return $id;
    }

    private function convJson(RedConversacion $c): array
    {
        return [
            'id' => $c->id, 'plataforma' => $c->plataforma, 'nombre' => $c->nombre, 'usuario' => $c->contacto_usuario, 'foto' => $c->contacto_foto,
            'texto' => $c->ultimo_texto, 'cuando' => $c->ultimo_en?->locale('es')->diffForHumans(null, true, true), 'no_leidos' => $c->no_leidos,
            'etiqueta' => $c->etiqueta, 'cliente_id' => $c->cliente_id, 'puede' => $c->puede_responder,
            'vence' => $c->vence_en?->locale('es')->diffForHumans(), 'horas' => \App\Support\Meta::ventanaHoras(),
            'correo' => $c->plataforma === 'correo' ? $c->contacto_id : null, 'asunto' => $c->asunto,
        ];
    }

    // Mensajes de una conversación (y la marca como leída)
    public function mensajes(RedConversacion $conversacion, Request $request)
    {
        $desde = $request->integer('desde');
        $m = $conversacion->mensajes()->with('user:id,name')->when($desde, fn ($q) => $q->where('id', '>', $desde))
            ->orderBy('enviado_en')->orderBy('id')->limit(300)->get()
            ->map(fn ($x) => ['id' => $x->id, 'yo' => !$x->del_cliente, 'texto' => $x->texto, 'img' => $x->adjunto_tipo === 'image' || $x->adjunto_tipo === 'imagen' ? $x->adjunto_url : null,
                'adj' => $x->adjunto_url && !in_array($x->adjunto_tipo, ['image', 'imagen'], true) ? $x->adjunto_url : null,
                'hora' => $x->enviado_en?->format('d/m h:i a'), 'quien' => $x->user?->name]);
        if ($conversacion->no_leidos) $conversacion->update(['no_leidos' => 0]);
        return response()->json(['conv' => $this->convJson($conversacion->fresh()), 'mensajes' => $m]);
    }

    public function enviar(Request $request, RedConversacion $conversacion)
    {
        $d = $request->validate(['texto' => 'nullable|string|max:1900', 'imagen' => 'nullable|url|max:900'], ['texto.max' => 'El mensaje es muy largo.']);
        $texto = trim((string) ($d['texto'] ?? ''));
        if ($texto === '' && empty($d['imagen'])) return response()->json(['message' => 'Escriba el mensaje.'], 422);
        if (!$conversacion->puede_responder) {
            $plazo = \App\Support\Meta::ventanaHoras() > 24 ? '7 días' : '24 horas';
            return response()->json(['message' => 'Pasaron más de ' . $plazo . ' desde el último mensaje del cliente: Meta no deja responder por aquí. Escríbale por WhatsApp o espere a que vuelva a escribir.'], 422);
        }
        // Correo: se responde en el mismo hilo (una foto de producto va como enlace)
        if ($conversacion->plataforma === 'correo') {
            $cuerpo = trim($texto . (!empty($d['imagen']) ? "\n\n" . $d['imagen'] : ''));
            try {
                $mid = \App\Support\Correo::enviar($conversacion, $cuerpo);
            } catch (Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            Meta::guardarMensaje($conversacion, $mid, false, $cuerpo, null, null, now(), Auth::id(), false);
            if (in_array($conversacion->etiqueta, [null, 'nuevo'], true)) $conversacion->update(['etiqueta' => 'interesado']);
            return response()->json(['ok' => true]);
        }
        try {
            $mids = [];
            if (!empty($d['imagen'])) $mids[] = [Meta::enviar($conversacion, null, $d['imagen']), null, $d['imagen']];
            if ($texto !== '') $mids[] = [Meta::enviar($conversacion, $texto), $texto, null];
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        foreach ($mids as [$mid, $t, $img]) {
            Meta::guardarMensaje($conversacion, $mid, false, $t, $img, $img ? 'imagen' : null, now(), Auth::id(), false);
        }
        if (in_array($conversacion->etiqueta, [null, 'nuevo'], true)) $conversacion->update(['etiqueta' => 'interesado']);
        return response()->json(['ok' => true]);
    }

    public function etiqueta(Request $request, RedConversacion $conversacion)
    {
        $e = (string) $request->input('etiqueta');
        $conversacion->update(['etiqueta' => isset(RedConversacion::ETIQUETAS[$e]) ? $e : null]);
        return response()->json(['ok' => true]);
    }

    // Guarda el contacto del chat como cliente de la tienda
    public function guardarCliente(Request $request, RedConversacion $conversacion)
    {
        $d = $request->validate(['nombre' => 'required|string|min:2|max:80', 'telefono' => 'nullable|string|max:20', 'documento' => 'nullable|string|max:20']);
        $tel = preg_replace('/\D/', '', (string) ($d['telefono'] ?? ''));
        if (strlen($tel) === 12 && str_starts_with($tel, '57')) $tel = substr($tel, 2);
        $doc = preg_replace('/[^0-9A-Za-z-]/', '', (string) ($d['documento'] ?? ''));
        $cliente = DB::transaction(function () use ($d, $tel, $doc, $conversacion) {
            $p = null;
            if ($doc !== '') $p = Persona::whereHas('cliente')->where('numero_documento', $doc)->first();
            if (!$p && $tel !== '') $p = Persona::whereHas('cliente')->where('telefono', $tel)->first();
            if (!$p) {
                $p = Persona::create([
                    'razon_social' => trim($d['nombre']), 'direccion' => '', 'telefono' => $tel ?: null, 'tipo_persona' => 'natural',
                    'documento_id' => \App\Models\Documento::orderByRaw("tipo_documento = 'Cédula de ciudadanía' desc")->value('id'),
                    'numero_documento' => $doc ?: ($tel ? 'TEL-' . $tel : 'RED-' . strtoupper(substr($conversacion->plataforma, 0, 2)) . '-' . $conversacion->contacto_id),
                ]);
                $p->cliente()->create(['persona_id' => $p->id]);
            }
            return $p->cliente;
        });
        $conversacion->update(['cliente_id' => $cliente->id, 'etiqueta' => $conversacion->etiqueta === 'nuevo' ? 'cliente' : $conversacion->etiqueta]);
        return response()->json(['ok' => true, 'cliente_id' => $cliente->id]);
    }

    // "Actualizar": trae los chats de Meta en segundo plano. Meta a veces tarda minutos y la página
    // no puede esperar (Cloudflare corta a los 100 s con error 524), así que se responde de una vez.
    public function sincronizar()
    {
        $tid = TiendaActual::id();
        $ids = $this->cuentas()->pluck('id')->all();
        if (!$ids) return back()->with('info', 'No hay cuentas de Facebook o Instagram conectadas.');

        self::sincronizarEnFondo($tid, $ids);

        return back()->with('info', 'Buscando mensajes nuevos en Facebook e Instagram… Van apareciendo solos en la bandeja en uno o dos minutos.');
    }

    // Trae los chats de Meta después de responder la página (Meta puede tardar minutos; Cloudflare corta a los 100 s)
    private static function sincronizarEnFondo(?int $tid, array $ids): void
    {
        dispatch(function () use ($tid, $ids) {
            @set_time_limit(300);
            Meta::$hasta = microtime(true) + 240; // máximo 4 minutos en total
            foreach (RedCuenta::withoutGlobalScopes()->whereIn('id', $ids)->get() as $c) {
                Meta::$avisos = [];
                try {
                    if ($c->es_correo) {
                        \App\Support\Correo::sincronizar($c);
                        continue;
                    }
                    Meta::sincronizar($c);
                    if (Meta::$avisos) {
                        $c->update(['error' => mb_substr(Meta::sinLlaves(implode(' · ', array_unique(Meta::$avisos))), 0, 250)]);
                    }
                } catch (Throwable $e) {
                    $c->update(['estado' => 'error', 'error' => mb_substr(Meta::sinLlaves($e->getMessage()), 0, 250)]);
                }
            }
            TiendaActual::set($tid);
        })->afterResponse();
    }

    // Productos para enviar en el chat o publicar
    public function productos(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $t = TiendaActual::tienda();
        return response()->json(Producto::where('estado', 1)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%")))
            ->orderByRaw('img_path IS NULL')->latest()->limit(24)->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'nombre' => $p->nombre, 'precio' => dinero($p->precio_venta),
                'img' => $p->img_path ? asset('storage/productos/' . $p->img_path) : null,
                'url' => $t?->catalogo_activo && $t->slug ? catalogoController::urlProducto($t, $p) : null,
            ]));
    }

    // ---------- Publicar ----------
    public const VIDEO_MAX_MB = 500;
    private const PARTE_MB = 4;

    public function publicar()
    {
        $cuentas = RedCuenta::meta()->where('estado', '<>', 'desconectada')->orderBy('page_nombre')->get();
        $canales = $this->canalesYoutube();
        if ($cuentas->isEmpty() && $canales->isEmpty()) return redirect()->route('redes.index');
        return view('redes.publicar', [
            'cuentas' => $cuentas, 'canales' => $canales,
            'lista' => RedPublicacion::with('user:id,name', 'cuenta:id,page_nombre,ig_usuario', 'ytCuenta:id,page_nombre')->latest()->limit(40)->get(),
            'tienda' => TiendaActual::tienda(),
            'videoMaxMb' => self::VIDEO_MAX_MB, 'parteMb' => self::PARTE_MB,
        ]);
    }

    /**
     * Subida del video por partes de 4 MB (así no choca con el límite de tamaño del servidor y aguanta internet de celular).
     * El navegador manda las partes en orden; al llegar la última se revisa que sea un MP4/MOV de verdad y queda listo para publicar.
     */
    public function subirVideo(Request $request)
    {
        $d = $request->validate([
            'subida' => 'required|string|regex:/^[A-Za-z0-9]{24,40}$/',
            'parte' => 'required|integer|min:0|max:1000',
            'partes' => 'required|integer|min:1|max:1000',
            'total' => 'required|integer|min:1|max:' . (self::VIDEO_MAX_MB * 1024 * 1024),
            'archivo' => 'required|file|max:' . (self::PARTE_MB * 1024 + 64),
        ], ['total.max' => 'El video pesa más de ' . self::VIDEO_MAX_MB . ' MB.']);
        $tid = (int) TiendaActual::id();
        $disco = Storage::disk('local');
        $tmp = 'redes-subidas/' . $tid . '-' . $d['subida'] . '.part';
        $tam = $disco->exists($tmp) ? $disco->size($tmp) : 0;
        $esperado = $d['parte'] * self::PARTE_MB * 1024 * 1024;

        if ($tam > $esperado) {
            // Esa parte ya había llegado (el celular la reenvió): se ignora
        } elseif ($tam < $esperado) {
            return response()->json(['ok' => false, 'msg' => 'Se perdió una parte del video. Vuelva a elegirlo.', 'desde' => intdiv($tam, self::PARTE_MB * 1024 * 1024)], 409);
        } else {
            if (!$disco->exists('redes-subidas')) $disco->makeDirectory('redes-subidas');
            $orig = fopen($request->file('archivo')->getRealPath(), 'rb');
            $dest = fopen($disco->path($tmp), 'ab');
            stream_copy_to_stream($orig, $dest);
            fclose($orig); fclose($dest);
            clearstatcache();
        }

        if ($d['parte'] < $d['partes'] - 1) return response()->json(['ok' => true]);

        // Última parte: tamaño exacto y que de verdad sea un video MP4 o MOV (empieza con la caja "ftyp")
        $ruta = $disco->path($tmp);
        $h = fopen($ruta, 'rb'); $cab = fread($h, 12); fclose($h);
        if (filesize($ruta) !== (int) $d['total'] || substr($cab, 4, 4) !== 'ftyp') {
            $disco->delete($tmp);
            return response()->json(['ok' => false, 'msg' => filesize($ruta) !== (int) $d['total'] ? 'El video no llegó completo. Intente de nuevo.' : 'Ese archivo no es un video MP4 o MOV.'], 422);
        }
        $final = 'redes/' . $tid . '/video-' . now()->format('Ymd-His') . '-' . Str::random(8) . '.mp4';
        Storage::disk('public')->makeDirectory('redes/' . $tid);
        rename($ruta, Storage::disk('public')->path($final));
        Cache::put('redvid:' . $tid . ':' . $d['subida'], $final, now()->addDay());
        return response()->json(['ok' => true, 'listo' => true]);
    }

    public function guardarPublicacion(Request $request)
    {
        $d = $request->validate([
            'tipo' => 'nullable|in:foto,video',
            'cuenta_id' => 'nullable|integer',
            'texto' => 'nullable|string|max:2200',
            'imagen' => 'nullable|image|max:8192',
            'producto_id' => 'nullable|integer',
            'en_facebook' => 'nullable|boolean',
            'en_instagram' => 'nullable|boolean',
            'en_youtube' => 'nullable|boolean',
            'yt_cuenta_id' => 'nullable|integer',
            'titulo' => 'nullable|string|max:100',
            'yt_privacidad' => 'nullable|in:' . implode(',', array_keys(RedPublicacion::PRIVACIDAD)),
            'subida' => 'nullable|string|regex:/^[A-Za-z0-9]{24,40}$/',
            'duracion' => 'nullable|numeric|min:0|max:43200',
            'ancho' => 'nullable|integer|min:0|max:10000',
            'alto' => 'nullable|integer|min:0|max:10000',
            'portada_data' => 'nullable|string|max:3000000',
            'cuando' => 'nullable|in:ahora,programar',
            'programada_para' => 'nullable|date',
        ], ['imagen.image' => 'El archivo debe ser una foto (JPG o PNG).', 'imagen.max' => 'La foto pesa más de 8 MB.', 'texto.max' => 'El texto es muy largo (máximo 2.200 letras).',
            'titulo.max' => 'El título de YouTube puede tener máximo 100 letras.']);
        $esVideo = ($d['tipo'] ?? 'foto') === 'video';
        $cuenta = !empty($d['cuenta_id']) ? RedCuenta::meta()->where('estado', '<>', 'desconectada')->whereKey($d['cuenta_id'])->first() : null;
        $fb = $cuenta && (bool) ($d['en_facebook'] ?? false);
        $ig = $cuenta && (bool) ($d['en_instagram'] ?? false) && $cuenta->tiene_instagram;
        $canal = $esVideo && ($d['en_youtube'] ?? false) ? RedCuenta::youtube()->where('estado', '<>', 'desconectada')->when(!empty($d['yt_cuenta_id']), fn ($q) => $q->whereKey($d['yt_cuenta_id']))->first() : null;
        $yt = (bool) $canal;
        if (!$fb && !$ig && !$yt) return back()->withInput()->withErrors($esVideo ? 'Elija dónde publicar el video: Facebook, Instagram o YouTube.' : 'Elija dónde publicar: Facebook, Instagram o ambos.');

        $programar = ($d['cuando'] ?? 'ahora') === 'programar' && !empty($d['programada_para']);
        $cuandoProg = $programar ? \Illuminate\Support\Carbon::parse($d['programada_para']) : null;
        if ($programar && $cuandoProg->lt(now()->addMinutes(2))) return back()->withInput()->withErrors('La fecha programada debe ser en el futuro.');

        if ($esVideo) return $this->guardarVideo($request, $d, $cuenta, $fb, $ig, $canal, $cuandoProg);

        $imagen = null;
        $producto = !empty($d['producto_id']) ? Producto::find($d['producto_id']) : null;
        try {
            if ($request->hasFile('imagen')) $imagen = $this->guardarJpg($request->file('imagen')->getRealPath());
            elseif ($producto && $producto->img_path && Storage::disk('public')->exists('productos/' . $producto->img_path)) $imagen = $this->guardarJpg(Storage::disk('public')->path('productos/' . $producto->img_path));
        } catch (Throwable $e) {
            return back()->withInput()->withErrors('No se pudo leer la foto: ' . $e->getMessage());
        }
        if ($ig && !$imagen) return back()->withInput()->withErrors('Instagram necesita una foto: súbala o elija un producto que tenga foto.');
        if (trim((string) ($d['texto'] ?? '')) === '' && !$imagen) return back()->withInput()->withErrors('Escriba un texto o suba una foto.');

        $p = RedPublicacion::create([
            'cuenta_id' => $cuenta->id, 'user_id' => Auth::id(), 'producto_id' => $producto?->id, 'tipo' => 'foto',
            'texto' => trim((string) ($d['texto'] ?? '')), 'imagen' => $imagen, 'en_facebook' => $fb, 'en_instagram' => $ig,
            'estado' => $cuandoProg ? 'programada' : 'pendiente', 'programada_para' => $cuandoProg,
        ]);
        if ($cuandoProg) return redirect()->route('redes.publicar')->with('success', 'Publicación programada para el ' . $cuandoProg->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') . '.');

        Meta::publicar($p);
        return redirect()->route('redes.publicar')->with($p->estado === 'error' ? 'error' : 'success',
            $p->estado === 'publicada' ? '¡Publicado! 🎉' : ($p->estado === 'parcial' ? 'Se publicó solo en una red: ' . $p->error : 'No se pudo publicar: ' . $p->error));
    }

    // Video: queda en la cola del programador, que lo sube red por red (Reels de Facebook e Instagram, YouTube)
    private function guardarVideo(Request $request, array $d, ?RedCuenta $cuenta, bool $fb, bool $ig, ?RedCuenta $canal, $cuandoProg)
    {
        $tid = (int) TiendaActual::id();
        $video = !empty($d['subida']) ? Cache::get('redvid:' . $tid . ':' . $d['subida']) : null;
        if (!$video || !Storage::disk('public')->exists($video)) return back()->withInput()->withErrors('Suba el video primero (espere a que la barra llegue al 100 %).');
        $texto = trim((string) ($d['texto'] ?? ''));
        $titulo = trim((string) ($d['titulo'] ?? ''));
        if ($canal && $titulo === '') $titulo = mb_substr(strtok($texto, "\n") ?: '', 0, 100);
        if ($canal && $titulo === '') return back()->withInput()->withErrors('Escriba el título del video para YouTube.');

        $portada = null;
        if (!empty($d['portada_data']) && preg_match('#^data:image/(jpeg|png|webp);base64,#', $d['portada_data'])) {
            $bin = base64_decode(substr($d['portada_data'], strpos($d['portada_data'], ',') + 1), true);
            if ($bin !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'por');
                file_put_contents($tmp, $bin);
                try { $portada = $this->guardarJpg($tmp); } catch (Throwable $e) {}
                @unlink($tmp);
            }
        }

        $p = RedPublicacion::create([
            'cuenta_id' => $cuenta?->id, 'user_id' => Auth::id(), 'tipo' => 'video', 'texto' => $texto, 'video' => $video, 'portada' => $portada,
            'duracion' => $d['duracion'] ?? null, 'ancho' => $d['ancho'] ?? null, 'alto' => $d['alto'] ?? null, 'peso' => Storage::disk('public')->size($video),
            'en_facebook' => $fb, 'en_instagram' => $ig, 'en_youtube' => (bool) $canal, 'yt_cuenta_id' => $canal?->id,
            'titulo' => $canal ? $titulo : null, 'yt_privacidad' => $d['yt_privacidad'] ?? 'public',
            'estado' => $cuandoProg ? 'programada' : 'pendiente', 'programada_para' => $cuandoProg ?? now(),
        ]);
        Cache::forget('redvid:' . $tid . ':' . $d['subida']);
        return redirect()->route('redes.publicar')->with('success', $cuandoProg
            ? 'Video programado para el ' . $cuandoProg->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') . ' en ' . $p->redes_texto . '.'
            : '¡Listo! El video empieza a subir a ' . $p->redes_texto . ' en menos de un minuto. Puede cerrar esta página: aquí abajo ve cómo va.');
    }

    // Estado de las publicaciones que van subiendo (la lista se refresca sola)
    public function estadoPublicaciones(Request $request)
    {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string) $request->get('ids')))), 0, 40);
        return response()->json(RedPublicacion::whereIn('id', $ids)->get(['id', 'estado', 'error', 'fb_url', 'ig_url', 'yt_url'])->keyBy('id'));
    }

    public function reintentar(RedPublicacion $publicacion)
    {
        if (!in_array($publicacion->estado, ['error', 'parcial'], true)) return back();
        if ($publicacion->es_video) {
            if (!$publicacion->video || !Storage::disk('public')->exists($publicacion->video)) return back()->with('error', 'El video ya no está en el servidor (se borra a los 15 días). Súbalo de nuevo.');
            Publicador::reintentar($publicacion);
            return back()->with('success', 'Reintentando: en menos de un minuto vuelve a subir a las redes que fallaron.');
        }
        Meta::publicar($publicacion);
        return back()->with($publicacion->estado === 'publicada' ? 'success' : 'error', $publicacion->estado === 'publicada' ? '¡Publicado!' : 'Sigue sin publicarse: ' . $publicacion->error);
    }

    public function borrarPublicacion(RedPublicacion $publicacion)
    {
        abort_unless(in_array($publicacion->estado, ['programada', 'error'], true), 403);
        $publicacion->borrarArchivos();
        $publicacion->delete();
        return back()->with('success', 'Publicación eliminada.');
    }

    // ---------- YouTube ----------
    public function youtubeConectar()
    {
        if (!YouTube::activo()) return redirect()->route('redes.cuentas')->withErrors('La conexión con YouTube aún no está habilitada. Comuníquese con LocalPro.');
        if ($this->canalesYoutube()->count() >= YouTube::MAX_CANALES) return redirect()->route('redes.cuentas')->withErrors('Ya tiene ' . YouTube::MAX_CANALES . ' canales de YouTube conectados (el máximo). Desconecte uno para agregar otro.');
        $state = Str::random(40);
        session(['yt_state' => $state, 'yt_tienda' => TiendaActual::id()]);
        return redirect()->away(YouTube::urlLogin($state));
    }

    public function youtubeRetorno(Request $request)
    {
        if ($request->query('error') || !$request->query('code')) {
            $porque = $request->query('error') === 'access_denied' ? 'no se aceptaron los permisos' : (string) $request->query('error');
            return redirect()->route('redes.cuentas')->withErrors('No se conectó YouTube' . ($porque ? ': ' . $porque : '') . '. Inténtelo de nuevo y acepte los permisos.');
        }
        abort_unless(hash_equals((string) session('yt_state'), (string) $request->query('state')) && (int) session('yt_tienda') === (int) TiendaActual::id(), 403);
        session()->forget('yt_state');
        try {
            $token = YouTube::tokenDesdeCodigo((string) $request->query('code'));
            $ch = YouTube::canal($token['access']);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('redes.cuentas')->withErrors('YouTube: ' . $e->getMessage());
        }
        $c = RedCuenta::firstOrNew(['page_id' => 'yt:' . $ch['id']]);
        if (!$c->exists || $c->estado === 'desconectada') {
            if ($this->canalesYoutube()->count() >= YouTube::MAX_CANALES) return redirect()->route('redes.cuentas')->withErrors('Ya tiene ' . YouTube::MAX_CANALES . ' canales de YouTube conectados (el máximo).');
        }
        $c->forceFill([
            'tipo' => 'youtube', 'page_nombre' => $ch['nombre'], 'page_foto' => $ch['foto'], 'ig_usuario' => $ch['usuario'],
            'page_token' => json_encode($token), 'conectado_por' => Auth::id(), 'estado' => 'activa', 'error' => null, 'sincronizado_en' => now(),
        ])->save();
        return redirect()->route('redes.cuentas')->with('success', '¡Listo! Se conectó el canal de YouTube "' . $ch['nombre'] . '". Ya puede subir videos desde Publicar.');
    }

    // Instagram solo acepta JPG: se convierte y se reduce a máximo 1440 px
    private function guardarJpg(string $origen): string
    {
        $info = @getimagesize($origen);
        if (!$info) throw new \RuntimeException('archivo de imagen no válido');
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($origen),
            IMAGETYPE_PNG => imagecreatefrompng($origen),
            IMAGETYPE_WEBP => imagecreatefromwebp($origen),
            IMAGETYPE_GIF => imagecreatefromgif($origen),
            default => throw new \RuntimeException('use una foto JPG, PNG o WEBP'),
        };
        [$w, $h] = [imagesx($img), imagesy($img)];
        $max = 1440;
        $esc = min(1, $max / max($w, $h));
        $nw = (int) round($w * $esc); $nh = (int) round($h * $esc);
        $lienzo = imagecreatetruecolor($nw, $nh);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start(); imagejpeg($lienzo, null, 88); $bin = ob_get_clean();
        imagedestroy($img); imagedestroy($lienzo);
        $ruta = 'redes/' . TiendaActual::id() . '/' . now()->format('Ymd-His') . '-' . Str::random(8) . '.jpg';
        Storage::disk('public')->put($ruta, $bin);
        return $ruta;
    }

    // ---------- Conectar cuentas ----------
    public function conectar(Request $request)
    {
        if (!Meta::activo()) return redirect()->route('redes.index')->withErrors('La conexión con Facebook e Instagram aún no está habilitada. Comuníquese con LocalPro.');
        // Cada marca conecta sola sus redes: sin pedir acceso a un asesor
        // Cada negocio conecta 1 red (página de Facebook con su Instagram). Reconectar la misma sí se permite.
        if (!$request->boolean('reconectar') && self::cupoRedes() <= 0) {
            return redirect()->route('redes.cuentas')->withErrors('Su negocio ya tiene su red social conectada. Si falla, use "Reconectar".');
        }
        $state = Str::random(32);
        session(['meta_state' => $state, 'meta_tienda' => TiendaActual::id()]);
        return redirect()->away(Meta::urlLogin(route('redes.callback'), $state));
    }

    public function callback(Request $request)
    {
        if ($request->query('error') || !$request->query('code')) {
            // Facebook explica por qué (canceló, permisos, app en desarrollo…): se muestra y queda para el administrador
            $porque = trim((string) ($request->query('error_description') ?: $request->query('error_reason') ?: $request->query('error')));
            Meta::anotarError('Volviendo de Facebook (' . (TiendaActual::tienda()->codigo ?? '') . ')', $porque ?: 'sin código de autorización');
            return redirect()->route('redes.index')->withErrors('No se completó la conexión con Facebook' . ($porque ? ': ' . $porque : '') . '. Inténtelo de nuevo y acepte todos los permisos.');
        }
        abort_unless(hash_equals((string) session('meta_state'), (string) $request->query('state')) && (int) session('meta_tienda') === (int) TiendaActual::id(), 403);
        try {
            $token = Meta::tokenDeUsuario((string) $request->query('code'), route('redes.callback'));
            $paginas = Meta::paginas($token);
        } catch (Throwable $e) {
            report($e);
            Meta::anotarError('Pidiendo el acceso (' . (TiendaActual::tienda()->codigo ?? '') . ')', $e->getMessage());
            return redirect()->route('redes.index')->withErrors('Facebook respondió: ' . $e->getMessage());
        }
        session()->forget('meta_state');
        if (!$paginas) return redirect()->route('redes.index')->withErrors('Su cuenta de Facebook no administra ninguna página, o no la eligió al dar los permisos. Cree una página de su negocio en Facebook y vuelva a conectar.');
        session(['meta_paginas' => encrypt($paginas)]);
        return redirect()->route('redes.elegir');
    }

    // Redes que el negocio todavía puede conectar (1 por negocio; el superadmin lo puede cambiar en pruebas)
    public static function cupoRedes(): int
    {
        $max = max(1, (int) (TiendaActual::tienda()?->redes_max ?? 1));
        return $max - RedCuenta::meta()->where('estado', '<>', 'desconectada')->count();
    }

    public function elegir()
    {
        $paginas = session('meta_paginas') ? decrypt(session('meta_paginas')) : [];
        if (!$paginas) return redirect()->route('redes.index');
        return view('redes.elegir', ['paginas' => $paginas, 'ya' => RedCuenta::pluck('page_id')->all(), 'cupo' => self::cupoRedes(),
            'activas' => RedCuenta::meta()->where('estado', '<>', 'desconectada')->pluck('page_id')->all()]);
    }

    public function guardarPaginas(Request $request)
    {
        $paginas = collect(session('meta_paginas') ? decrypt(session('meta_paginas')) : [])->keyBy('id');
        $ids = collect((array) $request->input('paginas'))->filter(fn ($id) => $paginas->has($id));
        if ($ids->isEmpty()) return back()->withErrors('Elija al menos una página.');
        // Las páginas que ya estaban conectadas se pueden reconectar; las nuevas cuentan contra el cupo
        $activas = RedCuenta::meta()->where('estado', '<>', 'desconectada')->pluck('page_id')->map(fn ($x) => (string) $x)->all();
        $nuevas = $ids->reject(fn ($id) => in_array((string) $id, $activas, true));
        if ($nuevas->count() > self::cupoRedes()) {
            return back()->withErrors(self::cupoRedes() <= 0
                ? 'Su negocio ya tiene su red social conectada. Elija solo la página que ya tenía para reconectarla.'
                : 'Puede conectar ' . self::cupoRedes() . ' página. Elija solo esa.');
        }
        $n = 0; $avisos = []; $conectadas = [];
        foreach ($ids as $id) {
            $p = $paginas[$id];
            $c = RedCuenta::updateOrCreate(['page_id' => $p['id']], [
                'page_nombre' => $p['nombre'], 'page_foto' => $p['foto'], 'page_token' => $p['token'],
                'ig_id' => $p['ig_id'], 'ig_usuario' => $p['ig_usuario'], 'ig_foto' => $p['ig_foto'],
                'conectado_por' => Auth::id(), 'estado' => 'activa', 'error' => null,
            ]);
            try { Meta::suscribir($c); } catch (Throwable $e) { $avisos[] = $p['nombre'] . ': no se activaron los mensajes en tiempo real (' . $e->getMessage() . ')'; }
            $conectadas[] = $c->id;
            $n++;
        }
        session()->forget('meta_paginas');
        // Los chats anteriores se traen en segundo plano para no dejar la página esperando
        self::sincronizarEnFondo(TiendaActual::id(), $conectadas);
        return redirect()->route('redes.index')->with('success', '¡Listo! ' . ($n === 1 ? 'Se conectó 1 página' : "Se conectaron $n páginas") . '. Los chats anteriores van apareciendo en uno o dos minutos.' . ($avisos ? ' ' . implode(' · ', $avisos) : ''));
    }

    public function desconectar(RedCuenta $cuenta)
    {
        if ($cuenta->es_youtube) {
            // Se le devuelve el permiso a Google para que no quede acceso guardado
            try { \Illuminate\Support\Facades\Http::timeout(10)->asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => $cuenta->youtube_token['refresh'] ?? '']); } catch (Throwable $e) {}
        } elseif (!$cuenta->es_correo) {
            Meta::desuscribir($cuenta);
        }
        $cuenta->update(['estado' => 'desconectada', 'page_token' => '-']);
        return redirect()->route('redes.cuentas')->with('success', 'Se desconectó ' . $cuenta->page_nombre . '. Los chats guardados se conservan.');
    }

    public function cuentasVista()
    {
        $cuentas = RedCuenta::orderByRaw("estado = 'desconectada'")->orderBy('page_nombre')->get();
        // "Revisar mensajes": compara lo que tiene Meta con lo guardado, para saber si algo no está llegando
        $revision = request()->boolean('revisar') && Meta::activo()
            ? $cuentas->where('estado', '<>', 'desconectada')->reject(fn ($c) => $c->es_correo || $c->es_youtube)->flatMap(fn ($c) => Meta::probarCuenta($c))->all() : null;
        return view('redes.cuentas', ['cuentas' => $cuentas, 'activo' => Meta::activo(), 'revision' => $revision,
            'ytActivo' => YouTube::activo(), 'cupoYt' => YouTube::MAX_CANALES - $this->canalesYoutube()->count(),
            'webhook' => \App\Models\Ajuste::valor('meta_ultimo_webhook'),
            'proveedores' => \App\Support\Correo::PROVEEDORES,
            'cupoCorreo' => self::MAX_CORREOS - RedCuenta::correo()->where('estado', '<>', 'desconectada')->count()]);
    }

    public const MAX_CORREOS = 2;

    // Conectar un correo (Gmail, Outlook o el de su dominio) con contraseña de aplicación: se prueba antes de guardar
    public function conectarCorreo(Request $request)
    {
        $d = $request->validate([
            'proveedor' => 'required|in:' . implode(',', array_keys(\App\Support\Correo::PROVEEDORES)),
            'email' => 'required|email|max:150',
            'clave' => 'required|string|max:200',
            'imap_host' => 'nullable|required_if:proveedor,otro|string|max:120',
            'imap_port' => 'nullable|integer|min:1|max:65535',
            'smtp_host' => 'nullable|required_if:proveedor,otro|string|max:120',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
        ], ['email.email' => 'Escriba un correo válido.', 'clave.required' => 'Pegue la contraseña de aplicación.', 'imap_host.required_if' => 'Escriba el servidor IMAP.', 'smtp_host.required_if' => 'Escriba el servidor SMTP.']);
        $email = mb_strtolower(trim($d['email']));
        $ya = RedCuenta::correo()->where('page_id', 'correo:' . $email)->first();
        if ((!$ya || $ya->estado === 'desconectada') && RedCuenta::correo()->where('estado', '<>', 'desconectada')->count() >= self::MAX_CORREOS) {
            return back()->withInput($request->except('clave'))->withErrors('Puede conectar hasta ' . self::MAX_CORREOS . ' correos. Desconecte uno para agregar otro.');
        }
        $p = \App\Support\Correo::PROVEEDORES[$d['proveedor']];
        $cfg = [
            'proveedor' => $d['proveedor'], 'email' => $email, 'nombre' => TiendaActual::tienda()->nombre ?? null,
            'usuario' => $email, 'clave' => preg_replace('/\s+/', '', $d['clave']), // Google muestra la clave con espacios
            'imap_host' => $d['imap_host'] ?: $p[1], 'imap_port' => (int) ($d['imap_port'] ?: $p[2]),
            'smtp_host' => $d['smtp_host'] ?: $p[3], 'smtp_port' => (int) ($d['smtp_port'] ?: $p[4]),
            'smtp_cifrado' => (int) ($d['smtp_port'] ?: $p[4]) === 587 ? 'tls' : 'ssl',
        ];
        try {
            \App\Support\Correo::probar($cfg);
        } catch (Throwable $e) {
            return back()->withInput($request->except('clave'))->withErrors('No se pudo conectar el correo: ' . \App\Support\Correo::limpiar($e->getMessage()));
        }
        $c = $ya ?? new RedCuenta();
        $c->forceFill([
            'tipo' => 'correo', 'page_id' => 'correo:' . $email, 'page_nombre' => $email, 'page_foto' => null,
            'page_token' => json_encode($cfg), 'conectado_por' => Auth::id(), 'estado' => 'activa', 'error' => null,
        ])->save();
        self::sincronizarEnFondo(TiendaActual::id(), [$c->id]);
        return redirect()->route('redes.cuentas')->with('success', '¡Listo! Se conectó ' . $email . '. Los correos nuevos van a aparecer en Mensajes en uno o dos minutos.');
    }
}
