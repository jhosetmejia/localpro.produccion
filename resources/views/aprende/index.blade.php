@extends('layouts.app')

@section('title', 'Aprende LocalPro')

@push('css')
@include('aprende._css')
@endpush

@section('content')
@php $n = count($cursos); $pct = $n ? round($aprobados / $n * 100) : 0; @endphp
<div class="lp-dash">
    @include('layouts.partials.lp-flash')
    <div class="ap-hero">
        <small><i class="fa-solid fa-graduation-cap me-1"></i>Aprende LocalPro</small>
        <h1>Cursos cortos para que <em>venda más</em> y maneje mejor su negocio</h1>
        <p>Lecciones de 2 a 4 minutos con ejemplos reales: ventas, caja, vender más, inventario y contabilidad. Hágalos usted y su equipo, a su ritmo, desde el celular.</p>
        <div class="ap-barra"><i style="width:{{ $pct }}%"></i></div>
        <small class="d-block mt-2" style="color:#b8c4bc;letter-spacing:0;text-transform:none;font-size:.85rem;position:relative;z-index:1">{{ $aprobados }} de {{ $n }} cursos completados</small>
    </div>

    <div class="ap-grid">
        @foreach ($cursos as $slug => $c)
        @php $a = $avances[$slug] ?? null; $p = \App\Http\Controllers\academiaController::porcentaje($c, $a); $min = collect($c['lecciones'])->sum('min'); @endphp
        <a href="{{ route('aprende.curso', $slug) }}" class="ap-card">
            @if ($a?->aprobado_at)<span class="sello">✓ Completado</span>@elseif ($c['para'] === 'dueno')<span class="sello d">Para el dueño</span>@endif
            <div class="ic" style="background:{{ $c['color'] }}"><i class="fa-solid {{ $c['icono'] }}"></i></div>
            <h3>{{ $c['titulo'] }}</h3>
            <p>{{ $c['resumen'] }}</p>
            <div class="meta"><span>{{ count($c['lecciones']) }} lecciones · {{ $min }} min</span><span>{{ $p }} %</span></div>
            <div class="ap-barra"><i style="width:{{ $p }}%"></i></div>
        </a>
        @endforeach
    </div>

    @if ($equipo->isNotEmpty())
    <div class="lp-box mt-3">
        <h3><span><i class="fa-solid fa-people-group me-1"></i> Cómo va su equipo</span></h3>
        @foreach ($equipo as $e)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <span class="fw-bold">{{ $e['nombre'] }}</span>
            <span class="small">@if ($e['empezados'])<b>{{ $e['aprobados'] }}</b> cursos completados @else <span class="text-muted">Aún no empieza</span>@endif</span>
        </div>
        @endforeach
        <small class="text-muted d-block mt-2">Sus vendedores ven los cursos de ventas, caja y vender más en el menú <b>Aprende</b>.</small>
    </div>
    @endif
</div>
@endsection
