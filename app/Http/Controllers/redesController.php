<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Producto;
use App\Models\RedConversacion;
use App\Models\RedCuenta;
use App\Models\RedMensaje;
use App\Models\RedPublicacion;
use App\Support\Meta;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $this->middleware('permission:editar-negocio', ['only' => ['conectar', 'callback', 'elegir', 'guardarPaginas', 'desconectar']]);
        $this->middleware('permission:publicar-redes|editar-negocio', ['only' => ['publicar', 'guardarPublicacion', 'productos', 'reintentar', 'borrarPublicacion']]);
    }

    private function cuentas()
    {
        return RedCuenta::where('estado', '<>', 'desconectada')->orderBy('page_nombre')->get();
    }

    // ---------- Bandeja de mensajes ----------
    public function index(Request $request)
    {
        $cuentas = $this->cuentas();
        if ($cuentas->isEmpty()) return view('redes.conectar', ['activo' => Meta::activo()]);

        $filtro = $request->get('ver', 'todos');
        $q = trim((string) $request->get('q'));
        $convs = RedConversacion::with('cuenta')
            ->when($filtro === 'facebook' || $filtro === 'instagram', fn ($w) => $w->where('plataforma', $filtro))
            ->when($filtro === 'sin_leer', fn ($w) => $w->where('no_leidos', '>', 0))
            ->when(isset(RedConversacion::ETIQUETAS[$filtro]), fn ($w) => $w->where('etiqueta', $filtro))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('contacto_nombre', 'like', "%$q%")->orWhere('contacto_usuario', 'like', "%$q%")->orWhere('ultimo_texto', 'like', "%$q%")))
            ->orderByDesc('ultimo_en')->limit(150)->get();

        return view('redes.index', [
            'cuentas' => $cuentas, 'convs' => $convs, 'filtro' => $filtro, 'q' => $q,
            'actual' => $request->integer('c') ?: null,
            'sinLeer' => RedConversacion::where('no_leidos', '>', 0)->count(),
            'tienda' => TiendaActual::tienda(),
        ]);
    }

    // Lista de chats en JSON (se refresca sola cada pocos segundos)
    public function lista(Request $request)
    {
        $filtro = $request->get('ver', 'todos');
        $q = trim((string) $request->get('q'));
        return response()->json(RedConversacion::query()
            ->when($filtro === 'facebook' || $filtro === 'instagram', fn ($w) => $w->where('plataforma', $filtro))
            ->when($filtro === 'sin_leer', fn ($w) => $w->where('no_leidos', '>', 0))
            ->when(isset(RedConversacion::ETIQUETAS[$filtro]), fn ($w) => $w->where('etiqueta', $filtro))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('contacto_nombre', 'like', "%$q%")->orWhere('contacto_usuario', 'like', "%$q%")->orWhere('ultimo_texto', 'like', "%$q%")))
            ->orderByDesc('ultimo_en')->limit(150)->get()->map(fn ($c) => $this->convJson($c)));
    }

    private function convJson(RedConversacion $c): array
    {
        return [
            'id' => $c->id, 'plataforma' => $c->plataforma, 'nombre' => $c->nombre, 'usuario' => $c->contacto_usuario, 'foto' => $c->contacto_foto,
            'texto' => $c->ultimo_texto, 'cuando' => $c->ultimo_en?->locale('es')->diffForHumans(null, true, true), 'no_leidos' => $c->no_leidos,
            'etiqueta' => $c->etiqueta, 'cliente_id' => $c->cliente_id, 'puede' => $c->puede_responder,
            'vence' => $c->ultimo_del_cliente_en ? $c->ultimo_del_cliente_en->copy()->addHours(24)->locale('es')->diffForHumans() : null,
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
            return response()->json(['message' => 'Pasaron más de 24 horas desde el último mensaje del cliente: Meta no deja responder por aquí. Escríbale por WhatsApp o espere a que vuelva a escribir.'], 422);
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

    public function sincronizar()
    {
        $n = 0; $err = []; $tid = TiendaActual::id();
        foreach ($this->cuentas() as $c) {
            try { $n += Meta::sincronizar($c); } catch (Throwable $e) { $err[] = $c->page_nombre . ': ' . $e->getMessage(); $c->update(['estado' => 'error', 'error' => mb_substr($e->getMessage(), 0, 250)]); }
        }
        TiendaActual::set($tid);
        return back()->with($err ? 'error' : 'success', $err ? implode(' · ', $err) : ($n ? "Se trajeron $n mensajes nuevos." : 'Todo al día.'));
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
    public function publicar()
    {
        $cuentas = $this->cuentas();
        if ($cuentas->isEmpty()) return redirect()->route('redes.index');
        return view('redes.publicar', [
            'cuentas' => $cuentas,
            'lista' => RedPublicacion::with('user:id,name', 'cuenta:id,page_nombre,ig_usuario')->latest()->limit(40)->get(),
            'tienda' => TiendaActual::tienda(),
        ]);
    }

    public function guardarPublicacion(Request $request)
    {
        $d = $request->validate([
            'cuenta_id' => 'required|integer',
            'texto' => 'nullable|string|max:2200',
            'imagen' => 'nullable|image|max:8192',
            'producto_id' => 'nullable|integer',
            'en_facebook' => 'nullable|boolean',
            'en_instagram' => 'nullable|boolean',
            'cuando' => 'nullable|in:ahora,programar',
            'programada_para' => 'nullable|date',
        ], ['imagen.image' => 'El archivo debe ser una foto (JPG o PNG).', 'imagen.max' => 'La foto pesa más de 8 MB.', 'texto.max' => 'El texto es muy largo (máximo 2.200 letras).']);
        $cuenta = RedCuenta::whereKey($d['cuenta_id'])->firstOrFail();
        $fb = (bool) ($d['en_facebook'] ?? false);
        $ig = (bool) ($d['en_instagram'] ?? false) && $cuenta->tiene_instagram;
        if (!$fb && !$ig) return back()->withInput()->withErrors('Elija dónde publicar: Facebook, Instagram o ambos.');

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

        $programar = ($d['cuando'] ?? 'ahora') === 'programar' && !empty($d['programada_para']);
        $cuandoProg = $programar ? \Illuminate\Support\Carbon::parse($d['programada_para']) : null;
        if ($programar && $cuandoProg->lt(now()->addMinutes(2))) return back()->withInput()->withErrors('La fecha programada debe ser en el futuro.');

        $p = RedPublicacion::create([
            'cuenta_id' => $cuenta->id, 'user_id' => Auth::id(), 'producto_id' => $producto?->id,
            'texto' => trim((string) ($d['texto'] ?? '')), 'imagen' => $imagen, 'en_facebook' => $fb, 'en_instagram' => $ig,
            'estado' => $programar ? 'programada' : 'pendiente', 'programada_para' => $cuandoProg,
        ]);
        if ($programar) return redirect()->route('redes.publicar')->with('success', 'Publicación programada para el ' . $cuandoProg->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') . '.');

        Meta::publicar($p);
        return redirect()->route('redes.publicar')->with($p->estado === 'error' ? 'error' : 'success',
            $p->estado === 'publicada' ? '¡Publicado! 🎉' : ($p->estado === 'parcial' ? 'Se publicó solo en una red: ' . $p->error : 'No se pudo publicar: ' . $p->error));
    }

    public function reintentar(RedPublicacion $publicacion)
    {
        if (in_array($publicacion->estado, ['error', 'parcial'], true)) Meta::publicar($publicacion);
        return back()->with($publicacion->estado === 'publicada' ? 'success' : 'error', $publicacion->estado === 'publicada' ? '¡Publicado!' : 'Sigue sin publicarse: ' . $publicacion->error);
    }

    public function borrarPublicacion(RedPublicacion $publicacion)
    {
        abort_unless(in_array($publicacion->estado, ['programada', 'error'], true), 403);
        if ($publicacion->imagen) Storage::disk('public')->delete($publicacion->imagen);
        $publicacion->delete();
        return back()->with('success', 'Publicación eliminada.');
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
        $state = Str::random(32);
        session(['meta_state' => $state, 'meta_tienda' => TiendaActual::id()]);
        return redirect()->away(Meta::urlLogin(route('redes.callback'), $state));
    }

    public function callback(Request $request)
    {
        if ($request->query('error') || !$request->query('code')) {
            return redirect()->route('redes.index')->withErrors('No se completó la conexión con Facebook. Inténtelo de nuevo y acepte todos los permisos.');
        }
        abort_unless(hash_equals((string) session('meta_state'), (string) $request->query('state')) && (int) session('meta_tienda') === (int) TiendaActual::id(), 403);
        try {
            $token = Meta::tokenDeUsuario((string) $request->query('code'), route('redes.callback'));
            $paginas = Meta::paginas($token);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('redes.index')->withErrors('Facebook respondió: ' . $e->getMessage());
        }
        session()->forget('meta_state');
        if (!$paginas) return redirect()->route('redes.index')->withErrors('Su cuenta de Facebook no administra ninguna página, o no la eligió al dar los permisos. Cree una página de su negocio en Facebook y vuelva a conectar.');
        session(['meta_paginas' => encrypt($paginas)]);
        return redirect()->route('redes.elegir');
    }

    public function elegir()
    {
        $paginas = session('meta_paginas') ? decrypt(session('meta_paginas')) : [];
        if (!$paginas) return redirect()->route('redes.index');
        return view('redes.elegir', ['paginas' => $paginas, 'ya' => RedCuenta::pluck('page_id')->all()]);
    }

    public function guardarPaginas(Request $request)
    {
        $paginas = collect(session('meta_paginas') ? decrypt(session('meta_paginas')) : [])->keyBy('id');
        $ids = collect((array) $request->input('paginas'))->filter(fn ($id) => $paginas->has($id));
        if ($ids->isEmpty()) return back()->withErrors('Elija al menos una página.');
        $n = 0; $avisos = [];
        foreach ($ids as $id) {
            $p = $paginas[$id];
            $c = RedCuenta::updateOrCreate(['page_id' => $p['id']], [
                'page_nombre' => $p['nombre'], 'page_foto' => $p['foto'], 'page_token' => $p['token'],
                'ig_id' => $p['ig_id'], 'ig_usuario' => $p['ig_usuario'], 'ig_foto' => $p['ig_foto'],
                'conectado_por' => Auth::id(), 'estado' => 'activa', 'error' => null,
            ]);
            try { Meta::suscribir($c); } catch (Throwable $e) { $avisos[] = $p['nombre'] . ': no se activaron los mensajes en tiempo real (' . $e->getMessage() . ')'; }
            try { Meta::sincronizar($c); } catch (Throwable $e) { report($e); }
            $n++;
        }
        session()->forget('meta_paginas');
        return redirect()->route('redes.index')->with('success', '¡Listo! ' . ($n === 1 ? 'Se conectó 1 página' : "Se conectaron $n páginas") . '.' . ($avisos ? ' ' . implode(' · ', $avisos) : ''));
    }

    public function desconectar(RedCuenta $cuenta)
    {
        Meta::desuscribir($cuenta);
        $cuenta->update(['estado' => 'desconectada', 'page_token' => '-']);
        return redirect()->route('redes.cuentas')->with('success', 'Se desconectó ' . $cuenta->page_nombre . '. Los chats guardados se conservan.');
    }

    public function cuentasVista()
    {
        return view('redes.cuentas', ['cuentas' => RedCuenta::orderByRaw("estado = 'desconectada'")->orderBy('page_nombre')->get(), 'activo' => Meta::activo()]);
    }
}
