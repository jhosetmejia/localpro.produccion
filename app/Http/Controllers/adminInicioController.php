<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Models\Comision;
use App\Models\PagoSuscripcion;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Panel general del superadministrador de LocalPro
class adminInicioController extends Controller
{
    public function index()
    {
        $hoy = today();
        $aprobados = fn () => PagoSuscripcion::where('estado', 'aprobado');

        // Ingresos de los últimos 6 meses para la gráfica
        $meses = collect(range(5, 0))->map(function ($i) use ($aprobados) {
            $ini = now()->startOfMonth()->subMonths($i);
            return [
                'label' => ucfirst($ini->locale('es')->isoFormat('MMM')),
                'total' => (float) $aprobados()->whereBetween('created_at', [$ini, $ini->copy()->endOfMonth()])->sum(PagoSuscripcion::sqlEnCop()),
            ];
        });

        return view('admin.inicio', [
            'kpi' => [
                'tiendas' => Tienda::count(),
                'activas' => Tienda::where('estado', 'activa')->count(),
                'suspendidas' => Tienda::where('estado', '<>', 'activa')->count(),
                'nuevasMes' => Tienda::where('created_at', '>=', now()->startOfMonth())->count(),
                'configurando' => Tienda::where('onboarding_completo', false)->count(),
                'pagando' => Tienda::whereNotNull('plan_vence')->where('plan_vence', '>=', $hoy)->count(),
                'gratis' => Tienda::whereNull('plan_vence')->count(),
                'vencidas' => Tienda::whereNotNull('plan_vence')->where('plan_vence', '<', $hoy)->count(),
                'ingresosMes' => (float) $aprobados()->where('created_at', '>=', now()->startOfMonth())->sum(PagoSuscripcion::sqlEnCop()),
                'ingresosAnio' => (float) $aprobados()->where('created_at', '>=', now()->startOfYear())->sum(PagoSuscripcion::sqlEnCop()),
                'ingresosTotal' => (float) $aprobados()->sum(PagoSuscripcion::sqlEnCop()),
                'pagosPendientes' => PagoSuscripcion::where('estado', 'pendiente')->count(),
                'usuarios' => User::count(),
                'red' => Asesor::where('activo', true)->count(),
                // En pesos: las comisiones en otra moneda (Venezuela) se suman a la tasa del día
                'comisionesPendientes' => (float) Comision::where('estado', 'pendiente')->sum(DB::raw(\App\Support\Moneda::sqlACop('monto'))),
                'ventasHoy' => DB::table('pos_ventas')->where('estado', 'pagada')->whereDate('created_at', $hoy)->count(),
                // En pesos: las ventas de las tiendas de Venezuela (dólares o bolívares) se pasan a la tasa del día
                'vendidoHoy' => (float) DB::table('pos_ventas')->join('tiendas', 'tiendas.id', '=', 'pos_ventas.tienda_id')
                    ->where('pos_ventas.estado', 'pagada')->whereDate('pos_ventas.created_at', $hoy)
                    ->sum(DB::raw(\App\Support\Moneda::sqlACop('pos_ventas.total', "CASE WHEN tiendas.pais = 'VE' THEN '" . \App\Support\Moneda::deVenezuela() . "' ELSE 'COP' END"))),
            ],
            'meses' => $meses,
            // Por cobrar: vencidas o que vencen en los próximos 10 días
            'porCobrar' => Tienda::with(['plan:id,nombre,precio', 'asesor.user:id,name'])->whereNotNull('plan_vence')
                ->where('plan_vence', '<=', $hoy->copy()->addDays(10))->orderBy('plan_vence')->take(15)->get(),
            'ultimosPagos' => PagoSuscripcion::with(['tienda:id,nombre', 'plan:id,nombre'])->latest()->take(8)->get(),
            'nuevas' => Tienda::with(['plan:id,nombre', 'asesor.user:id,name'])->latest()->take(8)->get(),
        ]);
    }

    public function usuarios(Request $request)
    {
        $q = trim((string) $request->q);
        $lista = User::with(['tienda:id,nombre', 'roles:id,name', 'asesor'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('telefono', 'like', "%$q%")))
            ->when($request->tipo === 'admin', fn ($w) => $w->where('es_superadmin', true))
            ->when($request->tipo === 'red', fn ($w) => $w->whereHas('asesor'))
            ->when($request->tipo === 'tiendas', fn ($w) => $w->whereNotNull('tienda_id'))
            ->orderBy('name')->paginate(40)->withQueryString();

        return view('admin.usuarios', [
            'lista' => $lista, 'q' => $q, 'tipo' => $request->tipo,
            'conteo' => [
                'todos' => User::count(),
                'tiendas' => User::whereNotNull('tienda_id')->count(),
                'red' => Asesor::count(),
                'admin' => User::where('es_superadmin', true)->count(),
            ],
        ]);
    }

    // El administrador le pone una clave nueva a un usuario (soporte: "olvidé mi clave")
    public function clave(Request $request, User $user)
    {
        $d = $request->validate(['clave' => 'required|string|min:8'], ['clave.min' => 'La clave debe tener mínimo 8 caracteres.']);
        $user->forceFill(['password' => Hash::make($d['clave'])])->save();
        return back()->with('success', 'Clave nueva guardada para ' . $user->name . '.');
    }

    // Cuando alguien perdió el acceso a su correo: el administrador (después de verificar quién es) le pone uno nuevo.
    // Se avisa al correo nuevo y queda en el log con el correo anterior.
    public function correo(Request $request, User $user)
    {
        $d = $request->validate(['email' => ['required', 'email', 'max:120', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)]],
            ['email.required' => 'Escriba el correo nuevo.', 'email.email' => 'El correo no es válido.', 'email.unique' => 'Ese correo ya lo tiene otra cuenta.']);
        $antes = $user->email;
        $nuevo = \Illuminate\Support\Str::lower(trim($d['email']));
        $user->forceFill(['email' => $nuevo, 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        \Illuminate\Support\Facades\Log::info('Correo de usuario cambiado por el administrador', ['user' => $user->id, 'antes' => $antes, 'nuevo' => $nuevo, 'admin' => $request->user()?->id]);
        try {
            \Illuminate\Support\Facades\Mail::html(
                '<p>Hola ' . e($user->name) . ',</p><p>El correo de su cuenta de LocalProo se cambió a <b>' . e($nuevo) . '</b>. Desde ahora entra con este correo. Si olvidó la contraseña, en la pantalla de ingreso toque "¿Olvidó su contraseña?" y le llega un código.</p><p>Si usted no pidió este cambio, responda este correo de inmediato.</p><p>El equipo de LocalProo</p>',
                fn ($m) => $m->to($nuevo)->subject('Se cambió el correo de su cuenta de LocalProo')
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo avisar el cambio de correo', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
        return back()->with('success', 'Correo de ' . $user->name . ' cambiado a ' . $nuevo . '. Ya puede pedir el código de "¿Olvidó su contraseña?" con ese correo.');
    }

    // Auditoría del sistema: muestra el último informe y permite correr una revisión de datos
    public function auditoria()
    {
        $archivos = collect(\Illuminate\Support\Facades\Storage::disk('local')->files('auditoria'))->sort()->values();
        $ultimo = $archivos->last();
        return view('admin.auditoria', [
            'texto' => $ultimo ? \Illuminate\Support\Facades\Storage::disk('local')->get($ultimo) : null,
            'fecha' => $ultimo ? \Carbon\Carbon::createFromTimestamp(\Illuminate\Support\Facades\Storage::disk('local')->lastModified($ultimo)) : null,
        ]);
    }

    public function correrAuditoria()
    {
        \Illuminate\Support\Facades\Artisan::call('localpro:auditoria', ['--sin-pantallas' => true]);
        return redirect()->route('admin.auditoria')->with('success', 'Revisión terminada.');
    }

    // Botón "Revisar con Wompi" en Pagos
    public function verificarPagos()
    {
        if (!config('services.wompi.private_key')) {
            return back()->withErrors('Falta la llave privada de Wompi en el servidor para revisar los pagos.');
        }
        $n = \App\Support\Wompi::verificarPendientes(null, 50);
        return back()->with('success', $n ? ($n === 1 ? 'Se confirmó 1 pago con Wompi.' : "Se confirmaron $n pagos con Wompi.") : 'Revisado con Wompi: no hay pagos nuevos aprobados.');
    }

    public function pagos(Request $request)
    {
        // Confirma con Wompi los pagos que siguen pendientes, sin consola
        try { $aprobados = \App\Support\Wompi::verificarPendientes(null, 10); } catch (\Throwable $e) { report($e); $aprobados = 0; }
        if ($aprobados) session()->now('success', $aprobados === 1 ? 'Se confirmó 1 pago con Wompi.' : "Se confirmaron $aprobados pagos con Wompi.");

        $filtro = fn ($q) => $q
            ->when($request->estado, fn ($w, $e) => $w->where('estado', $e))
            ->when($request->mes, fn ($w, $m) => $w->whereYear('created_at', substr($m, 0, 4))->whereMonth('created_at', substr($m, 5, 2)));

        return view('admin.pagos', [
            'lista' => $filtro(PagoSuscripcion::with(['tienda:id,nombre', 'plan:id,nombre']))->latest()->paginate(40)->withQueryString(),
            'total' => (float) $filtro(PagoSuscripcion::query())->where('estado', 'aprobado')->sum(PagoSuscripcion::sqlEnCop()), // en pesos (USD a pesos)
            'estado' => $request->estado, 'mes' => $request->mes,
        ]);
    }
}
