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
.aq-bar{height:8px;border-radius:99px;background:#eef1ef;overflow:hidden;margin:.3rem 0}
.aq-bar i{display:block;height:100%;background:#2fd15a}
.aq-ent{font-size:.72rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;white-space:nowrap;display:inline-block;margin-top:.25rem}
.aq-ent.pendiente{background:#eef1ef;color:#45504a}.aq-ent.alquilado{background:#e0edff;color:#1d4ed8}.aq-ent.devuelto{background:#e8f8ec;color:#11702f}.aq-ent.novedad{background:#fff1e0;color:#a15c00}
.aq-sec{width:100%;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;border-top:1px dashed #e3ebe6;padding-top:.55rem}
.aq-sec>span.lbl{font-weight:800;font-size:.78rem;color:#6b7570;min-width:92px}
.aq-abonos{width:100%;font-size:.8rem}
.aq-abonos summary{cursor:pointer;font-weight:700;color:#45504a}
</style>
@endpush

@section('content')
@php
    $esAdmin = \App\Http\Controllers\alquilerController::esAdmin();
    $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-calendar-days me-1"></i>Ventas</small><h1>Alqui<span>ler</span></h1>
            <span class="text-muted fw-semibold">Ponga en alquiler sus servicios: qué se alquila, fecha, horario, lugar, quién contrata y valor.</span></div>
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

    @if ($abonoNuevo && $abonoNuevo->alquiler)
    @php
        $al = $abonoNuevo->alquiler;
        $waA = $al->telefono_wa ? 'https://wa.me/' . $al->telefono_wa . '?text=' . rawurlencode('Hola 👋 En ' . $negocio->nombre . ' recibimos su abono de *' . dinero($abonoNuevo->monto) . '* (comprobante ' . $abonoNuevo->numero . ') para el alquiler ' . $al->numero . ': ' . $al->que_alquila . ' el ' . $al->fecha->format('d/m/Y') . ".\n\nValor: " . dinero($al->valor) . "\nAbonado: " . dinero($al->abonado) . "\n*Le falta: " . dinero($al->saldo) . '*') : null;
    @endphp
    <div class="lp-box mb-3 d-flex flex-wrap align-items-center gap-2" style="border-color:#2fd15a;background:#f4fcf6">
        <div class="flex-grow-1"><b><i class="fa-solid fa-receipt me-1"></i>Abono {{ $abonoNuevo->numero }} por {{ dinero($abonoNuevo->monto) }}.</b> <span class="text-muted">Le falta {{ dinero($al->saldo) }}. Envíele el comprobante al responsable.</span></div>
        @if ($waA)<a href="{{ $waA }}" target="_blank" rel="noopener" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>Enviar comprobante</a>@endif
    </div>
    @endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-calendar-check"></i></div><div class="lbl">Alquileres próximos</div><div class="val">{{ $kpi['proximos'] }}</div><div class="sub">Por {{ $f($kpi['valorProximos']) }} · hoy: {{ $kpi['hoy'] }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Ganancia de alquileres este mes</div><div class="val">{{ $f($kpi['gananciaMes']) }}</div><div class="sub">Cobrado {{ $f($kpi['mes']) }} · comisiones {{ $f($kpi['mes'] - $kpi['gananciaMes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-hand-holding-dollar"></i></div><div class="lbl">Abonando</div><div class="val">{{ $kpi['abonando'] }}</div><div class="sub">Les falta {{ $f($kpi['saldoAbonando']) }} · sin cobrar (fecha pasada): {{ $kpi['pendientes'] }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-key"></i></div><div class="lbl">En manos del cliente</div><div class="val">{{ $kpi['alquilados'] }}</div><div class="sub">{{ $kpi['vencidosSinDevolver'] ? $kpi['vencidosSinDevolver'] . ' ya pasaron la fecha y no han devuelto' : 'Pendientes por devolver' }}</div></div>
    </div>

    <div class="lp-box">
        <form method="get" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="ver" value="{{ $filtro }}">
            <input name="q" value="{{ $q }}" class="form-control" style="max-width:320px" placeholder="Buscar: qué se alquila, lugar, celular o ALQ-000…" data-libre>
            <button class="btn btn-dark">Buscar</button>
        </form>
        <div class="aq-fil">
            @foreach (['proximos' => 'Próximos', 'hoy' => 'Hoy', 'abonando' => 'Abonando', 'alquilados' => 'Sin devolver', 'pendientes' => 'Sin cobrar', 'realizados' => 'Pagados', 'cancelados' => 'Cancelados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('alquileres.index', ['ver' => $k, 'q' => $q ?: null]) }}" class="{{ $filtro === $k ? 'on' : '' }}">{{ $v }}</a>
            @endforeach
        </div>

        @forelse ($lista as $a)
        @php
            $esHoy = $a->fecha->isToday();
            $est = $a->estado !== 'programado' ? $a->estado : ($esHoy ? 'hoy' : ($a->vencido ? 'pendiente' : 'programado'));
            $wa = !$a->telefono_wa ? null : 'https://wa.me/' . $a->telefono_wa . '?text=' . rawurlencode($a->venta
                ? 'Hola 👋 Gracias por alquilar con ' . $negocio->nombre . '. Aquí está su factura ' . $a->venta->numero . ' del alquiler ' . $a->numero . ' (' . $a->que_alquila . ') por ' . dinero($a->valor) . ': ' . $a->venta->url_factura
                : 'Hola 👋 Le escribimos de ' . $negocio->nombre . ' por el alquiler ' . $a->numero . ': ' . $a->que_alquila . ' el ' . $a->fecha->format('d/m/Y') . ', ' . $a->horario_texto . ' en ' . $a->lugar . '. Valor: ' . dinero($a->valor) . '.');
        @endphp
        <div class="aq-card">
            <div class="aq-fecha {{ $esHoy && $a->estado === 'programado' ? 'hoy' : '' }}"><small>{{ $meses[$a->fecha->month - 1] }}</small><b>{{ $a->fecha->format('d') }}</b><small>{{ $a->fecha->year != now()->year ? $a->fecha->year : $a->hora_texto }}</small></div>
            <div class="t">
                <b><span class="num">{{ $a->numero }}</span>{{ $a->que_alquila }}</b>
                <small><i class="fa-solid fa-clock"></i> {{ $a->fecha->format('d/m/Y') }} · {{ $a->horario_texto }}</small>
                <small><i class="fa-solid fa-location-dot"></i> {{ $a->lugar }}</small>
                <small><i class="fa-solid fa-user"></i> Contrata: {{ $a->responsable_telefono }}</small>
                @if ($a->encargado_nombre)<small><i class="fa-solid fa-user-gear"></i> Encargado: {{ $a->encargado_nombre }}@if ($a->encargado_telefono) · {{ $a->encargado_telefono }}@endif</small>@endif
                @if ($a->nota)<small class="nota"><i class="fa-solid fa-note-sticky"></i> {{ $a->nota }}</small>@endif
            </div>
            <div class="m">
                <small class="text-muted fw-bold">Valor</small><b>{{ $f($a->valor) }}</b>
                @if ($a->con_comision)<small class="d-block text-muted fw-semibold">Comisión −{{ $f($a->comision) }}</small><small class="d-block fw-bold" style="color:#11702f">Ganancia {{ $f($a->ganancia) }}</small>@endif
                @if (!$a->venta && $a->abonado > 0)
                <div class="aq-bar"><i style="width:{{ min(100, round($a->abonado / max($a->valor, 1) * 100)) }}%"></i></div>
                <small class="d-block fw-semibold" style="color:#11702f">Abonado {{ $f($a->abonado) }}</small>
                <small class="d-block fw-bold" style="color:#b0173f">Falta {{ $f($a->saldo) }}</small>
                @endif
                @if ($a->venta)<small class="d-block fw-bold">Venta {{ $a->venta->numero }}</small>@endif
                <span class="aq-est {{ $est }}">{{ ['programado' => $a->abonado > 0 ? '💵 Abonando' : 'Programado', 'hoy' => '📅 Es hoy', 'pendiente' => 'Sin cobrar', 'realizado' => '✅ Pagado', 'cancelado' => 'Cancelado'][$est] }}</span>
                @if ($a->estado !== 'cancelado')<br><span class="aq-ent {{ $a->entrega === 'devuelto' && $a->devolucion_estado === 'con_novedad' ? 'novedad' : $a->entrega }}">{{ $a->entrega_texto }}</span>@endif
            </div>
            <div class="aq-acc w-100 justify-content-end">
                @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="fa-brands fa-whatsapp me-1"></i>{{ $a->venta ? 'Enviar factura' : 'Escribir al cliente' }}</a>@endif
                @if ($a->venta)
                <a href="{{ $a->venta->url_factura }}" target="_blank" class="btn btn-sm btn-light"><i class="fa-solid fa-file-invoice me-1"></i>Factura</a>
                @if ($esAdmin)<a href="{{ route('alquileres.edit', $a) }}" class="btn btn-sm btn-light" title="Solo administrador"><i class="fa-solid fa-pen me-1"></i>Editar</a>@endif
                @elseif ($a->estado === 'programado')
                <form method="post" action="{{ route('alquileres.abonar', $a) }}" class="d-inline-flex gap-1 align-items-center flex-wrap">@csrf
                    <input name="monto" type="number" min="1" step="any" class="form-control form-control-sm" style="width:130px" value="{{ $a->saldo }}" required data-dinero title="Deje el valor completo si paga todo, o cambie el valor si deja un abono">
                    <select name="metodo_id" class="form-select form-select-sm" style="width:auto" required>
                        @foreach ($metodos as $m)<option value="{{ $m->id }}" @selected($m->es_efectivo)>{{ $m->nombre }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-dark" title="{{ $cajaAbierta ? '' : 'Primero se abre su caja' }}"><i class="fa-solid fa-cash-register me-1"></i>{{ $a->abonado > 0 ? 'Agregar abono' : 'Pagar / abonar' }}</button>
                </form>
                <a href="{{ route('alquileres.edit', $a) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-pen me-1"></i>Editar</a>
                <form method="post" action="{{ route('alquileres.estado', $a) }}" data-confirm="¿Cancelar el alquiler {{ $a->numero }}?{{ $a->abonado > 0 ? ' Tiene abonos por ' . $f($a->abonado) . '.' : '' }}" data-confirm-ok="Sí, cancelar" data-peligro>@csrf<input type="hidden" name="estado" value="cancelado">@if ($a->abonado > 0)<label class="small fw-semibold me-1"><input type="checkbox" name="devolver" value="1" checked> Devolver {{ $f($a->abonado) }}</label>@endif<button class="btn btn-sm btn-light"><i class="fa-solid fa-ban me-1"></i>Cancelar</button></form>
                @else
                <form method="post" action="{{ route('alquileres.estado', $a) }}">@csrf<input type="hidden" name="estado" value="programado"><button class="btn btn-sm btn-light"><i class="fa-solid fa-rotate-left me-1"></i>Volver a programado</button></form>
                @endif
                @unless ($a->venta)
                <form method="post" action="{{ route('alquileres.destroy', $a) }}" data-confirm="¿Eliminar el alquiler {{ $a->numero }}? No se puede deshacer." data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                @endunless
            </div>
            @if ($a->estado !== 'cancelado')
            <div class="aq-sec">
                <span class="lbl"><i class="fa-solid fa-box me-1"></i>Entrega</span>
                @if ($a->entrega === 'pendiente')
                <form method="post" action="{{ route('alquileres.entrega', $a) }}">@csrf<input type="hidden" name="accion" value="entregar"><button class="btn btn-sm btn-primary"><i class="fa-solid fa-key me-1"></i>Entregar (queda alquilado)</button></form>
                <small class="text-muted">Márquelo cuando el cliente se lleve lo alquilado.</small>
                @elseif ($a->entrega === 'alquilado')
                <small class="fw-semibold">Entregado {{ $a->entregado_en?->format('d/m/Y g:i a') }}.</small>
                <form method="post" action="{{ route('alquileres.entrega', $a) }}" class="d-inline-flex gap-1 align-items-center flex-wrap">@csrf<input type="hidden" name="accion" value="devolver">
                    <select name="devolucion_estado" class="form-select form-select-sm" style="width:auto" onchange="this.form.devolucion_nota.style.display=this.value==='con_novedad'?'':'none';this.form.devolucion_nota.required=this.value==='con_novedad'">
                        <option value="en_orden">Devolvió todo en orden</option><option value="con_novedad">Devolvió con novedad</option>
                    </select>
                    <input name="devolucion_nota" class="form-control form-control-sm" style="width:220px;display:none" maxlength="255" placeholder="¿Qué pasó? Daño, faltante…" data-libre>
                    <button class="btn btn-sm btn-success"><i class="fa-solid fa-rotate-left me-1"></i>Recibir devolución</button>
                </form>
                @else
                <small class="fw-semibold">Devuelto {{ $a->devuelto_en?->format('d/m/Y g:i a') }}@if ($a->devolucion_nota) · <span style="color:#a15c00">{{ $a->devolucion_nota }}</span>@endif</small>
                <form method="post" action="{{ route('alquileres.entrega', $a) }}" data-confirm="¿Deshacer la entrega/devolución de {{ $a->numero }}?" data-confirm-ok="Sí, deshacer">@csrf<input type="hidden" name="accion" value="deshacer"><button class="btn btn-sm btn-light"><i class="fa-solid fa-arrow-rotate-left"></i></button></form>
                @endif
            </div>
            @endif
            @if ($a->abonos->isNotEmpty())
            <details class="aq-abonos"><summary><i class="fa-solid fa-list me-1"></i>Pagos ({{ $a->abonos->count() }})</summary>
                @foreach ($a->abonos as $ab)
                <div class="d-flex justify-content-between border-bottom py-1"><span><b>{{ $ab->numero }}</b> · {{ $ab->created_at->format('d/m/Y g:i a') }} · {{ $ab->metodo?->nombre }}@if ($ab->nota) · {{ $ab->nota }}@endif</span><b style="color:{{ $ab->monto < 0 ? '#b0173f' : '#11702f' }}">{{ $f($ab->monto) }}</b></div>
                @endforeach
            </details>
            @endif
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
