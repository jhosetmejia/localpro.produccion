@extends('layouts.app')

@section('title', 'Visitas y uso')

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
<style>
.vs-barras div.c{background:linear-gradient(180deg,#7a3dff,#3a1f8a)}
.vs-leyenda{display:flex;gap:1rem;font-size:.75rem;font-weight:700;color:#5b665e;margin-top:6px}
.vs-leyenda i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:4px;vertical-align:-1px}
.cut-barras .par{flex:1;display:flex;flex-direction:column;justify-content:flex-end;height:100%;background:none;gap:1px}
.cut-barras .par>div{flex:none;width:100%}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Visitas <span>y uso</span></h1>
            <span class="text-muted fw-semibold">Quién está en LocalProo ahora, desde dónde y en qué equipo, qué secciones usan, qué negocios trabajan más y por dónde llega la gente.
                @if ($k['desde']) Contando desde el {{ \Illuminate\Support\Carbon::parse($k['desde'])->format('d/m/Y') }}.@endif</span></div>
    </div>
    @include('admin._visitas-tabs')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="lbl">Ahora mismo</div><div class="val" data-ahora>{{ $activos->count() }}</div><div class="sub"><span data-hoy>{{ $k['hoy'] }}</span> visitas hoy · {{ number_format($k['historico'], 0, ',', '.') }} desde el inicio</div><div class="ic"><i class="fa-solid fa-signal"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Visitas</div><div class="val">{{ number_format($k['visitas'], 0, ',', '.') }}</div><div class="sub">{{ number_format($k['personas'], 0, ',', '.') }} personas · {{ $k['nuevos'] }} nuevas</div><div class="ic"><i class="fa-solid fa-eye"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Páginas vistas</div><div class="val">{{ number_format($k['paginas'], 0, ',', '.') }}</div><div class="sub">{{ $k['visitas'] ? round($k['paginas'] / $k['visitas'], 1) : 0 }} por visita</div><div class="ic"><i class="fa-solid fa-file-lines"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Usuarios con sesión</div><div class="val">{{ $k['con_sesion'] }}</div><div class="sub">de {{ $k['negocios'] }} negocios</div><div class="ic"><i class="fa-solid fa-user-check"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Desde el celular</div><div class="val">{{ $k['visitas'] ? round($k['celular'] / $k['visitas'] * 100) : 0 }}%</div><div class="sub">{{ $k['app'] }} visitas desde la app</div><div class="ic"><i class="fa-solid fa-mobile-screen"></i></div></div>
    </div>

    <div class="cut-filtros">
        @foreach ($periodos as $p => $n)
        <a href="{{ route('admin.sitio', ['periodo' => $p]) }}" class="{{ $periodo === $p ? 'on' : '' }}">{{ $n }}</a>
        @endforeach
    </div>

    <div class="lp-box mb-3">
        <h3><span class="cut-dot"></span>Ahora mismo <small class="cut-sub">(últimos 5 minutos · se actualiza solo)</small></h3>
        <div class="table-responsive">
        <table class="table table-sm cut-tabla mb-0">
            <thead><tr><th>Persona</th><th>Dónde está (aprox.)</th><th>Equipo</th><th>Qué está viendo</th><th>Llegó desde</th><th>Recorrido</th></tr></thead>
            <tbody data-activos>@include('admin.sitio-activos', ['activos' => $activos])</tbody>
        </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="lp-box h-100">
                <h3><i class="fa-solid fa-chart-column me-1"></i>Visitas por día (últimos 30 días)</h3>
                @php $max = max(1, $serie->max('n')); @endphp
                <div class="cut-barras">
                    @foreach ($serie as $dia => $x)
                    <div class="par" title="{{ \Illuminate\Support\Carbon::parse($dia)->format('d/m') }}: {{ $x['n'] }} visitas, {{ $x['c'] }} con sesión">
                        <div style="height:{{ round(($x['n'] - $x['c']) / $max * 100) }}%;background:linear-gradient(180deg,#a6f34a,#16b84e);border-radius:5px 5px 0 0"></div>
                        <div class="c" style="height:{{ round($x['c'] / $max * 100) }}%;background:linear-gradient(180deg,#7a3dff,#3a1f8a)"></div>
                    </div>
                    @endforeach
                </div>
                <div class="cut-eje"><span>{{ \Illuminate\Support\Carbon::parse($serie->keys()->first())->format('d/m') }}</span><span>Hoy</span></div>
                <div class="vs-leyenda"><span><i style="background:#16b84e"></i>Visitantes (sin sesión)</span><span><i style="background:#7a3dff"></i>Usuarios con sesión</span></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="lp-box h-100">
                <h3><i class="fa-solid fa-layer-group me-1"></i>Secciones más usadas</h3>
                @php $m = max(1, $secciones->max() ?? 1); @endphp
                <ul class="cut-lista">
                    @forelse ($secciones as $s => $n)
                    <li><span>{{ $s }}<div class="bar"><i style="width:{{ round($n / $m * 100) }}%"></i></div></span><span>{{ number_format($n, 0, ',', '.') }}</span></li>
                    @empty
                    <li class="cut-sub">Sin datos aún</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="lp-box mt-3">
        <h3><i class="fa-solid fa-store me-1"></i>Negocios más activos</h3>
        <div class="table-responsive">
        <table class="table table-sm cut-tabla mb-0">
            <thead><tr><th>Negocio</th><th class="text-end">Visitas</th><th class="text-end">Páginas</th><th>Última vez</th><th></th></tr></thead>
            <tbody>
            @forelse ($negocios as $n)
            <tr>
                <td><b>{{ $nombres[$n->tienda_id] ?? 'Negocio #' . $n->tienda_id }}</b></td>
                <td class="text-end">{{ $n->visitas }}</td>
                <td class="text-end fw-bold">{{ number_format($n->paginas, 0, ',', '.') }}</td>
                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($n->ultima)->diffForHumans() }}</td>
                <td><a class="btn btn-sm btn-outline-dark" href="{{ route('admin.tiendas.show', $n->tienda_id) }}">Ver</a></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted fw-semibold py-3">Sin datos aún.</td></tr>
            @endforelse
            </tbody>
        </table>
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
        <h3><i class="fa-solid fa-route me-1"></i>Últimas visitas y su recorrido</h3>
        <div class="table-responsive">
        <table class="table table-sm cut-tabla">
            <thead><tr><th>Cuándo</th><th>Quién</th><th>Dónde</th><th>Equipo</th><th>Llegó desde</th><th>Recorrido</th></tr></thead>
            <tbody>
            @forelse ($ultimas as $v)
            <tr>
                <td class="text-nowrap">{{ $v->created_at?->format('d/m h:i a') }}<div class="cut-sub">{{ $v->paginas }} pág. · {{ $v->ultima_vez && $v->created_at ? $v->created_at->diffForHumans($v->ultima_vez, true) : '' }}</div></td>
                <td>{{ $v->user?->name ?? 'Visitante ' . substr($v->visitante, 0, 5) }}<div class="cut-sub">{{ $v->tienda?->nombre }}{{ $v->nuevo ? '' : ' · volvió' }}</div></td>
                <td>{{ $v->geo_consultada ? (($v->ciudad ?: '?') . ', ' . ($v->pais ?: '?')) : 'Ubicando…' }}<div class="cut-sub">{{ $v->operador }}</div></td>
                <td>{{ $v->marca ?: $v->dispositivo }}<div class="cut-sub">{{ $v->sistema }} · {{ $v->navegador }}{{ $v->es_app ? ' · App' : '' }}</div></td>
                <td>{{ $v->origen ?: ($v->app_origen ?: ($v->referencia ?: 'Directo')) }}</td>
                <td>@foreach ($v->paginasVistas->take(16) as $pg)<span class="cut-ev" title="{{ $pg->created_at?->format('h:i:s a') }} · {{ $pg->pagina }}">{{ \App\Models\VisitaSitio::seccion($pg->ruta, $pg->pagina) }}</span>@endforeach{{ $v->paginasVistas->count() > 16 ? ' …' : '' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted fw-semibold py-3">Todavía no hay visitas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <p class="cut-sub mb-0">Ubicación aproximada por la IP (la IP se borra apenas se ubica). El recorrido se guarda 60 días.</p>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var cuerpo = document.querySelector('[data-activos]');
    setInterval(function () {
        if (document.hidden) return;
        fetch({!! json_encode(route('admin.sitio.vivo')) !!}, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                cuerpo.innerHTML = d.html;
                document.querySelector('[data-ahora]').textContent = d.ahora;
                document.querySelector('[data-hoy]').textContent = d.hoy;
            }).catch(function () {});
    }, 15000);
})();
</script>
@endpush
