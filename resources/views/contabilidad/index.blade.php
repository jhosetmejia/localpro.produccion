@extends('layouts.app')

@section('title', 'Contabilidad')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Retenciones e IVA</small><h1>Mis <span>clientes contables</span></h1>
            <p class="text-muted mb-0">Cruce las facturas de la DIAN con la contabilidad y deje listo el borrador del 350 y del IVA.</p></div>
        <div class="lp-quick">
            <a href="{{ route('contabilidad.guia') }}" class="btn btn-light"><i class="fa-solid fa-book me-1"></i>Guía de uso</a>
            <a href="{{ route('contabilidad.calendario') }}" class="btn btn-light"><i class="fa-solid fa-calendar-days me-1"></i>Calendario</a>
            <a href="{{ route('contabilidad.tarifas') }}" class="btn btn-dark"><i class="fa-solid fa-table me-1"></i>Tabla de retenciones</a>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#nuevo"><i class="fa-solid fa-plus me-1"></i>Nuevo cliente</button>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @unless ($clientes->contains('nit', '900123456'))
    <div class="lp-box mb-3 d-flex align-items-center gap-3 flex-wrap" style="background:#f4fcf6;border-color:#bfeccb">
        <div style="font-size:1.8rem">🧪</div>
        <div class="flex-grow-1" style="min-width:220px"><b>¿Primera vez? Pruébelo con datos de ejemplo</b>
            <div class="small text-muted">Creamos un cliente de ejemplo con el listado de la DIAN y el auxiliar contable ya cargados, para que vea el cruce, las alertas y el borrador del 350 en un minuto. Lo puede borrar después.</div></div>
        <form method="post" action="{{ route('contabilidad.ejemplo') }}">@csrf<button class="btn btn-success"><i class="fa-solid fa-flask me-1"></i>Probar con datos de ejemplo</button></form>
    </div>
    @endunless

    <div class="collapse {{ $clientes->isEmpty() || $errors->any() ? 'show' : '' }}" id="nuevo">
        <form method="post" action="{{ route('contabilidad.clientes.store') }}" class="lp-box mb-3">
            @csrf
            <h3>Nuevo cliente</h3>
            @include('contabilidad._cliente-campos', ['c' => new \App\Models\ContableCliente(['tipo_persona' => 'juridica', 'agente_retenedor' => true, 'periodicidad_iva' => 'bimestral'])])
            <button class="btn btn-primary mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Crear cliente</button>
        </form>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="lp-box">
                <h3>Clientes ({{ $clientes->count() }})</h3>
                @forelse ($clientes as $c)
                <a href="{{ route('contabilidad.cliente', $c) }}" class="d-flex align-items-center gap-3 p-2 rounded-3 text-reset text-decoration-none border mb-2 {{ $c->activo ? '' : 'opacity-50' }}">
                    <div class="rounded-3 d-grid" style="width:42px;height:42px;place-items:center;background:#f4fcf6;color:#11702f"><i class="fa-solid {{ $c->tipo_persona === 'juridica' ? 'fa-building' : 'fa-user' }}"></i></div>
                    <div class="flex-grow-1">
                        <b>{{ $c->nombre }}</b>
                        <div class="small text-muted">NIT {{ $c->nit_completo }} · {{ $c->tipo_persona === 'juridica' ? 'Persona jurídica' : 'Persona natural' }} · {{ \App\Models\ContableCliente::PERIODICIDAD[$c->periodicidad_iva] ?? '' }}@unless ($c->agente_retenedor) · <span class="text-warning fw-bold">No es agente retenedor</span>@endunless</div>
                    </div>
                    <span class="badge bg-light text-dark">{{ $c->periodos_count }} {{ $c->periodos_count === 1 ? 'mes' : 'meses' }}</span>
                </a>
                @empty
                <p class="text-muted">Aún no tiene clientes. Cree el primero con el botón <b>Nuevo cliente</b>.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4">
            <div class="lp-box mb-3">
                <div class="d-flex justify-content-between align-items-center"><h3 class="mb-2">Próximos vencimientos</h3><a href="{{ route('contabilidad.calendario') }}" class="small">Ver todo</a></div>
                @forelse ($vencimientos as $v)
                @php [$txt, $color] = \App\Support\CalendarioTributario::falta($v['dias']); @endphp
                <div class="small mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between gap-2"><b>{{ $v['fecha']->locale('es')->isoFormat('D MMM') }} · {{ $v['nombre'] }}</b><span class="text-{{ $color }} fw-bold text-nowrap">{{ $txt }}</span></div>
                    <div class="text-muted">{{ \Illuminate\Support\Str::limit($v['cliente']->nombre, 30) }} · {{ $v['periodo'] }}</div>
                </div>
                @empty
                <p class="small text-muted mb-0">Sin vencimientos pendientes en los próximos 30 días.</p>
                @endforelse
            </div>
            <div class="lp-box">
                <h3>Trabajado hace poco</h3>
                @forelse ($ultimos as $p)
                <a href="{{ route('contabilidad.periodo', $p) }}" class="d-block small mb-2 text-decoration-none"><b>{{ $p->cliente->nombre }}</b> · {{ $p->nombre }} <span class="badge bg-light text-dark">{{ \App\Models\ContablePeriodo::ESTADOS[$p->estado] ?? $p->estado }}</span></a>
                @empty
                <p class="small text-muted mb-0">Aquí aparecen los meses que va revisando.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
