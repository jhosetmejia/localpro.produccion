<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Examen · Curso LocalPro</title>
</head>
<body>
<div class="w">
    @include('curso._top')
    <div class="hero">
        <small>Examen final</small>
        <h1>¡Ya casi! <em>{{ count($preguntas) }} preguntas</em></h1>
        <p>Son fáciles: todo está en las lecciones. Apruebas con el {{ $minima }}% y si no te va bien puedes intentarlo otra vez.</p>
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
</body>
</html>
