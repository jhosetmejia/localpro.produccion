/* ================= 2026-11-28 (Jhoset): seleccionar mensajes como WhatsApp =================
   Mantener presionado (o clic derecho) un mensaje: arriba responder · info · eliminar · copiar · reenviar,
   y encima del mensaje las reacciones 👍 ❤️ 😂 😮 😢 🙏 + (más emojis). Tocando otros mensajes se agregan a la selección. */
var SV = {
    resp: '<svg viewBox="0 0 24 24"><path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>',
    borrar: '<svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
    copiar: '<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
    reenviar: '<svg viewBox="0 0 24 24"><path d="M14 9V5l7 7-7 7v-4.1c-5 0-8.5 1.6-11 5.1 1-5 4-10 11-11z"/></svg>',
    atras: '<svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>'
};
var REAC_RAPIDAS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
(function () {
    var bar = document.createElement('div'); bar.className = 'selbar'; bar.id = 'selbar'; bar.hidden = true;
    bar.innerHTML = '<button class="ib" data-s="salir" title="Cancelar">' + SV.atras + '</button><span class="n" id="sel-n">1</span>' +
        '<button class="ib" data-s="responder" title="Responder">' + SV.resp + '</button>' +
        '<button class="ib" data-s="info" title="Info">' + SV.info + '</button>' +
        '<button class="ib" data-s="borrar" title="Eliminar">' + SV.borrar + '</button>' +
        '<button class="ib" data-s="copiar" title="Copiar">' + SV.copiar + '</button>' +
        '<button class="ib" data-s="reenviar" title="Reenviar">' + SV.reenviar + '</button>';
    $('conv').insertBefore(bar, $('conv').firstChild);
    var rb = document.createElement('div'); rb.className = 'reacbar'; rb.id = 'reacbar'; rb.hidden = true;
    document.body.appendChild(rb);
    var rp = document.createElement('div'); rp.className = 'resp-prev'; rp.id = 'resp-prev'; rp.hidden = true;
    rp.innerHTML = '<div class="cita"><b id="rp-a"></b><span id="rp-t"></span></div><button type="button" class="ib" id="rp-x" title="Quitar">✕</button>';
    $('barra').parentNode.insertBefore(rp, $('barra'));
    $('rp-x').onclick = function () { quitarResp(); };
    bar.addEventListener('click', function (e) { var b = e.target.closest('[data-s]'); if (b) accionSel(b.dataset.s); });
    rb.addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        var id = idsSel()[0];
        if (b.dataset.mas) { salirSel(); return masEmojis(id); }
        reaccionar(id, b.dataset.e); salirSel();
    });
})();

function idsSel() { return Object.keys(st.sel || {}).map(Number).sort(function (a, b) { return a - b; }); }
function entrarSel(id) {
    var m = st.msg[id]; if (!m) return;
    if (!st.sel) { st.sel = {}; try { history.pushState({ chat: st.abierto, sel: 1 }, '', location.href); } catch (e) {} }
    st.sel[id] = 1; pintarSel();
}
function alternarSel(id) {
    if (!st.sel || !st.msg[id]) return;
    if (st.sel[id]) delete st.sel[id]; else st.sel[id] = 1;
    if (!idsSel().length) return salirSel();
    pintarSel();
}
function salirSel(desdeAtras) {
    if (!st.sel) return;
    st.sel = null;
    document.querySelectorAll('#msgs .m.sel').forEach(function (x) { x.classList.remove('sel'); });
    $('selbar').hidden = true; $('reacbar').hidden = true;
    if (!desdeAtras && history.state && history.state.sel) { st.ignorarPop = true; history.back(); }
}
function pintarSel() {
    var ids = idsSel(), ms = ids.map(function (i) { return st.msg[i]; });
    document.querySelectorAll('#msgs .m').forEach(function (x) { x.classList.toggle('sel', !!st.sel[+x.dataset.m]); });
    $('sel-n').textContent = ids.length;
    $('selbar').hidden = false;
    var uno = ids.length === 1, m0 = ms[0], vivo = function (m) { return m && !m.elim; };
    $('selbar').querySelector('[data-s=responder]').hidden = !(uno && vivo(m0));
    $('selbar').querySelector('[data-s=info]').hidden = !(uno && m0.mio && vivo(m0));
    $('selbar').querySelector('[data-s=copiar]').hidden = !ms.some(function (m) { return vivo(m) && m.texto; });
    $('selbar').querySelector('[data-s=reenviar]').hidden = !ms.every(function (m) { return vivo(m) && !m.una_vez; });
    // Reacciones encima del mensaje (solo con uno seleccionado)
    var rb = $('reacbar');
    if (!uno || !vivo(m0)) { rb.hidden = true; return; }
    var mia = ((st.reac[m0.id] || []).filter(function (r) { return r.mio; })[0] || {}).e;
    rb.innerHTML = REAC_RAPIDAS.map(function (e) { return '<button type="button" data-e="' + e + '"' + (e === mia ? ' class="on"' : '') + '>' + e + '</button>'; }).join('') + '<button type="button" class="mas" data-mas="1" title="Más">＋</button>';
    rb.hidden = false;
    var el = $('msgs').querySelector('[data-m="' + m0.id + '"]'); if (!el) return;
    var r = el.getBoundingClientRect(), w = rb.offsetWidth, h = rb.offsetHeight;
    var top = r.top - h - 8; if (top < 70) top = Math.min(r.bottom + 8, window.innerHeight - h - 80);
    var left = m0.mio ? r.right - w : r.left; left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
    rb.style.top = top + 'px'; rb.style.left = left + 'px';
}
document.addEventListener('scroll', function () { if (!$('reacbar').hidden) $('reacbar').hidden = true; }, true);

/* Mantener presionado / clic derecho para seleccionar; con selección, tocar agrega o quita. */
(function () {
    var box = $('msgs'), t = null, x0 = 0, y0 = 0, recien = 0;
    function burbuja(e) { var m = e.target.closest('.m[data-m]'); return m && st.msg[+m.dataset.m] ? m : null; }
    box.addEventListener('touchstart', function (e) {
        var m = burbuja(e); if (!m || e.touches.length > 1) return;
        x0 = e.touches[0].clientX; y0 = e.touches[0].clientY;
        t = setTimeout(function () { t = null; recien = Date.now(); if (navigator.vibrate) navigator.vibrate(18); if (st.sel) alternarSel(+m.dataset.m); else entrarSel(+m.dataset.m); }, 430);
    }, { passive: true });
    box.addEventListener('touchmove', function (e) { if (t && (Math.abs(e.touches[0].clientX - x0) > 10 || Math.abs(e.touches[0].clientY - y0) > 10)) { clearTimeout(t); t = null; } }, { passive: true });
    box.addEventListener('touchend', function () { if (t) { clearTimeout(t); t = null; } });
    box.addEventListener('contextmenu', function (e) {
        var m = burbuja(e); if (!m) return;
        e.preventDefault(); if (Date.now() - recien < 800) return;
        if (st.sel) alternarSel(+m.dataset.m); else entrarSel(+m.dataset.m);
    });
    box.addEventListener('click', function (e) {
        if (Date.now() - recien < 500) { e.preventDefault(); e.stopPropagation(); return; }
        var reac = e.target.closest('[data-reac]');
        if (!st.sel && reac) { e.preventDefault(); e.stopPropagation(); return verReacciones(+reac.dataset.reac); }
        var cita = e.target.closest('.cita[data-ir]');
        if (!st.sel && cita) { e.preventDefault(); e.stopPropagation(); return irAMsg(+cita.dataset.ir); }
        if (!st.sel) return;
        var m = burbuja(e); e.preventDefault(); e.stopPropagation();
        if (m) alternarSel(+m.dataset.m);
    }, true);
})();
function irAMsg(id) {
    var el = $('msgs').querySelector('[data-m="' + id + '"]');
    if (!el) return toast('Ese mensaje ya no está en pantalla.');
    el.scrollIntoView({ block: 'center', behavior: 'smooth' }); el.classList.remove('brillo'); void el.offsetWidth; el.classList.add('brillo');
}

function accionSel(a) {
    var ids = idsSel(), ms = ids.map(function (i) { return st.msg[i]; });
    if (a === 'salir') return salirSel();
    if (a === 'responder') { salirSel(); return responderA(ms[0]); }
    if (a === 'info') { salirSel(); return infoMsg(ms[0]); }
    if (a === 'copiar') {
        var txt = ms.filter(function (m) { return m.texto && !m.elim; }).map(function (m) { return m.texto; }).join('\n');
        salirSel();
        (navigator.clipboard ? navigator.clipboard.writeText(txt) : Promise.reject()).then(function () { toast(ms.length > 1 ? 'Mensajes copiados' : 'Mensaje copiado'); }, function () { toast('No se pudo copiar.'); });
        return;
    }
    if (a === 'borrar') return borrarSel(ids, ms);
    if (a === 'reenviar') { salirSel(); return reenviar(ids); }
}

/* Reaccionar */
function reaccionar(id, e) {
    var l = (st.reac[id] || []).map(function (r) { return { e: r.e, n: r.n, mio: r.mio, quien: r.quien }; }), mia = l.filter(function (r) { return r.mio; })[0];
    // Se ve de una (sin esperar al servidor)
    if (mia) { mia.n--; mia.mio = false; mia.quien = mia.quien.filter(function (q) { return q !== 'Tú'; }); }
    if (!mia || mia.e !== e) { var x = l.filter(function (r) { return r.e === e; })[0]; if (x) { x.n++; x.mio = true; x.quien.push('Tú'); } else l.push({ e: e, n: 1, mio: true, quien: ['Tú'] }); }
    st.reac[id] = l.filter(function (r) { return r.n > 0; }); if (!st.reac[id].length) delete st.reac[id];
    repintarMsg(id);
    post(U + '/mensaje/' + id + '/reaccion', { emoji: e }).then(function (j) { if (!j.ok) toast(j.mensaje || 'No se pudo reaccionar.'); });
}
function masEmojis(id) {
    var todos = []; EMOJIS.forEach(function (g) { g[2].split(' ').forEach(function (e) { if (e) todos.push(e); }); });
    ventana('Reaccionar', '<div class="emo-todo">' + todos.map(function (e) { return '<button type="button" data-e="' + e + '">' + e + '</button>'; }).join('') + '</div>');
    document.querySelector('.emo-todo').onclick = function (ev) { var b = ev.target.closest('[data-e]'); if (!b) return; cerrarVentana(); reaccionar(id, b.dataset.e); };
}
function verReacciones(id) {
    var l = st.reac[id] || []; if (!l.length) return;
    var h = l.map(function (r) { return r.quien.map(function (q) { return '<div class="fila" style="display:flex;align-items:center;gap:12px;padding:8px 2px' + (q === 'Tú' ? ';cursor:pointer" data-quitar="' + esc(r.e) : '') + '"><span style="font-size:1.5rem">' + r.e + '</span><span style="flex:1">' + esc(q) + (q === 'Tú' ? '<br><small style="color:var(--txt2)">Toca para quitarla</small>' : '') + '</span></div>'; }).join(''); }).join('');
    ventana('Reacciones', h);
    var q = document.querySelector('[data-quitar]'); if (q) q.onclick = function () { cerrarVentana(); reaccionar(id, q.dataset.quitar); };
}

/* Responder */
function responderA(m) {
    if (!m) return;
    st.resp = m.id;
    var autor = m.mio ? 'Tú' : (m.autor || (st.info && st.info.nombre) || '');
    var adj = { foto: '📷 Foto', video: '🎥 Video', audio: '🎤 Audio', sticker: '🧩 Sticker' }[m.adj] || '';
    $('rp-a').textContent = autor; $('rp-a').style.color = m.mio ? 'var(--verde)' : color(autor);
    $('rp-t').textContent = adj ? adj + (m.texto ? ' · ' + m.texto : '') : (m.texto || '');
    $('resp-prev').hidden = false; $('txt').focus();
}
function quitarResp() { st.resp = null; if ($('resp-prev')) $('resp-prev').hidden = true; }

/* Info */
function infoMsg(m) {
    api(U + '/mensaje/' + m.id + '/info').then(function (j) {
        if (!j.ok) return toast('No se pudo ver la info.');
        var h = '<p style="margin-top:0;color:var(--txt2)">Enviado: <b style="color:var(--txt)">' + esc(j.enviado) + '</b></p>';
        if (!j.grupo) h += '<p>' + (j.leyeron.length ? '✓✓ <b style="color:#53bdeb">Leído</b>' : '✓ Enviado, todavía no lo ha leído') + '</p>';
        else {
            h += '<p style="margin-bottom:4px"><b style="color:#53bdeb">✓✓ Leído por (' + j.leyeron.length + ')</b></p><div style="color:var(--txt2)">' + (j.leyeron.map(esc).join('<br>') || '—') + '</div>';
            h += '<p style="margin-bottom:4px"><b>✓ Entregado a (' + j.faltan.length + ')</b></p><div style="color:var(--txt2)">' + (j.faltan.map(esc).join('<br>') || '—') + '</div>';
        }
        ventana('Info del mensaje', h);
    });
}

/* Eliminar */
function borrarSel(ids, ms) {
    var todosMios = ms.every(function (m) { return m.mio && !m.elim; });
    ventana(ids.length > 1 ? '¿Eliminar ' + ids.length + ' mensajes?' : '¿Eliminar mensaje?',
        '<p style="margin-top:0;color:var(--txt2)">' + (todosMios ? '"Eliminar para todos" lo quita del chat de todos (tus mensajes de las últimas 48 horas).' : 'Se quita solo de tu chat; los demás lo siguen viendo.') + '</p>',
        '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt sec" id="bm-mi">Eliminar para mí</button>' + (todosMios ? '<button class="bt rojo" id="bm-todos">Eliminar para todos</button>' : ''));
    function hacer(todos) {
        cerrarVentana(); salirSel();
        post(U + '/mensajes/borrar', { ids: ids, todos: todos }).then(function (j) {
            if (!j.ok) return toast(j.mensaje || 'No se pudo eliminar.');
            ids.forEach(function (id) {
                var m = st.msg[id]; if (!m) return;
                if (todos) { m.elim = true; m.texto = null; m.adj = null; m.url = null; m.una_vez = false; delete st.reac[id]; repintarMsg(id); }
                else { var w = $('msgs').querySelector('[data-w="' + id + '"]'); if (w) w.remove(); delete st.msg[id]; }
            });
            cargarChats();
        });
    }
    $('bm-mi').onclick = function () { hacer(false); };
    if ($('bm-todos')) $('bm-todos').onclick = function () { hacer(true); };
}

/* Reenviar */
function reenviar(ids) {
    var vistos = {}, chats = (st.chats.mis || []).concat(st.chats.general || []).filter(function (c) { if (vistos[c.id] || c.solicitud) return false; vistos[c.id] = 1; return true; });
    ventana('Reenviar a…', '<input type="search" id="fw-q" placeholder="Buscar chat" style="width:100%"><div class="fw-lista" id="fw-l"></div><small style="color:var(--txt2)">Hasta 5 chats.</small>',
        '<button class="bt sec" data-cerrar>Cancelar</button><button class="bt" id="fw-ok" disabled>Reenviar</button>');
    var marc = {};
    function pintar() {
        var q = normTx($('fw-q').value);
        $('fw-l').innerHTML = chats.filter(function (c) { return !q || normTx(c.nombre).indexOf(q) >= 0; }).slice(0, 80).map(function (c) {
            return '<label><input type="checkbox" value="' + c.id + '"' + (marc[c.id] ? ' checked' : '') + '>' + avatar(c, 'p', c.tipo === 'grupo') + '<span>' + esc(c.nombre) + '</span></label>';
        }).join('') || '<div style="color:var(--txt2);padding:8px">No hay chats.</div>';
    }
    pintar();
    $('fw-q').oninput = pintar;
    $('fw-l').onchange = function (e) {
        var c = e.target; if (c.type !== 'checkbox') return;
        if (c.checked && Object.keys(marc).length >= 5) { c.checked = false; return toast('Máximo 5 chats.'); }
        if (c.checked) marc[c.value] = 1; else delete marc[c.value];
        $('fw-ok').disabled = !Object.keys(marc).length; $('fw-ok').textContent = 'Reenviar' + (Object.keys(marc).length ? ' (' + Object.keys(marc).length + ')' : '');
    };
    $('fw-ok').onclick = function () {
        var b = this; b.disabled = true; b.textContent = 'Reenviando…';
        post(U + '/mensajes/reenviar', { ids: ids, chats: Object.keys(marc).map(Number) }).then(function (j) {
            cerrarVentana(); toast(j.ok ? '↪ Reenviado' : (j.mensaje || 'No se pudo reenviar.'));
            if (j.ok) { cargarChats(); if (marc[st.abierto]) traer(false); }
        });
    };
}
