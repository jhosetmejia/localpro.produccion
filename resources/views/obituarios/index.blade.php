@extends('layouts.app')

@section('title', 'Obituarios')

@push('css')
<style>
.ob-card{display:flex;gap:.8rem;align-items:center;background:#fff;border:1.5px solid #e3efe5;border-radius:18px;padding:.8rem;margin-bottom:.7rem}
.ob-card.fin{opacity:.6}
.ob-foto{width:64px;height:64px;border-radius:50%;object-fit:cover;flex:0 0 64px;background:#eef2f0;display:grid;place-items:center;color:#9aa8a0;font-size:1.6rem;overflow:hidden}
.ob-foto img{width:100%;height:100%;object-fit:cover}
.ob-card .tx{flex:1;min-width:0}
.ob-card .tx b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-transform:uppercase}
.ob-card .tx small{display:block;color:#6b7570}
.ob-sala{border:1.5px solid #e3efe5;border-radius:16px;padding:.7rem .8rem;margin-bottom:.6rem;background:#fff}
.ob-sala .top{display:flex;align-items:center;gap:.5rem}
.ob-sala code{font-size:.75rem;word-break:break-all;color:#11702f}
.ob-tabs a{font-weight:700}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Funeraria</small><h1>Obituarios <span>y salas</span></h1>
            <p class="text-muted mb-0">Cada sala tiene su pantalla. Cree el obituario, elija la sala y sale solo en el televisor.</p></div>
        <div class="lp-quick">
            <a href="{{ route('obituarios.plantillas') }}" class="btn btn-dark"><i class="fa-solid fa-palette me-1"></i>Plantillas ({{ $plantillas }})</a>
            <a href="{{ route('obituarios.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo obituario</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="lp-box">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                    <ul class="nav nav-pills ob-tabs">
                        @foreach (['activos' => 'En pantalla', 'finalizados' => 'Finalizados', 'todos' => 'Todos'] as $k => $txt)
                        <li class="nav-item"><a class="nav-link {{ $ver === $k ? 'active' : '' }}" href="{{ route('obituarios.index', ['ver' => $k]) }}">{{ $txt }}</a></li>
                        @endforeach
                    </ul>
                    <form class="ms-auto d-flex gap-1" method="get"><input type="hidden" name="ver" value="{{ $ver }}"><input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Buscar nombre"><button class="btn btn-sm btn-light"><i class="fa-solid fa-magnifying-glass"></i></button></form>
                </div>
                @forelse ($obituarios as $o)
                <div class="ob-card {{ $o->estado === 'finalizado' ? 'fin' : '' }}">
                    <a href="{{ route('obituarios.show', $o) }}" class="ob-foto">@if ($o->foto_url)<img src="{{ $o->foto_url }}" alt="">@else<i class="fa-solid fa-user"></i>@endif</a>
                    <div class="tx">
                        <b><a href="{{ route('obituarios.show', $o) }}" class="text-reset text-decoration-none">{{ $o->nombre }}</a></b>
                        <small>
                            @if ($o->sala)<i class="fa-solid fa-door-open"></i> {{ $o->sala->nombre }} · @endif
                            @if ($o->fecha_texto){{ $o->fecha_texto }}@if ($o->hora_texto) · {{ $o->hora_texto }}@endif · @endif
                            @if ($o->en_pantalla)<span class="text-success fw-bold">En pantalla</span>@elseif ($o->estado === 'activo')<span class="text-muted">Ya salió de la pantalla</span>@else<span class="text-muted">Finalizado</span>@endif
                        </small>
                    </div>
                    <div class="d-flex gap-1 flex-wrap justify-content-end">
                        <a class="btn btn-sm btn-light" href="{{ route('obituarios.show', $o) }}" title="Ver y descargar"><i class="fa-solid fa-eye"></i></a>
                        <a class="btn btn-sm btn-dark" href="{{ route('obituarios.edit', $o) }}" title="Editar"><i class="fa-solid fa-pen"></i></a>
                        <form method="post" action="{{ route('obituarios.estado', $o) }}">@csrf<button class="btn btn-sm btn-light" title="{{ $o->estado === 'activo' ? 'Finalizar (sacar de la pantalla)' : 'Volver a mostrar' }}"><i class="fa-solid {{ $o->estado === 'activo' ? 'fa-flag-checkered' : 'fa-rotate-left' }}"></i></button></form>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-dove fa-2x mb-2"></i>
                    <p class="mb-2">{{ $ver === 'activos' ? 'No hay obituarios en pantalla.' : 'No hay obituarios.' }}</p>
                    <a href="{{ route('obituarios.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Crear obituario</a>
                </div>
                @endforelse
                {{ $obituarios->links() }}
            </div>
        </div>

        <div class="col-lg-5">
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-door-open me-1"></i>Salas de velación</h3>
                <p class="small text-muted">Abra la dirección de cada sala en el navegador del televisor (Smart TV, TV Box o un computador) y déjela abierta: muestra el obituario de esa sala y se actualiza sola.</p>
                @forelse ($salas as $s)
                <div class="ob-sala">
                    <div class="top">
                        <b>{{ $s->nombre }}</b>
                        @if (!$s->activa)<span class="badge bg-secondary">Apagada</span>@elseif ($enSala->has($s->id))<span class="badge bg-success text-truncate" style="max-width:180px">{{ $enSala[$s->id]->nombre }}</span>@else<span class="badge bg-light text-muted">Libre</span>@endif
                        <div class="ms-auto d-flex gap-1">
                            <a class="btn btn-sm btn-light" target="_blank" href="{{ $s->url_pantalla }}" title="Abrir pantalla"><i class="fa-solid fa-tv"></i></a>
                            <button type="button" class="btn btn-sm btn-light" data-copiar="{{ $s->url_pantalla }}" title="Copiar dirección"><i class="fa-regular fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="collapse" data-bs-target="#sala-{{ $s->id }}" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        </div>
                    </div>
                    <code>{{ $s->url_pantalla }}</code>
                    <div class="collapse" id="sala-{{ $s->id }}">
                        <form method="post" action="{{ route('obituarios.salas.update', $s) }}" class="mt-2">
                            @csrf @method('put')
                            <div class="input-group input-group-sm mb-2"><input class="form-control" name="nombre" value="{{ $s->nombre }}" maxlength="60" required><button class="btn btn-primary">Guardar</button></div>
                            <input type="hidden" name="activa" value="0">
                            <div class="form-check form-switch small"><input class="form-check-input" type="checkbox" name="activa" value="1" id="act-{{ $s->id }}" @checked($s->activa)><label class="form-check-label" for="act-{{ $s->id }}">Sala en uso</label></div>
                            <div class="form-check small"><input class="form-check-input" type="checkbox" name="nueva_direccion" value="1" id="nd-{{ $s->id }}"><label class="form-check-label" for="nd-{{ $s->id }}">Cambiar la dirección de la pantalla (si alguien que no debe la tiene)</label></div>
                        </form>
                        <form method="post" action="{{ route('obituarios.salas.destroy', $s) }}" data-confirm="¿Eliminar la sala «{{ $s->nombre }}»?" data-confirm-ok="Sí, eliminar" data-peligro class="mt-1">@csrf @method('delete')<button class="btn btn-sm btn-link text-danger p-0">Eliminar sala</button></form>
                    </div>
                </div>
                @empty
                <p class="text-muted small">Aún no tiene salas. Cree la primera (ej: "Sala 1", "Sala San José").</p>
                @endforelse
                <form method="post" action="{{ route('obituarios.salas.store') }}" class="input-group mt-2">
                    @csrf
                    <input class="form-control" name="nombre" maxlength="60" required placeholder="Nombre de la sala (ej: Sala {{ $salas->count() + 1 }})">
                    <button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Crear sala</button>
                </form>
            </div>

            <div class="lp-box">
                <h3><i class="fa-solid fa-tv me-1"></i>Pantalla general</h3>
                <p class="small text-muted">Para la recepción o la entrada: muestra todos los obituarios activos, uno tras otro.</p>
                @php $urlGeneral = route('obituarios.general', $tienda->obituarios_token); @endphp
                <div class="ob-sala">
                    <div class="top"><b>Todas las salas</b>
                        <div class="ms-auto d-flex gap-1">
                            <a class="btn btn-sm btn-light" target="_blank" href="{{ $urlGeneral }}"><i class="fa-solid fa-tv"></i></a>
                            <button type="button" class="btn btn-sm btn-light" data-copiar="{{ $urlGeneral }}"><i class="fa-regular fa-copy"></i></button>
                        </div>
                    </div>
                    <code>{{ $urlGeneral }}</code>
                </div>
                <form method="post" action="{{ route('obituarios.general.config') }}" class="d-flex align-items-center gap-2 flex-wrap">
                    @csrf
                    <label class="small fw-bold">Cambiar cada</label>
                    <input type="number" class="form-control form-control-sm" style="width:80px" name="obituarios_rotar" min="5" max="120" value="{{ $tienda->obituarios_rotar ?: 12 }}">
                    <span class="small">segundos</span>
                    <div class="form-check small"><input class="form-check-input" type="checkbox" name="nueva_direccion" value="1" id="ndg"><label class="form-check-label" for="ndg">Nueva dirección</label></div>
                    <button class="btn btn-sm btn-dark ms-auto">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('[data-copiar]').forEach(function (b) {
    b.addEventListener('click', function () {
        var t = b.getAttribute('data-copiar');
        (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () {
            b.innerHTML = '<i class="fa-solid fa-check"></i>'; setTimeout(function () { b.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 1500);
        }).catch(function () { prompt('Copie la dirección:', t); });
    });
});
</script>
@endpush
