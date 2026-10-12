@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
@php
    $lineas = collect(explode("\n", (string) $texto));
    $nErr = $lineas->filter(fn ($l) => str_contains($l, 'ERROR '))->count();
    $nAv = $lineas->filter(fn ($l) => str_contains($l, 'AVISO '))->count();
    $nOk = $lineas->filter(fn ($l) => str_contains($l, 'OK '))->count();
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Auditoría del <span>sistema</span></h1>
            <span class="text-muted fw-semibold">{{ $fecha ? 'Última revisión: ' . $fecha->locale('es')->diffForHumans() . ' (' . $fecha->format('d/m/Y H:i') . ')' : 'Aún no se ha hecho ninguna revisión.' }}</span></div>
        <form method="post" action="{{ route('admin.auditoria.correr') }}">@csrf<button class="btn btn-primary btn-lg"><i class="fa-solid fa-stethoscope me-1"></i>Revisar ahora</button></form>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($texto)
    <div class="lp-kpis">
        <div class="lp-kpi {{ $nErr ? '' : 'hero' }}"><div class="ic"><i class="fa-solid fa-circle-xmark"></i></div><div class="lbl">Errores</div><div class="val" style="{{ $nErr ? 'color:#d61f69' : '' }}">{{ $nErr }}</div><div class="sub">Hay que corregirlos</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="lbl">Avisos</div><div class="val">{{ $nAv }}</div><div class="sub">Para tener en cuenta</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-circle-check"></i></div><div class="lbl">Bien</div><div class="val">{{ $nOk }}</div><div class="sub">Revisiones sin problema</div></div>
    </div>
    <div class="lp-box">
        <ul class="lp-list">
        @foreach ($lineas as $l)
            @php $t = trim($l); @endphp
            @if ($t === '') @continue @endif
            @if (str_starts_with($t, '■'))
                <li style="background:#0b0d0c;color:#a6f34a;font-weight:900;border-radius:12px">{{ ltrim($t, '■ ') }}</li>
            @elseif (str_starts_with($t, 'ERROR'))
                <li><div class="av" style="background:#ffe9f3;color:#d61f69"><i class="fa-solid fa-xmark"></i></div><div class="t"><b style="color:#9b1c5a">{{ trim(substr($t, 5)) }}</b></div></li>
            @elseif (str_starts_with($t, 'AVISO'))
                <li><div class="av" style="background:#fff5d6;color:#a86b00"><i class="fa-solid fa-exclamation"></i></div><div class="t"><span>{{ trim(substr($t, 5)) }}</span></div></li>
            @elseif (str_starts_with($t, 'OK'))
                <li><div class="av"><i class="fa-solid fa-check"></i></div><div class="t"><small>{{ trim(substr($t, 2)) }}</small></div></li>
            @else
                <li><b>{{ $t }}</b></li>
            @endif
        @endforeach
        </ul>
    </div>
    @endif
    <p class="text-muted small mt-3">Desde aquí se revisan configuración, tiendas, pagos, comisiones, asesores y ventas. La prueba completa que además abre todas las pantallas se corre en el servidor con <code>php artisan localpro:auditoria</code>.</p>
</div>
@endsection
