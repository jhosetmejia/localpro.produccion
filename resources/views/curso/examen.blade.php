@extends('layouts.app')

@section('title', 'Examen')

@push('css')
@endpush

@section('content')
@include('curso._estilos_panel')
<div class="lpx">
<div class="w">
    @include('curso._top_panel')
    <div class="hero">
        <small>Prueba básica</small>
        <h1>¡Ya casi! <em>{{ count($preguntas) }} preguntas</em></h1>
        <p>Son fáciles: todo está en las lecciones. Pasas con el {{ $minima }}% y si no te va bien puedes intentarlo otra vez.</p>
    </div>
    <div class="card" style="background:#e8f8ec;border:1.5px solid #bfe8c8">
        <h2 style="margin:0 0 4px;font-size:1.15rem">🚀 ¡Recorrido terminado! Avísale a tu guía</h2>
        <p style="margin:0 0 12px;color:var(--mut);font-weight:600">
            {{ $guia ? 'Cuéntale a ' . $guia->nombre . ' que ya viste todas las lecciones.' : 'Cuéntale a la persona de LocalPro que te contactó que ya viste todas las lecciones.' }}
        </p>
        <a class="btn btn-w btn-block" target="_blank" rel="noopener" href="{{ $waRecorrido }}"><i class="fa-brands fa-whatsapp"></i> Enviar "Recorrido listo" a mi guía</a>
    </div>
    <form method="post" action="{{ route('curso.calificar') }}" id="ex">
        @csrf
        @foreach ($preguntas as $i => $q)
        <div class="card preg">
            <h3>{{ $i + 1 }}. {{ $q['p'] }}</h3>
            @foreach ($q['o'] as $k => $op)
                <label class="op"><input type="radio" name="r[{{ $q['id'] }}]" value="{{ $k }}" required> <span>{{ $op }}</span></label>
            @endforeach
        </div>
        @endforeach
        <button class="btn btn-g btn-block" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar mis respuestas</button>
    </form>
</div>
</div>
@endsection
