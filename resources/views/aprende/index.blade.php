@extends('layouts.app')

@section('title', 'Aprende LocalPro')

@push('css')
@include('aprende._css')
@endpush

@section('content')
@php
    $base = collect($cursos)->reject(fn ($c) => \App\Support\Academia::esOpcional($c));
    $n = $base->count();
    $hechos = $base->keys()->filter(fn ($k) => ($avances[$k] ?? null)?->aprobado_at)->count();
    $pct = $n ? round($hechos / $n * 100) : 0;
@endphp
<div class="lp-dash">
    @include('layouts.partials.lp-flash')
    <div class="ap-hero">
        <small><i class="fa-solid fa-graduation-cap me-1"></i>Aprende LocalPro</small>
        <h1>Cursos cortos para que <em>venda más</em> y maneje mejor su negocio</h1>
        @if (auth()->user()->asesor && !auth()->user()->tienda_id && !auth()->user()->es_superadmin)
        <p>Lecciones de 2 a 4 minutos para asesores: encontrar negocios, presentar LocalPro, responder objeciones, cerrar el plan anual y armar su equipo. Hágalos usted y su línea, desde el celular.</p>
        @else
        <p>Lecciones de 2 a 4 minutos con ejemplos reales: ventas, caja, vender más, inventario y contabilidad. Hágalos usted y su equipo, a su ritmo, desde el celular.</p>
        @endif
        <div class="ap-barra"><i style="width:{{ $pct }}%"></i></div>
        <small class="d-block mt-2" style="color:#b8c4bc;letter-spacing:0;text-transform:none;font-size:.85rem;position:relative;z-index:1">{{ $hechos }} de {{ $n }} cursos completados</small>
    </div>

    @php $suCurso = \App\Support\Academia::suCurso(); $sc = $suCurso ? ($cursos[$suCurso] ?? null) : null; @endphp
    @if ($sc)
    @php $sa = $avances[$suCurso] ?? null; $sp = \App\Http\Controllers\academiaController::porcentaje($sc, $sa); @endphp
    <a href="{{ route('aprende.curso', $suCurso) }}" class="d-flex align-items-center gap-3 p-3 mt-3 mb-2 text-decoration-none" style="border-radius:20px;background:linear-gradient(135deg,{{ $sc['color'] }},#0b0d0c);color:#fff;box-shadow:0 18px 40px -22px {{ $sc['color'] }}">
        <div class="d-grid flex-shrink-0" style="width:58px;height:58px;border-radius:16px;background:rgba(255,255,255,.16);place-items:center;font-size:1.6rem"><i class="fa-solid {{ $sc['icono'] }}"></i></div>
        <div class="flex-grow-1">
            <small style="opacity:.8;font-weight:800;letter-spacing:.06em;text-transform:uppercase;font-size:.72rem">Empiece aquí · cómo manejar su sistema</small>
            <div style="font-weight:900;font-size:1.15rem;line-height:1.2">{{ $sc['titulo'] }}</div>
            <small style="opacity:.85">{{ count($sc['lecciones']) }} lecciones · {{ $sa?->aprobado_at ? '✓ Completado' : $sp . ' %' }}</small>
        </div>
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    @endif

    @php
        $grupos = collect($cursos)->groupBy(fn ($c) => \App\Support\Academia::esOpcional($c) ? 'op' : 'base', true);
    @endphp
    @foreach (['base' => null, 'op' => ['Conozca LocalPro a fondo · opcional', 'Los mismos cursos que hacen los negocios: caja, inventario y contabilidad. Con ellos podrá explicarle todo al dueño y a su contador.']] as $g => $tit)
    @if ($grupos->has($g))
    @if ($tit)<div class="mt-4 mb-2"><h3 style="font-weight:900;font-size:1.15rem;margin:0"><i class="fa-solid fa-star me-1" style="color:#f5b544"></i>{{ $tit[0] }}</h3><small class="text-muted fw-semibold">{{ $tit[1] }}</small></div>@endif
    <div class="ap-grid">
        @foreach ($grupos[$g] as $slug => $c)
        @php $a = $avances[$slug] ?? null; $p = \App\Http\Controllers\academiaController::porcentaje($c, $a); $min = collect($c['lecciones'])->sum('min'); @endphp
        <a href="{{ route('aprende.curso', $slug) }}" class="ap-card">
            @if ($a?->aprobado_at)<span class="sello">✓ Completado</span>@elseif ($g === 'op')<span class="sello" style="background:#f1f5f2;color:#55605a">Opcional</span>@elseif ($c['para'] === 'dueno')<span class="sello d">Para el dueño</span>@elseif ($c['para'] === 'asesor')<span class="sello" style="background:#ede9fe;color:#6d28d9">Asesores</span>@endif
            <div class="ic" style="background:{{ $c['color'] }}"><i class="fa-solid {{ $c['icono'] }}"></i></div>
            <h3>{{ $c['titulo'] }}</h3>
            <p>{{ $c['resumen'] }}</p>
            <div class="meta"><span>{{ count($c['lecciones']) }} lecciones · {{ $min }} min</span><span>{{ $p }} %</span></div>
            <div class="ap-barra"><i style="width:{{ $p }}%"></i></div>
        </a>
        @endforeach
    </div>
    @endif
    @endforeach

    @if ($linea->isNotEmpty())
    @php $completos = $linea->filter(fn ($x) => $x['aprobados'] >= $x['total'])->count(); $sinEmpezar = $linea->where('empezo', false)->count(); @endphp
    <div class="lp-box mt-3">
        <h3><span><i class="fa-solid fa-sitemap me-1"></i> {{ auth()->user()->es_superadmin ? 'Cómo va la red de asesores' : 'Cómo va su línea' }}</span>
            <small class="text-muted fw-semibold">{{ $completos }} con todos los cursos · {{ $sinEmpezar }} sin empezar</small></h3>
        @foreach ($linea as $x)
        @php $pct = $x['total'] ? round($x['aprobados'] / $x['total'] * 100) : 0; @endphp
        <div class="d-flex align-items-center gap-2 py-2 border-bottom flex-wrap">
            <div style="flex:1 1 200px;min-width:0">
                <b>{{ $x['nombre'] }}</b> <span class="text-muted small">{{ $x['codigo'] }} · {{ $x['cargo'] }}</span>
                <div class="ap-barra mt-1" style="max-width:260px"><i style="width:{{ $pct }}%"></i></div>
            </div>
            <span class="small fw-bold" style="min-width:150px">@if ($x['empezo']){{ $x['aprobados'] }} de {{ $x['total'] }} cursos @if ($x['ultimo'])<span class="text-muted fw-semibold">· {{ \Illuminate\Support\Carbon::parse($x['ultimo'])->diffForHumans() }}</span>@endif @else<span class="text-muted">Aún no empieza</span>@endif</span>
            @if ($x['tel'] && $x['aprobados'] < $x['total'])
            <a class="btn btn-sm btn-success rounded-pill" target="_blank" rel="noopener" href="https://wa.me/{{ $x['tel'] }}?text={{ rawurlencode('¡Hola ' . strtok($x['nombre'], ' ') . '! 👋 Ya están los cursos de Aprende LocalPro para asesores: cómo encontrar negocios, presentar LocalPro en 5 minutos, responder objeciones y armar tu equipo. Son cortos y desde el celular 💪 Entra aquí: ' . route('aprende.index')) }}"><i class="fa-brands fa-whatsapp me-1"></i>Animar</a>
            @endif
        </div>
        @endforeach
    </div>
    @endif

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
