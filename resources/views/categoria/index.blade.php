@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="lp-dash" style="max-width:860px;margin:0 auto">
    <div class="lp-hello">
        <div><small>Inventario</small><h1>Categorías</h1><span class="text-muted fw-semibold">Agrupe sus productos (ej. Camisetas, Jeans, Chaquetas) para encontrarlos y filtrarlos fácil.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    @can('crear-categoria')
    <form method="post" action="{{ route('categorias.store') }}" class="lp-box mb-3">
        @csrf
        <h3>Nueva categoría</h3>
        <div class="d-flex gap-2 flex-wrap">
            <input name="nombre" class="form-control flex-grow-1" style="min-width:180px;flex-basis:200px" placeholder="Ej: Chaquetas" maxlength="60" value="{{ old('nombre') }}" required>
            <input name="descripcion" class="form-control flex-grow-1" style="min-width:180px;flex-basis:200px" placeholder="Descripción (opcional)" maxlength="255" value="{{ old('descripcion') }}">
            <button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Agregar</button>
        </div>
    </form>
    @endcan

    <div class="lp-box">
        <h3>Sus categorías <small class="text-muted fw-semibold">{{ $categorias->count() }}</small></h3>
        @if ($categorias->count() > 6)
        <input type="search" class="form-control mb-2" placeholder="Buscar categoría..." oninput="var t=this.value.toLowerCase();document.querySelectorAll('.cat-item').forEach(function(li){li.style.display=li.dataset.n.indexOf(t)>-1?'':'none'})">
        @endif
        <ul class="lp-list">
            @forelse ($categorias as $c)
            @php $activa = $c->caracteristica->estado == 1; @endphp
            <li class="cat-item {{ $activa ? '' : 'opacity-50' }}" data-n="{{ mb_strtolower($c->caracteristica->nombre) }}">
                <div class="av"><i class="fa-solid fa-tag"></i></div>
                <div class="t">
                    <b>{{ $c->caracteristica->nombre }}</b>
                    <small>{{ $c->productos_count }} {{ $c->productos_count === 1 ? 'producto' : 'productos' }}{{ $c->caracteristica->descripcion ? ' · ' . $c->caracteristica->descripcion : '' }}{{ $activa ? '' : ' · Oculta' }}</small>
                </div>
                <div class="d-flex gap-1 flex-none">
                    @can('editar-categoria')
                    <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#ed{{ $c->id }}" title="Editar"><i class="fa-solid fa-pen"></i></button>
                    @endcan
                    @can('eliminar-categoria')
                    <form method="post" action="{{ route('categorias.destroy', $c) }}" onsubmit="return confirm('{{ $activa ? '¿Ocultar la categoría ' . e($c->caracteristica->nombre) . '? Los productos no se borran.' : '¿Volver a mostrar esta categoría?' }}')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm {{ $activa ? 'btn-danger' : 'btn-success' }}" title="{{ $activa ? 'Ocultar' : 'Restaurar' }}"><i class="fa-solid {{ $activa ? 'fa-eye-slash' : 'fa-rotate-left' }}"></i></button>
                    </form>
                    @endcan
                </div>
            </li>
            @empty
            <li class="lp-empty">Aún no tiene categorías. Cree la primera arriba.</li>
            @endforelse
        </ul>
    </div>
</div>

@can('editar-categoria')
@foreach ($categorias as $c)
<div class="modal fade" id="ed{{ $c->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="{{ route('categorias.update', $c) }}" style="border-radius:22px">
            @csrf @method('PATCH')
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Editar categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body pt-0">
                <label class="form-label">Nombre</label>
                <input name="nombre" class="form-control mb-3" value="{{ $c->caracteristica->nombre }}" maxlength="60" required>
                <label class="form-label">Descripción (opcional)</label>
                <input name="descripcion" class="form-control" value="{{ $c->caracteristica->descripcion }}" maxlength="255">
            </div>
            <div class="modal-footer border-0"><button class="btn btn-primary w-100">Guardar</button></div>
        </form>
    </div>
</div>
@endforeach
@endcan
@endsection
