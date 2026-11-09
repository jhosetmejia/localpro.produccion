@extends('layouts.app')

@section('title', 'LocalProCut en números')

@push('css')
<style>
.lp-kpis{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}
.cut-filtros{display:flex;gap:.4rem;flex-wrap:wrap;margin:.6rem 0 1rem}
.cut-filtros a{border:1px solid #dfe7e1;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.85rem;color:#151a17;text-decoration:none;background:#fff}
.cut-filtros a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.cut-sub{color:#5b665e;font-size:.78rem;font-weight:600}
.cut-dot{display:inline-block;width:9px;height:9px;border-radius:50%;background:#16b84e;margin-right:6px;box-shadow:0 0 0 0 rgba(22,184,78,.6);animation:cutLatido 1.6s infinite}
@keyframes cutLatido{70%{box-shadow:0 0 0 8px rgba(22,184,78,0)}100%{box-shadow:0 0 0 0 rgba(22,184,78,0)}}
.cut-barras{display:flex;align-items:flex-end;gap:3px;height:140px;padding-top:10px}
.cut-barras div{flex:1;background:linear-gradient(180deg,#a6f34a,#16b84e);border-radius:5px 5px 0 0;min-height:2px;position:relative}
.cut-barras div:hover::after{content:attr(data-t);position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:#0b0d0c;color:#fff;font-size:.7rem;padding:2px 6px;border-radius:6px;white-space:nowrap}
.cut-eje{display:flex;justify-content:space-between;font-size:.7rem;color:#5b665e;margin-top:4px}
.cut-lista{list-style:none;padding:0;margin:0}
.cut-lista li{display:flex;justify-content:space-between;gap:.6rem;padding:.35rem 0;border-bottom:1px solid #eef3ef;font-size:.88rem;font-weight:600}
.cut-lista li span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cut-lista .bar{height:6px;border-radius:9px;background:#e9f6ec;margin-top:3px}
.cut-lista .bar i{display:block;height:100%;border-radius:9px;background:#16b84e}
.cut-emb{display:flex;flex-direction:column;gap:.45rem}
.cut-emb div{display:flex;align-items:center;gap:.6rem;font-weight:700;font-size:.9rem}
.cut-emb b{flex:0 0 170px}
.cut-emb span{height:26px;border-radius:8px;background:linear-gradient(90deg,#0b0d0c,#16b84e);color:#fff;font-size:.8rem;display:flex;align-items:center;padding:0 .6rem;min-width:44px}
.cut-tabla td{vertical-align:top;font-size:.87rem}
.cut-ev{display:inline-block;background:#f2f7f3;border-radius:999px;padding:.1rem .55rem;margin:.1rem .15rem .1rem 0;font-size:.74rem;font-weight:700}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>LocalProCut <span>en números</span></h1>
            <span class="text-muted fw-semibold">Visitas al editor, quién lo tiene abierto ahora, desde dónde y en qué equipo, qué hace, y cuántos comparten, instalan la app y se registran.
                @if ($k['desde']) Contando desde el {{ \Illuminate\Support\Carbon::parse($k['desde'])->format('d/m/Y') }}.@endif</span></div>
        <div class="lp-quick"><a href="{{ route('cut') }}" target="_blank" rel="noopener" class="btn btn-dark"><i class="fa-solid fa-scissors me-1"></i>Abrir LocalProCut</a></div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="lbl">Visitas desde el inicio</div><div class="val" data-historico>{{ number_format($k['historico'], 0, ',', '.') }}</div><div class="sub">{{ number_format($k['personas_historico'], 0, ',', '.') }} personas distintas</div><div class="ic"><i class="fa-solid fa-eye"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Ahora mismo</div><div class="val" data-ahora>{{ $activos->count() }}</div><div class="sub"><span data-hoy>{{ $k['hoy'] }}</span> visitas hoy</div><div class="ic"><i class="fa-solid fa-signal"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Videos exportados</div><div class="val">{{ number_format($k['videos'], 0, ',', '.') }}</div><div class="sub">en {{ mb_strtolower($periodos[$periodo]) }}</div><div class="ic"><i class="fa-solid fa-film"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Compartieron</div><div class="val">{{ $k['compartidos'] }}</div><div class="sub">{{ $k['invitados'] }} llegaron por un amigo</div><div class="ic"><i class="fa-brands fa-whatsapp"></i></div></div>
        <div class="lp-kpi"><div class="lbl">App instalada</div><div class="val">{{ $k['instalaron'] }}</div><div class="sub">{{ $k['en_app'] }} visitas desde la app · {{ $k['clic_app'] }} tocaron descargar</div><div class="ic"><i class="fa-solid fa-mobile-screen"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Se registraron</div><div class="val">{{ $k['registros'] }}</div><div class="sub">negocios creados desde el editor</div><div class="ic"><i class="fa-solid fa-user-plus"></i></div></div>
    </div>

    <div class="cut-filtros">
        @foreach ($periodos as $p => $n)
        <a href="{{ route('admin.cut', ['periodo' => $p]) }}" class="{{ $periodo === $p ? 'on' : '' }}">{{ $n }}</a>
        @endforeach
        <span class="cut-sub align-self-center ms-2">{{ number_format($k['visitas'], 0, ',', '.') }} visitas · {{ number_format($k['personas'], 0, ',', '.') }} personas · {{ $k['vuelven'] }} volvieron · {{ $k['minutos'] }} min en promedio</span>
    </div>

    <div class="lp-box mb-3">
        <h3><span class="cut-dot"></span>Ahora mismo <small class="cut-sub">(se actualiza solo)</small></h3>
        <div class="table-responsive">
        <table class="table table-sm cut-tabla mb-0">
            <thead><tr><th>Persona</th><th>Dónde está (aprox.)</th><th>Equipo</th><th>Llegó desde</th><th>Qué está haciendo</th><th>Tiempo</th></tr></thead>
            <tbody data-activos>@include('admin.cut-activos', ['activos' => $activos])</tbody>
        </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="lp-box h-100">
                <h3><i class="fa-solid fa-chart-column me-1"></i>Visitas por día (últimos 30 días)</h3>
                @php $max = max(1, $serie->max()); @endphp
                <div class="cut-barras">
                    @foreach ($serie as $dia => $n)
                    <div style="height:{{ round($n / $max * 100) }}%" data-t="{{ \Illuminate\Support\Carbon::parse($dia)->format('d/m') }}: {{ $n }}"></div>
                    @endforeach
                </div>
                <div class="cut-eje"><span>{{ \Illuminate\Support\Carbon::parse($serie->keys()->first())->format('d/m') }}</span><span>Hoy</span></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="lp-box h-100">
                <h3><i class="fa-solid fa-filter me-1"></i>Del editor a cliente</h3>
                @php $tope = max(1, reset($embudo)); @endphp
                <div class="cut-emb">
                    @foreach ($embudo as $n => $v)
                    <div><b>{{ $n }}</b><span style="width:{{ max(8, round($v / $tope * 100)) }}%">{{ $v }}@if ($v && $tope && !$loop->first) · {{ round($v / $tope * 100) }}%@endif</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        @foreach ($grupos as $titulo => $filas)
        <div class="col-md-6 col-xl-3">
            <div class="lp-box h-100">
                <h3>{{ $titulo }}</h3>
                @php $m = max(1, $filas->max() ?? 1); @endphp
                <ul class="cut-lista">
                    @forelse ($filas as $k2 => $n)
                    <li><span title="{{ $k2 }}">{{ $k2 }}<div class="bar"><i style="width:{{ round($n / $m * 100) }}%"></i></div></span><span>{{ $n }}</span></li>
                    @empty
                    <li class="cut-sub">Sin datos aún</li>
                    @endforelse
                </ul>
            </div>
        </div>
        @endforeach
    </div>

    <div class="lp-box mt-3">
        <h3><i class="fa-solid fa-clock-rotate-left me-1"></i>Últimas visitas y lo que hizo cada persona</h3>
        <div class="table-responsive">
        <table class="table table-sm cut-tabla">
            <thead><tr><th>Cuándo</th><th>Dónde</th><th>Equipo</th><th>Llegó desde</th><th>Lo que hizo</th><th>Tiempo</th></tr></thead>
            <tbody>
            @forelse ($ultimas as $v)
            <tr>
                <td class="text-nowrap">{{ $v->created_at?->format('d/m h:i a') }}<div class="cut-sub">{{ $v->user?->name ?? 'Visitante ' . substr($v->visitante, 0, 5) }}{{ $v->nuevo ? '' : ' · volvió' }}</div></td>
                <td>{{ $v->geo_consultada ? (($v->ciudad ?: '?') . ', ' . ($v->pais ?: '?')) : 'Ubicando…' }}<div class="cut-sub">{{ $v->operador }}</div></td>
                <td>{{ $v->marca ?: $v->dispositivo }}<div class="cut-sub">{{ $v->sistema }} · {{ $v->navegador }}{{ $v->es_app ? ' · App' : '' }}</div></td>
                <td>{{ $v->origen ?: ($v->app_origen ?: ($v->referencia ?: 'Directo')) }}{{ $v->invito ? ' 🤝' : '' }}</td>
                <td>@foreach ($v->eventos->take(14) as $e)<span class="cut-ev" title="{{ $e->created_at?->format('h:i:s a') }}{{ $e->datos ? ' · ' . json_encode($e->datos, JSON_UNESCAPED_UNICODE) : '' }}">{{ \App\Http\Controllers\adminCutController::nombreEvento($e->tipo) }}</span>@endforeach</td>
                <td class="text-nowrap">{{ intdiv($v->segundos, 60) }} min</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted fw-semibold py-3">Todavía no hay visitas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <p class="cut-sub mb-0">La ubicación es aproximada (por la IP, como en ECO) y la IP se borra apenas se ubica. No se guarda ningún video ni nombre de archivo.</p>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var cuerpo = document.querySelector('[data-activos]');
    setInterval(function () {
        if (document.hidden) return;
        fetch({!! json_encode(route('admin.cut.vivo')) !!}, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                cuerpo.innerHTML = d.html;
                document.querySelector('[data-ahora]').textContent = cuerpo.querySelectorAll('.cut-dot').length;
                document.querySelector('[data-hoy]').textContent = d.hoy;
                document.querySelector('[data-historico]').textContent = Number(d.historico).toLocaleString('es-CO');
            }).catch(function () {});
    }, 15000);
})();
</script>
@endpush
