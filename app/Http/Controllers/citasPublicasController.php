<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consentimiento;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Tienda;
use App\Models\TratamientoPlan;
use App\Support\AgendaEnLinea;
use App\Support\TiendaActual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Páginas para los pacientes, sin clave: pedir cita en línea, confirmar o cancelar la cita y su portal (citas, saldos, consentimientos)
class citasPublicasController extends Controller
{
    private function consultorio(string $slug): Tienda
    {
        $t = Tienda::where('slug', strtolower($slug))->firstOrFail();
        abort_unless($t->activa && $t->mod_consultorio && !$t->plan_vencido, 404);
        TiendaActual::set($t->id);
        return $t;
    }

    // ================= Pedir cita en línea =================
    public function show(string $slug)
    {
        $t = $this->consultorio($slug);
        $c = AgendaEnLinea::config($t);
        $esDueno = auth()->check() && ((int) auth()->user()->tienda_id === (int) $t->id || auth()->user()->es_superadmin);
        abort_unless($c['activa'] || $esDueno, 404);
        $servicios = Producto::where('estado', 1)->where('es_servicio', true)->orderBy('nombre')->get(['id', 'nombre', 'precio_venta']);
        return view('citas.publica', ['t' => $t, 'c' => $c, 'dias' => AgendaEnLinea::diasDisponibles($t), 'servicios' => $servicios, 'borrador' => !$c['activa']]);
    }

    public function horas(Request $request, string $slug)
    {
        $t = $this->consultorio($slug);
        $dia = rescue(fn () => Carbon::parse((string) $request->get('fecha')), null, false);
        return response()->json(['horas' => $dia ? array_map(fn ($h) => ['v' => $h, 't' => Carbon::parse($h)->format('h:i a')], AgendaEnLinea::horasLibres($t, $dia)) : []]);
    }

    public function store(Request $request, string $slug)
    {
        $t = $this->consultorio($slug);
        abort_unless(AgendaEnLinea::config($t)['activa'], 404);
        $d = $request->validate([
            'fecha' => 'required|date', 'hora' => 'required|date_format:H:i',
            'servicio' => ['nullable', 'integer', Rule::exists('productos', 'id')->where('tienda_id', $t->id)],
            'tipo_documento' => ['required', Rule::in(array_keys(Paciente::DOCUMENTOS))],
            'documento' => 'required|string|min:4|max:30',
            'nombres' => 'required|string|max:80', 'apellidos' => 'required|string|max:80',
            'telefono' => 'required|string|min:7|max:30', 'correo' => 'nullable|email|max:120',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'nota' => 'nullable|string|max:200',
            'autoriza' => 'accepted',
            'web' => 'nullable|max:0', // trampa para robots
        ], ['autoriza.accepted' => 'Debe autorizar el tratamiento de sus datos para pedir la cita.', 'documento.required' => 'Escriba su número de documento.',
            'telefono.required' => 'Escriba su celular para confirmarle la cita.', 'hora.required' => 'Elija la hora.']);

        $doc = preg_replace('/[^0-9A-Za-z]/', '', $d['documento']);
        $cita = DB::transaction(function () use ($t, $d, $doc) {
            $dia = Carbon::parse($d['fecha']);
            // Se vuelve a revisar el cupo dentro de la transacción (dos personas pueden pedir la misma hora a la vez)
            \App\Models\Tienda::whereKey($t->id)->lockForUpdate()->first();
            if (!in_array($d['hora'], AgendaEnLinea::horasLibres($t, $dia), true)) {
                throw ValidationException::withMessages(['hora' => 'Esa hora se acaba de ocupar. Elija otra, por favor.']);
            }
            $p = Paciente::where('documento', $doc)->first();
            if (!$p) {
                $p = Paciente::create(['tipo_documento' => $d['tipo_documento'], 'documento' => $doc, 'nombres' => trim($d['nombres']), 'apellidos' => trim($d['apellidos']),
                    'telefono' => $d['telefono'], 'correo' => $d['correo'] ?? null, 'fecha_nacimiento' => $d['fecha_nacimiento'] ?? null, 'autoriza_datos_at' => now()]);
            } else {
                // Paciente que ya existe: solo se completan los datos que falten (no se cambia lo que el consultorio ya tiene)
                $p->fill(array_filter(['telefono' => $p->telefono ? null : $d['telefono'], 'correo' => $p->correo ? null : ($d['correo'] ?? null),
                    'autoriza_datos_at' => $p->autoriza_datos_at ? null : now()]))->save();
            }
            $serv = !empty($d['servicio']) ? Producto::find($d['servicio']) : null;
            return Cita::create([
                'paciente_id' => $p->id, 'fecha' => $dia->toDateString(), 'hora' => $d['hora'], 'duracion' => AgendaEnLinea::config($t)['duracion'],
                'motivo' => mb_substr(($serv?->nombre ?? 'Cita') . ' (pedida en línea)', 0, 160), 'valor' => $serv?->precio_venta, 'nota' => $d['nota'] ?? null,
                'estado' => 'programada', 'origen' => 'en_linea', 'token' => \Illuminate\Support\Str::random(40),
            ]);
        });
        // Aviso al consultorio por correo (si el servidor tiene correo configurado)
        if (\App\Support\AvisosPlan::correoConfigurado()) {
            foreach (\App\Support\AvisosPlan::destinatarios($t) as $correo) {
                try {
                    Mail::html(view('emails.cita-en-linea', ['t' => $t, 'cita' => $cita->load('paciente')])->render(), fn ($m) => $m->to($correo)->subject('Nueva cita en línea: ' . $cita->paciente->nombre_completo));
                } catch (\Throwable $e) {
                    Log::warning('Aviso de cita en línea no enviado', ['error' => $e->getMessage()]);
                }
            }
        }
        return redirect()->route('citas.confirmar', $cita->token)->with('nueva', true);
    }

    // ================= Confirmar o cancelar la cita (enlace del recordatorio) =================
    private function citaPorToken(string $token): Cita
    {
        abort_unless(strlen($token) === 40, 404);
        $c = Cita::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        TiendaActual::set($c->tienda_id);
        return $c->load('paciente');
    }

    public function confirmar(string $token)
    {
        $c = $this->citaPorToken($token);
        return view('citas.confirmar', ['c' => $c, 't' => TiendaActual::tienda(), 'pasada' => Carbon::parse($c->fecha->toDateString() . ' ' . $c->hora)->lt(now())]);
    }

    public function responder(Request $request, string $token)
    {
        $c = $this->citaPorToken($token);
        $request->validate(['accion' => 'required|in:confirmar,cancelar']);
        if (!$c->activa || Carbon::parse($c->fecha->toDateString() . ' ' . $c->hora)->lt(now())) {
            return back()->with('aviso', 'Esta cita ya no se puede cambiar. Comuníquese con el consultorio.');
        }
        if ($request->accion === 'confirmar') {
            $c->update(['estado' => 'confirmada', 'confirmada_at' => now()]);
            return back()->with('aviso', '¡Gracias! Su cita quedó confirmada.');
        }
        $c->update(['estado' => 'cancelada', 'nota' => mb_substr(trim(($c->nota ? $c->nota . ' · ' : '') . 'Cancelada por el paciente desde el enlace'), 0, 255)]);
        return back()->with('aviso', 'Su cita quedó cancelada. Si quiere otra fecha, pídala de nuevo o escríbale al consultorio.');
    }

    // ================= Portal del paciente =================
    private function pacientePorToken(string $token): Paciente
    {
        abort_unless(strlen($token) === 40, 404);
        $p = Paciente::withoutGlobalScopes()->where('portal_token', $token)->firstOrFail();
        TiendaActual::set($p->tienda_id);
        abort_unless(TiendaActual::tienda()?->activa, 404);
        return $p;
    }

    public function portal(string $token)
    {
        $p = $this->pacientePorToken($token);
        $citas = $p->citas()->whereDate('fecha', '>=', today()->subMonths(6))->orderByDesc('fecha')->orderByDesc('hora')->take(15)->get();
        $planes = $p->tratamientos()->with(['items', 'abonos' => fn ($q) => $q->where('anulado', false)])->whereIn('estado', ['propuesto', 'aprobado', 'terminado'])->get();
        $consent = $p->consentimientos()->get(['id', 'titulo', 'procedimiento', 'firmado_at']);
        foreach ($citas as $c) if (!$c->token) $c->forceFill(['token' => \Illuminate\Support\Str::random(40)])->save();
        $t = TiendaActual::tienda();
        return view('citas.portal', ['p' => $p, 't' => $t, 'citas' => $citas, 'planes' => $planes, 'consent' => $consent,
            'agenda' => AgendaEnLinea::config($t)['activa'], 'token' => $token]);
    }

    public function portalPlan(string $token, int $plan)
    {
        $p = $this->pacientePorToken($token);
        $pl = TratamientoPlan::where('paciente_id', $p->id)->whereIn('estado', ['propuesto', 'aprobado', 'terminado'])->findOrFail($plan);
        $pl->load(['paciente', 'items', 'abonos', 'profesional:id,name,registro_profesional,especialidad,firma_path']);
        $u = $pl->profesional;
        $firma = $u?->firma_path && Storage::disk('local')->exists($u->firma_path) ? 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($u->firma_path)) : null;
        return view('odontologia.presupuesto', ['plan' => $pl, 'p' => $p, 'tienda' => TiendaActual::tienda(), 'firma' => $firma, 'wa' => '', 'portal' => $token]);
    }

    public function portalConsentimiento(string $token, int $id)
    {
        $p = $this->pacientePorToken($token);
        $c = Consentimiento::where('paciente_id', $p->id)->findOrFail($id);
        $img = fn ($path) => $path && Storage::disk('local')->exists($path) ? 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($path)) : null;
        $prof = $c->profesional_id ? \App\Models\User::find($c->profesional_id, ['id', 'firma_path']) : null;
        return view('odontologia.consentimiento', ['c' => $c, 'p' => $p, 'tienda' => TiendaActual::tienda(),
            'firmaPaciente' => $img($c->firma_path), 'firmaProfesional' => $img($prof?->firma_path), 'portal' => $token]);
    }
}
