{{-- Detalle de cada movimiento de dinero del turno: de dónde sale cada valor de la caja --}}
@php $movs = $caja->movimientos(); @endphp
@if ($movs->isNotEmpty())
<details class="lp-box mb-3" {{ $movs->count() <= 8 ? 'open' : '' }}>
    <summary style="cursor:pointer;list-style:none" class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h3 class="mb-0"><span><i class="fa-solid fa-list-ul me-1"></i> Movimientos del turno</span></h3>
        <span class="small text-muted fw-bold">{{ $movs->count() }} movimiento(s) · tocar para ver / ocultar</span>
    </summary>
    <div class="table-responsive mt-3">
        <table class="table table-sm align-middle mb-0" style="font-size:.88rem">
            <thead><tr><th>Hora</th><th>Qué fue</th><th>Detalle</th><th>Cómo</th><th class="text-end">Valor</th></tr></thead>
            <tbody>
            @foreach ($movs as $m)
            <tr>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($m->hora)->format('h:i a') }}</td>
                <td class="text-nowrap"><b>{{ $m->tipo }}</b><br><small class="text-muted">{{ $m->numero }}</small></td>
                <td>{{ $m->detalle }}@if (!empty($m->extra))<br><small class="text-muted">{{ $m->extra }}</small>@endif</td>
                <td class="text-nowrap">{{ $m->nombre }}@if ($m->es_efectivo) <span class="badge rounded-pill" style="background:#dcfce7;color:#14753a">Efectivo</span>@endif</td>
                <td class="text-end text-nowrap fw-bold" style="color:{{ $m->monto < 0 ? '#b0206a' : 'inherit' }}">{{ $m->monto < 0 ? '-' : '' }}{{ dinero(abs($m->monto)) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted d-block mt-2">Solo lo marcado como <b>Efectivo</b> cuenta para el dinero que debe haber en el cajón. Nequi, transferencias y tarjetas nunca cuentan como efectivo.</small>
</details>
@endif
