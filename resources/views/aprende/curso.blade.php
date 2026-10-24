@extends('layouts.app')

@section('title', $curso['titulo'])

@push('css')
@include('aprende._css')
@endpush

@section('content')
@php
    $total = count($curso['lecciones']);
    $todas = count(array_intersect(array_keys($curso['lecciones']), $hechas)) === $total;
    $abierta = collect(array_keys($curso['lecciones']))->first(fn ($i) => !in_array($i, $hechas, true));
    $p = \App\Http\Controllers\academiaController::porcentaje($curso, $avance->exists ? $avance : null);
@endphp
<div class="lp-dash">
    <div class="ap-hero">
        <small><a href="{{ route('aprende.index') }}" style="color:inherit;text-decoration:none"><i class="fa-solid fa-arrow-left me-1"></i>Aprende LocalPro</a></small>
        <h1><i class="fa-solid {{ $curso['icono'] }} me-2" style="color:#a6f34a"></i>{{ $curso['titulo'] }}</h1>
        <p>{{ $curso['resumen'] }}</p>
        <div class="ap-barra"><i style="width:{{ $p }}%"></i></div>
        <small class="d-block mt-2" style="color:#b8c4bc;letter-spacing:0;text-transform:none;font-size:.85rem;position:relative;z-index:1">{{ $p }} % · {{ $avance->aprobado_at ? '✓ Curso completado' : $total . ' lecciones y una prueba de 3 preguntas' }}</small>
    </div>

    @foreach ($curso['lecciones'] as $i => $l)
    @php $hecha = in_array($i, $hechas, true); @endphp
    <details class="ap-lec {{ $hecha ? 'hecha' : '' }}" id="l{{ $i }}" @if ($abierta === $i) open @endif>
        <summary><span class="n">{{ $hecha ? '✓' : $i + 1 }}</span>{{ $l['titulo'] }}<small>{{ $l['min'] }} min</small></summary>
        <div class="cuerpo">
            @foreach ((array) ($l['rutas'] ?? (isset($l['ruta']) ? [$l['ruta']] : [])) as $r)
            <x-guia-ruta :ruta="$r" :tipo="$tieneTienda ? null : ($curso['tipos'][0] ?? null)" />
            @endforeach
            <ul>@foreach ($l['contenido'] as $t)<li>{!! $t !!}</li>@endforeach</ul>
            @if ($l['tip'])<div class="ap-tip">💡 {{ $l['tip'] }}</div>@endif
            <div class="d-flex gap-2 flex-wrap">
                @if (!$hecha)
                <form method="post" action="{{ route('aprende.leccion', [$slug, $i]) }}">@csrf<button class="btn btn-primary rounded-pill fw-bold">Entendido ✓</button></form>
                @endif
                @if ($l['accion'] && $tieneTienda && \Illuminate\Support\Facades\Route::has($l['accion'][0]))
                <a href="{{ route($l['accion'][0]) }}" class="btn btn-light rounded-pill fw-bold" style="border:1.5px solid #bfeccb">{{ $l['accion'][1] }} →</a>
                @endif
            </div>
        </div>
    </details>
    @endforeach

    <div class="ap-prueba mt-3" id="prueba">
        <h3 style="font-weight:900;font-size:1.15rem;margin:0 0 .4rem"><i class="fa-solid fa-award me-1" style="color:#16b34a"></i> Prueba final</h3>
        @if ($resultado)
            @if ($resultado['aprobo'])
            <div class="alert alert-success fw-bold">🎉 ¡Aprobó con {{ $resultado['nota'] }} %! Curso completado.</div>
            @else
            <div class="alert alert-warning fw-bold">Casi: sacó {{ $resultado['nota'] }} %. Revise las preguntas marcadas e inténtelo otra vez (no hay límite).</div>
            @endif
        @endif
        @if ($avance->aprobado_at && !$resultado)
            <p class="fw-semibold mb-2">✓ Ya aprobó este curso el {{ $avance->aprobado_at->format('d/m/Y') }}. ¡Bien hecho!</p>
            <a href="{{ route('aprende.index') }}" class="btn btn-dark rounded-pill fw-bold">Ver más cursos</a>
        @elseif (!$todas)
            <p class="text-muted fw-semibold mb-0">Termine las lecciones (botón "Entendido") para hacer la prueba. Son 3 preguntas fáciles.</p>
        @elseif (!($resultado['aprobo'] ?? false))
        <form method="post" action="{{ route('aprende.prueba', $slug) }}">
            @csrf
            @foreach ($curso['prueba'] as $qi => $q)
            @php $orden = collect(array_keys($q['o']))->shuffle(); $mala = in_array($qi, $resultado['malas'] ?? [], true); @endphp
            <div class="mb-3">
                <div class="fw-bold mb-2 {{ $mala ? 'text-danger' : '' }}">{{ $qi + 1 }}. {{ $q['p'] }} @if ($mala)<small>(revísela)</small>@endif</div>
                @foreach ($orden as $oi)
                <label class="ap-op"><input type="radio" name="r[{{ $qi }}]" value="{{ $oi }}" required>{{ $q['o'][$oi] }}</label>
                @endforeach
            </div>
            @endforeach
            <button class="btn btn-success btn-lg rounded-pill fw-bold">Enviar respuestas</button>
        </form>
        @else
            <a href="{{ route('aprende.index') }}" class="btn btn-dark rounded-pill fw-bold">Ver más cursos</a>
        @endif
    </div>
</div>
@endsection
