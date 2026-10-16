<?php

namespace App\Http\Controllers;

use App\Mail\ProspectoMail;
use App\Models\Ajuste;
use App\Models\Prospecto;
use App\Models\ProspectoEnvio;
use App\Models\Tienda;
use App\Support\Prospectos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

// Administrador: prospección automática (firmas contables) — ajustes del envío diario, lista y resultados
class adminProspectosController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado');
        $q = trim((string) $request->query('q'));
        $lista = Prospecto::query()
            ->when($estado && isset(Prospecto::ESTADOS[$estado]), fn ($x) => $x->where('estado', $estado))
            ->when($q !== '', fn ($x) => $x->where(fn ($y) => $y->where('empresa', 'like', "%$q%")->orWhere('correo', 'like', "%$q%")->orWhere('ciudad', 'like', "%$q%")))
            ->orderByDesc('updated_at')->paginate(50)->withQueryString();

        $porEstado = Prospecto::selectRaw('estado, count(*) n')->groupBy('estado')->pluck('n', 'estado');
        return view('admin.prospectos', [
            'lista' => $lista, 'estado' => $estado, 'q' => $q, 'porEstado' => $porEstado,
            'kpi' => [
                'hoy' => ProspectoEnvio::whereDate('created_at', today())->where('estado', 'enviado')->count(),
                'semana' => ProspectoEnvio::where('created_at', '>=', now()->startOfWeek())->where('estado', 'enviado')->count(),
                'total' => ProspectoEnvio::where('estado', 'enviado')->count(),
                'registrados' => Tienda::where('origen', 'like', 'correo:p%')->count(),
                'fallidos' => ProspectoEnvio::whereDate('created_at', today())->where('estado', 'fallido')->count(),
            ],
            'ultimoError' => ProspectoEnvio::where('estado', 'fallido')->latest()->first(),
            'cfg' => [
                'activo' => Prospectos::activo(), 'limite' => Prospectos::limiteDiario(),
                'smtp_host' => Prospectos::ajuste('smtp_host', 'smtp.gmail.com'), 'smtp_puerto' => Prospectos::ajuste('smtp_puerto', 587),
                'smtp_usuario' => Prospectos::ajuste('smtp_usuario'), 'tieneClave' => (bool) Prospectos::secreto('smtp_clave'),
                'remitente' => Prospectos::ajuste('remitente', 'Jhoset Mejía · LocalProo'),
                'tieneMaps' => (bool) Prospectos::secreto('maps_clave'), 'ciudades' => implode("\n", Prospectos::ciudades()),
                'imap' => function_exists('imap_open'),
            ],
        ]);
    }

    public function guardar(Request $request)
    {
        $d = $request->validate([
            'limite' => 'required|integer|min:1|max:200',
            'smtp_host' => 'required|string|max:120',
            'smtp_puerto' => 'required|in:465,587',
            'smtp_usuario' => 'nullable|email|max:150',
            'smtp_clave' => 'nullable|string|max:200',
            'remitente' => 'required|string|max:80',
            'maps_clave' => 'nullable|string|max:200',
            'ciudades' => 'nullable|string|max:5000',
        ]);
        foreach (['limite', 'smtp_host', 'smtp_puerto', 'smtp_usuario', 'remitente', 'ciudades'] as $k) {
            Ajuste::guardar('prosp_' . $k, $d[$k] ?? null);
        }
        Ajuste::guardar('prosp_activo', $request->boolean('activo') ? '1' : '');
        // Las claves de aplicación de Gmail se muestran con espacios: se guardan sin ellos
        if ($request->filled('smtp_clave')) Ajuste::guardar('prosp_smtp_clave', Crypt::encryptString(str_replace(' ', '', $request->smtp_clave)));
        if ($request->filled('maps_clave')) Ajuste::guardar('prosp_maps_clave', Crypt::encryptString(trim($request->maps_clave)));
        $aviso = $request->boolean('activo') && !Prospectos::buzonListo() ? ' Ojo: falta el correo y la clave de aplicación, sin eso no sale nada.' : '';
        return back()->with('success', 'Ajustes de prospección guardados.' . $aviso);
    }

    // Agregar a mano: una línea por prospecto. Acepta "empresa; correo-o-web; ciudad", solo la web o solo el correo.
    public function agregar(Request $request)
    {
        $request->validate(['lineas' => 'required|string|max:50000']);
        $nuevos = $repetidos = 0;
        foreach (preg_split('/\r\n|\r|\n/', $request->lineas) as $linea) {
            $partes = array_values(array_filter(array_map('trim', preg_split('/[;\t|]/', $linea)), fn ($x) => $x !== ''));
            if (!$partes) continue;
            $correo = $web = $empresa = $ciudad = null;
            foreach ($partes as $x) {
                if (filter_var($x, FILTER_VALIDATE_EMAIL)) $correo = strtolower($x);
                elseif (preg_match('#^(https?://)?[\w\-]+(\.[\w\-]+)+(/\S*)?$#i', $x) && !$web) $web = Prospectos::normalizarWeb($x);
                elseif (!$empresa) $empresa = mb_substr($x, 0, 150);
                elseif (!$ciudad) $ciudad = mb_substr($x, 0, 80);
            }
            if (!$correo && !$web) continue;
            if (($correo && Prospecto::where('correo', $correo)->exists()) || ($web && Prospecto::where('web', $web)->exists())) {
                $repetidos++;
                continue;
            }
            Prospecto::create([
                'empresa' => $empresa ?: ($web ? Prospectos::host($web) : explode('@', $correo)[1]),
                'correo' => $correo, 'web' => $web, 'ciudad' => $ciudad,
                'fuente' => $correo ? 'Agregado a mano por el administrador' : null,
                'estado' => $correo ? 'nuevo' : 'sin_correo',
            ]);
            $nuevos++;
        }
        return back()->with('success', "$nuevos prospectos agregados" . ($repetidos ? " · $repetidos ya estaban" : '') . '. Los que solo tienen página web se revisan para sacar el correo.');
    }

    public function estado(Request $request, Prospecto $prospecto)
    {
        $request->validate(['estado' => 'required|in:' . implode(',', array_keys(Prospecto::ESTADOS))]);
        $cambio = ['estado' => $request->estado];
        if (in_array($request->estado, Prospecto::CERRADOS, true)) $cambio['proximo_envio'] = null;
        if ($request->estado === 'nuevo') $cambio += ['paso' => 0];
        $prospecto->update($cambio);
        return back()->with('success', $prospecto->empresa . ': ' . Prospecto::ESTADOS[$request->estado][0] . '.');
    }

    // Correo de prueba al administrador con el paso elegido (para ver cómo llega)
    public function prueba(Request $request)
    {
        if (!Prospectos::buzonListo()) return back()->withErrors(['prospectos' => 'Primero guarde el correo y la clave de aplicación del buzón de envío.']);
        $paso = (int) $request->input('paso', 1);
        $p = new Prospecto(['empresa' => 'Firma de ejemplo', 'nombre' => auth()->user()->name, 'fuente' => 'https://ejemplo.com/contacto']);
        $p->id = 0;
        $p->token = 'prueba';
        [$de, $nombre] = Prospectos::remitente();
        try {
            Prospectos::mailer()->to(auth()->user()->email)->send(new ProspectoMail($p, max(1, min(3, $paso)), $de, $nombre));
        } catch (\Throwable $e) {
            return back()->withErrors(['prospectos' => 'No se pudo enviar: ' . mb_substr($e->getMessage(), 0, 300)]);
        }
        return back()->with('success', 'Correo de prueba enviado a ' . auth()->user()->email . ' desde ' . $de . '.');
    }

    public function correr(Request $request)
    {
        $cmd = $request->input('que') === 'buscar' ? 'prospectos:buscar' : 'prospectos:enviar';
        Artisan::call($cmd, $cmd === 'prospectos:enviar' ? ['--todo' => true] : ['--webs' => 15]);
        $salida = trim(Artisan::output());
        return back()->with('success', mb_substr($salida, -1500) ?: 'Listo.');
    }

    // ----- Público: enlace de baja (un clic, también por List-Unsubscribe-Post) -----
    public function baja(string $token)
    {
        $p = Prospecto::where('token', $token)->first();
        if ($p && $p->estado !== 'baja') {
            $p->forceFill(['estado' => 'baja', 'baja_at' => now(), 'proximo_envio' => null])->save();
        }
        if (request()->isMethod('post')) return response('', 204);
        return response()->view('prospectos.baja', ['ok' => (bool) $p, 'correo' => $p?->correo], $p ? 200 : 404);
    }
}
