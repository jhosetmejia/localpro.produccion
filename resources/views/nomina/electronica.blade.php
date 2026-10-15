@extends('layouts.app')

@section('title', 'Nómina electrónica')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('nomina.index') }}">Nómina</a> · {{ $proveedor }}</small><h1>Nómina <span>electrónica</span></h1></div>
        <form method="get" class="lp-quick d-flex gap-2"><input type="month" name="mes" class="form-control" value="{{ $mes }}" onchange="this.form.submit()"></form>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-calendar-check"></i></div><div class="lbl">Plazo para transmitir {{ $mes }}</div><div class="val">{{ $limite->format('d/m/Y') }}</div><div class="sub">10 primeros días hábiles (verifique festivos)</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Empleados con nómina en el mes</div><div class="val">{{ $liqs->count() }}</div><div class="sub">{{ $pendientes->count() }} por enviar</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-file-circle-check"></i></div><div class="lbl">Documentos</div><div class="val">{{ $docs->count() }}</div><div class="sub">{{ $docs->whereIn('estado', ['aceptado', 'simulado'])->count() }} validados</div></div>
    </div>

    <div class="lp-box mb-3">
        <h3>Enviar la nómina de {{ $mes }}</h3>
        @if ($abiertos)
        <div class="alert alert-warning small">Hay {{ $abiertos }} periodo(s) de este mes sin liquidar. Solo se reportan los periodos liquidados o pagados.</div>
        @endif
        @if ($pendientes->isEmpty())
        <p class="text-muted mb-0">No hay empleados pendientes por enviar en este mes.</p>
        @else
        <ul class="lp-list mb-3">
            @foreach ($pendientes as $empId => $g)
            <li><div class="av"><i class="fa-solid fa-user"></i></div><div class="t"><b>{{ $g->first()->empleado->nombre }}</b><small>{{ $g->count() }} periodo(s)</small></div><span class="m">@pesos($g->sum('neto'))</span></li>
            @endforeach
        </ul>
        <form method="post" action="{{ route('nomina.electronica.generar') }}" data-confirm="¿Enviar la nómina electrónica de {{ $mes }}?" data-confirm-texto="Se genera un documento por cada empleado y se envía al proveedor tecnológico." data-confirm-ok="Sí, enviar">
            @csrf <input type="hidden" name="mes" value="{{ $mes }}">
            <button class="btn btn-primary w-100"><i class="fa-solid fa-paper-plane me-1"></i>Generar y enviar {{ $pendientes->count() }} documento(s)</button>
        </form>
        @endif
        <small class="text-muted d-block mt-2">Los empleados marcados como "No reportar a la DIAN" no aparecen aquí.</small>
    </div>

    <div class="lp-box">
        <h3>Documentos de {{ $mes }}</h3>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Número</th><th>Empleado</th><th>Tipo</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($docs as $d)
                @php [$txt, $col, $bg] = $d->estado_info; @endphp
                <tr>
                    <td class="fw-bold">{{ $d->numero }}</td>
                    <td>{{ $d->empleado?->nombre }}</td>
                    <td><small>{{ $d->nombre_tipo }}</small></td>
                    <td>@pesos($d->total)</td>
                    <td><span class="badge" style="color:{{ $col }};background:{{ $bg }}">{{ $txt }}</span>@if ($d->ultimo_error)<small class="d-block" style="color:#b42318">{{ \Illuminate\Support\Str::limit($d->ultimo_error, 120) }}</small>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('nomina.electronica.ver', $d) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-eye"></i></a>
                        @if (in_array($d->estado, ['pendiente', 'error', 'rechazado'], true))
                        <form method="post" action="{{ route('nomina.electronica.enviar', $d) }}" class="d-inline">@csrf<button class="btn btn-sm btn-dark" title="Reintentar"><i class="fa-solid fa-rotate"></i></button></form>
                        @elseif ($d->tipo_xml === '102' || $d->tipo_nota === 1)
                        <form method="post" action="{{ route('nomina.electronica.ajuste', $d) }}" class="d-inline" data-confirm="¿Reemplazar {{ $d->numero }}?" data-confirm-texto="Se envía una nota de ajuste con los valores actuales de la nómina." data-confirm-ok="Sí, reemplazar">@csrf<input type="hidden" name="tipo_nota" value="1"><button class="btn btn-sm btn-dark" title="Nota de ajuste: reemplazar"><i class="fa-solid fa-pen-to-square"></i></button></form>
                        <form method="post" action="{{ route('nomina.electronica.ajuste', $d) }}" class="d-inline" data-confirm="¿Eliminar {{ $d->numero }} ante la DIAN?" data-confirm-texto="Se envía una nota de ajuste de eliminación." data-confirm-ok="Sí, eliminar" data-peligro>@csrf<input type="hidden" name="tipo_nota" value="2"><button class="btn btn-sm btn-danger" title="Nota de ajuste: eliminar"><i class="fa-solid fa-ban"></i></button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="lp-empty">Aún no hay documentos en este mes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
