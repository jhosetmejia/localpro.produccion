<?php

namespace App\Http\Controllers;

use App\Models\LpchatUsuario as U;
use App\Support\LpChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * 2026-11-17 (Jhoset): cuentas de LP Chat. Se entra con correo o celular + clave.
 * Los usuarios de LocalProo entran solos; los de PFN/NES llegan con un enlace firmado (/chat/conectar/{origen}).
 */
class lpchatCuentaController extends Controller
{
    private function guard()
    {
        return Auth::guard('lpchat');
    }

    /** Si ya tiene sesión de LocalProo, le crea/vincula su cuenta de LP Chat sin pedir nada. */
    public static function desdeLocalProo(): ?U
    {
        $w = Auth::guard('web')->user();
        if (! $w) {
            return null;
        }
        $u = self::cuentaDeUsuarioWeb($w);
        Auth::guard('lpchat')->login($u, true);

        return $u;
    }

    /** Cuenta de LP Chat de un usuario de LocalProo (la crea o la vincula). */
    public static function cuentaDeUsuarioWeb($w): U
    {
        $u = U::where('user_id', $w->id)->first() ?: ($w->email ? U::where('email', mb_strtolower($w->email))->first() : null);
        if (! $u) {
            $cel = U::normalizarCelular($w->telefono ?? null);
            $u = U::create([
                'nombre' => mb_substr($w->name ?: 'Usuario', 0, 80),
                'email' => $w->email ? mb_strtolower($w->email) : null,
                'celular' => $cel && ! U::where('celular', $cel)->exists() ? $cel : null,
                'user_id' => $w->id,
                'origen' => 'localproo',
            ]);
        } elseif (! $u->user_id) {
            $u->user_id = $w->id;
            $u->save();
        }

        return $u;
    }

    /**
     * 2026-11-25 (Jhoset): se entra a LP Chat con los MISMOS accesos de LocalProo, PFN o NES (correo/celular y clave).
     * Primero la clave propia de LP Chat; si no, la de LocalProo; si no, se le pregunta a PFN y NES (servidor a servidor, firmado).
     */
    private function cuentaConAccesosDeOtroSistema(string $identificador, string $clave): ?U
    {
        $id = U::identificador($identificador);
        if (! $id) {
            return null;
        }
        if ($id[0] === 'email') {
            try {
                $w = \App\Models\User::whereRaw('LOWER(email) = ?', [$id[1]])->first();
                if ($w && $w->password && Hash::check($clave, $w->password)) {
                    return self::cuentaDeUsuarioWeb($w);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
        foreach ((array) config('lpchat.origenes') as $origen => $cfg) {
            if (empty($cfg['secreto']) || empty($cfg['volver'])) {
                continue;
            }
            $cuerpo = json_encode(['ts' => time(), 'clave' => $clave] + ($id[0] === 'email' ? ['email' => $id[1]] : ['celular' => $id[1]]), JSON_UNESCAPED_UNICODE);
            try {
                $r = \Illuminate\Support\Facades\Http::timeout(8)->withHeaders(['X-LPChat-Firma' => hash_hmac('sha256', $cuerpo, $cfg['secreto']), 'Accept' => 'application/json'])
                    ->withBody($cuerpo, 'application/json')->post(rtrim($cfg['volver'], '/').'/api/lpchat/verificar');
                if ($r->successful() && $r->json('ok') && is_array($r->json('perfil'))) {
                    $u = LpChat::desdeOrigen($origen, $r->json('perfil'));
                    if ($u) {
                        return $u;
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('LP Chat entrar con '.$origen.': '.$e->getMessage());
            }
        }

        return null;
    }

    public function entrar(Request $request)
    {
        if ($this->guard()->check() || self::desdeLocalProo()) {
            return redirect()->to($this->destino($request));
        }

        return view('lpchat.entrar', ['volver' => $this->destinoSeguro($request->query('volver'))]);
    }

    public function login(Request $request)
    {
        $d = $request->validate(['identificador' => 'required|string|max:150', 'password' => 'required|string|max:100']);
        $u = U::buscarPorIdentificador($d['identificador']);
        if (! $u || ! $u->password || ! Hash::check($d['password'], $u->password)) {
            $u = $this->cuentaConAccesosDeOtroSistema($d['identificador'], $d['password']);
        }
        if (! $u) {
            return back()->withInput($request->only('identificador'))->withErrors(['identificador' => 'El correo/celular o la clave no coinciden. Puedes entrar con los mismos datos de LocalProo, Pasto Fight Night o Nariño ExpoShow.']);
        }
        $this->guard()->login($u, true);
        $request->session()->regenerate();

        return redirect()->to($this->destino($request));
    }

    public function registro(Request $request)
    {
        $d = $request->validate([
            'nombre' => 'required|string|min:2|max:80',
            'identificador' => 'required|string|max:150',
            'password' => 'required|string|min:6|max:100',
            'mayor' => 'accepted',
        ], ['mayor.accepted' => 'LP Chat es para mayores de 18 años.']);
        $id = U::identificador($d['identificador']);
        if (! $id) {
            return back()->withInput($request->except('password'))->withErrors(['identificador' => 'Escribe un correo o un número de celular válido.'])->with('tab', 'registro');
        }
        if (U::where($id[0], $id[1])->exists()) {
            return back()->withInput($request->except('password'))->withErrors(['identificador' => 'Ya hay una cuenta con ese '.($id[0] === 'email' ? 'correo' : 'celular').'. Entra con tu clave.'])->with('tab', 'registro');
        }
        $u = U::create(['nombre' => trim($d['nombre']), $id[0] => $id[1], 'password' => Hash::make($d['password'])]);
        $this->guard()->login($u, true);
        $request->session()->regenerate();

        return redirect()->to($this->destino($request));
    }

    public function salir(Request $request)
    {
        $this->guard()->logout();

        return redirect()->route('lpchat.entrar');
    }

    /**
     * Enlace firmado desde PFN/NES: /chat/conectar/pfn?d={base64url(json)}&f={hmac}
     * json: id, nombre, email, celular, foto (https), etiqueta, bio, ciudad, ts, abrir (opcional: id del otro en ese sistema), menor
     */
    public function conectar(Request $request, string $origen)
    {
        $d = (string) $request->query('d', '');
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $d, $request->query('f')), 403, 'Enlace no válido.');
        $p = json_decode(base64_decode(strtr($d, '-_', '+/')), true);
        abort_unless(is_array($p) && abs(time() - (int) ($p['ts'] ?? 0)) < 900, 403, 'El enlace venció. Vuelve a intentarlo.');
        // 2026-11-17 (Jhoset): las cuentas con inscripciones de menores también entran (las maneja el papá/mamá),
        // protegidas: fuera de Conocer gente y solo hablan con el equipo del evento (LpChat::puedeContactar).
        $u = LpChat::desdeOrigen($origen, $p);
        abort_unless($u, 422, 'Faltan datos de la cuenta.');
        $this->guard()->login($u, true);
        $request->session()->regenerate();
        $destino = route('lpchat.index');
        if (! empty($p['abrir'])) {
            // Abrir de una el chat con alguien de ese mismo sistema (ej. un peleador que buscó en PFN, o Edgar del equipo de NES).
            $uid = \DB::table('lpchat_vinculos')->where('origen', $origen)->where('origen_id', (string) $p['abrir'])->value('usuario_id');
            if (! $uid && is_array($p['abrir_perfil'] ?? null)) {
                // Aún no había entrado a LP Chat: se crea su cuenta con lo que manda el sistema (sin correo ni celular).
                $uid = LpChat::desdeOrigen($origen, ['id' => (string) $p['abrir']] + $p['abrir_perfil'])?->id;
            }
            if ($uid && $uid !== $u->id && ($otro = U::find($uid)) && ! LpChat::bloqueado($u->id, $otro->id) && LpChat::puedeContactar($u, $otro)) {
                if ($otro->sin_limite) {
                    // Equipo del evento (atención a la gente): directo a Mis chats, sin solicitud ni límite.
                    $destino = route('lpchat.index', ['chat' => LpChat::abrirDirecto($u, $otro, 'contacto')]);
                } elseif (LpChat::directo($u->id, $otro->id) || LpChat::contactosHoy($u, 'conocer') < LpChat::LIMITE_CONOCER) {
                    $destino = route('lpchat.index', ['chat' => LpChat::abrirDirecto($u, $otro, 'conocer')]);
                }
            }
        }

        // 2026-11-27 (Jhoset): el sistema puede mandar un mensaje que queda escrito (sin enviar) en la caja del chat.
        if ($destino !== route('lpchat.index') && is_string($p['texto'] ?? null) && trim($p['texto']) !== '') {
            $destino .= (str_contains($destino, '?') ? '&' : '?').'texto='.rawurlencode(mb_substr(trim($p['texto']), 0, 500));
        }

        return redirect()->to($destino);
    }

    /** Servidor a servidor: PFN/NES mandan sus perfiles públicos para "Conocer gente". Firma en X-LPChat-Firma. */
    public function sincronizar(Request $request, string $origen)
    {
        $cuerpo = $request->getContent();
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $cuerpo, $request->header('X-LPChat-Firma')), 403);
        $datos = json_decode($cuerpo, true);
        $n = 0;
        foreach (array_slice((array) ($datos['perfiles'] ?? []), 0, 2000) as $p) {
            if (is_array($p) && empty($p['menor']) && LpChat::desdeOrigen($origen, $p)) { // menores nunca en Conocer gente
                $n++;
            }
        }

        return response()->json(['ok' => true, 'perfiles' => $n]);
    }

    /**
     * 2026-11-25 (Jhoset): servidor a servidor: PFN/NES preguntan el usuario único (@) de sus personas. LocalPro es el que
     * manda: el @ es uno solo por persona en LP Chat, PFN y NES. Si traen "quiere" (el @ que la persona escogió allá),
     * se le pone si está libre; si lo tiene otra persona, vuelve con error y se queda con el que tenía.
     * Cuerpo: {"personas":[{id, nombre, email, celular, foto, etiqueta, ciudad, menor, quiere?}]} · Firma en X-LPChat-Firma.
     */
    public function usuarios(Request $request, string $origen)
    {
        $cuerpo = $request->getContent();
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $cuerpo, $request->header('X-LPChat-Firma')), 403);
        $d = json_decode($cuerpo, true) ?: [];
        $out = [];
        foreach (array_slice((array) ($d['personas'] ?? []), 0, 1000) as $p) {
            if (! is_array($p)) {
                continue;
            }
            $p['conocer'] = $p['conocer'] ?? false; // por esto no entra nadie nuevo a "Conocer gente"
            try {
                $u = LpChat::desdeOrigen($origen, $p);
            } catch (\Throwable $e) {
                report($e);
                $u = null;
            }
            if (! $u) {
                continue;
            }
            if (! $u->usuario) {
                $u->usuario = U::usuarioLibre((string) $u->nombre, $u->id, $u->tienda_id ? (int) $u->tienda_id : null);
                $u->save();
            }
            $r = [];
            $quiere = isset($p['quiere']) ? strtolower(trim((string) $p['quiere'])) : '';
            if ($quiere !== '' && $quiere !== $u->usuario) {
                if (U::usuarioDisponible($quiere, $u->id, $u->tienda_id ? (int) $u->tienda_id : null)) {
                    $u->usuario = $quiere;
                    $u->save();
                } else {
                    $r['error'] = 'Esa dirección ya la tiene otra persona, escoge otra.';
                }
            }
            $r['usuario'] = $u->usuario;
            $out[(string) $p['id']] = $r;
        }

        return response()->json(['ok' => true, 'usuarios' => $out]);
    }

    /** 2026-11-18: servidor a servidor: NES/PFN mandan sus mensajes (los chats que ya tenían y los nuevos). */
    public function importar(Request $request, string $origen)
    {
        $cuerpo = $request->getContent();
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $cuerpo, $request->header('X-LPChat-Firma')), 403);
        $d = json_decode($cuerpo, true) ?: [];

        $admin = collect((array) ($d['usuarios'] ?? []))->contains(fn ($p) => is_array($p) && ! empty($p['admin']));

        return response()->json(['ok' => true, 'importados' => LpChat::importar($origen, (array) ($d['usuarios'] ?? []), (array) ($d['mensajes'] ?? [])),
            'admin_unido' => $admin && LpChat::cuentaAdmin() !== null]);
    }

    /** 2026-11-18: servidor a servidor: lo que se escribió en LP Chat entre personas de NES/PFN, para que les llegue allá. Firma = hmac(desde). */
    public function exportar(Request $request, string $origen)
    {
        $desde = (string) (int) $request->query('desde', 0);
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $desde, $request->query('f')), 403);
        [$mensajes, $ultimo] = LpChat::exportar($origen, (int) $desde);

        // 2026-11-18: en la primera tanda van también los mensajes de allá que ya se leyeron aquí
        $leidos = $request->boolean('leidos') ? LpChat::leidosAqui($origen) : [];

        return response()->json(['ok' => true, 'mensajes' => $mensajes, 'ultimo' => $ultimo, 'leidos' => $leidos]);
    }

    /** Cuántos mensajes sin leer tiene alguien de PFN/NES (para su campanita). Firma = hmac(origen_id). */
    public function noLeidos(Request $request, string $origen)
    {
        $oid = (string) $request->query('id', '');
        abort_unless(config('lpchat.origenes.'.$origen) && LpChat::firmaValida($origen, $oid, $request->query('f')), 403);
        $uid = \DB::table('lpchat_vinculos')->where('origen', $origen)->where('origen_id', $oid)->value('usuario_id');
        $u = $uid ? U::find($uid) : null;

        return response()->json(['ok' => true, 'sin_leer' => $u ? LpChat::sinLeerTotal($u) : 0, 'solicitudes' => $u ? LpChat::solicitudesTotal($u) : 0]);
    }

    private function destino(Request $request): string
    {
        return $this->destinoSeguro($request->input('volver', $request->query('volver'))) ?: route('lpchat.index');
    }

    /** Solo deja volver a páginas del mismo chat (evita redirecciones a otros sitios). */
    private function destinoSeguro(?string $v): ?string
    {
        return $v && str_starts_with($v, '/chat') && ! str_starts_with($v, '//') ? $v : null;
    }
}
