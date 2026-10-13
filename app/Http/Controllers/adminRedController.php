<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Asesor;
use App\Models\Comision;
use App\Models\Tienda;
use App\Models\User;
use App\Support\Bancos;
use App\Support\Comisiones;
use App\Support\Ubicaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Administración de la red comercial (solo el administrador de LocalPro)
class adminRedController extends Controller
{
    public const CLAVES_VALOR = [
        'red_nueva_venta', 'red_nueva_zona', 'red_nueva_ciudad', 'red_nueva_departamento', 'red_nueva_pais',
        'red_renov_venta', 'red_renov_zona', 'red_renov_ciudad', 'red_renov_departamento', 'red_renov_pais',
    ];

    public function index(Request $request)
    {
        $gente = Asesor::with('user:id,name,email,telefono')->withCount('tiendas')
            ->where('estado', '<>', 'capacitacion')
            ->when($request->departamento, fn ($q, $d) => $q->where('departamento', $d))
            ->orderByRaw("FIELD(cargo, 'lider_pais', 'lider_departamento', 'lider_ciudad', 'lider_zona', 'asesor')")->get();
        $ids = $gente->pluck('id');
        $negocios = Tienda::with('plan:id,nombre')->whereIn('asesor_id', $ids)->orderBy('nombre')->get()->groupBy('asesor_id');

        // De quién cuelga cada uno en el mapa: su líder; si no tiene líder (queda directo con LocalPro),
        // la persona que lo inscribió con su enlace, para que se vea a quiénes metió cada asesor
        $enMapa = $ids->flip();
        $arriba = [];
        foreach ($gente as $a) {
            $p = $a->padre_id && isset($enMapa[$a->padre_id]) ? $a->padre_id
                : ($a->reclutado_por && isset($enMapa[$a->reclutado_por]) ? $a->reclutado_por : null);
            $arriba[$a->id] = $p;
        }
        // Sin ciclos: si subiendo se vuelve a la misma persona, queda arriba con LocalPro
        foreach (array_keys($arriba) as $id) {
            $vistos = [$id => true];
            for ($x = $arriba[$id]; $x !== null; $x = $arriba[$x] ?? null) {
                if (isset($vistos[$x])) { $arriba[$id] = null; break; }
                $vistos[$x] = true;
            }
        }

        return view('admin.red.index', [
            'raices' => $gente->filter(fn ($a) => $arriba[$a->id] === null),
            'hijos' => $gente->filter(fn ($a) => $arriba[$a->id] !== null)->groupBy(fn ($a) => $arriba[$a->id]),
            'negocios' => $negocios,
            'lideres' => Asesor::with('user:id,name')->whereIn('cargo', ['lider_pais', 'lider_departamento', 'lider_ciudad', 'lider_zona'])->where('activo', true)->get(),
            'departamentos' => Asesor::whereNotNull('departamento')->distinct()->orderBy('departamento')->pluck('departamento'),
            'departamento' => $request->departamento,
            'kpi' => [
                'gente' => Asesor::where('activo', true)->count(),
                'negocios' => Tienda::whereNotNull('asesor_id')->count(),
                'sinAsesor' => Tienda::sinAsignar()->count(),
                'pendiente' => (float) Comision::where('estado', 'pendiente')->sum('monto'),
            ],
            'valores' => Comisiones::valores(),
            'candidatos' => \App\Support\RedComercial::candidatos(),
            'metas' => \App\Support\RedComercial::metas(),
            'salario' => (float) \App\Models\Ajuste::valor('red_salario_minimo', 1750000),
            'pendientes' => Asesor::with(['user:id,name', 'reclutador.user:id,name'])->where('estado', 'pendiente')->latest()->get(),
            'enCurso' => Asesor::with(['user:id,name,telefono', 'reclutador.user:id,name'])->where('estado', 'capacitacion')->latest()->get(),
            'certificados' => Asesor::whereNotNull('curso_aprobado_at')->count(),
            'sinCurso' => Asesor::where('activo', true)->where('estado', 'activo')->whereNull('curso_aprobado_at')->count(),
        ]);
    }

    // Aprobar el ascenso que el sistema sugiere
    public function ascender(Asesor $asesor)
    {
        $antes = $asesor->cargo_nombre;
        $nuevo = \App\Support\RedComercial::ascender($asesor);
        return back()->with('success', $asesor->nombre . ' subió de ' . $antes . ' a ' . Asesor::CARGOS[$nuevo][0] . '. 🎉');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos += $request->validate([
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8',
        ], ['email.unique' => 'Ya existe un usuario con ese correo.', 'password.min' => 'La contraseña debe tener mínimo 8 caracteres.']);

        DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'password' => Hash::make($datos['password']),
                'tienda_id' => null,
            ]);
            Asesor::create([
                'user_id' => $user->id,
                'cargo' => $datos['cargo'],
                'padre_id' => $datos['padre_id'] ?? null,
                'pais' => $datos['pais'],
                'departamento' => $datos['departamento'] ?? null,
                'ciudad' => $datos['ciudad'] ?? null,
                'zona' => $datos['zona'] ?? null,
                'documento' => $datos['documento'] ?? null,
                'banco' => $datos['banco'] ?? null,
                'codigo' => Asesor::nuevoCodigo($datos['name']),
                'activo' => true,
                'estado' => 'activo',
                'aprobado_at' => now(),
            ]);
            \App\Support\RedComercial::historial(Asesor::where('user_id', $user->id)->first(), 'inscrito', 'Inscrito por el administrador como ' . Asesor::CARGOS[$datos['cargo']][0]);
        });

        return back()->with('success', $datos['name'] . ' quedó inscrito en la red. Ya puede entrar con su correo y contraseña.');
    }

    public function show(Asesor $asesor)
    {
        $asesor->load('user', 'padre.user');
        return view('admin.red.show', [
            'a' => $asesor,
            'negocios' => Tienda::with('plan:id,nombre')->where('asesor_id', $asesor->id)->orderBy('nombre')->get(),
            'equipo' => Asesor::with('user:id,name')->withCount('tiendas')->where('padre_id', $asesor->id)->get(),
            'comisiones' => Comision::with('tienda:id,nombre')->where('asesor_id', $asesor->id)->latest()->take(30)->get(),
            'pendiente' => (float) Comision::where('asesor_id', $asesor->id)->where('estado', 'pendiente')->sum('monto'),
            'lideres' => Asesor::with('user:id,name')->whereIn('cargo', ['lider_pais', 'lider_departamento', 'lider_ciudad', 'lider_zona'])
                ->whereNotIn('id', $asesor->idsRed())->get(),
            'sinAsesor' => Tienda::sinAsignar()->orderBy('nombre')->get(['id', 'nombre']),
            'progreso' => \App\Support\RedComercial::progreso($asesor),
            'historial' => DB::table('asesor_historial')->where('asesor_id', $asesor->id)->latest('id')->take(20)->get(),
            'reclutador' => $asesor->reclutador()->with('user:id,name')->first(),
        ]);
    }

    public function update(Request $request, Asesor $asesor)
    {
        $datos = $this->validar($request, $asesor);
        $datos += $request->validate([
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($asesor->user_id)],
            'password' => 'nullable|string|min:8',
            'activo' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($datos, $asesor, $request) {
            $u = $asesor->user;
            $u->fill(['name' => $datos['name'], 'email' => $datos['email'], 'telefono' => $datos['telefono'] ?? null]);
            if (!empty($datos['password'])) {
                $u->password = Hash::make($datos['password']);
            }
            $u->save();
            $asesor->update([
                'cargo' => $datos['cargo'],
                'padre_id' => $datos['padre_id'] ?? null,
                'pais' => $datos['pais'],
                'departamento' => $datos['departamento'] ?? null,
                'ciudad' => $datos['ciudad'] ?? null,
                'zona' => $datos['zona'] ?? null,
                'documento' => $datos['documento'] ?? null,
                'banco' => $datos['banco'] ?? null,
                'activo' => $request->boolean('activo'),
                'estado' => $request->boolean('activo') ? 'activo' : ($asesor->estado === 'pendiente' ? 'pendiente' : 'activo'),
            ]);
            if ($asesor->wasChanged('cargo')) \App\Support\RedComercial::historial($asesor, 'cambio_cargo', 'Cargo cambiado a ' . $asesor->cargo_nombre);
            if ($asesor->wasChanged('padre_id')) \App\Support\RedComercial::historial($asesor, 'cambio_lider', 'Nuevo líder: ' . ($asesor->padre?->nombre ?? 'LocalPro'));
            if ($asesor->wasChanged('activo')) \App\Support\RedComercial::historial($asesor, $asesor->activo ? 'activado' : 'inactivo', null);
        });

        return back()->with('success', 'Datos de ' . $datos['name'] . ' guardados.');
    }

    // Pasar un negocio a un asesor, a LocalPro (venta directa del súper administrador) o dejarlo sin asignar
    public function asignar(Request $request, Tienda $tienda)
    {
        $request->validate(['asesor_id' => ['nullable', function ($attr, $v, $fail) {
            if ($v !== 'localpro' && !Asesor::whereKey($v)->exists()) $fail('Asesor no válido.');
        }]]);
        $valor = $request->input('asesor_id');

        if ($valor === 'localpro') {
            $tienda->forceFill(['asesor_id' => null, 'venta_directa_id' => $request->user()->id])->save();
            return back()->with('success', $tienda->nombre . ' quedó con LocalPro · ' . $request->user()->name . '.');
        }
        if ($valor) {
            $tienda->forceFill(['asesor_id' => (int) $valor, 'venta_directa_id' => null])->save();
            return back()->with('success', $tienda->nombre . ' quedó con ' . Asesor::find($valor)->nombre . '.');
        }
        $tienda->forceFill(['asesor_id' => null, 'venta_directa_id' => null])->save();
        return back()->with('success', $tienda->nombre . ' quedó sin asesor.');
    }

    public function comisiones(Request $request)
    {
        $filtro = fn ($q) => $q->when($request->estado ?? 'pendiente', fn ($q, $e) => $e === 'todas' ? $q : $q->where('estado', $e))
            ->when($request->asesor, fn ($q, $a) => $q->where('asesor_id', $a));

        return view('admin.red.comisiones', [
            'lista' => $filtro(Comision::with(['asesor.user:id,name', 'tienda:id,nombre']))->latest()->paginate(40)->withQueryString(),
            'porAsesor' => Comision::with('asesor.user:id,name')->where('estado', 'pendiente')
                ->selectRaw('asesor_id, SUM(monto) as total, COUNT(*) as n')->groupBy('asesor_id')->orderByDesc('total')->get(),
            'asesores' => Asesor::with('user:id,name')->get(),
            'estado' => $request->estado ?? 'pendiente',
            'asesor' => $request->asesor,
            'valores' => Comisiones::valores(),
        ]);
    }

    // Marcar comisiones como pagadas (las elegidas o todas las pendientes de un asesor)
    public function pagar(Request $request)
    {
        $datos = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'asesor_id' => 'nullable|integer|exists:asesores,id',
            'nota' => 'nullable|string|max:255',
        ]);
        $q = Comision::where('estado', 'pendiente');
        if (!empty($datos['asesor_id'])) {
            $q->where('asesor_id', $datos['asesor_id']);
        } elseif (!empty($datos['ids'])) {
            $q->whereIn('id', $datos['ids']);
        } else {
            return back()->withErrors('Elija las comisiones a pagar.');
        }
        $total = (float) (clone $q)->sum('monto');
        $n = $q->update(['estado' => 'pagada', 'pagada_at' => now(), 'nota' => $datos['nota'] ?? null]);
        return back()->with('success', $n . ' comisión(es) marcadas como pagadas por $' . number_format($total, 0, ',', '.') . '.');
    }

    public function anular(Comision $comision)
    {
        abort_if($comision->estado === 'pagada', 422, 'Ya está pagada.');
        $comision->update(['estado' => 'anulada']);
        return back()->with('success', 'Comisión anulada.');
    }

    public function valores(Request $request)
    {
        $reglas = [];
        foreach (array_merge(['red_valor_plan'], self::CLAVES_VALOR) as $k) {
            $reglas[$k] = 'required|numeric|min:0|max:100000000';
        }
        $reglas['red_meta_negocios'] = 'required|integer|min:1|max:1000';
        $reglas['red_meta_asesores'] = 'required|integer|min:0|max:1000';
        $reglas['red_salario_minimo'] = 'nullable|numeric|min:0|max:100000000';
        $datos = $request->validate($reglas);
        $suma = fn ($t) => collect($datos)->except(['red_meta_negocios', 'red_meta_asesores', 'red_salario_minimo'])->filter(fn ($v, $k) => str_starts_with($k, 'red_' . $t . '_'))->sum();
        if ($suma('nueva') > $datos['red_valor_plan'] || $suma('renov') > $datos['red_valor_plan']) {
            return back()->withErrors(['red_valor_plan' => 'Las comisiones suman más que el valor del plan anual.']);
        }
        foreach ($datos as $k => $v) {
            Ajuste::guardar($k, $v);
        }
        return back()->with('success', 'Valores de comisión guardados. Aplican a los pagos que lleguen de ahora en adelante.');
    }

    private function validar(Request $request, ?Asesor $asesor = null): array
    {
        $datos = $request->validate([
            'name' => 'required|string|max:80',
            'telefono' => 'nullable|string|max:20',
            'cargo' => ['required', Rule::in(array_keys(Asesor::CARGOS))],
            'padre_id' => 'nullable|integer|exists:asesores,id',
            'documento' => 'nullable|string|max:20',
            'zona' => 'nullable|string|max:60',
            'banco' => 'nullable|string|max:120',
            'pais' => 'required|string|size:2',
        ] + Bancos::reglas() + Ubicaciones::reglas($request->input('pais'), $request->input('departamento'), false), Ubicaciones::mensajes() + [
            'name.required' => 'Escriba el nombre.',
        ]);

        if (!empty($datos['padre_id'])) {
            $padre = Asesor::find($datos['padre_id']);
            $nivel = Asesor::CARGOS[$datos['cargo']][2];
            if ($padre->nivel <= $nivel) {
                throw \Illuminate\Validation\ValidationException::withMessages(['padre_id' => 'El líder debe tener un cargo más alto (' . $padre->nombre . ' es ' . $padre->cargo_nombre . ').']);
            }
            // El líder de zona tiene máximo 10 asesores
            if ($padre->cargo === 'lider_zona' && $datos['cargo'] === 'asesor'
                && (!$asesor || (int) $asesor->padre_id !== (int) $padre->id || $asesor->cargo !== 'asesor')) {
                $tiene = Asesor::where('padre_id', $padre->id)->where('cargo', 'asesor')->where('activo', true)->count();
                if ($tiene >= Asesor::MAX_ASESORES_ZONA) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['padre_id' => $padre->nombre . ' ya tiene ' . Asesor::MAX_ASESORES_ZONA . ' asesores (el máximo por zona). Asígnelo a otro líder de zona o cree una zona nueva.']);
                }
            }
            if ($asesor && in_array($padre->id, $asesor->idsRed(), true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['padre_id' => 'No puede quedar debajo de alguien de su propio equipo.']);
            }
        }
        if ($datos['cargo'] === 'lider_pais') {
            $datos['padre_id'] = null; // el líder de país responde directamente a LocalPro
        }
        $datos['banco'] = Bancos::componer($request);
        return $datos;
    }
}
