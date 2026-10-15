@extends('blog._base')
@section('titulo', 'Blog')
@section('descripcion', 'Guías cortas para manejar mejor su negocio con LocalProo.')
@section('volver')<a class="v" href="{{ url('/') }}">← LocalProo</a>@endsection
@section('cuerpo')
<h1>Blog LocalProo</h1>
<div class="f">Guías cortas para manejar mejor su negocio.</div>
@foreach ($articulos as $slug => $a)
<a class="card" href="{{ route('blog.show', $slug) }}">
    <b><i class="fa-solid {{ $a['icono'] }} me-1"></i> {{ $a['titulo'] }}</b>
    <span>{{ $a['resumen'] }}</span>
    <div class="f" style="margin:8px 0 0">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>
</a>
@endforeach
@endsection
