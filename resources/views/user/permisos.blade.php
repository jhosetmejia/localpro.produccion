@extends('layouts.app')

@section('title','Permisos del vendedor')

@push('css')
<style>
    .perm-grupo { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; height: 100%; background: #fff; }
    .perm-grupo h6 { font-weight: 700; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .perm-grupo .form-check { margin-bottom: 6px; }
    .perm-grupo .form-check-label { cursor: pointer; }
    .perm-base { font-size: .9rem; color: #555; }
</style>
@endpush

@section('content')
@include('layouts.partials.alert')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Permisos de {{ $user->name }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Usuarios</a></li>
        <li class="breadcrumb-item active">Permisos</li>
    </ol>

    <div class="alert alert-light border perm-base">
        <i class="fa-solid fa-cash-register me-1"></i>
        Como vendedor ya puede: vender en caja, ver productos y existencias, ver y crear clientes.
        Marque abajo lo que quiera que <strong>además</strong> pueda hacer. Nunca podrá manejar usuarios, los datos del negocio ni el plan.
    </div>

    <form action="{{ route('users.permisos.guardar', ['user' => $user]) }}" method="post">
        @method('PUT')
        @csrf

        @if (empty($grupos))
            <div class="alert alert-warning">No hay permisos extra para asignar.</div>
        @else
        <div class="row g-3">
            @foreach ($grupos as $grupo => $permisos)
            <div class="col-md-6 col-xl-4">
                <div class="perm-grupo" data-grupo>
                    <h6>
                        <span>{{ $grupo }}</span>
                        <span class="form-check form-switch m-0" title="Marcar todo">
                            <input class="form-check-input" type="checkbox" data-todo>
                        </span>
                    </h6>
                    @foreach ($permisos as $p => $txt)
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="permisos[]" value="{{ $p }}" id="p-{{ $p }}"
                            @checked(in_array($p, old('permisos', $actuales)))>
                        <label class="form-check-label" for="p-{{ $p }}">{{ $txt }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="d-flex gap-2 justify-content-center my-4">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Volver</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar permisos</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('[data-grupo]').forEach(function (g) {
    const todo = g.querySelector('[data-todo]');
    const items = g.querySelectorAll('input[name="permisos[]"]');
    const refrescar = () => { todo.checked = [...items].every(i => i.checked); };
    todo.addEventListener('change', () => { items.forEach(i => i.checked = todo.checked); });
    items.forEach(i => i.addEventListener('change', refrescar));
    refrescar();
});
</script>
@endpush
