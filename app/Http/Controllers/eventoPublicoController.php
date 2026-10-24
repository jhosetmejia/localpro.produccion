<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\EventoAsistente;
use App\Models\EventoEntrada;
use App\Models\Tienda;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * LocalProo Eventos (lo que ve el público):
 * landing del evento → "Comprar entrada" → registro con clave → pagar por Nequi y mandar el comprobante por WhatsApp
 * → el negocio aprueba → "Mis entradas" muestra el QR (y se descarga en PDF).
 * Los compradores entran con el guard "asistente" (no son usuarios del negocio).
 */
class eventoPublicoController extends Controller
{
    private function guard()
    {
        return Auth::guard('asistente');
    }

    private function yo(): ?EventoAsistente
    {
        return $this->guard()->user();
    }

    // Evento por su dirección. Los borradores solo los ve el negocio dueño (vista previa).
    private function evento(string $slug): Evento
    {
        $evento = Evento::withoutGlobalScope('tienda')->where('slug', $slug)->firstOrFail();
        $tienda = Tienda::find($evento->tienda_id);
        abort_unless($tienda && $tienda->activa && $tienda->mod_eventos, 404);
        if ($evento->estado === 'borrador') {
            abort_unless(auth()->check() && (int) TiendaActual::id() === (int) $evento->tienda_id, 404);
        }
        $evento->setRelation('tienda', $tienda);
        return $evento;
    }

    private function entradaDe(Evento $evento, ?EventoAsistente $a): ?EventoEntrada
    {
        if (!$a) return null;
        return EventoEntrada::withoutGlobalScope('tienda')->where('evento_id', $evento->id)->where('asistente_id', $a->id)
            ->orderByRaw('titular_documento IS NOT NULL')->latest('id')->first();
    }

    public function show(Request $request, string $slug)
    {
        $evento = $this->evento($slug);
        // Visitas: una por persona (sesión) y por evento; no cuentan robots ni el equipo del negocio con sesión abierta
        $clave = 'ev_visto_' . $evento->id;
        $robot = preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|headless/i', (string) $request->userAgent());
        if (!$robot && !Auth::guard('web')->check() && !$request->session()->has($clave)) {
            $request->session()->put($clave, 1);
            DB::table('eventos')->where('id', $evento->id)->increment('visitas');
            $evento->visitas = (int) $evento->visitas + 1;
        }
        return view('eventos.publico.landing', [
            'evento' => $evento, 'tienda' => $evento->tienda, 'yo' => $this->yo(),
            'entrada' => $this->entradaDe($evento, $this->yo()), 'motivo' => $evento->motivoSinVenta(),
        ]);
    }

    public function comprar(string $slug)
    {
        $evento = $this->evento($slug);
        $yo = $this->yo();
        if ($e = $this->entradaDe($evento, $yo)) {
            return redirect()->route($e->estado === 'aprobada' ? 'mis-entradas.ver' : 'evento.pago', $e->estado === 'aprobada' ? $e->uuid : $evento->slug);
        }
        if ($m = $evento->motivoSinVenta()) {
            return redirect()->route('evento.publico', $evento->slug)->with('error', $m);
        }
        return view('eventos.publico.registro', ['evento' => $evento, 'tienda' => $evento->tienda, 'yo' => $yo]);
    }

    public function registrar(Request $request, string $slug)
    {
        $evento = $this->evento($slug);
        if ($m = $evento->motivoSinVenta()) {
            return redirect()->route('evento.publico', $evento->slug)->with('error', $m);
        }

        $yo = $this->yo();
        $pideNac = $evento->pedir_nacimiento || $evento->edad_minima;
        if (!$yo) {
            $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
            $datos = $request->validate([
                'nombre' => 'required|string|min:5|max:120',
                'email' => 'required|email|max:150',
                'tipo_documento' => ['required', Rule::in(array_keys(EventoAsistente::DOCUMENTOS))],
                'documento' => ['required', 'string', 'max:30', 'regex:/^[0-9A-Za-z]{5,20}$/'],
                'fecha_nacimiento' => [$pideNac ? 'required' : 'nullable', 'date', 'before:today', 'after:1920-01-01'],
                'celular' => [$evento->pedir_celular ? 'required' : 'nullable', 'string', 'regex:/^[0-9+ ]{7,20}$/'],
                'password' => 'required|string|min:6|max:60|confirmed',
                'acepta_datos' => 'accepted',
            ] + $evento->reglasCampos(), [
                'nombre.min' => 'Escriba su nombre completo.',
                'documento.regex' => 'Escriba el número de documento sin puntos ni espacios.',
                'fecha_nacimiento.before' => 'Revise la fecha de nacimiento.',
                'celular.regex' => 'Escriba su número de celular (solo números).',
                'password.min' => 'La clave debe tener mínimo 6 caracteres.',
                'password.confirmed' => 'Las dos claves no coinciden.',
                'acepta_datos.accepted' => 'Debe autorizar el tratamiento de sus datos para registrarse.',
            ], ['documento' => 'número de documento', 'fecha_nacimiento' => 'fecha de nacimiento'] + $evento->nombresCampos());

            $edad = !empty($datos['fecha_nacimiento']) ? Carbon::parse($datos['fecha_nacimiento'])->age : null;
            if ($edad !== null && $edad < 18 && !$request->boolean('autoriza_acudiente')) {
                throw ValidationException::withMessages(['autoriza_acudiente' => 'Como es menor de edad, su padre, madre o acudiente debe autorizar el registro (marque la casilla).']);
            }
            if ($evento->edad_minima && $edad !== null && $edad < $evento->edad_minima) {
                throw ValidationException::withMessages(['fecha_nacimiento' => "Este evento es para mayores de {$evento->edad_minima} años."]);
            }
            if (EventoAsistente::where('email', $datos['email'])->exists()) {
                throw ValidationException::withMessages(['email' => 'Ya hay una cuenta con ese correo. Toque "Ya tengo cuenta" e ingrese con su clave.']);
            }
        } else {
            if ($evento->edad_minima && $yo->edad !== null && $yo->edad < $evento->edad_minima) {
                return back()->with('error', "Este evento es para mayores de {$evento->edad_minima} años.");
            }
            $datos = $request->validate($evento->reglasCampos(), [], $evento->nombresCampos());
        }
        // Varias entradas en una compra: cada acompañante con su nombre y documento (cada uno recibe su propio QR)
        $max = max(1, min(20, (int) ($evento->max_por_compra ?: 1)));
        $cantidad = max(1, min($max, (int) $request->input('cantidad', 1)));
        $acomp = [];
        if ($cantidad > 1) {
            $reglas = []; $nombres = [];
            for ($i = 2; $i <= $cantidad; $i++) {
                $reglas["acompanantes.$i.nombre"] = 'required|string|min:5|max:120';
                $reglas["acompanantes.$i.tipo_documento"] = ['required', Rule::in(array_keys(EventoAsistente::DOCUMENTOS))];
                $reglas["acompanantes.$i.documento"] = ['required', 'string', 'max:30', 'regex:/^[0-9A-Za-z]{5,20}$/'];
                $reglas["acompanantes.$i.email"] = 'nullable|email|max:150';
                $nombres["acompanantes.$i.nombre"] = "nombre de la persona $i";
                $nombres["acompanantes.$i.documento"] = "documento de la persona $i";
                $nombres["acompanantes.$i.email"] = "correo de la persona $i";
            }
            $v = $request->validate($reglas, ['regex' => 'Escriba el :attribute sin puntos ni espacios.'], $nombres);
            $docs = [strtoupper((string) ($yo?->documento ?? $request->input('documento')))];
            for ($i = 2; $i <= $cantidad; $i++) {
                $a = $v['acompanantes'][$i];
                $doc = strtoupper($a['documento']);
                if (in_array($doc, $docs, true)) {
                    throw ValidationException::withMessages(["acompanantes.$i.documento" => "La persona $i tiene el mismo documento que otra de la compra: cada entrada es de una persona distinta."]);
                }
                $docs[] = $doc;
                $acomp[] = ['nombre' => trim($a['nombre']), 'tipo_documento' => $a['tipo_documento'], 'documento' => $doc, 'email' => !empty($a['email']) ? mb_strtolower(trim($a['email'])) : null];
            }
        }

        // Respuestas a los campos propios del organizador
        $respuestas = [];
        foreach ($evento->camposFormulario() as $c) {
            $v = $datos['respuestas'][$c['id']] ?? null;
            if ($v !== null && $v !== '') $respuestas[$c['id']] = is_string($v) ? trim($v) : $v;
        }

        $entrada = DB::transaction(function () use ($evento, $yo, $request, &$datos, $respuestas, $cantidad, $acomp) {
            // Bloquea el evento para que el cupo no se pase si se registran muchos a la vez
            $ev = Evento::withoutGlobalScope('tienda')->lockForUpdate()->find($evento->id);
            if ($ev->cupo) {
                $quedan = $ev->cupo - $ev->entradas()->whereIn('estado', ['pendiente', 'aprobada'])->count();
                if ($quedan <= 0) throw ValidationException::withMessages(['nombre' => 'Lo sentimos: las entradas se acaban de agotar.']);
                if ($quedan < $cantidad) throw ValidationException::withMessages(['cantidad' => "Solo quedan {$quedan} entradas: elija una cantidad menor."]);
            }
            if (!$yo) {
                $yo = EventoAsistente::create([
                    'nombre' => trim($datos['nombre']), 'email' => $datos['email'], 'password' => $datos['password'],
                    'tipo_documento' => $datos['tipo_documento'], 'documento' => strtoupper($datos['documento']),
                    'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                    'celular' => !empty($datos['celular']) ? preg_replace('/\D/', '', $datos['celular']) : null, 'acepta_datos_at' => now(),
                    'autoriza_acudiente' => $request->boolean('autoriza_acudiente'),
                ]);
            }
            // El precio de cada entrada sale de la promoción que aplique (el servidor lo calcula, no el navegador)
            $cot = $ev->cotizar($cantidad);
            $grupo = $cantidad > 1 ? (string) \Illuminate\Support\Str::uuid() : null;
            $primera = null;
            for ($i = 0; $i < $cantidad; $i++) {
                $a = $i === 0 ? null : $acomp[$i - 1];
                $e = new EventoEntrada([
                    'evento_id' => $ev->id, 'asistente_id' => $yo->id, 'precio' => $cot['precios'][$i], 'etapa' => $ev->etapa, 'estado' => 'pendiente',
                    'respuestas' => $i === 0 ? ($respuestas ?: null) : null, 'grupo' => $grupo,
                    'precio_lista' => $cot['promo'] ? $ev->precio : null, 'promo' => $cot['promo'],
                    'titular_nombre' => $a['nombre'] ?? null, 'titular_tipo_documento' => $a['tipo_documento'] ?? null,
                    'titular_documento' => $a['documento'] ?? null, 'titular_email' => $a['email'] ?? null,
                ]);
                $e->tienda_id = $ev->tienda_id;
                $e->save();
                $primera ??= $e;
            }
            return $primera;
        });

        if (!$this->yo()) {
            $this->guard()->login($entrada->asistente, true);
            $request->session()->regenerate();
        }
        return redirect()->route('evento.pago', $evento->slug)->with('success', 'Registro terminado. Ahora haga el pago y mande el comprobante.');
    }

    public function pago(string $slug)
    {
        $evento = $this->evento($slug);
        $yo = $this->yo();
        if (!$yo) return redirect()->route('mis-entradas.ingresar', ['e' => $evento->slug]);
        $entrada = $this->entradaDe($evento, $yo);
        if (!$entrada) return redirect()->route('evento.comprar', $evento->slug);
        if (in_array($entrada->estado, ['aprobada', 'anulada'], true)) return redirect()->route('mis-entradas.ver', $entrada->uuid);
        $grupo = $entrada->delGrupo()->orderBy('id')->get();
        return view('eventos.publico.pago', ['evento' => $evento, 'tienda' => $evento->tienda, 'yo' => $yo, 'entrada' => $entrada, 'grupo' => $grupo]);
    }

    // "Enviar comprobante de pago": queda la constancia de que avisó y se abre el WhatsApp con el mensaje listo
    public function aviso(string $slug)
    {
        $evento = $this->evento($slug);
        $yo = $this->yo();
        $entrada = $this->entradaDe($evento, $yo);
        abort_unless($entrada && $evento->whatsapp_link, 404);
        if (!$entrada->aviso_pago_at) $entrada->delGrupo()->whereNull('aviso_pago_at')->update(['aviso_pago_at' => now()]);
        $grupo = $entrada->delGrupo()->orderBy('id')->get();
        $texto = ($evento->mensaje_whatsapp ?: Evento::MENSAJE)
            . "\n\nEvento: {$evento->nombre}"
            . "\nNombre: {$yo->nombre}"
            . "\nDocumento: {$yo->tipo_documento} {$yo->documento}"
            . "\nCorreo: {$yo->email}"
            . "\nRegistro N.° {$entrada->id}"
            . ($grupo->count() > 1 ? "\nEntradas: {$grupo->count()} · Total: " . $evento->plata($grupo->sum('precio')) . ($entrada->promo ? " ({$entrada->promo})" : '') : '');
        return redirect()->away($evento->whatsapp_link . '?text=' . rawurlencode($texto));
    }

    // ===================== Mis entradas =====================

    public function ingresar(Request $request)
    {
        if ($this->yo()) return redirect()->route('mis-entradas.index');
        $evento = $request->query('e') ? Evento::withoutGlobalScope('tienda')->where('slug', $request->query('e'))->where('estado', '!=', 'borrador')->first() : null;
        return view('eventos.publico.ingresar', ['evento' => $evento]);
    }

    public function login(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (!$this->guard()->attempt($cred, true)) {
            throw ValidationException::withMessages(['email' => 'Correo o clave incorrectos. Si olvidó la clave, pídale al organizador que se la restablezca.']);
        }
        $request->session()->regenerate();
        if ($slug = $request->input('e')) {
            $ev = Evento::withoutGlobalScope('tienda')->where('slug', $slug)->first();
            if ($ev) return redirect()->route('evento.comprar', $ev->slug);
        }
        return redirect()->route('mis-entradas.index');
    }

    public function salir(Request $request)
    {
        $this->guard()->logout();
        $request->session()->regenerateToken();
        // "Comprar otra entrada": cierra la sesión de este celular y abre el registro del evento para la otra persona
        $volver = (string) $request->input('volver', '');
        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $volver) && Evento::where('slug', $volver)->exists()) {
            return redirect()->route('evento.comprar', $volver)
                ->with('success', 'Listo. Ahora registre los datos de la otra persona: su entrada queda a su nombre con su propio QR. La suya sigue guardada; entra de nuevo con su documento y clave.');
        }
        return redirect()->route('mis-entradas.ingresar')->with('success', 'Cerró sesión.');
    }

    public function index()
    {
        $yo = $this->yo();
        if (!$yo) return redirect()->route('mis-entradas.ingresar');
        $entradas = EventoEntrada::withoutGlobalScope('tienda')->with('evento')->where('asistente_id', $yo->id)->latest('id')->get();
        return view('eventos.publico.mis-entradas', ['yo' => $yo, 'entradas' => $entradas]);
    }

    public function ver(string $uuid)
    {
        $yo = $this->yo();
        if (!$yo) return redirect()->route('mis-entradas.ingresar');
        $entrada = EventoEntrada::withoutGlobalScope('tienda')->with('evento')->where('uuid', $uuid)->where('asistente_id', $yo->id)->firstOrFail();
        $tienda = Tienda::find($entrada->tienda_id);
        return view('eventos.publico.entrada', ['yo' => $yo, 'entrada' => $entrada, 'evento' => $entrada->evento, 'tienda' => $tienda]);
    }
}
