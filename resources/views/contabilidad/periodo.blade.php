@extends('layouts.app')

@section('title', $cliente->nombre . ' · ' . $p->nombre)

@push('css')
<style>
.ct-paso{border:1.5px solid #e3efe5;border-radius:16px;padding:1rem;background:#fff;height:100%}
.ct-paso.ok{border-color:#9fdcae;background:#f7fdf8}
.ct-paso .num{width:30px;height:30px;border-radius:50%;display:inline-grid;place-items:center;background:#0f1411;color:#a6f34a;font-weight:800;margin-right:.4rem}
.ct-kpi{border-radius:14px;padding:.8rem 1rem;background:#f4fcf6}
.ct-kpi b{font-size:1.5rem;display:block}
.ct-kpi.malo{background:#fff1f1}.ct-kpi.malo b{color:#c0392b}
.ct-tabla{font-size:.82rem}
.ct-tabla td,.ct-tabla th{white-space:nowrap;vertical-align:middle}
.ct-tabla .num{text-align:right;font-variant-numeric:tabular-nums}
.ct-obs{display:inline-block;font-size:.7rem;font-weight:700;border-radius:99px;padding:.15rem .5rem;margin:.1rem 0;white-space:normal}
.ct-obs.solo_dian{background:#fde2e2;color:#a61b1b}.ct-obs.solo_contable{background:#fff1d6;color:#8a5a00}
.ct-obs.iva,.ct-obs.base{background:#e7efff;color:#24489b}.ct-obs.falta_retencion,.ct-obs.falta_rete_iva{background:#fde2e2;color:#a61b1b}.ct-obs.sobra_retencion,.ct-obs.sobra_rete_iva{background:#fff1d6;color:#8a5a00}
.ct-350 td{font-variant-numeric:tabular-nums}
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Retenciones::CONCEPTOS;
    $n = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
    $mes = fn ($m) => \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->locale('es')->translatedFormat('F'));
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>{{ $cliente->nombre }} · NIT {{ $cliente->nit_completo }}</small><h1>{{ $p->nombre }}</h1>
            <p class="mb-0"><span class="badge {{ $p->estado === 'presentado' ? 'bg-success' : ($p->estado === 'revisado' ? 'bg-info' : 'bg-secondary') }}">{{ \App\Models\ContablePeriodo::ESTADOS[$p->estado] }}</span> <small class="text-muted ms-1">UVT {{ $p->anio }}: {{ $n($uvt) }}</small></p></div>
        <div class="lp-quick">
            <a href="{{ route('contabilidad.cliente', $cliente) }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>{{ \Illuminate\Support\Str::limit($cliente->nombre, 20) }}</a>
            @if ($todas)<a href="{{ route('contabilidad.exportar', $p) }}" class="btn btn-success"><i class="fa-solid fa-file-excel me-1"></i>Descargar Excel</a>@endif
            <a href="{{ route('contabilidad.guia') }}" class="btn btn-light" target="_blank"><i class="fa-solid fa-circle-question"></i></a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    {{-- Pasos de carga --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="ct-paso {{ $conteo['dian'] ? 'ok' : '' }}">
                <div class="fw-bold mb-1"><span class="num">1</span>Facturas de la DIAN</div>
                @if ($conteo['dian'])
                    <div class="small mb-2"><i class="fa-solid fa-circle-check text-success me-1"></i>{{ $conteo['dian'] }} documentos cargados.</div>
                @else
                    <div class="small text-muted mb-2">En el portal de la DIAN (Facturación electrónica → Documentos) descargue el listado del mes, emitidos y recibidos, en Excel.</div>
                @endif
                <form method="post" action="{{ route('contabilidad.dian', $p) }}" enctype="multipart/form-data" class="d-flex gap-2">
                    @csrf
                    <input type="file" class="form-control form-control-sm" name="archivo" accept=".xlsx,.csv" required data-sin-optimizar>
                    <button class="btn btn-sm btn-dark text-nowrap">{{ $conteo['dian'] ? 'Reemplazar' : 'Subir' }}</button>
                </form>
                @if ($conteo['dian'])<form method="post" action="{{ route('contabilidad.carga.borrar', [$p, 'dian']) }}" class="mt-1">@csrf @method('delete')<button class="btn btn-link btn-sm text-danger p-0">Quitar</button></form>@endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="ct-paso {{ $conteo['contable'] ? 'ok' : '' }}">
                <div class="fw-bold mb-1"><span class="num">2</span>Base de su contabilidad</div>
                @if ($conteo['contable'])
                    <div class="small mb-2"><i class="fa-solid fa-circle-check text-success me-1"></i>{{ $conteo['contable'] }} facturas cargadas. <a href="{{ route('contabilidad.mapeo', $p) }}">Cambiar columnas</a></div>
                @else
                    <div class="small text-muted mb-2">Exporte de Contapyme (u otro programa) el auxiliar de compras y retenciones del mes en Excel o CSV. Luego le pregunta qué columna es cada dato.</div>
                @endif
                <form method="post" action="{{ route('contabilidad.contable', $p) }}" enctype="multipart/form-data" class="d-flex gap-2">
                    @csrf
                    <input type="file" class="form-control form-control-sm" name="archivo" accept=".xlsx,.csv,.txt" required data-sin-optimizar>
                    <button class="btn btn-sm btn-dark text-nowrap">{{ $conteo['contable'] ? 'Reemplazar' : 'Subir' }}</button>
                </form>
                @if ($conteo['contable'])<form method="post" action="{{ route('contabilidad.carga.borrar', [$p, 'contable']) }}" class="mt-1">@csrf @method('delete')<button class="btn btn-link btn-sm text-danger p-0">Quitar</button></form>@endif
            </div>
        </div>
    </div>

    @if ($todas)
    {{-- Resumen --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3"><div class="ct-kpi"><b>{{ count($todas) }}</b><small>Documentos del mes</small></div></div>
        <div class="col-6 col-lg-3"><div class="ct-kpi {{ $conteo['sin_contabilizar'] ? 'malo' : '' }}"><b>{{ $conteo['sin_contabilizar'] }}</b><small>En la DIAN sin contabilizar</small></div></div>
        <div class="col-6 col-lg-3"><div class="ct-kpi {{ $conteo['retencion'] ? 'malo' : '' }}"><b>{{ $conteo['retencion'] }}</b><small>Retenciones a revisar</small></div></div>
        <div class="col-6 col-lg-3"><div class="ct-kpi"><b>{{ $n($b350['total']) }}</b><small>Total retenciones (borrador)</small></div></div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-cruce" type="button">Cruce de facturas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-350" type="button">Borrador 350</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-iva" type="button">IVA</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-estado" type="button">Estado y notas</button></li>
    </ul>
    <div class="tab-content">
        {{-- ===== Cruce ===== --}}
        <div class="tab-pane fade show active" id="t-cruce">
            <div class="lp-box">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach (['todas' => 'Todas', 'compras' => 'Compras y gastos', 'ventas' => 'Ventas', 'revisar' => 'Para revisar (' . $conteo['revisar'] . ')', 'sin_contabilizar' => 'Sin contabilizar', 'retencion' => 'Retención a revisar'] as $k => $txt)
                    <a href="{{ route('contabilidad.periodo', [$p, 'ver' => $k]) }}" class="btn btn-sm {{ $ver === $k ? 'btn-dark' : 'btn-light' }}">{{ $txt }}</a>
                    @endforeach
                </div>
                <form method="post" action="{{ route('contabilidad.concepto', $p) }}" id="f-concepto">
                    @csrf
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2 small">
                        <span>Con las marcadas:</span>
                        <select name="concepto" class="form-select form-select-sm" style="width:auto">@foreach ($C as $k => [$nm])<option value="{{ $k }}">{{ $nm }}</option>@endforeach</select>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="recordar" value="1" id="rec" checked><label class="form-check-label" for="rec">Recordar para ese proveedor</label></div>
                        <button class="btn btn-sm btn-dark">Cambiar concepto</button>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-sm table-hover ct-tabla">
                        <thead><tr>
                            <th><input type="checkbox" class="form-check-input" id="todas"></th><th>Documento</th><th>Tercero</th><th>Concepto</th>
                            <th class="num">Base</th><th class="num">IVA</th><th class="num">Retención practicada</th><th class="num">Según tabla</th><th class="num">Rete IVA</th><th>Revisar</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($filas as $f)
                        <tr class="{{ $f['direccion'] === 'emitida' ? 'table-light' : '' }}">
                            <td><input type="checkbox" class="form-check-input ct-chk" name="ids[]" value="{{ $f['id'] }}"></td>
                            <td><b>{{ $f['documento'] }}</b><div class="text-muted">{{ $f['direccion'] === 'emitida' ? 'Venta' : 'Compra' }} · {{ \Illuminate\Support\Str::limit($f['tipo'], 22) }} · {{ $f['fecha']?->format('d/m') }}</div></td>
                            <td style="white-space:normal;min-width:180px"><b>{{ \Illuminate\Support\Str::limit($f['nombre'], 34) }}</b><div class="text-muted">{{ $f['nit'] }} · {{ $f['tipo_persona'] === 'juridica' ? 'Jurídica' : 'Natural' }}{{ $f['declarante'] === false ? ' no declarante' : '' }}</div></td>
                            <td>{{ $f['direccion'] === 'recibida' ? ($C[$f['concepto']][0] ?? '—') : '' }}</td>
                            <td class="num">{{ $n($f['base']) }}</td>
                            <td class="num">{{ $n($f['iva']) }}@if ($f['iva_dian'] !== null && $f['iva_cont'] !== null && abs($f['iva_dian'] - $f['iva_cont']) > 1000)<div class="text-danger">DIAN {{ $n($f['iva_dian']) }}</div>@endif</td>
                            <td class="num">{{ $f['direccion'] === 'recibida' ? $n($f['practicada']) : '' }}</td>
                            <td class="num">@if ($f['direccion'] === 'recibida'){{ $n($f['esperada']) }}<div class="text-muted">{{ $f['tarifa'] }}</div>@endif</td>
                            <td class="num">{{ $f['rete_iva'] ? $n($f['rete_iva']) : '' }}@if ($f['rete_iva_esperada'] && abs($f['rete_iva_esperada'] - $f['rete_iva']) > 1000)<div class="text-muted" title="15 % del IVA según la tabla">Debe: {{ $n($f['rete_iva_esperada']) }}</div>@endif</td>
                            <td style="white-space:normal;min-width:170px">@foreach ($f['obs'] as [$k, $txt])<span class="ct-obs {{ $k }}">{{ $txt }}</span> @endforeach</td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-muted text-center py-4">No hay facturas con este filtro.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </form>
                <small class="text-muted">"Según tabla" usa la tabla de retenciones vigente en la fecha de cada factura, el tipo de proveedor (jurídica/natural, declarante) y la UVT del año. Cambie el tipo de un proveedor en la ficha del cliente.</small>
            </div>
        </div>

        {{-- ===== Borrador 350 ===== --}}
        <div class="tab-pane fade" id="t-350">
            <div class="lp-box">
                <h3>Borrador de la declaración de retenciones (Formulario 350)</h3>
                @unless ($cliente->agente_retenedor)<div class="alert alert-warning small">El cliente está marcado como no agente de retención.</div>@endunless
                <div class="table-responsive">
                <table class="table table-sm ct-350">
                    <thead><tr><th>Concepto</th><th class="text-end">Base personas jurídicas</th><th class="text-end">Retención jurídicas</th><th class="text-end">Base personas naturales</th><th class="text-end">Retención naturales</th></tr></thead>
                    <tbody>
                    @forelse ($b350['conceptos'] as $x)
                    <tr><td>{{ $x['nombre'] }}</td><td class="text-end">{{ $n($x['base_j']) }}</td><td class="text-end fw-bold">{{ $n($x['ret_j']) }}</td><td class="text-end">{{ $n($x['base_n']) }}</td><td class="text-end fw-bold">{{ $n($x['ret_n']) }}</td></tr>
                    @empty
                    <tr><td colspan="5" class="text-muted">Aún no hay retenciones practicadas en la contabilidad de este mes.</td></tr>
                    @endforelse
                    <tr class="table-light"><td><b>Total retenciones a título de renta</b></td><td colspan="4" class="text-end fw-bold">{{ $n($b350['rete_renta']) }}</td></tr>
                    <tr><td>Autorretenciones (base {{ $n($b350['autorretencion_base']) }})</td><td colspan="4" class="text-end">{{ $n($b350['autorretencion']) }}</td></tr>
                    <tr><td>Retenciones de IVA practicadas</td><td colspan="4" class="text-end">{{ $n($b350['rete_iva']) }}</td></tr>
                    <tr class="table-success"><td><b>Total retenciones a pagar</b></td><td colspan="4" class="text-end fw-bold fs-5">{{ $n($b350['total']) }}</td></tr>
                    </tbody>
                </table>
                </div>
                <div class="small text-muted">
                    Retención de ICA del mes: <b>{{ $n($b350['rete_ica']) }}</b> (va en la declaración del municipio, no en el 350).<br>
                    Pase estos valores a las casillas del Formulario 350 vigente en el portal MUISCA de la DIAN y presente con la firma electrónica del declarante. Las autorretenciones se escriben en "Estado y notas".
                </div>
            </div>
        </div>

        {{-- ===== IVA ===== --}}
        <div class="tab-pane fade" id="t-iva">
            <div class="lp-box">
                <h3>IVA · {{ \App\Models\ContableCliente::PERIODICIDAD[$cliente->periodicidad_iva] ?? '' }}</h3>
                @if ($cliente->periodicidad_iva === 'no_responsable')
                    <p class="text-muted">El cliente está marcado como no responsable de IVA.</p>
                @endif
                <table class="table table-sm">
                    <thead><tr><th>Mes</th><th class="text-end">Base ventas</th><th class="text-end">IVA generado</th><th class="text-end">Base compras</th><th class="text-end">IVA descontable</th><th class="text-end">Saldo</th></tr></thead>
                    <tbody>
                    @php $tot = ['base_ventas' => 0, 'generado' => 0, 'base_compras' => 0, 'descontable' => 0, 'saldo' => 0]; @endphp
                    @foreach ($ivaMeses as $m => $iv)
                    <tr class="{{ $m === $p->mes ? 'table-light' : '' }}"><td>{{ $mes($m) }}</td>
                        @if ($iv)
                            @foreach (['base_ventas', 'generado', 'base_compras', 'descontable', 'saldo'] as $k)@php $tot[$k] += $iv[$k]; @endphp<td class="text-end">{{ $n($iv[$k]) }}</td>@endforeach
                        @else
                            <td colspan="5" class="text-muted text-end small">Aún no se ha cargado este mes</td>
                        @endif
                    </tr>
                    @endforeach
                    <tr class="table-success fw-bold"><td>Total del periodo</td>@foreach ($tot as $v)<td class="text-end">{{ $n($v) }}</td>@endforeach</tr>
                    </tbody>
                </table>
                <small class="text-muted">Saldo positivo = IVA a pagar; negativo = saldo a favor. Es antes de retenciones que le practicaron (reteIVA) y saldos a favor anteriores. Úselo como borrador del Formulario 300; los ajustes (IVA no descontable, proporcionalidad, devoluciones) los define el contador.</small>
            </div>
        </div>

        {{-- ===== Estado ===== --}}
        <div class="tab-pane fade" id="t-estado">
            <form method="post" action="{{ route('contabilidad.periodo.datos', $p) }}" class="lp-box">
                @csrf @method('put')
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label small fw-bold">Estado</label><select class="form-select" name="estado">@foreach (\App\Models\ContablePeriodo::ESTADOS as $k => $nm)<option value="{{ $k }}" @selected($p->estado === $k)>{{ $nm }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Base de autorretención</label><input type="number" step="1" min="0" class="form-control" name="autorretencion_base" value="{{ (int) $p->autorretencion_base }}"></div>
                    <div class="col-md-4"><label class="form-label small fw-bold">Valor autorretención</label><input type="number" step="1" min="0" class="form-control" name="autorretencion_valor" value="{{ (int) $p->autorretencion_valor }}"></div>
                    <div class="col-12"><label class="form-label small fw-bold">Notas del mes</label><textarea class="form-control" name="notas" rows="4" maxlength="3000">{{ $p->notas }}</textarea></div>
                </div>
                <button class="btn btn-primary mt-3">Guardar</button>
            </form>
            <form method="post" action="{{ route('contabilidad.periodo.destroy', $p) }}" data-confirm="¿Eliminar {{ $p->nombre }} de {{ $cliente->nombre }}?" data-confirm-ok="Sí, eliminar" data-peligro class="mt-2">@csrf @method('delete')<button class="btn btn-link text-danger">Eliminar este mes</button></form>
        </div>
    </div>
    @else
    <div class="lp-box text-center text-muted py-5"><i class="fa-solid fa-file-import fa-2x mb-2"></i><p class="mb-0">Suba el listado de la DIAN y la base contable para ver el cruce.</p></div>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var t = document.getElementById('todas');
    if (t) t.addEventListener('change', function () { document.querySelectorAll('.ct-chk').forEach(function (c) { c.checked = t.checked; }); });
    var f = document.getElementById('f-concepto');
    if (f) f.addEventListener('submit', function (e) { if (!f.querySelector('.ct-chk:checked')) { e.preventDefault(); LP.toast('Marque al menos una factura.', 'error'); } });
    // Recordar la pestaña abierta
    var tab = location.hash && document.querySelector('[data-bs-target="' + location.hash.replace('#', '#t-') + '"]');
    if (tab && window.bootstrap) new bootstrap.Tab(tab).show();
})();
</script>
@endpush
