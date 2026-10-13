@extends('layouts.app')

@section('title', 'Alquiler')

@push('css')
<style>
.aq-fil{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem}
.aq-fil a{border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.85rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.aq-fil a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.aq-card{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.9rem 1rem;margin-bottom:.6rem}
.aq-fecha{min-width:64px;text-align:center;background:#f1f5f2;border-radius:14px;padding:.45rem .5rem;line-height:1.1}
.aq-fecha b{display:block;font-size:1.35rem;font-weight:900}
.aq-fecha small{font-weight:800;font-size:.72rem;text-transform:uppercase;color:#6b7570}
.aq-fecha.hoy{background:#0b0d0c;color:#a6f34a}.aq-fecha.hoy small{color:#c9d3cc}
.aq-card .t{flex:1 1 240px;min-width:0}
.aq-card .t b{display:block;font-size:1rem}
.aq-card .t .num{font-weight:900;font-size:.72rem;background:#f1f5f2;border-radius:8px;padding:.1rem .45rem;margin-right:.3rem}
.aq-card .t small{display:block;color:#6b7570;font-weight:600;margin-top:.15rem}
.aq-card .t small i{width:16px;text-align:center}
.aq-card .t .nota{font-style:italic}
.aq-card .m{text-align:right;min-width:120px}
.aq-card .m b{display:block;font-size:1.1rem}
.aq-est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;white-space:nowrap}
.aq-est.programado{background:#fff5d6;color:#8a6400}.aq-est.hoy{background:#dcfce7;color:#14753a}.aq-est.pendiente{background:#ffe9f3;color:#b0173f}
.aq-est.realizado{background:#e8f8ec;color:#11702f}.aq-est.cancelado{background:#eef1ef;color:#6b7570}
.aq-acc{display:flex;gap:.35rem;flex-wrap:wrap}
.aq-acc .btn{border-radius:12px;font-weight:700}
.aq-acc form{display:inline}
</style>
@endpush

@section('content')
@php
    $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-calendar-days me-1"></i>Ventas</small><h1>Alqui<span>ler</span></h1>
            <span class="text-muted fw-semibold">Ponga en alquiler sus servicios: qué se alquila, fecha, hora, lugar, responsable y valor.</span></div>
        <div class="lp-quick"><a href="{{ route('alquileres.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Crear servicio en alquiler</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($ventaNueva)
    @php $waF = $ventaNueva->whatsapp_numero ? 'https://wa.me/' . $ventaNueva->whatsapp_numero . '?text=' . rawurlencode('Hola 👋 Gracias por alquilar con ' . $negocio->nombre . '. Aquí está su factura ' . $ventaNueva->numero . ' por ' . dinero($ventaNueva->total) . ': ' . $ventaNueva->url_factura) : null; @endphp
    <div class="lp-box mb-3 d-flex flex-wrap align-items-center gap-2" style="border-color:#2fd15a;background:#f4fcf6">
        <div class="flex-grow-1"><b><i class="fa-solid fa-file-invoice me-1"></i>Factura {{ $ventaNueva->numero }} lista.</b> <span class="text-muted">Envíesela al responsable por WhatsApp.</span></div>
        @if ($waF)<a href="{{ $waF }}" target="_blank" rel="noopener" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>Enviar factura</a>@endif
        <a href="{{ $ventaNueva->url_factura }}" target="_blank" class="btn btn-light"><i class="fa-solid fa-eye me-1"></i>Ver factura</a>
        <a href="{{ route('pos.ticket', $ventaNueva) }}" target="_blank" class="btn btn-light"><i class="fa-solid fa-print me-1"></i>Imprimir</a>
    </div>
    @endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-calendar-check"></i></div><div class="lbl">Alquileres próximos</div><div class="val">{{ $kpi['proximos'] }}</div><div class="sub">Por {{ $f($kpi['valorProximos']) }} · hoy: {{ $kpi['hoy'] }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Ganancia de alquileres este mes</div><div class="val">{{ $f($kpi['gananciaMes']) }}</div><div class="sub">Cobrado {{ $f($kpi['mes']) }} · comisiones {{ $f($kpi['mes'] - $kpi['gananciaMes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="lbl">Sin cobrar</div><div class="val">{{ $kpi['pendientes'] }}</div><div class="sub">Ya pasó la fecha y no se han cobrado</div></div>
    </div>

    <div class="lp-box">
        <form method="get" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="ver" value="{{ $filtro }}">
            <input name="q" value="{{ $q }}" class="form-control" style="max-width:320px" placeholder="Buscar: qué se alquila, lugar, celular o ALQ-000…" data-libre>
            <button class="btn btn-dark">Buscar</button>
        </form>
        <div class="aq-fil">
            @foreach (['proximos' => 'Próximos', 'hoy' => 'Hoy', 'pendientes' => 'Sin cobrar', 'realizados' => 'Cobrados', 'cancelados' => 'Cancelados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('alquileres.index', ['ver' => $k, 'q' => $q ?: null]) }}" class="{{ $filtro === $k ? 'on' : '' }}">{{ $v }}</a>
            @endforeach
        </div>

        @forelse ($lista as $a)
        @php
            $esHoy = $a->fecha->isToday();
            $est = $a->estado !== 'programado' ? $a->estado : ($esHoy ? 'hoy' : ($a->vencido ? 'pendiente' : 'programado'));
            $wa = !$a->telefono_wa ? null : 'https://wa.me/' . $a->telefono_wa . '?text=' . rawurlencode($a->venta
                ? 'Hola 👋 Gracias por alquilar con ' . $negocio->nombre . '. Aquí está su factura ' . $a->venta->numero . ' del alquiler ' . $a->numero . ' (' . $a->que_alquila . ') por ' . dinero($a->valor) . ': ' . $a->venta->url_factura
                : 'Hola 👋 Le escribimos de ' . $negocio->nombre . ' por el alquiler ' . $a->numero . ': ' . $a->que_alquila . ' el ' . $a->fecha->format('d/m/Y') . ' a las ' . $a->hora_texto . ' en ' . $a->lugar . '. Valor: ' . dinero($a->valor) . '.');
        @endphp
        <div class="aq-card">
            <div class="aq-fecha {{ $esHoy && $a->estado === 'programado' ? 'hoy' : '' }}"><small>{{ $meses[$a->fecha->month - 1] }}</small><b>{{ $a->fecha->format('d') }}</b><small>{{ $a->fecha->year != now()->year ? $a->fecha->year : $a->hora_texto }}</small></div>
            <div class="t">
                <b><span class="num">{{ $a->numero }}</span>{{ $a->que_alquila }}</b>
                <small><i class="fa-solid fa-clock"></i> {{ $a->fecha->format('d/m/Y') }} · {{ $a->hora_texto }}</small>
                <small><i class="fa-solid fa-location-dot"></i> {{ $a->lugar }}</small>
                <small><i class="fa-solid fa-user"></i> Responsable: {{ $a->responsable_telefono }}</small>
                @if ($a->nota)<small class="nota"><i class="fa-solid fa-note-sticky"></i> {{ $a->nota }}</small>@endif
            </div>
            <div class="m">
                <small class="text-muted fw-bold">Valor</small><b>{{ $f($a->valor) }}</b>
                @if ($a->con_comision)<small class="d-block text-muted fw-semibold">Comisión −{{ $f($a->comision) }}</small><small class="d-block fw-bold" style="color:#11702f">Ganancia {{ $f($a->ganancia) }}</small>@endif
                @if ($a->venta)<small class="d-block fw-bold">Venta {{ $a->venta->numero }}</small>@endif
                <span class="aq-est {{ $est }}">{{ ['programado' => 'Programado', 'hoy' => '📅 Es hoy', 'pendiente' => 'Sin cobrar', 'realizado' => '✅ Cobrado', 'cancelado' => 'Cancelado'][$est] }}</span>
            </div>
            <div class="aq-acc w-100 justify-content-end">
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="fa-brands fa-whatsapp me-1"></i>{{ $a->venta ? 'Enviar factura' : 'Responsable' }}</a>@endif
                @if ($a->venta)
                <a href="{{ $a->venta->url_factura }}" target="_blank" class="btn btn-sm btn-light"><i class="fa-solid fa-file-invoice me-1"></i>Factura</a>
                @elseif ($a->estado === 'programado')
                <form method="post" action="{{ route('alquileres.cobrar', $a) }}" class="d-inline-flex gap-1 align-items-center">@csrf
                    <select name="metodo_id" class="form-select form-select-sm" style="width:auto" required>
                        @foreach ($metodos as $m)<option value="{{ $m->id }}" @selected($m->es_efectivo)>{{ $m->nombre }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-dark" title="{{ $cajaAbierta ? '' : 'Primero se abre su caja' }}"><i class="fa-solid fa-cash-register me-1"></i>Cobrar y facturar</button>
                </form>
                <a href="{{ route('alquileres.edit', $a) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                <form method="post" action="{{ route('alquileres.estado', $a) }}" data-confirm="¿Cancelar el alquiler {{ $a->numero }}?" data-confirm-ok="Sí, cancelar" data-peligro>@csrf<input type="hidden" name="estado" value="cancelado"><button class="btn btn-sm btn-light"><i class="fa-solid fa-ban me-1"></i>Cancelar</button></form>
                @else
                <form method="post" action="{{ route('alquileres.estado', $a) }}">@csrf<input type="hidden" name="estado" value="programado"><button class="btn btn-sm btn-light"><i class="fa-solid fa-rotate-left me-1"></i>Volver a programado</button></form>
                @endif
                @unless ($a->venta)
                <form method="post" action="{{ route('alquileres.destroy', $a) }}" data-confirm="¿Eliminar el alquiler {{ $a->numero }}? No se puede deshacer." data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                @endunless
            </div>
        </div>
        @empty
        <div class="lp-empty text-center py-4">
            <div style="font-size:2.2rem">📅</div>
            <b>No hay alquileres aquí.</b>
            <div class="text-muted">Toque <b>"Crear servicio en alquiler"</b> para registrar el primero.</div>
        </div>
        @endforelse
        {{ $lista->links() }}
    </div>
</div>
@endsection
