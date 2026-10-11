{{--
    Mapa de la red en burbujas (multinivel). Se abre con un botón "Ver mi red" (cualquier elemento con data-ver-red).
    Tres vistas: Todo · Asesores · Negocios. Las personas son bolitas y los negocios cuadritos.
    Uso: @include('red._mapa', ['raices' => .., 'hijos' => .., 'negocios' => .., 'admin' => bool, 'raiz' => 'LocalPro' | null])
--}}
@php
    $nodos = [];
    // Personas de este mapa (para metas y negocios pagados en pocas consultas)
    $todos = collect();
    $juntar = function ($n) use (&$juntar, &$todos, $hijos) {
        if ($todos->has($n->id)) return;
        $todos->put($n->id, $n);
        foreach ($hijos[$n->id] ?? [] as $h) $juntar($h);
    };
    foreach ($raices as $r) $juntar($r);
    $ids = $todos->keys()->all();
    $pagados = \App\Support\RedComercial::negociosPagados($ids);
    $reclutas = \App\Support\RedComercial::reclutadosActivos($ids);
    $metas = \App\Support\RedComercial::metas();
    $tiendaIds = collect($negocios)->flatten()->pluck('id')->all();
    $tiendasPagas = $tiendaIds ? \Illuminate\Support\Facades\DB::table('pagos_suscripcion as p')->join('planes as pl', 'pl.id', '=', 'p.plan_id')
        ->whereIn('p.tienda_id', $tiendaIds)->where('p.estado', 'aprobado')->where('p.monto', '>', 0)->whereBetween('pl.dias', [360, 400])
        ->distinct()->pluck('p.tienda_id')->flip()->all() : [];

    $estadoPlan = function ($t) use ($tiendasPagas) {
        if ($t->plan_vence && $t->plan_vence->isPast()) return 'vencido';
        if (!isset($tiendasPagas[$t->id])) return 'prueba';
        return $t->plan_vence && now()->diffInDays($t->plan_vence) <= 30 ? 'pronto' : 'ok';
    };
    foreach ($todos as $n) {
        $equipo = $hijos[$n->id] ?? collect();
        $sig = \App\Support\RedComercial::siguiente($n->cargo);
        $nodos[$n->id] = [
            'id' => $n->id,
            'n' => $n->nombre,
            'cod' => $n->codigo,
            'c' => $n->cargo_nombre,
            'k' => $n->cargo,
            'ic' => $n->cargo_icono,
            'z' => trim(implode(' · ', array_filter([$n->zona, $n->ciudad ?: $n->departamento]))),
            'on' => (bool) $n->activo,
            'pe' => $n->estado === 'pendiente',
            'u' => !empty($admin) ? route('admin.red.show', $n) : null,
            'mn' => $pagados[$n->id] ?? 0,
            'ma' => $reclutas[$n->id] ?? 0,
            'sig' => $sig ? \App\Models\Asesor::CARGOS[$sig][0] : null,
            'neg' => ($negocios[$n->id] ?? collect())->map(fn ($t) => [
                'n' => $t->nombre,
                'cod' => $t->codigo ?? '',
                'e' => $estadoPlan($t),
                'p' => ($t->plan->nombre ?? 'Sin plan') . ($t->plan_vence ? ' · ' . ($t->plan_vence->isPast() ? 'venció ' : 'vence ') . $t->plan_vence->format('d/m/Y') : ''),
                'mes' => $t->plan_vence && !$t->plan_vence->isPast() && $t->plan_vence->lte(now()->endOfMonth()),
                'u' => !empty($admin) ? route('admin.tiendas.show', $t) : null,
            ])->values()->all(),
            'h' => $equipo->pluck('id')->values()->all(),
        ];
    }
    $raizId = $raices->count() === 1 && empty($raiz) ? $raices->first()->id : 0;
    if ($raizId === 0) {
        $nodos[0] = ['id' => 0, 'n' => $raiz ?: 'Mi red', 'cod' => '', 'c' => 'Toda la red', 'k' => 'raiz', 'ic' => 'fa-crown', 'z' => '', 'on' => true, 'pe' => false, 'u' => null, 'mn' => 0, 'ma' => 0, 'sig' => null, 'neg' => [], 'h' => $raices->pluck('id')->values()->all()];
    }
@endphp

<div class="mr-ov" id="mrOv" aria-hidden="true">
    <div class="mr-win" role="dialog" aria-modal="true" aria-label="Mapa de la red">
        <div class="mr-top">
            <div class="mr-tit"><i class="fa-solid fa-circle-nodes"></i> Mapa de la red</div>
            <div class="mr-modo" id="mrModo" role="tablist">
                <button type="button" data-modo="todo" class="on">Todo</button>
                <button type="button" data-modo="asesores"><i class="fa-solid fa-users"></i> Asesores</button>
                <button type="button" data-modo="negocios"><i class="fa-solid fa-store"></i> Negocios</button>
            </div>
            <button type="button" class="mr-x" data-mr-cerrar aria-label="Cerrar">×</button>
        </div>
        <div class="mr-migas" id="mrMigas"></div>
        <div class="mr-lienzo" id="mrLienzo">
            <svg class="mr-lineas" id="mrLineas" aria-hidden="true"></svg>
            <div class="mr-foco" id="mrFoco"></div>
            <div class="mr-res" id="mrRes"></div>
            <div class="mr-sec" id="mrSecEq"><span>Su equipo</span><small>Toque una burbuja para ver a quiénes metió y qué negocios tiene</small></div>
            <div class="mr-eq" id="mrEq"></div>
            <div class="mr-sec" id="mrSecNeg"><span id="mrNegTit">Sus negocios</span></div>
            <div class="mr-neg" id="mrNeg"></div>
            <div class="mr-ramas" id="mrRamas"></div>
        </div>
        <div class="mr-ley">
            <span style="--c:#7c3aed">País</span><span style="--c:#2563eb">Departamento</span><span style="--c:#0891b2">Ciudad</span><span style="--c:#f59e0b">Zona</span><span style="--c:#16b34a">Asesor</span>
            <span class="neg" style="--c:#16b34a">Pagó · al día</span><span class="neg" style="--c:#f2b705">Vence pronto</span><span class="neg" style="--c:#e0245e">Vencido</span><span class="neg" style="--c:#9aa8a0">Prueba / gratis</span>
            <span class="ani">Anillo = meta para subir</span>
        </div>
    </div>
</div>

@once
<style>
.mr-btn{display:inline-flex;align-items:center;gap:.5rem;border:0;border-radius:999px;padding:.7rem 1.2rem;font-weight:800;background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;box-shadow:0 10px 24px -12px rgba(22,184,78,.8);cursor:pointer;white-space:nowrap}
.mr-btn:hover{filter:brightness(1.05)}
.mr-btn .mr-pulso{width:10px;height:10px;border-radius:50%;background:#0b0d0c;box-shadow:0 0 0 0 rgba(11,13,12,.5);animation:mrPul 1.8s infinite}
@keyframes mrPul{70%{box-shadow:0 0 0 8px rgba(11,13,12,0)}100%{box-shadow:0 0 0 0 rgba(11,13,12,0)}}
.mr-ov{position:fixed;inset:0;z-index:1990;background:rgba(11,13,12,.6);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.mr-ov.on{opacity:1;pointer-events:auto}
.mr-win{background:radial-gradient(1200px 500px at 50% -10%,#effbf1 0%,#fff 55%);width:min(1100px,100%);height:min(880px,100%);border-radius:28px;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 40px 100px -30px rgba(0,0,0,.6);transform:translateY(16px) scale(.98);transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.mr-ov.on .mr-win{transform:none}
.mr-top{display:flex;align-items:center;gap:10px;padding:14px 18px 6px;flex-wrap:wrap}
.mr-tit{font-weight:900;font-size:1.15rem;margin-right:auto}.mr-tit i{color:#16b34a;margin-right:6px}
.mr-modo{display:flex;background:#f1f5f2;border-radius:999px;padding:4px;gap:2px;order:3;width:100%;justify-content:center}
@media (min-width:640px){.mr-modo{order:0;width:auto}}
.mr-modo button{border:0;background:none;border-radius:999px;padding:.42rem .9rem;font-weight:800;font-size:.84rem;color:#5b665e;white-space:nowrap;flex:1}
.mr-modo button.on{background:#0b0d0c;color:#fff;box-shadow:0 6px 14px -8px rgba(0,0,0,.6)}
.mr-x{border:0;background:#f1f5f2;width:40px;height:40px;border-radius:14px;font-size:1.5rem;font-weight:700;line-height:1;color:#33403a}
.mr-migas{display:flex;gap:6px;align-items:center;overflow-x:auto;padding:4px 18px 8px;scrollbar-width:none}
.mr-migas button{border:1.5px solid #dfe6e2;background:#fff;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.82rem;white-space:nowrap;color:#33403a}
.mr-migas button:last-child{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.mr-migas i{color:#b3bab6;font-size:.7rem}
.mr-lienzo{position:relative;flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 16px 28px;display:flex;flex-direction:column;align-items:center}
.mr-lineas{position:absolute;left:0;top:0;width:100%;pointer-events:none;overflow:visible}
.mr-lineas path{fill:none;stroke:#bfe6c9;stroke-width:2;stroke-dasharray:4 5;animation:mrDash 1.2s linear infinite}
.mr-lineas path.neg{stroke:#e6e0bf;stroke-dasharray:2 4}
@keyframes mrDash{to{stroke-dashoffset:-18}}
.mr-foco{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;text-align:center;margin:6px 0 4px;animation:mrIn .35s cubic-bezier(.2,.8,.2,1)}
.mr-foco h3{font-weight:900;font-size:1.25rem;margin:14px 0 2px}
.mr-foco .mr-c{font-weight:700;color:#5b665e;font-size:.9rem}
.mr-cod{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800;font-size:.72rem;color:#11702f;background:#eaf8ee;border-radius:6px;padding:.05rem .4rem}
.mr-stats{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;justify-content:center}
.mr-stat{background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;padding:.45rem .8rem;text-align:left;min-width:150px}
.mr-stat small{display:block;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#7a8580}
.mr-stat b{font-size:1rem;font-weight:900}
.mr-stat span{color:#6b7570;font-weight:700;font-size:.8rem}
.mr-stat i{color:#16b34a;margin-right:4px}
.mr-meta{margin-top:10px;font-size:.8rem;font-weight:700;color:#5b665e;background:#fff;border:1.5px dashed #cfe9d6;border-radius:12px;padding:.35rem .75rem}
.mr-meta b{color:#11702f}
.mr-foco a.mr-ficha{margin-top:8px;font-weight:700;font-size:.85rem;color:#11702f}
/* Bolita con anillo de meta */
.mr-anillo{--p:0;--cc:#16b34a;position:relative;border-radius:50%;padding:5px;background:conic-gradient(var(--cc) calc(var(--p) * 1%), #e6ece8 0);flex:none}
.mr-anillo.sin{background:none;padding:0}
.mr-bola{--cc:#16b34a;position:relative;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:900;background:radial-gradient(circle at 30% 25%,rgba(255,255,255,.45),transparent 45%),var(--cc);box-shadow:0 0 0 4px #fff;flex:none}
.mr-bola.off{filter:grayscale(.85);opacity:.7}
.mr-bola .mr-ic{position:absolute;right:-2px;bottom:-2px;width:34%;height:34%;min-width:22px;min-height:22px;border-radius:50%;background:#fff;color:var(--cc);display:grid;place-items:center;font-size:.7em;box-shadow:0 4px 10px -4px rgba(0,0,0,.4)}
.mr-foco .mr-anillo{padding:8px}
.mr-foco .mr-bola{width:112px;height:112px;font-size:2rem;box-shadow:0 0 0 4px #fff,0 24px 50px -18px var(--cc)}
.mr-res{z-index:1;display:none;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;width:min(720px,100%);margin-top:16px}
.mr-res div{background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;padding:.6rem .7rem;text-align:center}
.mr-res b{display:block;font-size:1.3rem;font-weight:900}
.mr-res small{font-weight:700;color:#6b7570;font-size:.74rem}
.mr-res i{display:inline-block;width:10px;height:10px;border-radius:3px;background:var(--c);margin-right:4px}
@media (max-width:575.98px){.mr-res{grid-template-columns:repeat(2,minmax(0,1fr))}}
.mr-sec{z-index:1;display:flex;flex-direction:column;align-items:center;margin:26px 0 10px;text-align:center}
.mr-sec span{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#7a8580;background:#fff;padding:0 10px}
.mr-sec small{color:#93a097;font-weight:600;font-size:.78rem;margin-top:2px}
.mr-eq{z-index:1;display:flex;flex-wrap:wrap;justify-content:center;gap:24px 18px;max-width:1000px}
.mr-hijo{border:0;background:none;padding:0;display:flex;flex-direction:column;align-items:center;width:124px;cursor:pointer;animation:mrIn .4s cubic-bezier(.2,.8,.2,1) both;color:inherit}
.mr-hijo:hover .mr-anillo{transform:scale(1.06)}
.mr-hijo .mr-anillo{transition:transform .15s}
.mr-hijo b{margin-top:8px;font-size:.88rem;line-height:1.15;max-width:100%;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.mr-hijo small{color:#6b7570;font-weight:600;font-size:.74rem}
.mr-hijo .mr-mini{display:flex;gap:8px;margin-top:4px;font-size:.72rem;font-weight:800;color:#33403a}
.mr-hijo .mr-mini i{color:#16b34a}
.mr-cuad{display:flex;flex-wrap:wrap;gap:3px;justify-content:center;margin-top:6px;max-width:110px}
.mr-cuad i{width:11px;height:11px;border-radius:3px;background:var(--c);display:block}
.mr-cuad em{font-style:normal;font-size:.66rem;font-weight:800;color:#6b7570;line-height:11px}
.mr-sat{position:absolute;width:10px;height:10px;border-radius:50%;background:#fff;border:2.5px solid var(--cc);left:50%;top:50%;z-index:2}
.mr-tag{position:absolute;top:-8px;left:50%;transform:translateX(-50%);background:#f59e0b;color:#fff;font-size:.6rem;font-weight:800;border-radius:999px;padding:.1rem .45rem;white-space:nowrap;z-index:3}
.mr-neg{z-index:1;display:flex;flex-wrap:wrap;justify-content:center;gap:8px;max-width:920px}
.mr-n{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1.5px solid #e3ebe6;border-left:5px solid var(--c);border-radius:12px;padding:.4rem .75rem .4rem .55rem;font-weight:700;font-size:.82rem;text-decoration:none;color:#151a17;animation:mrIn .4s both;text-align:left}
.mr-n:hover{border-color:var(--c);color:#151a17}
.mr-n i{width:24px;height:24px;border-radius:7px;background:var(--c);display:grid;place-items:center;color:#fff;font-size:.62rem;flex:none}
.mr-n small{display:block;color:#7a8580;font-weight:600;font-size:.7rem}
.mr-ramas{z-index:1;width:min(900px,100%);display:flex;flex-direction:column;gap:8px;margin-top:6px}
.mr-rama{display:flex;align-items:center;gap:10px;background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;padding:.55rem .7rem;cursor:pointer;text-align:left;width:100%;animation:mrIn .4s both}
.mr-rama:hover{border-color:#16b34a}
.mr-rama .mr-bola{width:38px;height:38px;font-size:.8rem;box-shadow:none}
.mr-rama .t{flex:0 0 170px;min-width:0}
.mr-rama .t b{display:block;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mr-rama .t small{color:#6b7570;font-weight:600;font-size:.74rem}
.mr-rama .mr-cuad{max-width:none;justify-content:flex-start;margin:0;flex:1}
.mr-rama .mr-cuad i{width:14px;height:14px}
@media (max-width:575.98px){.mr-rama{flex-wrap:wrap}.mr-rama .t{flex:1 1 auto}.mr-rama .mr-cuad{flex-basis:100%}}
.mr-vacio{color:#93a097;font-weight:600;font-size:.88rem;text-align:center;padding:4px 12px}
.mr-ley{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;padding:10px 16px 14px;border-top:1px solid #edf1ee;font-size:.75rem;font-weight:700;color:#5b665e;background:#fff}
.mr-ley span::before{content:"";display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px;background:var(--c);vertical-align:-1px}
.mr-ley span.neg::before{width:9px;height:9px;border-radius:3px}
.mr-ley span.ani::before{background:conic-gradient(#16b34a 60%,#e6ece8 0);width:11px;height:11px}
@keyframes mrIn{from{opacity:0;transform:translateY(14px) scale(.9)}to{opacity:1;transform:none}}
@media (max-width:575.98px){
  .mr-ov{padding:0}
  .mr-win{border-radius:0;height:100%;height:100dvh}
  .mr-foco .mr-bola{width:92px;height:92px;font-size:1.6rem}
  .mr-hijo{width:100px}
  .mr-eq{gap:20px 10px}
  .mr-stat{min-width:0;flex:1 1 140px}
  .mr-ley{gap:8px;font-size:.7rem}
}
</style>
@endonce

<script>
(function () {
    var N = @json($nodos, JSON_UNESCAPED_UNICODE), RAIZ = @json($raizId), META = @json($metas);
    var COL = { raiz: '#0b0d0c', lider_pais: '#7c3aed', lider_departamento: '#2563eb', lider_ciudad: '#0891b2', lider_zona: '#f59e0b', asesor: '#16b34a' };
    var PLAN = { ok: '#16b34a', pronto: '#f2b705', vencido: '#e0245e', prueba: '#9aa8a0' };
    var PLAN_TXT = { ok: 'Pagó · al día', pronto: 'Vence pronto', vencido: 'Vencido', prueba: 'Prueba / gratis' };
    var $ = function (id) { return document.getElementById(id); };
    var ov = $('mrOv'), lienzo = $('mrLienzo'), svg = $('mrLineas'), foco = $('mrFoco'), eq = $('mrEq'), neg = $('mrNeg'), migas = $('mrMigas'), res = $('mrRes'), ramas = $('mrRamas');
    var camino = [RAIZ], modo = 'todo';
    var padre = {};
    Object.keys(N).forEach(function (k) { (N[k].h || []).forEach(function (h) { padre[h] = +k; }); });
    var cache = {};
    function total(id) { // toda la rama: personas, negocios y negocios por estado
        if (cache[id]) return cache[id];
        var n = N[id], r = { p: 0, t: 0, niv: 0, e: { ok: 0, pronto: 0, vencido: 0, prueba: 0 }, mes: 0 };
        if (!n) return r;
        n.neg.forEach(function (g) { r.t++; r.e[g.e]++; if (g.mes) r.mes++; });
        n.h.forEach(function (h) { var s = total(h); r.p += 1 + s.p; r.t += s.t; r.mes += s.mes; r.niv = Math.max(r.niv, 1 + s.niv); for (var k in s.e) r.e[k] += s.e[k]; });
        return (cache[id] = r);
    }
    function meta(n) { // % de la meta para subir (negocios pagados + asesores sumados)
        if (!n.sig || n.k === 'raiz') return -1;
        var a = Math.min(1, n.mn / Math.max(1, META.negocios)), b = Math.min(1, n.ma / Math.max(1, META.asesores));
        return Math.round((a + b) / 2 * 100);
    }
    function iniciales(t) { return (t || '?').trim().split(/\s+/).slice(0, 2).map(function (p) { return p[0]; }).join('').toUpperCase(); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function bola(n, tam, extra) {
        var c = COL[n.k] || '#16b34a', p = meta(n);
        var b = '<span class="mr-bola' + (n.on ? '' : ' off') + '" style="--cc:' + c + ';' + (tam ? 'width:' + tam + 'px;height:' + tam + 'px;font-size:' + Math.round(tam / 3.3) + 'px;' : '') + '">' +
            esc(n.k === 'raiz' ? 'LP' : iniciales(n.n)) + '<span class="mr-ic"><i class="fa-solid ' + esc(n.ic) + '"></i></span>' + (extra || '') + '</span>';
        return '<span class="mr-anillo' + (p < 0 ? ' sin' : '') + '" style="--cc:' + c + ';--p:' + Math.max(p, 0) + '" title="' + (p < 0 ? '' : 'Meta para subir: ' + p + '%') + '">' + b + '</span>';
    }
    function cuadritos(lista, max) {
        if (!lista.length) return '';
        var h = lista.slice(0, max).map(function (g) { return '<i style="--c:' + PLAN[g.e] + '" title="' + esc(g.n + ' · ' + PLAN_TXT[g.e]) + '"></i>'; }).join('');
        if (lista.length > max) h += '<em>+' + (lista.length - max) + '</em>';
        return '<span class="mr-cuad">' + h + '</span>';
    }
    function negociosRama(id) { var r = N[id].neg.slice(); N[id].h.forEach(function (h) { r = r.concat(negociosRama(h)); }); return r; }

    function pintar() {
        var id = camino[camino.length - 1], n = N[id], t = total(id), p = meta(n);
        var verEq = modo !== 'negocios', verNeg = modo !== 'asesores';
        cacheLineas = null;
        migas.innerHTML = camino.map(function (c, i) { return (i ? '<i class="fa-solid fa-chevron-right"></i>' : '') + '<button type="button" data-ir="' + i + '">' + esc(N[c].n.split(' ')[0]) + '</button>'; }).join('');

        // Foco
        foco.style.animation = 'none'; void foco.offsetWidth; foco.style.animation = '';
        var propios = n.neg.length;
        foco.innerHTML = bola(n) + '<h3>' + esc(n.n) + '</h3>' +
            '<div class="mr-c">' + (n.cod ? '<span class="mr-cod">' + esc(n.cod) + '</span> · ' : '') + esc(n.c) + (n.z ? ' · ' + esc(n.z) : '') + (n.pe ? ' · por aprobar' : (!n.on ? ' · inactivo' : '')) + '</div>' +
            '<div class="mr-stats">' +
                '<div class="mr-stat"><small><i class="fa-solid fa-users"></i>Asesores</small><b>' + n.h.length + '</b> <span>directos · ' + t.p + ' en su red</span></div>' +
                '<div class="mr-stat"><small><i class="fa-solid fa-store"></i>Negocios</small><b>' + (n.k === 'raiz' ? t.t : propios) + '</b> <span>' + (n.k === 'raiz' ? 'en toda la red' : 'propios · ' + t.t + ' en su red') + '</span></div>' +
                (t.niv ? '<div class="mr-stat"><small><i class="fa-solid fa-layer-group"></i>Niveles</small><b>' + t.niv + '</b> <span>hacia abajo</span></div>' : '') +
            '</div>' +
            (p >= 0 ? '<div class="mr-meta">Meta para subir a <b>' + esc(n.sig) + '</b>: ' + n.mn + '/' + META.negocios + ' negocios pagados · ' + n.ma + '/' + META.asesores + ' asesores (' + p + '%)</div>' : '') +
            (n.u ? '<a class="mr-ficha" href="' + esc(n.u) + '">Ver ficha y editar →</a>' : '') +
            (camino.length > 1 ? '<a class="mr-ficha" href="#" data-subir>↑ Volver a ' + esc(N[camino[camino.length - 2]].n.split(' ')[0]) + '</a>' : '');

        // Resumen de negocios (vista Negocios)
        res.style.display = modo === 'negocios' ? 'grid' : 'none';
        res.innerHTML = '<div><b>' + t.t + '</b><small>Negocios en la red</small></div>' +
            '<div><b>' + (t.e.ok + t.e.pronto) + '</b><small><i style="--c:#16b34a"></i>Pagan</small></div>' +
            '<div><b>' + t.e.prueba + '</b><small><i style="--c:#9aa8a0"></i>En prueba</small></div>' +
            '<div><b>' + t.mes + '</b><small><i style="--c:#f2b705"></i>Vencen este mes</small></div>';

        // Equipo (bolitas)
        $('mrSecEq').style.display = verEq ? '' : 'none';
        eq.style.display = verEq ? '' : 'none';
        var max = 1; n.h.forEach(function (h) { max = Math.max(max, total(h).p); });
        eq.innerHTML = !verEq ? '' : (n.h.length ? n.h.map(function (h, i) {
            var m = N[h], s = total(h), tam = 60 + Math.round(36 * Math.sqrt(s.p / max));
            var sats = '', ns = Math.min(m.h.length, 8);
            for (var j = 0; j < ns; j++) {
                var a = (j / ns) * Math.PI * 2 - Math.PI / 2, r = tam / 2 + 6;
                sats += '<span class="mr-sat" style="transform:translate(-50%,-50%) translate(' + Math.round(Math.cos(a) * r) + 'px,' + Math.round(Math.sin(a) * r) + 'px)"></span>';
            }
            return '<button type="button" class="mr-hijo" data-id="' + h + '" style="animation-delay:' + Math.min(i * 40, 600) + 'ms">' +
                bola(m, tam, sats + (m.pe ? '<span class="mr-tag">Por aprobar</span>' : '')) +
                '<b>' + esc(m.n) + '</b><small>' + (m.cod ? esc(m.cod) + ' · ' : '') + esc(m.c) + '</small>' +
                '<span class="mr-mini"><span title="Asesores en su red"><i class="fa-solid fa-users"></i> ' + s.p + '</span><span title="Negocios propios / en su red"><i class="fa-solid fa-store"></i> ' + m.neg.length + (s.t > m.neg.length ? ' · ' + s.t : '') + '</span></span>' +
                (verNeg ? cuadritos(m.neg, 12) : '') + '</button>';
        }).join('') : '<div class="mr-vacio">Todavía no tiene asesores en su equipo.</div>');

        // Negocios propios
        var muestraNeg = verNeg && n.k !== 'raiz';
        $('mrSecNeg').style.display = muestraNeg || modo === 'negocios' ? '' : 'none';
        $('mrNegTit').textContent = n.k === 'raiz' ? 'Negocios por persona' : 'Negocios propios (' + propios + ')';
        neg.innerHTML = !muestraNeg ? '' : (propios ? n.neg.map(function (g, i) {
            var tag = g.u ? 'a href="' + esc(g.u) + '"' : 'span';
            return '<' + tag + ' class="mr-n" title="' + esc(g.p) + '" style="--c:' + PLAN[g.e] + ';animation-delay:' + Math.min(i * 30, 500) + 'ms"><i class="fa-solid fa-store"></i><span>' + esc(g.n) + '<small>' + (g.cod ? esc(g.cod) + ' · ' : '') + PLAN_TXT[g.e] + '</small></span></' + (g.u ? 'a' : 'span') + '>';
        }).join('') : '<div class="mr-vacio">Aún no ha inscrito negocios.</div>');

        // Vista Negocios: los negocios de cada persona de su equipo, por fila
        ramas.style.display = modo === 'negocios' ? '' : 'none';
        ramas.innerHTML = modo !== 'negocios' ? '' : n.h.map(function (h, i) {
            var m = N[h], lista = negociosRama(h), s = total(h);
            return '<button type="button" class="mr-rama" data-id="' + h + '" style="animation-delay:' + Math.min(i * 30, 500) + 'ms">' + bola(m) +
                '<span class="t"><b>' + esc(m.n) + '</b><small>' + esc(m.c) + ' · ' + m.neg.length + ' propios' + (s.t > m.neg.length ? ' · ' + s.t + ' con su equipo' : '') + '</small></span>' +
                (lista.length ? cuadritos(lista, 60) : '<span class="mr-vacio" style="padding:0">Sin negocios</span>') + '</button>';
        }).join('');

        lienzo.scrollTop = 0;
        setTimeout(lineas, 30); setTimeout(lineas, 450);
    }
    var cacheLineas = null;
    function lineas() {
        if (!ov.classList.contains('on')) return;
        var L = lienzo.getBoundingClientRect(), st = lienzo.scrollTop;
        var fr = foco.getBoundingClientRect(), fb = foco.querySelector('.mr-bola').getBoundingClientRect();
        var x0 = fb.left + fb.width / 2 - L.left, y0 = fr.bottom - L.top + st + 4;
        var d = '';
        eq.querySelectorAll('.mr-hijo .mr-anillo').forEach(function (h) {
            var r = h.getBoundingClientRect(), x1 = r.left + r.width / 2 - L.left, y1 = r.top - L.top + st - 4, my = (y0 + y1) / 2;
            d += '<path d="M' + x0 + ' ' + y0 + ' C ' + x0 + ' ' + my + ', ' + x1 + ' ' + my + ', ' + x1 + ' ' + y1 + '"/>';
        });
        svg.setAttribute('height', lienzo.scrollHeight);
        svg.innerHTML = d;
    }
    function ir(id) { camino.push(+id); pintar(); }
    eq.addEventListener('click', function (e) { var b = e.target.closest('.mr-hijo'); if (b) ir(b.dataset.id); });
    ramas.addEventListener('click', function (e) { var b = e.target.closest('.mr-rama'); if (b) ir(b.dataset.id); });
    migas.addEventListener('click', function (e) { var b = e.target.closest('[data-ir]'); if (b) { camino = camino.slice(0, +b.dataset.ir + 1); pintar(); } });
    foco.addEventListener('click', function (e) { if (e.target.closest('[data-subir]')) { e.preventDefault(); camino.pop(); pintar(); } });
    $('mrModo').addEventListener('click', function (e) {
        var b = e.target.closest('[data-modo]'); if (!b) return;
        modo = b.dataset.modo;
        [].forEach.call(this.children, function (x) { x.classList.toggle('on', x === b); });
        pintar();
    });
    function abrir(id) {
        camino = [RAIZ];
        if (id != null && N[id] && +id !== RAIZ) {
            var c = [+id], p = padre[id];
            while (p != null && p !== RAIZ && N[p]) { c.unshift(p); p = padre[p]; }
            camino = camino.concat(c);
        }
        ov.classList.add('on'); ov.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden';
        pintar();
    }
    function cerrar() { ov.classList.remove('on'); ov.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
    ov.addEventListener('click', function (e) { if (e.target === ov || e.target.closest('[data-mr-cerrar]')) cerrar(); });
    document.addEventListener('keydown', function (e) {
        if (!ov.classList.contains('on')) return;
        if (e.key === 'Escape') { if (camino.length > 1) { camino.pop(); pintar(); } else cerrar(); }
    });
    window.addEventListener('resize', lineas);
    document.addEventListener('click', function (e) { var b = e.target.closest('[data-ver-red]'); if (b) { e.preventDefault(); abrir(b.dataset.verRed || null); } });
    if (location.hash === '#mapa') abrir(null);
})();
</script>
