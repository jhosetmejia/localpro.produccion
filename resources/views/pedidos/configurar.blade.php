@extends('layouts.app')

@section('title', 'Configurar pedidos')

@push('css')
<style>
.cf-modo{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
@media (max-width:700px){.cf-modo{grid-template-columns:1fr}}
.cf-modo label{border:2px solid #e3ebe6;border-radius:18px;padding:.9rem 1rem;cursor:pointer;display:block}
.cf-modo label:has(input:checked){border-color:#16b34a;background:#f0fdf4}
.cf-modo b{display:block;font-size:1.02rem}.cf-modo small{color:#5b665e;font-weight:600}
.cf-est{display:grid;grid-template-columns:minmax(140px,1fr) 170px auto auto auto;gap:.5rem;align-items:center;padding:.5rem 0;border-bottom:1px solid #eef1ef}
@media (max-width:700px){.cf-est{grid-template-columns:1fr 1fr}}
.cf-prod{max-height:460px;overflow:auto;border:1.5px solid #e3ebe6;border-radius:16px}
.cf-prod .r{display:flex;gap:.6rem;align-items:center;padding:.45rem .8rem;border-bottom:1px solid #eef1ef}
.cf-prod .r b{flex:1;font-weight:700;font-size:.92rem}.cf-prod .r small{color:#6b7570}
.cf-prod select{max-width:190px}
</style>
@endpush

@section('content')
@php $iconos = ['fa-fire-burner' => 'Cocina', 'fa-mug-hot' => 'Café', 'fa-martini-glass' => 'Bar', 'fa-drumstick-bite' => 'Parrilla', 'fa-ice-cream' => 'Postres', 'fa-pizza-slice' => 'Pizza', 'fa-ticket' => 'Entradas', 'fa-horse' => 'Animales', 'fa-tractor' => 'Granja', 'fa-kitchen-set' => 'Estación']; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-sliders me-1"></i>Pedidos</small><h1>Configurar <span>pedidos</span></h1>
            <span class="text-muted fw-semibold">Elija cuándo paga el cliente, sus estaciones y qué prepara cada una. Los roles de su equipo se crean en <a href="{{ route('roles-negocio.index') }}">Roles del negocio</a>.</span></div>
        <div class="lp-quick"><a href="{{ route('pedidos.guia') }}" class="btn btn-light"><i class="fa-solid fa-book-open me-1"></i>Cómo funciona</a></div>
    </div>
    @include('pedidos._nav')
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('pedidos.configurar.guardar') }}">@csrf
        <div class="lp-box mb-3">
            <h3><i class="fa-solid fa-money-bill-wave me-1"></i>¿Cuándo paga el cliente?</h3>
            <div class="cf-modo">
                <label><input type="radio" name="pedidos_cobro" value="antes" class="form-check-input me-1" @checked($tienda->pedidos_cobro !== 'despues')><b>Paga primero</b>
                    <small>Mesero toma el pedido → caja cobra con el nombre del cliente → recién ahí llega a cocina. Ideal para comidas rápidas, cafeterías, granjas y parques.</small></label>
                <label><input type="radio" name="pedidos_cobro" value="despues" class="form-check-input me-1" @checked($tienda->pedidos_cobro === 'despues')><b>Paga al final</b>
                    <small>El pedido llega de una a cocina; el mesero puede agregar más y cuando el cliente pide la cuenta pasa a caja. Ideal para restaurante a la mesa.</small></label>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3><i class="fa-solid fa-kitchen-set me-1"></i>Estaciones</h3>
            <small class="text-muted d-block mb-2">Cada estación tiene su pantalla (tablet, celular o TV) y ve solo lo suyo. Ej.: Cocina, Cafetería, Bar, Entradas, Paseos con animales.</small>
            @foreach ($estaciones as $e)
            <div class="cf-est">
                <input name="est[{{ $e->id }}][nombre]" class="form-control" maxlength="40" value="{{ $e->nombre }}" required>
                <select name="est[{{ $e->id }}][icono]" class="form-select">@foreach ($iconos as $ic => $txt)<option value="{{ $ic }}" @selected($e->icono === $ic)>{{ $txt }}</option>@endforeach</select>
                <label class="form-check form-switch m-0 small fw-bold"><input class="form-check-input" type="checkbox" name="est[{{ $e->id }}][activo]" value="1" @checked($e->activo)> Activa</label>
                <a href="{{ route('pedidos.estacion', ['e' => $e->id]) }}" target="_blank" class="btn btn-sm btn-light"><i class="fa-solid fa-tv me-1"></i>Pantalla</a>
                <button type="submit" form="del{{ $e->id }}" class="btn btn-sm btn-light" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </div>
            @endforeach
            <div class="cf-est">
                <input name="nueva" class="form-control" maxlength="40" placeholder="+ Nueva estación (ej. Cafetería)" list="sugeridas">
                <select name="nueva_icono" class="form-select">@foreach ($iconos as $ic => $txt)<option value="{{ $ic }}">{{ $txt }}</option>@endforeach</select>
            </div>
            <datalist id="sugeridas">@foreach (\App\Models\PedidoEstacion::SUGERIDAS as [$n])<option value="{{ $n }}">@endforeach</datalist>
        </div>
        <button class="btn btn-primary btn-lg mb-4"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
    </form>
    @foreach ($estaciones as $e)
    <form id="del{{ $e->id }}" method="post" action="{{ route('pedidos.estaciones.destroy', $e) }}" data-confirm="¿Eliminar la estación {{ $e->nombre }}?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('delete')</form>
    @endforeach

    <div class="lp-box mb-3">
        <h3><i class="fa-solid fa-utensils me-1"></i>¿Qué estación prepara cada producto?</h3>
        <small class="text-muted d-block mb-2">Lo que no tenga estación va a <b>{{ $estaciones->firstWhere('activo', true)?->nombre ?? 'la primera estación' }}</b>.</small>
        <form method="post" action="{{ route('pedidos.configurar.productos') }}" class="d-flex gap-2 flex-wrap align-items-center mb-3">@csrf
            <span class="fw-bold small">Toda la categoría</span>
            <select name="categoria" class="form-select" style="max-width:220px">@foreach ($categorias as $c)<option>{{ $c }}</option>@endforeach</select>
            <span class="fw-bold small">va a</span>
            <select name="categoria_estacion" class="form-select" style="max-width:200px">@foreach ($estaciones as $e)<option value="{{ $e->id }}">{{ $e->nombre }}</option>@endforeach</select>
            <button class="btn btn-dark">Aplicar</button>
        </form>
        <form method="post" action="{{ route('pedidos.configurar.productos') }}">@csrf
            <input type="search" class="form-control mb-2" placeholder="Buscar producto…" oninput="var q=this.value.toLowerCase();document.querySelectorAll('.cf-prod .r').forEach(function(r){r.style.display=r.dataset.n.indexOf(q)>=0?'':'none'})">
            <div class="cf-prod">
                @forelse ($productos as $p)
                <div class="r" data-n="{{ mb_strtolower($p->nombre . ' ' . $catDe($p)) }}"><b>{{ $p->nombre }} <small>{{ $catDe($p) }}</small></b>
                    <select name="prod[{{ $p->id }}]" class="form-select form-select-sm"><option value="">— Primera estación —</option>@foreach ($estaciones as $e)<option value="{{ $e->id }}" @selected((int) $p->estacion_id === $e->id)>{{ $e->nombre }}</option>@endforeach</select></div>
                @empty
                <div class="p-3 text-muted">No hay productos activos.</div>
                @endforelse
            </div>
            <button class="btn btn-primary mt-2"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar estaciones de los productos</button>
        </form>
    </div>

    <div class="lp-box">
        <h3><i class="fa-solid fa-tv me-1"></i>Pantalla para los clientes</h3>
        <p class="text-muted mb-2">Ábrala en un televisor del local: muestra <b>"Su pedido está listo"</b> con el nombre del cliente. No pide usuario.</p>
        <div class="input-group"><input class="form-control" readonly value="{{ route('pedidos.clientes', $tienda->pedidos_token) }}" id="cfUrl"><a class="btn btn-dark" target="_blank" href="{{ route('pedidos.clientes', $tienda->pedidos_token) }}"><i class="fa-solid fa-up-right-from-square"></i></a></div>
    </div>
</div>
@endsection
