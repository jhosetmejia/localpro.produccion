@extends('layouts.app')

@section('title', 'Clientes 360')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Clientes <span>360</span></h1>
            <span class="text-muted fw-semibold">Personas que dejaron nombre y WhatsApp después de grabar su video en localproo.com/360.</span></div>
        <div class="lp-quick"><a href="{{ route('giro360') }}" target="_blank" rel="noopener" class="btn btn-light"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Abrir Giro 360</a>
            <a href="{{ route('admin.giro360.exportar', array_filter(['evento' => $evento])) }}" class="btn btn-dark"><i class="fa-solid fa-file-csv me-1"></i>Excel{{ $evento ? ' de este evento' : '' }}</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="lbl">Clientes</div><div class="val">{{ number_format($total, 0, ',', '.') }}</div><div class="sub">en total</div><div class="ic"><i class="fa-solid fa-users"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Hoy</div><div class="val">{{ $hoy }}</div><div class="sub">registrados hoy</div><div class="ic"><i class="fa-solid fa-video"></i></div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="lp-box">
                <h3><i class="fa-solid fa-champagne-glasses me-1"></i>Eventos</h3>
                <a href="{{ route('admin.giro360') }}" class="d-flex justify-content-between py-2 border-bottom text-decoration-none {{ $evento ? 'text-muted' : 'fw-bold' }}"><span>Todos</span><span>{{ $total }}</span></a>
                @forelse ($eventos as $e)
                <a href="{{ route('admin.giro360', ['evento' => $e->evento]) }}" class="d-flex justify-content-between py-2 border-bottom text-decoration-none {{ $evento === $e->evento ? 'fw-bold' : 'text-muted' }}"><span>{{ $e->evento }}</span><span>{{ $e->n }}</span></a>
                @empty
                <small class="text-muted d-block mt-2">El evento sale del texto que se pone abajo del video.</small>
                @endforelse
            </div>
        </div>
        <div class="col-lg-8">
            <div class="lp-box">
                <h3><i class="fa-brands fa-whatsapp me-1"></i>{{ $evento ?: 'Todos los clientes' }}</h3>
                @forelse ($clientes as $c)
                @php $wa = numero_whatsapp($c->whatsapp); @endphp
                <div class="d-flex align-items-center gap-2 py-2 border-bottom flex-wrap">
                    <div class="flex-grow-1"><b>{{ $c->nombre }}</b> <small class="text-muted">{{ $c->whatsapp }}{{ $c->evento ? ' · ' . $c->evento : '' }} · {{ \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y H:i') }}</small></div>
                    @if (!empty($c->latitud))<a class="btn btn-sm btn-outline-secondary me-1" target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $c->latitud }},{{ $c->longitud }}" title="Desde dónde se registró"><i class="fa-solid fa-location-dot me-1"></i>Mapa</a>@endif
                    @if ($wa)<a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="https://wa.me/{{ $wa }}"><i class="fa-brands fa-whatsapp me-1"></i>Escribir</a>@endif
                </div>
                @empty
                <p class="text-muted mb-0">Todavía no hay clientes. Aparecen aquí cuando alguien deja su nombre y WhatsApp después de grabar su video.</p>
                @endforelse
                <div class="mt-3">{{ $clientes->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
