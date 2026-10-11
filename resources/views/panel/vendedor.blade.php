@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
@include('layouts.partials.alert')
@php
    $hora = (int) now()->format('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $max = max(1, $semana->max('total'), (float) $yo->meta_dia);
    $metas = [
        ['Meta de hoy', (float) $yo->meta_dia, $ventasHoy, 'fa-bullseye', 'hoy'],
        ['Meta del mes', (float) $yo->meta_mes, $ventasMes, 'fa-calendar-check', 'este mes'],
    ];
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

    <div class="lp-kpis">
        <div class="lp-kpi hero">
            <div class="ic"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="lbl">Mis ventas de hoy</div>
            <div class="val">@pesos($ventasHoy)</div>
            <div class="sub">{{ $numVentasHoy }} {{ $numVentasHoy === 1 ? 'venta' : 'ventas' }}@if ($caja) · <span style="color:#a6f34a">● caja abierta</span>@endif</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-calendar-days"></i></div>
            <div class="lbl">Mis ventas del mes</div>
            <div class="val">@pesos($ventasMes)</div>
            <div class="sub">{{ $numVentasMes }} {{ $numVentasMes === 1 ? 'venta' : 'ventas' }}</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-receipt"></i></div>
            <div class="lbl">Venta promedio hoy</div>
            <div class="val">@pesos($numVentasHoy ? $ventasHoy / $numVentasHoy : 0)</div>
            <div class="sub">Por cliente</div>
        </div>
    </div>

    <div class="vm-metas">
        @foreach ($metas as [$titulo, $meta, $lleva, $icono, $cuando])
        @php
            $pct = $meta > 0 ? min(100, round($lleva / $meta * 100)) : 0;
            $falta = max(0, $meta - $lleva);
            $ok = $meta > 0 && $lleva >= $meta;
        @endphp
        <div class="vm-meta {{ $ok ? 'ok' : '' }}">
            <div class="vm-anillo" style="--p:{{ $pct }}"><span>{{ $meta > 0 ? $pct . '%' : '—' }}</span></div>
            <div class="vm-t">
                <small><i class="fa-solid {{ $icono }} me-1"></i>{{ $titulo }}</small>
                @if ($meta <= 0)
                    <b>Sin meta todavía</b>
                    <span>Su administrador le pondrá una meta. Mientras tanto, ¡a vender!</span>
                @elseif ($ok)
                    <b>¡Meta cumplida! 🎉</b>
                    <span>Vendió @pesos($lleva) de @pesos($meta){{ $lleva > $meta ? ' · superó por ' . dinero($lleva - $meta) : '' }}</span>
                @else
                    <b>Le faltan @pesos($falta)</b>
                    <span>Lleva @pesos($lleva) de @pesos($meta) {{ $cuando }}@if ($cuando === 'este mes' && $diasRestantes > 0) · unos @pesos($falta / $diasRestantes) por día @endif</span>
                @endif
                <span class="vm-barra"><i style="width:{{ $pct }}%"></i></span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Mis ventas de la semana</h3>
            <div class="lp-bars">
                @foreach ($semana as $d)
                <div class="lp-bar {{ $loop->last ? 'today' : '' }}">
                    <i style="height:{{ max(3, round($d['total'] / $max * 100)) }}%" data-v="@pesos($d['total'])"></i>
                    <span>{{ $d['label'] }}</span>
                </div>
                @endforeach
            </div>
            @if ((float) $yo->meta_dia > 0)<small class="text-muted d-block mt-2"><i class="fa-solid fa-bullseye me-1" style="color:#16b34a"></i>Meta diaria: @pesos($yo->meta_dia)</small>@endif
        </div>
        <div class="lp-box">
            <h3>Mis últimas ventas @can('usar-pos')<a href="{{ route('pos.ventas') }}">Ver todas</a>@endcan</h3>
            <ul class="lp-list">
                @forelse ($ultimas as $v)
                <li>
                    <div class="av"><i class="fa-solid fa-receipt"></i></div>
                    <div class="t"><b>{{ $v->cliente_nombre ?: 'Cliente de mostrador' }}</b><small>{{ $v->numero }} · {{ $v->created_at->diffForHumans() }}</small></div>
                    @if ($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif
                    <span class="m">@pesos($v->total)</span>
                </li>
                @empty
                <li class="lp-empty">Aún no tiene ventas. Abra su caja y haga la primera 🚀</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
<style>
.vm-metas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1.2rem}
@media (max-width:767.98px){.vm-metas{grid-template-columns:minmax(0,1fr)}}
.vm-meta{display:flex;gap:1rem;align-items:center;background:#fff;border:1.5px solid var(--lp-line,#e3ebe6);border-radius:24px;padding:1.1rem 1.2rem;min-width:0}
.vm-meta.ok{border-color:#a6f34a;background:linear-gradient(135deg,#f3fde9,#fff)}
.vm-anillo{--p:0;width:92px;height:92px;border-radius:50%;flex:none;display:grid;place-items:center;background:conic-gradient(#16b34a calc(var(--p) * 1%), #e8eee9 0);position:relative}
.vm-anillo::before{content:"";position:absolute;inset:10px;border-radius:50%;background:#fff}
.vm-anillo span{position:relative;font-weight:900;font-size:1.25rem}
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
