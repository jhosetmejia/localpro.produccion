<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Resultado del pago · LocalPro</title>
</head>
<body>
<div class="w">
    <div class="top"><span class="brand"><b>L</b>LocalPro · Pagos</span></div>
    @php $ok = $pago->estado === 'aprobado'; $mal = $pago->estado === 'rechazado'; @endphp
    <div class="hero">
        <small>{{ $tienda->nombre }}</small>
        <h1>@if ($ok)¡Pago <em>aprobado</em>! 🎉@elseif ($mal)Pago no aprobado@else Pago en proceso…@endif</h1>
        <p>
            @if ($ok)
                El plan <b style="color:#fff">{{ $pago->plan->nombre ?? '' }}</b> quedó activo{{ $pago->periodo_hasta ? ' hasta el ' . $pago->periodo_hasta->format('d/m/Y') : '' }}.
            @elseif ($mal)
                El banco o el medio de pago no aprobó la transacción. No se hizo ningún cobro; puede intentarlo de nuevo.
            @else
                Wompi está confirmando el pago (con PSE o Nequi puede tardar unos minutos). El plan se activa solo apenas se confirme.
            @endif
        </p>
    </div>
    <div class="card">
        <ul class="puntos" style="margin:0">
            <li><i class="fa-solid fa-store"></i> Negocio: <b>{{ $tienda->nombre }}</b></li>
            <li><i class="fa-solid fa-gem"></i> Plan: <b>{{ $pago->plan->nombre ?? '' }}</b> · {{ \App\Support\Moneda::formato($pago->monto, $pago->moneda ?: 'COP', true) }}</li>
            <li><i class="fa-solid fa-hashtag"></i> Referencia: <b>{{ $pago->referencia }}</b></li>
        </ul>
    </div>
    @if ($mal)<a class="btn btn-g btn-block" href="{{ $tienda->url_pago }}">Intentar de nuevo</a>@endif
    @if (!$ok && !$mal)<a class="btn btn-l btn-block" href="{{ request()->fullUrl() }}">Actualizar</a>@endif
    @auth<a class="btn btn-k btn-block" style="margin-top:10px" href="{{ url('/') }}">Volver a mi panel</a>@endauth
    <div class="pie">LocalProo · Todos los derechos reservados</div>
</div>
</body>
</html>
