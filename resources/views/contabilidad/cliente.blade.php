@extends('layouts.app')

@section('title', $cliente->nombre)

@section('content')
@php $C = \App\Support\Retenciones::CONCEPTOS; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Contabilidad · NIT {{ $cliente->nit_completo }}</small><h1>{{ $cliente->nombre }}</h1>
            <p class="text-muted mb-0">{{ $cliente->tipo_persona === 'juridica' ? 'Persona jurídica' : 'Persona natural' }} · {{ \App\Models\ContableCliente::PERIODICIDAD[$cliente->periodicidad_iva] ?? '' }} · {{ $cliente->agente_retenedor ? 'Agente de retención' : 'No es agente de retención' }}</p></div>
        <div class="lp-quick"><a href="{{ route('contabilidad.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Clientes</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @unless ($cliente->agente_retenedor)
    <div class="alert alert-warning">Este cliente no es agente de retención: no presenta el Formulario 350. Igual puede cruzar las facturas para revisar el IVA y la contabilidad.</div>
    @endunless

    <div class="row g-3">
        <div class="col-lg-5">
            <form method="post" action="{{ route('contabilidad.periodos.store', $cliente) }}" class="lp-box mb-3">
                @csrf
                <h3><i class="fa-regular fa-calendar me-1"></i>Abrir un mes</h3>
                @php $ant = now()->subMonth(); @endphp
                <div class="d-flex gap-2">
                    <select class="form-select" name="mes">@for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}" @selected($m === $ant->month)>{{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->locale('es')->translatedFormat('F')) }}</option>@endfor</select>
                    <input type="number" class="form-control" name="anio" value="{{ $ant->year }}" min="2020" max="2100" style="max-width:110px">
                    <button class="btn btn-primary">Abrir</button>
                </div>
            </form>
            <div class="lp-box mb-3">
                <div class="d-flex justify-content-between align-items-center"><h3 class="mb-2"><i class="fa-solid fa-calendar-days me-1"></i>Vencimientos</h3><a href="{{ route('contabilidad.calendario') }}" class="small">Calendario</a></div>
                @forelse (array_slice($vencimientos, 0, 4) as $v)
                @php [$txt, $color] = \App\Support\CalendarioTributario::falta($v['dias']); @endphp
                <div class="d-flex justify-content-between gap-2 small border-bottom py-1">
                    <span><b>{{ $v['fecha']->locale('es')->isoFormat('D MMM') }}</b> · {{ $v['nombre'] }} <span class="text-muted">· {{ $v['periodo'] }}</span></span>
                    @if ($v['presentado'])<span class="text-success fw-bold text-nowrap"><i class="fa-solid fa-check"></i> Presentado</span>@else<span class="text-{{ $color }} fw-bold text-nowrap">{{ $txt }}</span>@endif
                </div>
                @empty
                <p class="small text-muted mb-0">Sin vencimientos próximos. Revise que el NIT, si es agente de retención y la periodicidad del IVA estén bien.</p>
                @endforelse
                <small class="text-muted">Según el último dígito del NIT: {{ \App\Support\CalendarioTributario::digito($cliente->nit) ?? '—' }}.</small>
            </div>
            <div class="lp-box mb-3">
                <h3>Meses</h3>
                @forelse ($periodos as $p)
                <a href="{{ route('contabilidad.periodo', $p) }}" class="d-flex justify-content-between align-items-center border rounded-3 p-2 mb-2 text-reset text-decoration-none">
                    <span><b>{{ $p->nombre }}</b> <small class="text-muted">· {{ $p->documentos_count }} documentos</small></span>
                    <span class="badge {{ $p->estado === 'presentado' ? 'bg-success' : ($p->estado === 'revisado' ? 'bg-info' : 'bg-light text-dark') }}">{{ \App\Models\ContablePeriodo::ESTADOS[$p->estado] ?? $p->estado }}</span>
                </a>
                @empty
                <p class="text-muted small mb-0">Abra el primer mes que va a revisar.</p>
                @endforelse
            </div>
            <details class="lp-box">
                <summary class="fw-bold" style="cursor:pointer">Datos del cliente</summary>
                <form method="post" action="{{ route('contabilidad.clientes.update', $cliente) }}" class="mt-3">
                    @csrf @method('put')
                    @include('contabilidad._cliente-campos', ['c' => $cliente])
                    <button class="btn btn-dark mt-3">Guardar</button>
                </form>
                <form method="post" action="{{ route('contabilidad.clientes.destroy', $cliente) }}" data-confirm="¿Eliminar a {{ $cliente->nombre }} con todos sus meses?" data-confirm-ok="Sí, eliminar" data-peligro class="mt-2">@csrf @method('delete')<button class="btn btn-link text-danger p-0">Eliminar cliente</button></form>
            </details>
        </div>
        <div class="col-lg-7">
            <div class="lp-box">
                <h3>Proveedores y clientes del cliente ({{ $terceros->count() }})</h3>
                <p class="small text-muted">Se crean solos al cargar facturas. Aquí indica si son persona natural o jurídica, si declaran renta, si son autorretenedores o grandes contribuyentes y su concepto habitual: se recuerda para todos los meses.</p>
                <div class="table-responsive">
                <table class="table table-sm align-middle small">
                    <thead><tr><th>Tercero</th><th>Tipo</th><th>Concepto habitual</th><th>Declarante</th><th>Autorret.</th><th title="Gran contribuyente: no se le practica retención de IVA si el cliente también lo es">Gran contrib.</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($terceros as $t)
                    <tr>
                        <td><b>{{ $t->nombre ?: 'Sin nombre' }}</b><div class="text-muted">{{ $t->nit }}</div></td>
                        <td><select form="t-{{ $t->id }}" class="form-select form-select-sm" name="tipo_persona"><option value="juridica" @selected($t->tipo_persona === 'juridica')>Jurídica</option><option value="natural" @selected($t->tipo_persona !== 'juridica')>Natural</option></select></td>
                        <td><select form="t-{{ $t->id }}" class="form-select form-select-sm" name="concepto"><option value="">—</option>@foreach ($C as $k => [$n])<option value="{{ $k }}" @selected($t->concepto === $k)>{{ $n }}</option>@endforeach</select></td>
                        <td class="text-center"><input form="t-{{ $t->id }}" type="checkbox" class="form-check-input" name="declarante" value="1" @checked($t->declarante)></td>
                        <td class="text-center"><input form="t-{{ $t->id }}" type="checkbox" class="form-check-input" name="autorretenedor" value="1" @checked($t->autorretenedor)></td>
                        <td class="text-center"><input form="t-{{ $t->id }}" type="checkbox" class="form-check-input" name="gran_contribuyente" value="1" @checked($t->gran_contribuyente)></td>
                        <td class="text-nowrap"><form id="t-{{ $t->id }}" method="post" action="{{ route('contabilidad.tercero', $t) }}" class="d-inline">@csrf @method('put')<button class="btn btn-sm btn-dark" title="Guardar"><i class="fa-solid fa-check"></i></button></form>
                            <a class="btn btn-sm btn-light" title="Certificado de retención" target="_blank" href="{{ route('contabilidad.certificado', [$cliente, 'nit' => $t->nit, 'anio' => now()->year]) }}"><i class="fa-solid fa-file-signature"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted">Aparecen al cargar las facturas del primer mes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
