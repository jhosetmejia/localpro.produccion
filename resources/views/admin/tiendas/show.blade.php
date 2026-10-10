@extends('layouts.app')

@section('title', $tienda->nombre)

@section('content')
@php $m = \App\Support\Paises::datos($tienda->pais); $f = fn ($v) => $m['simbolo'] . number_format((float) $v, $m['decimales'], $m['decimal'], $m['miles']); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><a href="{{ route('admin.tiendas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiendas</a></small>
            <h1>{{ $tienda->nombre }}</h1>
            <span class="text-muted fw-semibold"><i class="fa-solid {{ $tienda->tipo_icono }} me-1"></i>{{ $tienda->tipo_nombre }} · {{ $m['nombre'] }} ({{ $m['moneda'] }}){{ $tienda->ciudad ? ' · ' . $tienda->ciudad : '' }} · Inscrita {{ $tienda->created_at->locale('es')->isoFormat('D MMM YYYY') }}</span>
        </div>
        <div class="lp-quick">
            <form method="post" action="{{ route('admin.tiendas.entrar', $tienda) }}">@csrf<button class="btn btn-primary"><i class="fa-solid fa-right-to-bracket me-1"></i>Entrar a la tienda</button></form>
            <form method="post" action="{{ route('admin.tiendas.estado', $tienda) }}" onsubmit="return confirm('{{ $tienda->activa ? '¿Suspender esta tienda? Sus usuarios no podrán entrar.' : '¿Activar esta tienda?' }}')">@csrf
                <button class="btn {{ $tienda->activa ? 'btn-danger' : 'btn-dark' }}"><i class="fa-solid {{ $tienda->activa ? 'fa-ban' : 'fa-circle-check' }} me-1"></i>{{ $tienda->activa ? 'Suspender' : 'Activar' }}</button>
            </form>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Vendido en total</div><div class="val">{{ $f($metricas['total']) }}</div><div class="sub">{{ $metricas['ventas'] }} ventas · este mes {{ $f($metricas['mes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-boxes-stacked"></i></div><div class="lbl">Productos</div><div class="val">{{ $metricas['productos'] }}</div><div class="sub">{{ number_format($metricas['unidades'], 0, ',', '.') }} unidades en inventario</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-signal"></i></div><div class="lbl">Estado</div><div class="val" style="font-size:1.6rem">{{ !$tienda->onboarding_completo ? 'Configurando (paso ' . $tienda->onboarding_paso . ')' : ($tienda->activa ? 'Activa' : 'Suspendida') }}</div><div class="sub">{{ $metricas['cajas_abiertas'] }} caja(s) abierta(s)</div></div>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Últimas ventas</h3>
            <ul class="lp-list">
                @forelse ($ultimas as $v)
                <li><div class="av"><i class="fa-solid fa-receipt"></i></div><div class="t"><b>{{ $v->numero }}</b><small>{{ $v->cliente_nombre ?: 'Mostrador' }} · {{ \Carbon\Carbon::parse($v->created_at)->diffForHumans() }}</small></div>@if($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif<span class="m">{{ $f($v->total) }}</span></li>
                @empty
                <li class="lp-empty">Aún no ha vendido.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3>Datos y usuarios</h3>
            <ul class="lp-list">
                <li><div class="av"><i class="fa-solid fa-phone"></i></div><div class="t"><b>{{ $tienda->telefono ?: '—' }}</b><small>Teléfono</small></div>@if($tienda->telefono)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ preg_replace('/\D/', '', $m['indicativo'] . ltrim(preg_replace('/\D/', '', $tienda->telefono), '0')) }}"><i class="fa-brands fa-whatsapp"></i></a>@endif</li>
                <li><div class="av"><i class="fa-solid fa-id-card"></i></div><div class="t"><b>{{ $tienda->nit ?: '—' }}</b><small>NIT</small></div></li>
                @foreach ($usuarios as $u)
                <li><div class="av"><i class="fa-solid fa-user"></i></div><div class="t"><b>{{ $u->name }}</b><small>{{ $u->email }}{{ $u->telefono ? ' · ' . $u->telefono : '' }}</small></div><span class="badge bg-{{ $u->hasRole('dueno') ? 'success' : 'secondary' }}">{{ $u->es_superadmin ? 'Admin' : ($u->hasRole('dueno') ? 'Dueño' : 'Vendedor') }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
