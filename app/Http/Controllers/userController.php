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
        $this->middleware('permission:editar-user', ['only' => ['edit', 'update', 'permisos', 'guardarPermisos', 'compartirAccesos']]);
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
                'telefono' => self::whatsapp($request->telefono),
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
            $datos = ['name' => $request->name, 'email' => $request->email, 'telefono' => self::whatsapp($request->telefono)];
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

    // Compartir accesos por WhatsApp: se le pone una clave nueva al trabajador y se arma el mensaje
    // con el link para descargar la app. La clave solo se muestra una vez (en el mensaje).
    public function compartirAccesos(Request $request, User $user)
    {
        $this->deMiTienda($user);
        abort_if((int) $user->id === (int) Auth::id() || $user->es_superadmin, 422, 'No se pueden compartir los accesos de este usuario.');

        $datos = $request->validate([
            'telefono' => ['nullable', 'string', 'max:20'],
            'clave' => ['nullable', 'string', 'min:8', 'max:60'],
        ], [], ['clave' => 'clave', 'telefono' => 'WhatsApp']);

        $clave = trim((string) ($datos['clave'] ?? '')) ?: self::claveLegible();
        // Si escriben un número nuevo se usa (y se guarda); si no, el que ya tiene el usuario
        $tel = self::whatsapp($datos['telefono'] ?? null) ?? self::whatsapp($user->telefono) ?? '';

        $user->password = Hash::make($clave);
        if ($tel !== '') {
            $user->telefono = $tel;
        }
        $user->save();

        $tienda = TiendaActual::tienda()?->nombre ?: 'Su negocio';
        $link = route('app.descargar', ['email' => $user->email]);
        $mensaje = "Hola " . strtok($user->name, ' ') . " 👋\n"
            . "{$tienda} le dio acceso a LocalPro.\n\n"
            . "1️⃣ Descargue la app aquí:\n{$link}\n\n"
            . "2️⃣ Entre con estos datos:\n"
            . "Correo: {$user->email}\n"
            . "Clave: {$clave}\n\n"
            . "No comparta esta clave con nadie.";

        $resultado = [
            'nombre' => $user->name,
            'mensaje' => $mensaje,
            'url' => 'https://wa.me/' . $tel . '?text=' . rawurlencode($mensaje),
        ];
        // Desde la tabla se pide por JS para abrir WhatsApp de una vez
        if ($request->expectsJson()) {
            return response()->json($resultado);
        }
        return redirect()->route('users.index')->with('compartir', $resultado);
    }

    // Número de WhatsApp solo con dígitos; celular colombiano de 10 dígitos → con indicativo 57
    private static function whatsapp(?string $numero): ?string
    {
        $tel = preg_replace('/\D+/', '', (string) $numero);
        if (strlen($tel) === 10 && $tel[0] === '3') {
            $tel = '57' . $tel;
        }
        return $tel !== '' ? substr($tel, 0, 20) : null;
    }

    // Clave fácil de dictar: sin letras que se confunden (l, I, O, 0) y con números
    private static function claveLegible(): string
    {
        $letras = 'abcdefghjkmnpqrstuvwxyz';
        $c = 'Lp';
        for ($i = 0; $i < 4; $i++) {
            $c .= $letras[random_int(0, strlen($letras) - 1)];
        }
        return $c . random_int(1000, 9999);
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
