@extends('layouts.app')

@section('title', $rol->exists ? 'Rol ' . $rol->nombre : 'Nuevo rol')

@push('css')
<style>
.rf-g{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.9rem 1rem;margin-bottom:.7rem}
.rf-g h4{font-size:.95rem;font-weight:900;margin:0 0 .5rem;display:flex;align-items:center;gap:.5rem}
.rf-g h4 button{margin-left:auto;font-size:.75rem}
.rf-p{display:flex;gap:.6rem;align-items:flex-start;padding:.35rem 0}
.rf-p input{margin-top:.2rem;width:1.15rem;height:1.15rem;flex:none}
.rf-p label{font-weight:600;cursor:pointer}
.rf-ics{display:flex;flex-wrap:wrap;gap:.35rem}
.rf-ics label{width:42px;height:42px;border-radius:12px;border:1.5px solid #e3ebe6;display:grid;place-items:center;cursor:pointer;font-size:1.05rem}
.rf-ics input{display:none}
.rf-ics input:checked + i{color:#a6f34a}
.rf-ics label:has(input:checked){background:#0b0d0c;border-color:#0b0d0c}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-id-badge me-1"></i>Roles del negocio</small><h1>{{ $rol->exists ? $rol->nombre : 'Nuevo rol' }}</h1>
            <span class="text-muted fw-semibold">Marque lo que puede hacer quien tenga este rol. Al guardar se aplica a todas las personas con este rol.</span></div>
        <div class="lp-quick"><a href="{{ route('roles-negocio.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Roles</a></div>
    </div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ $rol->exists ? route('roles-negocio.update', $rol) : route('roles-negocio.store') }}">
        @csrf @if ($rol->exists) @method('put') @endif
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="lp-box mb-3">
                    <label class="form-label fw-bold">Nombre del rol</label>
                    <input name="nombre" class="form-control form-control-lg mb-3" maxlength="40" required value="{{ old('nombre', $rol->nombre) }}" placeholder="Ej. Mesero, Cocina, Entradas">
                    <label class="form-label fw-bold">Ícono</label>
                    <div class="rf-ics mb-3">
                        @foreach (['fa-user', 'fa-cash-register', 'fa-bell-concierge', 'fa-fire-burner', 'fa-mug-hot', 'fa-martini-glass', 'fa-ticket', 'fa-horse', 'fa-tractor', 'fa-user-tie', 'fa-broom', 'fa-motorcycle', 'fa-ice-cream', 'fa-drumstick-bite', 'fa-boxes-stacked'] as $ic)
                        <label title="{{ $ic }}"><input type="radio" name="icono" value="{{ $ic }}" @checked(old('icono', $rol->icono ?: 'fa-user') === $ic)><i class="fa-solid {{ $ic }}"></i></label>
                        @endforeach
                    </div>
                    <label class="form-label fw-bold">Al entrar ve</label>
                    <select name="inicio" class="form-select">
                        @foreach ($inicios as $k => $txt)<option value="{{ $k }}" @selected(old('inicio', $rol->inicio ?: 'panel') === $k)>{{ $txt }}</option>@endforeach
                    </select>
                </div>
                @if ($estaciones->isNotEmpty())
                <div class="lp-box mb-3">
                    <h3 class="mb-1"><i class="fa-solid fa-kitchen-set me-1"></i>Estaciones que atiende</h3>
                    <small class="text-muted d-block mb-2">Solo si tiene el permiso de pantalla de estación. Si no marca ninguna, ve todas.</small>
                    @foreach ($estaciones as $e)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="estaciones[]" value="{{ $e->id }}" id="e{{ $e->id }}" @checked(in_array($e->id, old('estaciones', $rol->estacionIds())))><label class="form-check-label fw-semibold" for="e{{ $e->id }}"><i class="fa-solid {{ $e->icono ?: 'fa-kitchen-set' }} me-1"></i>{{ $e->nombre }}</label></div>
                    @endforeach
                </div>
                @endif
                <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar rol</button>
            </div>
            <div class="col-lg-8">
                @php $marcados = old('permisos', (array) $rol->permisos); @endphp
                @foreach ($grupos as $grupo => $permisos)
                <div class="rf-g">
                    <h4>{{ $grupo }} <button type="button" class="btn btn-light btn-sm" data-todos>Marcar todo</button></h4>
                    @foreach ($permisos as $p => $txt)
                    <div class="rf-p"><input type="checkbox" class="form-check-input" name="permisos[]" value="{{ $p }}" id="p-{{ $p }}" @checked(in_array($p, $marcados, true))><label for="p-{{ $p }}">{{ $txt }}</label></div>
                    @endforeach
                </div>
                @endforeach
                <small class="text-muted">Lo de administración (usuarios, datos del negocio, plan y roles) queda solo para el dueño o administrador.</small>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('[data-todos]').forEach(function (b) {
    b.addEventListener('click', function () {
        var cs = b.closest('.rf-g').querySelectorAll('input[type=checkbox]'), todos = [].every.call(cs, function (c) { return c.checked; });
        cs.forEach(function (c) { c.checked = !todos; });
    });
});
</script>
@endpush
