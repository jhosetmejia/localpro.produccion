@extends('layouts.app')

@section('title', 'Centro de control')

@section('content')
{{-- 2026-11-25 (Jhoset): LocalPro + PFN + NES en vivo en una sola pantalla. Datos: /admin/centro/datos (App\Support\CentroControl) --}}
<style>
.cc{--bg:#0b0f0d;--card:#121815;--line:#1f2a24;--txt:#e8efe9;--mut:#8a948d;--ok:#22c55e;--am:#f5b400;--rojo:#ef4444;color:var(--txt);background:var(--bg);border-radius:18px;padding:18px;margin:-4px 0 20px}
.cc h1{font-size:1.35rem;font-weight:800;margin:0}
.cc .top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:14px}
.cc .reloj{font-variant-numeric:tabular-nums;color:var(--mut);font-weight:600}
.cc .vivo{display:inline-flex;align-items:center;gap:6px;font-size:.8rem;color:var(--ok);font-weight:700}
.cc .vivo i{width:8px;height:8px;border-radius:50%;background:var(--ok);animation:ccp 1.6s infinite}
@keyframes ccp{0%{box-shadow:0 0 0 0 rgba(34,197,94,.6)}70%{box-shadow:0 0 0 8px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}
.cc .btnc{background:#1b2420;color:var(--txt);border:1px solid var(--line);border-radius:10px;padding:6px 12px;font-weight:700;font-size:.85rem;cursor:pointer}
.cc .btnc:hover{border-color:var(--ok)}
.cc .sis{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:14px}
.cc .s{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;border-top:4px solid var(--c);cursor:pointer}
.cc .s.caido{border-color:var(--rojo)}
.cc .s h3{font-size:1rem;font-weight:800;margin:0 0 2px;display:flex;justify-content:space-between;gap:6px}
.cc .s small{color:var(--mut)}
.cc .s .num{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:10px}
.cc .s .num div,.cc .s .num a{display:block;background:#0e1311;border-radius:10px;padding:8px;color:var(--txt);text-decoration:none;border:1px solid transparent}
.cc .s .num a:hover{border-color:var(--c)}
.cc .s .num em{font-style:normal;color:var(--ok);font-size:.7rem;font-weight:700}
.cc .s .num b{display:block;font-size:1.15rem}
.cc .s .num span{font-size:.72rem;color:var(--mut)}
.cc .tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.cc .tabs button{background:#121815;color:var(--mut);border:1px solid var(--line);border-radius:999px;padding:6px 14px;font-weight:700;font-size:.85rem}
.cc .tabs button.on{color:#0b0f0d;background:var(--ok);border-color:var(--ok)}
.cc .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:12px}
.cc .p{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;min-width:0}
.cc .p h4{font-size:.95rem;font-weight:800;margin:0 0 10px;display:flex;justify-content:space-between;align-items:center;gap:8px}
.cc .p h4 a{font-size:.75rem;color:var(--ok);text-decoration:none;font-weight:700}
.cc .fila{display:flex;gap:8px;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--line);font-size:.86rem}
.cc .fila:last-child{border-bottom:0}
.cc .fila .t{flex:1;min-width:0;overflow-wrap:anywhere}
.cc .fila .m{color:var(--mut);font-size:.75rem;white-space:nowrap}
.cc .tag{font-size:.66rem;font-weight:800;padding:2px 7px;border-radius:6px;color:#fff;background:var(--c);white-space:nowrap}
.cc .n{min-width:30px;text-align:center;font-weight:800;border-radius:8px;padding:1px 6px;background:#1b2420}
.cc .n.rojo{background:var(--rojo);color:#fff}.cc .n.amarillo{background:var(--am);color:#111}.cc .n.ok{color:var(--ok)}
.cc .kp{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}
.cc .kp a,.cc .kp div.k{display:block;background:#0e1311;border-radius:10px;padding:10px;color:var(--txt);text-decoration:none}
.cc .kp b{display:block;font-size:1.3rem}
.cc .kp span{font-size:.74rem;color:var(--mut)}
.cc .kp em{font-style:normal;color:var(--ok);font-size:.74rem;font-weight:700}
.cc .vacio{color:var(--mut);font-size:.85rem;padding:6px 0}
.cc .err{color:#fca5a5;font-size:.85rem}
.cc code{color:#93c5fd;font-size:.75rem}
.cc .barra{height:6px;border-radius:4px;background:#1b2420;overflow:hidden;margin-top:4px}.cc .barra i{display:block;height:100%;background:var(--ok)}
</style>

<div class="cc">
    <div class="top">
        <div>
            <h1><i class="fa-solid fa-tower-broadcast me-1"></i> Centro de control</h1>
            <span class="vivo"><i></i> En vivo · se actualiza cada 30 s</span> <span class="reloj" id="ccHora"></span>
        </div>
        <div><button type="button" class="btnc" id="ccYa"><i class="fa-solid fa-rotate me-1"></i>Actualizar ya</button></div>
    </div>

    <div class="sis" id="ccSis"></div>
    <div class="tabs" id="ccTabs"></div>
    <div id="ccCuerpo"><div class="vacio">Cargando los tres sistemas…</div></div>
</div>

<script>
(function () {
    var SIS = {!! json_encode($sistemas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!};
    var URL_DATOS = {!! json_encode(route('admin.centro.datos')) !!};
    var datos = null, tab = 'todo';
    try { tab = localStorage.getItem('ccTab') || 'todo'; } catch (e) {}
    function $(id) { return document.getElementById(id); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
    function plata(v) { return v == null ? '—' : '$' + Math.round(v).toLocaleString('es-CO'); }
    function num(v) { return v == null ? '—' : (typeof v === 'number' ? v.toLocaleString('es-CO') : esc(v)); }
    function col(k) { return (SIS[k] && SIS[k].color) || '#22c55e'; }
    function tag(k) { return '<span class="tag" style="--c:' + col(k) + '">' + esc(k === 'localpro' ? 'LocalPro' : k.toUpperCase()) + '</span>'; }
    function link(u, t) { return u ? '<a href="' + esc(u) + '" target="_blank" rel="noopener">' + (t || 'Abrir') + ' ↗</a>' : ''; }
    function dinero(s, v) { return s.ingresos && s.ingresos.tipo === 'conteo' ? num(v) : plata(v); }
    function pendientesActivos(s) { return (s.pendientes || []).filter(function (p) { return p.nivel === 'rojo' || p.nivel === 'amarillo' || (p.n && p.nivel !== 'info'); }); }

    // Cuadro con clic: abre en otra pestaña lo específico de ese sistema
    function cuadro(url, valor, titulo, sub, color) {
        var inn = '<b' + (color ? ' style="color:' + color + '"' : '') + '>' + valor + '</b><span>' + esc(titulo) + '</span>' + (sub ? '<small class="d-block" style="font-size:.7rem">' + sub + '</small>' : '');
        return url ? '<a href="' + esc(url) + '" target="_blank" rel="noopener">' + inn + '</a>' : '<div>' + inn + '</div>';
    }
    function fallasLink(k, f) {
        var u = f.link || {!! json_encode(route('admin.fallas')) !!};
        return k === 'localpro' ? u : u + (u.indexOf('?') < 0 ? '?' : '&') + 'modulo=' + encodeURIComponent(k);
    }

    function tarjetas() {
        var h = '';
        Object.keys(SIS).forEach(function (k) {
            var s = datos.sistemas[k] || {}, caido = !s.ok;
            var f = s.fallas || {}, e = s.errores || {}, r = s.resumen || {}, nf = (e.n24 || 0) + (f.abiertas || 0);
            h += '<div class="s' + (caido ? ' caido' : '') + '" style="--c:' + col(k) + '" data-tab="' + k + '">'
                + '<h3><span>' + esc(SIS[k].nombre) + '</span>' + (caido ? '<span style="color:var(--rojo)">● Con problema</span>' : '<span style="color:var(--ok)">● En línea</span>') + '</h3>'
                + '<small>' + (s.version ? 'versión ' + esc(s.version) + ' · ' : '') + (s.ms != null ? s.ms + ' ms' : '') + '</small>'
                + (caido ? '<div class="err mt-2">' + esc(s.error || 'Sin respuesta') + '</div>' : '')
                + '<div class="num">'
                + cuadro(r.registrados && r.registrados.link, num(r.registrados ? r.registrados.n : null), (r.registrados && r.registrados.label) || 'Registrados',
                    r.registrados && r.registrados.hoy ? '<em>+' + num(r.registrados.hoy) + ' hoy</em>' : (r.registrados && r.registrados.sub ? esc(r.registrados.sub) : ''), '')
                + cuadro(r.ingresos_anio && r.ingresos_anio.link, plata(r.ingresos_anio ? r.ingresos_anio.n : null), 'Ingresos ' + ((r.ingresos_anio && r.ingresos_anio.anio) || 'del año'), '', '')
                + cuadro(fallasLink(k, f), num(nf), 'Fallas del sistema', num(e.n24 || 0) + ' del servidor · ' + num(f.abiertas || 0) + ' en celulares', nf ? 'var(--rojo)' : 'var(--ok)')
                + cuadro(r.mia && r.mia.link, num(r.mia ? r.mia.n : null), 'Preguntas de Mía sin responder', '', r.mia && r.mia.n ? 'var(--am)' : 'var(--ok)')
                + '</div></div>';
        });
        $('ccSis').innerHTML = h;
        var t = '<button data-tab="todo" class="' + (tab === 'todo' ? 'on' : '') + '">Todo junto</button>';
        Object.keys(SIS).forEach(function (k) {
            var s = datos.sistemas[k] || {}, n = pendientesActivos(s).length;
            t += '<button data-tab="' + k + '" class="' + (tab === k ? 'on' : '') + '">' + esc(SIS[k].nombre) + (n ? ' · ' + n : '') + '</button>';
        });
        $('ccTabs').innerHTML = t;
    }

    function panel(titulo, cuerpo, enlace) { return '<div class="p"><h4><span>' + titulo + '</span>' + (enlace || '') + '</h4>' + cuerpo + '</div>'; }
    function filasPendientes(lista, conTag) {
        if (!lista.length) return '<div class="vacio">✅ Nada pendiente.</div>';
        return lista.map(function (p) {
            return '<div class="fila">' + (conTag ? tag(p._k) : '') + '<div class="t">' + (p.link ? '<a href="' + esc(p.link) + '" target="_blank" rel="noopener" style="color:inherit">' + esc(p.texto) + '</a>' : esc(p.texto)) + '</div>'
                + (p.n != null ? '<span class="n ' + esc(p.nivel || '') + '">' + num(p.n) + '</span>' : '<span class="n ' + esc(p.nivel || '') + '">!</span>') + '</div>';
        }).join('');
    }
    function filasErrores(lista, conTag) {
        if (!lista.length) return '<div class="vacio">✅ Sin errores del servidor en las últimas 24 h.</div>';
        return lista.map(function (g) {
            return '<div class="fila">' + (conTag ? tag(g._k) : '') + '<div class="t">' + esc(g.mensaje) + (g.archivo ? '<br><code>' + esc(g.archivo) + '</code>' : '') + '</div><div class="m">' + num(g.veces) + '× · ' + esc((g.ultima || '').slice(5)) + '</div></div>';
        }).join('');
    }
    function filasFallas(lista, conTag) {
        if (!lista.length) return '<div class="vacio">✅ Sin fallas abiertas en los celulares.</div>';
        return lista.map(function (f) {
            return '<div class="fila">' + (conTag ? tag(f._k) : '') + '<div class="t"><b>' + esc(f.id) + '</b> ' + esc(f.mensaje) + (f.pagina ? '<br><code>' + esc(f.pagina) + '</code>' : '') + '</div><div class="m">' + num(f.veces) + '× · ' + esc((f.ultima || '').slice(5)) + '</div></div>';
        }).join('');
    }
    function filasEnvios(s, k, conTag) {
        var e = s.envios || {}, h = '';
        (e.campanas || []).forEach(function (c) {
            var pct = c.enviados ? Math.round(100 * (c.leidos || 0) / c.enviados) : 0;
            h += '<div class="fila">' + (conTag ? tag(k) : '') + '<div class="t"><b>' + esc(c.titulo) + '</b><br><span style="color:var(--mut);font-size:.78rem">' + num(c.enviados) + ' enviados'
                + (c.leidos != null ? ' · ' + num(c.leidos) + ' leídos (' + pct + '%)' : '') + (c.respondieron != null ? ' · ' + num(c.respondieron) + ' respondieron' : '') + (c.detalle ? ' · ' + esc(c.detalle) : '') + '</span>'
                + (c.leidos != null ? '<div class="barra"><i style="width:' + pct + '%"></i></div>' : '') + '</div><div class="m">' + esc((c.ultimo || '').slice(5)) + '</div></div>';
        });
        (e.correo || []).forEach(function (c) {
            if (c.limite == null && !c.usados) return;
            h += '<div class="fila">' + (conTag ? tag(k) : '') + '<div class="t">✉️ Correo · ' + esc(c.proveedor) + '</div><div class="m">' + num(c.usados) + (c.limite != null ? ' / ' + num(c.limite) + ' hoy' : ' hoy') + '</div></div>';
        });
        return h;
    }
    function filasActividad(lista, conTag) {
        if (!lista.length) return '<div class="vacio">Sin movimiento reciente.</div>';
        return lista.map(function (a) { return '<div class="fila">' + (conTag ? tag(a._k) : '') + '<div class="t">' + esc(a.texto) + '</div><div class="m">' + esc((a.fecha || '').slice(5)) + '</div></div>'; }).join('');
    }
    function juntar(campo, sub) {
        var out = [];
        Object.keys(SIS).forEach(function (k) {
            var s = datos.sistemas[k] || {}, v = s[campo];
            if (sub && v) v = v[sub];
            (v || []).forEach(function (x) { var y = Object.assign({}, x); y._k = k; out.push(y); });
        });
        return out;
    }

    function vistaTodo() {
        var pend = [];
        Object.keys(SIS).forEach(function (k) { pendientesActivos(datos.sistemas[k] || {}).forEach(function (p) { var y = Object.assign({}, p); y._k = k; pend.push(y); }); });
        var caidos = Object.keys(SIS).filter(function (k) { return !(datos.sistemas[k] || {}).ok; });
        caidos.forEach(function (k) { pend.unshift({ _k: k, texto: (datos.sistemas[k] || {}).error || 'No responde', nivel: 'rojo', n: null }); });
        var err = juntar('errores', 'grupos').sort(function (a, b) { return (b.ultima || '').localeCompare(a.ultima || ''); });
        var fal = juntar('fallas', 'lista').sort(function (a, b) { return (b.ultima || '').localeCompare(a.ultima || ''); });
        var act = juntar('actividad').sort(function (a, b) { return (b.fecha || '').localeCompare(a.fecha || ''); }).slice(0, 25);
        var ing = '';
        Object.keys(SIS).forEach(function (k) {
            var s = datos.sistemas[k] || {}, i = s.ingresos;
            if (!i) return;
            ing += '<div class="fila">' + tag(k) + '<div class="t">Hoy <b>' + dinero(s, i.hoy) + '</b> · 7 días <b>' + dinero(s, i.semana) + '</b> · mes <b>' + dinero(s, i.mes) + '</b><br><span style="color:var(--mut);font-size:.78rem">' + esc(i.etiqueta_total || 'Total') + ': ' + dinero(s, i.total)
                + (i.contabilidad ? ' · Contabilidad ' + i.contabilidad.anio + ': ingresos ' + plata(i.contabilidad.ingresos) + ', gastos ' + plata(i.contabilidad.gastos) : '') + '</span></div></div>';
        });
        var env = '';
        Object.keys(SIS).forEach(function (k) { env += filasEnvios(datos.sistemas[k] || {}, k, true); });
        return '<div class="grid">'
            + panel('🚨 Para atender ya', filasPendientes(pend, true))
            + panel('💰 Ingresos', ing || '<div class="vacio">Sin datos.</div>')
            + panel('🐞 Errores del servidor (24 h)', filasErrores(err.slice(0, 15), true))
            + panel('📱 Fallas en celulares (sin resolver)', filasFallas(fal.slice(0, 15), true), link({!! json_encode(route('admin.fallas')) !!}, 'Ver todas'))
            + panel('📣 Envíos masivos y correo', env || '<div class="vacio">Sin envíos en los últimos días.</div>')
            + panel('⚡ Actividad reciente', filasActividad(act, true))
            + '</div>';
    }

    function vistaSistema(k) {
        var s = datos.sistemas[k] || {};
        if (!s.ok) return '<div class="p"><h4>' + esc(SIS[k].nombre) + '</h4><div class="err">' + esc(s.error || 'Sin respuesta') + '</div></div>';
        var kp = (s.kpis || []).map(function (x) {
            var inn = '<b>' + num(x.valor) + '</b><span>' + esc(x.label) + '</span>' + (x.hoy != null ? ' <em>+' + num(x.hoy) + ' hoy</em>' : '') + (x.sub ? '<br><span>' + esc(x.sub) + '</span>' : '');
            return x.link ? '<a href="' + esc(x.link) + '" target="_blank" rel="noopener">' + inn + '</a>' : '<div class="k">' + inn + '</div>';
        }).join('');
        var i = s.ingresos || {}, ult = (i.ultimos || []).map(function (u) {
            return '<div class="fila"><div class="t">' + esc(u.concepto) + ' · ' + esc(u.persona) + '</div><div class="m">' + (u.monto != null ? plata(u.monto) + ' · ' : '') + esc((u.fecha || '').slice(5)) + '</div></div>';
        }).join('');
        var conK = function (l) { return (l || []).map(function (x) { var y = Object.assign({}, x); y._k = k; return y; }); };
        return '<div class="grid">'
            + panel('📊 Números', '<div class="kp">' + (kp || '<div class="vacio">Sin datos.</div>') + '</div>', link(s.url, 'Ir al sitio'))
            + panel('💰 Ingresos', '<div class="fila"><div class="t">Hoy <b>' + dinero(s, i.hoy) + '</b> · 7 días <b>' + dinero(s, i.semana) + '</b> · mes <b>' + dinero(s, i.mes) + '</b> · ' + esc(i.etiqueta_total || 'total') + ' <b>' + dinero(s, i.total) + '</b>'
                + (i.nota ? '<br><span style="color:var(--mut);font-size:.75rem">' + esc(i.nota) + '</span>' : '') + '</div></div>' + (ult || '<div class="vacio">Sin pagos todavía.</div>'), link(i.link, 'Detalle'))
            + panel('📋 Pendientes', filasPendientes(s.pendientes || [], false))
            + panel('🐞 Errores del servidor (24 h)', filasErrores((s.errores || {}).grupos || [], false), link((s.errores || {}).link, 'Reporte'))
            + panel('📱 Fallas en celulares (sin resolver)', filasFallas(conK((s.fallas || {}).lista), false), link((s.fallas || {}).link, 'Ver todas'))
            + panel('📣 Envíos masivos y correo', filasEnvios(s, k, false) || '<div class="vacio">Sin envíos en los últimos días.</div>', link((s.envios || {}).link, 'Detalle'))
            + panel('⚡ Actividad reciente', filasActividad(s.actividad || [], false))
            + ((s.fallas_panel || []).length ? panel('⚠️ Partes que no cargaron', s.fallas_panel.map(function (x) { return '<div class="fila err">' + esc(x) + '</div>'; }).join('')) : '')
            + '</div>';
    }

    function pintar() {
        if (!datos) return;
        tarjetas();
        $('ccCuerpo').innerHTML = tab === 'todo' || !SIS[tab] ? vistaTodo() : vistaSistema(tab);
    }

    var cargando = false;
    function cargar(fresco) {
        if (cargando) return; cargando = true;
        fetch(URL_DATOS + (fresco ? '?fresco=1' : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (d) { datos = d; pintar(); })
            .catch(function (e) { if (!datos) $('ccCuerpo').innerHTML = '<div class="err">No se pudo cargar (' + esc(e.message) + '). Se reintenta solo.</div>'; })
            .finally(function () { cargando = false; });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.cc .num a')) return;
        var b = e.target.closest('.cc [data-tab]');
        if (!b) return;
        tab = b.dataset.tab;
        try { localStorage.setItem('ccTab', tab); } catch (x) {}
        pintar();
    });
    $('ccYa').addEventListener('click', function () { cargar(true); });
    setInterval(function () { $('ccHora').textContent = new Date().toLocaleTimeString('es-CO'); }, 1000);
    cargar(false);
    setInterval(function () { if (!document.hidden) cargar(false); }, 30000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) cargar(false); });
})();
</script>
@endsection
