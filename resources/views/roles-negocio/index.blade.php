@extends('layouts.app')

@section('title', 'Roles del negocio')

@push('css')
<style>
.rn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.8rem}
.rn-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:1rem 1.1rem;display:flex;flex-direction:column;gap:.45rem}
.rn-card .h{display:flex;align-items:center;gap:.6rem}
.rn-ic{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:#0b0d0c;color:#a6f34a;font-size:1.1rem;flex:none}
.rn-card b{font-size:1.05rem}
.rn-card small{color:#6b7570;font-weight:600}
.rn-chips{display:flex;flex-wrap:wrap;gap:.3rem}
.rn-chips span{background:#f1f5f2;border-radius:999px;padding:.15rem .55rem;font-size:.75rem;font-weight:700;color:#334039}
.rn-pl{display:flex;flex-wrap:wrap;gap:.45rem}
.rn-pl button{border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.86rem}
.rn-pl button:hover{border-color:#16b34a;background:#f0fdf4}
.rn-pl i{color:#16b34a;margin-right:.3rem}
.rn-u{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.6rem 0;border-bottom:1px solid #eef1ef}
.rn-u:last-child{border-bottom:0}
.rn-u .n{flex:1 1 200px;min-width:0}
.rn-u .n b{display:block}.rn-u .n small{color:#6b7570}
.rn-u select{max-width:240px}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-id-badge me-1"></i>Equipo</small><h1>Roles del <span>negocio</span></h1>
            <span class="text-muted fw-semibold">Cree los roles de su equipo (Caja, Mesero, Cocina…), marque qué puede hacer cada uno y asígnelos. Cambia un rol y se actualiza para todos los que lo tienen.</span></div>
        <div class="lp-quick"><a href="{{ route('roles-negocio.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Nuevo rol</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="lp-box mb-3">
        <h3><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Crear rápido desde una plantilla</h3>
        <form method="post" action="{{ route('roles-negocio.plantilla') }}" class="rn-pl">@csrf
            @foreach ($plantillas as $k => [$nom, $ic])
            <button name="plantilla" value="{{ $k }}"><i class="fa-solid {{ $ic }}"></i>{{ $nom }}</button>
            @endforeach
        </form>
        <small class="text-muted d-block mt-2">Las plantillas de estación (Cocina, Cafetería, Entradas, Paseos…) crean también su pantalla de pedidos.</small>
    </div>

    @if ($roles->isEmpty())
    <div class="lp-box text-center text-muted py-4">Aún no tiene roles propios. Use una plantilla o toque <b>Nuevo rol</b>.</div>
    @else
    <div class="rn-grid mb-3">
        @foreach ($roles as $r)
        <div class="rn-card">
            <div class="h"><div class="rn-ic"><i class="fa-solid {{ $r->icono ?: 'fa-user' }}"></i></div>
                <div><b>{{ $r->nombre }}</b><small class="d-block">{{ $r->users_count }} {{ $r->users_count === 1 ? 'persona' : 'personas' }} · {{ count((array) $r->permisos) }} permisos</small></div></div>
            <div class="rn-chips">
                @if ($r->tiene('pedidos-tomar'))<span>Toma pedidos</span>@endif
                @if ($r->tiene('pedidos-cobrar'))<span>Cobra pedidos</span>@endif
                @if ($r->tiene('pedidos-preparar'))<span>Estación: {{ $r->estacionIds() ? $estaciones->only($r->estacionIds())->pluck('nombre')->join(', ') : 'todas' }}</span>@endif
                @if ($r->tiene('usar-pos'))<span>Caja (POS)</span>@endif
                <span>Entra a: {{ \Illuminate\Support\Str::before(\App\Models\NegocioRol::INICIOS[$r->inicio ?: 'panel'] ?? 'Panel', ' (') }}</span>
            </div>
            <div class="d-flex gap-2 mt-auto">
                <a href="{{ route('roles-negocio.edit', $r) }}" class="btn btn-sm btn-dark flex-grow-1"><i class="fa-solid fa-pen me-1"></i>Permisos</a>
                <form method="post" action="{{ route('roles-negocio.destroy', $r) }}" data-confirm="¿Eliminar el rol {{ $r->nombre }}?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('delete')<button class="btn btn-sm btn-light"><i class="fa-solid fa-trash"></i></button></form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="lp-box">
        <h3><i class="fa-solid fa-users me-1"></i>¿Quién tiene cada rol?</h3>
        @foreach ($personas as $u)
        <div class="rn-u">
            <div class="n"><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div>
            @if ($u->es_superadmin || $u->id === auth()->id())
            <span class="badge bg-success">{{ \App\Support\RolesNegocio::etiqueta($u) }}</span>
            @else
            <form method="post" action="{{ route('roles-negocio.asignar') }}" class="d-flex gap-2">@csrf
                <input type="hidden" name="user_id" value="{{ $u->id }}">
                <select name="rol" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="dueno" @selected(!$u->negocio_rol_id && $u->hasRole('dueno'))>Dueño / administrador</option>
                    <option value="vendedor" @selected(!$u->negocio_rol_id && $u->hasRole('vendedor'))>Vendedor (caja)</option>
                    @foreach ($roles as $r)<option value="r{{ $r->id }}" @selected((int) $u->negocio_rol_id === $r->id)>{{ $r->nombre }}</option>@endforeach
                </select>
            </form>
            @endif
        </div>
        @endforeach
        <a href="{{ route('users.create') }}" class="btn btn-light mt-2"><i class="fa-solid fa-user-plus me-1"></i>Agregar persona</a>
    </div>
</div>
@endsection
