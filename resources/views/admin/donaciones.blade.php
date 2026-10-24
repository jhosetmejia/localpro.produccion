@extends('layouts.app')

@section('title', 'Donaciones')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Donaciones <span>de Navidad</span></h1>
            <span class="text-muted fw-semibold">Ayudas para los niños de los barrios más necesitados de Pasto. Los negocios que donan se invitan a la entrega y van en los agradecimientos.</span></div>
        <div class="lp-quick"><a href="{{ route('donaciones') }}" target="_blank" rel="noopener" class="btn btn-light"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver la página</a>
            <a href="{{ route('admin.donaciones.exportar') }}" class="btn btn-dark"><i class="fa-solid fa-file-csv me-1"></i>Excel de donantes</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="lbl">Recaudado</div><div class="val">{{ dinero($total) }}</div><div class="sub">pagos aprobados</div><div class="ic"><i class="fa-solid fa-gift"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Negocios que donaron</div><div class="val">{{ $donantes->pluck('tienda_id')->unique()->count() }}</div><div class="sub">{{ $donantes->count() }} donaciones</div><div class="ic"><i class="fa-solid fa-store"></i></div></div>
        <div class="lp-kpi"><div class="lbl">En proceso</div><div class="val">{{ $pendientes }}</div><div class="sub">pagos sin confirmar</div><div class="ic"><i class="fa-solid fa-hourglass-half"></i></div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <form method="post" action="{{ route('admin.donaciones.guardar') }}" class="lp-box">@csrf
                <h3><i class="fa-solid fa-sliders me-1"></i>Campaña</h3>
                <input type="hidden" name="activa" value="0"><input type="hidden" name="marcada" value="0">
                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="activa" value="1" id="dact" @checked($activa)><label class="form-check-label fw-bold" for="dact">Mostrar la donación al pagar el plan</label></div>
                <label class="form-label fw-bold">Valor de la donación</label>
                <input class="form-control mb-2" type="number" name="monto" min="1000" step="500" value="{{ (int) $monto }}">
                <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="marcada" value="1" id="dmar" @checked($marcada)><label class="form-check-label" for="dmar">Casilla marcada por defecto</label></div>
                <small class="text-muted d-block mb-3">Si está marcada, el negocio debe desmarcarla para no donar. La Ley 1480 (Estatuto del Consumidor) exige que el consumidor acepte de forma expresa los cobros adicionales; con la casilla desmarcada no hay riesgo de reclamos.</small>
                <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="lp-box">
                <h3><i class="fa-solid fa-heart me-1"></i>Donantes (para invitar a la entrega y para los agradecimientos)</h3>
                @forelse ($donantes as $d)
                @php $wa = $d->tienda?->telefono ? numero_whatsapp($d->tienda->telefono) : null; @endphp
                <div class="d-flex align-items-center gap-2 py-2 border-bottom flex-wrap">
                    <div class="flex-grow-1"><b>{{ $d->tienda?->nombre ?? '—' }}</b> <small class="text-muted">{{ $d->tienda?->codigo }} · {{ $d->tienda?->ciudad }}</small>
                        <div class="small text-muted">{{ dinero($d->donacion) }} · {{ $d->updated_at?->format('d/m/Y') }}</div></div>
                    @if ($wa)<a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('¡Hola! 💚 Gracias por donar a la Navidad LocalProo para los niños de Pasto. Lo invitamos a acompañarnos en la entrega de ayudas en diciembre. Le confirmamos fecha y lugar por aquí.') }}"><i class="fa-brands fa-whatsapp me-1"></i>Invitar</a>@endif
                </div>
                @empty
                <div class="text-muted">Todavía no hay donaciones aprobadas.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
