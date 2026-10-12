@extends('layouts.app')

@section('title', 'Panel')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({ title: @json(session('success')), icon: 'success', timer: 2200, showConfirmButton: false });
    });
</script>
@endif

@php
    $hora = (int) now()->format('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $max = max(1, $semana->max('total'));
@endphp

<div class="lp-dash">
    @php $tPlan = \App\Support\TiendaActual::tienda(); @endphp
    @if ($tPlan?->plan && (float) $tPlan->plan->precio <= 0 && $tPlan->plan_vence && !$tPlan->plan_vencido)
    <div class="lp-limite" style="margin-bottom:.8rem">
        <div class="lp-limite-t"><b><i class="fa-solid fa-gift me-1" style="color:#a6f34a"></i>Le quedan {{ $tPlan->dias_para_vencer }} {{ $tPlan->dias_para_vencer == 1 ? 'día' : 'días' }} de su prueba gratis</b><small>Úselo con todo su equipo. Al terminar, elija un plan para seguir vendiendo sin perder nada.</small></div>
        <a href="{{ route('plan.index') }}" class="lp-limite-btn"><i class="fa-solid fa-rocket me-1"></i>Pásate a Pro</a>
    </div>
    @endif
    @include('layouts.partials.limite-plan', ['que' => 'productos'])
    <div class="lp-hello">
        <div>
            <small>{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</small>
            <h1>{{ $saludo }}, <span>{{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</span> 👋</h1>
        </div>
        <div class="lp-quick">
            @can('usar-pos')
                @if ($caja)
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-cash-register me-1"></i>Vender (POS)</a>
                    <a href="{{ route('cajas.cerrar') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-lock me-1"></i>Cerrar caja</a>
                @else
                    <a href="{{ route('cajas.abrir') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-lock-open me-1"></i>Abrir caja y vender</a>
                @endif
            @endcan
            @can('crear-producto')<a href="{{ route('productos.create') }}" class="btn btn-ghost btn-lg" style="border:2px solid #0b0d0c;background:#fff"><i class="fa-solid fa-plus me-1"></i>Producto</a>@endcan
            @php $tW = \App\Support\TiendaActual::tienda(); @endphp
            @if ($tW?->catalogo_activo)<a href="{{ $tW->url_catalogo }}" target="_blank" class="btn btn-ghost btn-lg" style="border:2px solid #16b34a;background:#f2fbf3;color:#11702f"><i class="fa-solid fa-globe me-1"></i>Mi tienda en línea</a>@endif
        </div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi hero">
            <div class="ic"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="lbl">Vendido hoy</div>
            <div class="val">@pesos($ventasHoy)</div>
            <div class="sub">{{ $numVentasHoy }} {{ $numVentasHoy === 1 ? 'venta' : 'ventas' }}@can('ver-reportes') · ganancia <b style="color:#a6f34a">@pesos($gananciaHoy)</b>@endcan</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="lbl">Ventas del mes</div>
            <div class="val">@pesos($ventasMes)</div>
            <div class="sub">Desde el día 1</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-shirt"></i></div>
            <div class="lbl">Prendas en inventario</div>
            <div class="val">{{ dinero($unidades, false) }}</div>
            <div class="sub">
                @if ($caja) <span style="color:#16b34a">● Su caja está abierta</span>
                @else Unidades disponibles @endif
            </div>
        </div>
    </div>


    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-bullseye me-1" style="color:#16b34a"></i> Metas de su equipo de caja</span>@can('crear-user')<a href="{{ route('users.create') }}">+ Vendedor</a>@endcan</h3>
        @if ($equipo->isEmpty())
            <p class="lp-empty mb-0">Aún no tiene vendedores. Cree uno en <b>Usuarios</b> con el rol "Vendedor (caja)": venden en el POS, no ven ganancias y usted les pone metas aquí.</p>
        @else
        <div class="eq-lista">
            @foreach ($equipo as $u)
            @php
                $pd = $u->meta_dia > 0 ? min(100, round($u->vendido_dia / $u->meta_dia * 100)) : null;
                $pm = $u->meta_mes > 0 ? min(100, round($u->vendido_mes / $u->meta_mes * 100)) : null;
            @endphp
            <div class="eq-f">
                <div class="eq-n"><span class="eq-av">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span><div><b>{{ $u->name }}</b><small>Hoy @pesos($u->vendido_dia) · Mes @pesos($u->vendido_mes)</small></div></div>
                <div class="eq-m"><small>Meta de hoy</small>@if ($pd !== null)<span class="eq-b"><i style="width:{{ $pd }}%"></i></span><em>{{ $pd }}% de @pesos($u->meta_dia)</em>@else<em class="text-muted">Sin meta</em>@endif</div>
                <div class="eq-m"><small>Meta del mes</small>@if ($pm !== null)<span class="eq-b"><i style="width:{{ $pm }}%"></i></span><em>{{ $pm }}% de @pesos($u->meta_mes)</em>@else<em class="text-muted">Sin meta</em>@endif</div>
                <button type="button" class="btn btn-sm eq-btn" onclick="this.nextElementSibling.classList.toggle('d-none')"><i class="fa-solid fa-pen me-1"></i>Meta</button>
                <form method="post" action="{{ route('equipo.meta', $u) }}" class="eq-form d-none">
                    @csrf
                    <label><small>Meta del día</small><input data-dinero type="number" min="0" name="meta_dia" class="form-control" value="{{ $u->meta_dia ? (int) $u->meta_dia : '' }}" placeholder="0"></label>
                    <label><small>Meta del mes</small><input data-dinero type="number" min="0" name="meta_mes" class="form-control" value="{{ $u->meta_mes ? (int) $u->meta_mes : '' }}" placeholder="0"></label>
                    <button class="btn btn-dark">Guardar</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <style>
    .eq-lista{display:flex;flex-direction:column}
    .eq-f{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) minmax(0,1fr) auto;gap:.9rem;align-items:center;padding:.8rem 0;border-bottom:1px dashed var(--lp-line,#e3ebe6)}
    .eq-f:last-child{border-bottom:0}
    .eq-n{display:flex;gap:.7rem;align-items:center;min-width:0}
    .eq-n b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .eq-n small{color:#6b7570;font-weight:600;font-size:.8rem}
    .eq-av{width:40px;height:40px;border-radius:14px;background:#e8f8ec;color:#11702f;display:grid;place-items:center;font-weight:900;flex:none}
    .eq-m small{display:block;font-size:.7rem;font-weight:800;color:#7a8580;text-transform:uppercase;letter-spacing:.05em}
    .eq-m em{font-style:normal;font-weight:700;font-size:.8rem}
    .eq-b{display:block;height:7px;border-radius:9px;background:#e8eee9;margin:.25rem 0;overflow:hidden}
    .eq-b i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a);border-radius:9px}
    .eq-btn{border:1.5px solid #cfe9d6;background:#f2fbf3;color:#11702f;font-weight:800;border-radius:999px}
    .eq-form{grid-column:1/-1;display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;background:#f7faf8;border-radius:16px;padding:.8rem}
    .eq-form label{flex:1 1 160px;margin:0}.eq-form small{font-weight:700;color:#5b665e}
    @media (max-width:767.98px){.eq-f{grid-template-columns:1fr 1fr}.eq-n{grid-column:1/-1}.eq-btn{grid-column:1/-1}}
    </style>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Ventas de la semana <a href="{{ route('pos.ventas') }}">Ver todas</a></h3>
            <div class="lp-bars">
                @foreach ($semana as $d)
                <div class="lp-bar {{ $loop->last ? 'today' : '' }}">
                    <i style="height:{{ max(3, round($d['total'] / $max * 100)) }}%" data-v="@pesos($d['total'])"></i>
                    <span>{{ $d['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lp-box">
            <h3>Se está agotando <a href="{{ route('inventario.index', ['filtro' => 'bajo']) }}">Inventario</a></h3>
            <ul class="lp-list">
                @forelse ($stockBajo as $v)
                <li>
                    <div class="av"><i class="fa-solid fa-shirt"></i></div>
                    <div class="t"><b>{{ $v->producto->nombre ?? '' }}</b><small>{{ $v->etiqueta }}</small></div>
                    <span class="lp-pill {{ $v->stock > 0 ? 'warn' : '' }}">{{ $v->stock > 0 ? $v->stock . ' und' : 'Agotado' }}</span>
                </li>
                @empty
                <li class="lp-empty"><i class="fa-solid fa-circle-check me-1" style="color:#2fd15a"></i>Todo el inventario está bien surtido.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-grid" style="grid-template-columns:1fr">
        <div class="lp-box">
            <h3>Últimas ventas <a href="{{ route('pos.ventas') }}">Historial</a></h3>
            <ul class="lp-list">
                @forelse ($ultimasVentas as $v)
                <li>
                    <div class="av"><i class="fa-solid fa-receipt"></i></div>
                    <div class="t"><b>{{ $v->cliente_nombre ?: 'Cliente de mostrador' }}</b><small>{{ $v->numero }} · {{ $v->created_at->diffForHumans() }} · {{ $v->user->name ?? '' }}</small></div>
                    @if ($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif
                    <span class="m">@pesos($v->total)</span>
                </li>
                @empty
                <li class="lp-empty">Aún no hay ventas. Abra su caja y haga la primera desde el POS 🚀</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-counts">
        @foreach ($conteos as [$nombre, $total, $icono, $ruta, $permiso])
            @can($permiso)
            <a class="lp-count" href="{{ route($ruta) }}">
                <div class="ci"><i class="fa-solid {{ $icono }}"></i></div>
                <b>{{ dinero($total, false) }}</b>
                <small>{{ $nombre }}</small>
            </a>
            @endcan
        @endforeach
    </div>
</div>
@endsection
