@extends('blog._base')
@section('titulo', 'Blog LocalProo: guías para negocios, contadores y funerarias')
@section('descripcion', 'Guías prácticas para manejar su negocio en Colombia: ventas, inventario, tienda en línea, nómina, obituarios para funerarias y herramientas para contadores.')
@section('volver')@endsection
@section('cuerpo')
<h1>Blog LocalProo</h1>
<div class="f">Guías prácticas para negocios, contadores y funerarias en Colombia.</div>
@foreach (config('blog_roles') as $k => $rr)
    @php $lista = collect($articulos)->filter(fn ($x) => ($x['rol'] ?? 'negocios') === $k); @endphp
    @continue($lista->isEmpty())
    <h2 style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap"><span><i class="fa-solid {{ $rr['icono'] }} me-1" style="color:#11702f"></i> Para {{ mb_strtolower($rr['nombre']) }}</span><a href="{{ route('blog.rol', $k) }}" style="font-size:.9rem;color:#11702f">Ver todo →</a></h2>
    @foreach ($lista as $s => $x)
    <a class="card" href="{{ route('blog.show', $s) }}">
        <b><i class="fa-solid {{ $x['icono'] }} me-1"></i> {{ $x['titulo'] }}</b>
        <span>{{ $x['resumen'] }}</span>
        <div class="f" style="margin:8px 0 0">{{ $x['fecha'] }} · {{ $x['minutos'] }} min de lectura</div>
    </a>
    @endforeach
@endforeach
@endsection
