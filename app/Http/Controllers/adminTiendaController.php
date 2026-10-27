<?php

namespace App\Http\Controllers;

use App\Models\Tienda;
use App\Models\User;
use App\Support\CrearTienda;
use App\Support\Paises;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// Panel del administrador de LocalPro: todas las tiendas inscritas en la plataforma
class adminTiendaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $tiendas = Tienda::query()->with(['plan', 'asesor.user:id,name', 'ventaDirecta:id,name'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('ciudad', 'like', "%$q%")->orWhere('codigo', 'like', "%$q%")
                ->orWhere('telefono', 'like', "%$q%")->orWhere('nit', 'like', "%$q%")
                ->orWhereIn('id', User::whereNotNull('tienda_id')->where(fn ($u) => $u->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))->select('tienda_id'))
                ->orWhereHas('asesor', fn ($a) => $a->where('codigo', 'like', "%$q%"))))
            ->when($request->get('estado'), fn ($w, $e) => $w->where('estado', $e))
            ->when($request->get('ciudad') === 'sin', fn ($w) => $w->where(fn ($x) => $x->whereNull('ciudad')->orWhere('ciudad', '')))
            ->when($request->get('ciudad') && $request->get('ciudad') !== 'sin', fn ($w) => $w->where('ciudad', $request->get('ciudad')))
            ->when($request->get('asesor') === 'sin', fn ($w) => $w->sinAsignar())
            ->latest()
            ->get();

        // Métricas de todas las tiendas en pocas consultas (sin el filtro por tienda)
        $ventas = DB::table('pos_ventas')->where('estado', 'pagada')
            ->groupBy('tienda_id')
            ->select('tienda_id', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as total'), DB::raw('MAX(created_at) as ultima'))
            ->get()->keyBy('tienda_id');
        $productos = DB::table('productos')->groupBy('tienda_id')->select('tienda_id', DB::raw('COUNT(*) as n'))->pluck('n', 'tienda_id');
        $usuarios = DB::table('users')->whereNotNull('tienda_id')->groupBy('tienda_id')->select('tienda_id', DB::raw('COUNT(*) as n'))->pluck('n', 'tienda_id');
        $duenos = User::role('dueno')->whereIn('tienda_id', $tiendas->pluck('id'))->get()->groupBy('tienda_id');

        $resumen = [
            'total' => Tienda::count(),
            'activas' => Tienda::where('estado', 'activa')->count(),
            'nuevas_mes' => Tienda::where('created_at', '>=', now()->startOfMonth())->count(),
            'configurando' => Tienda::where('onboarding_completo', false)->count(),
            'ventas_hoy' => DB::table('pos_ventas')->where('estado', 'pagada')->whereDate('created_at', today())->count(),
        ];

        $resumen['sin_asesor'] = Tienda::sinAsignar()->count();
        // De qué ciudad se están creando las tiendas (de más a menos)
        $ciudades = Tienda::query()->selectRaw("COALESCE(NULLIF(ciudad, ''), 'sin') as ciudad, MAX(departamento) as departamento, COUNT(*) as n")
            ->groupByRaw("COALESCE(NULLIF(ciudad, ''), 'sin')")->orderByDesc('n')->get();
        $asesores = \App\Models\Asesor::with('user:id,name,email')->where('activo', true)
            ->orderByRaw("FIELD(cargo, 'asesor', 'lider_zona', 'lider_ciudad', 'lider_departamento', 'lider_pais')")->get();
        return view('admin.tiendas.index', compact('tiendas', 'ventas', 'productos', 'usuarios', 'duenos', 'resumen', 'q', 'asesores', 'ciudades'));
    }

    public function create()
    {
        return view('admin.tiendas.create', ['tipos' => Tienda::TIPOS, 'paises' => Paises::lista()]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:80',
            'tipo_negocio' => ['required', Rule::in(array_keys(Tienda::TIPOS))],
            'pais' => ['required', Rule::in(array_keys(Paises::LISTA))],
            'telefono' => 'required|string|max:30',
            'dueno_nombre' => 'required|string|max:80',
            'dueno_email' => 'required|email|max:120|unique:users,email',
            'dueno_telefono' => 'required|string|max:20',
            'dueno_password' => 'required|string|min:8',
        ] + \App\Support\Ubicaciones::reglas($request->input('pais'), $request->input('departamento')), \App\Support\Ubicaciones::mensajes(), [
            'dueno_nombre' => 'nombre del dueño', 'dueno_email' => 'correo del dueño', 'dueno_password' => 'contraseña del dueño',
            'telefono' => 'teléfono de la tienda', 'dueno_telefono' => 'celular del dueño',
        ]);

        [$tienda] = CrearTienda::crear(
            ['nombre' => $datos['nombre'], 'tipo_negocio' => $datos['tipo_negocio'], 'pais' => $datos['pais'], 'departamento' => $datos['departamento'], 'ciudad' => $datos['ciudad'], 'telefono' => $datos['telefono']],
            ['name' => $datos['dueno_nombre'], 'email' => $datos['dueno_email'], 'telefono' => $datos['dueno_telefono'], 'password' => $datos['dueno_password']]
        );

        return redirect()->route('admin.tiendas.show', $tienda)
            ->with('success', 'Tienda creada. El dueño entra con ' . $datos['dueno_email'] . ' y la contraseña que usted definió; el sistema le pedirá terminar la configuración.');
    }

    public function show(Tienda $tienda)
    {
        // Si la tienda tiene pagos de Wompi pendientes, se confirman de una vez
        try {
            if (\App\Support\Wompi::verificarPendientes($tienda->id, 5)) {
                $tienda->refresh();
                session()->now('success', 'Pago confirmado con Wompi: el plan de la tienda ya quedó activo.');
            }
        } catch (\Throwable $e) { report($e); }
        $usuarios = User::where('tienda_id', $tienda->id)->orderBy('name')->get();
        $ventas = DB::table('pos_ventas')->where('tienda_id', $tienda->id)->where('estado', 'pagada');
        $metricas = [
            'ventas' => (clone $ventas)->count(),
            'total' => (float) (clone $ventas)->sum('total'),
            'mes' => (float) (clone $ventas)->where('created_at', '>=', now()->startOfMonth())->sum('total'),
            'productos' => DB::table('productos')->where('tienda_id', $tienda->id)->count(),
            'unidades' => (int) DB::table('producto_variantes')->where('tienda_id', $tienda->id)->sum('stock'),
            'cajas_abiertas' => DB::table('cajas')->where('tienda_id', $tienda->id)->where('estado', 'abierta')->count(),
        ];
        $ultimas = DB::table('pos_ventas')->where('tienda_id', $tienda->id)->latest()->limit(8)->get();

        $planes = \App\Models\Plan::orderBy('orden')->get();
        $pagosPlan = \App\Models\PagoSuscripcion::with('plan')->where('tienda_id', $tienda->id)->latest()->limit(10)->get();
        $asesores = \App\Models\Asesor::with('user:id,name,email')->where('activo', true)->get()->sortBy('nombre');
        // Constancia de avisos de fin de prueba (correos y pantalla vista) y fecha de eliminación
        $avisos = \App\Models\AvisoPlan::with('user:id,name')->where('tienda_id', $tienda->id)->latest()->limit(20)->get();
        $fechaBorrado = $tienda->datos_depurados ? null : \App\Support\AvisosPlan::fechaBorrado($tienda);
        return view('admin.tiendas.show', compact('tienda', 'usuarios', 'metricas', 'ultimas', 'planes', 'pagosPlan', 'asesores', 'avisos', 'fechaBorrado'));
    }

    // Subir ventas pasadas del negocio (Excel) como historial: crea/completa clientes, sin mover inventario
    public function ventasHistoricas(Request $request, Tienda $tienda)
    {
        $request->validate(['archivo' => 'required|file|max:10240'], ['archivo.required' => 'Elija el archivo de Excel.']);
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
        if (!in_array($ext, ['xlsx', 'xlsm', 'csv', 'txt'], true)) return back()->withErrors('Suba un archivo .xlsx o .csv.');
        try {
            $filas = \App\Support\HojaCalculo::leer($f->getRealPath(), $ext);
            $l = \App\Support\ImportarVentasHistoricas::leer($filas);
            if (!$l['ventas']) return back()->withErrors('No encontré ventas válidas (fecha, producto y valor) en el archivo.');
            $r = \App\Support\ImportarVentasHistoricas::guardar($tienda, $l['ventas']);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors('No se pudo subir: ' . $e->getMessage());
        } finally {
            \App\Support\TiendaActual::set(null);
        }
        $msg = 'Se subieron ' . $r['ventas'] . ' ventas por $' . number_format($r['total'], 0, ',', '.') . ' · ' . $r['clientes_nuevos'] . ' clientes nuevos';
        if ($r['clientes_completados']) $msg .= ' · ' . $r['clientes_completados'] . ' clientes completados';
        if ($r['repetidas']) $msg .= ' · ' . $r['repetidas'] . ' ya estaban (no se repitieron)';
        if ($l['malas']) $msg .= ' · ' . $l['malas'] . ' filas sin fecha/producto/valor se saltaron';
        return back()->with('success', $msg . '.');
    }

    // Genera un enlace de pago de PRUEBA (1.500) para probar Wompi con esta tienda
    public function pagoPrueba(Request $request, Tienda $tienda)
    {
        if (!\App\Support\Wompi::activo()) {
            return back()->withErrors('Faltan las llaves de Wompi en el servidor.');
        }
        $plan = \App\Support\Wompi::planAnual();
        if (!$plan) {
            return back()->withErrors('No hay un plan anual activo.');
        }
        $pago = \App\Support\Wompi::crearPagoPrueba($tienda, $plan, 1500, $request->user()->id);
        $url = \App\Support\Wompi::urlCheckout($pago, route('pagar.retorno', ['ref' => $pago->referencia]), $tienda->email, $tienda->nombre, $tienda->telefono);

        return back()->with('enlace_prueba', $url)->with('success', 'Enlace de prueba de $1.500 listo. Compártalo o ábralo para pagar.');
    }

    public function estado(Tienda $tienda)
    {
        $tienda->update(['estado' => $tienda->activa ? 'suspendida' : 'activa']);
        return back()->with('success', 'La tienda ' . $tienda->nombre . ' quedó ' . ($tienda->activa ? 'activa' : 'suspendida') . '.');
    }

    // Asignar plan y fecha de vencimiento a mano
    public function plan(Request $request, Tienda $tienda)
    {
        $datos = $request->validate([
            'plan_id' => 'required|exists:planes,id',
            'plan_vence' => 'nullable|date',
            'redes_max' => 'nullable|integer|min:1|max:20',
        ]);
        $tienda->forceFill(['plan_id' => $datos['plan_id'], 'plan_vence' => $datos['plan_vence'] ?? null, 'redes_max' => $datos['redes_max'] ?? $tienda->redes_max ?? 1])->save();
        return back()->with('success', 'Plan actualizado.');
    }

    // Tiempo de prueba gratis: 15 días, 1, 2, 3 o 6 meses, contados desde que se registró o desde hoy.
    // La tienda queda con el plan gratis hasta esa fecha y el dueño ve el aviso en su panel.
    public const PRUEBAS = ['15d' => '15 días', '1' => '1 mes', '2' => '2 meses', '3' => '3 meses', '6' => '6 meses'];

    public function prueba(Request $request, Tienda $tienda)
    {
        $datos = $request->validate([
            'tiempo' => ['required', \Illuminate\Validation\Rule::in(array_keys(self::PRUEBAS))],
            'desde' => 'required|in:registro,hoy',
        ]);
        $inicio = $datos['desde'] === 'hoy' ? today() : $tienda->created_at->copy()->startOfDay();
        $hasta = $datos['tiempo'] === '15d' ? $inicio->copy()->addDays(14) : $inicio->copy()->addMonthsNoOverflow((int) $datos['tiempo'])->subDay();
        if ($hasta->lt(today())) {
            return back()->with('error', 'Contando desde el registro (' . $inicio->format('d/m/Y') . '), ' . self::PRUEBAS[$datos['tiempo']] . ' ya se cumplieron el ' . $hasta->format('d/m/Y') . '. Elija "desde hoy" o más tiempo.');
        }
        $gratis = \App\Models\Plan::gratis();
        $tienda->forceFill([
            'plan_id' => $gratis?->id ?? $tienda->plan_id,
            'plan_vence' => $hasta,
            'estado' => 'activa',
            'aviso_prueba' => self::PRUEBAS[$datos['tiempo']],
        ])->save();
        $dias = (int) round(today()->diffInDays($hasta)) + 1;
        return back()->with('success', 'Listo: ' . $tienda->nombre . ' tiene prueba gratis de ' . self::PRUEBAS[$datos['tiempo']] . ', hasta el ' . $hasta->format('d/m/Y') . ' (' . $dias . ' ' . ($dias === 1 ? 'día' : 'días') . '). Al entrar a su panel le sale el aviso.')
            ->with('aviso_prueba_tel', $tienda->telefono ? numero_whatsapp($tienda->telefono) : null)
            ->with('aviso_prueba_wa', 'Hola 👋 Le cuento que su prueba gratis de LocalProo quedó de ' . self::PRUEBAS[$datos['tiempo']] . ': puede usar todo hasta el ' . $hasta->locale('es')->isoFormat('D [de] MMMM [de] YYYY') . ' (' . $dias . ' días). Cualquier duda me escribe.');
    }

    // Productos extra comprados a LocalPro (paquetes de, por defecto, 100 productos por $20.000)
    public function productosExtra(Request $request, Tienda $tienda)
    {
        $datos = $request->validate([
            'paquetes' => 'required|integer|min:1|max:100',
            'accion' => 'required|in:sumar,quitar',
        ]);
        $cant = $datos['paquetes'] * \App\Support\AmpliarProductos::cantidad();
        $nuevo = $datos['accion'] === 'sumar'
            ? (int) $tienda->productos_extra + $cant
            : max(0, (int) $tienda->productos_extra - $cant);
        $tienda->forceFill(['productos_extra' => $nuevo])->save();
        return back()->with('success', 'Listo: la tienda tiene ' . number_format($nuevo, 0, ',', '.') . ' productos extra'
            . ($datos['accion'] === 'sumar' ? ' (cobrar ' . \App\Support\AmpliarProductos::precioTexto($tienda, (int) $datos['paquetes']) . ').' : '.'));
    }

    // Registrar un pago recibido por fuera (transferencia, Nequi, efectivo): extiende el plan
    public function pago(Request $request, Tienda $tienda)
    {
        $datos = $request->validate([
            'plan_id' => 'required|exists:planes,id',
            'monto' => 'required|numeric|min:0',
            'nota' => 'nullable|string|max:255',
        ]);
        $moneda = $tienda->moneda_cobro; // Venezuela: el pago se registra en dólares
        DB::transaction(function () use ($datos, $tienda, $moneda) {
            \App\Models\PagoSuscripcion::create([
                'tienda_id' => $tienda->id,
                'plan_id' => $datos['plan_id'],
                'monto' => $datos['monto'],
                'moneda' => $moneda,
                'metodo' => 'manual',
                'referencia' => 'MAN-' . $tienda->id . '-' . now()->format('ymdHis'),
                'nota' => $datos['nota'] ?? null,
                'registrado_por' => auth()->id(),
            ])->aprobar();
        });
        return back()->with('success', 'Pago registrado y plan extendido.');
    }

    // Ver el sistema como si fuera la tienda (para dar soporte)
    public function entrar(Request $request, Tienda $tienda)
    {
        $request->session()->put('tienda_visitada', $tienda->id);
        return redirect()->route('panel');
    }

    // Selector de vista del superadministrador: "interno" (panel LocalPro) o "tienda" (su propia tienda, como cliente)
    public function vista(Request $request)
    {
        $modo = $request->input('modo') === 'tienda' && $request->user()->tienda_id ? 'tienda' : 'interno';
        $request->session()->forget('tienda_visitada');
        $request->session()->put('vista_admin', $modo);
        return $modo === 'tienda' ? redirect()->route('panel') : redirect()->route('admin.inicio');
    }

    // Eliminar una tienda (ej. duplicada). Pide escribir su código para confirmar.
    // No deja borrar tiendas con pagos reales aprobados: a esas se les suspende.
    public function eliminar(Request $request, Tienda $tienda)
    {
        if (strtoupper(trim((string) $request->input('confirmar'))) !== strtoupper((string) $tienda->codigo)) {
            return back()->withErrors('No se eliminó: el código escrito no coincide con ' . $tienda->codigo . '.');
        }
        $pagosReales = \App\Models\PagoSuscripcion::where('tienda_id', $tienda->id)->where('estado', 'aprobado')
            ->where('monto', '>', 0)->where('referencia', 'not like', \App\Models\PagoSuscripcion::PREFIJO_PRUEBA . '%')->exists();
        if ($pagosReales) {
            return back()->withErrors('La tienda ' . $tienda->nombre . ' tiene pagos aprobados; no se puede eliminar. Puede suspenderla.');
        }
        // Consultorios: la historia clínica se debe conservar por ley (Res. 1995/1999)
        if (\Illuminate\Support\Facades\Schema::hasTable('atenciones') && DB::table('atenciones')->where('tienda_id', $tienda->id)->exists()) {
            return back()->withErrors($tienda->nombre . ' tiene historias clínicas de pacientes: por ley se deben conservar y no se puede eliminar. Puede suspenderla.');
        }

        $nombre = $tienda->nombre . ' (' . $tienda->codigo . ')';
        DB::transaction(function () use ($tienda) {
            $usuarios = User::where('tienda_id', $tienda->id)->get();
            // Productos, ventas, cajas, clientes, etc. se borran solos con la tienda (en cascada)
            $tienda->delete();
            foreach ($usuarios as $u) {
                // Nunca se borra un superadministrador ni a alguien de la red de asesores: solo queda sin tienda
                if ($u->es_superadmin || \App\Models\Asesor::where('user_id', $u->id)->exists()) {
                    continue;
                }
                $u->syncRoles([]);
                $u->tokens()->delete();
                $u->delete();
            }
        });
        if ((int) $request->session()->get('tienda_visitada') === (int) $tienda->id) {
            $request->session()->forget('tienda_visitada');
        }

        return redirect()->route('admin.tiendas.index')->with('success', 'Se eliminó la tienda ' . $nombre . ' con sus usuarios y datos.');
    }

    public function salir(Request $request)
    {
        $request->session()->forget(['tienda_visitada', 'vista_admin']);
        return redirect()->route('admin.inicio');
    }
}
