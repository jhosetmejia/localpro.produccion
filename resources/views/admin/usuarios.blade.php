@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Usuarios del <span>sistema</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-box">
        <form method="get" class="d-flex flex-wrap gap-2 mb-3">
            <input name="q" class="form-control" style="max-width:320px" placeholder="Buscar nombre, correo o celular" value="{{ $q }}">
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <button class="btn btn-dark">Buscar</button>
        </form>
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach (['' => ['Todos', 'todos'], 'tiendas' => ['De empresas', 'tiendas'], 'red' => ['Red comercial', 'red'], 'admin' => ['Administradores', 'admin']] as $k => [$n, $c])
            <a href="{{ route('admin.usuarios', array_filter(['tipo' => $k, 'q' => $q])) }}" class="btn btn-sm {{ ($tipo ?? '') === $k ? 'btn-dark' : 'btn-light' }}">{{ $n }} ({{ $conteo[$c] }})</a>
            @endforeach
        </div>
        <ul class="lp-list">
            @forelse ($lista as $u)
            <li style="flex-wrap:wrap">
                <div class="av"><i class="fa-solid {{ $u->es_superadmin ? 'fa-crown' : ($u->asesor ? $u->asesor->cargo_icono : 'fa-user') }}"></i></div>
                <div class="t"><b>{{ $u->name }}</b>
                    <small>{{ $u->email }}{{ $u->telefono ? ' · ' . $u->telefono : '' }} ·
                        @if ($u->es_superadmin) Administrador LocalPro @endif
                        @if ($u->asesor) {{ $u->asesor->cargo_nombre }} @endif
                        @if ($u->tienda) <a href="{{ route('admin.tiendas.show', $u->tienda_id) }}">{{ $u->tienda->nombre }}</a> ({{ $u->roles->pluck('name')->map(fn ($r) => $r === 'dueno' ? 'dueño' : $r)->implode(', ') ?: 'sin rol' }}) @endif
                    </small></div>
                <details style="flex-basis:100%;margin-left:52px">
                    <summary class="small fw-bold" style="cursor:pointer;color:#11702f">Ponerle una clave nueva</summary>
                    <form method="post" action="{{ route('admin.usuarios.clave', $u) }}" class="d-flex gap-2 mt-2" data-confirm="¿Cambiar la clave de {{ $u->name }}?" data-confirm-texto="Tendrá que entrar con la clave nueva." data-confirm-ok="Sí, cambiar">
                        @csrf
                        <input type="text" name="clave" class="form-control form-control-sm" minlength="8" required placeholder="Clave nueva (mínimo 8)" style="max-width:240px" autocomplete="off">
                        <button class="btn btn-sm btn-dark">Guardar</button>
                    </form>
                </details>
                <details style="flex-basis:100%;margin-left:52px">
                    <summary class="small fw-bold" style="cursor:pointer;color:#11702f">Cambiarle el correo (perdió el acceso a su correo)</summary>
                    <form method="post" action="{{ route('admin.usuarios.correo', $u) }}" class="d-flex gap-2 mt-2 flex-wrap" data-confirm="¿Cambiar el correo de {{ $u->name }}?" data-confirm-texto="Hágalo solo después de verificar que es la persona (por ejemplo, por su WhatsApp registrado). Desde ahora entra con el correo nuevo y le llega un aviso." data-confirm-ok="Sí, cambiar">
                        @csrf
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="Correo nuevo" style="max-width:260px" autocomplete="off">
                        <button class="btn btn-sm btn-dark">Guardar</button>
                    </form>
                </details>
            </li>
            @empty
            <li class="lp-empty">No se encontraron usuarios.</li>
            @endforelse
        </ul>
        <div class="mt-3">{{ $lista->links() }}</div>
    </div>
</div>
@endsection
