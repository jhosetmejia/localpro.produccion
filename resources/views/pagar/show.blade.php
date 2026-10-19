<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Pagar plan · {{ $tienda->nombre }} · LocalPro</title>
    <style>
        .plan{border:1.5px solid var(--line);border-radius:22px;padding:18px;margin-bottom:12px;background:#fff}
        .plan.top{border-color:#2fd15a;box-shadow:0 0 0 4px rgba(47,209,90,.15)}
        .plan .pr{font-size:1.9rem;font-weight:800;letter-spacing:-.03em;margin:.2rem 0}
        .plan .pr small{font-size:.85rem;color:var(--mut)}
        .chip{display:inline-block;background:#e8f8ec;color:#11702f;font-weight:800;border-radius:999px;padding:.15rem .6rem;font-size:.75rem}
        .mal{background:#ffe9f3;color:#9b1c5a}
    </style>
</head>
<body>
<div class="w">
    <div class="top"><span class="brand"><b>L</b>LocalPro · Pagos</span></div>

    <div class="hero">
        <small>Pago del plan</small>
        <h1>{{ $tienda->nombre }}</h1>
        <p>
            Plan actual: <b style="color:#fff">{{ $tienda->plan->nombre ?? 'Gratis' }}</b>
            @if ($tienda->plan_vence) · {{ $tienda->plan_vencido ? 'venció' : 'vence' }} el {{ $tienda->plan_vence->format('d/m/Y') }}@endif
            @if ($tienda->codigo) · {{ $tienda->codigo }}@endif
        </p>
    </div>

    @if ($errors->any())<div class="msg mal">{{ $errors->first() }}</div>@endif

    @if (!$wompi)
        <div class="card">
            <b>El pago en línea todavía no está disponible.</b>
            <p class="mb-0" style="color:var(--mut)">Escríbanos y le ayudamos a pagar su plan.</p>
            @if ($soporte)<a class="btn btn-w btn-block" style="margin-top:12px" target="_blank" href="https://wa.me/{{ $soporte }}?text={{ rawurlencode('Hola LocalPro, quiero pagar el plan de ' . $tienda->nombre . ' (' . ($tienda->codigo ?: '#' . $tienda->id) . ').') }}"><i class="fa-brands fa-whatsapp"></i> Pagar con LocalPro</a>@endif
        </div>
    @else
        <p style="color:var(--mut);font-weight:600">Elija el plan. Al pagar, se activa de una vez para el negocio.</p>
        @foreach ($planes as $p)
        @php $periodo = $p->dias ? ($p->dias >= 365 ? 'año' : ($p->dias >= 28 ? 'mes' : $p->dias . ' días')) : null; @endphp
        <div class="plan {{ $p->dias >= 365 ? 'top' : '' }}">
            <div style="display:flex;justify-content:space-between;gap:8px;align-items:center">
                <b style="font-size:1.2rem">{{ $p->nombre }}</b>
                @if ($p->dias >= 365)<span class="chip">Recomendado</span>@endif
                @if ((int) $tienda->plan_id === (int) $p->id)<span class="chip">Su plan</span>@endif
            </div>
            @php $mc = $tienda->moneda_cobro; $usd = \App\Support\Moneda::esExtranjera($mc); @endphp
            <div class="pr">{{ $p->precioTexto($tienda) }} <small>{{ $mc }}{{ $periodo ? ' / ' . $periodo : '' }}</small></div>
            @if ($tienda->usa_bolivares)<p style="color:var(--mut);font-size:.85rem;margin:-.2rem 0 .3rem">Equivale a {{ \App\Support\Moneda::formato(round($p->precioEn($mc) * $tienda->tasa_bs_vigente, 2), 'VES') }} a la tasa de hoy.</p>@endif
            @if ($usd)<p style="color:var(--mut);font-size:.85rem;margin:.1rem 0 .6rem">Con Wompi se cobra en pesos colombianos: {{ \App\Support\Moneda::formato(\App\Support\Moneda::aCop($p->precioEn($mc), $mc), 'COP', true) }}. Su tarjeta lo convierte a su moneda.</p>@endif
            @if ($p->descripcion)<p style="color:var(--mut);margin:.2rem 0 .8rem">{{ $p->descripcion }}</p>@endif
            <form method="post" action="{{ url(\Illuminate\Support\Facades\URL::signedRoute('pagar.ir', ['tienda' => $tienda->id, 'plan' => $p->id], null, false)) }}" style="margin:0">
                @csrf
                <button class="btn btn-g btn-block"><i class="fa-solid fa-credit-card"></i> Pagar {{ $p->precioTexto($tienda) }}</button>
            </form>
        </div>
        @endforeach
        <p style="color:var(--mut);font-size:.85rem;text-align:center"><i class="fa-solid fa-lock"></i> Pago seguro con Wompi (Bancolombia): tarjeta, PSE, Nequi o botón Bancolombia.</p>
    @endif

    <div class="pie">LocalProo · Todos los derechos reservados</div>
</div>
</body>
</html>
