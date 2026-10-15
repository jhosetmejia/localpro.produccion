@extends('layouts.app')

@section('title', 'Nómina')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>{{ $tienda->nomina_modo === 'electronica' ? 'Nómina electrónica DIAN' : 'Nómina sin envío a la DIAN' }}</small><h1>Nómina <span>del negocio</span></h1></div>
        <div class="lp-quick">
            <a href="{{ route('nomina.empleados') }}" class="btn btn-dark"><i class="fa-solid fa-users me-1"></i>Empleados ({{ $empleados }})</a>
            @if ($tienda->nomina_modo === 'electronica')
            <a href="{{ route('nomina.electronica') }}" class="btn btn-primary"><i class="fa-solid fa-building-columns me-1"></i>Nómina electrónica</a>
            @endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-grid" style="grid-template-columns:1fr 1.6fr">
        <div>
            <form method="post" action="{{ route('nomina.periodos.store') }}" class="lp-box mb-3">
                @csrf
                <h3>Liquidar un periodo</h3>
                @if (!$empleados)
                <p class="text-muted">Primero <a href="{{ route('nomina.empleados.create') }}">registre sus empleados</a>.</p>
                @endif
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small fw-bold">Desde</label><input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $sugerido['inicio']) }}" required></div>
                    <div class="col-6"><label class="form-label small fw-bold">Hasta</label><input type="date" name="fecha_fin" class="form-control" value="{{ old('fecha_fin', $sugerido['fin']) }}" required></div>
                    <div class="col-6"><label class="form-label small fw-bold">Fecha de pago</label><input type="date" name="fecha_pago" class="form-control" value="{{ old('fecha_pago', $sugerido['pago']) }}" required></div>
                    <div class="col-6"><label class="form-label small fw-bold">Nombre (opcional)</label><input name="nombre" class="form-control" maxlength="60" placeholder="Automático" value="{{ old('nombre') }}"></div>
                </div>
                <button class="btn btn-primary w-100 mt-3" @disabled(!$empleados)><i class="fa-solid fa-calculator me-1"></i>Crear y calcular</button>
                <small class="text-muted d-block mt-2">Valores {{ date('Y') }}: salario mínimo @pesos($anio['smmlv']) · auxilio de transporte @pesos($anio['aux']).</small>
            </form>

            <form method="post" action="{{ route('nomina.config') }}" class="lp-box">
                @csrf
                <h3>Configuración</h3>
                <label class="form-label small fw-bold">¿Cómo maneja la nómina?</label>
                <select name="nomina_modo" class="form-select mb-2">
                    <option value="interna" @selected($tienda->nomina_modo === 'interna')>Nómina sin envío a la DIAN</option>
                    <option value="electronica" @selected($tienda->nomina_modo === 'electronica')>Nómina electrónica DIAN</option>
                    <option value="no">Desactivar nómina</option>
                </select>
                <label class="form-label small fw-bold">Pago</label>
                <select name="nomina_periodicidad" class="form-select mb-2">
                    <option value="mensual" @selected($tienda->nomina_periodicidad === 'mensual')>Mensual</option>
                    <option value="quincenal" @selected($tienda->nomina_periodicidad === 'quincenal')>Quincenal</option>
                </select>
                <label class="form-label small fw-bold">Prefijo nómina electrónica</label>
                <input name="nomina_prefijo" class="form-control mb-2" maxlength="10" value="{{ $tienda->nomina_prefijo ?: 'NE' }}">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="nomina_exonerada" value="1" id="exo" @checked($tienda->nomina_exonerada)>
                    <label class="form-check-label" for="exo">Exonerado de salud, SENA e ICBF <small class="text-muted d-block">Art. 114-1 E.T.: empresas y personas naturales con 2 o más empleados, para quienes ganan menos de 10 SMMLV.</small></label>
                </div>
                <button class="btn btn-dark w-100">Guardar</button>
            </form>
        </div>

        <div class="lp-box">
            <h3>Periodos</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Periodo</th><th>Fechas</th><th>Neto a pagar</th><th>Costo empresa</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($periodos as $p)
                    @php [$txt, $col, $bg] = \App\Models\NominaPeriodo::ESTADOS[$p->estado]; @endphp
                    <tr>
                        <td class="fw-bold">{{ $p->nombre }}</td>
                        <td><small>{{ $p->fecha_inicio->format('d/m') }} – {{ $p->fecha_fin->format('d/m/Y') }}</small></td>
                        <td class="fw-bold">@pesos($p->total_neto)</td>
                        <td><small>@pesos($p->total_devengado + $p->total_aportes + $p->total_provisiones)</small></td>
                        <td><span class="badge" style="color:{{ $col }};background:{{ $bg }}">{{ $txt }}</span></td>
                        <td class="text-end"><a href="{{ route('nomina.periodo', $p) }}" class="btn btn-sm btn-dark">Abrir</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="lp-empty">Aún no ha liquidado ningún periodo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $periodos->links() }}
        </div>
    </div>
</div>
@endsection
