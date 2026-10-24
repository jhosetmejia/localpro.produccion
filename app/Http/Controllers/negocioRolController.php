<?php

namespace App\Http\Controllers;

use App\Models\NegocioRol;
use App\Models\PedidoEstacion;
use App\Models\User;
use App\Support\RolesNegocio;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Roles del negocio: el dueño o administrador crea sus roles (Caja, Mesero, Cocina…) y elige qué puede hacer cada uno
class negocioRolController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:editar-negocio');
    }

    public function index()
    {
        $roles = NegocioRol::withCount('users')->orderBy('orden')->orderBy('nombre')->get();
        $tienda = TiendaActual::tienda();
        return view('roles-negocio.index', [
            'roles' => $roles,
            'plantillas' => collect(RolesNegocio::PLANTILLAS)->filter(fn ($p, $k) => ($k !== 'puerta' || $tienda->mod_eventos) && ($tienda->mod_pedidos || !in_array('pedidos-preparar', $p[2], true) && $k !== 'mesero')),
            'personas' => User::where('tienda_id', $tienda->id)->orderBy('name')->get(),
            'estaciones' => $tienda->mod_pedidos ? PedidoEstacion::orderBy('orden')->get()->keyBy('id') : collect(),
        ]);
    }

    public function plantilla(Request $request)
    {
        $k = (string) $request->input('plantilla');
        abort_unless(isset(RolesNegocio::PLANTILLAS[$k]), 404);
        $rol = DB::transaction(fn () => RolesNegocio::desdePlantilla($k));
        return redirect()->route('roles-negocio.edit', $rol)->with('success', 'Rol "' . $rol->nombre . '" creado. Revise sus permisos y guárdelo.');
    }

    public function create()
    {
        return $this->form(new NegocioRol(['icono' => 'fa-user', 'permisos' => [], 'inicio' => 'panel']));
    }

    public function edit(NegocioRol $rol)
    {
        return $this->form($rol);
    }

    private function form(NegocioRol $rol)
    {
        $t = TiendaActual::tienda();
        return view('roles-negocio.form', [
            'rol' => $rol,
            'grupos' => RolesNegocio::disponibles(),
            'estaciones' => $t->mod_pedidos ? PedidoEstacion::orderBy('orden')->get() : collect(),
            'inicios' => collect(NegocioRol::INICIOS)->filter(fn ($v, $k) => $t->mod_pedidos || !in_array($k, ['mesero', 'caja', 'estacion'], true)),
        ]);
    }

    public function store(Request $request)
    {
        $rol = new NegocioRol(['orden' => (int) NegocioRol::max('orden') + 1]);
        $this->guardar($request, $rol);
        return redirect()->route('roles-negocio.index')->with('success', 'Rol "' . $rol->nombre . '" creado. Ahora asígnelo a sus empleados.');
    }

    public function update(Request $request, NegocioRol $rol)
    {
        $this->guardar($request, $rol);
        $n = $rol->users()->count();
        return redirect()->route('roles-negocio.index')->with('success', 'Rol "' . $rol->nombre . '" guardado' . ($n ? " y aplicado a {$n} persona" . ($n > 1 ? 's' : '') : '') . '.');
    }

    private function guardar(Request $request, NegocioRol $rol): void
    {
        $d = $request->validate([
            'nombre' => 'required|string|max:40',
            'icono' => 'nullable|string|max:40|regex:/^fa-[a-z0-9-]+$/',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|max:60',
            'estaciones' => 'nullable|array',
            'estaciones.*' => 'integer',
            'inicio' => 'nullable|in:' . implode(',', array_keys(NegocioRol::INICIOS)),
        ], ['nombre.required' => 'Escriba el nombre del rol (ej. Mesero).']);

        $existe = NegocioRol::where('nombre', $d['nombre'])->when($rol->exists, fn ($q) => $q->where('id', '<>', $rol->id))->exists();
        if ($existe) throw \Illuminate\Validation\ValidationException::withMessages(['nombre' => 'Ya tiene un rol con ese nombre.']);

        $estaciones = PedidoEstacion::whereIn('id', (array) ($d['estaciones'] ?? []))->pluck('id')->all();
        DB::transaction(function () use ($rol, $d, $estaciones) {
            $rol->fill([
                'nombre' => trim($d['nombre']), 'icono' => $d['icono'] ?? 'fa-user',
                'permisos' => RolesNegocio::limpiar((array) ($d['permisos'] ?? [])),
                'estaciones' => $estaciones, 'inicio' => $d['inicio'] ?? 'panel',
            ])->save();
            RolesNegocio::sincronizar($rol);
        });
    }

    public function destroy(NegocioRol $rol)
    {
        if ($rol->users()->exists()) {
            return back()->withErrors('Este rol lo tienen ' . $rol->users()->count() . ' persona(s). Cámbieles el rol primero.');
        }
        $rol->delete();
        return redirect()->route('roles-negocio.index')->with('success', 'Rol eliminado.');
    }

    // Asignar rápido el rol a una persona desde la lista
    public function asignar(Request $request)
    {
        $d = $request->validate(['user_id' => 'required|integer', 'rol' => 'required|string|max:20']);
        $u = User::where('tienda_id', TiendaActual::id())->findOrFail($d['user_id']);
        abort_if($u->es_superadmin || $u->id === auth()->id(), 422, 'A esta persona no se le puede cambiar el rol aquí.');
        DB::transaction(function () use ($u, $d) {
            if (in_array($d['rol'], ['dueno', 'vendedor'], true)) {
                RolesNegocio::asignar($u, null);
                $u->syncRoles([$d['rol']]);
                $u->syncPermissions([]);
            } else {
                $rol = NegocioRol::findOrFail((int) ltrim($d['rol'], 'r'));
                RolesNegocio::asignar($u, $rol);
            }
        });
        return back()->with('success', $u->name . ' ahora es ' . RolesNegocio::etiqueta($u->fresh()) . '.');
    }
}
