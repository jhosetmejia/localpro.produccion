@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
@include('layouts.partials.alert')
@php
    $hora = (int) now()->format('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $porVentas = ($yo->meta_tipo ?? 'dinero') === 'ventas';
    // [título, meta, lleva, icono, cuándo]
    $metas = [['Mi meta de hoy', (float) $yo->meta_dia, $porVentas ? $numVentasHoy : $ventasHoy, 'fa-bullseye', 'hoy']];
    if ((float) $yo->meta_mes > 0) $metas[] = ['Mi meta del mes', (float) $yo->meta_mes, $porVentas ? $numVentasMes : $ventasMes, 'fa-calendar-check', 'este mes'];
    $val = fn ($n) => $porVentas ? (int) round($n) . ' ' . ((int) round($n) === 1 ? 'venta' : 'ventas') : dinero($n);
    $animo = ['¡Vamos con toda! 💪', '¡Usted puede! 🚀', '¡Cada cliente cuenta! 🙌', '¡A cerrar esa meta! 🔥'][now()->dayOfYear % 4];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small>{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</small>
            <h1>{{ $saludo }}, <span>{{ \Illuminate\Support\Str::words($yo->name, 1, '') }}</span> 👋</h1>
        </div>
        <div class="lp-quick">
            @can('usar-pos')
                @if ($caja)
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-cash-register me-1"></i>Vender</a>
                    <a href="{{ route('cajas.cerrar') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-lock me-1"></i>Cerrar caja</a>
                @else
                    <a href="{{ route('cajas.abrir') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-lock-open me-1"></i>Abrir caja y vender</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="vd-hoy">
        <div class="vd-k">
            <small><i class="fa-solid fa-sack-dollar me-1"></i>Mis ventas de hoy</small>
            <b>@pesos($ventasHoy)</b>
            <span>{{ $numVentasHoy }} {{ $numVentasHoy === 1 ? 'venta' : 'ventas' }}@if ($caja) · <em>● caja abierta</em>@endif</span>
        </div>
    </div>

    <div class="vm-metas {{ count($metas) === 1 ? 'una' : '' }}">
        @foreach ($metas as [$titulo, $meta, $lleva, $icono, $cuando])
        @php
            $pct = $meta > 0 ? min(100, round($lleva / $meta * 100)) : 0;
            $falta = max(0, $meta - $lleva);
            $ok = $meta > 0 && $lleva >= $meta;
        @endphp
        <div class="vm-meta {{ $ok ? 'ok' : '' }}">
            <div class="vm-anillo" style="--p:{{ $pct }}"><span class="{{ $porVentas && $meta > 0 ? 'num' : '' }}">{{ $meta <= 0 ? '—' : ($porVentas ? (int) $lleva . '/' . (int) $meta : $pct . '%') }}</span></div>
            <div class="vm-t">
                <small><i class="fa-solid {{ $icono }} me-1"></i>{{ $titulo }}</small>
                @if ($meta <= 0)
                    <b>Sin meta todavía</b>
                    <span>Su administrador le pondrá una meta. Mientras tanto, ¡a vender! 🚀</span>
                @elseif ($ok)
                    <b>¡Meta cumplida! 🎉</b>
                    <span>Lleva {{ $val($lleva) }} de {{ $val($meta) }} {{ $cuando }}. ¡Excelente trabajo!</span>
                @else
                    <b>Le {{ $porVentas && (int) ceil($falta) === 1 ? 'falta' : 'faltan' }} {{ $porVentas ? (int) ceil($falta) . ' ' . ((int) ceil($falta) === 1 ? 'venta' : 'ventas') : dinero($falta) }}</b>
                    <span>para cumplir su meta {{ $cuando }} · lleva {{ $val($lleva) }} de {{ $val($meta) }}. {{ $animo }}</span>
                @endif
                <span class="vm-barra"><i style="width:{{ $pct }}%"></i></span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="lp-box">
        <h3>Mis ventas de hoy</h3>
        <ul class="lp-list">
            @forelse ($ultimas as $v)
            <li>
                <div class="av"><i class="fa-solid fa-receipt"></i></div>
                <div class="t"><b>{{ $v->cliente_nombre ?: 'Cliente de mostrador' }}</b><small>{{ $v->numero }} · {{ $v->created_at->format('g:i a') }}</small></div>
                @if ($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif
                <span class="m">@pesos($v->total)</span>
            </li>
            @empty
            <li class="lp-empty">Aún no tiene ventas hoy. ¡La primera está por llegar! 🚀</li>
            @endforelse
        </ul>
    </div>
</div>
<style>
.vd-hoy{margin-bottom:1rem}
.vd-k{background:linear-gradient(135deg,#0b0d0c,#163b22);color:#fff;border-radius:24px;padding:1.3rem 1.4rem}
.vd-k small{display:block;font-weight:800;letter-spacing:.08em;text-transform:uppercase;font-size:.75rem;color:#a9b5ad}
.vd-k b{display:block;font-size:clamp(2rem,7vw,2.8rem);font-weight:900;color:#a6f34a;line-height:1.1;margin:.2rem 0}
.vd-k span{color:#cfd8d2;font-weight:700}.vd-k em{font-style:normal;color:#a6f34a}
.vm-metas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1.2rem}
.vm-metas.una{grid-template-columns:minmax(0,1fr)}
@media (max-width:767.98px){.vm-metas{grid-template-columns:minmax(0,1fr)}}
.vm-meta{display:flex;gap:1rem;align-items:center;background:#fff;border:1.5px solid var(--lp-line,#e3ebe6);border-radius:24px;padding:1.1rem 1.2rem;min-width:0}
.vm-meta.ok{border-color:#a6f34a;background:linear-gradient(135deg,#f3fde9,#fff)}
.vm-anillo{--p:0;width:92px;height:92px;border-radius:50%;flex:none;display:grid;place-items:center;background:conic-gradient(#16b34a calc(var(--p) * 1%), #e8eee9 0);position:relative}
.vm-anillo::before{content:"";position:absolute;inset:10px;border-radius:50%;background:#fff}
.vm-anillo span{position:relative;font-weight:900;font-size:1.25rem}
.vm-anillo span.num{font-size:1.05rem;letter-spacing:-.02em}
.vm-meta.ok .vm-anillo{background:conic-gradient(#a6f34a,#16b34a)}
.vm-t{flex:1;min-width:0}
.vm-t small{display:block;font-weight:800;color:#6b7570;text-transform:uppercase;letter-spacing:.06em;font-size:.72rem}
.vm-t small i{color:#16b34a}
.vm-t b{display:block;font-size:1.35rem;font-weight:900;line-height:1.2;margin:.15rem 0}
.vm-t > span{color:#5b665e;font-weight:600;font-size:.88rem}
.vm-barra{display:block;height:8px;border-radius:9px;background:#e8eee9;margin-top:.6rem;overflow:hidden}
.vm-barra i{display:block;height:100%;border-radius:9px;background:linear-gradient(90deg,#a6f34a,#16b34a);transition:width .6s}
</style>
@endsection
