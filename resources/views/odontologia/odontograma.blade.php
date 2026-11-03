@extends('layouts.app')

@section('title', 'Odontograma · ' . $p->nombre_completo)

@push('css')
<style>
.odo-wrap{display:grid;grid-template-columns:1fr 290px;gap:14px;align-items:start}
.odo-wrap>*{min-width:0}
.odo-wrap .cs-card h3{flex-wrap:wrap}
.odo-lienzo{overflow-x:auto;padding:.4rem 0;-webkit-overflow-scrolling:touch}
.odo-svg{width:100%;min-width:640px;display:block;user-select:none}
.odo-cara{stroke:#94a3b8;stroke-width:1.1;cursor:pointer;transition:fill .15s}
.odo-pieza:hover .odo-cara{stroke:#0891b2}
.odo-pieza.elegida .odo-cara{stroke:#0e7490;stroke-width:2}
.odo-pieza.tenue .odo-cara{fill-opacity:.35}
.odo-num{font:800 11px system-ui,sans-serif;fill:#475569}.odo-num.on{fill:#0e7490;font-size:13px}
.odo-sim{font:900 9px system-ui,sans-serif;fill:#fff}
.odo-medio{stroke:#cbd5e1;stroke-dasharray:3 4}
.odo-tools{display:flex;flex-wrap:wrap;gap:6px}
.odo-tool{display:flex;align-items:center;gap:.4rem;border:1.5px solid var(--lp-line);background:#fff;border-radius:999px;padding:.35rem .7rem;font-weight:800;font-size:.8rem;cursor:pointer;color:var(--lp-ink)}
.odo-tool i.c{width:13px;height:13px;border-radius:4px;display:inline-block}
.odo-tool.on{border-color:#0e7490;background:#0e7490;color:#fff}
.odo-tool small{font-weight:700;opacity:.65}
.odo-grupo{font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:var(--lp-muted);margin:.7rem 0 .35rem}
.odo-res{display:flex;gap:.6rem;align-items:flex-start;padding:.4rem 0;border-bottom:1px dashed #e2eef1;font-size:.85rem}
.odo-res b{flex:none;width:30px;height:24px;border-radius:8px;background:#ecfeff;color:#0e7490;display:grid;place-items:center;font-size:.8rem}
.odo-res span{display:flex;flex-wrap:wrap;gap:4px}
.odo-res i{font-style:normal;color:#fff;border-radius:999px;padding:.1rem .55rem;font-weight:700;font-size:.75rem}
.odo-vacio{color:var(--lp-muted);font-size:.88rem}
.odo-ver{display:flex;justify-content:space-between;gap:.5rem;padding:.45rem .2rem;border-bottom:1px solid #eef3ef;font-size:.85rem;text-decoration:none;color:inherit}
.odo-ver.on{background:#ecfeff;border-radius:10px;font-weight:800}
.odo-pend{display:flex;justify-content:space-between;gap:.5rem;font-size:.85rem;padding:.3rem 0;border-bottom:1px dashed #fde2e7}
@media print{.sb-sidenav,#layoutSidenav_nav,.sb-topnav,.cs-tabs,.odo-tools,.odo-grupo,.lp-dash button,.lp-dash .cs-btn,.odo-tip,#odo-sel{display:none!important}#layoutSidenav_content{padding:0!important;margin:0!important}.odo-wrap{grid-template-columns:1fr}}
.odo-tip{background:#f0fdfa;border:1.5px dashed #99f6e4;border-radius:16px;padding:.6rem .8rem;font-size:.82rem;color:#115e59}
@media (max-width:991px){.odo-wrap{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php $H = \App\Support\Odontologia::HALLAZGOS; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.paciente', $p) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p->nombre_completo }}</a></small>
            <h1>Odonto<span>grama</span></h1>
            <span class="text-muted fw-semibold">{{ trim($p->tipo_documento . ' ' . $p->documento) }}{{ $p->edad ? ' · ' . $p->edad : '' }}
                @if ($actual) · {{ $soloVer ? 'Versión del' : 'Última versión:' }} {{ $actual->created_at->format('d/m/Y h:i a') }} ({{ $actual->profesional_nombre }})@endif</span></div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($atencion)<a href="{{ route('consultorio.atencion', $atencion) }}" class="cs-btn"><i class="fa-solid fa-notes-medical"></i>Volver a la atención</a>@endif
            <button type="button" class="cs-btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimir</button>
        </div>
    </div>
    @include('consultorio._nav')

    @if ($p->alergias)<div class="cs-alerta mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i>Alergias: {{ $p->alergias }}</div>@endif
    @if ($soloVer)
    <div class="cs-alerta mb-3" style="background:#fff8e1;border-color:#ffe08a;color:#7a5a00"><i class="fa-solid fa-clock-rotate-left me-1"></i>Está viendo una versión anterior (solo lectura).
        <a href="{{ route('odonto.odontograma', $p) }}" class="fw-bold">Ver la actual</a></div>
    @endif

    <form method="post" action="{{ route('odonto.odontograma.guardar', $p) }}" id="odo-form">
        @csrf
        <input type="hidden" name="piezas" id="odo-piezas" value="{{ json_encode($actual?->piezas ?? new \stdClass) }}">
        <input type="hidden" name="atencion_id" value="{{ $atencion?->id }}">
        <div class="odo-wrap">
            <div class="cs-card">
                <h3>
                    <span><i class="fa-solid fa-tooth me-1" style="color:#0891b2"></i>Piezas dentales <small>toque una herramienta y luego la cara o la pieza</small></span>
                    <select name="denticion" id="odo-den" data-nativo class="form-select form-select-sm" style="width:auto;border-radius:999px;font-weight:800" @disabled($soloVer)>
                        @foreach (\App\Support\Odontologia::DENTICIONES as $k => $txt)<option value="{{ $k }}" @selected($denticion === $k)>{{ $txt }}</option>@endforeach
                    </select>
                </h3>
                @unless ($soloVer)
                <div class="odo-grupo">Por caras (toque la cara: vestibular, lingual, mesial, distal u oclusal)</div>
                <div class="odo-tools">
                    @foreach ($H as $k => [$n, $c, $alc])@if ($alc === 'cara')<button type="button" class="odo-tool" data-h="{{ $k }}"><i class="c" style="background:{{ $c }}"></i>{{ $n }}</button>@endif @endforeach
                </div>
                <div class="odo-grupo">Toda la pieza</div>
                <div class="odo-tools">
                    @foreach ($H as $k => [$n, $c, $alc, $proc, $sim])@if ($alc === 'pieza')<button type="button" class="odo-tool" data-h="{{ $k }}"><i class="c" style="background:{{ $c }}"></i>{{ $n }} <small>{{ $sim }}</small></button>@endif @endforeach
                    <button type="button" class="odo-tool" data-h="borrar"><i class="fa-solid fa-eraser"></i>Borrar</button>
                </div>
                @endunless
                <div class="odo-lienzo mt-2" id="odo-lienzo"></div>
                <div class="d-flex gap-3 flex-wrap small fw-bold mt-1" style="color:var(--lp-muted)">
                    <span><i class="fa-solid fa-square" style="color:#e11d48"></i> Rojo: por tratar</span>
                    <span><i class="fa-solid fa-square" style="color:#2563eb"></i> Azul: hecho / existente</span>
                    <span><i class="fa-solid fa-circle" style="color:#f59e0b"></i> Pieza con nota</span>
                </div>
                <div class="mt-3">
                    <label class="small fw-bold" style="color:var(--lp-muted)">Observaciones generales</label>
                    <textarea name="notas" rows="2" class="form-control" style="border-radius:14px" maxlength="3000" placeholder="Ej. Higiene regular, bruxismo, recesiones en sector anterior inferior" @disabled($soloVer)>{{ $actual?->notas }}</textarea>
                </div>
                @unless ($soloVer)
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
                    <div class="cs-privado"><i class="fa-solid fa-lock"></i>Cada vez que guarda queda una versión nueva con fecha y profesional.</div>
                    <button class="cs-btn prim"><i class="fa-solid fa-floppy-disk"></i>Guardar odontograma</button>
                </div>
                @endunless
            </div>

            <div class="d-flex flex-column gap-3">
                @unless ($soloVer)
                <div class="cs-card" id="odo-sel" style="display:none">
                    <h3>Pieza <span id="odo-sel-num" style="color:#0e7490"></span></h3>
                    <label class="small fw-bold" style="color:var(--lp-muted)">Nota de la pieza</label>
                    <input class="form-control" id="odo-nota" maxlength="160" placeholder="Ej. Sensibilidad al frío" style="border-radius:12px">
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="cs-btn sm" id="odo-nota-ok"><i class="fa-solid fa-check"></i>Poner nota</button>
                        <button type="button" class="cs-btn sm" id="odo-limpiar"><i class="fa-solid fa-rotate-left"></i>Dejar sana</button>
                    </div>
                </div>
                @endunless
                <div class="cs-card">
                    <h3>Hallazgos</h3>
                    <div id="odo-leyenda"></div>
                </div>
                @if ($actual && $pendientes)
                <div class="cs-card">
                    <h3>Por hacer <small>{{ count($pendientes) }}</small></h3>
                    @foreach ($pendientes as $x)
                    <div class="odo-pend"><span><b>{{ $x['pieza'] }}</b>{{ $x['caras'] ? ' (' . $x['caras'] . ')' : '' }}</span><span>{{ $x['procedimiento'] }}{{ ($x['cantidad'] ?? 1) > 1 ? ' x' . $x['cantidad'] : '' }}</span></div>
                    @endforeach
                    <button type="submit" form="odo-plan" class="cs-btn dark w-100 justify-content-center mt-3"><i class="fa-solid fa-file-invoice-dollar"></i>Pasar al plan de tratamiento</button>
                    <div class="small text-muted mt-2">Arma el presupuesto con los precios de sus servicios. Antes guarde los cambios del odontograma.</div>
                </div>
                @endif
                @if ($versiones->count())
                <div class="cs-card">
                    <h3>Versiones <small>{{ $versiones->count() }}</small></h3>
                    @foreach ($versiones->take(12) as $v)
                    <a href="{{ route('odonto.odontograma', [$p, 'version' => $v->id]) }}" class="odo-ver {{ $actual?->id === $v->id ? 'on' : '' }}"><span>{{ $v->created_at->format('d/m/Y h:i a') }}</span><small class="text-muted">{{ \Illuminate\Support\Str::limit($v->profesional_nombre, 18) }}</small></a>
                    @endforeach
                </div>
                @endif
                <div class="odo-tip"><i class="fa-solid fa-lightbulb me-1"></i>Sin herramienta elegida, tocar una pieza solo la selecciona para ponerle una nota. Tocar dos veces la misma cara con la misma herramienta la quita.</div>
            </div>
        </div>
    </form>
    @if ($actual && $pendientes)
    <form method="post" action="{{ route('odonto.odontograma.plan', $actual) }}" id="odo-plan">@csrf</form>
    @endif
</div>
@endsection

@push('js')
<script src="{{ asset('js/odontograma.js') }}?v=1"></script>
<script>
(function () {
    var input = document.getElementById('odo-piezas'), sel = document.getElementById('odo-sel'), nota = document.getElementById('odo-nota'), elegida = null, cambios = false;
    var od = Odonto.montar(document.getElementById('odo-lienzo'), {
        piezas: JSON.parse(input.value || '{}'), denticion: document.getElementById('odo-den').value,
        hallazgos: {!! json_encode($H) !!}, soloVer: {{ $soloVer ? 'true' : 'false' }}, leyenda: document.getElementById('odo-leyenda'),
        alCambiar: function (d) { input.value = JSON.stringify(d); cambios = true; },
        alElegir: function (num, d) { elegida = num; if (!sel) return; sel.style.display = ''; document.getElementById('odo-sel-num').textContent = num; nota.value = d.nota || ''; }
    });
    document.getElementById('odo-den').addEventListener('change', function () { od.denticion(this.value); cambios = true; });
    document.querySelectorAll('.odo-tool').forEach(function (b) {
        b.addEventListener('click', function () {
            var on = !b.classList.contains('on');
            document.querySelectorAll('.odo-tool').forEach(function (x) { x.classList.remove('on'); });
            if (on) b.classList.add('on');
            od.herramienta(on ? b.dataset.h : null);
        });
    });
    if (sel) {
        document.getElementById('odo-nota-ok').addEventListener('click', function () { if (elegida) od.nota(elegida, nota.value); });
        nota.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); if (elegida) od.nota(elegida, nota.value); } });
        document.getElementById('odo-limpiar').addEventListener('click', function () { if (elegida) { od.limpiarPieza(elegida); nota.value = ''; } });
    }
    var form = document.getElementById('odo-form');
    form.addEventListener('submit', function () { cambios = false; });
    var plan = document.getElementById('odo-plan');
    if (plan) plan.addEventListener('submit', function (e) {
        if (cambios) { e.preventDefault(); if (window.LP) LP.toast('Primero toque "Guardar odontograma" para guardar los cambios.', 'aviso'); form.scrollIntoView({ behavior: 'smooth' }); }
    });
})();
</script>
@endpush
