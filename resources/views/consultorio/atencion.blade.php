@extends('layouts.app')

@section('title', 'Atención · ' . $p->nombre_completo)

@push('css')
<style>
.at-cab{display:flex;gap:1rem;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
.at-cab h2{font-size:1.35rem;font-weight:900;margin:0}
.at-cab .meta{color:var(--lp-muted);font-weight:600;font-size:.88rem}
.at-estado{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.3rem .8rem;font-weight:800;font-size:.8rem}
.at-estado.borrador{background:#fff6d6;color:#9a6a00}.at-estado.cerrada{background:#dcfce7;color:#14753a}.at-estado.mal{background:#fde2e8;color:#b0173f}
.at-grid{display:grid;grid-template-columns:1fr 320px;gap:14px;margin-top:14px;align-items:start}
.at-sec{margin-bottom:1.1rem}
.at-sec>label,.at-sec>h4{display:block;font-weight:900;font-size:.88rem;margin:0 0 .35rem}
.at-sec small.ay{display:block;color:var(--lp-muted);font-weight:600;font-size:.74rem;margin:-.2rem 0 .35rem}
.at-sec textarea{min-height:76px}
.at-signos{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.at-signos label{font-size:.72rem;font-weight:800;color:var(--lp-muted);margin-bottom:.15rem;display:block}
.at-imc{background:#f4f8f5;border-radius:12px;padding:.45rem .7rem;font-weight:800;font-size:.85rem;display:flex;align-items:center}
.at-dx{display:grid;grid-template-columns:110px 1fr 150px 36px;gap:6px;margin-bottom:6px;position:relative}
.at-dx .res{position:absolute;left:0;right:40px;top:100%;z-index:30;background:#fff;border:1.5px solid var(--lp-line);border-radius:12px;box-shadow:0 18px 30px -18px rgba(0,0,0,.4);max-height:240px;overflow:auto;display:none}
.at-dx .res button{display:block;width:100%;text-align:left;border:0;background:none;padding:.45rem .7rem;font-size:.85rem;border-bottom:1px solid #f1f4f2}
.at-dx .res button:hover{background:#f0fbf4}
.at-dx .res b{color:var(--lp-green-2)}
.at-dx .quitar{border:0;background:#fde2e8;color:#b0173f;border-radius:10px;font-weight:900}
.at-barra{position:sticky;bottom:0;z-index:20;background:rgba(244,248,245,.96);backdrop-filter:blur(8px);padding:.8rem 0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--lp-line);margin-top:.5rem}
.at-guardado{color:var(--lp-muted);font-size:.8rem;font-weight:700;margin-left:auto}
.at-leer h4{font-size:.8rem;font-weight:900;color:var(--lp-muted);text-transform:uppercase;letter-spacing:.04em;margin:1rem 0 .25rem}
.at-leer h4:first-child{margin-top:0}
.at-leer p{white-space:pre-line;margin:0}
.at-leer .sv{display:flex;flex-wrap:wrap;gap:6px}.at-leer .sv span{background:#f4f8f5;border-radius:10px;padding:.25rem .6rem;font-size:.85rem;font-weight:700}
.at-nota{border-left:3px solid #ffcc33;background:#fffbea;border-radius:0 12px 12px 0;padding:.6rem .8rem;margin-bottom:.6rem}
.at-nota small{display:block;color:#8a6d00;font-weight:800;font-size:.74rem}
.at-nota p{margin:.2rem 0 0;white-space:pre-line}
.at-side .cs-card{margin-bottom:12px}
.at-ant{font-size:.84rem}.at-ant b{display:block;color:var(--lp-muted);font-size:.72rem;margin-top:.4rem}
.at-prev{display:block;padding:.45rem 0;border-bottom:1px dashed #edf2ee;text-decoration:none;color:inherit;font-size:.85rem}
.at-prev:last-child{border-bottom:0}.at-prev small{color:var(--lp-muted);display:block}
@media (max-width:991px){.at-grid{grid-template-columns:1fr}.at-signos{grid-template-columns:repeat(2,1fr)}.at-dx{grid-template-columns:90px 1fr 36px}.at-dx select{grid-column:1/3}}
</style>
@endpush

@section('content')
@php
    $c = $a->contenido ?? [];
    $s = $c['signos'] ?? [];
    $dxs = $a->diagnosticos ?: [];
    $etiquetas = ['motivo' => 'Motivo de consulta', 'enfermedad_actual' => 'Enfermedad actual', 'analisis' => 'Análisis', 'plan' => 'Plan y conducta'];
    foreach (\App\Support\Consultorio::plantillas() as $pl) foreach ($pl['campos'] as $f) $etiquetas[$f[0]] ??= $f[1];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.paciente', $p) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p->nombre_completo }}</a></small></div>
    </div>
    @include('consultorio._nav')

    <div class="cs-card">
        <div class="at-cab">
            <div>
                <h2>{{ $p->nombre_completo }}</h2>
                <div class="meta">{{ trim($p->tipo_documento . ' ' . $p->documento) }}{{ $p->edad ? ' · ' . $p->edad : '' }}{{ $p->aseguradora ? ' · ' . $p->aseguradora : '' }}</div>
                <div class="meta">Atención n.º {{ $a->numero }} · {{ $a->fecha->format('d/m/Y h:i a') }} · {{ $a->profesional_nombre ?: $a->profesional?->name }}{{ $a->profesional_registro ? ' · Reg. ' . $a->profesional_registro : '' }}</div>
            </div>
            <div class="text-end">
                @if ($a->cerrada)
                    @if ($a->integra)<span class="at-estado cerrada"><i class="fa-solid fa-file-signature"></i>Cerrada y firmada {{ $a->cerrada_at->format('d/m/Y h:i a') }}</span>
                    @else<span class="at-estado mal"><i class="fa-solid fa-triangle-exclamation"></i>¡El contenido no coincide con lo firmado!</span>@endif
                @else
                    <span class="at-estado borrador"><i class="fa-solid fa-pen-to-square"></i>Borrador (sin cerrar)</span>
                @endif
                @if ($a->cerrada)
                <div class="d-flex gap-2 justify-content-end flex-wrap mt-2">
                    <a href="{{ route('consultorio.atencion.imprimir', [$a, 'doc' => 'historia']) }}" target="_blank" class="cs-btn sm"><i class="fa-solid fa-print"></i>Historia</a>
                    @if ($a->formula)<a href="{{ route('consultorio.atencion.imprimir', [$a, 'doc' => 'formula']) }}" target="_blank" class="cs-btn sm"><i class="fa-solid fa-prescription"></i>Fórmula</a>@endif
                    @if ($a->incapacidad_dias)<a href="{{ route('consultorio.atencion.imprimir', [$a, 'doc' => 'incapacidad']) }}" target="_blank" class="cs-btn sm"><i class="fa-solid fa-bed"></i>Incapacidad</a>@endif
                </div>
                @endif
            </div>
        </div>
        @if ($p->alergias)<div class="cs-alerta mt-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>Alergias: {{ $p->alergias }}</div>@endif
    </div>

    <div class="at-grid">
        <div>
        @if ($puedeEditar)
        <form method="post" action="{{ route('consultorio.atencion.guardar', $a) }}" class="cs-card" id="fAt">
            @csrf @method('PUT')
            <div class="at-sec">
                <label for="fPl">Tipo de historia</label>
                <select name="plantilla" id="fPl" class="form-select" style="max-width:320px">
                    @foreach (\App\Support\Consultorio::plantillas() as $k => $pl)<option value="{{ $k }}" @selected($a->plantilla === $k)>{{ $pl['nombre'] }}</option>@endforeach
                </select>
            </div>
            <div class="at-sec"><label>Motivo de consulta</label><textarea name="c[motivo]" class="form-control" rows="2" maxlength="10000" placeholder="En palabras del paciente">{{ $c['motivo'] ?? '' }}</textarea></div>
            <div class="at-sec"><label>Enfermedad actual</label><textarea name="c[enfermedad_actual]" class="form-control" rows="3" maxlength="10000" placeholder="Inicio, evolución, síntomas asociados, tratamientos previos">{{ $c['enfermedad_actual'] ?? '' }}</textarea></div>

            @if ($plantilla['signos'])
            <div class="at-sec">
                <h4>Signos vitales y medidas</h4>
                <div class="at-signos">
                    @foreach (\App\Support\Consultorio::SIGNOS as [$k, $lab, $tipo, $ay])
                    <div><label>{{ $lab }} <span class="fw-normal">({{ $ay }})</span></label><input name="s[{{ $k }}]" class="form-control form-control-sm" value="{{ $s[$k] ?? '' }}" maxlength="30" @if ($tipo === 'numero') inputmode="decimal" @endif data-sv="{{ $k }}"></div>
                    @endforeach
                    <div><label>IMC</label><div class="at-imc" id="imc">—</div></div>
                </div>
            </div>
            @endif

            @foreach ($plantilla['campos'] as [$k, $lab, $tipo, $ay])
            <div class="at-sec"><label>{{ $lab }}</label>@if ($ay)<small class="ay">{{ $ay }}</small>@endif
                @if ($tipo === 'area')<textarea name="c[{{ $k }}]" class="form-control" rows="3" maxlength="10000">{{ $c[$k] ?? '' }}</textarea>
                @else<input name="c[{{ $k }}]" class="form-control" value="{{ $c[$k] ?? '' }}" maxlength="500">@endif
            </div>
            @endforeach

            <div class="at-sec">
                <h4>Diagnósticos (CIE-10)</h4>
                <small class="ay">Escriba el código o una palabra (ej. "lumbago", "caries", "ansiedad") y elija de la lista. También puede escribirlo a mano.</small>
                <div id="dxLista">
                    @foreach (array_merge($dxs, [[]]) as $i => $d)
                    <div class="at-dx">
                        <input name="dx[{{ $i }}][codigo]" class="form-control form-control-sm dx-cod" value="{{ $d['codigo'] ?? '' }}" placeholder="Código" maxlength="10" autocomplete="off">
                        <input name="dx[{{ $i }}][texto]" class="form-control form-control-sm dx-txt" value="{{ $d['texto'] ?? '' }}" placeholder="Diagnóstico" maxlength="200" autocomplete="off">
                        <select name="dx[{{ $i }}][tipo]" class="form-select form-select-sm"><option value="impresion" @selected(($d['tipo'] ?? '') !== 'confirmado')>Impresión diagnóstica</option><option value="confirmado" @selected(($d['tipo'] ?? '') === 'confirmado')>Confirmado</option></select>
                        <button type="button" class="quitar" title="Quitar">×</button>
                        <div class="res"></div>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="cs-btn sm" id="dxMas"><i class="fa-solid fa-plus"></i>Otro diagnóstico</button>
            </div>

            <div class="at-sec"><label>Análisis</label><textarea name="c[analisis]" class="form-control" rows="2" maxlength="10000">{{ $c['analisis'] ?? '' }}</textarea></div>
            <div class="at-sec"><label>Plan y conducta</label><textarea name="c[plan]" class="form-control" rows="3" maxlength="10000" placeholder="Exámenes, remisiones, recomendaciones, próximo control">{{ $c['plan'] ?? '' }}</textarea></div>
            <div class="at-sec"><label>Fórmula / prescripción</label><small class="ay">Un medicamento por línea: nombre, presentación, dosis, frecuencia, duración y cantidad. Sale en una hoja aparte para el paciente.</small>
                <textarea name="formula" class="form-control" rows="3" maxlength="10000" placeholder="Acetaminofén tabletas 500 mg · 1 cada 8 horas por 3 días · Total: 9 tabletas">{{ $a->formula }}</textarea></div>
            <div class="at-sec row g-2">
                <div class="col-6 col-md-3"><label>Incapacidad (días)</label><input type="number" name="incapacidad_dias" class="form-control" min="0" max="180" value="{{ $a->incapacidad_dias }}"></div>
                <div class="col-6 col-md-3"><label>Desde</label><input type="date" name="incapacidad_desde" class="form-control" value="{{ optional($a->incapacidad_desde)->toDateString() ?: today()->toDateString() }}"></div>
            </div>

            <div class="at-barra">
                <button type="submit" class="cs-btn"><i class="fa-solid fa-floppy-disk"></i>Guardar borrador</button>
                <button type="button" class="cs-btn prim" id="btnCerrar"><i class="fa-solid fa-file-signature"></i>Cerrar y firmar</button>
                <span class="at-guardado" id="guardado">Se guarda solo cada minuto</span>
                <input type="hidden" name="cerrar" value="" id="fCerrar">
            </div>
        </form>
        @else
        <div class="cs-card at-leer">
            @foreach (['motivo', 'enfermedad_actual'] as $k)
            @if (!empty($c[$k]))<h4>{{ $etiquetas[$k] }}</h4><p>{{ $c[$k] }}</p>@endif
            @endforeach
            @if ($s)
            <h4>Signos vitales</h4>
            <div class="sv">@foreach (\App\Support\Consultorio::SIGNOS as [$k, $lab, $t, $ay])@if (!empty($s[$k]))<span>{{ $lab }}: {{ $s[$k] }} {{ \Illuminate\Support\Str::before($ay, ' ·') }}</span>@endif @endforeach
                @if (!empty($s['peso']) && !empty($s['talla']) && (float) $s['talla'] > 0)<span>IMC: {{ number_format((float) $s['peso'] / pow((float) $s['talla'] / 100, 2), 1, ',', '.') }}</span>@endif</div>
            @endif
            @foreach ($c as $k => $v)
            @if (!in_array($k, ['motivo', 'enfermedad_actual', 'signos', 'analisis', 'plan'], true) && is_string($v) && trim($v) !== '')<h4>{{ $etiquetas[$k] ?? ucfirst(str_replace('_', ' ', $k)) }}</h4><p>{{ $v }}</p>@endif
            @endforeach
            @if ($dxs)
            <h4>Diagnósticos</h4>
            @foreach ($dxs as $d)<p><b>{{ $d['codigo'] ?? '' }}</b> {{ $d['texto'] ?? '' }} <span class="text-muted small">· {{ ($d['tipo'] ?? '') === 'confirmado' ? 'Confirmado' : 'Impresión diagnóstica' }}</span></p>@endforeach
            @endif
            @foreach (['analisis', 'plan'] as $k)
            @if (!empty($c[$k]))<h4>{{ $etiquetas[$k] }}</h4><p>{{ $c[$k] }}</p>@endif
            @endforeach
            @if ($a->formula)<h4>Fórmula</h4><p>{{ $a->formula }}</p>@endif
            @if ($a->incapacidad_dias)<h4>Incapacidad</h4><p>{{ $a->incapacidad_dias }} día(s) desde el {{ $a->incapacidad_desde?->format('d/m/Y') }}</p>@endif
            @if (!$a->cerrada)<div class="cs-alerta mt-3">Esta atención está en borrador y solo la puede editar {{ $a->profesional_nombre }}.</div>@endif
        </div>

        @if ($a->cerrada)
        <div class="cs-card mt-3">
            <h3>Notas aclaratorias <small>la historia firmada no se modifica: se aclara con notas</small></h3>
            @forelse ($a->notas as $n)
            <div class="at-nota"><small>{{ $n->autor }} · {{ $n->created_at?->format('d/m/Y h:i a') }}</small><p>{{ $n->texto }}</p></div>
            @empty<div class="text-muted small mb-2">Sin notas.</div>@endforelse
            <form method="post" action="{{ route('consultorio.atencion.nota', $a) }}" class="mt-2">
                @csrf
                <textarea name="texto" class="form-control mb-2" rows="2" maxlength="5000" required placeholder="Ej. Se aclara que la dosis correcta es 500 mg y no 50 mg."></textarea>
                <button class="cs-btn sm"><i class="fa-solid fa-plus"></i>Agregar nota aclaratoria</button>
            </form>
        </div>
        @endif
        @endif
        </div>

        <div class="at-side">
            @php $ant = array_filter((array) $p->antecedentes); @endphp
            <div class="cs-card at-ant">
                <h3>Antecedentes <a href="{{ route('consultorio.pacientes.edit', $p) }}" class="small fw-bold text-decoration-none" style="color:var(--lp-green-2)">Editar</a></h3>
                @forelse ($ant as $k => $v)<b>{{ \App\Models\Paciente::ANTECEDENTES[$k] ?? $k }}</b>{{ $v }}@empty<span class="text-muted">Sin antecedentes registrados.</span>@endforelse
            </div>
            <div class="cs-card">
                <h3>Atenciones anteriores</h3>
                @forelse ($anteriores as $x)
                <a href="{{ route('consultorio.atencion', $x) }}" class="at-prev"><b>{{ $x->fecha->format('d/m/Y') }}</b> · {{ \App\Support\Consultorio::plantilla($x->plantilla)['nombre'] }}<small>{{ \Illuminate\Support\Str::limit($x->contenido['motivo'] ?? '', 70) }}</small></a>
                @empty<span class="text-muted small">Es su primera atención.</span>@endforelse
            </div>
            @can('usar-pos')<a href="{{ route('pos.index') }}" class="cs-btn w-100 justify-content-center"><i class="fa-solid fa-cash-register"></i>Cobrar la consulta en la caja</a>@endcan
        </div>
    </div>
</div>
@endsection

@if ($puedeEditar)
@push('js')
<script>
(function () {
    var f = document.getElementById('fAt'), URL_CIE = @json(route('consultorio.cie')), cambios = false, guardado = document.getElementById('guardado');
    // IMC automático
    function imc() {
        var p = parseFloat((document.querySelector('[data-sv=peso]') || {}).value), t = parseFloat((document.querySelector('[data-sv=talla]') || {}).value), el = document.getElementById('imc');
        if (!el) return;
        if (p > 0 && t > 0) { var v = p / Math.pow(t / 100, 2); el.textContent = v.toFixed(1).replace('.', ',') + (v < 18.5 ? ' · bajo peso' : v < 25 ? ' · normal' : v < 30 ? ' · sobrepeso' : ' · obesidad'); }
        else el.textContent = '—';
    }
    document.querySelectorAll('[data-sv]').forEach(function (i) { i.addEventListener('input', imc); }); imc();

    // Diagnósticos con buscador CIE-10
    var lista = document.getElementById('dxLista'), n = lista.children.length, t = null;
    function fila() {
        var d = document.createElement('div'); d.className = 'at-dx';
        d.innerHTML = '<input name="dx[' + n + '][codigo]" class="form-control form-control-sm dx-cod" placeholder="Código" maxlength="10" autocomplete="off">' +
            '<input name="dx[' + n + '][texto]" class="form-control form-control-sm dx-txt" placeholder="Diagnóstico" maxlength="200" autocomplete="off">' +
            '<select name="dx[' + n + '][tipo]" class="form-select form-select-sm"><option value="impresion">Impresión diagnóstica</option><option value="confirmado">Confirmado</option></select>' +
            '<button type="button" class="quitar" title="Quitar">×</button><div class="res"></div>';
        n++; lista.appendChild(d); return d;
    }
    document.getElementById('dxMas').addEventListener('click', function () { fila().querySelector('.dx-cod').focus(); });
    lista.addEventListener('click', function (e) {
        var q = e.target.closest('.quitar');
        if (q) { var r = q.closest('.at-dx'); if (lista.children.length > 1) r.remove(); else r.querySelectorAll('input').forEach(function (i) { i.value = ''; }); cambios = true; return; }
        var b = e.target.closest('[data-cod]');
        if (b) { var row = b.closest('.at-dx'); row.querySelector('.dx-cod').value = b.dataset.cod; row.querySelector('.dx-txt').value = b.dataset.txt; row.querySelector('.res').style.display = 'none'; cambios = true; }
    });
    lista.addEventListener('input', function (e) {
        if (!e.target.matches('.dx-cod, .dx-txt')) return;
        var row = e.target.closest('.at-dx'), res = row.querySelector('.res'), q = e.target.value.trim();
        clearTimeout(t);
        if (q.length < 2) { res.style.display = 'none'; return; }
        t = setTimeout(function () {
            fetch(URL_CIE + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
                if (!l.length) { res.style.display = 'none'; return; }
                res.innerHTML = l.map(function (x) { return '<button type="button" data-cod="' + x.codigo + '" data-txt="' + x.texto.replace(/"/g, '&quot;') + '"><b>' + x.codigo + '</b> ' + x.texto + '</button>'; }).join('');
                res.style.display = 'block';
            });
        }, 200);
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.at-dx')) lista.querySelectorAll('.res').forEach(function (r) { r.style.display = 'none'; }); });

    // Cambiar el tipo de historia: guarda y recarga con los campos de esa especialidad
    document.getElementById('fPl').addEventListener('change', function () { cambios = false; f.submit(); });

    // Autoguardado cada minuto si hubo cambios
    f.addEventListener('input', function () { cambios = true; guardado.textContent = 'Cambios sin guardar…'; });
    function autoguardar() {
        if (!cambios) return;
        cambios = false;
        var datos = new FormData(f); datos.delete('cerrar');
        fetch(f.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: datos }).then(function (r) { return r.json(); }).then(function (r) {
            guardado.textContent = r.ok ? '✓ Guardado a las ' + r.hora : (r.msg || 'No se pudo guardar');
        }).catch(function () { cambios = true; guardado.textContent = 'Sin conexión: se intentará de nuevo'; });
    }
    setInterval(autoguardar, 60000);
    window.addEventListener('beforeunload', function (e) { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
    f.addEventListener('submit', function () { cambios = false; });

    // Cerrar y firmar
    document.getElementById('btnCerrar').addEventListener('click', function () {
        LP.confirmar({ titulo: '¿Cerrar y firmar la atención?', ok: 'Sí, cerrar y firmar',
            texto: 'Queda guardada en la historia clínica con su nombre y registro. Después no se puede modificar: solo se agregan notas aclaratorias.' })
            .then(function (ok) { if (ok) { document.getElementById('fCerrar').value = '1'; cambios = false; f.submit(); } });
    });
})();
</script>
@endpush
@endif
