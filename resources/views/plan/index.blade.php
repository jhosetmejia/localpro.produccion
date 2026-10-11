@extends('layouts.app')

@section('title', 'Mi plan')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>LocalPro</small><h1>Mi <span>plan</span></h1><span class="text-muted fw-semibold">{{ $tienda->nombre }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    @if ($tienda->plan_vencido)
    <div class="lp-box mb-3" style="background:#ffe9f3;border-color:#ffc3de">
        <h3 style="color:#9b1c5a"><span><i class="fa-solid fa-circle-exclamation me-1"></i> Su plan venció el {{ $tienda->plan_vence->format('d/m/Y') }}</span></h3>
        <p class="mb-0" style="color:#9b1c5a">Renuévelo para seguir vendiendo. Sus datos están guardados y seguros.</p>
    </div>
    @endif

    <div class="lp-kpis" style="grid-template-columns:1.3fr 1fr">
        <div class="lp-kpi hero">
            <div class="ic"><i class="fa-solid fa-gem"></i></div>
            <div class="lbl">Plan actual</div>
            <div class="val">{{ $tienda->plan->nombre ?? 'Gratis' }}</div>
            <div class="sub">{{ $tienda->plan_vence ? ($tienda->plan_vencido ? 'Vencido' : 'Vence el ' . $tienda->plan_vence->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) : 'Sin vencimiento' }}</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-headset"></i></div>
            <div class="lbl">¿Necesita ayuda?</div>
            <div class="val" style="font-size:1.4rem">Soporte LocalPro</div>
            <div class="sub">@if ($soporte)<a href="https://wa.me/{{ preg_replace('/\D/', '', $soporte) }}?text={{ rawurlencode('Hola LocalPro, necesito ayuda con mi tienda ' . $tienda->nombre . ' (#' . $tienda->id . ')') }}" target="_blank" class="fw-bold" style="color:#16b34a">Escribir por WhatsApp</a>@else Escríbanos desde la página principal @endif</div>
        </div>
    </div>

    <div class="lp-counts mb-3" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
        @foreach ($planes as $p)
        <div class="lp-count" style="padding:1.4rem;{{ (int) $tienda->plan_id === (int) $p->id ? 'border-color:#2fd15a;box-shadow:0 0 0 4px rgba(47,209,90,.15)' : '' }}">
            <div class="d-flex justify-content-between align-items-start">
                <b style="font-size:1.25rem">{{ $p->nombre }}</b>
                @if ((int) $tienda->plan_id === (int) $p->id)<span class="badge bg-success">Su plan</span>@endif
            </div>
            <div style="font-size:1.8rem;font-weight:800;letter-spacing:-.03em;margin:.4rem 0">{{ $p->es_gratis ? 'Gratis' : '$' . number_format($p->precio, 0, ',', '.') }}<small style="font-size:.85rem;color:var(--lp-muted)">{{ $p->es_gratis ? '' : ' COP' . ($p->dias ? ' / ' . ($p->dias >= 365 ? 'año' : ($p->dias >= 28 ? 'mes' : $p->dias . ' días')) : '') }}</small></div>
            <p class="text-muted small mb-3">{{ $p->descripcion }}</p>
            @unless ($p->es_gratis)
                @can('editar-negocio')
                <form method="post" action="{{ route('plan.pagar', $p) }}">@csrf
                    <button class="btn {{ $wompi ? 'btn-primary' : 'btn-success' }} w-100">
                        @if ($wompi)<i class="fa-solid fa-credit-card me-1"></i>Pagar en línea @else<i class="fa-brands fa-whatsapp me-1"></i>Pagar con LocalPro @endif
                    </button>
                </form>
                @endcan
            @endunless
        </div>
        @endforeach
    </div>
    @if ($wompi)<p class="text-muted small"><i class="fa-solid fa-lock me-1"></i>Pagos seguros con Wompi (Bancolombia): tarjeta, PSE, Nequi o Bancolombia.</p>@endif

    @if ($pagos->count())
    <div class="lp-box">
        <h3>Historial de pagos</h3>
        <ul class="lp-list">
            @foreach ($pagos as $pg)
            <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="t"><b>{{ $pg->plan->nombre ?? 'Plan' }} · ${{ number_format($pg->monto, 0, ',', '.') }}</b><small>{{ $pg->created_at->format('d/m/Y') }} · {{ ucfirst($pg->metodo) }}{{ $pg->periodo_hasta ? ' · hasta ' . $pg->periodo_hasta->format('d/m/Y') : '' }}</small></div>
                <span class="badge {{ $pg->estado === 'aprobado' ? 'bg-success' : ($pg->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($pg->estado) }}</span></li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
