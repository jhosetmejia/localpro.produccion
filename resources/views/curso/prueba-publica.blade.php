<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    @php
        $f = $a->curso_aprobado_at;
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fecha = $f->day . ' de ' . $meses[$f->month - 1] . ' de ' . $f->year;
    @endphp
    <title>{{ $a->nombre }} pasó la prueba básica · LocalPro</title>
    <meta property="og:title" content="{{ $a->nombre }} pasó la prueba básica de LocalPro ✅">
    <meta property="og:description" content="Prueba básica para asesores comerciales LocalPro · {{ $fecha }}">
    <style>
        .ok-ic{width:76px;height:76px;border-radius:24px;background:var(--g);display:grid;place-items:center;font-size:2rem;color:var(--k);margin:0 auto 14px}
        .dato{display:flex;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px dashed var(--line);font-weight:600}
        .dato:last-child{border-bottom:0}
        .dato span{color:var(--mut)}
    </style>
</head>
<body>
<div class="w" style="max-width:560px">
    <div class="top"><a href="{{ url('/') }}" class="brand"><b>L</b>LocalPro</a></div>

    <div class="card" style="text-align:center;padding:28px 22px">
        <div class="ok-ic"><i class="fa-solid fa-check"></i></div>
        <small style="color:#16b34a;font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.72rem">Resultado verificado por LocalPro</small>
        <h1 style="font-size:1.7rem;margin:.4rem 0 .3rem;letter-spacing:-.02em">{{ $a->nombre }}</h1>
        <p style="margin:0;color:var(--mut);font-weight:600">pasó la prueba básica para asesores comerciales de LocalPro.</p>
    </div>

    <div class="card">
        @if ($a->curso_nota)<div class="dato"><span>Nota</span><b>{{ $a->curso_nota }}%</b></div>@endif
        <div class="dato"><span>Fecha</span><b>{{ $fecha }}</b></div>
        <div class="dato"><span>Código de asesor</span><b>{{ $a->codigo }}</b></div>
        @if ($a->ciudad)<div class="dato"><span>Ciudad</span><b>{{ $a->ciudad }}</b></div>@endif
    </div>

    <p class="pie">© {{ date('Y') }} LocalProo · Todos los derechos reservados</p>
</div>
</body>
</html>
