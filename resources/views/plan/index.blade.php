@extends('layouts.app')

@section('title', 'Mi plan')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>LocalPro</small><h1>Mi <span>plan</span></h1><span class="text-muted fw-semibold">{{ $tienda->nombre }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @include('layouts.partials.cuenta-regresiva')

    @if ($tienda->plan_vencido)
    <div class="lp-box mb-3" style="background:#ffe9f3;border-color:#ffc3de">
        <h3 style="color:#9b1c5a"><span><i class="fa-solid fa-circle-exclamation me-1"></i> Su plan venció el {{ $tienda->plan_vence->format('d/m/Y') }}</span></h3>
        @php
            $borraPV = $tienda->datos_depurados ? null : \App\Support\AvisosPlan::fechaBorrado($tienda);
            $gratisPV = $borraPV !== null;
        @endphp
        @if ($tienda->datos_depurados)
        <p class="mb-0" style="color:#9b1c5a">Como la prueba terminó sin activar un plan, se eliminaron los productos, imágenes y ventas del negocio. Sus datos de contacto siguen guardados: active un plan y vuelva a empezar cuando quiera.</p>
        @elseif ($gratisPV)
        <p class="mb-0" style="color:#9b1c5a">Su prueba gratis terminó. Active un plan a más tardar el <b>{{ $borraPV->copy()->subDay()->locale('es')->isoFormat('D [de] MMMM') }}</b>: después de esa fecha se eliminan sus productos, imágenes, ventas y clientes (así lo dicen las <a href="{{ route('legal.terminos') }}" target="_blank" style="color:inherit;font-weight:800">condiciones de uso</a>).</p>
        @else
        <p class="mb-0" style="color:#9b1c5a">Renuévelo para seguir vendiendo. Sus datos están guardados y seguros.</p>
        @endif
    </div>
    @endif

    <div class="lp-kpis dos">
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

    <div class="lp-box mb-3" style="background:linear-gradient(120deg,#0b0d0c,#163b22);color:#fff;border:0">
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <div style="width:56px;height:56px;border-radius:18px;background:linear-gradient(135deg,#a6f34a,#16b34a);display:grid;place-items:center;font-size:1.5rem;color:#0b0d0c;flex:none"><i class="fa-solid fa-globe"></i></div>
            <div style="flex:1;min-width:220px">
                <b style="font-size:1.15rem">Plus: su página web con tienda en línea que aparece en Google</b>
                <div style="color:#b9c6be;font-weight:600">Sus productos por categorías, botón <b style="color:#a6f34a">Comprar</b> por WhatsApp y cada producto con su propia página para que lo encuentren en los resultados de búsqueda. Incluida en los planes pagos.</div>
            </div>
            <a href="{{ $tienda->url_catalogo_navegador }}" target="_blank" rel="noopener" class="btn" style="background:#a6f34a;color:#0b0d0c;font-weight:800;border-radius:999px">Ver mi tienda en línea</a>
        </div>
    </div>

    @php $usoP = \App\Support\TiendaActual::uso('productos'); @endphp
    @if ($usoP && !$usoP['gratis'])
    @php $urlAmp = \App\Support\AmpliarProductos::urlWhatsapp($tienda, $usoP['max']); $pctP = min(100, round($usoP['actual'] / max(1, $usoP['max']) * 100)); @endphp
    <div class="lp-box mb-3">
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <div style="flex:1;min-width:220px">
                <b style="font-size:1.1rem"><i class="fa-solid fa-box me-1" style="color:#16b34a"></i>Productos: {{ number_format($usoP['actual'], 0, ',', '.') }} de {{ number_format($usoP['max'], 0, ',', '.') }}</b>
                @if ($usoP['extra'])<small class="text-muted d-block">Incluye {{ number_format($usoP['extra'], 0, ',', '.') }} productos extra que amplió con LocalPro.</small>@endif
                <div class="progress mt-2" style="height:8px;border-radius:99px"><div class="progress-bar" style="width:{{ $pctP }}%;background:{{ $pctP >= 100 ? '#e0245e' : '#16b34a' }}"></div></div>
                <small class="d-block mt-2 fw-semibold">¿Necesita más? Amplíe con LocalPro: <b>{{ \App\Support\AmpliarProductos::oferta() }}</b>.</small>
            </div>
            <a href="{{ $urlAmp ?: '#' }}" target="_blank" rel="noopener" class="btn btn-success" style="border-radius:999px;font-weight:800"><i class="fa-brands fa-whatsapp me-1"></i>Ampliar productos</a>
        </div>
    </div>
    @endif

    <div class="lp-counts mb-3" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
        @foreach ($planes as $p)
        <div class="lp-count" style="padding:1.4rem;{{ (int) $tienda->plan_id === (int) $p->id ? 'border-color:#2fd15a;box-shadow:0 0 0 4px rgba(47,209,90,.15)' : '' }}">
            <div class="d-flex justify-content-between align-items-start">
                <b style="font-size:1.25rem">{{ $p->nombre }}</b>
                @if ((int) $tienda->plan_id === (int) $p->id)<span class="badge bg-success">Su plan</span>@endif
            </div>
            <div style="font-size:1.8rem;font-weight:800;letter-spacing:-.03em;margin:.4rem 0">{{ $p->es_gratis ? 'Gratis' : $p->precioTexto($tienda) }}<small style="font-size:.85rem;color:var(--lp-muted)">{{ $p->es_gratis ? '' : ' ' . $tienda->moneda_cobro . ($p->dias ? ' / ' . ($p->dias >= 365 ? 'año' : ($p->dias >= 28 ? 'mes' : $p->dias . ' días')) : '') }}</small></div>
            <p class="text-muted small mb-2">{{ $p->descripcion }}</p>
            <p class="small fw-bold mb-3" style="color:#11702f"><i class="fa-solid fa-box me-1"></i>{{ $p->max_productos ? 'Hasta ' . number_format($p->max_productos, 0, ',', '.') . ' productos' . ($p->es_gratis ? '' : ' (ampliable)') : 'Productos ilimitados' }} · <i class="fa-solid fa-users ms-1 me-1"></i>{{ (float) $p->precio <= 0 && !$p->dias ? '3 meses · ' : '' }}{{ $p->max_usuarios ? ($p->max_usuarios > 1 ? 'Dueño + ' . ($p->max_usuarios - 1) . ' vendedores' : 'Solo el dueño') : 'Vendedores ilimitados' }}</p>
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
            <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="t"><b>{{ $pg->plan->nombre ?? 'Plan' }} · {{ $pg->monto_texto }}</b><small>{{ $pg->created_at->format('d/m/Y') }} · {{ ucfirst($pg->metodo) }}{{ $pg->periodo_hasta ? ' · hasta ' . $pg->periodo_hasta->format('d/m/Y') : '' }}</small></div>
                <span class="badge {{ $pg->estado === 'aprobado' ? 'bg-success' : ($pg->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($pg->estado) }}</span></li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
