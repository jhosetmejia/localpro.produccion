@extends('layouts.app')

@section('title', 'Periodontograma · ' . $p->nombre_completo)

@push('css')
<style>
.pe-tabla{border-collapse:separate;border-spacing:0;font-size:.78rem;min-width:980px;width:100%}
.pe-tabla th,.pe-tabla td{text-align:center;padding:2px 1px;border-bottom:1px solid #eef5f7}
.pe-tabla th.l,.pe-tabla td.l{text-align:left;padding-left:6px;white-space:nowrap;font-weight:800;color:var(--lp-muted);position:sticky;left:0;background:#fff;z-index:1}
.pe-tabla .num{font-weight:900;color:#0e7490;font-size:.85rem}
.pe-tabla .medio{border-left:2px dashed #cbd5e1}
.pe-3{display:flex;gap:1px;justify-content:center}
.pe-3 input[type=text]{width:21px;height:24px;border:1.3px solid #d6eef3;border-radius:6px;text-align:center;font-weight:800;font-size:.8rem;padding:0}
.pe-3 input.p4{background:#fff7ed;border-color:#fdba74;color:#c2410c}
.pe-3 input.p6{background:#fff1f2;border-color:#fda4af;color:#be123c}
.pe-chk{display:flex;gap:1px;justify-content:center}
.pe-chk span{width:21px;height:16px;border-radius:5px;border:1.3px solid #e2e8f0;cursor:pointer;display:block}
.pe-chk span.s.on{background:#e11d48;border-color:#e11d48}
.pe-chk span.pl.on{background:#f59e0b;border-color:#f59e0b}
.pe-sel{width:46px;height:24px;border:1.3px solid #d6eef3;border-radius:6px;font-size:.75rem;padding:0 2px}
.pe-aus{cursor:pointer;color:#cbd5e1;font-size:.75rem;font-weight:900;padding:0 4px}
.pe-aus.on{color:#e11d48}
td.ausente{opacity:.25;pointer-events:none}
td.ausente .pe-aus{pointer-events:auto;opacity:1}
.pe-res{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:12px}
.pe-res div{background:#f5feff;border:1.5px solid #d6eef3;border-radius:16px;padding:.6rem .8rem}
.pe-res small{display:block;color:var(--lp-muted);font-weight:700;font-size:.72rem}.pe-res b{font-size:1.3rem}
@media (max-width:767px){.pe-res{grid-template-columns:repeat(2,1fr)}}
@media print{.sb-sidenav,#layoutSidenav_nav,.sb-topnav,.cs-tabs,.lp-dash .cs-btn,.pe-guardar{display:none!important}}
</style>
@endpush

@section('content')
@php
    $P = \App\Support\Odontologia::PERMANENTES;
    $res = $actual?->resumen ?? null;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.paciente', $p) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p->nombre_completo }}</a></small>
            <h1>Periodonto<span>grama</span></h1>
            <span class="text-muted fw-semibold">{{ $actual ? ($soloVer ? 'Versión del ' : 'Último: ') . $actual->created_at->format('d/m/Y h:i a') . ' (' . $actual->profesional_nombre . ')' : 'Primera evaluación periodontal' }}</span></div>
        <div class="d-flex gap-2"><a href="{{ route('odonto.odontograma', $p) }}" class="cs-btn"><i class="fa-solid fa-tooth"></i>Odontograma</a><button type="button" class="cs-btn" onclick="window.print()"><i class="fa-solid fa-print"></i>Imprimir</button></div>
    </div>
    @include('consultorio._nav')

    <div class="pe-res" id="pe-res">
        <div><small>Profundidad promedio</small><b id="r-prom">{{ $res['promedio'] ?? '—' }}</b> mm</div>
        <div><small>Sitios ≥ 4 mm</small><b id="r-b4" style="color:#c2410c">{{ $res['bolsas4'] ?? 0 }}</b></div>
        <div><small>Sitios ≥ 6 mm</small><b id="r-b6" style="color:#be123c">{{ $res['bolsas6'] ?? 0 }}</b></div>
        <div><small>Sangrado al sondaje</small><b id="r-s">{{ $res['sangrado'] ?? 0 }}</b> %</div>
        <div><small>Índice de placa</small><b id="r-pl">{{ $res['placa'] ?? 0 }}</b> %</div>
    </div>

    <form method="post" action="{{ route('odonto.perio.guardar', $p) }}" id="pe-form">
        @csrf
        <input type="hidden" name="datos" id="pe-datos">
        <div class="cs-card">
            <h3><span>Sondaje por pieza <small>3 sitios por cara (distal · medio · mesial) · mm</small></span></h3>
            @foreach (['sup' => ['Vestibular', 'Palatino'], 'inf' => ['Lingual', 'Vestibular']] as $arco => [$cara1, $cara2])
            <div class="table-responsive mb-3">
            <table class="pe-tabla" data-arco="{{ $arco }}">
                <tr><th class="l"></th>@foreach ($P[$arco] as $i => $n)<th class="num {{ $i === 8 ? 'medio' : '' }}">{{ $n }}<div><span class="pe-aus" data-aus="{{ $n }}" title="Marcar o quitar pieza ausente">✕</span></div></th>@endforeach</tr>
                <tr><td class="l">Movilidad</td>@foreach ($P[$arco] as $i => $n)<td class="{{ $i === 8 ? 'medio' : '' }}" data-p="{{ $n }}"><select class="pe-sel" data-mov="{{ $n }}">@for ($m = 0; $m <= 3; $m++)<option value="{{ $m }}">{{ $m ?: '–' }}</option>@endfor</select></td>@endforeach</tr>
                <tr><td class="l">Furca</td>@foreach ($P[$arco] as $i => $n)<td class="{{ $i === 8 ? 'medio' : '' }}" data-p="{{ $n }}"><select class="pe-sel" data-furca="{{ $n }}">@for ($m = 0; $m <= 3; $m++)<option value="{{ $m }}">{{ $m ? ['', 'I', 'II', 'III'][$m] : '–' }}</option>@endfor</select></td>@endforeach</tr>
                @foreach ([[$cara1, 0], [$cara2, 3]] as [$cara, $off])
                <tr><td class="l">{{ $cara }} · bolsa</td>@foreach ($P[$arco] as $i => $n)<td class="{{ $i === 8 ? 'medio' : '' }}" data-p="{{ $n }}"><div class="pe-3">@for ($s = 0; $s < 3; $s++)<input type="text" inputmode="numeric" maxlength="2" data-pz="{{ $n }}" data-s="{{ $off + $s }}">@endfor</div></td>@endforeach</tr>
                <tr><td class="l">{{ $cara }} · sangra / placa</td>@foreach ($P[$arco] as $i => $n)<td class="{{ $i === 8 ? 'medio' : '' }}" data-p="{{ $n }}"><div class="pe-chk">@for ($s = 0; $s < 3; $s++)<span class="s" data-pz="{{ $n }}" data-s="{{ $off + $s }}" title="Sangrado"></span>@endfor</div><div class="pe-chk mt-1">@for ($s = 0; $s < 3; $s++)<span class="pl" data-pz="{{ $n }}" data-s="{{ $off + $s }}" title="Placa"></span>@endfor</div></td>@endforeach</tr>
                @endforeach
            </table>
            </div>
            @endforeach
            <div class="d-flex gap-3 flex-wrap small fw-bold mb-2" style="color:var(--lp-muted)">
                <span><i class="fa-solid fa-square" style="color:#fdba74"></i> 4–5 mm</span><span><i class="fa-solid fa-square" style="color:#fda4af"></i> 6 mm o más</span>
                <span><i class="fa-solid fa-square" style="color:#e11d48"></i> Sangrado (fila de arriba)</span><span><i class="fa-solid fa-square" style="color:#f59e0b"></i> Placa (fila de abajo)</span>
            </div>
            <textarea name="notas" class="form-control mb-3" rows="2" maxlength="3000" placeholder="Diagnóstico periodontal y plan (ej. Periodontitis estadio II grado B; raspaje y alisado radicular por cuadrantes)" style="border-radius:14px" @disabled($soloVer)>{{ $actual?->notas }}</textarea>
            @unless ($soloVer)
            <div class="d-flex justify-content-between align-items-center pe-guardar flex-wrap gap-2">
                <small class="text-muted">Escriba un número y salta solo al siguiente sitio. Cada vez que guarda queda una versión nueva para comparar.</small>
                <button class="cs-btn prim"><i class="fa-solid fa-floppy-disk"></i>Guardar periodontograma</button>
            </div>
            @endunless
        </div>
    </form>
    @if ($versiones->count() > 1)
    <div class="cs-card mt-3">
        <h3>Versiones <small>compare la evolución</small></h3>
        @foreach ($versiones as $v)
        <a href="{{ route('odonto.perio', [$p, 'version' => $v->id]) }}" class="d-flex justify-content-between text-decoration-none py-2 border-bottom" style="color:inherit;{{ $actual?->id === $v->id ? 'font-weight:800' : '' }}">
            <span>{{ $v->created_at->format('d/m/Y') }} · {{ $v->profesional_nombre }}</span>
            <small class="text-muted">Prom. {{ $v->resumen['promedio'] ?? '—' }} mm · ≥4 mm: {{ $v->resumen['bolsas4'] ?? 0 }} · Sangrado {{ $v->resumen['sangrado'] ?? 0 }} %</small></a>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var D = {!! json_encode($actual?->datos ?: new \stdClass) !!}, solo = {{ $soloVer ? 'true' : 'false' }};
    var form = document.getElementById('pe-form');
    function pz(n) { return D[n] = D[n] || { p: [null, null, null, null, null, null], s: [0, 0, 0, 0, 0, 0], pl: [0, 0, 0, 0, 0, 0], mov: 0, furca: 0 }; }
    function color(i) { var v = parseInt(i.value, 10); i.classList.toggle('p4', v >= 4 && v < 6); i.classList.toggle('p6', v >= 6); }
    function ausente(n, on) { document.querySelectorAll('td[data-p="' + n + '"]').forEach(function (td) { td.classList.toggle('ausente', on); }); var a = document.querySelector('[data-aus="' + n + '"]'); if (a) a.classList.toggle('on', on); }
    // Cargar
    Object.keys(D).forEach(function (n) {
        var d = D[n];
        if (d.ausente) { ausente(n, true); return; }
        (d.p || []).forEach(function (v, s) { var i = document.querySelector('input[data-pz="' + n + '"][data-s="' + s + '"]'); if (i && v !== null) { i.value = v; color(i); } });
        (d.s || []).forEach(function (v, s) { var e = document.querySelector('.s[data-pz="' + n + '"][data-s="' + s + '"]'); if (e && v) e.classList.add('on'); });
        (d.pl || []).forEach(function (v, s) { var e = document.querySelector('.pl[data-pz="' + n + '"][data-s="' + s + '"]'); if (e && v) e.classList.add('on'); });
        var m = document.querySelector('[data-mov="' + n + '"]'); if (m) m.value = d.mov || 0;
        var f = document.querySelector('[data-furca="' + n + '"]'); if (f) f.value = d.furca || 0;
    });
    function resumen() {
        var prof = [], sitios = 0, sang = 0, pl = 0;
        Object.keys(D).forEach(function (n) { var d = D[n]; if (d.ausente) return; for (var s = 0; s < 6; s++) { if (d.p[s] !== null && d.p[s] !== undefined) prof.push(d.p[s]); sitios++; sang += d.s[s] ? 1 : 0; pl += d.pl[s] ? 1 : 0; } });
        document.getElementById('r-prom').textContent = prof.length ? (prof.reduce(function (a, b) { return a + b; }, 0) / prof.length).toFixed(1) : '—';
        document.getElementById('r-b4').textContent = prof.filter(function (x) { return x >= 4; }).length;
        document.getElementById('r-b6').textContent = prof.filter(function (x) { return x >= 6; }).length;
        document.getElementById('r-s').textContent = sitios ? Math.round(sang * 100 / sitios) : 0;
        document.getElementById('r-pl').textContent = sitios ? Math.round(pl * 100 / sitios) : 0;
    }
    if (solo) { document.querySelectorAll('#pe-form input, #pe-form select').forEach(function (x) { x.disabled = true; }); return; }
    var entradas = Array.prototype.slice.call(document.querySelectorAll('.pe-3 input'));
    entradas.forEach(function (i, k) {
        i.addEventListener('input', function () {
            i.value = i.value.replace(/\D/g, '').slice(0, 2);
            var v = i.value === '' ? null : Math.min(15, parseInt(i.value, 10));
            pz(i.dataset.pz).p[+i.dataset.s] = v; color(i); resumen();
            if (i.value.length >= 1 && !(i.value === '1')) { var sig = entradas[k + 1]; if (sig) sig.focus(); }
        });
    });
    document.querySelectorAll('.pe-chk span').forEach(function (e) {
        e.addEventListener('click', function () { e.classList.toggle('on'); pz(e.dataset.pz)[e.classList.contains('s') ? 's' : 'pl'][+e.dataset.s] = e.classList.contains('on') ? 1 : 0; resumen(); });
    });
    document.querySelectorAll('[data-mov]').forEach(function (s) { s.addEventListener('change', function () { pz(s.dataset.mov).mov = +s.value; }); });
    document.querySelectorAll('[data-furca]').forEach(function (s) { s.addEventListener('change', function () { pz(s.dataset.furca).furca = +s.value; }); });
    document.querySelectorAll('[data-aus]').forEach(function (a) {
        a.addEventListener('click', function () { var n = a.dataset.aus, on = !(D[n] && D[n].ausente); if (on) D[n] = { ausente: true }; else delete D[n]; ausente(n, on); resumen(); });
    });
    form.addEventListener('submit', function () { document.getElementById('pe-datos').value = JSON.stringify(D); });
})();
</script>
@endpush
