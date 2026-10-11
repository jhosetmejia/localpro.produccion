{{-- Aviso de cuánto lleva del plan (productos o usuarios) y botón "Pásate a Pro". Uso: @include('layouts.partials.limite-plan', ['que' => 'productos']) --}}
@php $uso = \App\Support\TiendaActual::uso($que); @endphp
@if ($errors->any())
<div class="alert border-0 d-flex gap-2 align-items-center flex-wrap" style="background:#ffe9f3;color:#9b1c5a;border-radius:16px">
    <i class="fa-solid fa-triangle-exclamation"></i><span class="flex-grow-1">{{ $errors->first() }}</span>
    @if ($uso)<a href="{{ route('plan.index') }}" class="btn btn-sm btn-dark" style="border-radius:999px;font-weight:800">Pásate a Pro</a>@endif
</div>
@endif
@if ($uso && ($uso['gratis'] || $uso['actual'] >= $uso['max'] * 0.8))
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
