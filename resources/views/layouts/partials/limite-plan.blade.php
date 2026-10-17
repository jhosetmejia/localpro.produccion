{{-- Aviso de cuánto lleva del plan (productos o usuarios) y botón "Pásate a Pro". Uso: @include('layouts.partials.limite-plan', ['que' => 'productos']) --}}
@php $uso = \App\Support\TiendaActual::uso($que); @endphp
@if ($errors->any())
<div class="alert border-0 d-flex gap-2 align-items-center flex-wrap" style="background:#ffe9f3;color:#9b1c5a;border-radius:16px">
    <i class="fa-solid fa-triangle-exclamation"></i><span class="flex-grow-1">{{ $errors->first() }}</span>
    @if ($uso && !($que === 'productos' && !$uso['gratis']))<a href="{{ route('plan.index') }}" class="btn btn-sm btn-dark" style="border-radius:999px;font-weight:800">Pásate a Pro</a>@endif
</div>
@endif
@php
    // Planes pagos (productos): no se muestra el conteo; avisa cuando está por llenarse o se llenó y lo manda a LocalPro a ampliar
    $soloAlLimite = $uso && $que === 'productos' && !$uso['gratis'];
    $urlAmpliar = $soloAlLimite ? \App\Support\AmpliarProductos::urlWhatsapp(\App\Support\TiendaActual::tienda(), $uso['max']) : null;
@endphp
@if ($soloAlLimite)
@if ($uso['actual'] >= $uso['max'])
<div class="lp-limite lleno">
    <div class="lp-limite-t">
        <b><i class="fa-solid fa-box-open me-1"></i>Llegaste a tus {{ number_format($uso['max'], 0, ',', '.') }} productos</b>
        <small>Comunícate con LocalPro para ampliar tus productos: <b>{{ \App\Support\AmpliarProductos::oferta() }}</b>.</small>
    </div>
    <a href="{{ $urlAmpliar ?: route('plan.index') }}" @if ($urlAmpliar) target="_blank" rel="noopener" @endif class="lp-limite-btn"><i class="fa-brands fa-whatsapp me-1"></i>Ampliar con LocalPro</a>
</div>
@elseif ($uso['actual'] >= $uso['max'] - 20)
<div class="lp-limite">
    <div class="lp-limite-t">
        <b><i class="fa-solid fa-box me-1"></i>Le quedan {{ $uso['max'] - $uso['actual'] }} productos de {{ number_format($uso['max'], 0, ',', '.') }}</b>
        <small>Cuando necesite más, LocalPro le amplía: {{ \App\Support\AmpliarProductos::oferta() }}.</small>
    </div>
    <a href="{{ $urlAmpliar ?: route('plan.index') }}" @if ($urlAmpliar) target="_blank" rel="noopener" @endif class="lp-limite-btn"><i class="fa-brands fa-whatsapp me-1"></i>Ampliar</a>
</div>
@endif
@elseif ($uso && ($uso['gratis'] || $uso['actual'] >= $uso['max'] * 0.8))
@php $pct = min(100, round($uso['actual'] / max(1, $uso['max']) * 100)); $lleno = $uso['actual'] >= $uso['max']; @endphp
<div class="lp-limite {{ $lleno ? 'lleno' : '' }}">
    <div class="lp-limite-t">
        <b><i class="fa-solid fa-gem me-1"></i>Plan {{ $uso['plan'] }}: {{ $uso['actual'] }} de {{ $uso['max'] }} {{ $que === 'usuarios' ? 'personas (dueño + vendedores)' : 'productos' }}</b>
        <small>{{ $lleno ? ($que === 'usuarios' ? 'Llegó al límite. Pásate a Pro para sumar más vendedores.' : 'Llegó al límite. Pásate a Pro para crear más productos.') : ($que === 'usuarios' ? 'Con Pro suma más vendedores a su equipo.' : 'Con Pro crea muchos más productos.') }}</small>
        <span class="lp-limite-b"><i style="width:{{ $pct }}%"></i></span>
    </div>
    <a href="{{ route('plan.index') }}" class="lp-limite-btn"><i class="fa-solid fa-rocket me-1"></i>Pásate a Pro</a>
</div>

@endif
