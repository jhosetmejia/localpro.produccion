/* ================= 2026-11-28 (Jhoset): llamadas de voz y videollamadas (WebRTC, uno a uno, como WhatsApp) ================= */
var LL = null, llRechazadas = {};
var IC = {
    tel: '<svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
    cam: '<svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>',
    mic: '<svg viewBox="0 0 24 24"><path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/></svg>',
    girar: '<svg viewBox="0 0 24 24"><path d="M20 5h-3.17L15 3H9L7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-5 11.5V14H9v2.5L5.5 13 9 9.5V12h6V9.5l3.5 3.5-3.5 3.5z"/></svg>'
};
(function () {
    // Botones de llamar en la barra del chat (solo chats de dos personas).
    var menu = $('b-cmenu'); if (!menu) return;
    var bv = document.createElement('button'); bv.className = 'ib'; bv.id = 'b-llam-v'; bv.title = 'Videollamada'; bv.hidden = true; bv.innerHTML = IC.cam;
    var bl = document.createElement('button'); bl.className = 'ib'; bl.id = 'b-llam'; bl.title = 'Llamada de voz'; bl.hidden = true; bl.innerHTML = IC.tel;
    menu.parentNode.insertBefore(bv, menu); menu.parentNode.insertBefore(bl, menu);
    bv.onclick = function (e) { e.stopPropagation(); llamar(true); };
    bl.onclick = function (e) { e.stopPropagation(); llamar(false); };
})();
function llamadaBotones(c) {
    var ok = !!(c && c.tipo === 'directo' && window.RTCPeerConnection && navigator.mediaDevices);
    if ($('b-llam')) { $('b-llam').hidden = !ok; $('b-llam-v').hidden = !ok; }
}

/* Timbre (sin archivos de sonido: se arma con el navegador). */
var llTono = { ctx: null, t: null };
function sonar(tipo) {
    callar();
    try { var A = window.AudioContext || window.webkitAudioContext; llTono.ctx = llTono.ctx || new A(); if (llTono.ctx.resume) llTono.ctx.resume(); } catch (e) { return; }
    var cx = llTono.ctx, vol = tipo === 'entrante' ? 0.22 : 0.07;
    function beep(f, d, w) {
        var o = cx.createOscillator(), g = cx.createGain(); o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, w); g.gain.exponentialRampToValueAtTime(vol, w + 0.03); g.gain.setValueAtTime(vol, w + d - 0.06); g.gain.exponentialRampToValueAtTime(0.0001, w + d);
        o.connect(g); g.connect(cx.destination); o.start(w); o.stop(w + d + 0.05);
    }
    function ciclo() {
        var n = cx.currentTime;
        if (tipo === 'entrante') { beep(988, 0.3, n); beep(784, 0.3, n + 0.35); beep(988, 0.3, n + 0.7); beep(784, 0.3, n + 1.05); if (navigator.vibrate) navigator.vibrate([500, 250, 500]); }
        else beep(425, 1.1, n);
    }
    ciclo(); llTono.t = setInterval(ciclo, tipo === 'entrante' ? 2600 : 3500);
}
function callar() { if (llTono.t) clearInterval(llTono.t); llTono.t = null; if (navigator.vibrate) navigator.vibrate(0); }

function mmss(s) { s = Math.max(0, Math.round(s)); var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (h ? h + ':' + (m < 10 ? '0' : '') : '') + m + ':' + (x < 10 ? '0' : '') + x; }
function llMostrar(estado) {
    var l = LL, av = $('ll-av');
    $('llam').hidden = false; $('llam').classList.toggle('vid', l.video); $('llam').classList.remove('con');
    av.style.backgroundImage = l.foto ? "url('" + String(l.foto).replace(/'/g, '%27') + "')" : ''; av.textContent = l.foto ? '' : (String(l.nombre || '?').trim()[0] || '?').toUpperCase();
    $('ll-n').textContent = l.nombre || ''; $('ll-s').textContent = estado || '';
}
function llBotones(modo) {
    var h = '';
    function b(acc, ic, txt, cls) { return '<div class="ll-bt"><button type="button" class="ll-b ' + (cls || '') + '" data-ll="' + acc + '">' + ic + '</button>' + txt + '</div>'; }
    if (modo === 'entrante') h = b('rechazar', IC.tel, 'Rechazar', 'rojo colgar') + b('contestar', LL.video ? IC.cam : IC.tel, 'Contestar', 'verde');
    else {
        h = b('mic', IC.mic, 'Micrófono');
        if (LL.video) h += b('cam', IC.cam, 'Cámara') + b('girar', IC.girar, 'Girar');
        h += b('colgar', IC.tel, 'Colgar', 'rojo colgar');
    }
    $('ll-bts').innerHTML = h;
}
$('ll-bts').addEventListener('click', function (e) {
    var b = e.target.closest('[data-ll]'); if (!b || !LL) return;
    var a = b.dataset.ll;
    if (a === 'contestar') contestar();
    else if (a === 'rechazar') { post(U + '/llamada/' + LL.id + '/rechazar'); llRechazadas[LL.id] = 1; llFin(null); }
    else if (a === 'colgar') colgar();
    else if (a === 'mic' && LL.local) { var on = false; LL.local.getAudioTracks().forEach(function (t) { t.enabled = !t.enabled; on = t.enabled; }); b.classList.toggle('off', !on); }
    else if (a === 'cam' && LL.local) { var v = false; LL.local.getVideoTracks().forEach(function (t) { t.enabled = !t.enabled; v = t.enabled; }); b.classList.toggle('off', !v); }
    else if (a === 'girar') girarCamara();
});

function medios(video) {
    var a = { echoCancellation: true, noiseSuppression: true, autoGainControl: true };
    return navigator.mediaDevices.getUserMedia({ audio: a, video: video ? { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } : false })
        .catch(function (e) { if (!video) throw e; return navigator.mediaDevices.getUserMedia({ audio: a, video: false }); }); // sin cámara: sigue solo con voz
}
function verLocal() { if (LL && LL.local && LL.video) { $('ll-loc').srcObject = LL.local; $('llam').classList.add('loc'); } }
function crearPc(ice) {
    var pc = LL.pc = new RTCPeerConnection({ iceServers: ice || [] });
    LL.local.getTracks().forEach(function (t) { pc.addTrack(t, LL.local); });
    pc.onicecandidate = function (e) {
        if (!e.candidate || !LL) return;
        LL.iceSal.push(e.candidate.toJSON ? e.candidate.toJSON() : e.candidate);
        if (!LL.iceT) LL.iceT = setTimeout(function () { if (!LL) return; var l = LL.iceSal; LL.iceSal = []; LL.iceT = null; if (l.length) senal('ice', l); }, 350);
    };
    pc.ontrack = function (e) {
        var s = e.streams && e.streams[0]; if (!s) return;
        var el = LL.video ? $('ll-rem') : $('ll-aud'); if (el.srcObject !== s) { el.srcObject = s; var p = el.play(); if (p && p.catch) p.catch(function () {}); }
    };
    function estadoPc() {
        if (!LL || LL.pc !== pc) return;
        var c = pc.connectionState || pc.iceConnectionState;
        if ((c === 'connected' || c === 'completed') && !LL.conectada) {
            LL.conectada = true; callar(); $('llam').classList.remove('suena'); $('llam').classList.add('con');
            var t0 = Date.now(); LL.reloj = setInterval(function () { if (LL) $('ll-s').textContent = mmss((Date.now() - t0) / 1000); }, 1000); $('ll-s').textContent = '0:00';
        } else if (c === 'failed') { toast('No se pudo conectar la llamada. Revisa tu internet.'); colgar(); }
        else if (c === 'disconnected' && LL.conectada) $('ll-s').textContent = 'Reconectando…';
    }
    pc.onconnectionstatechange = estadoPc; pc.oniceconnectionstatechange = estadoPc;
}
function senal(tipo, datos) { if (LL && LL.id) post(U + '/llamada/' + LL.id + '/senal', { tipo: tipo, datos: JSON.stringify(datos) }); }
function vaciarCola() { var l = LL; if (!l) return; l.cola.splice(0).forEach(function (c) { l.pc.addIceCandidate(c).catch(function () {}); }); }
function procesar(s) {
    var l = LL, d; try { d = JSON.parse(s.datos); } catch (e) { return Promise.resolve(); }
    if (s.tipo === 'offer' && l.rol === 'para') {
        return l.pc.setRemoteDescription(d).then(function () { l.remoto = true; vaciarCola(); return l.pc.createAnswer(); })
            .then(function (a) { return l.pc.setLocalDescription(a); }).then(function () { senal('answer', l.pc.localDescription); });
    }
    if (s.tipo === 'answer' && l.rol === 'de' && !l.remoto) {
        return l.pc.setRemoteDescription(d).then(function () { l.remoto = true; vaciarCola(); });
    }
    if (s.tipo === 'ice') { (Array.isArray(d) ? d : [d]).forEach(function (c) { if (l.remoto) l.pc.addIceCandidate(c).catch(function () {}); else l.cola.push(c); }); }
    return Promise.resolve();
}
function sondear() {
    if (!LL || !LL.id) return;
    var l = LL;
    api(U + '/llamada/' + l.id + '?desde=' + (l.pc ? l.desde : 0)).then(function (j) {
        if (LL !== l) return;
        if (j.ok) {
            if (j.estado === 'aceptada' && l.rol === 'de' && !l.contestada) { l.contestada = true; callar(); $('ll-s').textContent = 'Conectando…'; }
            if (['rechazada', 'perdida', 'terminada'].indexOf(j.estado) >= 0) {
                return llFin(j.estado === 'terminada' ? 'Llamada terminada' : l.rol === 'de' ? 'No contestó' : 'Llamada perdida');
            }
            if (l.pc && j.senales && j.senales.length) {
                var p = Promise.resolve();
                j.senales.forEach(function (s) { p = p.then(function () { if (LL === l) { l.desde = s.id; return procesar(s); } }); });
                return p.then(function () { if (LL === l) l.t = setTimeout(sondear, 900); }, function () { if (LL === l) l.t = setTimeout(sondear, 900); });
            }
        }
        l.t = setTimeout(sondear, 1000);
    }).catch(function () { if (LL === l) l.t = setTimeout(sondear, 2000); });
}
function llamar(video) {
    if (LL) return toast('Ya estás en una llamada.');
    if (!st.info || st.info.tipo !== 'directo') return;
    if (!window.RTCPeerConnection || !navigator.mediaDevices) return toast('Este navegador no permite llamadas. Usa Chrome o la app.');
    LL = { video: !!video, rol: 'de', desde: 0, cola: [], iceSal: [], chat: st.abierto, nombre: st.info.nombre, foto: st.info.foto || (st.info.otro || {}).foto, estado: 'llamando' };
    var l = LL; llMostrar('Preparando…'); llBotones('curso');
    medios(video).then(function (s) {
        if (LL !== l) { s.getTracks().forEach(function (t) { t.stop(); }); return; }
        l.local = s; if (!s.getVideoTracks().length) { l.video = false; llMostrar('Preparando…'); llBotones('curso'); } verLocal();
        return post(U + '/llamada', { chat_id: l.chat, video: l.video }).then(function (j) {
            if (LL !== l) { if (j.ok) post(U + '/llamada/' + j.id + '/colgar'); return; }
            if (!j.ok) { toast(j.mensaje || 'No se pudo llamar.'); return llFin(null); }
            l.id = j.id; crearPc(j.ice);
            $('ll-s').textContent = 'Llamando…'; $('llam').classList.add('suena'); sonar('saliente');
            l.pc.createOffer().then(function (o) { return l.pc.setLocalDescription(o); }).then(function () { senal('offer', l.pc.localDescription); });
            l.vence = setTimeout(function () { if (LL === l && !l.contestada) colgar(); }, 46000);
            sondear();
        });
    }).catch(function () {
        toast(video ? 'Para la videollamada permite la cámara y el micrófono.' : 'Para llamar permite el micrófono.'); llFin(null);
    });
}
function contestar() {
    var l = LL; if (!l || l.rol !== 'para') return;
    callar(); $('ll-s').textContent = 'Conectando…'; llBotones('curso');
    medios(l.video).then(function (s) {
        if (LL !== l) { s.getTracks().forEach(function (t) { t.stop(); }); return; }
        l.local = s; verLocal();
        return post(U + '/llamada/' + l.id + '/aceptar').then(function (j) {
            if (LL !== l) return;
            if (!j.ok) { toast(j.mensaje || 'La llamada ya terminó.'); return llFin(null); }
            l.contestada = true; crearPc(l.ice);
        });
    }).catch(function () { toast('Permite el micrófono para contestar.'); post(U + '/llamada/' + l.id + '/rechazar'); llFin(null); });
}
function colgar() { if (!LL) return; if (LL.id) post(U + '/llamada/' + LL.id + '/colgar'); llFin(LL.conectada ? 'Llamada terminada' : null); }
function girarCamara() {
    var l = LL; if (!l || !l.local || !l.pc) return;
    l.frente = l.frente === 'environment' ? 'user' : 'environment';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: l.frente, width: { ideal: 640 }, height: { ideal: 480 } } }).then(function (s) {
        var nt = s.getVideoTracks()[0], snd = l.pc.getSenders().filter(function (x) { return x.track && x.track.kind === 'video'; })[0];
        if (!nt || !snd) return;
        snd.replaceTrack(nt);
        l.local.getVideoTracks().forEach(function (t) { t.stop(); l.local.removeTrack(t); }); l.local.addTrack(nt);
        $('ll-loc').style.transform = l.frente === 'user' ? 'scaleX(-1)' : 'none'; $('ll-loc').srcObject = l.local;
    }).catch(function () { toast('No se pudo cambiar la cámara.'); });
}
function llFin(texto) {
    var l = LL; if (!l) return; LL = null;
    callar(); clearTimeout(l.t); clearTimeout(l.vence); clearTimeout(l.iceT); clearInterval(l.reloj);
    try { if (l.pc) l.pc.close(); } catch (e) {}
    if (l.local) l.local.getTracks().forEach(function (t) { t.stop(); });
    ['ll-rem', 'll-loc', 'll-aud'].forEach(function (i) { $(i).srcObject = null; });
    $('llam').classList.remove('suena', 'con', 'loc');
    if (texto) { $('ll-s').textContent = texto; $('ll-bts').innerHTML = ''; setTimeout(function () { if (!LL) $('llam').hidden = true; }, 1600); }
    else $('llam').hidden = true;
    if (st.abierto) setTimeout(function () { traer(false); }, 800);
}
/* ¿Me están llamando? Cada 2 s con la app a la vista (si está cerrada, llega el aviso del celular). */
function revisarEntrante() {
    if (LL || document.hidden) return;
    api(U + '/llamada/entrante').then(function (j) {
        if (!j.ok || !j.llamada || LL || llRechazadas[j.llamada.id]) return;
        var c = j.llamada;
        LL = { id: c.id, video: !!c.video, rol: 'para', desde: 0, cola: [], iceSal: [], chat: c.chat_id, nombre: c.de.nombre, foto: c.de.foto, ice: c.ice, estado: 'llamando' };
        llMostrar(c.video ? 'Videollamada de LP Chat…' : 'Llamada de LP Chat…'); llBotones('entrante');
        $('llam').classList.add('suena'); sonar('entrante');
        sondear();
    }).catch(function () {});
}
setInterval(revisarEntrante, 2000);
revisarEntrante();
document.addEventListener('visibilitychange', function () { if (!document.hidden) revisarEntrante(); });
window.addEventListener('pagehide', function () {
    if (LL && LL.id && navigator.sendBeacon) { var f = new FormData(); f.append('_token', CSRF); navigator.sendBeacon(U + '/llamada/' + LL.id + '/' + (LL.rol === 'para' && !LL.contestada ? 'rechazar' : 'colgar'), f); }
});
if (/[?&]llamada=/.test(location.search)) history.replaceState(null, '', '/chat');
