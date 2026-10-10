<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Cada dueño administra solo a las personas de su tienda (dueños y vendedores)
class userController extends Controller
{
    public const ROLES_TIENDA = ['dueno' => 'Dueño', 'vendedor' => 'Vendedor'];

    function __construct()
    {
        $this->middleware('permission:ver-user|crear-user|editar-user|eliminar-user', ['only' => ['index']]);
        $this->middleware('permission:crear-user', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-user', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::where('tienda_id', TiendaActual::id())->orderBy('name')->get();
        return view('user.index', compact('users'));
    }

    public function create()
    {
        return view('user.create', ['roles' => $this->roles()]);
    }

    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tienda_id' => TiendaActual::id(),
            ]);
            $user->assignRole($request->role);
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
                $user->syncRoles([$request->role]);
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

    // Solo los roles de tienda, con nombre legible
    private function roles()
    {
        return Role::whereIn('name', array_keys(self::ROLES_TIENDA))->get()
            ->each(fn ($r) => $r->etiqueta = self::ROLES_TIENDA[$r->name]);
    }

    private function deMiTienda(User $user): void
    {
        abort_unless((int) $user->tienda_id === (int) TiendaActual::id(), 404);
    }
}
