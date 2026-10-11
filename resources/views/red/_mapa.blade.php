{{--
    Mapa de la red en burbujas (multinivel). Se abre con un botón "Ver mi red".
    Uso: @include('red._mapa', ['raices' => .., 'hijos' => .., 'negocios' => .., 'admin' => bool, 'raiz' => 'LocalPro' | null])
    Si 'raiz' es texto, se crea una burbuja arriba con ese nombre (LocalPro) que agrupa a las raíces.
--}}
@php
    $nodos = [];
    $estadoPlan = function ($t) {
        if (!$t->plan_vence) return 'gratis';
        if ($t->plan_vence->isPast()) return 'vencido';
        return now()->diffInDays($t->plan_vence) <= 30 ? 'pronto' : 'ok';
    };
    $agregar = function ($n) use (&$agregar, &$nodos, $hijos, $negocios, $admin, $estadoPlan) {
        $equipo = $hijos[$n->id] ?? collect();
        $nodos[$n->id] = [
            'id' => $n->id,
            'n' => $n->nombre,
            'c' => $n->cargo_nombre,
            'k' => $n->cargo,
            'ic' => $n->cargo_icono,
            'z' => trim(implode(' · ', array_filter([$n->zona, $n->ciudad ?: $n->departamento]))),
            'on' => (bool) $n->activo,
            'pe' => $n->estado === 'pendiente',
            'u' => !empty($admin) ? route('admin.red.show', $n) : null,
            'neg' => ($negocios[$n->id] ?? collect())->map(fn ($t) => [
                'n' => $t->nombre,
                'e' => $estadoPlan($t),
                'p' => ($t->plan->nombre ?? 'Sin plan') . ($t->plan_vence ? ' · ' . ($t->plan_vence->isPast() ? 'venció ' : 'vence ') . $t->plan_vence->format('d/m/Y') : ''),
                'u' => !empty($admin) ? route('admin.tiendas.show', $t) : null,
            ])->values()->all(),
            'h' => $equipo->pluck('id')->values()->all(),
        ];
        foreach ($equipo as $h) if (!isset($nodos[$h->id])) $agregar($h);
    };
    foreach ($raices as $r) $agregar($r);
    $raizId = $raices->count() === 1 && empty($raiz) ? $raices->first()->id : 0;
    if ($raizId === 0) {
        $nodos[0] = ['id' => 0, 'n' => $raiz ?: 'Mi red', 'c' => 'Toda la red', 'k' => 'raiz', 'ic' => 'fa-crown', 'z' => '', 'on' => true, 'pe' => false, 'u' => null, 'neg' => [], 'h' => $raices->pluck('id')->values()->all()];
    }
@endphp

<div class="mr-ov" id="mrOv" aria-hidden="true">
    <div class="mr-win" role="dialog" aria-modal="true" aria-label="Mapa de la red">
        <div class="mr-top">
            <div class="mr-tit"><i class="fa-solid fa-circle-nodes"></i> Mapa de la red</div>
            <button type="button" class="mr-x" data-mr-cerrar aria-label="Cerrar">×</button>
        </div>
        <div class="mr-migas" id="mrMigas"></div>
        <div class="mr-lienzo" id="mrLienzo">
            <svg class="mr-lineas" id="mrLineas" aria-hidden="true"></svg>
            <div class="mr-foco" id="mrFoco"></div>
            <div class="mr-sec" id="mrSecEq"><span>Su equipo</span><small>Toque una burbuja para ver a quiénes metió esa persona</small></div>
            <div class="mr-eq" id="mrEq"></div>
            <div class="mr-sec" id="mrSecNeg"><span>Sus negocios</span></div>
            <div class="mr-neg" id="mrNeg"></div>
        </div>
        <div class="mr-ley">
            <span style="--c:#7c3aed">País</span><span style="--c:#2563eb">Departamento</span><span style="--c:#0891b2">Ciudad</span><span style="--c:#f59e0b">Zona</span><span style="--c:#16b34a">Asesor</span>
            <span class="neg" style="--c:#16b34a">Plan al día</span><span class="neg" style="--c:#f2b705">Vence pronto</span><span class="neg" style="--c:#e0245e">Vencido</span><span class="neg" style="--c:#b8c2bc">Gratis</span>
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
.mr-win{background:radial-gradient(1200px 500px at 50% -10%,#effbf1 0%,#fff 55%);width:min(1100px,100%);height:min(860px,100%);border-radius:28px;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 40px 100px -30px rgba(0,0,0,.6);transform:translateY(16px) scale(.98);transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.mr-ov.on .mr-win{transform:none}
.mr-top{display:flex;align-items:center;justify-content:space-between;padding:14px 18px 6px}
.mr-tit{font-weight:900;font-size:1.15rem}.mr-tit i{color:#16b34a;margin-right:6px}
.mr-x{border:0;background:#f1f5f2;width:40px;height:40px;border-radius:14px;font-size:1.5rem;font-weight:700;line-height:1;color:#33403a}
.mr-migas{display:flex;gap:6px;align-items:center;overflow-x:auto;padding:4px 18px 8px;scrollbar-width:none}
.mr-migas button{border:1.5px solid #dfe6e2;background:#fff;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.82rem;white-space:nowrap;color:#33403a}
.mr-migas button:last-child{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.mr-migas i{color:#b3bab6;font-size:.7rem}
.mr-lienzo{position:relative;flex:1;overflow-y:auto;overflow-x:hidden;padding:10px 16px 24px;display:flex;flex-direction:column;align-items:center}
.mr-lineas{position:absolute;left:0;top:0;width:100%;pointer-events:none;overflow:visible}
.mr-lineas path{fill:none;stroke:#bfe6c9;stroke-width:2;stroke-dasharray:4 5;animation:mrDash 1.2s linear infinite}
@keyframes mrDash{to{stroke-dashoffset:-18}}
.mr-foco{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;text-align:center;margin:6px 0 4px;animation:mrIn .35s cubic-bezier(.2,.8,.2,1)}
.mr-foco .mr-bola{width:118px;height:118px;font-size:2.1rem;box-shadow:0 0 0 8px rgba(255,255,255,.9),0 0 0 10px var(--cc),0 24px 50px -18px var(--cc)}
.mr-foco h3{font-weight:900;font-size:1.25rem;margin:16px 0 2px}
.mr-foco .mr-c{font-weight:700;color:#5b665e;font-size:.9rem}
.mr-stats{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;justify-content:center}
.mr-stats span{background:#fff;border:1.5px solid #e3ebe6;border-radius:999px;padding:.3rem .75rem;font-weight:800;font-size:.82rem}
.mr-stats span i{color:#16b34a;margin-right:4px}
.mr-foco a.mr-ficha{margin-top:8px;font-weight:700;font-size:.85rem;color:#11702f}
.mr-bola{--cc:#16b34a;position:relative;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:900;background:radial-gradient(circle at 30% 25%,rgba(255,255,255,.45),transparent 45%),var(--cc);flex:none}
.mr-bola.off{filter:grayscale(.85);opacity:.7}
.mr-bola .mr-ic{position:absolute;right:-2px;bottom:-2px;width:34%;height:34%;min-width:22px;min-height:22px;border-radius:50%;background:#fff;color:var(--cc);display:grid;place-items:center;font-size:.7em;box-shadow:0 4px 10px -4px rgba(0,0,0,.4)}
.mr-sec{z-index:1;display:flex;flex-direction:column;align-items:center;margin:26px 0 10px;text-align:center}
.mr-sec span{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#7a8580;background:#fff;padding:0 10px}
.mr-sec small{color:#93a097;font-weight:600;font-size:.78rem;margin-top:2px}
.mr-eq{z-index:1;display:flex;flex-wrap:wrap;justify-content:center;gap:22px 18px;max-width:980px}
.mr-hijo{border:0;background:none;padding:0;display:flex;flex-direction:column;align-items:center;width:118px;cursor:pointer;animation:mrIn .4s cubic-bezier(.2,.8,.2,1) both;color:inherit}
.mr-hijo:hover .mr-bola{transform:scale(1.07)}
.mr-hijo .mr-bola{transition:transform .15s;box-shadow:0 0 0 5px #fff,0 14px 30px -14px var(--cc)}
.mr-hijo b{margin-top:10px;font-size:.88rem;line-height:1.15;max-width:100%;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.mr-hijo small{color:#6b7570;font-weight:600;font-size:.74rem}
.mr-hijo .mr-mini{display:flex;gap:6px;margin-top:4px;font-size:.72rem;font-weight:800;color:#33403a}
.mr-hijo .mr-mini i{color:#16b34a}
.mr-sat{position:absolute;width:11px;height:11px;border-radius:50%;background:#fff;border:2.5px solid var(--cc);left:50%;top:50%}
.mr-tag{position:absolute;top:-6px;left:50%;transform:translateX(-50%);background:#f59e0b;color:#fff;font-size:.6rem;font-weight:800;border-radius:999px;padding:.1rem .45rem;white-space:nowrap}
.mr-neg{z-index:1;display:flex;flex-wrap:wrap;justify-content:center;gap:8px;max-width:900px}
.mr-n{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1.5px solid #e3ebe6;border-radius:999px;padding:.35rem .8rem .35rem .4rem;font-weight:700;font-size:.82rem;text-decoration:none;color:#151a17;animation:mrIn .4s both}
.mr-n:hover{border-color:#16b34a;color:#151a17}
.mr-n i{width:22px;height:22px;border-radius:50%;background:var(--c);display:grid;place-items:center;color:#fff;font-size:.62rem}
.mr-vacio{color:#93a097;font-weight:600;font-size:.88rem;text-align:center;padding:4px 12px}
.mr-ley{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;padding:10px 16px 14px;border-top:1px solid #edf1ee;font-size:.75rem;font-weight:700;color:#5b665e;background:#fff}
.mr-ley span::before{content:"";display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px;background:var(--c);vertical-align:-1px}
.mr-ley span.neg::before{width:8px;height:8px;border-radius:3px}
@keyframes mrIn{from{opacity:0;transform:translateY(14px) scale(.9)}to{opacity:1;transform:none}}
@media (max-width:575.98px){
  .mr-ov{padding:0}
  .mr-win{border-radius:0;height:100%;height:100dvh}
  .mr-foco .mr-bola{width:96px;height:96px;font-size:1.7rem}
  .mr-hijo{width:96px}
  .mr-eq{gap:18px 10px}
  .mr-ley{gap:8px;font-size:.7rem}
}
</style>
@endonce

<script>
(function () {
    var N = @json($nodos, JSON_UNESCAPED_UNICODE), RAIZ = @json($raizId);
    var COL = { raiz: '#0b0d0c', lider_pais: '#7c3aed', lider_departamento: '#2563eb', lider_ciudad: '#0891b2', lider_zona: '#f59e0b', asesor: '#16b34a' };
    var PLAN = { ok: '#16b34a', pronto: '#f2b705', vencido: '#e0245e', gratis: '#b8c2bc' };
    var ov = document.getElementById('mrOv'), lienzo = document.getElementById('mrLienzo'), svg = document.getElementById('mrLineas');
    var foco = document.getElementById('mrFoco'), eq = document.getElementById('mrEq'), neg = document.getElementById('mrNeg'), migas = document.getElementById('mrMigas');
    var camino = [RAIZ];
    // Padres para poder volver
    var padre = {};
    Object.keys(N).forEach(function (k) { (N[k].h || []).forEach(function (h) { padre[h] = +k; }); });
    var cache = {};
    function total(id) { // personas y negocios en toda la rama
        if (cache[id]) return cache[id];
        var n = N[id], r = { p: 0, t: n ? n.neg.length : 0, niv: 0 };
        if (!n) return r;
        n.h.forEach(function (h) { var s = total(h); r.p += 1 + s.p; r.t += s.t; r.niv = Math.max(r.niv, 1 + s.niv); });
        return (cache[id] = r);
    }
    function iniciales(t) { return (t || '?').trim().split(/\s+/).slice(0, 2).map(function (p) { return p[0]; }).join('').toUpperCase(); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function bola(n, extra) {
        var c = COL[n.k] || '#16b34a';
        return '<span class="mr-bola' + (n.on ? '' : ' off') + '" style="--cc:' + c + ';' + (extra || '') + '">' + esc(n.k === 'raiz' ? 'LP' : iniciales(n.n)) +
            '<span class="mr-ic"><i class="fa-solid ' + esc(n.ic) + '"></i></span></span>';
    }
    function pintar() {
        var id = camino[camino.length - 1], n = N[id], t = total(id);
        // Migas
        migas.innerHTML = camino.map(function (c, i) { return (i ? '<i class="fa-solid fa-chevron-right"></i>' : '') + '<button type="button" data-ir="' + i + '">' + esc(N[c].n.split(' ')[0]) + '</button>'; }).join('');
        // Foco
        foco.style.animation = 'none'; void foco.offsetWidth; foco.style.animation = '';
        foco.innerHTML = bola(n) + '<h3>' + esc(n.n) + '</h3><div class="mr-c">' + esc(n.c) + (n.z ? ' · ' + esc(n.z) : '') + (n.pe ? ' · por aprobar' : (!n.on ? ' · inactivo' : '')) + '</div>' +
            '<div class="mr-stats"><span><i class="fa-solid fa-users"></i>' + t.p + ' en su red</span><span><i class="fa-solid fa-store"></i>' + t.t + ' negocios</span>' + (t.niv ? '<span><i class="fa-solid fa-layer-group"></i>' + t.niv + ' nivel' + (t.niv > 1 ? 'es' : '') + '</span>' : '') + '</div>' +
            (n.u ? '<a class="mr-ficha" href="' + esc(n.u) + '">Ver ficha y editar →</a>' : '') +
            (camino.length > 1 ? '<a class="mr-ficha" href="#" data-subir>↑ Volver a ' + esc(N[camino[camino.length - 2]].n.split(' ')[0]) + '</a>' : '');
        // Equipo
        var max = 1; n.h.forEach(function (h) { max = Math.max(max, total(h).p); });
        eq.innerHTML = n.h.length ? n.h.map(function (h, i) {
            var m = N[h], s = total(h), tam = 64 + Math.round(40 * Math.sqrt(s.p / max));
            var sats = '', ns = Math.min(m.h.length, 8);
            for (var j = 0; j < ns; j++) {
                var a = (j / ns) * Math.PI * 2 - Math.PI / 2, r = tam / 2 + 4;
                sats += '<span class="mr-sat" style="transform:translate(-50%,-50%) translate(' + Math.round(Math.cos(a) * r) + 'px,' + Math.round(Math.sin(a) * r) + 'px)"></span>';
            }
            return '<button type="button" class="mr-hijo" data-id="' + h + '" style="animation-delay:' + Math.min(i * 40, 600) + 'ms">' +
                bola(m, 'width:' + tam + 'px;height:' + tam + 'px;font-size:' + Math.round(tam / 3.4) + 'px').replace('</span></span>', '</span>' + sats + (m.pe ? '<span class="mr-tag">Por aprobar</span>' : '') + '</span>') +
                '<b>' + esc(m.n) + '</b><small>' + esc(m.c) + '</small>' +
                '<span class="mr-mini"><span><i class="fa-solid fa-users"></i> ' + s.p + '</span><span><i class="fa-solid fa-store"></i> ' + s.t + '</span></span></button>';
        }).join('') : '<div class="mr-vacio">Todavía no tiene personas en su equipo.</div>';
        // Negocios
        document.getElementById('mrSecNeg').style.display = n.k === 'raiz' ? 'none' : '';
        neg.innerHTML = n.k === 'raiz' ? '' : (n.neg.length ? n.neg.map(function (g, i) {
            var tag = g.u ? 'a href="' + esc(g.u) + '"' : 'span';
            return '<' + tag + ' class="mr-n" title="' + esc(g.p) + '" style="--c:' + PLAN[g.e] + ';animation-delay:' + Math.min(i * 30, 500) + 'ms"><i class="fa-solid fa-store"></i>' + esc(g.n) + '</' + (g.u ? 'a' : 'span') + '>';
        }).join('') : '<div class="mr-vacio">Aún no ha metido negocios.</div>');
        lienzo.scrollTop = 0;
        setTimeout(lineas, 30); setTimeout(lineas, 450);
    }
    function lineas() {
        var b = foco.querySelector('.mr-bola'); if (!b || !ov.classList.contains('on')) return;
        var L = lienzo.getBoundingClientRect(), st = lienzo.scrollTop;
        var fb = b.getBoundingClientRect(), x0 = fb.left + fb.width / 2 - L.left, y0 = foco.getBoundingClientRect().bottom - L.top + st + 4;
        var d = '';
        eq.querySelectorAll('.mr-hijo .mr-bola').forEach(function (h) {
            var r = h.getBoundingClientRect(), x1 = r.left + r.width / 2 - L.left, y1 = r.top - L.top + st - 4;
            var my = (y0 + y1) / 2;
            d += '<path d="M' + x0 + ' ' + y0 + ' C ' + x0 + ' ' + my + ', ' + x1 + ' ' + my + ', ' + x1 + ' ' + y1 + '"/>';
        });
        svg.setAttribute('height', lienzo.scrollHeight);
        svg.innerHTML = d;
    }
    function ir(id) { camino.push(+id); pintar(); }
    eq.addEventListener('click', function (e) { var b = e.target.closest('.mr-hijo'); if (b) ir(b.dataset.id); });
    migas.addEventListener('click', function (e) { var b = e.target.closest('[data-ir]'); if (b) { camino = camino.slice(0, +b.dataset.ir + 1); pintar(); } });
    foco.addEventListener('click', function (e) { if (e.target.closest('[data-subir]')) { e.preventDefault(); camino.pop(); pintar(); } });
    function abrir(id) {
        camino = [RAIZ];
        if (id != null && N[id] && +id !== RAIZ) { // armar el camino hasta esa persona
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
