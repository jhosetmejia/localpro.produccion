@extends('layouts.app')

@section('title', 'Laboratorio dental')

@push('css')
<style>
.lb-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:14px;align-items:start}
.lb-grid>*{min-width:0}
.lb-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:1rem}
.lb-k{background:#fff;border:1.5px solid #d6eef3;border-radius:20px;padding:.9rem 1rem}.lb-k small{display:block;color:var(--lp-muted);font-weight:700;font-size:.78rem}.lb-k b{font-size:1.45rem;font-weight:900}
.lb-fila{display:grid;grid-template-columns:1fr auto;gap:.6rem;padding:.75rem 0;border-bottom:1px solid #eef5f7}
.lb-fila small{display:block;color:var(--lp-muted);font-weight:600}
.lb-acc{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;align-items:center}
.lb-fil{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:.6rem}
.lb-fil a{border:1.5px solid var(--lp-line);border-radius:999px;padding:.3rem .8rem;font-weight:800;font-size:.8rem;text-decoration:none;color:var(--lp-ink);background:#fff}
.lb-fil a.on{background:#0e7490;border-color:#0e7490;color:#fff}
.lb-form label{font-size:.75rem;font-weight:800;color:var(--lp-muted);margin:.4rem 0 .15rem;display:block}
.lb-form .form-control,.lb-form .form-select{border-radius:12px}
.lb-res{position:absolute;left:0;right:0;top:100%;z-index:20;background:#fff;border:1.5px solid #d6eef3;border-radius:14px;box-shadow:0 18px 40px -20px rgba(0,0,0,.3);max-height:240px;overflow:auto}
.lb-res a{display:block;padding:.5rem .8rem;text-decoration:none;color:inherit;border-bottom:1px solid #f1f5f9}
.lb-res a:hover{background:#ecfeff}
@media (max-width:991px){.lb-grid{grid-template-columns:1fr}.lb-kpis{grid-template-columns:1fr 1fr}.lb-fila{grid-template-columns:1fr}.lb-acc{justify-content:flex-start}}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-flask me-1"></i>Consultorio odontológico</small><h1>Laboratorio <span>dental</span></h1>
            <span class="text-muted fw-semibold">Coronas, prótesis, placas y demás trabajos que envía al laboratorio: cuándo llegan y cuánto cuestan.</span></div>
    </div>
    @include('consultorio._nav')

    <div class="lb-kpis">
        <div class="lb-k"><small>En el laboratorio</small><b>{{ $kpi['en_lab'] }}</b></div>
        <div class="lb-k"><small>Atrasados</small><b style="color:{{ $kpi['atrasados'] ? '#e11d48' : 'inherit' }}">{{ $kpi['atrasados'] }}</b></div>
        <div class="lb-k"><small>Costo de laboratorio este mes</small><b>{{ dinero($kpi['costo_mes']) }}</b></div>
    </div>

    <div class="lb-grid">
        <div class="cs-card">
            <div class="lb-fil">
                @foreach (['pendientes' => 'Pendientes', 'enviado' => 'En el laboratorio', 'recibido' => 'Recibidos', 'instalado' => 'Instalados', 'devuelto' => 'Devueltos', 'todos' => 'Todos'] as $k => $txt)
                <a href="{{ route('odonto.laboratorio', ['estado' => $k]) }}" class="{{ $estado === $k ? 'on' : '' }}">{{ $txt }}</a>
                @endforeach
            </div>
            @forelse ($ordenes as $o)
            @php [$en, $ec] = \App\Models\LaboratorioOrden::ESTADOS[$o->estado]; @endphp
            <div class="lb-fila">
                <div>
                    <b>{{ $o->trabajo }}{{ $o->pieza ? ' · pieza ' . $o->pieza : '' }}</b> <span class="cs-est" style="background:{{ $ec }}">{{ $en }}</span>
                    @if ($o->atrasado)<span class="cs-est" style="background:#e11d48">Atrasado</span>@endif
                    <small><a href="{{ route('consultorio.paciente', $o->paciente_id) }}" class="text-decoration-none" style="color:#0e7490">{{ $o->paciente?->nombre_completo }}</a> · {{ $o->numero }} · {{ $o->laboratorio }}</small>
                    <small>Enviado {{ $o->enviado->format('d/m/Y') }}{{ $o->entrega ? ' · Entrega ' . $o->entrega->format('d/m/Y') : '' }}{{ $o->recibido ? ' · Llegó ' . $o->recibido->format('d/m/Y') : '' }}{{ $o->color ? ' · Color ' . $o->color : '' }}{{ $o->material ? ' · ' . $o->material : '' }}{{ $o->costo ? ' · ' . dinero($o->costo) : '' }}</small>
                    @if ($o->notas)<small>{{ $o->notas }}</small>@endif
                </div>
                <div class="lb-acc">
                    @php $sig = ['enviado' => ['recibido'], 'devuelto' => ['recibido'], 'recibido' => ['instalado', 'devuelto'], 'instalado' => []][$o->estado] ?? []; @endphp
                    @foreach (['recibido' => ['Llegó', 'fa-box-open'], 'instalado' => ['Instalado', 'fa-check'], 'devuelto' => ['Devolver para corregir', 'fa-rotate-left']] as $est => [$txt, $ic])
                    @if (in_array($est, $sig, true))
                    <form method="post" action="{{ route('odonto.laboratorio.estado', $o) }}" @if ($est === 'devuelto') data-confirm="¿Devolver al laboratorio?" data-confirm-texto="Escriba el motivo en la nota de la orden si quiere dejarlo registrado." data-confirm-ok="Sí, devolver" @endif>
                        @csrf<input type="hidden" name="estado" value="{{ $est }}"><button class="cs-btn sm"><i class="fa-solid {{ $ic }}"></i>{{ $txt }}</button></form>
                    @endif
                    @endforeach
                </div>
            </div>
            @empty
            <div class="lp-empty">No hay trabajos en esta lista.</div>
            @endforelse
            <div class="mt-3">{{ $ordenes->links() }}</div>
        </div>

        <div class="cs-card lb-form" id="nueva">
            <h3>Enviar al laboratorio</h3>
            <form method="post" action="{{ route('odonto.laboratorio.guardar') }}">
                @csrf
                <label>Paciente</label>
                <div style="position:relative">
                    <input type="hidden" name="paciente_id" id="lb-pid" value="{{ old('paciente_id', $paciente?->id) }}">
                    <input class="form-control" id="lb-buscar" autocomplete="off" placeholder="Nombre o documento" value="{{ $paciente?->nombre_completo }}">
                    <div class="lb-res" id="lb-res" style="display:none"></div>
                </div>
                @if ($planes->count())
                <label>Plan de tratamiento (opcional)</label>
                <select name="plan_id" class="form-select"><option value="">—</option>@foreach ($planes as $pl)<option value="{{ $pl->id }}">{{ $pl->numero }} · {{ $pl->nombre }}</option>@endforeach</select>
                @endif
                <label>Laboratorio</label>
                <input name="laboratorio" class="form-control" list="lb-labs" required maxlength="120" value="{{ old('laboratorio') }}" placeholder="Ej. Laboratorio Dental Andino">
                <datalist id="lb-labs">@foreach ($laboratorios as $l)<option value="{{ $l }}">@endforeach</datalist>
                <label>Trabajo</label>
                <input name="trabajo" class="form-control" list="lb-trab" required maxlength="160" value="{{ old('trabajo') }}" placeholder="Ej. Corona en zirconio">
                <datalist id="lb-trab">@foreach (['Corona en zirconio', 'Corona metal-porcelana', 'Corona en disilicato de litio', 'Incrustación', 'Prótesis total superior', 'Prótesis total inferior', 'Prótesis parcial removible', 'Puente fijo', 'Placa miorrelajante', 'Retenedor', 'Carillas', 'Modelos de estudio', 'Guía quirúrgica'] as $tr)<option value="{{ $tr }}">@endforeach</datalist>
                <div class="row g-2">
                    <div class="col-4"><label>Pieza(s)</label><input name="pieza" class="form-control" maxlength="30" placeholder="Ej. 36"></div>
                    <div class="col-4"><label>Color</label><input name="color" class="form-control" maxlength="30" placeholder="Ej. A2"></div>
                    <div class="col-4"><label>Material</label><input name="material" class="form-control" maxlength="80"></div>
                    <div class="col-6"><label>Enviado</label><input type="date" name="enviado" class="form-control" required value="{{ old('enviado', today()->toDateString()) }}"></div>
                    <div class="col-6"><label>Entrega</label><input type="date" name="entrega" class="form-control" value="{{ old('entrega', today()->addDays(7)->toDateString()) }}"></div>
                </div>
                <label>Costo del laboratorio</label>
                <input name="costo" class="form-control" data-dinero inputmode="numeric" placeholder="0">
                <label>Notas</label>
                <input name="notas" class="form-control" maxlength="500" placeholder="Indicaciones para el laboratorio">
                <button class="cs-btn prim w-100 justify-content-center mt-3"><i class="fa-solid fa-truck-fast"></i>Registrar envío</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var q = document.getElementById('lb-buscar'), res = document.getElementById('lb-res'), pid = document.getElementById('lb-pid'), t;
    q.addEventListener('input', function () {
        pid.value = ''; clearTimeout(t);
        if (q.value.trim().length < 2) { res.style.display = 'none'; return; }
        t = setTimeout(function () {
            fetch({!! json_encode(route('consultorio.pacientes')) !!} + '?q=' + encodeURIComponent(q.value.trim()), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); }).then(function (l) {
                    res.innerHTML = ''; res.style.display = '';
                    if (!l.length) { res.innerHTML = '<div class="p-2 text-muted small">Sin resultados</div>'; return; }
                    l.forEach(function (p) {
                        var a = document.createElement('a'); a.href = '#'; a.textContent = p.nombre + (p.documento ? ' · ' + p.documento : '');
                        a.addEventListener('click', function (e) { e.preventDefault(); window.location = {!! json_encode(route('odonto.laboratorio')) !!} + '?paciente=' + p.id + '#nueva'; });
                        res.appendChild(a);
                    });
                });
        }, 250);
    });
})();
</script>
@endpush
