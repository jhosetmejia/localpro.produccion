@extends('layouts.app')

@section('title', 'Matrícula · ' . $e->nombre)

@section('content')
@php
    $actual = \App\Models\Instituto\Matricula::periodoActual();
    [$anio] = explode('-', $actual);
    $periodos = [($anio - 1) . '-2', $anio . '-1', $anio . '-2', ($anio + 1) . '-1'];
    $progSel = (int) old('programa_id', $ultima->programa_id ?? $e->programa_id);
    $semSel = (int) old('semestre', $ultima ? min(20, $ultima->semestre + 1) : 1);
    $dia = $inst->inst_dia_pago ?: 5;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('instituto.estudiante', $e) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $e->nombre }}</a></small><h1>Información de <span>matrícula</span></h1>
            <span class="text-muted fw-semibold">{{ $e->tipo_doc }} {{ $e->documento ?: '—' }}{{ $ultima ? ' · última matrícula: ' . $ultima->programa_nombre . ', semestre ' . $ultima->semestre . ' (' . $ultima->periodo_texto . ')' : '' }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('instituto.matricula.store', $e) }}" id="fm">
        @csrf
        <div class="lp-box mb-3">
            <h3>Matrícula académica</h3>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Programa</label>
                    <select name="programa_id" class="form-select" id="m-prog" required>
                        <option value="">— Elegir —</option>
                        @foreach ($programas as $p)<option value="{{ $p->id }}" data-valor="{{ (int) $p->valor_semestre }}" data-contado="{{ (int) $p->precio_contado }}" data-fin="{{ $p->tiene_financiacion ? (int) $p->precio_financiado : 0 }}" data-ini="{{ (int) $p->fin_cuota_inicial }}" data-n="{{ (int) $p->fin_num_cuotas }}" data-sem="{{ $p->semestres }}" @selected($progSel === $p->id)>{{ $p->nombre }}</option>@endforeach
                    </select></div>
                <div class="col-6 col-md-3"><label class="form-label">Periodo</label>
                    <select name="periodo" class="form-select">@foreach ($periodos as $pe)<option value="{{ $pe }}" @selected(old('periodo', $actual) === $pe)>{{ str_replace(['-1', '-2'], [' - I', ' - II'], $pe) }}</option>@endforeach</select></div>
                <div class="col-6 col-md-3"><label class="form-label">Semestre</label><input type="number" name="semestre" class="form-control" min="1" max="20" value="{{ $semSel }}" id="m-sem"></div>
                <div class="col-md-6"><label class="form-label">Jornada</label>
                    <div class="d-flex flex-wrap gap-3 pt-1">@foreach (\App\Models\Instituto\Estudiante::JORNADAS as $k => $n)<label class="form-check"><input class="form-check-input" type="radio" name="jornada" value="{{ $k }}" @checked(old('jornada', $ultima->jornada ?? $e->jornada) === $k) required> {{ $n }}</label>@endforeach</div></div>
                <div class="col-md-3"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control" value="{{ old('fecha', today()->format('Y-m-d')) }}" id="m-fecha"></div>
                <div class="col-12"><label class="form-label">Módulos pendientes <span class="text-muted fw-normal">(opcional)</span></label><textarea name="modulos_pendientes" class="form-control" rows="2" maxlength="2000" placeholder="Semestre, horario y módulos que debe">{{ old('modulos_pendientes') }}</textarea></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>Matrícula financiera</h3>
            <div class="row g-2">
                <div class="col-12">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="modalidad" value="contado" id="mod-c" @checked(old('modalidad', 'contado') === 'contado')><label class="btn btn-outline-dark" for="mod-c">Contado</label>
                        <input type="radio" class="btn-check" name="modalidad" value="credito" id="mod-k" @checked(old('modalidad') === 'credito')><label class="btn btn-outline-dark" for="mod-k">Crédito (cuotas)</label>
                    </div>
                </div>
                <div class="col-12"><div class="small fw-semibold" id="m-precios" style="color:#11702f"></div></div>
                <div class="col-md-4"><label class="form-label">Valor a pagar</label><input data-dinero type="number" name="valor_semestre" class="form-control fw-bold" min="0" required id="m-valor" value="{{ old('valor_semestre') }}"></div>
                <div class="col-md-4 m-cred"><label class="form-label">Cuota inicial</label><input data-dinero type="number" name="cuota_inicial" class="form-control" min="0" id="m-inicial" value="{{ old('cuota_inicial', 0) }}"></div>
                <div class="col-md-4 m-cred"><label class="form-label">No. de cuotas mensuales</label><input type="number" name="num_cuotas" class="form-control" min="1" max="24" id="m-n" value="{{ old('num_cuotas', 5) }}"></div>
                <div class="col-12 m-cred"><div class="p-3 rounded-3" style="background:#f4fcf6;border:1.5px solid #bfeccb" id="m-plan"></div>
                    <div class="small text-muted mt-1">Las cuotas vencen el día {{ $dia }} de cada mes (se pagan del 1 al {{ $dia }}). El incumplimiento queda en Cartera y morosos.
                        @if (auth()->user()->es_superadmin || auth()->user()->hasAnyRole(['dueno', 'administrador']))<a href="#" onclick="document.getElementById('cfg-dia').hidden=false;return false">Cambiar el día</a>@endif</div></div>
            </div>
        </div>
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-file-signature me-1"></i>Guardar matrícula</button>
            <a href="{{ route('instituto.estudiante', $e) }}" class="btn btn-light btn-lg">Cancelar</a>
        </div>
    </form>
    <form method="post" action="{{ route('instituto.config') }}" id="cfg-dia" class="lp-box d-flex gap-2 align-items-center" hidden>
        @csrf <span class="small fw-bold">Las cuotas vencen el día</span>
        <input type="number" name="inst_dia_pago" class="form-control" style="width:90px" min="1" max="28" value="{{ $dia }}">
        <span class="small fw-bold">de cada mes</span><button class="btn btn-dark btn-sm">Guardar</button>
    </form>
</div>
@push('js')
<script>
(function () {
    var f = document.getElementById('fm'), prog = document.getElementById('m-prog'), valor = document.getElementById('m-valor');
    var ini = document.getElementById('m-inicial'), n = document.getElementById('m-n'), plan = document.getElementById('m-plan'), fecha = document.getElementById('m-fecha');
    var DIA = {{ (int) $dia }}, fmt = new Intl.NumberFormat('es-CO'), tocado = !!valor.value;
    var meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    function credito() { return (f.querySelector('[name=modalidad]:checked') || {}).value === 'credito'; }
    function calc() {
        document.querySelectorAll('.m-cred').forEach(function (x) { x.style.display = credito() ? '' : 'none'; });
        if (!credito()) return;
        var total = +valor.value || 0, i0 = Math.min(total, +ini.value || 0), k = Math.max(1, +n.value || 1);
        var resto = Math.round(total - i0), c = Math.floor(resto / k), ult = resto - c * (k - 1);
        var d = fecha.value ? new Date(fecha.value + 'T12:00:00') : new Date(), html = '<b>Plan de pagos</b><div class="small mt-1">';
        if (i0 > 0) html += 'Cuota inicial: <b>$' + fmt.format(i0) + '</b> (hoy)<br>';
        for (var i = 1; i <= k; i++) {
            var m = new Date(d.getFullYear(), d.getMonth() + i, DIA);
            html += 'Cuota ' + i + ': <b>$' + fmt.format(i === k ? ult : c) + '</b> · vence ' + DIA + ' de ' + meses[m.getMonth()] + ' ' + m.getFullYear() + '<br>';
        }
        plan.innerHTML = html + '</div>';
    }
    // Precios del programa: contado (con descuento) o financiado (cuota inicial + cuotas); si no tiene, el precio pleno
    var precios = document.getElementById('m-precios');
    function aplicar() {
        var o = prog.options[prog.selectedIndex], dd = o ? o.dataset : {};
        var pleno = +dd.valor || 0, cont = +dd.contado || 0, fin = +dd.fin || 0;
        var t = [];
        if (pleno) t.push('Precio pleno $' + fmt.format(pleno));
        if (cont) t.push('Contado $' + fmt.format(cont) + (pleno > cont ? ' (ahorra $' + fmt.format(pleno - cont) + ')' : ''));
        if (fin) t.push('Financiado $' + fmt.format(fin) + ': inicial $' + fmt.format(+dd.ini || 0) + ' + ' + dd.n + ' cuotas');
        precios.textContent = t.join(' · ');
        if (!o || !o.value) return;
        if (credito()) {
            valor.value = fin || pleno;
            if (fin) { ini.value = +dd.ini || 0; n.value = +dd.n || 1; }
        } else {
            valor.value = cont || pleno;
        }
    }
    prog.addEventListener('change', function () { aplicar(); calc(); });
    valor.addEventListener('input', function () { tocado = true; calc(); });
    [ini, n, fecha].forEach(function (x) { x.addEventListener('input', calc); });
    f.querySelectorAll('[name=modalidad]').forEach(function (r) { r.addEventListener('change', function () { aplicar(); calc(); }); });
    if (!valor.value) aplicar(); else { var v0 = valor.value, i0 = ini.value, n0 = n.value; aplicar(); valor.value = v0; ini.value = i0; n.value = n0; }
    calc();
})();
</script>
@endpush
@endsection
