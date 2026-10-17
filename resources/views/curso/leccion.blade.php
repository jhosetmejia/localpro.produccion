@extends('layouts.app')

@section('title', ($l['titulo']))

@push('css')
@endpush

@section('content')
@include('curso._estilos_panel')
<div class="lpx">
<div class="w">
    @include('curso._top_panel')
    <div style="display:flex;justify-content:space-between;font-weight:800;color:var(--mut);font-size:.9rem"><span>Lección {{ $n }} de {{ $total }}</span><span>{{ $l['minutos'] }} min</span></div>
    <div class="barra" style="margin-bottom:16px"><div style="width:{{ $n / $total * 100 }}%"></div></div>

    <div class="card">
        <div style="width:52px;height:52px;border-radius:16px;background:var(--g);display:grid;place-items:center;font-size:1.3rem;margin-bottom:12px"><i class="fa-solid {{ $l['icono'] }}"></i></div>
        <h1 style="margin:0 0 8px;font-size:1.7rem;letter-spacing:-.02em">{{ $l['titulo'] }}</h1>
        <p style="margin:0;font-weight:600;color:var(--mut);font-size:1.05rem">{{ $l['intro'] }}</p>
        <ul class="puntos">
            @foreach ($l['puntos'] as $p)<li><i class="fa-solid fa-circle-check"></i><span>{!! $p !!}</span></li>@endforeach
        </ul>
        <div class="clave"><i class="fa-solid fa-lightbulb me-1"></i> {{ $l['clave'] }}</div>
    </div>

    @if (session('tarea'))<div class="msg mal">{{ session('tarea') }}</div>@endif
    @if (!empty($tarea))
    <div class="card" style="border-color:{{ $tarea['hecha'] ? '#bfeccb' : '#fde68a' }};background:{{ $tarea['hecha'] ? '#f4fcf6' : '#fffdf3' }}">
        <h2 style="margin:0 0 8px;font-size:1.15rem"><i class="fa-solid fa-list-check"></i> Tarea práctica</h2>
        <div class="paso {{ $tarea['r']['total'] > 0 ? 'ok' : '' }}"><div class="ic"><i class="fa-solid {{ $tarea['r']['total'] > 0 ? 'fa-check' : 'fa-upload' }}"></i></div><div class="t"><b>Subir sus contactos</b><small>{{ $tarea['r']['total'] > 0 ? number_format($tarea['r']['total'], 0, ',', '.') . ' contactos guardados' : 'Aún no ha subido contactos' }}</small></div></div>
        <div class="paso {{ $tarea['r']['contactados'] >= \App\Support\CursoAsesor::TAREA_MINIMO ? 'ok' : '' }}"><div class="ic"><i class="fa-brands {{ $tarea['r']['contactados'] >= \App\Support\CursoAsesor::TAREA_MINIMO ? 'fa-whatsapp' : 'fa-whatsapp' }}"></i></div><div class="t"><b>Escribirle a {{ \App\Support\CursoAsesor::TAREA_MINIMO }} contactos</b><small>Lleva {{ min($tarea['r']['contactados'], \App\Support\CursoAsesor::TAREA_MINIMO) }} de {{ \App\Support\CursoAsesor::TAREA_MINIMO }}</small></div></div>
        @if (!$tarea['hecha'])
        <a href="{{ route('asesor.contactos', ['volver' => 'curso']) }}" class="btn btn-k btn-block" style="margin-top:10px"><i class="fa-solid fa-address-book"></i> Abrir Mis contactos y hacer la tarea</a>
        @else
        <div class="msg ok" style="margin:10px 0 0">✓ ¡Tarea hecha! Ya puede seguir.</div>
        @endif
    </div>
    @endif

    <form method="post" action="{{ route('curso.completar', $n) }}">
        @csrf
        <button class="btn btn-g btn-block" @if (!empty($tarea) && !$tarea['hecha']) disabled style="opacity:.45;cursor:not-allowed" @endif>{{ $n < $total ? 'Entendido, siguiente lección' : 'Entendido, ir al examen' }} <i class="fa-solid fa-arrow-right"></i></button>
    </form>
    <div style="text-align:center;margin-top:12px"><a href="{{ $n > 1 ? route('curso.leccion', $n - 1) : route('curso.index') }}" style="color:var(--mut);font-weight:700;text-decoration:none"><i class="fa-solid fa-arrow-left"></i> {{ $n > 1 ? 'Lección anterior' : 'Volver al curso' }}</a></div>
</div>
</div>
@endsection
