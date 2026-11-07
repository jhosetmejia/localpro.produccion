/* LocalProCut · editor de video en el navegador (celular primero).
   Todo pasa en el equipo del usuario: los videos no se suben al servidor. */
import * as MB from './mediabunny.min.js';
import { Motor, TRANSICIONES } from './motor.js';
import * as SUB from './subtitulos.js';
import * as BEL from './belleza.js';

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const fmt = (t) => { t = Math.max(0, t); const m = Math.floor(t / 60), s = Math.floor(t % 60), d = Math.floor((t % 1) * 10); return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${d}`; };
const esc = (s) => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uid = () => Math.random().toString(36).slice(2, 10) + Date.now().toString(36);

const IOS = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const ASPECTOS = { '9:16': [9, 16], '16:9': [16, 9], '1:1': [1, 1], '4:5': [4, 5] };
const RESOL = { 720: '720p', 1080: '1080p · Full HD', 1440: '2K', 2160: '4K' };

const S = {
  clips: [], subs: [], segs: [],
  sub: { estilo: 'tiktok', tam: 'M', pos: 0.72, color: '#a6f34a', mayus: false, palabras: 3 },
  belleza: { on: false, suave: 0.45, aclara: 0.25, brillo: 0, preset: 'natural' },
  aspecto: '9:16',
  musica: [], silenciar: false, selM: null,
  t: 0, playing: false, sel: null, tab: null, pps: 56,
  dirty: true,
};

let motor;
const vista = $('#cutLienzo');
const vctx = vista.getContext('2d');

/* ---------- avisos con estilo LocalPro (nada de alert/confirm) ---------- */
function aviso(msg, tipo = '') {
  const d = document.createElement('div');
  d.className = 'cut-toast ' + tipo;
  d.textContent = msg;
  $('#cutToasts').appendChild(d);
  setTimeout(() => d.classList.add('fuera'), 3200);
  setTimeout(() => d.remove(), 3700);
}
function preguntar(titulo, texto, si = 'Sí', no = 'Cancelar') {
  return new Promise((ok) => {
    const m = $('#cutPregunta');
    $('h3', m).textContent = titulo; $('p', m).textContent = texto;
    $('[data-si]', m).textContent = si; $('[data-no]', m).textContent = no;
    m.hidden = false;
    const fin = (v) => { m.hidden = true; ok(v); };
    $('[data-si]', m).onclick = () => fin(true);
    $('[data-no]', m).onclick = () => fin(false);
  });
}

/* ---------- volumen (iPhone ignora el volumen de los elementos: se usa WebAudio) ---------- */
let actx = null;
function audioCtx() {
  if (!actx) { try { actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { actx = null; } }
  if (actx && actx.state === 'suspended') actx.resume().catch(() => {});
  return actx;
}
function volumen(el, v) {
  if (!el) return;
  if (!el._g && v !== 1 && !el._gFallo) {
    const a = audioCtx();
    try { const src = a.createMediaElementSource(el); el._g = a.createGain(); src.connect(el._g).connect(a.destination); } catch (e) { el._gFallo = true; }
  }
  if (el._g) el._g.gain.value = v; else { try { el.volume = Math.max(0, Math.min(1, v)); } catch (e) { /* nada */ } }
}

/* ---------- deshacer / rehacer ---------- */
const REG = new Map();
function reg(o) { REG.set(o.id, o); return o; }
const HIST = { pila: [], pos: -1, aplicando: false };
function foto() {
  return JSON.stringify({
    clips: S.clips.map(c => ({ id: c.id, in: c.in, out: c.out, mute: !!c.mute, vol: c.vol ?? 1, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null, trans: c.trans })),
    musica: S.musica.map(m => ({ id: m.id, start: m.start, in: m.in, out: m.out ?? null, vol: m.vol ?? 1, fadeIn: !!m.fadeIn, fadeOut: !!m.fadeOut })),
    subs: S.subs, segs: S.segs, sub: S.sub, belleza: S.belleza, aspecto: S.aspecto, silenciar: !!S.silenciar,
  });
}
function registrarHist() {
  if (HIST.aplicando) return;
  const f = foto();
  if (HIST.pila[HIST.pos] === f) return;
  HIST.pila = HIST.pila.slice(0, HIST.pos + 1);
  HIST.pila.push(f);
  if (HIST.pila.length > 80) HIST.pila.shift();
  HIST.pos = HIST.pila.length - 1;
  botonesHist();
}
function botonesHist() {
  $('#cutDeshacer').disabled = HIST.pos <= 0;
  $('#cutRehacer').disabled = HIST.pos >= HIST.pila.length - 1;
}
function aplicarFoto(f) {
  const d = JSON.parse(f);
  HIST.aplicando = true;
  if (S.playing) pausar();
  S.clips = d.clips.map(m => Object.assign(REG.get(m.id), m, { trans: { ...m.trans } })).filter(Boolean);
  S.musica = d.musica.map(m => Object.assign(REG.get(m.id), m)).filter(Boolean);
  S.subs = JSON.parse(JSON.stringify(d.subs)); S.segs = d.segs;
  Object.assign(S.sub, d.sub); Object.assign(S.belleza, d.belleza);
  const cambioAsp = S.aspecto !== d.aspecto;
  S.aspecto = d.aspecto; S.silenciar = d.silenciar;
  if (!S.clips.some(c => c.id === S.sel)) S.sel = null;
  if (!S.musica.some(m => m.id === S.selM)) S.selM = null;
  S.clips.forEach(c => { if (c.tipo === 'video' && !c.el) cargarVideo(c).then(() => { S.dirty = true; }); });
  if (cambioAsp) ajustarVista();
  $('#cutVacio').hidden = !!S.clips.length;
  irA(S.t); pintarLinea(); pintarPanel(); guardarDB();
  HIST.aplicando = false;
  botonesHist();
}
function deshacer() { if (HIST.pos <= 0) return; HIST.pos--; aplicarFoto(HIST.pila[HIST.pos]); aviso('Deshecho ↩'); }
function rehacer() { if (HIST.pos >= HIST.pila.length - 1) return; HIST.pos++; aplicarFoto(HIST.pila[HIST.pos]); aviso('Rehecho ↪'); }

/* ---------- tiempos de la línea ---------- */
/* ---------- velocidad (normal y en curva, como CapCut) ---------- */
// Cada curva: multiplicador de velocidad a lo largo del clip (se suaviza entre puntos)
const CURVAS = {
  montaje: { n: 'Montaje', p: [1, 1, 3.5, 3.5, 1, 1] },
  heroe: { n: 'Héroe', p: [3, 3, 0.35, 0.35, 3, 3] },
  bala: { n: 'Bala', p: [5, 5, 0.25, 5, 5] },
  salto: { n: 'Salto', p: [0.6, 0.6, 5, 0.6, 0.6] },
  entrada: { n: 'Entrada rápida', p: [5, 3, 1, 1, 1] },
  salida: { n: 'Salida rápida', p: [1, 1, 1, 3, 5] },
  pulso: { n: 'Pulso', p: [1, 4, 0.5, 4, 0.5, 4, 1] },
};
function velEn(c, u) {
  let v = 1;
  const cu = c.tipo === 'video' && c.curva && CURVAS[c.curva];
  if (cu) {
    const p = cu.p, x = Math.max(0, Math.min(1, u)) * (p.length - 1);
    const i = Math.min(p.length - 2, Math.floor(x)), f = x - i, sm = (1 - Math.cos(f * Math.PI)) / 2;
    v = p[i] + (p[i + 1] - p[i]) * sm;
  }
  return (c.tipo === 'video' ? (c.vel || 1) : 1) * v;
}
const NT = 256;
function tablaVel(c) {
  const k = (c.vel || 1) + '|' + (c.curva || '') + '|' + c.tipo;
  if (c._tk === k) return c._t;
  const cum = [0];
  for (let i = 1; i <= NT; i++) cum.push(cum[i - 1] + velEn(c, (i - 0.5) / NT) / NT);
  c._t = { cum, media: cum[NT] }; c._tk = k;
  return c._t;
}
function largo(c) { return Math.max(0.1, (c.out - c.in) / tablaVel(c).media); }
// Tiempo del archivo original que corresponde a "lt" segundos dentro del clip en la línea
function tOrigen(c, lt) {
  const T = tablaVel(c), u = Math.max(0, Math.min(1, lt / largo(c))) * NT;
  const i = Math.min(NT - 1, Math.floor(u)), f = u - i;
  const acum = T.cum[i] + (T.cum[i + 1] - T.cum[i]) * f;
  return c.in + (c.out - c.in) * acum / T.media;
}
/* ---------- zoom por momentos (como los fotogramas clave de CapCut) ---------- */
// Si se hace zoom a mitad del clip, se acerca suave desde ese momento; antes queda como estaba.
const RAMPA = 0.35;
function encuadreEn(c, lt) {
  const k = c.kf;
  if (!k || !k.length) return { z: c.z || 1, x: c.x || 0, y: c.y || 0 };
  if (lt <= k[0].t) return k[0];
  const u = k[k.length - 1];
  if (lt >= u.t) return u;
  for (let i = 0; i < k.length - 1; i++) {
    if (lt < k[i + 1].t) {
      const a = k[i], b = k[i + 1], f = (lt - a.t) / Math.max(0.001, b.t - a.t), s = f * f * (3 - 2 * f);
      return { z: a.z + (b.z - a.z) * s, x: a.x + (b.x - a.x) * s, y: a.y + (b.y - a.y) * s };
    }
  }
  return u;
}
function ltClip(c) { const L = lay(), i = S.clips.indexOf(c); return Math.max(0, S.t - (L.start[i] || 0)); }
// Punto que se edita en el momento actual (lo crea si hace falta)
function puntoZoom(c) {
  const lt = ltClip(c);
  if (!c.kf?.length && lt < 0.15) return c; // al inicio del clip: zoom en todo el clip
  if (!c.kf?.length) c.kf = [{ t: 0, z: c.z || 1, x: c.x || 0, y: c.y || 0 }];
  let k = c.kf.find(q => Math.abs(q.t - lt) < 0.12);
  if (k) return k;
  const antes = { ...encuadreEn(c, Math.max(0, lt - RAMPA)) }, ahora = { ...encuadreEn(c, lt) };
  if (lt - RAMPA > 0.05 && !c.kf.some(q => q.t < lt && q.t > lt - RAMPA - 0.05)) c.kf.push({ t: lt - RAMPA, z: antes.z, x: antes.x, y: antes.y });
  k = { t: lt, z: ahora.z, x: ahora.x, y: ahora.y };
  c.kf.push(k);
  c.kf.sort((a, b) => a.t - b.t);
  return k;
}

function lay() {
  const n = S.clips.length, start = [], tr = [];
  let t = 0;
  for (let i = 0; i < n; i++) {
    start.push(t);
    const c = S.clips[i], sig = S.clips[i + 1];
    let d = 0;
    if (sig && c.trans.tipo !== 'ninguna') d = Math.min(c.trans.dur, largo(c) / 2, largo(sig) / 2);
    tr.push(d);
    t += largo(c) - d;
  }
  const total = n ? start[n - 1] + largo(S.clips[n - 1]) : 0;
  return { start, tr, total };
}
function activos(t, L = lay()) {
  const a = [];
  S.clips.forEach((c, i) => { if (t >= L.start[i] && t < L.start[i] + largo(c)) a.push(i); });
  if (!a.length && S.clips.length && t >= L.total - 1e-6) a.push(S.clips.length - 1);
  return a.slice(0, 2);
}

/* ---------- tamaño de salida ---------- */
function tamSalida(corto) {
  const [a, b] = ASPECTOS[S.aspecto];
  let W, H;
  if (a <= b) { W = corto; H = Math.round(corto * b / a); } else { H = corto; W = Math.round(corto * a / b); }
  return { W: W - (W % 2), H: H - (H % 2) };
}
function ajustarVista() {
  const { W, H } = tamSalida(Math.min(720, Math.round(540 * Math.min(2, devicePixelRatio || 1))));
  vista.width = W; vista.height = H;
  motor.tamano(W, H);
  S.dirty = true;
}

/* ---------- dibujar un cuadro (vista previa y exportado usan lo mismo) ---------- */
function dibujarCuadro(t, fuentes, W, H, ctx) {
  const L = lay();
  const act = activos(t, L);
  if (!act.length) { motor.negro(); }
  else {
    act.forEach((i, k) => {
      const f = fuentes(i);
      const cc = S.clips[i];
      motor.clip(k, f?.src, f?.w, f?.h, S.belleza.on ? f?.mascara : null, S.belleza, encuadreEn(cc, t - L.start[i]));
    });
    if (act.length === 2) {
      const c = S.clips[act[0]];
      const p = (t - L.start[act[1]]) / Math.max(0.01, L.tr[act[0]]);
      motor.mezclar((TRANSICIONES.find(x => x.id === c.trans.tipo) || TRANSICIONES[2]).t, p);
    } else motor.mezclar(2, 0);
  }
  ctx.drawImage(motor.canvas, 0, 0, W, H);
  if (S.subs.length) SUB.dibujar(ctx, W, H, t, S.subs, S.sub);
}

/* ---------- vista previa ---------- */
function fuentePrevia(i) {
  const c = S.clips[i];
  if (c.tipo === 'imagen') return { src: c.bmp, w: c.w, h: c.h, mascara: c.mascara };
  const v = c.el;
  if (!v || v.readyState < 2) return null;
  if (S.belleza.on && BEL.listo()) {
    const ahora = performance.now();
    // Detectar la cara en cada cuadro nuevo para que el filtro quede pegado al rostro.
    // Solo si el celular es lento (más de 40 ms por detección) se salta un cuadro de por medio.
    const cuadroNuevo = c.ultT !== v.currentTime;
    const saltar = S.playing && BEL.ultimoMs > 40 && (c.par = !c.par);
    if (cuadroNuevo && !saltar) { c.ultT = v.currentTime; c.ultDet = ahora; c.mascara ||= document.createElement('canvas'); BEL.mascara(v, c.mascara); }
  }
  return { src: v, w: v.videoWidth, h: v.videoHeight, mascara: c.mascara };
}
function sincronizar() {
  const L = lay();
  S.clips.forEach((c, i) => {
    if (c.tipo !== 'video' || !c.el) return;
    const v = c.el;
    const act = S.t >= L.start[i] && S.t < L.start[i] + largo(c);
    const loc = tOrigen(c, S.t - L.start[i]);
    const vel = Math.max(0.1, Math.min(8, velEn(c, (S.t - L.start[i]) / largo(c))));
    if (Math.abs(v.playbackRate - vel) > 0.01) { try { v.playbackRate = vel; } catch (e) { /* fuera de rango */ } }
    v.muted = !!c.mute || !!S.silenciar;
    volumen(v, c.vol ?? 1);
    if (act && S.playing) {
      if (v.paused) { v.currentTime = loc; v.play().catch(() => {}); }
      else if (Math.abs(v.currentTime - loc) > 0.3 * Math.max(1, vel)) v.currentTime = loc;
    } else {
      if (!v.paused) v.pause();
      if (act && Math.abs(v.currentTime - loc) > 0.03 && !v.seeking) v.currentTime = loc;
    }
  });
  for (const m of S.musica) {
    const a = m.el; if (!a) continue;
    const act = S.playing && S.t >= m.start && S.t < Math.min(m.start + largoM(m), L.total);
    const loc = m.in + (S.t - m.start);
    volumen(a, m.vol ?? 1);
    if (act) { if (a.paused) { a.currentTime = loc; a.play().catch(() => {}); } else if (Math.abs(a.currentTime - loc) > 0.3) a.currentTime = loc; }
    else if (!a.paused) a.pause();
  }
}
let ignorarScroll = false;
function bucle(ahora) {
  if (S.playing) {
    const L = lay();
    S.t = S.t0 + (ahora - S.inicio) / 1000;
    if (S.hasta != null && S.t >= S.hasta) { S.t = S.hasta; pausar(); }
    if (S.t >= L.total) { S.t = L.total; pausar(); }
    sincronizar();
    moverLinea();
    S.dirty = true;
  }
  // Si el celular le quitó la parte gráfica y no la devolvió, se arma de nuevo
  if (motor && !S.exportando && motor.gl.isContextLost()) {
    motor._perdidoDesde ||= ahora;
    if (ahora - motor._perdidoDesde > 2500) { try { motor = new Motor(); ajustarVista(); } catch (e) { /* nada */ } }
  }
  if (S.dirty && motor && !S.exportando) {
    S.dirty = false;
    if (S.clips.length) dibujarCuadro(S.t, fuentePrevia, vista.width, vista.height, vctx);
    else { vctx.fillStyle = '#000'; vctx.fillRect(0, 0, vista.width, vista.height); }
    $('#cutTiempo').textContent = `${fmt(S.t).slice(0, 5)} / ${fmt(lay().total).slice(0, 5)}`;
  }
  requestAnimationFrame(bucle);
}
function reproducir(hasta = null) {
  if (!S.clips.length) return;
  const L = lay();
  if (S.t >= L.total - 0.05) S.t = 0;
  audioCtx();
  S.playing = true; S.t0 = S.t; S.inicio = performance.now(); S.hasta = hasta;
  $('#cutPlay').classList.add('on');
  sincronizar();
}
function pausar() {
  S.playing = false; S.hasta = null;
  $('#cutPlay').classList.remove('on');
  sincronizar();
  S.dirty = true;
}
function irA(t) {
  S.t = Math.max(0, Math.min(t, lay().total));
  sincronizar();
  S.dirty = true;
}

/* ---------- línea de tiempo ---------- */
const linea = $('#cutLinea');
function moverLinea() {
  ignorarScroll = true;
  linea.scrollLeft = S.t * S.pps;
  requestAnimationFrame(() => { ignorarScroll = false; });
}
linea.addEventListener('scroll', () => {
  if (ignorarScroll || S.playing) return;
  irA(linea.scrollLeft / S.pps);
});
linea.addEventListener('pointerdown', () => { if (S.playing) pausar(); });

function pintarLinea() {
  const L = lay();
  const pad = linea.clientWidth / 2;
  const cont = $('#cutLineaIn');
  const ancho = Math.max(L.total, ...S.musica.map(finM), ...S.subs.map(x => x.end)) * S.pps;
  cont.style.width = (ancho + pad * 2 + 60) + 'px';
  // Regla de tiempos
  const regla = $('#cutRegla');
  const paso = [0.5, 1, 2, 5, 10, 30, 60].find(x => x * S.pps >= 70) || 60;
  let rh = '';
  for (let t = 0; t <= Math.max(L.total, 1) + paso; t += paso) rh += `<span style="left:${pad + t * S.pps}px">${fmt(t).slice(0, 5)}</span><i style="left:${pad + (t + paso / 2) * S.pps}px"></i>`;
  regla.innerHTML = rh;
  // Columna izquierda (como CapCut): silenciar clip y los íconos de las filas
  const izq = $('#cutIzq');
  izq.style.left = Math.max(4, pad - 116) + 'px';
  izq.innerHTML = `<button type="button" class="cut-sil ${S.silenciar ? 'on' : ''}" data-silenciar>${S.silenciar ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="m17 9 4 6M21 9l-4 6"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="M16.5 8.5a5 5 0 0 1 0 7"/></svg>'}<small>${S.silenciar ? 'Activar<br>sonido' : 'Silenciar<br>clip'}</small></button>
    <span class="cut-fila-ic" style="top:68px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18V5l9-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="15" cy="16" r="3"/></svg></span><span class="cut-fila-ic" style="top:102px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5h14M12 5v15"/></svg></span>`;


  const pista = $('#cutPistaV');
  pista.innerHTML = '';
  S.clips.forEach((c, i) => {
    const b = document.createElement('div');
    b.className = 'cut-clip' + (S.sel === c.id ? ' sel' : '') + (c.mute || S.silenciar ? ' mudo' : '');
    b.style.left = (pad + L.start[i] * S.pps) + 'px';
    b.style.width = Math.max(18, largo(c) * S.pps - 2) + 'px';
    if (c.tira) {
      b.style.backgroundImage = `url(${c.tira})`;
      const anchoTotal = (c.tipo === 'imagen' ? largo(c) : c.dur / tablaVel(c).media) * S.pps;
      b.style.backgroundSize = c.tipo === 'imagen' ? 'auto 100%' : `${anchoTotal}px 100%`;
      b.style.backgroundPositionX = c.tipo === 'imagen' ? '0' : `${-c.in / tablaVel(c).media * S.pps}px`;
      b.style.backgroundRepeat = c.tipo === 'imagen' ? 'repeat-x' : 'no-repeat';
    }
    const etqVel = c.tipo === 'video' && c.curva ? '◷ ' + CURVAS[c.curva].n : (c.tipo === 'video' && (c.vel || 1) !== 1 ? '◷ ' + c.vel + 'x' : '');
    b.innerHTML = (etqVel ? `<em>${etqVel}</em>` : '') + `<span>${c.tipo === 'imagen' ? '🖼 ' : (c.mute ? '🔇 ' : '')}${fmt(largo(c)).slice(0, 5)}</span>`;
    b.onclick = (e) => { e.stopPropagation(); S.sel = c.id; S.selM = null; abrirTab(['editar', 'velocidad', 'belleza'].includes(S.tab) ? S.tab : 'editar'); pintarLinea(); };
    pista.appendChild(b);
    if (i < S.clips.length - 1) {
      const tb = document.createElement('button');
      tb.type = 'button';
      tb.className = 'cut-rombo' + (c.trans.tipo !== 'ninguna' ? ' on' : '');
      tb.title = 'Transición';
      tb.style.left = (pad + (L.start[i + 1] + L.tr[i] / 2) * S.pps - 12) + 'px';
      tb.innerHTML = c.trans.tipo !== 'ninguna' ? '⧓' : '|';
      tb.onclick = (e) => { e.stopPropagation(); S.sel = c.id; S.selM = null; abrirTab('transicion'); pintarLinea(); };
      pista.appendChild(tb);
    }
  });
  const mas = document.createElement('button');
  mas.type = 'button';
  mas.className = 'cut-mas';
  mas.style.left = (pad + L.total * S.pps + 8) + 'px';
  mas.innerHTML = '+';
  mas.onclick = () => $('#cutArchivo').click();
  pista.appendChild(mas);

  // Fila de audio
  const pa = $('#cutPistaA');
  pa.innerHTML = '';
  S.musica.forEach((m) => {
    const b = document.createElement('div');
    b.className = 'cut-mus' + (S.selM === m.id ? ' sel' : '');
    b.style.left = (pad + m.start * S.pps) + 'px';
    b.style.width = Math.max(14, Math.min(largoM(m), Math.max(0.1, L.total - m.start)) * S.pps - 2) + 'px';
    b.textContent = '♪ ' + m.nombre.replace(/\.[^.]+$/, '');
    b.onclick = (e) => { e.stopPropagation(); S.selM = m.id; S.sel = null; abrirTab('audio'); pintarLinea(); };
    pa.appendChild(b);
  });
  const addA = document.createElement('button');
  addA.type = 'button'; addA.className = 'cut-agregar';
  const finMus = S.musica.length ? Math.max(...S.musica.map(m => Math.min(finM(m), L.total))) : 0;
  addA.style.left = (pad + finMus * S.pps + (S.musica.length ? 6 : 0)) + 'px';
  addA.innerHTML = '+ Agregar audio';
  addA.onclick = (e) => { e.stopPropagation(); S.selM = null; S.sel = null; abrirTab('audio'); pintarLinea(); };
  pa.appendChild(addA);

  // Fila de texto (subtítulos y textos)
  const ps = $('#cutPistaT');
  ps.innerHTML = '';
  S.subs.forEach((x) => {
    const b = document.createElement('div');
    b.className = 'cut-sub';
    b.style.left = (pad + x.start * S.pps) + 'px';
    b.style.width = Math.max(10, (x.end - x.start) * S.pps - 2) + 'px';
    b.textContent = x.text;
    b.onclick = (e) => { e.stopPropagation(); editarSub(x); };
    ps.appendChild(b);
  });
  const addT = document.createElement('button');
  addT.type = 'button'; addT.className = 'cut-agregar';
  addT.style.left = (pad + (S.subs.length ? Math.max(...S.subs.map(x => x.end)) * S.pps + 6 : 0)) + 'px';
  addT.innerHTML = '+ Agregar texto';
  addT.onclick = (e) => { e.stopPropagation(); nuevoTexto(); };
  ps.appendChild(addT);
  moverLinea();
  S.dirty = true;
}

// Silenciar clip: responde al primer toque aunque esté dentro de la línea que se desliza
(() => {
  const izq = $('#cutIzq');
  let ini = null;
  const alternar = () => { S.silenciar = !S.silenciar; sincronizar(); pintarLinea(); guardar(); aviso(S.silenciar ? 'Sonido original silenciado 🔇' : 'Sonido original activado 🔈'); };
  izq.addEventListener('pointerdown', (e) => { if (e.target.closest('[data-silenciar]')) { ini = { x: e.clientX, y: e.clientY, t: Date.now() }; e.stopPropagation(); } }, true);
  izq.addEventListener('pointerup', (e) => {
    if (!ini || !e.target.closest('[data-silenciar]')) { ini = null; return; }
    const mov = Math.hypot(e.clientX - ini.x, e.clientY - ini.y);
    ini = null;
    if (mov < 14) { e.preventDefault(); e.stopPropagation(); izq._ultimo = Date.now(); alternar(); }
  }, true);
  izq.addEventListener('click', (e) => { if (e.target.closest('[data-silenciar]')) { e.stopPropagation(); if (Date.now() - (izq._ultimo || 0) > 600) alternar(); } });
})();

/* ---------- música ---------- */
function largoM(m) { return Math.max(0.1, (m.out ?? m.dur ?? 0) - (m.in || 0)); }
function finM(m) { return m.start + largoM(m); }
function prepararMusica(m) {
  return new Promise((ok, mal) => {
    m.url = URL.createObjectURL(m.file);
    const a = document.createElement('audio');
    a.preload = 'auto'; a.src = m.url;
    a.onloadedmetadata = () => { m.el = a; if (!m.dur || !isFinite(m.dur)) m.dur = isFinite(a.duration) ? a.duration : 30; ok(); };
    a.onerror = () => mal(new Error('No se pudo abrir ' + m.nombre));
    $('#cutVideos').appendChild(a);
  });
}
async function agregarMusica(f, nombre = null, msg = 'Música agregada 🎵') {
  if (!f) return;
  const L = lay();
  const m = reg({ id: uid(), fileId: uid(), file: f, nombre: nombre || f.name || 'Música', start: S.t >= L.total - 0.2 ? 0 : S.t, in: 0, dur: 0, vol: 1, fadeIn: false, fadeOut: true });
  try {
    await prepararMusica(m);
    S.musica.push(m);
    DB.put('archivos', m.fileId, f);
    S.selM = m.id; S.sel = null;
    abrirTab('audio'); pintarLinea(); guardar();
    aviso(msg);
  } catch (e) { aviso(e.message, 'mal'); }
}
$('#cutMusica').addEventListener('change', (e) => { agregarMusica(e.target.files[0]); e.target.value = ''; });
// Extraer audio (como CapCut): toma el sonido de un video del celular y lo pone en la fila de audio
$('#cutExtraer').addEventListener('change', (e) => {
  const f = e.target.files[0]; e.target.value = '';
  if (f) agregarMusica(f, 'Audio de ' + f.name.replace(/\.[^.]+$/, ''), 'Audio extraído del video 🎬');
});
// Extraer audio de un clip que ya está en la línea: el sonido pasa a la fila de audio y el clip queda en silencio
async function extraerDeClip(c) {
  if (!c || c.tipo !== 'video') return;
  const L = lay(), i = S.clips.indexOf(c);
  const m = reg({ id: uid(), fileId: c.fileId, file: c.file, nombre: 'Audio · ' + (c.nombre || 'video').replace(/\.[^.]+$/, ''), start: L.start[i], in: c.in, out: c.out, dur: c.dur, vol: c.vol ?? 1, fadeIn: false, fadeOut: false });
  try {
    await prepararMusica(m);
    S.musica.push(m);
    c.mute = true;
    S.selM = m.id; S.sel = null;
    abrirTab('audio'); pintarLinea(); sincronizar(); guardar();
    aviso((c.vel || 1) !== 1 || c.curva ? 'Audio extraído (suena a velocidad normal) 🎬' : 'Audio extraído: ya lo puede mover o cambiarle el volumen 🎬');
  } catch (e) { aviso('No se pudo extraer el audio de este video', 'mal'); }
}
function nuevoTexto() {
  if (!S.clips.length) return aviso('Primero agregue un video');
  const x = SUB.repartir({ id: uid(), start: S.t, end: Math.min(S.t + 2, Math.max(lay().total, S.t + 0.5)), text: 'Escriba aquí' });
  S.subs.push(x); S.subs.sort((a, b) => a.start - b.start);
  pintarLinea(); editarSub(x);
}

/* ---------- agregar videos y fotos ---------- */
async function tiraVideo(c) {
  try {
    const input = new MB.Input({ source: new MB.BlobSource(c.file), formats: MB.ALL_FORMATS });
    const tr = await input.getPrimaryVideoTrack();
    if (!tr || !(await tr.canDecode())) throw 0;
    const n = Math.max(2, Math.min(24, Math.ceil(c.dur)));
    const alto = 72, ancho = Math.round(alto * tr.displayWidth / tr.displayHeight);
    const sink = new MB.CanvasSink(tr, { width: ancho, height: alto, fit: 'fill' });
    const tira = document.createElement('canvas');
    tira.width = ancho * n; tira.height = alto;
    const tc = tira.getContext('2d');
    const marcas = Array.from({ length: n }, (_, k) => (k + 0.5) * c.dur / n);
    let k = 0;
    for await (const w of sink.canvasesAtTimestamps(marcas)) {
      if (w) tc.drawImage(w.canvas, k * ancho, 0, ancho, alto);
      k++;
    }
    c.tira = tira.toDataURL('image/jpeg', 0.6);
  } catch (e) {
    try {
      const cv = document.createElement('canvas'); cv.height = 72; cv.width = Math.round(72 * c.w / c.h);
      cv.getContext('2d').drawImage(c.el, 0, 0, cv.width, cv.height);
      c.tira = cv.toDataURL('image/jpeg', 0.6);
    } catch (e2) { /* sin miniatura */ }
  }
  pintarLinea();
}
function cargarVideo(c) {
  return new Promise((ok, mal) => {
    const v = document.createElement('video');
    v.playsInline = true; v.preload = 'auto'; v.crossOrigin = 'anonymous';
    v.setAttribute('playsinline', ''); v.setAttribute('webkit-playsinline', '');
    v.src = c.url;
    v.muted = true;
    v.onloadedmetadata = () => {
      c.el = v; c.w = v.videoWidth; c.h = v.videoHeight;
      if (!c.dur || !isFinite(c.dur)) c.dur = isFinite(v.duration) ? v.duration : 1;
      if (c.out == null) c.out = c.dur;
      // iPhone no carga el primer cuadro hasta que se mueve el tiempo
      try { v.currentTime = Math.max(0.01, c.in || 0); } catch (e) { /* nada */ }
      ok();
    };
    v.onerror = () => mal(new Error('No se pudo abrir ' + c.nombre));
    ['seeked', 'loadeddata', 'playing'].forEach(ev => v.addEventListener(ev, () => { S.dirty = true; }));
    $('#cutVideos').appendChild(v);
  });
}
async function prepararClip(c) {
  c.url = URL.createObjectURL(c.file);
  if (c.tipo === 'imagen') {
    c.bmp = await createImageBitmap(c.file, { imageOrientation: 'from-image' });
    c.w = c.bmp.width; c.h = c.bmp.height;
    const cv = document.createElement('canvas'); cv.height = 72; cv.width = Math.round(72 * c.w / c.h);
    cv.getContext('2d').drawImage(c.bmp, 0, 0, cv.width, cv.height);
    c.tira = cv.toDataURL('image/jpeg', 0.6);
    if (S.belleza.on && BEL.listo()) { c.mascara = document.createElement('canvas'); BEL.mascara(c.bmp, c.mascara); }
  } else {
    await cargarVideo(c);
    tiraVideo(c);
  }
}
async function agregarArchivos(lista) {
  const files = [...lista].filter(f => /^(video|image)\//.test(f.type) || /\.(mp4|mov|webm|m4v|jpe?g|png|webp|heic)$/i.test(f.name));
  if (!files.length) return;
  $('#cutVacio').hidden = true;
  cargandoMsg(`Cargando ${files.length} ${files.length === 1 ? 'archivo' : 'archivos'}…`);
  for (const f of files) {
    const c = { id: uid(), fileId: uid(), file: f, nombre: f.name, tipo: f.type.startsWith('image/') || /\.(jpe?g|png|webp|heic)$/i.test(f.name) ? 'imagen' : 'video', in: 0, out: null, dur: 0, mute: false, trans: { tipo: 'ninguna', dur: 0.5 } };
    if (c.tipo === 'imagen') { c.dur = 3; c.out = 3; }
    try {
      await prepararClip(c);
      S.clips.push(reg(c));
      await DB.put('archivos', c.fileId, f);
    } catch (e) { aviso(e.message || 'No se pudo abrir el archivo', 'mal'); }
  }
  cargandoMsg(null);
  if (S.clips.length && !S.sel) S.sel = S.clips[0].id;
  pintarLinea(); pintarPanel(); guardar();
}
$('#cutArchivo').addEventListener('change', (e) => { agregarArchivos(e.target.files); e.target.value = ''; });
$$('[data-agregar]').forEach(b => b.addEventListener('click', () => $('#cutArchivo').click()));

function cargandoMsg(txt) {
  const m = $('#cutCargando');
  m.hidden = !txt;
  if (txt) $('span', m).textContent = txt;
}

/* ---------- edición de clips ---------- */
function clipSel() { return S.clips.find(c => c.id === S.sel); }
function dividir() {
  const L = lay();
  const i = S.clips.findIndex((c, k) => S.t > L.start[k] + 0.1 && S.t < L.start[k] + largo(c) - 0.1);
  if (i < 0) return aviso('Ponga la línea blanca encima del clip que quiere cortar');
  const c = S.clips[i];
  const loc = tOrigen(c, S.t - L.start[i]);
  const corte = S.t - L.start[i];
  const n = reg({ ...c, _tk: null, id: uid(), in: loc, el: null, mascara: null, trans: { ...c.trans }, kf: c.kf ? c.kf.map(k => ({ ...k, t: k.t - corte })) : null });
  c.out = loc; c.trans = { tipo: 'ninguna', dur: 0.5 };
  S.clips.splice(i + 1, 0, n);
  const listo = () => { S.sel = n.id; pintarLinea(); pintarPanel(); guardar(); aviso('Clip dividido ✂️'); };
  if (n.tipo === 'video') cargarVideo(n).then(listo); else listo();
}
async function eliminar() {
  const c = clipSel(); if (!c) return;
  const i = S.clips.indexOf(c);
  S.clips.splice(i, 1);
  if (c.el) c.el.pause();
  S.sel = S.clips[Math.min(i, S.clips.length - 1)]?.id || null;
  if (!S.clips.length) $('#cutVacio').hidden = false;
  irA(S.t); pintarLinea(); pintarPanel(); guardar();
}
function duplicar() {
  const c = clipSel(); if (!c) return;
  const i = S.clips.indexOf(c);
  const n = reg({ ...c, id: uid(), el: null, mascara: null, _tk: null, trans: { ...c.trans }, kf: c.kf ? c.kf.map(k => ({ ...k })) : null });
  S.clips.splice(i + 1, 0, n);
  const listo = () => { S.sel = n.id; pintarLinea(); pintarPanel(); guardar(); };
  if (n.tipo === 'video') cargarVideo(n).then(listo); else listo();
}
function mover(d) {
  const c = clipSel(); if (!c) return;
  const i = S.clips.indexOf(c), j = i + d;
  if (j < 0 || j >= S.clips.length) return;
  [S.clips[i], S.clips[j]] = [S.clips[j], S.clips[i]];
  pintarLinea(); guardar();
}

/* ---------- panel de herramientas ---------- */
const TITULOS = { editar: 'Editar', audio: 'Audio', texto: 'Texto', subtitulos: 'Subtítulos', velocidad: 'Velocidad', transicion: 'Transición', belleza: 'Filtros · Belleza', formato: 'Formato' };
function abrirTab(t) {
  S.tab = t;
  $$('.cut-tab').forEach(b => b.classList.toggle('on', b.dataset.tab === t));
  $('#cutPanel').hidden = !t;
  document.body.classList.toggle('con-panel', !!t);
  $('#cutPanelTit').textContent = TITULOS[t] || '';
  pintarPanel();
  requestAnimationFrame(() => pintarLinea());
}
$$('.cut-tab').forEach(b => b.addEventListener('click', () => abrirTab(S.tab === b.dataset.tab && innerWidth < 1000 ? null : b.dataset.tab)));
$('#cutPanelCerrar').addEventListener('click', () => abrirTab(innerWidth >= 1000 ? 'editar' : null));
$('#cutDeshacer').addEventListener('click', deshacer);
$('#cutRehacer').addEventListener('click', rehacer);
$('#cutPantalla').addEventListener('click', () => { document.body.classList.toggle('pantalla'); setTimeout(() => { pintarLinea(); S.dirty = true; }, 50); });

function chips(nombre, opciones, actual) {
  return `<div class="cut-chips" data-grupo="${nombre}">${opciones.map(([v, t]) => `<button type="button" class="cut-chip ${String(v) === String(actual) ? 'on' : ''}" data-v="${v}">${t}</button>`).join('')}</div>`;
}

function pintarPanel() {
  const p = $('#cutPanelCuerpo');
  if (!S.tab) { p.innerHTML = ''; return; }
  const c = clipSel();
  const m = S.musica.find(x => x.id === S.selM);
  if (S.tab === 'editar') {
    if (!c) { p.innerHTML = `<p class="cut-nota">Agregue videos o fotos con el botón <b>+</b> y toque un clip para editarlo.</p>`; return; }
    p.innerHTML = `
      <div class="cut-acciones">
        <button type="button" data-a="dividir"><i>✂️</i>Dividir</button>
        ${c.tipo === 'video' ? `<button type="button" data-a="vel"><i>◷</i>Velocidad</button>` : ''}
        <button type="button" data-a="duplicar"><i>⧉</i>Duplicar</button>
        <button type="button" data-a="izq"><i>⬅️</i>Mover</button>
        <button type="button" data-a="der"><i>➡️</i>Mover</button>
        ${c.tipo === 'video' ? `<button type="button" data-a="extraer"><i>🎵</i>Extraer audio</button>` : ''}
        ${c.tipo === 'video' ? `<button type="button" data-a="mute"><i>${c.mute ? '🔈' : '🔇'}</i>${c.mute ? 'Con sonido' : 'Silenciar'}</button>` : ''}
        <button type="button" data-a="eliminar"><i>🗑️</i>Eliminar</button>
      </div>
      <div class="cut-fila"><small>Zoom aquí</small><input type="range" min="1" max="4" step="0.01" value="${encuadreEn(c, ltClip(c)).z}" data-zoom><b class="cut-val" data-zv>${Math.round(encuadreEn(c, ltClip(c)).z * 100)}%</b></div>
      ${c.kf?.length ? `<p class="cut-nota" style="text-align:left">🔍 Zoom animado: cambia en ${c.kf.filter(k => k.t > 0.01).map(k => fmt(k.t).slice(3, 7) + ' s').slice(0, 4).join(', ')}. Antes de ese momento el video se ve como estaba.</p>` : ''}
      ${(c.z || 1) > 1.001 || c.x || c.y || c.kf?.length ? `<div class="cut-acciones2"><button type="button" class="cut-btn-sec" data-reenc>Quitar zoom</button>${c.kf?.length ? '<button type="button" class="cut-btn-sec" data-todo>Zoom en todo el clip</button>' : ''}</div>` : ''}
      <p class="cut-nota">💡 Pellizque el video con dos dedos para acercarlo y arrástrelo con un dedo para escoger qué parte se ve. El zoom empieza en el momento donde está la línea blanca.</p>
      ${c.tipo === 'video' ? `<div class="cut-fila"><small>Volumen</small><input type="range" min="0" max="2" step="0.05" value="${c.vol ?? 1}" data-vol><b class="cut-val">${Math.round((c.vol ?? 1) * 100)}%</b></div>` : ''}
      ${c.tipo === 'imagen' ? `<div class="cut-fila"><small>Duración de la foto</small>${chips('durfoto', [[2, '2 s'], [3, '3 s'], [5, '5 s'], [8, '8 s']], largo(c))}</div>` : ''}
      <p class="cut-nota">Para recortar: ponga la línea blanca donde quiere cortar, toque <b>Dividir</b> y elimine el pedazo que sobra.</p>`;
    $$('[data-a]', p).forEach(b => b.onclick = () => ({ dividir, eliminar, duplicar, izq: () => mover(-1), der: () => mover(1), vel: () => abrirTab('velocidad'), extraer: () => extraerDeClip(c), mute: () => { c.mute = !c.mute; pintarLinea(); pintarPanel(); sincronizar(); guardar(); } })[b.dataset.a]());
    const rz = $('[data-zoom]', p);
    rz.oninput = () => { const k = puntoZoom(c); k.z = Number(rz.value); $('[data-zv]', p).textContent = Math.round(k.z * 100) + '%'; S.dirty = true; };
    rz.onchange = () => { guardar(); pintarPanel(); };
    const re = $('[data-reenc]', p); if (re) re.onclick = () => { c.z = 1; c.x = 0; c.y = 0; c.kf = null; S.dirty = true; guardar(); pintarPanel(); };
    const rt = $('[data-todo]', p); if (rt) rt.onclick = () => { const e2 = encuadreEn(c, ltClip(c)); c.z = e2.z; c.x = e2.x; c.y = e2.y; c.kf = null; S.dirty = true; guardar(); pintarPanel(); };
    const rv = $('[data-vol]', p);
    if (rv) { rv.oninput = () => { c.vol = Number(rv.value); $('.cut-val', p).textContent = Math.round(c.vol * 100) + '%'; sincronizar(); }; rv.onchange = guardar; }
    const g = $('[data-grupo="durfoto"]', p);
    if (g) g.onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.out = c.in + Number(v); pintarLinea(); pintarPanel(); guardar(); };
    return;
  }
  if (S.tab === 'transicion') {
    const i = S.clips.indexOf(c);
    if (!c || i < 0 || i >= S.clips.length - 1) { p.innerHTML = `<p class="cut-nota">Toque el rombo <b>◇</b> que aparece entre dos clips para ponerle una transición.</p>`; return; }
    p.innerHTML = `
      <div class="cut-trans">${TRANSICIONES.map(t => `<button type="button" class="${c.trans.tipo === t.id ? 'on' : ''}" data-t="${t.id}"><span class="ic ic-${t.id}"></span>${t.n}</button>`).join('')}</div>
      <div class="cut-fila"><small>Duración</small>${chips('durtr', [[0.3, 'Rápida'], [0.5, 'Normal'], [0.8, 'Lenta'], [1.2, 'Muy lenta']], c.trans.dur)}</div>
      <button type="button" class="cut-btn-sec" data-todas>Aplicar a todos los cortes</button>`;
    $('.cut-trans', p).onclick = (e) => {
      const b = e.target.closest('[data-t]'); if (!b) return;
      c.trans.tipo = b.dataset.t; pintarLinea(); pintarPanel(); guardar(); verCorte(i);
    };
    $('[data-grupo="durtr"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.trans.dur = Number(v); pintarLinea(); pintarPanel(); guardar(); verCorte(i); };
    $('[data-todas]', p).onclick = () => { S.clips.slice(0, -1).forEach(x => { x.trans = { ...c.trans }; }); pintarLinea(); guardar(); aviso('Transición puesta en todos los cortes ✨'); };
    return;
  }
  if (S.tab === 'audio') {
    if (m) {
      p.innerHTML = `
        <p class="cut-nota" style="text-align:left">🎵 <b>${esc(m.nombre)}</b></p>
        <div class="cut-fila"><small>Volumen</small><input type="range" min="0" max="2" step="0.05" value="${m.vol ?? 1}" data-mvol><b class="cut-val">${Math.round((m.vol ?? 1) * 100)}%</b></div>
        <label class="cut-check"><input type="checkbox" data-fi ${m.fadeIn ? 'checked' : ''}> Aparecer suave al inicio</label>
        <label class="cut-check"><input type="checkbox" data-fo ${m.fadeOut ? 'checked' : ''}> Desaparecer suave al final</label>
        <div class="cut-acciones2">
          <button type="button" class="cut-btn-sec" data-aqui>Empezar donde está la línea</button>
          <button type="button" class="cut-btn-sec" data-cero>Empezar al inicio</button>
          <button type="button" class="cut-btn-sec" data-quitar>🗑 Quitar música</button>
        </div>`;
      const r = $('[data-mvol]', p);
      r.oninput = () => { m.vol = Number(r.value); $('.cut-val', p).textContent = Math.round(m.vol * 100) + '%'; sincronizar(); };
      r.onchange = guardar;
      $('[data-fi]', p).onchange = (e) => { m.fadeIn = e.target.checked; guardar(); };
      $('[data-fo]', p).onchange = (e) => { m.fadeOut = e.target.checked; guardar(); };
      $('[data-aqui]', p).onclick = () => { m.start = S.t; pintarLinea(); guardar(); };
      $('[data-cero]', p).onclick = () => { m.start = 0; pintarLinea(); guardar(); };
      $('[data-quitar]', p).onclick = () => { if (m.el) m.el.pause(); S.musica = S.musica.filter(x => x !== m); S.selM = null; pintarLinea(); pintarPanel(); guardar(); };
      return;
    }
    p.innerHTML = `
      <div class="cut-acciones cut-acc-audio">
        <button type="button" data-mus><i>🎵</i>Agregar música</button>
        <button type="button" data-ext><i>🎬</i>Extraer audio</button>
      </div>
      <p class="cut-nota"><b>Agregar música:</b> una canción o audio de su celular (MP3, M4A…).<br><b>Extraer audio:</b> escoja un video y se le saca solo el sonido para ponerlo en su edición.<br>Empieza donde está la línea blanca.</p>
      <label class="cut-check"><input type="checkbox" data-sil ${S.silenciar ? 'checked' : ''}> Silenciar el sonido original de los videos</label>
      ${S.musica.length ? `<p class="cut-nota">Toque una música en la fila <b>♪</b> para cambiarle el volumen o quitarla.</p>` : ''}`;
    $('[data-mus]', p).onclick = () => $('#cutMusica').click();
    $('[data-ext]', p).onclick = () => $('#cutExtraer').click();
    $('[data-sil]', p).onchange = (e) => { S.silenciar = e.target.checked; sincronizar(); pintarLinea(); guardar(); };
    return;
  }
  if (S.tab === 'subtitulos' || S.tab === 'texto') {
    const st = S.sub;
    const esSub = S.tab === 'subtitulos';
    p.innerHTML = `
      ${esSub ? `<div class="cut-gen">
        <button type="button" class="cut-btn" data-gen>✨ ${S.segs.length ? 'Volver a generar' : 'Generar subtítulos automáticos'}</button>
        <div class="cut-gen-op">
          <select data-idioma><option value="spanish">Español</option><option value="english">Inglés</option><option value="portuguese">Portugués</option></select>
          <select data-modelo><option value="onnx-community/whisper-base">Rápido</option><option value="onnx-community/whisper-small">Más preciso</option></select>
        </div>
        <div class="cut-progreso" hidden><i></i><span></span></div>
      </div>` : `<button type="button" class="cut-btn" data-nuevo>T + Agregar texto aquí</button>`}
      <div class="cut-estilos">${SUB.ESTILOS.map(e => `<button type="button" class="est est-${e.id} ${st.estilo === e.id ? 'on' : ''}" data-e="${e.id}"><b>Hola</b><small>${e.n}</small></button>`).join('')}</div>
      <div class="cut-fila"><small>Tamaño</small>${chips('tam', [['S', 'Pequeño'], ['M', 'Mediano'], ['L', 'Grande']], st.tam)}</div>
      <div class="cut-fila"><small>Color</small><div class="cut-colores">${SUB.COLORES.map(col => `<button type="button" data-col="${col}" class="${st.color === col ? 'on' : ''}" style="background:${col}"></button>`).join('')}</div></div>
      ${esSub ? `<div class="cut-fila"><small>Palabras por subtítulo</small>${chips('pal', [[1, '1'], [2, '2'], [3, '3'], [5, '5'], [0, 'Frase']], st.palabras)}</div>` : ''}
      <div class="cut-fila"><small>Altura</small><input type="range" min="0.1" max="0.9" step="0.01" value="${st.pos}" data-pos></div>
      <label class="cut-check"><input type="checkbox" data-mayus ${st.mayus ? 'checked' : ''}> TODO EN MAYÚSCULAS</label>
      <div class="cut-acciones2">
        ${esSub ? '<button type="button" class="cut-btn-sec" data-nuevo>+ Texto aquí</button>' : ''}
        ${S.subs.length ? '<button type="button" class="cut-btn-sec" data-lista>Editar textos</button><button type="button" class="cut-btn-sec" data-borrar>Quitar todos</button>' : ''}
      </div>`;
    const gen = $('[data-gen]', p); if (gen) gen.onclick = () => generarSubs($('[data-idioma]', p).value, $('[data-modelo]', p).value);
    $('.cut-estilos', p).onclick = (e) => { const b = e.target.closest('[data-e]'); if (!b) return; st.estilo = b.dataset.e; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-grupo="tam"]', p).onclick = (e) => { if (!e.target.dataset.v) return; st.tam = e.target.dataset.v; pintarPanel(); S.dirty = true; guardar(); };
    const gp = $('[data-grupo="pal"]', p); if (gp) gp.onclick = (e) => { if (e.target.dataset.v == null) return; st.palabras = Number(e.target.dataset.v); if (S.segs.length) { S.subs = SUB.armar(S.segs, st.palabras); pintarLinea(); } pintarPanel(); guardar(); };
    $('.cut-colores', p).onclick = (e) => { if (!e.target.dataset.col) return; st.color = e.target.dataset.col; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-pos]', p).oninput = (e) => { st.pos = Number(e.target.value); S.dirty = true; };
    $('[data-pos]', p).onchange = guardar;
    $('[data-mayus]', p).onchange = (e) => { st.mayus = e.target.checked; S.dirty = true; guardar(); };
    $('[data-nuevo]', p).onclick = nuevoTexto;
    const l = $('[data-lista]', p); if (l) l.onclick = listaSubs;
    const q = $('[data-borrar]', p); if (q) q.onclick = async () => { if (await preguntar('¿Quitar todos los textos?', 'Se borran los subtítulos y textos del video. Puede deshacerlo con ↩.', 'Quitar')) { S.subs = []; S.segs = []; pintarLinea(); pintarPanel(); guardar(); } };
    return;
  }
  if (S.tab === 'belleza') {
    const b = S.belleza;
    p.innerHTML = `
      <label class="cut-switch"><input type="checkbox" data-on ${b.on ? 'checked' : ''}><span></span> Filtro de belleza</label>
      <div class="cut-estado-bel"></div>
      <div class="cut-fila"><small>Estilo</small>${chips('preset', [['natural', 'Natural'], ['suave', 'Suave'], ['glam', 'Glam']], b.preset)}</div>
      <div class="cut-fila"><small>Suavizar piel</small><input type="range" min="0" max="1" step="0.01" value="${b.suave}" data-k="suave"></div>
      <div class="cut-fila"><small>Iluminar (su tono)</small><input type="range" min="0" max="1" step="0.01" value="${b.aclara}" data-k="aclara"></div>
      <div class="cut-fila"><small>Brillo general</small><input type="range" min="0" max="1" step="0.01" value="${b.brillo}" data-k="brillo"></div>
      <p class="cut-nota">Toma su tono de piel y lo empareja sin cambiarle el color. Ojos, cejas, gafas y boca quedan nítidos.</p>`;
    const estado = $('.cut-estado-bel', p);
    const pintarEstado = () => { estado.textContent = b.on ? (BEL.listo() ? '✅ Detector de rostro activo' : '⏳ Cargando detector de rostro…') : ''; };
    pintarEstado();
    $('[data-on]', p).onchange = async (e) => { b.on = e.target.checked; S.dirty = true; guardar(); pintarEstado(); if (b.on) activarBelleza().then(pintarEstado); };
    $('[data-grupo="preset"]', p).onclick = (e) => {
      const v = e.target.dataset.v; if (!v) return;
      Object.assign(b, { natural: { suave: 0.45, aclara: 0.25, brillo: 0 }, suave: { suave: 0.7, aclara: 0.4, brillo: 0.2 }, glam: { suave: 0.95, aclara: 0.6, brillo: 0.4 } }[v], { preset: v, on: true });
      pintarPanel(); S.dirty = true; guardar(); activarBelleza().then(() => pintarPanel());
    };
    $$('input[type=range]', p).forEach(r => { r.oninput = () => { b[r.dataset.k] = Number(r.value); b.on = true; $('[data-on]', p).checked = true; S.dirty = true; }; r.onchange = () => { guardar(); activarBelleza(); }; });
    return;
  }
  if (S.tab === 'velocidad') {
    if (!c || c.tipo !== 'video') { p.innerHTML = `<p class="cut-nota">Toque un <b>video</b> en la línea de tiempo para cambiarle la velocidad.</p>`; return; }
    const modo = c.curva ? 'curva' : 'normal';
    const graf = (pts) => { const W = 64, H = 30, mx = 5.2; const d = Array.from({ length: 33 }, (_, k) => { const u = k / 32; const x = u * (pts.length - 1), i = Math.min(pts.length - 2, Math.floor(x)), f = x - i, sm = (1 - Math.cos(f * Math.PI)) / 2; const v = pts[i] + (pts[i + 1] - pts[i]) * sm; return `${(u * W).toFixed(1)},${(H - 3 - (Math.log(v) - Math.log(0.2)) / (Math.log(mx) - Math.log(0.2)) * (H - 6)).toFixed(1)}`; }).join(' '); return `<svg viewBox="0 0 ${W} ${H}"><polyline points="${d}" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`; };
    p.innerHTML = `
      <div class="cut-modo">${chips('modo', [['normal', 'Normal'], ['curva', 'Curva']], modo)}</div>
      ${modo === 'normal' ? `
        <div class="cut-vel-num"><b>${(c.vel || 1).toFixed(2).replace(/\.?0+$/, '')}x</b><small>${fmt(largo(c)).slice(0, 5)} de duración</small></div>
        <input type="range" class="cut-vel-rango" min="0" max="1" step="0.001" value="${Math.log(c.vel || 1) / Math.log(10) / 2 + 0.5}" data-vel>
        <div class="cut-vel-marcas"><span>0.1x</span><span>1x</span><span>10x</span></div>
        ${chips('velr', [[0.25, '0.25x'], [0.5, '0.5x'], [1, '1x'], [1.5, '1.5x'], [2, '2x'], [3, '3x'], [5, '5x']], c.vel || 1)}
      ` : `
        <div class="cut-curvas">
          <button type="button" data-cu="" class="${!c.curva ? 'on' : ''}">${graf([1, 1])}<small>Ninguna</small></button>
          ${Object.entries(CURVAS).map(([k, v]) => `<button type="button" data-cu="${k}" class="${c.curva === k ? 'on' : ''}">${graf(v.p)}<small>${v.n}</small></button>`).join('')}
        </div>
        <div class="cut-fila"><small>Velocidad base</small>${chips('velb', [[0.5, '0.5x'], [1, '1x'], [1.5, '1.5x'], [2, '2x']], c.vel || 1)}</div>
      `}
      <p class="cut-nota">${modo === 'normal' ? 'Más rápido acorta el clip; más lento lo alarga (cámara lenta).' : 'La curva cambia la velocidad dentro del mismo clip: rápido, cámara lenta en el mejor momento y rápido otra vez.'}</p>
      <button type="button" class="cut-btn-sec" data-todos>Aplicar a todos los videos</button>`;
    const cambio = (ver = true) => { pintarLinea(); pintarPanel(); guardar(); if (ver) { const L = lay(), i = S.clips.indexOf(c); irA(L.start[i]); setTimeout(() => reproducir(L.start[i] + largo(c)), 60); } };
    $('[data-grupo="modo"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; if (v === 'normal') { c.curva = null; cambio(false); } else { c.curva = c.curva || 'montaje'; cambio(); } };
    const r = $('[data-vel]', p);
    if (r) {
      r.oninput = () => { const v = Math.round(Math.pow(10, (Number(r.value) - 0.5) * 2) * 100) / 100; c.vel = Math.abs(v - 1) < 0.04 ? 1 : v; $('.cut-vel-num b', p).textContent = c.vel + 'x'; $('.cut-vel-num small', p).textContent = fmt(largo(c)).slice(0, 5) + ' de duración'; pintarLinea(); };
      r.onchange = () => cambio();
    }
    const g1 = $('[data-grupo="velr"]', p); if (g1) g1.onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.vel = Number(v); cambio(); };
    const g2 = $('[data-grupo="velb"]', p); if (g2) g2.onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.vel = Number(v); cambio(); };
    const cv = $('.cut-curvas', p); if (cv) cv.onclick = (e) => { const b = e.target.closest('[data-cu]'); if (!b) return; c.curva = b.dataset.cu || null; cambio(); };
    $('[data-todos]', p).onclick = () => { S.clips.forEach(x => { if (x.tipo === 'video') { x.vel = c.vel || 1; x.curva = c.curva || null; } }); pintarLinea(); guardar(); aviso('Velocidad puesta en todos los videos ⚡'); };
    return;
  }
  if (S.tab === 'formato') {
    p.innerHTML = `
      <div class="cut-formatos">
        ${[['9:16', 'TikTok · Reels · Estados'], ['16:9', 'YouTube · TV'], ['1:1', 'Cuadrado'], ['4:5', 'Instagram']].map(([v, t]) => `<button type="button" data-asp="${v}" class="${S.aspecto === v ? 'on' : ''}"><span class="caja-asp" style="aspect-ratio:${v.replace(':', '/')}"></span><b>${v}</b><small>${t}</small></button>`).join('')}
      </div>
      <button type="button" class="cut-btn-sec" data-nuevo-proy style="margin-top:.8rem">Empezar un video nuevo</button>`;
    $('[data-nuevo-proy]', p).onclick = nuevoProyecto;
    $('.cut-formatos', p).onclick = (e) => { const b = e.target.closest('[data-asp]'); if (!b) return; S.aspecto = b.dataset.asp; ajustarVista(); pintarPanel(); guardar(); };
  }
}

function verCorte(i) {
  const L = lay();
  irA(Math.max(0, L.start[i + 1] - 0.7));
  setTimeout(() => reproducir(L.start[i + 1] + L.tr[i] + 0.7), 60);
}

/* ---------- belleza ---------- */
async function activarBelleza() {
  try {
    await BEL.cargar();
    for (const c of S.clips) if (c.tipo === 'imagen') { c.mascara ||= document.createElement('canvas'); BEL.mascara(c.bmp, c.mascara); }
    S.dirty = true;
  } catch (e) {
    aviso('No se pudo cargar el detector de rostro. Se suaviza la piel sin detectarlo.', 'mal');
  }
}

/* ---------- subtítulos ---------- */
async function mezclarAudio(rate, canales, soloVoz = false) {
  const L = lay();
  const len = Math.max(1, Math.ceil(L.total * rate));
  const oc = new OfflineAudioContext(canales, len, rate);
  let hay = false;
  for (let i = 0; i < S.clips.length; i++) {
    const c = S.clips[i];
    if (c.tipo !== 'video' || c.mute || (S.silenciar && !soloVoz)) continue;
    const buf = await decodificar(c, oc);
    if (!buf) continue;
    hay = true;
    const src = oc.createBufferSource(); src.buffer = buf;
    const g = oc.createGain();
    const ini = L.start[i], dur = largo(c);
    const fe = i > 0 ? L.tr[i - 1] : 0, fs = L.tr[i] || 0;
    const vv = soloVoz ? 1 : (c.vol ?? 1);
    g.gain.setValueAtTime(fe ? 0 : vv, ini);
    if (fe) g.gain.linearRampToValueAtTime(vv, ini + fe);
    if (fs) { g.gain.setValueAtTime(vv, ini + dur - fs); g.gain.linearRampToValueAtTime(0, ini + dur); }
    if (c.curva && CURVAS[c.curva]) {
      const pts = Array.from({ length: 64 }, (_, k) => Math.max(0.1, velEn(c, k / 63)));
      src.playbackRate.setValueCurveAtTime(Float32Array.from(pts), ini, dur);
    } else src.playbackRate.value = c.vel || 1;
    src.connect(g).connect(oc.destination);
    src.start(ini, c.in, c.out - c.in);
  }
  if (!soloVoz) for (const m of S.musica) {
    const dur = Math.min(largoM(m), L.total - m.start);
    if (dur <= 0.05) continue;
    const buf = await decodificar(m, oc);
    if (!buf) continue;
    hay = true;
    const src = oc.createBufferSource(); src.buffer = buf;
    const g = oc.createGain(), v = m.vol ?? 1;
    const fi = m.fadeIn ? Math.min(1.5, dur / 3) : 0, fo = m.fadeOut ? Math.min(1.5, dur / 3) : 0;
    g.gain.setValueAtTime(fi ? 0 : v, m.start);
    if (fi) g.gain.linearRampToValueAtTime(v, m.start + fi);
    if (fo) { g.gain.setValueAtTime(v, m.start + dur - fo); g.gain.linearRampToValueAtTime(0, m.start + dur); }
    src.connect(g).connect(oc.destination);
    src.start(m.start, m.in || 0, dur);
  }
  if (!hay) return null;
  return oc.startRendering();
}
const audios = new Map();
async function decodificar(c, ctx) {
  const k = c.fileId + '@' + ctx.sampleRate;
  if (audios.has(k)) return audios.get(k);
  let buf = null;
  try { buf = await ctx.decodeAudioData(await c.file.arrayBuffer()); } catch (e) { buf = null; }
  audios.set(k, buf);
  return buf;
}

let trabajador = null;
async function generarSubs(idioma, modelo) {
  if (!S.clips.length) return aviso('Primero agregue un video');
  const p = $('#cutPanelCuerpo');
  const barra = $('.cut-progreso', p);
  const btn = $('[data-gen]', p);
  const poner = (pct, txt) => { barra.hidden = false; $('i', barra).style.width = pct + '%'; $('span', barra).textContent = txt; };
  btn.disabled = true;
  poner(3, 'Preparando el audio…');
  try {
    const audio = await mezclarAudio(16000, 1, true);
    if (!audio) throw new Error('El video no tiene sonido para escuchar');
    poner(8, 'Descargando el modelo de voz (solo la primera vez)…');
    trabajador ||= new Worker(new URL('./transcribir.js', import.meta.url), { type: 'module' });
    const datos = audio.getChannelData(0).slice();
    const segs = await new Promise((ok, mal) => {
      trabajador.onmessage = (e) => {
        const m = e.data;
        if (m.tipo === 'descarga') poner(8 + m.pct * 0.5, `Descargando el modelo de voz… ${m.pct}%`);
        if (m.tipo === 'escuchando') poner(62, 'Escuchando el video y escribiendo… (puede tardar un poco)');
        if (m.tipo === 'listo') ok(m.segmentos);
        if (m.tipo === 'error') mal(new Error(m.msg));
      };
      trabajador.onerror = (e) => mal(new Error(e.message || 'Falló la transcripción'));
      trabajador.postMessage({ tipo: 'transcribir', audio: datos, idioma, modelo }, [datos.buffer]);
    });
    const total = lay().total;
    S.segs = segs.filter(s => s.text.trim()).map(s => ({ ...s, end: s.end ?? Math.min(total, s.start + 3) }));
    S.subs = SUB.armar(S.segs, S.sub.palabras);
    poner(100, S.subs.length ? `¡Listo! ${S.subs.length} subtítulos` : 'No se entendió ninguna voz');
    pintarLinea(); guardar();
    setTimeout(() => { if (S.tab === 'subtitulos') pintarPanel(); }, 1200);
  } catch (e) {
    poner(0, '');
    barra.hidden = true;
    aviso(e.message || 'No se pudieron generar los subtítulos', 'mal');
  } finally { btn.disabled = false; }
}

function editarSub(s) {
  pausar(); irA(s.start + 0.01);
  const m = $('#cutEditSub');
  m.hidden = false;
  $('textarea', m).value = s.text;
  $('[data-ini]', m).value = s.start.toFixed(1);
  $('[data-fin]', m).value = s.end.toFixed(1);
  $('[data-guardar]', m).onclick = () => {
    s.text = $('textarea', m).value.trim() || s.text;
    s.start = Math.max(0, Number($('[data-ini]', m).value) || s.start);
    s.end = Math.max(s.start + 0.2, Number($('[data-fin]', m).value) || s.end);
    SUB.repartir(s); S.subs.sort((a, b) => a.start - b.start);
    m.hidden = true; pintarLinea(); guardar();
  };
  $('[data-quitar]', m).onclick = () => { S.subs = S.subs.filter(x => x !== s); m.hidden = true; pintarLinea(); guardar(); };
  $('[data-cerrar]', m).onclick = () => { m.hidden = true; };
}
function listaSubs() {
  const m = $('#cutListaSubs');
  m.hidden = false;
  const cuerpo = $('.cut-lista', m);
  cuerpo.innerHTML = S.subs.map((s, i) => `<div class="it"><small>${fmt(s.start)}</small><input value="${esc(s.text)}" data-i="${i}"></div>`).join('');
  $('[data-guardar]', m).onclick = () => {
    $$('input', cuerpo).forEach(inp => { const s = S.subs[inp.dataset.i]; if (s && inp.value.trim()) { s.text = inp.value.trim(); SUB.repartir(s); } });
    m.hidden = true; pintarLinea(); guardar(); aviso('Textos guardados');
  };
  $('[data-cerrar]', m).onclick = () => { m.hidden = true; };
}

/* ---------- exportar ---------- */
const EXP = { res: 1080, fps: 30, cancelar: false };
async function abrirExportar() {
  if (!S.clips.length) return aviso('Primero agregue un video');
  pausar();
  if (typeof VideoEncoder === 'undefined') return aviso('Este navegador no puede exportar video. Use Chrome actualizado.', 'mal');
  const m = $('#cutExportar');
  m.hidden = false;
  $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true; $('.cut-exp-fin', m).hidden = true;
  const ops = $('[data-res]', m);
  ops.innerHTML = Object.entries(RESOL).map(([v, t]) => `<button type="button" class="cut-chip" data-v="${v}">${t}</button>`).join('');
  // Desactivar lo que el equipo no puede codificar. En iPhone/iPad, 4K se queda sin memoria a mitad: solo en computador
  for (const b of $$('button', ops)) {
    if (IOS && Number(b.dataset.v) === 2160) { b.disabled = true; b.textContent = '4K (solo en computador)'; if (EXP.res === 2160) EXP.res = 1440; continue; }
    const { W, H } = tamSalida(Number(b.dataset.v));
    const ok = await MB.canEncodeVideo('avc', { width: W, height: H }) || await MB.canEncodeVideo('hevc', { width: W, height: H }) || await MB.canEncodeVideo('vp9', { width: W, height: H });
    if (!ok) { b.disabled = true; b.title = 'Este equipo no alcanza esta resolución'; if (EXP.res === Number(b.dataset.v)) EXP.res = 1080; }
  }
  const marcar = () => {
    $$('button', ops).forEach(b => b.classList.toggle('on', Number(b.dataset.v) === EXP.res));
    $$('[data-fps] button', m).forEach(b => b.classList.toggle('on', Number(b.dataset.v) === EXP.fps));
    const { W, H } = tamSalida(EXP.res);
    const mbps = (W * H * EXP.fps * 0.1) / 1e6;
    $('[data-info]', m).textContent = `${W}×${H} · ${EXP.fps} fps · ${fmt(lay().total).slice(0, 5)} · aprox. ${Math.max(1, Math.round(mbps * lay().total / 8))} MB`;
  };
  ops.onclick = (e) => { const v = e.target.dataset.v; if (!v || e.target.disabled) return; EXP.res = Number(v); marcar(); };
  $('[data-fps]', m).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; EXP.fps = Number(v); marcar(); };
  marcar();
  $('[data-cerrar]', m).onclick = () => { m.hidden = true; };
  const intentar = () => exportar().catch(err => {
    S.exportando = false; ajustarVista();
    if (EXP.res > 1080) {
      aviso(`Su equipo no alcanzó ${EXP.res === 2160 ? '4K' : '2K'}: lo exportamos en 1080p (Full HD), que se ve muy bien en redes.`);
      EXP.res = 1080; marcar();
      return intentar();
    }
    if (err.message === 'memoria' && (EXP.res > 720 || EXP.fps > 30)) {
      aviso('Al celular le faltó memoria: lo exportamos en 720p a 30 fps.');
      EXP.res = 720; EXP.fps = 30; marcar();
      return intentar();
    }
    aviso('No se pudo exportar: ' + (err.message === 'memoria' ? 'el equipo se quedó sin memoria. Pruebe con 720p.' : (err.message || err)), 'mal');
    $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true;
  });
  $('[data-iniciar]', m).onclick = intentar;
}
$('#cutBtnExportar').addEventListener('click', abrirExportar);

async function destino() {
  try {
    if (navigator.storage?.getDirectory) {
      const dir = await navigator.storage.getDirectory();
      const fh = await dir.getFileHandle('localprocut-export.mp4', { create: true });
      if (fh.createWritable) {
        const w = await fh.createWritable();
        return { target: new MB.StreamTarget(w), archivo: async () => fh.getFile(), opfs: true };
      }
    }
  } catch (e) { /* sin OPFS: en memoria */ }
  const target = new MB.BufferTarget();
  return { target, archivo: async () => new Blob([target.buffer], { type: 'video/mp4' }), opfs: false };
}

// ¿El cuadro salió negro? (los videos HDR/HEVC del iPhone a veces se leen negros con el lector rápido)
const _mini = document.createElement('canvas'); _mini.width = 8; _mini.height = 8;
function esNegro(fuente) {
  try {
    const x = _mini.getContext('2d', { willReadFrequently: true });
    x.clearRect(0, 0, 8, 8); x.drawImage(fuente, 0, 0, 8, 8);
    const d = x.getImageData(0, 0, 8, 8).data;
    let mx = 0; for (let i = 0; i < d.length; i += 4) mx = Math.max(mx, d[i], d[i + 1], d[i + 2]);
    return mx < 8;
  } catch (e) { return false; }
}
async function seekEl(v, t) {
  if (Math.abs(v.currentTime - t) < 0.001 && v.readyState >= 2) return;
  await new Promise((ok) => { const f = () => { v.removeEventListener('seeked', f); ok(); }; v.addEventListener('seeked', f); v.currentTime = t; setTimeout(f, 1500); });
}

async function exportar() {
  const m = $('#cutExportar');
  $('.cut-exp-op', m).hidden = true; $('.cut-exp-run', m).hidden = false;
  const barra = $('.cut-exp-run i', m), txt = $('.cut-exp-run span', m);
  const poner = (pct, t) => { barra.style.width = pct + '%'; txt.textContent = t; };
  EXP.cancelar = false;
  $('[data-cancelar]', m).onclick = () => { EXP.cancelar = true; };
  await document.fonts.ready;
  if (S.belleza.on) { poner(1, 'Cargando el filtro de belleza…'); await activarBelleza(); }

  const { W, H } = tamSalida(EXP.res);
  const fps = EXP.fps;
  const L = lay();
  const N = Math.max(1, Math.round(L.total * fps));
  const codec = await MB.getFirstEncodableVideoCodec(['avc', 'hevc', 'vp9', 'av1'], { width: W, height: H });
  if (!codec) throw new Error('este equipo no puede codificar video a esa resolución');
  const audioCodec = await MB.getFirstEncodableAudioCodec(['aac', 'opus']);

  poner(2, 'Preparando el audio…');
  const audio = audioCodec ? await mezclarAudio(48000, 2) : null;

  const { target, archivo } = await destino();
  const out = new MB.Output({ format: new MB.Mp4OutputFormat(), target });
  const lienzo = document.createElement('canvas'); lienzo.width = W; lienzo.height = H;
  const lctx = lienzo.getContext('2d');
  const vsrc = new MB.CanvasSource(lienzo, { codec, bitrate: MB.QUALITY_HIGH, keyFrameInterval: 2, latencyMode: 'quality' });
  out.addVideoTrack(vsrc, { frameRate: fps });
  let asrc = null;
  if (audio) { asrc = new MB.AudioBufferSource({ codec: audioCodec, bitrate: MB.QUALITY_HIGH }); out.addAudioTrack(asrc); }
  await out.start();

  const vistaW = motor.W, vistaH = motor.H;
  motor.tamano(W, H);

  // Un lector de cuadros por clip de video, con los tiempos exactos que va a necesitar
  const lectores = new Map();
  for (let i = 0; i < S.clips.length; i++) {
    const c = S.clips[i];
    if (c.tipo !== 'video') continue;
    const tiempos = [];
    for (let n = 0; n < N; n++) { const t = n / fps; if (t >= L.start[i] && t < L.start[i] + largo(c)) tiempos.push(tOrigen(c, t - L.start[i])); }
    try {
      if (IOS) throw 0;
      const input = new MB.Input({ source: new MB.BlobSource(c.file), formats: MB.ALL_FORMATS });
      const tr = await input.getPrimaryVideoTrack();
      if (!tr || !(await tr.canDecode())) throw 0;
      const esc2 = Math.min(1, Math.max(W / tr.displayWidth, H / tr.displayHeight) * 1.0001);
      const sw = Math.max(2, Math.round(tr.displayWidth * esc2)), sh = Math.max(2, Math.round(tr.displayHeight * esc2));
      const sink = new MB.CanvasSink(tr, { width: sw, height: sh, fit: 'fill', poolSize: 2 });
      lectores.set(i, { it: sink.canvasesAtTimestamps(tiempos)[Symbol.asyncIterator](), w: sw, h: sh, ultimo: null });
    } catch (e) {
      lectores.set(i, { el: true });
    }
  }

  const masc = new Map();
  const segAudio = 1;
  let audioHasta = 0;
  const pedazoAudio = (hasta) => {
    if (!asrc) return null;
    const ini = Math.round(audioHasta * 48000), fin = Math.min(audio.length, Math.round(hasta * 48000));
    if (fin <= ini) return null;
    const b = new AudioBuffer({ length: fin - ini, numberOfChannels: audio.numberOfChannels, sampleRate: 48000 });
    for (let ch = 0; ch < audio.numberOfChannels; ch++) b.copyToChannel(audio.getChannelData(ch).subarray(ini, fin), ch);
    audioHasta = fin / 48000;
    return asrc.add(b);
  };

  const t0 = performance.now();
  let recuperaciones = 0;
  S.exportando = true;

  // Modo "reproducir y grabar" (iPhone): en vez de saltar el video cuadro por cuadro (muy lento con HEVC 4K),
  // se reproduce seguido a media velocidad y se toma cada cuadro al pasar. Si el celular se atrasa, baja la velocidad.
  const tiempoReal = IOS || /[?&]tr=1/.test(location.search);
  const RT = { factor: 0.5, t: 0, desde: 0, atrasos: 0 };
  const reloj = () => RT.t + (performance.now() - RT.desde) / 1000 * RT.factor;
  const raf = () => new Promise(r => requestAnimationFrame(() => r()));
  const syncRT = (tt, tocar) => {
    S.clips.forEach((c, i) => {
      if (c.tipo !== 'video' || !c.el) return;
      const v = c.el; v.muted = true;
      const ini = L.start[i], fin = ini + largo(c);
      if (tocar && tt >= ini && tt < fin) {
        const lt = tt - ini, loc = tOrigen(c, lt);
        try { v.playbackRate = Math.max(0.1, Math.min(8, velEn(c, lt / largo(c)) * RT.factor)); } catch (e) { /* nada */ }
        if (v.paused) { v.currentTime = loc; v.play().catch(() => {}); }
        else if (Math.abs(v.currentTime - loc) > 0.35) v.currentTime = loc;
      } else {
        if (!v.paused) v.pause();
        // Dejar listo el siguiente clip un momento antes de que empiece
        if (tocar && tt >= ini - 0.5 && tt < ini && Math.abs(v.currentTime - c.in) > 0.05 && !v.seeking) v.currentTime = c.in;
      }
    });
  };
  if (tiempoReal) {
    syncRT(0, true);
    await Promise.race([
      Promise.all(S.clips.filter((c, i) => c.tipo === 'video' && c.el && L.start[i] <= 0.001).map(c => new Promise(r => { if (!c.el.paused && c.el.readyState >= 3) r(); else c.el.addEventListener('playing', r, { once: true }); }))),
      new Promise(r => setTimeout(r, 2500)),
    ]);
    RT.t = 0; RT.desde = performance.now();
  }

  try {
    for (let n = 0; n < N; n++) {
      if (EXP.cancelar) throw new Error('cancelado');
      const t = n / fps;
      if (tiempoReal) {
        while (reloj() < t) { syncRT(reloj(), true); await raf(); if (EXP.cancelar) throw new Error('cancelado'); }
        // Si el celular quedó muy atrás, bajar la velocidad de grabación para no perder cuadros
        if (reloj() - t > 0.25 && ++RT.atrasos > 5 && RT.factor > 0.2) { RT.t = reloj(); RT.desde = performance.now(); RT.factor *= 0.7; RT.atrasos = 0; }
        syncRT(t, true);
      }
      const act = activos(t, L);
      const cuadros = new Map();
      for (const i of act) {
        const c = S.clips[i];
        let f = null;
        if (c.tipo === 'imagen') f = { src: c.bmp, w: c.w, h: c.h };
        else {
          const lc = lectores.get(i);
          if (!lc.el) {
            const r = await lc.it.next();
            if (!r.done && r.value) lc.ultimo = r.value.canvas;
            f = lc.ultimo ? { src: lc.ultimo, w: lc.w, h: lc.h } : null;
            // Revisar los primeros cuadros: si el lector rápido los da negros pero el video sí tiene imagen, usar el método de la vista previa
            if (f && (lc.revisados = (lc.revisados || 0) + 1) <= 3 && esNegro(f.src)) {
              await seekEl(c.el, tOrigen(c, t - L.start[i]));
              if (!esNegro(c.el)) lc.el = true;
            }
          }
          if (lc.el) {
            if (!tiempoReal) await seekEl(c.el, tOrigen(c, t - L.start[i]));
            else if (c.el.readyState < 2) await new Promise(r => { c.el.addEventListener('loadeddata', r, { once: true }); setTimeout(r, 800); });
            const vw = c.el.videoWidth, vh = c.el.videoHeight;
            const k = Math.min(1, Math.max(W / vw, H / vh) * 1.0001);
            if (k < 0.99) {
              lc.cv ||= document.createElement('canvas');
              const cw = Math.max(2, Math.round(vw * k)), ch = Math.max(2, Math.round(vh * k));
              if (lc.cv.width !== cw || lc.cv.height !== ch) { lc.cv.width = cw; lc.cv.height = ch; }
              lc.cv.getContext('2d').drawImage(c.el, 0, 0, cw, ch);
              f = { src: lc.cv, w: cw, h: ch };
            } else f = { src: c.el, w: vw, h: vh };
          }
        }
        if (f && S.belleza.on && BEL.listo()) {
          const mc = masc.get(i) || document.createElement('canvas'); masc.set(i, mc);
          BEL.mascara(f.src, mc);
          f.mascara = mc;
        }
        cuadros.set(i, f);
      }
      dibujarCuadro(t, (i) => cuadros.get(i), W, H, lctx);
      if (motor.gl.isContextLost()) {
        if (++recuperaciones > 4) throw new Error('memoria');
        poner(3 + (n / N) * 95, 'El celular liberó memoria, retomando…');
        if (!(await motor.recuperar(3000))) { motor = new Motor(); }
        motor.tamano(W, H);
        masc.clear();
        dibujarCuadro(t, (i) => cuadros.get(i), W, H, lctx);
        if (motor.gl.isContextLost()) throw new Error('memoria');
      }
      await vsrc.add(t, 1 / fps);
      if (asrc && t + segAudio > audioHasta && audioHasta < L.total) await pedazoAudio(Math.min(L.total, audioHasta + segAudio * 2));
      if (n % 5 === 0) {
        const pct = (n + 1) / N;
        const seg = (performance.now() - t0) / 1000;
        const falta = pct > 0.02 ? Math.round(seg / pct - seg) : null;
        poner(3 + pct * 95, `Exportando ${Math.round(pct * 100)}%` + (falta != null ? ` · faltan ${falta < 60 ? falta + ' s' : Math.round(falta / 60) + ' min'}` : ''));
      }
    }
    if (tiempoReal) S.clips.forEach(c => { if (c.el) { c.el.pause(); try { c.el.playbackRate = 1; } catch (e) { /* nada */ } } });
    await pedazoAudio(L.total);
    poner(99, 'Terminando el archivo…');
    await out.finalize();
  } catch (e) {
    try { await out.cancel(); } catch (e2) { /* nada */ }
    S.clips.forEach(c => { if (c.el) { c.el.pause(); try { c.el.playbackRate = 1; } catch (e3) { /* nada */ } } });
    S.exportando = false;
    motor.tamano(vistaW, vistaH); S.dirty = true;
    if (e.message === 'cancelado') { $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true; aviso('Exportación cancelada'); return; }
    throw e;
  }
  S.exportando = false;
  motor.tamano(vistaW, vistaH); S.dirty = true;

  const blob = await archivo();
  const nombre = `localprocut-${new Date().toISOString().slice(0, 10)}-${EXP.res === 2160 ? '4k' : EXP.res + 'p'}${fps}.mp4`;
  const file = new File([blob], nombre, { type: 'video/mp4' });
  const url = URL.createObjectURL(file);
  $('.cut-exp-run', m).hidden = true;
  const fin = $('.cut-exp-fin', m);
  fin.hidden = false;
  $('video', fin).src = url;
  $('[data-bajar]', fin).onclick = () => { const a = document.createElement('a'); a.href = url; a.download = nombre; document.body.appendChild(a); a.click(); a.remove(); };
  const comp = $('[data-compartir]', fin);
  comp.hidden = !(navigator.canShare && navigator.canShare({ files: [file] }));
  comp.onclick = () => navigator.share({ files: [file], title: 'Mi video' }).catch(() => {});
  $('[data-otra]', fin).onclick = () => { $('.cut-exp-op', m).hidden = false; fin.hidden = true; };
  aviso(`¡Listo! Su video quedó en ${RESOL[EXP.res].split(' ')[0]} · ${fps} fps ✅`);
}

/* ---------- guardar el proyecto en el equipo (si se recarga la página, sigue) ---------- */
const DB = {
  db: null,
  abrir() {
    if (this.db) return Promise.resolve(this.db);
    return new Promise((ok, mal) => {
      const r = indexedDB.open('localprocut', 1);
      r.onupgradeneeded = () => { r.result.createObjectStore('archivos'); r.result.createObjectStore('estado'); };
      r.onsuccess = () => { this.db = r.result; ok(this.db); };
      r.onerror = () => mal(r.error);
    });
  },
  async put(st, k, v) { try { const db = await this.abrir(); await new Promise((ok, mal) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).put(v, k); tx.oncomplete = ok; tx.onerror = () => mal(tx.error); }); } catch (e) { /* sin espacio: seguimos */ } },
  async get(st, k) { try { const db = await this.abrir(); return await new Promise((ok) => { const r = db.transaction(st).objectStore(st).get(k); r.onsuccess = () => ok(r.result); r.onerror = () => ok(null); }); } catch (e) { return null; } },
  async limpiar() { try { const db = await this.abrir(); for (const st of ['archivos', 'estado']) await new Promise((ok) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).clear(); tx.oncomplete = ok; tx.onerror = ok; }); } catch (e) { /* nada */ } },
};
let tGuardar = null;
function guardar() { registrarHist(); guardarDB(); }
function guardarDB() {
  clearTimeout(tGuardar);
  tGuardar = setTimeout(() => {
    DB.put('estado', 'actual', {
      clips: S.clips.map(c => ({ id: c.id, fileId: c.fileId, nombre: c.nombre, tipo: c.tipo, in: c.in, out: c.out, dur: c.dur, mute: c.mute, vol: c.vol ?? 1, trans: c.trans, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null })),
      musica: S.musica.map(m => ({ id: m.id, fileId: m.fileId, nombre: m.nombre, start: m.start, in: m.in, out: m.out ?? null, dur: m.dur, vol: m.vol, fadeIn: m.fadeIn, fadeOut: m.fadeOut })),
      silenciar: !!S.silenciar,
      subs: S.subs, segs: S.segs, sub: S.sub, belleza: S.belleza, aspecto: S.aspecto, fecha: Date.now(),
    });
  }, 400);
}
async function recuperar() {
  const e = await DB.get('estado', 'actual');
  if (!e?.clips?.length) return;
  const si = await preguntar('Tiene un video sin terminar', `Lo dejó el ${new Date(e.fecha).toLocaleString('es-CO')}. ¿Quiere seguir editándolo?`, 'Seguir editando', 'Empezar uno nuevo');
  if (!si) { await DB.limpiar(); return; }
  $('#cutVacio').hidden = true;
  cargandoMsg('Abriendo su video…');
  Object.assign(S.sub, e.sub || {}); Object.assign(S.belleza, e.belleza || {});
  S.aspecto = e.aspecto || '9:16'; S.subs = e.subs || []; S.segs = e.segs || [];
  for (const d of e.clips) {
    const file = await DB.get('archivos', d.fileId);
    if (!file) continue;
    const c = { ...d, file };
    try { await prepararClip(c); S.clips.push(reg(c)); } catch (err) { /* archivo dañado */ }
  }
  S.silenciar = !!e.silenciar;
  for (const d of (e.musica || [])) {
    const file = await DB.get('archivos', d.fileId);
    if (!file) continue;
    const m = reg({ ...d, file });
    try { await prepararMusica(m); S.musica.push(m); } catch (err) { /* nada */ }
  }
  cargandoMsg(null);
  if (!S.clips.length) { $('#cutVacio').hidden = false; return; }
  S.sel = S.clips[0].id;
  ajustarVista(); pintarLinea(); pintarPanel();
  HIST.pila = []; HIST.pos = -1; registrarHist();
  if (S.belleza.on) activarBelleza();
}
async function nuevoProyecto() {
  if (S.clips.length && !(await preguntar('¿Empezar un video nuevo?', 'Se borra lo que tiene en la línea de tiempo.', 'Empezar de nuevo'))) return;
  pausar();
  S.clips.forEach(c => { if (c.el) { c.el.pause(); c.el.remove(); } });
  S.musica.forEach(m => { if (m.el) { m.el.pause(); m.el.remove(); } });
  S.clips = []; S.subs = []; S.segs = []; S.musica = []; S.sel = null; S.selM = null; S.t = 0; S.silenciar = false;
  REG.clear(); HIST.pila = []; HIST.pos = -1;
  await DB.limpiar();
  $('#cutVacio').hidden = false;
  registrarHist();
  pintarLinea(); abrirTab(innerWidth >= 1000 ? 'editar' : null);
}

/* ---------- zoom y encuadre con los dedos sobre el video ---------- */
const dedos = new Map();
let gesto = null;
function clipBajoLinea() {
  const act = activos(S.t);
  if (!act.length) return null;
  const sel = act.map(i => S.clips[i]).find(c => c.id === S.sel);
  return sel || S.clips[act[act.length - 1]];
}
vista.style.touchAction = 'none';
vista.addEventListener('pointerdown', (e) => {
  const c = clipBajoLinea(); if (!c) return;
  if (S.playing) pausar();
  vista.setPointerCapture(e.pointerId);
  dedos.set(e.pointerId, { x: e.clientX, y: e.clientY });
  if (S.sel !== c.id) { S.sel = c.id; S.selM = null; pintarLinea(); }
  const pts = [...dedos.values()];
  const k0 = gesto?.k || null;
  gesto = { c, k: k0, z0: k0?.z || 1, x0: k0?.x || 0, y0: k0?.y || 0, p0: pts.map(q => ({ ...q })), d0: pts.length > 1 ? Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y) : 0 };
});
vista.addEventListener('pointermove', (e) => {
  if (!gesto || !dedos.has(e.pointerId)) return;
  dedos.set(e.pointerId, { x: e.clientX, y: e.clientY });
  const c = gesto.c, r = vista.getBoundingClientRect(), pts = [...dedos.values()];
  if (!gesto.k) {
    const dm = Math.hypot(pts[0].x - gesto.p0[0].x, pts[0].y - gesto.p0[0].y);
    if (dm < 4 && pts.length === gesto.p0.length) return;
    gesto.k = puntoZoom(c); gesto.z0 = gesto.k.z || 1; gesto.x0 = gesto.k.x || 0; gesto.y0 = gesto.k.y || 0;
    gesto.p0 = pts.map(q => ({ ...q }));
    if (pts.length > 1) gesto.d0 = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
  }
  const k = gesto.k;
  if (pts.length > 1 && gesto.d0) {
    const d = Math.hypot(pts[0].x - pts[1].x, pts[0].y - pts[1].y);
    k.z = Math.max(1, Math.min(4, gesto.z0 * d / gesto.d0));
  } else if (pts.length === 1 && gesto.p0.length === 1) {
    // Arrastrar: la imagen sigue al dedo
    const ao = vista.width / vista.height, as = (c.w || 1) / (c.h || 1);
    const z = k.z || 1;
    const sc = (as > ao ? [ao / as, 1] : [1, as / ao]).map(v => v / z);
    const mx = (1 - sc[0]) / 2, my = (1 - sc[1]) / 2;
    const dx = (pts[0].x - gesto.p0[0].x) / r.width, dy = (pts[0].y - gesto.p0[0].y) / r.height;
    if (mx > 0.001) k.x = Math.max(-1, Math.min(1, gesto.x0 - dx * sc[0] / mx));
    if (my > 0.001) k.y = Math.max(-1, Math.min(1, gesto.y0 - dy * sc[1] / my));
  }
  S.dirty = true;
  clearTimeout(vista._tg);
  vista._tg = setTimeout(() => { guardar(); if (S.tab === 'editar') pintarPanel(); }, 450);
});
const soltar = (e) => {
  if (!dedos.has(e.pointerId)) return;
  dedos.delete(e.pointerId);
  if (gesto) {
    const c = gesto.c;
    const pts = [...dedos.values()];
    const k = gesto.k;
    gesto = pts.length ? { c, k, z0: k?.z || 1, x0: k?.x || 0, y0: k?.y || 0, p0: pts.map(q => ({ ...q })), d0: 0 } : null;
    if (!pts.length && k) { clearTimeout(vista._tg); guardar(); if (S.tab === 'editar') pintarPanel(); }
  }
};
vista.addEventListener('pointerup', soltar);
vista.addEventListener('pointercancel', soltar);
vista.addEventListener('wheel', (e) => {
  const c = clipBajoLinea(); if (!c) return;
  e.preventDefault();
  const k = puntoZoom(c);
  k.z = Math.max(1, Math.min(4, (k.z || 1) * (e.deltaY < 0 ? 1.08 : 1 / 1.08)));
  S.dirty = true;
  clearTimeout(vista._tz); vista._tz = setTimeout(() => { guardar(); if (S.tab === 'editar') pintarPanel(); }, 300);
}, { passive: false });

/* ---------- X para cerrar todas las ventanas (y tocar afuera) ---------- */
function cerrarModal(m) {
  if (m.id === 'cutExportar' && !$('.cut-exp-run', m).hidden) { EXP.cancelar = true; return; } // primero cancela la exportación
  if (m.id === 'cutPregunta') { $('[data-no]', m).click(); return; }
  const v = $('video', m); if (v) v.pause();
  m.hidden = true;
}
$$('.cut-modal').forEach((m) => {
  const h = $('.cut-hoja', m);
  const x = document.createElement('button');
  x.type = 'button'; x.className = 'cut-x'; x.setAttribute('aria-label', 'Cerrar');
  x.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  x.onclick = () => cerrarModal(m);
  h.prepend(x);
  m.addEventListener('click', (e) => { if (e.target === m && !(m.id === 'cutExportar' && !$('.cut-exp-run', m).hidden)) cerrarModal(m); });
});

/* ---------- arranque ---------- */
$('#cutPlay').addEventListener('click', () => (S.playing ? pausar() : reproducir()));
$('#cutZoomMas').addEventListener('click', () => { S.pps = Math.min(240, S.pps * 1.4); pintarLinea(); });
$('#cutZoomMenos').addEventListener('click', () => { S.pps = Math.max(12, S.pps / 1.4); pintarLinea(); });
window.addEventListener('resize', () => { ajustarVista(); pintarLinea(); });
document.addEventListener('dragover', (e) => e.preventDefault());
document.addEventListener('drop', (e) => { e.preventDefault(); if (e.dataTransfer?.files?.length) agregarArchivos(e.dataTransfer.files); });

try {
  motor = new Motor();
} catch (e) {
  $('#cutVacio').innerHTML = `<h2>Su navegador no alcanza</h2><p>${esc(e.message)}</p>`;
  throw e;
}
ajustarVista();
pintarLinea();
abrirTab(innerWidth >= 1000 ? 'editar' : null);
registrarHist();
requestAnimationFrame(bucle);
recuperar();
document.fonts.load('900 40px Montserrat');
document.fonts.load('italic 700 40px "Playfair Display"');
document.fonts.load('700 40px "Space Mono"');
window.__cut = S;
