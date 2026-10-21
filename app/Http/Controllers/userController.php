<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\PermisosVendedor;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Cada dueño administra solo a las personas de su tienda (dueños y vendedores)
class userController extends Controller
{
    public const ROLES_TIENDA = ['dueno' => 'Dueño / administrador', 'vendedor' => 'Vendedor (caja)'];

    function __construct()
    {
        $this->middleware('permission:ver-user|crear-user|editar-user|eliminar-user', ['only' => ['index']]);
        $this->middleware('permission:crear-user', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-user', ['only' => ['edit', 'update', 'permisos', 'guardarPermisos']]);
        $this->middleware('permission:eliminar-user', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::where('tienda_id', TiendaActual::id())->orderBy('name')->get();
        return view('user.index', compact('users'));
    }

    public function create()
    {
        if ($msg = TiendaActual::limitePlan('usuarios')) {
            return redirect()->route('users.index')->withErrors($msg);
        }
        return view('user.create', ['roles' => $this->roles()]);
    }

    public function store(StoreUserRequest $request)
    {
        if ($msg = TiendaActual::limitePlan('usuarios')) {
            return back()->withInput()->withErrors($msg);
        }
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tienda_id' => TiendaActual::id(),
            ]);
            if (str_starts_with($request->role, 'r')) {
                \App\Support\RolesNegocio::asignar($user, \App\Models\NegocioRol::findOrFail((int) substr($request->role, 1)));
            } else {
                $user->assignRole($request->role);
            }
        });

        return redirect()->route('users.index')->with('success', 'Usuario registrado');
    }

    public function show(string $id)
    {
        return redirect()->route('users.index');
    }

    public function edit(User $user)
    {
        $this->deMiTienda($user);
        return view('user.edit', ['user' => $user, 'roles' => $this->roles()]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->deMiTienda($user);

        DB::transaction(function () use ($request, $user) {
            $datos = ['name' => $request->name, 'email' => $request->email];
            if (!empty($request->password)) {
                $datos['password'] = Hash::make($request->password);
            }
            $user->update($datos);
            // El superadministrador conserva su rol de plataforma
            if (!$user->es_superadmin) {
                if (str_starts_with($request->role, 'r')) {
                    // Rol propio del negocio (Mesero, Cocina…): sus permisos vienen del rol
                    \App\Support\RolesNegocio::asignar($user, \App\Models\NegocioRol::findOrFail((int) substr($request->role, 1)));
                } else {
                    $teniaRolPropio = (bool) $user->negocio_rol_id;
                    \App\Support\RolesNegocio::asignar($user, null);
                    $user->syncRoles([$request->role]);
                    // El dueño ya tiene todo por su rol: se limpian los permisos extra
                    if ($request->role !== 'vendedor' || $teniaRolPropio) $user->syncPermissions([]);
                }
            }
        });

        return redirect()->route('users.index')->with('success', 'Usuario editado');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $this->deMiTienda($user);
        abort_if((int) $user->id === (int) Auth::id(), 422, 'No puede eliminar su propio usuario.');
        abort_if($user->es_superadmin, 422, 'Este usuario no se puede eliminar.');

        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado');
    }

    // Permisos extra del vendedor: el dueño elige qué más puede hacer (crear productos, etc.)
    public function permisos(User $user)
    {
        $this->deMiTienda($user);
        if (!$user->hasRole('vendedor') || $user->es_superadmin) {
            return redirect()->route('users.index')->withErrors('Los permisos extra solo se asignan a vendedores. El dueño ya tiene todos.');
        }
        return view('user.permisos', [
            'user' => $user,
            'grupos' => PermisosVendedor::disponibles(),
            'actuales' => $user->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function guardarPermisos(Request $request, User $user)
    {
        $this->deMiTienda($user);
        abort_unless($user->hasRole('vendedor') && !$user->es_superadmin, 404);

        $elegidos = PermisosVendedor::completar((array) $request->input('permisos', []));
        $user->syncPermissions($elegidos);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $n = count($elegidos);
        return redirect()->route('users.index')->with('success', $n
            ? "Permisos de {$user->name} guardados ({$n} extra)"
            : "{$user->name} quedó solo con los permisos de caja");
    }

    // Solo los roles de tienda, con nombre legible
    private function roles()
    {
        $base = Role::whereIn('name', array_keys(self::ROLES_TIENDA))->get()
            ->each(fn ($r) => $r->etiqueta = self::ROLES_TIENDA[$r->name]);
        // Roles propios del negocio (Caja, Mesero, Cocina…)
        $propios = \Illuminate\Support\Facades\Schema::hasTable('negocio_roles')
            ? \App\Models\NegocioRol::orderBy('orden')->orderBy('nombre')->get()->map(fn ($r) => (object) ['name' => 'r' . $r->id, 'etiqueta' => $r->nombre])
            : collect();
        return collect($base->all())->concat($propios);
    }

    private function deMiTienda(User $user): void
    {
        abort_unless((int) $user->tienda_id === (int) TiendaActual::id(), 404);
    }
}
