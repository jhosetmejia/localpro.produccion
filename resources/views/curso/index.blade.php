<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Curso de asesores · LocalPro</title>
</head>
<body>
<div class="w">
    @include('curso._top')
    @php
        $total = count($lecciones);
        $vistas = count(array_intersect(array_keys($lecciones), $hechas));
        $aprobado = (bool) $yo->curso_aprobado_at;
        $pasos = $total + 2;
        $listos = $vistas + ($aprobado ? 2 : 0);
        $siguiente = collect(array_keys($lecciones))->first(fn ($n) => !in_array($n, $hechas, true));
        $res = session('resultado');
    @endphp

    <div class="hero">
        <small>Curso básico de asesores</small>
        <h1>Hola {{ strtok($yo->nombre, ' ') }}, <em>bienvenido</em> 👋</h1>
        <p>Son {{ $total }} lecciones cortas (unos 10 minutos) y un examen fácil de {{ $preguntas }} preguntas. Al terminar recibes tu <b>certificado de Asesor Comercial LocalPro</b> para tu hoja de vida y quedas listo para empezar a ganar.</p>
    </div>

    @if (session('bienvenida'))
        <div class="msg ok"><i class="fa-solid fa-circle-check"></i> ¡Tu inscripción quedó lista{{ $yo->reclutador ? ' en el equipo de ' . $yo->reclutador->nombre : '' }}! Empecemos.</div>
    @endif
    @if (!$admin && $yo->estado === 'activo' && !$yo->curso_aprobado_at)
        <div class="msg mal"><i class="fa-solid fa-circle-info"></i> LocalPro ahora certifica a toda su red. Haga este curso corto para seguir usando su panel: no pierde su cargo, su equipo ni sus comisiones.</div>
    @endif
    @if (session('error'))<div class="msg mal">{{ session('error') }}</div>@endif
    @if ($res && !$res['aprobado'])
        <div class="msg mal">
            <div style="font-size:1.05rem">Casi 💪 Acertaste {{ $res['correctas'] }} de {{ $res['total'] }}. Necesitas el {{ $minima }}%. Puedes intentarlo otra vez ya mismo.</div>
            @if (!empty($res['falladas']))
            <details style="margin-top:8px;font-weight:600"><summary style="cursor:pointer">Ver las respuestas correctas de las que fallaste</summary>
                <ul style="margin:8px 0 0;padding-left:18px">@foreach ($res['falladas'] as $t)<li style="margin-bottom:4px">{{ $t }}</li>@endforeach</ul>
            </details>
            @endif
        </div>
    @endif

    <div class="card">
        <div style="display:flex;justify-content:space-between;font-weight:800"><span>Tu avance</span><span>{{ (int) round($listos / $pasos * 100) }}%</span></div>
        <div class="barra"><div style="width:{{ $listos / $pasos * 100 }}%"></div></div>

        @foreach ($lecciones as $n => $l)
            @php $ok = in_array($n, $hechas, true); $abierta = $ok || $n === $siguiente || $aprobado; @endphp
            <a href="{{ route('curso.leccion', $n) }}" class="paso {{ $ok ? 'ok' : ($abierta ? '' : 'bloq') }}">
                <div class="ic"><i class="fa-solid {{ $ok ? 'fa-check' : $l['icono'] }}"></i></div>
                <div class="t"><b>{{ $n }}. {{ $l['titulo'] }}</b><small>{{ $ok ? 'Vista ✓' : $l['minutos'] . ' min de lectura' }}</small></div>
                @if (!$ok && $abierta)<i class="fa-solid fa-chevron-right" style="color:#16b34a"></i>@endif
            </a>
        @endforeach
        <a href="{{ route('curso.examen') }}" class="paso {{ $aprobado ? 'ok' : ($vistas >= $total ? '' : 'bloq') }}">
            <div class="ic"><i class="fa-solid {{ $aprobado ? 'fa-check' : 'fa-pen-to-square' }}"></i></div>
            <div class="t"><b>Examen final</b><small>{{ $aprobado ? 'Aprobado con ' . $yo->curso_nota . '% ✓' : $preguntas . ' preguntas fáciles · intentos ilimitados' }}</small></div>
            @if (!$aprobado && $vistas >= $total)<i class="fa-solid fa-chevron-right" style="color:#16b34a"></i>@endif
        </a>
        <a href="{{ route('curso.certificado') }}" class="paso {{ $aprobado ? 'ya' : 'bloq' }}">
            <div class="ic"><i class="fa-solid fa-award"></i></div>
            <div class="t"><b>Tu certificado</b><small>{{ $aprobado ? $yo->certificado . ' · listo para descargar' : 'Se desbloquea al aprobar el examen' }}</small></div>
        </a>
    </div>

    @if ($aprobado)
        <a href="{{ $panel }}" class="btn btn-k btn-block"><i class="fa-solid fa-gauge"></i> Ir a mi panel de asesor</a>
    @elseif ($siguiente)
        <a href="{{ route('curso.leccion', $siguiente) }}" class="btn btn-g btn-block">{{ $vistas ? 'Continuar' : 'Empezar el curso' }} <i class="fa-solid fa-arrow-right"></i></a>
    @else
        <a href="{{ route('curso.examen') }}" class="btn btn-g btn-block">{{ $intentos ? 'Intentar el examen otra vez' : 'Presentar el examen' }} <i class="fa-solid fa-arrow-right"></i></a>
    @endif

    <p class="pie">Desarrollado por Jhoset Mejía - DiCreWeb</p>
</div>
</body>
</html>
