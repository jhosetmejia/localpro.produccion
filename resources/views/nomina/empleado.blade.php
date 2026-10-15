@extends('layouts.app')

@section('title', $e->exists ? 'Editar empleado' : 'Nuevo empleado')

@section('content')
<div class="lp-dash" style="max-width:960px;margin:0 auto">
    <div class="lp-hello"><div><small><a href="{{ route('nomina.empleados') }}">Empleados</a></small><h1>{{ $e->exists ? $e->nombre : 'Nuevo' }} <span>empleado</span></h1></div></div>
    @include('layouts.partials.lp-flash')

    @php $auxModo = $e->aux_transporte === null ? 'auto' : ($e->aux_transporte ? 'si' : 'no'); @endphp
    <form method="post" action="{{ $e->exists ? route('nomina.empleados.update', $e) : route('nomina.empleados.store') }}">
        @csrf
        @if ($e->exists) @method('PUT') @endif

        <div class="lp-box mb-3">
            <h3>Datos personales</h3>
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label small fw-bold">Tipo de documento</label>
                    <select name="tipo_documento" class="form-select">@foreach (\App\Models\Empleado::DOCUMENTOS as $k => $n)<option value="{{ $k }}" @selected(old('tipo_documento', $e->tipo_documento) == $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Número</label><input name="documento" class="form-control" value="{{ old('documento', $e->documento) }}" required maxlength="20"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Cargo</label><input name="cargo" class="form-control" value="{{ old('cargo', $e->cargo) }}" maxlength="60"></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Nombres</label><input name="nombres" class="form-control" value="{{ old('nombres', $e->nombres) }}" required maxlength="60"></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Apellidos</label><input name="apellidos" class="form-control" value="{{ old('apellidos', $e->apellidos) }}" required maxlength="60"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Teléfono</label><input name="telefono" class="form-control" value="{{ old('telefono', $e->telefono) }}" maxlength="20"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Correo</label><input name="email" type="email" class="form-control" value="{{ old('email', $e->email) }}" maxlength="120"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Municipio (código DANE)</label><input name="municipio_dane" class="form-control" value="{{ old('municipio_dane', $e->municipio_dane) }}" maxlength="5" placeholder="Ej: 52001 Pasto"></div>
                <div class="col-12"><label class="form-label small fw-bold">Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $e->direccion) }}" maxlength="120"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>Contrato y salario</h3>
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label small fw-bold">Tipo de trabajador</label>
                    <select name="tipo" class="form-select">@foreach (\App\Support\Nomina\Liquidador::TIPOS_TRABAJADOR as $k => $n)<option value="{{ $k }}" @selected(old('tipo', $e->tipo) == $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Contrato</label>
                    <select name="tipo_contrato" class="form-select">@foreach (\App\Models\Empleado::CONTRATOS as $k => $n)<option value="{{ $k }}" @selected(old('tipo_contrato', $e->tipo_contrato) == $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Salario mensual</label><input data-dinero type="number" min="0" step="{{ paso_dinero() }}" id="salario" name="salario" class="form-control fw-bold" value="{{ old('salario', $e->salario) }}" required></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Fecha de ingreso</label><input type="date" name="fecha_ingreso" class="form-control" value="{{ old('fecha_ingreso', $e->fecha_ingreso?->format('Y-m-d')) }}" required></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Fecha de retiro</label><input type="date" name="fecha_retiro" class="form-control" value="{{ old('fecha_retiro', $e->fecha_retiro?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Riesgo ARL</label>
                    <select name="riesgo_arl" class="form-select">@foreach ([1 => 'I (oficina, comercio)', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V (alto riesgo)'] as $k => $n)<option value="{{ $k }}" @selected(old('riesgo_arl', $e->riesgo_arl) == $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Auxilio de transporte</label>
                    <select name="aux_modo" class="form-select">
                        <option value="auto" @selected(old('aux_modo', $auxModo) === 'auto')>Automático (hasta 2 SMMLV)</option>
                        <option value="si" @selected(old('aux_modo', $auxModo) === 'si')>Siempre pagar</option>
                        <option value="no" @selected(old('aux_modo', $auxModo) === 'no')>No pagar</option>
                    </select></div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="salario_integral" value="1" id="integral" @checked(old('salario_integral', $e->salario_integral))><label class="form-check-label" for="integral">Salario integral (mínimo 13 SMMLV; cotiza sobre el 70%)</label></div>
                </div>
            </div>
            <div id="avisoMinimo" class="alert alert-warning mt-3 mb-0 d-none small">
                El salario es menor al mínimo legal (@pesos($anio['smmlv'])). Solo es válido si la jornada es parcial o el pago es proporcional; si no, use liquidación personalizada bajo responsabilidad del negocio.
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>Tipo de liquidación</h3>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="personalizado" value="1" id="personalizado" @checked(old('personalizado', $e->personalizado))>
                <label class="form-check-label fw-bold" for="personalizado">Liquidación personalizada
                    <small class="text-muted d-block fw-normal">Para pagos pactados que no siguen la liquidación legal (más o menos del mínimo, sin auxilio automático). El negocio decide qué aplica.</small></label>
            </div>
            <div id="opcPersonal" class="ps-4">
                <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="seguridad_social" value="1" id="ss" @checked(old('seguridad_social', $e->seguridad_social))><label class="form-check-label" for="ss">Descontar salud y pensión y calcular aportes del empleador</label></div>
                <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="prestaciones" value="1" id="pr" @checked(old('prestaciones', $e->prestaciones))><label class="form-check-label" for="pr">Calcular prestaciones sociales (cesantías, prima, vacaciones)</label></div>
            </div>
            @if ($tienda->nomina_modo === 'electronica')
            <hr>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enviar_dian" value="1" id="dian" @checked(old('enviar_dian', $e->enviar_dian))>
                <label class="form-check-label fw-bold" for="dian">Reportar a la DIAN en la nómina electrónica
                    <small class="text-muted d-block fw-normal">Si lo apaga, el empleado se liquida y recibe su desprendible, pero no se envía a la DIAN.</small></label>
            </div>
            @else
            <input type="hidden" name="enviar_dian" value="{{ $e->enviar_dian ? 1 : 0 }}">
            @endif
        </div>

        <div class="lp-box mb-3">
            <h3>Seguridad social y pago</h3>
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label small fw-bold">EPS</label><input name="eps" class="form-control" value="{{ old('eps', $e->eps) }}" maxlength="60"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Fondo de pensiones</label><input name="afp" class="form-control" value="{{ old('afp', $e->afp) }}" maxlength="60"></div>
                <div class="col-md-4"><label class="form-label small fw-bold">Caja de compensación</label><input name="caja" class="form-control" value="{{ old('caja', $e->caja) }}" maxlength="60"></div>
                <div class="col-md-3"><label class="form-label small fw-bold">Forma de pago</label>
                    <select name="forma_pago" class="form-select">@foreach (['transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', 'cheque' => 'Cheque'] as $k => $n)<option value="{{ $k }}" @selected(old('forma_pago', $e->forma_pago) == $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-bold">Banco</label><input name="banco" class="form-control" value="{{ old('banco', $e->banco) }}" maxlength="40"></div>
                <div class="col-md-3"><label class="form-label small fw-bold">Tipo de cuenta</label>
                    <select name="tipo_cuenta" class="form-select"><option value="">—</option><option value="ahorros" @selected(old('tipo_cuenta', $e->tipo_cuenta) === 'ahorros')>Ahorros</option><option value="corriente" @selected(old('tipo_cuenta', $e->tipo_cuenta) === 'corriente')>Corriente</option></select></div>
                <div class="col-md-3"><label class="form-label small fw-bold">Número de cuenta</label><input name="numero_cuenta" class="form-control" value="{{ old('numero_cuenta', $e->numero_cuenta) }}" maxlength="30"></div>
            </div>
        </div>

        <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar empleado</button>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var min = {{ (float) $anio['smmlv'] }}, sal = document.getElementById('salario'), aviso = document.getElementById('avisoMinimo');
    var per = document.getElementById('personalizado'), opc = document.getElementById('opcPersonal');
    function revisar() {
        var v = parseFloat(sal.value || 0);
        aviso.classList.toggle('d-none', !(v > 0 && v < min));
        opc.style.display = per.checked ? '' : 'none';
    }
    sal.addEventListener('input', revisar); per.addEventListener('change', revisar); revisar();
})();
</script>
@endpush
