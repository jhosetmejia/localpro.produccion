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
        </div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi hero">
            <div class="ic"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="lbl">Vendido hoy</div>
            <div class="val">@pesos($ventasHoy)</div>
            <div class="sub">{{ $numVentasHoy }} {{ $numVentasHoy === 1 ? 'venta' : 'ventas' }} en el POS</div>
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
