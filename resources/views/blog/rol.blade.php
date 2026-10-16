@extends('blog._base')
@section('titulo', $r['h1'])
@section('descripcion', $r['descripcion'])
@section('volver')@endsection
@section('cuerpo')
<h1><i class="fa-solid {{ $r['icono'] }}" style="color:#11702f"></i> {{ $r['h1'] }}</h1>
<p style="font-size:1.12rem">{{ $r['intro'] }}</p>

<h2>Lo que LocalProo hace por usted</h2>
<ul>@foreach ($r['beneficios'] as $b)<li>{{ $b }}</li>@endforeach</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => $r['tipo'], 'desde' => 'blog:para-' . $rolKey])) }}">{{ $r['cta'] }}</a>
<p style="text-align:center;color:#6b7570;margin-top:0">{{ $r['nota'] }}</p>

@if ($articulos)
<h2>Guías para {{ mb_strtolower($r['nombre']) }}</h2>
@foreach ($articulos as $s => $x)
<a class="card" href="{{ route('blog.show', $s) }}">
    <b><i class="fa-solid {{ $x['icono'] }} me-1"></i> {{ $x['titulo'] }}</b>
    <span>{{ $x['resumen'] }}</span>
    <div class="f" style="margin:8px 0 0">{{ $x['fecha'] }} · {{ $x['minutos'] }} min de lectura</div>
</a>
@endforeach
@endif
@endsection
