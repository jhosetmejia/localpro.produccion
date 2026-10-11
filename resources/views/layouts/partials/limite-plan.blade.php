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
@once
<style>
.lp-limite{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:linear-gradient(90deg,#0b0d0c,#1d2a22);color:#fff;border-radius:20px;padding:.9rem 1.1rem;margin:0 0 1rem}
.lp-limite.lleno{background:linear-gradient(90deg,#4a0f2a,#0b0d0c)}
.lp-limite-t{flex:1 1 240px;min-width:0}
.lp-limite-t b{display:block;font-size:.95rem}
.lp-limite-t b i{color:#a6f34a}
.lp-limite-t small{color:#b9c6be;font-weight:600}
.lp-limite-b{display:block;height:7px;border-radius:9px;background:rgba(255,255,255,.15);margin-top:.5rem;overflow:hidden}
.lp-limite-b i{display:block;height:100%;border-radius:9px;background:linear-gradient(90deg,#a6f34a,#16b34a)}
.lp-limite.lleno .lp-limite-b i{background:#ff5c93}
.lp-limite-btn{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;font-weight:900;border-radius:999px;padding:.6rem 1.1rem;text-decoration:none;white-space:nowrap}
.lp-limite-btn:hover{color:#0b0d0c;filter:brightness(1.05)}
</style>
@endonce
@endif
