/* LocalProCut · editor de video en el navegador (celular primero).
   Todo pasa en el equipo del usuario: los videos no se suben al servidor. */
import * as MB from './mediabunny.min.js';
import { Motor, TRANSICIONES, EFECTOS, FILTROS, ANIMACIONES } from './motor.js';
import * as SUB from './subtitulos.js';
import * as BEL from './belleza.js';

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const fmt = (t) => { t = Math.max(0, t); const m = Math.floor(t / 60), s = Math.floor(t % 60), d = Math.floor((t % 1) * 10); return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}.${d}`; };
const esc = (s) => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const contar = (tipo, datos) => { try { window.cutContar?.(tipo, datos); } catch (e) { /* el contador nunca debe dañar el editor */ } };
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
  // Como CapCut: filtro y ajustes para todo el video, fondo cuando el video no llena el formato, capas (stickers y fotos) y portada
  filtro: { id: 'ninguno', k: 1 },
  ajuste: { brillo: 0, contraste: 0, saturacion: 0, temperatura: 0, vineta: 0 },
  fondo: { modo: 'llenar', color: '#000000' },
  capas: [], selC: null, portada: null,
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
  const ms = Math.min(9000, 2600 + String(msg).length * 45);
  setTimeout(() => d.classList.add('fuera'), ms);
  setTimeout(() => d.remove(), ms + 500);
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
    try { const src = a.createMediaElementSource(el); el._src = src; el._g = a.createGain(); src.connect(el._g).connect(a.destination); } catch (e) { el._gFallo = true; }
  }
  if (el._g) el._g.gain.value = v; else { try { el.volume = Math.max(0, Math.min(1, v)); } catch (e) { /* nada */ } }
}

// Silenciar un video. Ojo: el iPhone PAUSA solo los videos mudos que no se ven en pantalla (la vista previa se quedaba quieta);
// por eso los videos van dentro de la pantalla (#cutVideos, 2 px casi invisibles) y no afuera.
function sonido(v, sil, vol = 1) {
  v.muted = !!sil;
  if (!sil) volumen(v, vol);
}

/* ---------- deshacer / rehacer ---------- */
const REG = new Map();
function reg(o) { REG.set(o.id, o); return o; }
const HIST = { pila: [], pos: -1, aplicando: false };
function foto() {
  return JSON.stringify({
    clips: S.clips.map(c => ({ id: c.id, in: c.in, out: c.out, mute: !!c.mute, vol: c.vol ?? 1, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null, trans: c.trans, ef: c.ef || 'ninguno', anim: c.anim || null, limpio: !!c.limpio })),
    capas: S.capas.map(datosCapa), filtro: S.filtro, ajuste: S.ajuste, fondo: S.fondo,
    musica: S.musica.map(m => ({ id: m.id, start: m.start, in: m.in, out: m.out ?? null, vol: m.vol ?? 1, fadeIn: !!m.fadeIn, fadeOut: !!m.fadeOut, limpio: !!m.limpio, mejor: !!m.mejor, aislar: !!m.aislar, efa: m.efa || '', vel: m.vel || 1, ritmos: m.ritmos || null, kfv: m.kfv?.length ? m.kfv.map(k => ({ ...k })) : null })),
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
  S.capas = (d.capas || []).map(m => Object.assign(REG.get(m.id) || {}, m)).filter(x => x.id && (x.tipo !== 'imagen' || x.bmp));
  if (d.filtro) S.filtro = { ...d.filtro };
  if (d.ajuste) S.ajuste = { ...d.ajuste };
  if (d.fondo) S.fondo = { ...d.fondo };
  if (!S.capas.some(x => x.id === S.selC)) S.selC = null;
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
      motor.clip(k, f?.src, f?.w, f?.h, S.belleza.on ? f?.mascara : null, S.belleza, encuadreEn(cc, t - L.start[i]), opClip(cc, t - L.start[i]));
    });
    if (act.length === 2) {
      const c = S.clips[act[0]];
      const p = (t - L.start[act[1]]) / Math.max(0.01, L.tr[act[0]]);
      motor.mezclar((TRANSICIONES.find(x => x.id === c.trans.tipo) || TRANSICIONES[2]).t, p);
    } else motor.mezclar(2, 0);
  }
  ctx.drawImage(motor.canvas, 0, 0, W, H);
  if (S.capas.length) dibujarCapas(ctx, W, H, t);
  if (S.subs.length) SUB.dibujar(ctx, W, H, t, S.subs, S.sub);
}
const hexRgb = (h) => [1, 3, 5].map(i => parseInt(h.slice(i, i + 2), 16) / 255);
function opClip(c, lt) {
  return {
    fit: S.fondo.modo, bg: hexRgb(S.fondo.color || '#000000'), adj: S.ajuste,
    fil: (FILTROS.find(f => f.id === S.filtro.id) || FILTROS[0]).t, filK: S.filtro.k ?? 1,
    ef: (EFECTOS.find(e => e.id === c.ef) || EFECTOS[0]).t, t: lt,
    ...animEn(c, lt),
  };
}
// Animación de entrada/salida en el momento lt del clip: tipo y cuánto falta (1 = todo aplicado)
function animEn(c, lt) {
  const an = c.anim; if (!an || (!an.in && !an.out)) return {};
  const L = largo(c), d = Math.min(an.dur || 0.5, L / 2);
  const suave = (x) => x * x * (3 - 2 * x);
  if (an.in && lt < d) return { anT: (ANIMACIONES.find(a => a.id === an.in) || {}).t || 0, anA: 1 - suave(lt / d) };
  if (an.out && lt > L - d) return { anT: (ANIMACIONES.find(a => a.id === an.out) || {}).t || 0, anA: suave((lt - (L - d)) / d) };
  return {};
}

/* ---------- capas: stickers y fotos/logos encima del video (Superposición) ---------- */
function datosCapa(x) { return { id: x.id, tipo: x.tipo, emoji: x.emoji || null, fileId: x.fileId || null, nombre: x.nombre || '', start: x.start, end: x.end, x: x.x, y: x.y, tam: x.tam }; }
function dibujarCapas(ctx, W, H, t) {
  for (const k of S.capas) {
    if (t < k.start || t >= k.end) continue;
    const cx = k.x * W, cy = k.y * H, w = k.tam * Math.min(W, H);
    // Entra con un pequeño "pop" (como los stickers de CapCut)
    const e = Math.min(1, (t - k.start) / 0.25), pop = e < 1 ? 0.6 + 0.4 * (1 - Math.pow(1 - e, 3)) * 1.08 : 1;
    ctx.save();
    ctx.translate(cx, cy); ctx.scale(pop, pop);
    if (k.tipo === 'sticker') {
      ctx.font = `${Math.round(w)}px "Apple Color Emoji","Segoe UI Emoji","Noto Color Emoji",sans-serif`;
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillText(k.emoji, 0, 0);
    } else if (k.bmp) {
      const h = w * k.bmp.height / k.bmp.width;
      ctx.drawImage(k.bmp, -w / 2, -h / 2, w, h);
    }
    ctx.restore();
  }
}
function nuevaCapa(datos) {
  if (!S.clips.length) { aviso('Primero agregue un video'); return null; }
  const L = lay();
  const ini = S.t >= L.total - 0.2 ? 0 : S.t;
  const k = reg({ id: uid(), start: ini, end: Math.min(Math.max(L.total, ini + 0.5), ini + 3), x: 0.5, y: 0.35, tam: 0.3, ...datos });
  S.capas.push(k);
  S.selC = k.id; S.sel = null; S.selM = null; S.barraClip = false;
  abrirTab('capa'); pintarLinea(); guardar();
  return k;
}
async function cargarCapaImagen(k) {
  k.bmp = await abrirImagen(k.file);
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
  // iPhone: subir el <video> directo a la parte gráfica (WebGL) deja la imagen pegada en el mismo cuadro con los
  // videos HEVC/HDR de la cámara aunque el video sí avance. Se copia primero a un lienzo 2D (camino que Safari sí
  // actualiza) y ya reducido al tamaño de la vista previa, que además es más liviano que subir un cuadro 4K.
  if (IOS || S.copia2D || Math.max(v.videoWidth, v.videoHeight) > 2 * Math.max(vista.width, vista.height)) {
    const k = Math.min(1, Math.max(vista.width / v.videoWidth, vista.height / v.videoHeight) * 1.25);
    const cw = Math.max(2, Math.round(v.videoWidth * k)), ch = Math.max(2, Math.round(v.videoHeight * k));
    const cv = c._prev ||= document.createElement('canvas');
    if (cv.width !== cw || cv.height !== ch) { cv.width = cw; cv.height = ch; c._prevT = -1; }
    if (c._prevT !== v.currentTime || S.playing) {
      try { cv.getContext('2d').drawImage(v, 0, 0, cw, ch); c._prevT = v.currentTime; } catch (e) { return { src: v, w: v.videoWidth, h: v.videoHeight, mascara: c.mascara }; }
      vigilarPegada(c, cv);
    }
    return { src: cv, w: cw, h: ch, mascara: c.mascara };
  }
  return { src: v, w: v.videoWidth, h: v.videoHeight, mascara: c.mascara };
}
// Si la línea avanza y el video también pero la imagen no cambia (o el video no arranca), se reporta solo una vez
// con los datos del video, para poder arreglarlo en ese celular (Admin → Opiniones y fallas → Errores automáticos)
let pegadaReportada = false;
function vigilarPegada(c, cv) {
  if (pegadaReportada || !S.playing) return;
  const ahora = performance.now();
  if (ahora - (c._vgT || 0) < 1000) return;
  c._vgT = ahora;
  let huella = '';
  // Huella de 16×16 con todo el detalle: en un video de cámara real el ruido del sensor la cambia siempre
  try { const m = vigilarPegada.cv ||= Object.assign(document.createElement('canvas'), { width: 16, height: 16 }); const x = m.getContext('2d', { willReadFrequently: true }); x.drawImage(cv, 0, 0, 16, 16); huella = x.getImageData(0, 0, 16, 16).data.join(','); } catch (e) { return; }
  const vt = c.el.currentTime;
  if (huella === c._vgH && vt - (c._vgV ?? vt) > 0.5) c._vgN = (c._vgN || 0) + 1; else c._vgN = 0;
  c._vgH = huella; c._vgV = vt;
  if (c._vgN >= 4) { pegadaReportada = true; reportarVideo('Vista previa: la imagen se queda pegada aunque el video avanza', c); }
}
function reportarVideo(msg, c) {
  const v = c.el || {};
  window.LPOpinion?.error(new Error(msg), {
    fase: 'reproducir', ios: IOS, paused: v.paused, readyState: v.readyState, networkState: v.networkState, error: v.error?.code || null,
    t: Math.round((v.currentTime || 0) * 10) / 10, px: (v.videoWidth || 0) + 'x' + (v.videoHeight || 0), tipo: c.file?.type || '?', mb: c.file ? Math.round(c.file.size / 1e5) / 10 : null,
    vel: c.vel || 1, curva: c.curva || null, mudo: !!v.muted, belleza: !!S.belleza.on,
  });
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
    sonido(v, !!c.mute || !!S.silenciar, c.vol ?? 1);
    if (claveCad(c) !== (v._cadKey ?? claveCad({}))) aplicarLimpieza(c);
    if (act && S.playing) {
      if (v.paused) {
        // Intenta reproducir (sin insistir cada cuadro, eso lo dejaba buscando y la imagen quieta);
        // si el navegador no lo deja, por lo menos mueve la imagen al tiempo de la línea
        const ahora = performance.now();
        if (!v._pt || ahora - v._pt > 900) { v._pt = ahora; v.currentTime = loc; v.play().catch(() => {}); }
        else if (ahora - v._pt > 300 && !v.seeking && Math.abs(v.currentTime - loc) > 0.1) v.currentTime = loc;
      }
      else if (Math.abs(v.currentTime - loc) > 0.8 * Math.max(1, vel)) v.currentTime = loc;
    } else {
      if (!v.paused) v.pause();
      if (act && Math.abs(v.currentTime - loc) > 0.03 && !v.seeking) v.currentTime = loc;
    }
  });
  for (const m of S.musica) {
    const a = m.el; if (!a) continue;
    const act = S.playing && S.t >= m.start && S.t < Math.min(m.start + largoM(m), L.total);
    const loc = m.in + (S.t - m.start) * (m.vel || 1);
    volumen(a, volEn(m, S.t));
    if (claveCad(m) !== (a._cadKey ?? claveCad({}))) aplicarLimpieza(m);
    if (Math.abs(a.playbackRate - (m.vel || 1)) > 0.01) { try { a.preservesPitch = false; a.playbackRate = m.vel || 1; } catch (e) { /* nada */ } }
    if (act) { if (a.paused) { a.currentTime = loc; a.play().catch(() => {}); } else if (Math.abs(a.currentTime - loc) > 0.3) a.currentTime = loc; }
    else if (!a.paused) a.pause();
  }
}
let ignorarScroll = false;
// Tiempo de la línea que corresponde al segundo "ct" del archivo (inverso de tOrigen, sirve también con curvas)
function ltDe(c, ct) {
  let a = 0, b = largo(c);
  for (let k = 0; k < 22; k++) { const m = (a + b) / 2; if (tOrigen(c, m) < ct) a = m; else b = m; }
  return (a + b) / 2;
}
// Reloj de la reproducción: manda el video que está sonando (como CapCut). Si el video se demora en arrancar
// o en cargar (videos pesados del iPhone), la línea lo espera en vez de adelantarse y obligarlo a saltar sin parar.
function relojVideo(L, ahora) {
  for (const i of activos(S.t, L)) {
    const c = S.clips[i];
    if (c.tipo !== 'video' || !c.el) continue;
    const v = c.el;
    // El clip llegó a su final: pasar al siguiente
    if (v.ended || v.currentTime >= c.out - 0.04) return Math.max(S.t, L.start[i] + largo(c));
    if (S._esperaId !== c.id) { S._esperaId = c.id; S._espera = 0; }
    if (v.paused || v.seeking || v.readyState < 3) {
      S._espera ||= ahora;
      if (ahora - S._espera > 1500) { if (!S._esperaRep) { S._esperaRep = true; reportarVideo('Vista previa: el video no arranca o se detiene (la línea sigue sola)', c); } return null; } // si no arranca en 1.5 s, sigue con el reloj normal
      return S.t;
    }
    S._espera = 0;
    const t = L.start[i] + ltDe(c, v.currentTime);
    return Math.max(S.t, Math.min(t, L.start[i] + largo(c)));
  }
  return null;
}
function bucle(ahora) {
  if (S.playing) {
    const L = lay();
    const rv = relojVideo(L, ahora);
    if (rv != null) { S.t = rv; S.t0 = rv; S.inicio = ahora; }
    else S.t = S.t0 + (ahora - S.inicio) / 1000;
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
    const cc = S.clips.length ? clipBajoLinea() : null, lt = cc ? ltClip(cc) : 0;
    const mk = S.selM && S.musica.find(x => x.id === S.selM);
    $('#cutClave').classList.toggle('on', mk ? !!kfvCerca(mk) : !!cc?.kf?.some(q => q.t > 0.01 && Math.abs(q.t - lt) < 0.12));
    if (S.tab === 'm-vol' && mk) refrescarVol(mk);
  }
  requestAnimationFrame(bucle);
}
function reproducir(hasta = null) {
  if (!S.clips.length) return;
  const L = lay();
  if (S.t >= L.total - 0.05) S.t = 0;
  audioCtx();
  S.playing = true; S.t0 = S.t; S.inicio = performance.now(); S.hasta = hasta; S._espera = 0;
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
  const ancho = Math.max(L.total, ...S.musica.map(finM), ...S.subs.map(x => x.end), ...S.capas.map(x => x.end)) * S.pps;
  cont.style.width = (ancho + pad * 2 + 60) + 'px';
  // Regla de tiempos
  const regla = $('#cutRegla');
  const paso = [0.5, 1, 2, 5, 10, 30, 60].find(x => x * S.pps >= 70) || 60;
  let rh = '';
  for (let t = 0; t <= Math.max(L.total, 1) + paso; t += paso) rh += `<span style="left:${pad + t * S.pps}px">${fmt(t).slice(0, 5)}</span><i style="left:${pad + (t + paso / 2) * S.pps}px"></i>`;
  regla.innerHTML = rh;
  // Columna izquierda (como CapCut): silenciar audio del clip, portada y los íconos de las filas
  const izq = $('#cutIzq');
  izq.style.left = Math.max(2, pad - 158) + 'px';
  const fotoPortada = S.portada?.url || S.clips[0]?.tira || '';
  izq.innerHTML = `<button type="button" class="cut-sil ${S.silenciar ? 'on' : ''}" data-silenciar>${S.silenciar ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="m17 9 4 6M21 9l-4 6"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="M16.5 8.5a5 5 0 0 1 0 7"/></svg>'}<small>${S.silenciar ? 'Reactivar audio<br>del clip' : 'Silenciar audio<br>del clip'}</small></button>
    <button type="button" class="cut-portada" data-portada style="${fotoPortada ? `background-image:url(${fotoPortada})` : ''}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></svg><small>Portada</small></button>
    <span class="cut-fila-ic" style="top:68px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18V5l9-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="15" cy="16" r="3"/></svg></span><span class="cut-fila-ic" style="top:102px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5h14M12 5v15"/></svg></span>${S.capas.length ? '<span class="cut-fila-ic" style="top:136px">😀</span>' : ''}`;
  document.body.classList.toggle('con-capas', S.capas.length > 0);


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
    const etqVel = c.tipo === 'video' && c.curva ? CURVAS[c.curva].n : (c.tipo === 'video' && (c.vel || 1) !== 1 ? c.vel + 'x' : '');
    const icVel = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M20 12a8 8 0 1 1-3-6.2"/><path d="m12 12 5-5"/></svg>';
    const icMudo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="m17 9 4 6M21 9l-4 6"/></svg>';
    const ef = c.ef && c.ef !== 'ninguno' ? EFECTOS.find(x => x.id === c.ef) : null;
    b.innerHTML = `<em>${etqVel ? icVel + esc(etqVel) : ''}${c.tipo === 'video' && (c.mute || S.silenciar) ? icMudo : ''}${ef ? `<i class="ef">${ef.e}</i>` : ''}</em>` + `<span>${c.tipo === 'imagen' ? '🖼 ' : ''}${fmt(largo(c)).slice(0, 5)}</span>`;
    b.addEventListener('pointerdown', (e) => presionarClip(e, c));
    b.onclick = (e) => { e.stopPropagation(); if (b._recorto) { b._recorto = false; return; } if (Date.now() - (S._finOrden || 0) < 500) return; S.sel = c.id; S.selM = null; S.selC = null; S.barraClip = true; S.barraAudio = false; abrirTab(TABS_CLIP.includes(S.tab) ? S.tab : null); pintarLinea(); };
    // Tiradores para recortar arrastrando los bordes (como CapCut)
    if (S.sel === c.id) {
      for (const lado of ['izq', 'der']) {
        const h = document.createElement('i');
        h.className = 'cut-tir ' + lado;
        h.addEventListener('pointerdown', (ev) => recortarBorde(ev, c, lado, b));
        b.appendChild(h);
      }
    }
    pista.appendChild(b);
    if (i < S.clips.length - 1) {
      const tb = document.createElement('button');
      tb.type = 'button';
      tb.className = 'cut-rombo' + (c.trans.tipo !== 'ninguna' ? ' on' : '');
      tb.title = 'Transición';
      tb.style.left = (pad + (L.start[i + 1] + L.tr[i] / 2) * S.pps - 12) + 'px';
      tb.innerHTML = c.trans.tipo !== 'ninguna' ? '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 5l8 7-8 7zM20 5l-8 7 8 7z"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"><path d="M4 5l8 7-8 7zM20 5l-8 7 8 7z"/></svg>';
      tb.onclick = (e) => { e.stopPropagation(); S.sel = c.id; S.selM = null; S.barraClip = true; abrirTab('transicion'); pintarLinea(); };
      pista.appendChild(tb);
    }
  });
  const mas = document.createElement('button');
  mas.type = 'button';
  mas.className = 'cut-mas';
  mas.style.left = (pad + L.total * S.pps + 8) + 'px';
  mas.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>';
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
    b.innerHTML = `<span>${esc(m.nombre.replace(/\.[^.]+$/, ''))}</span>` + (m.ritmos?.length ? m.ritmos.filter(t => t >= (m.in || 0) && t <= (m.out ?? m.dur)).map(t => `<i class="rit" style="left:${(t - (m.in || 0)) / (m.vel || 1) * S.pps}px"></i>`).join('') : '');
    if (m.onda) { const v = m.vel || 1; b.style.backgroundImage = `url(${m.onda})`; b.style.backgroundSize = `${(m.dur || largoM(m)) / v * S.pps}px 100%`; b.style.backgroundPositionX = `${-(m.in || 0) / v * S.pps}px`; }
    else ondas(m);
    if (m.kfv?.length) b.insertAdjacentHTML('beforeend', m.kfv.map(k => `<i class="kfv ${S.selM === m.id ? '' : 'apag'}" style="left:${k.t * S.pps}px"></i>`).join(''));
    b.onclick = (e) => { e.stopPropagation(); S.selM = m.id; S.sel = null; S.selC = null; S.barraClip = false; S.barraAudio = false; abrirTab(innerWidth < 1000 ? null : 'audio'); pintarLinea(); };
    pa.appendChild(b);
  });
  const addA = document.createElement('button');
  addA.type = 'button'; addA.className = 'cut-agregar';
  const finMus = S.musica.length ? Math.max(...S.musica.map(m => Math.min(finM(m), L.total))) : 0;
  addA.style.left = (pad + finMus * S.pps + (S.musica.length ? 6 : 0)) + 'px';
  addA.innerHTML = '+ Agregar audio';
  addA.onclick = (e) => { e.stopPropagation(); if (innerWidth < 1000) return abrirBarraAudio(); S.selM = null; S.sel = null; abrirTab('audio'); pintarLinea(); };
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
  // Fila de capas (stickers y fotos)
  const pc = $('#cutPistaC');
  pc.innerHTML = '';
  S.capas.forEach((k) => {
    const b = document.createElement('div');
    b.className = 'cut-capa' + (S.selC === k.id ? ' sel' : '');
    b.style.left = (pad + k.start * S.pps) + 'px';
    b.style.width = Math.max(14, (k.end - k.start) * S.pps - 2) + 'px';
    b.textContent = k.tipo === 'sticker' ? k.emoji + ' Sticker' : '🖼 ' + (k.nombre || 'Foto');
    b.onclick = (e) => { e.stopPropagation(); S.selC = k.id; S.sel = null; S.selM = null; S.barraClip = false; abrirTab('capa'); pintarLinea(); };
    pc.appendChild(b);
  });
  moverLinea();
  S.dirty = true;
  try { pintarBarra(); } catch (e) { /* la barra aún no está lista */ }
}

/* ---------- mantener presionado un clip para cambiarlo de lugar (como CapCut) ----------
   Los clips se vuelven cuadritos con su miniatura, el que se tocó sigue el dedo y los demás le abren espacio. */
const ORD = { paso: 60, lado: 52, margen: 14 };
let orden = null, ordenEspera = null;
function presionarClip(e, c) {
  if (S.clips.length < 2 || orden || (e.pointerType === 'mouse' && e.button !== 0)) return;
  const x0 = e.clientX, y0 = e.clientY, id = e.pointerId;
  const cancelar = () => { clearTimeout(ordenEspera); ordenEspera = null; window.removeEventListener('pointermove', mov); window.removeEventListener('pointerup', cancelar); window.removeEventListener('pointercancel', cancelar); };
  const mov = (ev) => { if (ev.pointerId === id && Math.hypot(ev.clientX - x0, ev.clientY - y0) > 9) cancelar(); }; // se movió: es para deslizar la línea
  window.addEventListener('pointermove', mov);
  window.addEventListener('pointerup', cancelar);
  window.addEventListener('pointercancel', cancelar);
  clearTimeout(ordenEspera);
  ordenEspera = setTimeout(() => { cancelar(); empezarOrden(c, x0, id); }, 380);
}
function empezarOrden(c, x, idPuntero) {
  if (S.playing) pausar();
  try { navigator.vibrate?.(18); } catch (e) { /* nada */ }
  const caja = linea.getBoundingClientRect(), pista = $('#cutPistaV').getBoundingClientRect();
  const i0 = S.clips.indexOf(c), n = S.clips.length;
  const ov = document.createElement('div');
  ov.className = 'cut-orden';
  Object.assign(ov.style, { left: caja.left + 'px', width: caja.width + 'px', top: (pista.top - 7) + 'px' });
  const fila = document.createElement('div');
  fila.className = 'fila';
  fila.style.width = (n * ORD.paso + ORD.margen * 2) + 'px';
  const cuadro = (k) => {
    const d = document.createElement('div');
    d.className = 'cuad';
    if (k.tira) d.style.backgroundImage = `url(${k.tira})`;
    d.innerHTML = `<span>${k.tipo === 'imagen' ? '🖼' : fmt(largo(k)).slice(0, 5)}</span>`;
    return d;
  };
  const cuads = S.clips.map((k) => { const d = cuadro(k); d._clip = k; return d; });
  const hueco = document.createElement('div'); hueco.className = 'cuad hueco';
  cuads.forEach((d, k) => fila.appendChild(k === i0 ? hueco : d));
  const fant = cuadro(c); fant.classList.add('fantasma');
  ov.append(fila, fant);
  document.body.appendChild(ov);
  document.body.classList.add('ordenando');
  // El cuadrito que se tocó queda debajo del dedo
  ov.scrollLeft = Math.max(0, ORD.margen + i0 * ORD.paso + ORD.lado / 2 - (x - caja.left));
  orden = { c, hueco, fila, ov, fant, caja, x, idPuntero, i: i0 };
  moverFantasma(x);
  const auto = () => {
    if (!orden) return;
    const b = orden.caja;
    if (orden.x < b.left + 44) ov.scrollLeft -= 7; else if (orden.x > b.right - 44) ov.scrollLeft += 7;
    moverFantasma(orden.x);
    requestAnimationFrame(auto);
  };
  requestAnimationFrame(auto);
  window.addEventListener('pointermove', moverOrden);
  window.addEventListener('pointerup', soltarOrden);
  window.addEventListener('pointercancel', soltarOrden);
}
function moverOrden(e) { if (orden && e.pointerId === orden.idPuntero) { orden.x = e.clientX; moverFantasma(e.clientX); } }
function moverFantasma(x) {
  const o = orden; if (!o) return;
  o.fant.style.left = (x - o.caja.left + o.ov.scrollLeft - ORD.lado / 2) + 'px'; // dentro de la fila que se desliza
  const pos = x - o.caja.left + o.ov.scrollLeft - ORD.margen;
  const idx = Math.max(0, Math.min(S.clips.length - 1, Math.floor(pos / ORD.paso)));
  if (idx === o.i) return;
  // Correr el hueco a la nueva posición (los demás cuadritos se acomodan)
  const otros = [...o.fila.children].filter(d => d !== o.hueco);
  o.fila.insertBefore(o.hueco, otros[idx] || null);
  o.i = idx;
}
function soltarOrden(e) {
  const o = orden; if (!o || (e && e.pointerId !== o.idPuntero)) return;
  orden = null;
  window.removeEventListener('pointermove', moverOrden);
  window.removeEventListener('pointerup', soltarOrden);
  window.removeEventListener('pointercancel', soltarOrden);
  o.ov.remove();
  document.body.classList.remove('ordenando');
  S._finOrden = Date.now();
  const antes = S.clips.indexOf(o.c);
  if (o.i !== antes) {
    S.clips.splice(antes, 1);
    S.clips.splice(o.i, 0, o.c);
    S.sel = o.c.id; S.barraClip = true;
    irA(lay().start[o.i] + 0.01);
    aviso('Clip movido ↔️');
    guardar();
  }
  pintarLinea();
}
// Mientras se cambia de lugar un clip, el dedo no desliza la línea ni abre el menú del celular
document.addEventListener('touchmove', (e) => { if (orden) e.preventDefault(); }, { passive: false });
linea.addEventListener('contextmenu', (e) => { if (e.target.closest('.cut-clip')) e.preventDefault(); });

// Silenciar clip: responde al primer toque aunque esté dentro de la línea que se desliza
(() => {
  const izq = $('#cutIzq');
  let ini = null;
  const alternar = () => { S.silenciar = !S.silenciar; sincronizar(); pintarLinea(); guardar(); aviso(S.silenciar ? 'Sonido original silenciado 🔇' : 'Sonido original activado 🔈'); };
  const accion = (e) => (e.target.closest('[data-silenciar]') ? alternar : e.target.closest('[data-portada]') ? () => { S.sel = null; S.selM = null; S.selC = null; abrirTab('portada'); } : null);
  izq.addEventListener('pointerdown', (e) => { if (accion(e)) { ini = { x: e.clientX, y: e.clientY, t: Date.now() }; e.stopPropagation(); } }, true);
  izq.addEventListener('pointerup', (e) => {
    const f = accion(e);
    if (!ini || !f) { ini = null; return; }
    const mov = Math.hypot(e.clientX - ini.x, e.clientY - ini.y);
    ini = null;
    if (mov < 14) { e.preventDefault(); e.stopPropagation(); izq._ultimo = Date.now(); f(); }
  }, true);
  izq.addEventListener('click', (e) => { const f = accion(e); if (f) { e.stopPropagation(); if (Date.now() - (izq._ultimo || 0) > 600) f(); } });
})();

/* ---------- forma de onda del audio (fila azul, como CapCut) ---------- */
async function ondas(m) {
  if (m.onda || m._ondaPend || !m.file || m.file.size > 80e6) return;
  m._ondaPend = true;
  try {
    const buf = await audioCtx().decodeAudioData(await m.file.arrayBuffer());
    const d = buf.getChannelData(0), N = Math.min(1200, Math.max(60, Math.round(buf.duration * 24)));
    const cv = document.createElement('canvas'); cv.width = N * 2; cv.height = 56;
    const g = cv.getContext('2d'); g.fillStyle = '#8fd3ff';
    const paso = Math.floor(d.length / N);
    for (let i = 0; i < N; i++) {
      let mx = 0; for (let j = i * paso, f = j + paso; j < f; j += 16) mx = Math.max(mx, Math.abs(d[j]));
      const h = Math.max(2, Math.min(1, mx * 1.3) * 50);
      g.fillRect(i * 2, 28 - h / 2, 1.4, h);
    }
    m.onda = cv.toDataURL('image/png');
    pintarLinea();
  } catch (e) { /* sin onda */ }
}

/* ---------- portada: el primer cuadro del video (el que muestran estados, TikTok y Reels) ---------- */
function ponerPortada(url, t) {
  const img = new Image();
  img.src = url;
  S.portada = { t, url, img };
}

/* ---------- música ---------- */
// Volumen del audio en el momento t de la línea: con fotogramas clave (◆) cambia de uno a otro, como CapCut
function volEn(m, t) {
  const k = m.kfv; if (!k?.length) return m.vol ?? 1;
  const r = t - m.start;
  if (r <= k[0].t) return k[0].v;
  for (let i = 1; i < k.length; i++) if (r <= k[i].t) { const a = k[i - 1], b = k[i]; return a.v + (b.v - a.v) * (r - a.t) / Math.max(0.001, b.t - a.t); }
  return k[k.length - 1].v;
}
const kfvCerca = (m, t = S.t) => m.kfv?.find(q => Math.abs(q.t - (t - m.start)) < 0.12);
// Pone el volumen: si el audio tiene fotogramas clave, se guarda en el de la línea blanca (o se crea uno ahí)
function ponerVol(m, v) {
  if (!m.kfv?.length) { m.vol = v; return; }
  const r = Math.max(0, Math.min(largoM(m), S.t - m.start));
  const k = kfvCerca(m); if (k) k.v = v; else { m.kfv.push({ t: r, v }); m.kfv.sort((a, b) => a.t - b.t); }
}
function largoM(m) { return Math.max(0.1, ((m.out ?? m.dur ?? 0) - (m.in || 0)) / (m.vel || 1)); }
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
async function agregarMusica(f, nombre = null, msg = 'Música agregada 🎵', extra = {}) {
  if (!f) return;
  const L = lay();
  const m = reg({ id: uid(), fileId: uid(), file: f, nombre: nombre || f.name || 'Música', start: S.t >= L.total - 0.2 ? 0 : S.t, in: 0, dur: 0, vol: 1, fadeIn: false, fadeOut: true, ...extra });
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
$('#cutCapaFoto').addEventListener('change', async (e) => {
  const f = e.target.files[0]; e.target.value = '';
  if (!f) return;
  const k = { tipo: 'imagen', file: f, fileId: uid(), nombre: f.name.replace(/\.[^.]+$/, ''), tam: 0.4, y: 0.25 };
  try { await cargarCapaImagen(k); } catch (err) { return aviso('No se pudo abrir la imagen', 'mal'); }
  DB.put('archivos', k.fileId, f);
  if (nuevaCapa(k)) aviso('Foto agregada encima del video 🖼️');
});
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

/* ---------- quitar pausas (silencios) de un clip donde alguien habla, como CapCut ----------
   Se escucha el audio del clip, se buscan los tramos en silencio de más de 0,6 s y el clip se parte en los
   pedazos con voz (dejando un respiro de 0,12 s a cada lado). Se puede deshacer con ↩. */
async function quitarPausas(c) {
  if (!c || c.tipo !== 'video') return;
  if ((c.vel || 1) !== 1 || c.curva) return aviso('Quite primero el cambio de velocidad de este clip para quitarle las pausas', 'mal');
  pausar();
  cargandoMsg('Escuchando el audio para encontrar las pausas…');
  let buf = null;
  try { buf = await decodificar(c, new OfflineAudioContext(1, 16000, 16000)); } catch (e) { buf = null; }
  if (!buf) { cargandoMsg(null); return aviso('Este clip no tiene sonido', 'mal'); }
  const d = buf.getChannelData(0), sr = buf.sampleRate, ven = Math.round(sr * 0.03);
  const ini = Math.floor(c.in * sr), fin = Math.min(d.length, Math.floor(c.out * sr));
  const rms = [];
  for (let i = ini; i < fin; i += ven) {
    let s2 = 0; const f = Math.min(fin, i + ven);
    for (let j = i; j < f; j++) s2 += d[j] * d[j];
    rms.push(Math.sqrt(s2 / Math.max(1, f - i)));
  }
  const orden = [...rms].sort((a, b) => a - b);
  const piso = orden[Math.floor(orden.length * 0.1)] || 0, voz = orden[Math.floor(orden.length * 0.9)] || 0;
  // Umbral entre el ruido de fondo y la voz (nunca más bajo que unos -50 dB)
  const umbral = Math.max(0.0032, piso + (voz - piso) * 0.12);
  const tv = ven / sr, minPausa = 0.6, margen = 0.12;
  const silencios = []; let desde = -1;
  rms.forEach((v, k) => {
    if (v < umbral) { if (desde < 0) desde = k; } else if (desde >= 0) { if ((k - desde) * tv >= minPausa) silencios.push([desde * tv, k * tv]); desde = -1; }
  });
  if (desde >= 0 && (rms.length - desde) * tv >= minPausa) silencios.push([desde * tv, rms.length * tv]);
  const largoC = c.out - c.in;
  const pedazos = []; let cursor = 0;
  for (const [a, b] of silencios) {
    const fA = Math.min(largoC, a + margen), iB = Math.max(0, b - margen);
    if (fA - cursor > 0.15) pedazos.push([c.in + cursor, c.in + fA]);
    cursor = Math.max(cursor, iB);
  }
  if (largoC - cursor > 0.15) pedazos.push([c.in + cursor, c.out]);
  cargandoMsg(null);
  const quitado = largoC - pedazos.reduce((a, [x, y]) => a + (y - x), 0);
  if (!pedazos.length || quitado < 0.3) return aviso('No encontré pausas largas en este clip 👌');
  const i = S.clips.indexOf(c), transFinal = c.trans;
  const nuevos = pedazos.map(([a, b], k) => k === 0
    ? Object.assign(c, { in: a, out: b, kf: null, _tk: null, trans: { tipo: 'ninguna', dur: 0.5 } })
    : reg({ ...c, id: uid(), in: a, out: b, el: null, mascara: null, kf: null, _tk: null, trans: { tipo: 'ninguna', dur: 0.5 } }));
  nuevos[nuevos.length - 1].trans = transFinal;
  S.clips.splice(i, 1, ...nuevos);
  cargandoMsg('Armando los cortes…');
  for (const n of nuevos.slice(1)) { try { await cargarVideo(n); } catch (e) { /* se reintenta al reproducir */ } }
  cargandoMsg(null);
  S.sel = c.id; irA(lay().start[i]); pintarLinea(); pintarPanel(); guardar();
  aviso(`Listo ✂️ Quité ${silencios.length} ${silencios.length === 1 ? 'pausa' : 'pausas'} (${quitado.toFixed(1)} s). Si no le gusta, toque ↩.`);
}

/* ---------- reducir ruido de la voz ----------
   Vista previa: filtro de voz (corta zumbidos graves y siseo agudo). Al exportar, además, baja el ruido de fondo
   en las pausas (puerta de ruido suave según el nivel del propio audio) y empareja el volumen de la voz. */
// Efectos de audio (como CapCut). La misma cadena sirve para la vista previa (AudioContext) y para exportar (OfflineAudioContext)
const EFECTOS_AUDIO = [['', 'Ninguno', '⦸'], ['telefono', 'Teléfono', '📞'], ['radio', 'Radio vieja', '📻'], ['megafono', 'Megáfono', '📢'],
  ['eco', 'Eco', '🏔️'], ['estadio', 'Estadio', '🏟️'], ['robot', 'Robot', '🤖'], ['cueva', 'Cueva', '🕳️']];
const claveCad = (o) => [!!o.limpio, !!o.mejor, !!o.aislar, o.efa || ''].join('|');
const filtro = (ctx, tipo, f, q, g) => { const b = ctx.createBiquadFilter(); b.type = tipo; b.frequency.value = f; if (q != null) b.Q.value = q; if (g != null) b.gain.value = g; return b; };
function distorsion(ctx, k) {
  const ws = ctx.createWaveShaper(), n = 1024, cur = new Float32Array(n);
  for (let i = 0; i < n; i++) { const x = i * 2 / n - 1; cur[i] = (1 + k) * x / (1 + k * Math.abs(x)); }
  ws.curve = cur; return ws;
}
function reverb(ctx, seg, decae) {
  const sr = ctx.sampleRate, n = Math.round(sr * seg), b = ctx.createBuffer(2, n, sr);
  for (let ch = 0; ch < 2; ch++) { const d = b.getChannelData(ch); for (let i = 0; i < n; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / n, decae); }
  const c = ctx.createConvolver(); c.buffer = b; return c;
}
// Devuelve el nodo de salida y lo que hay que apagar después (osciladores)
function cadenaEfectos(ctx, entrada, o) {
  let n = entrada; const parar = [];
  const sig = (x) => { n.connect(x); n = x; return x; };
  if (o.aislar) { sig(filtro(ctx, 'highpass', 160, 0.8)); sig(filtro(ctx, 'lowpass', 4800, 0.8)); sig(filtro(ctx, 'peaking', 2500, 1, 3)); }
  else if (o.limpio || o.mejor) { sig(filtro(ctx, 'highpass', 85)); sig(filtro(ctx, 'lowpass', 9000)); }
  if (o.mejor) {
    sig(filtro(ctx, 'peaking', 250, 1, -2)); sig(filtro(ctx, 'peaking', 3200, 0.9, 4)); sig(filtro(ctx, 'highshelf', 8000, null, 2));
    const comp = ctx.createDynamicsCompressor(); comp.threshold.value = -26; comp.ratio.value = 4; comp.attack.value = 0.004; comp.release.value = 0.18; sig(comp);
  } else if (o.limpio) {
    const comp = ctx.createDynamicsCompressor(); comp.threshold.value = -24; comp.ratio.value = 3; comp.attack.value = 0.005; comp.release.value = 0.2; sig(comp);
  }
  const e = o.efa || '';
  if (e === 'telefono') { sig(filtro(ctx, 'highpass', 400, 0.9)); sig(filtro(ctx, 'lowpass', 3200, 0.9)); sig(distorsion(ctx, 3)); }
  else if (e === 'radio') { sig(filtro(ctx, 'bandpass', 1500, 0.9)); sig(distorsion(ctx, 12)); sig(filtro(ctx, 'lowpass', 4000)); }
  else if (e === 'megafono') { sig(filtro(ctx, 'highpass', 700, 1)); sig(filtro(ctx, 'peaking', 2000, 1, 8)); sig(distorsion(ctx, 25)); sig(filtro(ctx, 'lowpass', 5000)); }
  else if (e === 'eco' || e === 'cueva') {
    const seco = ctx.createGain(), mezcla = ctx.createGain(), del = ctx.createDelay(2), fb = ctx.createGain(), lp = filtro(ctx, 'lowpass', e === 'cueva' ? 2500 : 5000);
    del.delayTime.value = e === 'cueva' ? 0.12 : 0.32; fb.gain.value = e === 'cueva' ? 0.55 : 0.42;
    n.connect(seco); n.connect(del); del.connect(lp).connect(fb).connect(del); lp.connect(mezcla); seco.connect(mezcla);
    if (e === 'cueva') { const rv = reverb(ctx, 2.2, 2), g = ctx.createGain(); g.gain.value = 0.5; n.connect(rv).connect(g).connect(mezcla); }
    n = mezcla;
  } else if (e === 'estadio') {
    const seco = ctx.createGain(), mezcla = ctx.createGain(), rv = reverb(ctx, 3, 3), g = ctx.createGain(); g.gain.value = 0.7;
    n.connect(seco).connect(mezcla); n.connect(rv).connect(g).connect(mezcla); n = mezcla;
  } else if (e === 'robot') {
    const mod = ctx.createGain(); mod.gain.value = 0; const osc = ctx.createOscillator(); osc.frequency.value = 55; osc.type = 'sine';
    osc.connect(mod.gain); osc.start(); parar.push(osc); n.connect(mod); n = mod;
    const g = ctx.createGain(); g.gain.value = 1.8; sig(g);
  }
  return { salida: n, parar };
}
function aplicarLimpieza(o) {
  const el = o?.el; if (!el) return;
  const a = audioCtx(); if (!a) return;
  try {
    if (!el._src && !el._gFallo) { const src = a.createMediaElementSource(el); el._src = src; el._g = a.createGain(); el._g.gain.value = o.vol ?? 1; src.connect(el._g).connect(a.destination); }
    if (!el._src) return;
    el._cadKey = claveCad(o);
    el._src.disconnect(); (el._parar || []).forEach(x => { try { x.stop(); } catch (e) { /* nada */ } });
    const { salida, parar } = cadenaEfectos(a, el._src, o);
    el._parar = parar; salida.connect(el._g);
  } catch (e) { el._gFallo = true; /* el navegador no deja: se aplica solo al exportar */ }
}
const limpios = new Map();
function limpiarBuffer(buf, clave) {
  if (limpios.has(clave)) return limpios.get(clave);
  const sr = buf.sampleRate, ven = Math.round(sr * 0.02), n = buf.length;
  const ch0 = buf.getChannelData(0), nv = Math.ceil(n / ven), niv = new Float32Array(nv);
  for (let k = 0; k < nv; k++) {
    let s2 = 0; const f = Math.min(n, (k + 1) * ven);
    for (let j = k * ven; j < f; j++) s2 += ch0[j] * ch0[j];
    niv[k] = Math.sqrt(s2 / Math.max(1, f - k * ven));
  }
  const ord = Float32Array.from(niv).sort();
  const piso = ord[Math.floor(nv * 0.15)] || 0, voz = ord[Math.floor(nv * 0.9)] || 0;
  const umbral = piso + (voz - piso) * 0.15;
  // Ganancia por ventana: 1 con voz y 0,15 (-16 dB) en el ruido, suavizada para que no se note el corte
  const gan = new Float32Array(nv); let g = 1;
  for (let k = 0; k < nv; k++) { const meta = niv[k] > umbral ? 1 : 0.15; g += (meta - g) * (meta > g ? 0.6 : 0.12); gan[k] = g; }
  const out = new AudioBuffer({ length: n, numberOfChannels: buf.numberOfChannels, sampleRate: sr });
  for (let ch = 0; ch < buf.numberOfChannels; ch++) {
    const src = buf.getChannelData(ch), dst = new Float32Array(n);
    for (let j = 0; j < n; j++) dst[j] = src[j] * gan[Math.min(nv - 1, (j / ven) | 0)];
    out.copyToChannel(dst, ch);
  }
  limpios.set(clave, out);
  return out;
}

/* ---------- texto a voz: Piper corriendo en el celular (vendor/piper), sin servidor ni costo ---------- */
const VOCES = [
  { id: 'es_MX-claude-high', n: 'México 1', ruta: 'es/es_MX/claude/high/es_MX-claude-high.onnx', mb: 63 },
  { id: 'es_MX-ald-medium', n: 'México 2', ruta: 'es/es_MX/ald/medium/es_MX-ald-medium.onnx', mb: 63 },
  { id: 'es_ES-mls_10246-low', n: 'España', ruta: 'es/es_ES/mls_10246/low/es_ES-mls_10246-low.onnx', mb: 63 },
];
let trabVoz = null, ttsOcupado = false, ttsAudio = null;
function wavDe(pcm, sr) {
  const b = new ArrayBuffer(44 + pcm.length * 2), d = new DataView(b);
  const w = (o, t) => { for (let i = 0; i < t.length; i++) d.setUint8(o + i, t.charCodeAt(i)); };
  w(0, 'RIFF'); d.setUint32(4, 36 + pcm.length * 2, true); w(8, 'WAVE'); w(12, 'fmt '); d.setUint32(16, 16, true); d.setUint16(20, 1, true); d.setUint16(22, 1, true);
  d.setUint32(24, sr, true); d.setUint32(28, sr * 2, true); d.setUint16(32, 2, true); d.setUint16(34, 16, true); w(36, 'data'); d.setUint32(40, pcm.length * 2, true);
  for (let i = 0; i < pcm.length; i++) d.setInt16(44 + i * 2, Math.max(-1, Math.min(1, pcm[i])) * 32767, true);
  return new Blob([b], { type: 'audio/wav' });
}
async function textoAVoz(poner) {
  const v = S.tts, texto = (v?.texto || '').trim();
  if (!texto) return aviso('Escriba primero el texto que quiere convertir en voz', 'mal');
  if (ttsOcupado) return;
  if (poner && !S.clips.length) return aviso('Primero agregue un video para ponerle la voz', 'mal');
  const voz = VOCES.find(x => x.id === v.voz) || VOCES[0];
  const prog = $('[data-tprog]'), pinta = (pct, txt) => { if (!prog) return; prog.hidden = false; $('i', prog).style.width = pct + '%'; $('span', prog).textContent = txt; };
  ttsOcupado = true; pinta(3, 'Preparando la voz…');
  contar('texto_a_voz', { voz: voz.id, letras: texto.length });
  try {
    trabVoz ||= new Worker(new URL('./voz.js', import.meta.url), { type: 'module' });
    const r = await new Promise((ok, mal) => {
      trabVoz.onmessage = (e) => {
        const d = e.data;
        if (d.tipo === 'descarga') pinta(d.pct * 0.8, `Bajando la voz (solo la primera vez) ${d.pct}%`);
        else if (d.tipo === 'cargando') pinta(82, 'Cargando la voz…');
        else if (d.tipo === 'hablando') pinta(85 + 14 * d.i / d.n, d.n > 1 ? `Creando el audio ${d.i} de ${d.n}…` : 'Creando el audio…');
        else if (d.tipo === 'listo') ok(d);
        else if (d.tipo === 'error') mal(new Error(d.mensaje));
      };
      trabVoz.onerror = (e) => mal(new Error(e.message || 'La voz no cargó'));
      trabVoz.postMessage({ tipo: 'hablar', texto, voz, lento: v.lento || 1 });
    });
    const wav = wavDe(r.pcm, r.sr);
    if (prog) prog.hidden = true;
    if (!poner) {
      if (ttsAudio) { ttsAudio.pause(); URL.revokeObjectURL(ttsAudio.src); }
      ttsAudio = new Audio(URL.createObjectURL(wav)); ttsAudio.play().catch(() => {});
    } else {
      const n = S.musica.filter(m => m.voz).length + 1;
      const f = new File([wav], `voz-texto-${n}.wav`, { type: 'audio/wav' });
      await agregarMusica(f, '🗣️ ' + texto.slice(0, 24) + (texto.length > 24 ? '…' : ''), 'Voz agregada 🗣️ Puede moverla en la fila de audio', { voz: true, fadeOut: false });
    }
  } catch (e) {
    if (prog) prog.hidden = true;
    aviso('No se pudo crear la voz: ' + e.message, 'mal');
    window.LPOpinion?.error(new Error('Texto a voz: ' + e.message), { fase: 'texto_a_voz', voz: voz.id, letras: texto.length, ios: IOS });
    try { trabVoz?.terminate(); } catch (e2) { /* nada */ } trabVoz = null;
  } finally { ttsOcupado = false; }
}

/* ---------- voz en off: graba el micrófono mientras se reproduce el video, como CapCut ----------
   El navegador ya quita ruido y eco (noiseSuppression / echoCancellation); además queda marcada "reducir ruido". */
const VOZ = { rec: null, stream: null, t0: 0, ini: 0, timer: 0, partes: [] };
async function vozEnOff() {
  if (VOZ.rec) return pararVoz();
  if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) return aviso('Este navegador no deja grabar la voz. Pruebe en Chrome o Safari actualizado.', 'mal');
  if (!S.clips.length) return aviso('Primero agregue un video para grabarle la voz encima', 'mal');
  try {
    VOZ.stream = await navigator.mediaDevices.getUserMedia({ audio: { noiseSuppression: true, echoCancellation: true, autoGainControl: true, channelCount: 1 } });
  } catch (e) {
    return aviso(e.name === 'NotAllowedError' ? 'Dele permiso al micrófono para grabar su voz (en el candado de la barra de direcciones)' : 'No se encontró el micrófono', 'mal');
  }
  const tipo = ['audio/mp4', 'audio/webm;codecs=opus', 'audio/webm', 'audio/ogg'].find(t => MediaRecorder.isTypeSupported?.(t)) || '';
  VOZ.partes = [];
  VOZ.rec = new MediaRecorder(VOZ.stream, tipo ? { mimeType: tipo } : {});
  VOZ.rec.ondataavailable = (e) => { if (e.data?.size) VOZ.partes.push(e.data); };
  VOZ.rec.onstop = terminarVoz;
  const L = lay();
  VOZ.ini = S.t >= L.total - 0.3 ? 0 : S.t;
  irA(VOZ.ini);
  // Mientras graba, la música no suena (para no grabarla de nuevo por el parlante)
  VOZ.musica = S.musica.map(m => [m, m.vol]); S.musica.forEach(m => { m.vol = 0; });
  let ind = $('#cutVozRec');
  if (!ind) {
    ind = document.createElement('button'); ind.type = 'button'; ind.id = 'cutVozRec'; ind.className = 'cut-voz-rec';
    ind.onclick = pararVoz;
    document.body.appendChild(ind);
  }
  ind.hidden = false;
  VOZ.rec.start(250);
  VOZ.t0 = performance.now();
  const pinta = () => { const s2 = (performance.now() - VOZ.t0) / 1000; ind.innerHTML = `<i></i> Grabando ${Math.floor(s2 / 60)}:${String(Math.floor(s2 % 60)).padStart(2, '0')} · <b>Parar</b>`; };
  pinta(); VOZ.timer = setInterval(() => { pinta(); if (!S.playing && performance.now() - VOZ.t0 > 800) pararVoz(); }, 250);
  if (!S.playing) reproducir();
}
function pararVoz() {
  if (!VOZ.rec) return;
  clearInterval(VOZ.timer);
  try { VOZ.rec.stop(); } catch (e) { terminarVoz(); }
  if (S.playing) pausar();
}
async function terminarVoz() {
  const rec = VOZ.rec; if (!rec) return;
  VOZ.rec = null;
  VOZ.stream?.getTracks().forEach(t => t.stop());
  (VOZ.musica || []).forEach(([m, v]) => { m.vol = v; }); VOZ.musica = null;
  const ind = $('#cutVozRec'); if (ind) ind.hidden = true;
  const seg = (performance.now() - VOZ.t0) / 1000;
  if (!VOZ.partes.length || seg < 0.5) return aviso('La grabación quedó muy corta', 'mal');
  const tipo = rec.mimeType || VOZ.partes[0].type || 'audio/webm';
  const ext = tipo.includes('mp4') ? 'm4a' : tipo.includes('ogg') ? 'ogg' : 'webm';
  const n = S.musica.filter(m => m.voz).length + 1;
  const f = new File(VOZ.partes, `voz-${n}.${ext}`, { type: tipo.split(';')[0] });
  const t = S.t; S.t = VOZ.ini;
  await agregarMusica(f, 'Voz ' + n, 'Voz grabada 🎙️ Puede moverla o bajarle el volumen a la música', { voz: true, limpio: true, fadeOut: false });
  // La música baja un poco para que se entienda la voz
  S.musica.forEach(m => { if (!m.voz && (m.vol ?? 1) > 0.5) m.vol = 0.5; });
  S.t = t; guardar();
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
    v.onerror = () => { const er = v.error; v.removeAttribute('src'); v.load(); v.remove(); mal(new Error('video ' + (er ? er.code + ' ' + (er.message || '') : 'sin detalle'))); };
    ['seeked', 'loadeddata', 'playing'].forEach(ev => v.addEventListener(ev, () => { S.dirty = true; }));
    $('#cutVideos').appendChild(v);
  });
}
async function prepararClip(c) {
  c.url = URL.createObjectURL(c.file);
  if (c.tipo === 'imagen') {
    c.bmp = await abrirImagen(c.file);
    c.w = c.bmp.width; c.h = c.bmp.height;
    const cv = document.createElement('canvas'); cv.height = 72; cv.width = Math.round(72 * c.w / c.h);
    cv.getContext('2d').drawImage(c.bmp, 0, 0, cv.width, cv.height);
    c.tira = cv.toDataURL('image/jpeg', 0.6);
    if (S.belleza.on && BEL.listo()) { c.mascara = document.createElement('canvas'); BEL.mascara(c.bmp, c.mascara); }
  } else {
    try {
      await cargarVideo(c);
    } catch (e1) {
      // En Android el celular a veces se queda sin decodificadores mientras arma las miniaturas de los otros videos:
      // se espera a que termine y se intenta otra vez
      await colaTiras; await new Promise(r => setTimeout(r, 700));
      try { await cargarVideo(c); } catch (e2) {
        // Formato que el reproductor del navegador no abre (ej. HEVC/H.265, 10 bits): se convierte aquí mismo a MP4 H.264, como CapCut
        await convertirCompatible(c, e2);
        await cargarVideo(c);
      }
    }
    colaTiras = colaTiras.then(() => tiraVideo(c)).catch(() => {}); // miniaturas de a una, no todas a la vez
  }
}
let colaTiras = Promise.resolve();
// Mira los primeros bytes para saber qué es de verdad el archivo (WhatsApp y algunos celulares mandan
// fotos sin tipo o con la extensión cambiada, y videos que dicen ser .jpg)
async function queEs(f) {
  try {
    const b = new Uint8Array(await f.slice(0, 16).arrayBuffer());
    const txt = String.fromCharCode(...b);
    if (b[0] === 0xFF && b[1] === 0xD8) return 'imagen'; // JPG
    if (b[0] === 0x89 && txt.slice(1, 4) === 'PNG') return 'imagen';
    if (txt.startsWith('GIF8') || txt.startsWith('BM')) return 'imagen';
    if (txt.startsWith('RIFF') && txt.slice(8, 12) === 'WEBP') return 'imagen';
    if (txt.slice(4, 8) === 'ftyp') return /^(hei[cfmsx]|mif1|msf1|avif|avis)/.test(txt.slice(8, 12)) ? 'imagen' : 'video';
    if (b[0] === 0x1A && b[1] === 0x45 && b[2] === 0xDF && b[3] === 0xA3) return 'video'; // webm / mkv
    if (txt.startsWith('RIFF') && txt.slice(8, 11) === 'AVI') return 'video';
    if (b[0] === 0x47 || (b[0] === 0 && b[1] === 0 && b[2] === 1 && b[3] === 0xBA)) return 'video'; // .ts / mpeg
  } catch (e) { /* no se pudo leer: se decide por el nombre */ }
  return null;
}
// Abre una foto por varios caminos (algunos celulares fallan con uno y no con otro) y la achica si es enorme
async function abrirImagen(file) {
  const MAX = 3840;
  const achicar = async (bmp) => {
    const lado = Math.max(bmp.width, bmp.height);
    if (lado <= MAX) return bmp;
    const k = MAX / lado, cw = Math.round(bmp.width * k), ch = Math.round(bmp.height * k);
    const r = await createImageBitmap(bmp, { resizeWidth: cw, resizeHeight: ch, resizeQuality: 'high' }).catch(() => null);
    if (r) { bmp.close?.(); return r; }
    const cv = document.createElement('canvas'); cv.width = cw; cv.height = ch; cv.getContext('2d').drawImage(bmp, 0, 0, cw, ch);
    bmp.close?.(); return createImageBitmap(cv);
  };
  try { return await achicar(await createImageBitmap(file, { imageOrientation: 'from-image' })); } catch (e) { /* otro camino */ }
  try { return await achicar(await createImageBitmap(file)); } catch (e) { /* otro camino */ }
  // Con <img> el navegador abre más formatos (en iPhone, HEIC)
  const url = URL.createObjectURL(file);
  try {
    const img = new Image(); img.decoding = 'async'; img.src = url;
    await img.decode();
    const lado = Math.max(img.naturalWidth, img.naturalHeight), k = Math.min(1, MAX / lado);
    const cv = document.createElement('canvas'); cv.width = Math.round(img.naturalWidth * k); cv.height = Math.round(img.naturalHeight * k);
    cv.getContext('2d').drawImage(img, 0, 0, cv.width, cv.height);
    return await createImageBitmap(cv);
  } finally { URL.revokeObjectURL(url); }
}
async function convertirCompatible(c, errOriginal) {
  const input = new MB.Input({ source: new MB.BlobSource(c.file), formats: MB.ALL_FORMATS });
  const tr = await input.getPrimaryVideoTrack().catch(() => null);
  if (!tr || !(await tr.canDecode().catch(() => false))) throw errOriginal || new Error('formato no compatible');
  const largo = Math.max(tr.displayWidth, tr.displayHeight), k = Math.min(1, 1920 / largo);
  const output = new MB.Output({ format: new MB.Mp4OutputFormat(), target: new MB.BufferTarget() });
  const conv = await MB.Conversion.init({
    input, output,
    video: { codec: 'avc', forceTranscode: true, bitrate: MB.QUALITY_HIGH, ...(k < 1 ? { width: Math.round(tr.displayWidth * k / 2) * 2, height: Math.round(tr.displayHeight * k / 2) * 2, fit: 'contain' } : {}) },
  });
  if (!conv.isValid) throw errOriginal || new Error('no se puede convertir');
  const nombre = c.nombre || 'video';
  conv.onProgress = (p) => cargandoMsg(`Convirtiendo "${nombre}" a un formato compatible… ${Math.round(p * 100)}%`);
  cargandoMsg(`Convirtiendo "${nombre}" a un formato compatible…`);
  await conv.execute();
  const f = new File([output.target.buffer], nombre.replace(/\.[^.]+$/, '') + '.mp4', { type: 'video/mp4' });
  URL.revokeObjectURL(c.url);
  c.file = f; c.url = URL.createObjectURL(f); c.convertido = true;
  aviso(`"${nombre}" se convirtió a un formato compatible ✅`);
}
async function agregarArchivos(lista) {
  const files = [...lista].filter(f => /^(video|image)\//.test(f.type) || /\.(mp4|mov|webm|m4v|3gp|mkv|avi|jpe?g|png|webp|gif|bmp|avif|hei[cf])$/i.test(f.name) || !f.type);
  if (!files.length) return;
  $('#cutVacio').hidden = true;
  cargandoMsg(`Cargando ${files.length} ${files.length === 1 ? 'archivo' : 'archivos'}…`);
  for (const f of files) {
    const real = await queEs(f);
    const c = { id: uid(), fileId: uid(), file: f, nombre: f.name, tipo: real || (f.type.startsWith('image/') || /\.(jpe?g|png|webp|gif|bmp|avif|hei[cf])$/i.test(f.name) ? 'imagen' : 'video'), in: 0, out: null, dur: 0, mute: false, trans: { tipo: 'ninguna', dur: 0.5 } };
    if (c.tipo === 'imagen') { c.dur = 3; c.out = 3; }
    try {
      try { await prepararClip(c); } catch (e1) {
        // Si no abrió como lo que parecía, se prueba como lo otro (foto ↔ video)
        if (real) throw e1;
        c.tipo = c.tipo === 'imagen' ? 'video' : 'imagen';
        if (c.tipo === 'imagen') { c.dur = 3; c.out = 3; } else { c.dur = 0; c.out = null; }
        try { await prepararClip(c); } catch (e2) { throw e1; }
      }
      S.clips.push(reg(c));
      await DB.put('archivos', c.fileId, c.file);
    } catch (e) {
      const heic = /\.hei[cf]$/i.test(f.name) || /hei[cf]/i.test(f.type);
      aviso(heic ? `"${f.name}" es una foto HEIC del iPhone y este navegador no la abre. Compártala como JPG o en el iPhone ponga Ajustes → Cámara → Formatos → Más compatible.`
        : c.tipo === 'imagen' ? `No pude abrir la foto "${f.name}" (puede estar dañada o incompleta). Ábrala en la galería y compártala de nuevo, o tómele captura de pantalla.`
        : `"${f.name}" usa un formato que este celular no puede leer en el navegador. Pruebe abrirlo en Chrome actualizado o grábelo en "Más compatible" (H.264).`, 'mal');
      window.LPOpinion?.error(new Error('No abrió un archivo: ' + (e?.message || e)), { fase: 'agregar', tipo: f.type || '?', ext: (f.name.match(/\.\w+$/) || [''])[0].toLowerCase(), mb: Math.round(f.size / 1e5) / 10, real: real || '?', como: c.tipo });
    }
  }
  cargandoMsg(null);
  contar('agrego', { n: files.length, fotos: files.filter(f => /^image\//.test(f.type)).length });
  if (S.clips.length && !S.sel) S.sel = S.clips[0].id;
  pintarLinea(); pintarPanel(); guardar();
}
$('#cutArchivo').addEventListener('change', (e) => {
  const fs = [...e.target.files]; e.target.value = '';
  const ed = fs.find(f => /\.lpcut$/i.test(f.name));
  if (ed) return importarEditable(ed); // escogió un editable en vez de un video: se importa
  agregarArchivos(fs);
});
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
  // Zoom animado: cada parte se queda con sus puntos y arranca/termina con el encuadre que había en el corte
  let kfN = null, fijo = null;
  if (c.kf?.length) {
    const e0 = { ...encuadreEn(c, corte) };
    kfN = [{ t: 0, z: e0.z, x: e0.x, y: e0.y }, ...c.kf.filter(k => k.t > corte + 0.01).map(k => ({ ...k, t: k.t - corte }))];
    c.kf = [...c.kf.filter(k => k.t < corte - 0.01), { t: corte, z: e0.z, x: e0.x, y: e0.y }];
    if (kfN.length === 1) { fijo = e0; kfN = null; } // después del corte ya no cambia: zoom fijo
  }
  const n = reg({ ...c, _tk: null, id: uid(), in: loc, el: null, mascara: null, trans: { ...c.trans }, kf: kfN, ...(fijo ? { z: fijo.z, x: fijo.x, y: fijo.y } : {}) });
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
const TITULOS = { 'm-vol': 'Volumen', 'm-fade': 'Desvanecer', 'm-efx': 'Efectos de audio', 'm-vel': 'Velocidad', voz: 'Texto a voz', volumen: 'Volumen', animaciones: 'Animaciones', recortar: 'Recortar', editar: 'Editar', audio: 'Audio', texto: 'Texto', efectos: 'Efectos', superposicion: 'Superposición', subtitulos: 'Subtítulos', filtros: 'Filtros', ajustar: 'Ajustar', stickers: 'Stickers', formato: 'Relación de aspecto', fondo: 'Fondo', velocidad: 'Velocidad', transicion: 'Transición', belleza: 'Belleza del rostro', capa: 'Capa', portada: 'Portada' };
function abrirTab(t) {
  S.tab = t;
  pintarBarra();
  $('#cutPanel').hidden = !t;
  document.body.classList.toggle('con-panel', !!t);
  document.body.classList.toggle('hoja-vol', t === 'm-vol');
  $('#cutPanelTit').textContent = TITULOS[t] || '';
  pintarPanel();
  requestAnimationFrame(() => pintarLinea());
}
// Barra de abajo: la general, o la del clip cuando se toca un video (como CapCut)
const TABS_CLIP = ['editar', 'volumen', 'animaciones', 'efectos', 'velocidad', 'recortar', 'ajustar', 'filtros', 'belleza', 'transicion'];
const barra = $('.cut-tabs');
const BARRA_GENERAL = barra.innerHTML;
const ICS = {
  volver: '<path d="m15 18-6-6 6-6"/>',
  dividir: '<path d="M12 3v18"/><path d="M5 8 2 12l3 4M19 8l3 4-3 4"/>',
  volumen: '<path d="M4 9v6h4l5 4V5L8 9H4z"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a9 9 0 0 1 0 12"/>',
  animaciones: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m10 9 5 3-5 3z"/>',
  efectos: '<path d="m12 3 2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6L3.4 9.3l6-.7z"/>',
  eliminar: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
  velocidad: '<path d="M20 12a8 8 0 1 1-3-6.2"/><path d="m12 12 5-5"/>',
  recortar: '<path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M2 6h14a2 2 0 0 1 2 2v14"/>',
  duplicar: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M4 16V5a1 1 0 0 1 1-1h11"/>',
  sustituir: '<path d="M4 9a7 7 0 0 1 12-3l3 3M20 15a7 7 0 0 1-12 3l-3-3"/><path d="M19 4v5h-5M5 20v-5h5"/>',
  extraer: '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
  ajustar: '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
  filtros: '<circle cx="12" cy="8" r="4.5"/><circle cx="8" cy="15" r="4.5"/><circle cx="16" cy="15" r="4.5"/>',
  belleza: '<circle cx="12" cy="9" r="5"/><path d="M5 21a7 7 0 0 1 14 0"/><path d="m18 3 .8 1.7L20.5 5l-1.7.8L18 7.5l-.8-1.7L15.5 5l1.7-.3z"/>',
  transicion: '<path d="M4 5l8 7-8 7zM20 5l-8 7 8 7z"/>',
  pausas: '<path d="M3 12h3l2-5 3 10 2-5h2"/><path d="M17 8v8M21 8v8"/>',
  ruido: '<path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3z"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/><path d="m4 4 16 16"/>',
  mover: '<path d="M5 12h14M15 8l4 4-4 4M9 8l-4 4 4 4"/>',
  'a-extraer': '<path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M14 11v5M14 11l3-1"/><circle cx="12.5" cy="16" r="1.5"/>',
  'a-musica': '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2.5"/><path d="M15 3.5v6l3-1"/>',
  'a-grabar': '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6"/>',
  'a-silenciar': '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="m16 9 5 6M21 9l-5 6"/>',
  'a-sonido': '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>',
  'a-tts': '<path d="M4 6h10M9 6v12"/><path d="M15 10.5a3 3 0 0 1 0 5M18 8a6.5 6.5 0 0 1 0 10"/>',
  'm-dividir': '<path d="M12 3v18"/><path d="M5 8 2 12l3 4M19 8l3 4-3 4"/>',
  'm-fade': '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M8 5v14M16 5v14"/>',
  'm-vol': '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/>',
  'm-efx': '<circle cx="12" cy="12" r="8.5"/><path d="M8 10v4M11 8v8M14 9.5v5M17 11v2"/>',
  'm-eliminar': '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
  'm-mejor': '<path d="m14 4 6 6-9 9H5v-6z"/><path d="M18 2v3M16.5 3.5h3"/>',
  'm-aislar': '<rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 9v4M11 7v8M14 9v4"/><path d="m17 17 3 3"/>',
  'm-ritmos': '<path d="M6 21V4h11l-2 4 2 4H6"/>',
  'm-vel': '<circle cx="12" cy="13" r="8"/><path d="m12 13 4-4"/>',
  'm-ruido': '<path d="M5 9v6M8 7v10M11 10v4"/><path d="M15 12h.01M18 9h.01M18 15h.01M21 12h.01"/>',
  'm-duplicar': '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
  'a-lista': '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
};
const ic = (k) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${ICS[k]}</svg>`;
function pintarBarra() {
  const c = S.barraClip && clipSel();
  const mSel = !c && S.selM && S.musica.find(x => x.id === S.selM);
  if (mSel) {
    const k = 'mus:' + mSel.id + ':' + claveCad(mSel) + ':' + (mSel.ritmos ? 1 : 0);
    if (barra._modo !== k) {
      barra._modo = k;
      // Barra del audio seleccionado, como CapCut
      const on = (b) => b ? ' ✓' : '';
      const it = [['acc', 'm-dividir', 'Dividir'], ['tab', 'm-fade', 'Desvanecer'], ['tab', 'm-vol', 'Volumen'], ['tab', 'm-efx', 'Efectos de audio' + on(mSel.efa)],
        ['acc', 'm-eliminar', 'Eliminar'], 'sep', ['acc', 'm-mejor', 'Mejorar voz' + on(mSel.mejor)], ['acc', 'm-aislar', 'Aislar voz' + on(mSel.aislar)],
        ['acc', 'm-ritmos', 'Ritmos' + on(mSel.ritmos)], ['tab', 'm-vel', 'Velocidad'], ['acc', 'm-ruido', 'Reducir el ruido' + on(mSel.limpio)], ['acc', 'm-duplicar', 'Duplicar']];
      barra.innerHTML = `<button type="button" class="cut-volver" data-volver aria-label="Volver">${ic('volver')}</button>`
        + it.map(x => x === 'sep' ? '<span class="cut-sep"></span>' : `<button type="button" class="cut-tab" ${x[0] === 'tab' ? 'data-tab' : 'data-acc'}="${x[1]}">${ic(x[1])}${x[2]}</button>`).join('');
      barra.scrollLeft = 0;
    }
    $$('.cut-tab', barra).forEach(b => b.classList.toggle('on', !!b.dataset.tab && b.dataset.tab === S.tab));
    return;
  }
  if (!c && S.barraAudio) {
    const modoA = 'audio:' + !!S.silenciar + ':' + S.musica.length;
    if (barra._modo !== modoA) {
      barra._modo = modoA;
      // Barra de audio como CapCut: se toca "Agregar audio" y abajo salen las opciones (sin abrir ventana)
      const it = [['acc', 'a-extraer', 'Extraer'], ['acc', 'a-musica', 'Música'], ['acc', 'a-grabar', 'Grabar'], ['acc', 'a-tts', 'Texto a voz'], 'sep',
        S.silenciar ? ['acc', 'a-sonido', 'Activar sonido original'] : ['acc', 'a-silenciar', 'Silenciar originales'],
        S.musica.length && ['acc', 'a-lista', 'Mis audios']].filter(Boolean);
      barra.innerHTML = `<button type="button" class="cut-volver" data-volver aria-label="Volver">${ic('volver')}</button>`
        + it.map(x => x === 'sep' ? '<span class="cut-sep"></span>' : `<button type="button" class="cut-tab" data-acc="${x[1]}">${ic(x[1])}${x[2]}</button>`).join('');
      barra.scrollLeft = 0;
    }
    return;
  }
  const modo = c ? 'clip:' + c.id + ':' + c.tipo + ':' + (S.clips.indexOf(c) < S.clips.length - 1) + ':' + !!c.mute + ':' + !!c.limpio : 'general';
  if (barra._modo !== modo) {
    barra._modo = modo;
    if (!c) barra.innerHTML = BARRA_GENERAL;
    else {
      const v = c.tipo === 'video', hay = S.clips.indexOf(c) < S.clips.length - 1;
      const it = [
        ['acc', 'dividir', 'Dividir'], v && ['tab', 'volumen', 'Volumen'], ['tab', 'animaciones', 'Animaciones'], ['tab', 'efectos', 'Efectos'],
        ['acc', 'eliminar', 'Eliminar'], v && ['tab', 'velocidad', 'Velocidad'], ['tab', 'recortar', 'Recortar'], ['acc', 'duplicar', 'Duplicar'],
        v && ['acc', 'pausas', 'Quitar pausas'], v && ['acc', 'ruido', c.limpio ? 'Ruido reducido ✓' : 'Reducir ruido'],
        ['acc', 'sustituir', 'Sustituir'], v && ['acc', 'extraer', 'Extraer audio'], ['tab', 'ajustar', 'Ajustar'], ['tab', 'filtros', 'Filtros'],
        'sep', ['tab', 'belleza', 'Retoque'], hay && ['tab', 'transicion', 'Transición'], ['tab', 'editar', 'Mover']].filter(Boolean);
      barra.innerHTML = `<button type="button" class="cut-volver" data-volver aria-label="Volver">${ic('volver')}</button>`
        + it.map(x => x === 'sep' ? '<span class="cut-sep"></span>' : `<button type="button" class="cut-tab" ${x[0] === 'tab' ? 'data-tab' : 'data-acc'}="${x[1]}">${ic(x[1] === 'editar' ? 'mover' : x[1])}${x[2]}</button>`).join('');
      barra.scrollLeft = 0;
    }
  }
  $$('.cut-tab', barra).forEach(b => b.classList.toggle('on', !!b.dataset.tab && b.dataset.tab === S.tab));
}
barra.addEventListener('click', (e) => {
  const b = e.target.closest('button'); if (!b) return;
  if (b.dataset.volver != null) { S.barraClip = false; S.barraAudio = false; S.sel = null; S.selM = null; abrirTab(null); pintarLinea(); return; }
  if (b.dataset.tab === 'audio' && innerWidth < 1000) return abrirBarraAudio();
  const acA = { 'a-extraer': () => $('#cutExtraer').click(), 'a-musica': () => $('#cutMusica').click(), 'a-grabar': () => vozEnOff(), 'a-tts': () => { S.selM = null; abrirTab(S.tab === 'voz' ? null : 'voz'); },
    'a-silenciar': () => silenciarOriginales(true), 'a-sonido': () => silenciarOriginales(false), 'a-lista': () => { S.selM = null; abrirTab(S.tab === 'audio' ? null : 'audio'); } }[b.dataset.acc];
  if (acA) return acA();
  const mS = S.musica.find(x => x.id === S.selM);
  if (mS && b.dataset.acc?.startsWith('m-')) return accionMusica(mS, b.dataset.acc.slice(2));
  if (b.dataset.tab) return abrirTab(S.tab === b.dataset.tab && innerWidth < 1000 ? null : b.dataset.tab);
  const c = clipSel(); if (!c) return;
  ({ dividir, eliminar: () => { eliminar(); S.barraClip = false; S.sel = null; abrirTab(null); pintarLinea(); }, duplicar, sustituir: () => $('#cutSustituir').click(), extraer: () => extraerDeClip(c), pausas: () => quitarPausas(c),
    ruido: () => { c.limpio = !c.limpio; aplicarLimpieza(c); pintarBarra(); guardar(); aviso(c.limpio ? 'Ruido reducido en este clip 🎙️ (se nota más en el video exportado)' : 'Sonido original'); } })[b.dataset.acc]?.();
});
/* ---------- acciones del audio seleccionado (barra como CapCut) ---------- */
async function accionMusica(m, a) {
  const cambia = (k, txt) => { m[k] = !m[k]; aplicarLimpieza(m); pintarBarra(); guardar(); aviso(m[k] ? txt : 'Quitado'); };
  if (a === 'dividir') {
    const loc = m.in + (S.t - m.start) * (m.vel || 1), fin = m.out ?? m.dur;
    if (S.t <= m.start + 0.1 || loc >= fin - 0.1) return aviso('Ponga la línea blanca encima del audio donde lo quiere cortar');
    const corte = S.t - m.start, vc = volEn(m, S.t);
    const kA = m.kfv ? [...m.kfv.filter(k => k.t < corte - 0.01), { t: corte, v: vc }] : null;
    const kB = m.kfv ? [{ t: 0, v: vc }, ...m.kfv.filter(k => k.t > corte + 0.01).map(k => ({ t: k.t - corte, v: k.v }))] : null;
    const n = reg({ ...m, id: uid(), el: null, url: null, start: S.t, in: loc, out: m.out ?? null, fadeIn: false, ritmos: m.ritmos ? [...m.ritmos] : null, kfv: kB });
    m.out = loc; m.fadeOut = false; m.kfv = kA;
    try { await prepararMusica(n); } catch (e) { return aviso(e.message, 'mal'); }
    S.musica.splice(S.musica.indexOf(m) + 1, 0, n); S.selM = n.id;
    pintarLinea(); pintarBarra(); guardar(); return aviso('Audio dividido ✂️');
  }
  if (a === 'eliminar') { if (m.el) m.el.pause(); S.musica = S.musica.filter(x => x !== m); S.selM = null; abrirTab(null); pintarLinea(); guardar(); return; }
  if (a === 'duplicar') {
    const n = reg({ ...m, id: uid(), el: null, url: null, start: finM(m), ritmos: m.ritmos ? [...m.ritmos] : null, kfv: m.kfv ? m.kfv.map(k => ({ ...k })) : null });
    try { await prepararMusica(n); } catch (e) { return aviso(e.message, 'mal'); }
    S.musica.splice(S.musica.indexOf(m) + 1, 0, n); S.selM = n.id; pintarLinea(); pintarBarra(); guardar(); return aviso('Audio duplicado');
  }
  if (a === 'mejor') return cambia('mejor', 'Voz mejorada ✨ (más clara y pareja)');
  if (a === 'aislar') return cambia('aislar', 'Voz resaltada: se baja lo que no es voz 🎙️ (se nota más al exportar)');
  if (a === 'ruido') return cambia('limpio', 'Ruido reducido 🎙️ (se nota más al exportar)');
  if (a === 'ritmos') {
    if (m.ritmos) { m.ritmos = null; pintarLinea(); pintarBarra(); guardar(); return aviso('Marcas de ritmo quitadas'); }
    cargandoMsg('Buscando los golpes de la música…');
    try { m.ritmos = await buscarRitmos(m); } catch (e) { m.ritmos = null; }
    cargandoMsg(null);
    if (!m.ritmos?.length) { m.ritmos = null; return aviso('No encontré ritmos marcados en este audio', 'mal'); }
    pintarLinea(); pintarBarra(); guardar();
    return aviso(`Marqué ${m.ritmos.length} golpes 🥁 Úselos para cortar sus clips al ritmo (puntos amarillos en el audio)`);
  }
}
// Detecta los golpes (beats) de la música: energía de los graves y picos que sobresalen del promedio
async function buscarRitmos(m) {
  const sr = 11025;
  const buf = await decodificar(m, new OfflineAudioContext(1, sr, sr));
  if (!buf) return [];
  const d = buf.getChannelData(0), ven = 256, nv = Math.floor(d.length / ven), en = new Float32Array(nv);
  let lp = 0;
  for (let k = 0; k < nv; k++) { let s2 = 0; for (let j = k * ven; j < (k + 1) * ven; j++) { lp += (d[j] - lp) * 0.12; s2 += lp * lp; } en[k] = s2; }
  const tv = ven / sr, vent = Math.round(1 / tv), out = []; let ult = -1;
  let suma = 0; for (let k = 0; k < Math.min(vent, nv); k++) suma += en[k];
  for (let k = 1; k < nv - 1; k++) {
    if (k >= vent) suma += en[k] - en[k - vent];
    const prom = suma / Math.min(vent, k + 1);
    if (en[k] > prom * 1.6 && en[k] >= en[k - 1] && en[k] >= en[k + 1] && (ult < 0 || (k - ult) * tv > 0.28)) { out.push(Math.round(k * tv * 100) / 100); ult = k; }
  }
  return out;
}
function refrescarVol(m, forzar = false) {
  const riel = $('#cutPanelCuerpo [data-riel]'); if (!riel || (!forzar && riel.classList.contains('mueve'))) return;
  const n = Math.round(volEn(m, S.t) * 100), x = riel._aPos(n) * 100;
  if (riel._n === n && !forzar) return; riel._n = n;
  $('.hv-lleno', riel).style.width = x + '%'; $('.hv-bola', riel).style.left = x + '%';
  const num = $('.hv-num', riel); num.style.left = x + '%'; num.textContent = n;
}
function abrirBarraAudio() { S.barraAudio = true; S.barraClip = false; S.sel = null; S.selM = null; S.selC = null; abrirTab(null); pintarLinea(); }
function silenciarOriginales(si) { S.silenciar = si; sincronizar(); pintarLinea(); pintarBarra(); guardar(); aviso(si ? 'Sonido original de los videos apagado 🔇' : 'Sonido original de los videos activado 🔊'); }
// Sustituir: cambia el video o la foto del clip y deja lo demás (velocidad, efectos, transición)
$('#cutSustituir').addEventListener('change', async (e) => {
  const f = e.target.files[0]; e.target.value = '';
  const c = clipSel(); if (!f || !c) return;
  const largoAntes = largo(c);
  const n = { ...c, file: f, fileId: uid(), nombre: f.name, tipo: f.type.startsWith('image/') || /\.(jpe?g|png|webp|heic)$/i.test(f.name) ? 'imagen' : 'video', in: 0, out: null, dur: 0, el: null, bmp: null, tira: null, mascara: null, _tk: null, kf: null, z: 1, x: 0, y: 0 };
  if (n.tipo === 'imagen') { n.dur = largoAntes; n.out = largoAntes; n.vel = 1; n.curva = null; }
  cargandoMsg('Cambiando el clip…');
  try {
    await prepararClip(n);
    if (n.tipo === 'video') n.out = Math.min(n.dur, largoAntes * tablaVel(n).media);
    if (c.el) { c.el.pause(); c.el.remove(); }
    S.clips[S.clips.indexOf(c)] = reg(n);
    DB.put('archivos', n.fileId, f);
    aviso('Clip sustituido 🔁');
  } catch (err) { aviso('No se pudo abrir ese archivo', 'mal'); }
  cargandoMsg(null);
  barra._modo = null; pintarLinea(); pintarPanel(); guardar();
});
// Recortar arrastrando el borde izquierdo o derecho del clip seleccionado
function recortarBorde(ev, c, lado, el) {
  ev.stopPropagation(); ev.preventDefault();
  if (S.playing) pausar();
  const x0 = ev.clientX, in0 = c.in, out0 = c.out, media = tablaVel(c).media;
  const minSrc = 0.2 * media;
  const mov = (e) => {
    const dSrc = (e.clientX - x0) / S.pps * media;
    if (c.tipo === 'imagen') {
      const d = lado === 'der' ? dSrc : -dSrc;
      c.out = Math.max(c.in + 0.3, out0 + d);
    } else if (lado === 'izq') c.in = Math.max(0, Math.min(out0 - minSrc, in0 + dSrc));
    else c.out = Math.min(c.dur, Math.max(in0 + minSrc, out0 + dSrc));
    c._tk = null; el._recorto = true;
    document.body.classList.add('recortando');
    pintarLinea(); S.dirty = true;
  };
  const fin = () => { document.body.classList.remove('recortando'); window.removeEventListener('pointermove', mov); window.removeEventListener('pointerup', fin); window.removeEventListener('pointercancel', fin); if (c.in !== in0 || c.out !== out0) {
    // Al soltar, la línea blanca va al borde recortado: en la vista previa se ve dónde quedó el corte
    const L = lay(), i = S.clips.indexOf(c);
    irA(i < 0 ? S.t : Math.max(0, lado === 'izq' ? L.start[i] + 0.01 : L.start[i] + largo(c) - 0.05));
    moverLinea(); guardar(); aviso('Clip recortado ✂️');
  } };
  window.addEventListener('pointermove', mov);
  window.addEventListener('pointerup', fin);
  window.addEventListener('pointercancel', fin);
}
$('#cutPanelCerrar').addEventListener('click', () => abrirTab(innerWidth >= 1000 ? 'editar' : null));
$('#cutDeshacer').addEventListener('click', deshacer);
// Fotograma clave (como CapCut): marca este momento para que el zoom cambie desde aquí
$('#cutClave').addEventListener('click', () => {
  const mk = S.selM && S.musica.find(x => x.id === S.selM);
  if (mk) {
    pausar();
    if (S.t < mk.start - 0.01 || S.t > finM(mk) + 0.01) return aviso('Ponga la línea blanca encima del audio para marcar el volumen ahí');
    const ya = kfvCerca(mk);
    if (ya) { const v0 = volEn(mk, S.t); mk.kfv = mk.kfv.filter(q => q !== ya); if (!mk.kfv.length) { mk.kfv = null; mk.vol = v0; } aviso('Fotograma clave quitado'); }
    else {
      const v = volEn(mk, S.t);
      mk.kfv ||= [];
      mk.kfv.push({ t: Math.max(0, Math.min(largoM(mk), S.t - mk.start)), v }); mk.kfv.sort((a, b) => a.t - b.t);
      aviso(mk.kfv.length === 1 ? '◆ Volumen marcado aquí. Mueva la línea, ponga otro ◆ y cambie el volumen: el audio sube o baja entre los dos' : '◆ Fotograma clave agregado');
    }
    guardar(); pintarLinea(); if (S.tab === 'm-vol') pintarPanel(); S.dirty = true; return;
  }
  const c = clipBajoLinea(); if (!c) return aviso('Ponga la línea blanca sobre un clip');
  pausar();
  const lt = ltClip(c);
  const ya = c.kf?.find(q => Math.abs(q.t - lt) < 0.12 && q.t > 0.01);
  if (ya) { c.kf = c.kf.filter(q => q !== ya); if (c.kf.length <= 1) { const e2 = c.kf[0] || encuadreEn(c, 0); c.z = e2.z; c.x = e2.x; c.y = e2.y; c.kf = null; } aviso('Fotograma clave quitado'); }
  else { if (!c.kf?.length) c.kf = [{ t: 0, z: c.z || 1, x: c.x || 0, y: c.y || 0 }]; puntoZoom(c); aviso('◆ Fotograma clave: pellizque o mueva el video y cambia desde aquí'); }
  S.sel = c.id; S.dirty = true; guardar(); pintarLinea(); if (S.tab === 'editar') pintarPanel();
});
$('#cutRehacer').addEventListener('click', rehacer);
$('#cutPantalla').addEventListener('click', () => { document.body.classList.toggle('pantalla'); setTimeout(() => { pintarLinea(); S.dirty = true; }, 50); });

/* ---------- rejilla de animaciones estilo CapCut (miniatura con su clip moviéndose + nombre debajo) ---------- */
// Primer cuadro del clip (recortado de la tira de miniaturas) para usarlo de fondo en las vistas previas
function fotoClip(c) {
  if (!c?.tira) return null;
  if (c._foto?.de === c.tira) return c._foto.url;
  if (!c._fotoCargando) {
    c._fotoCargando = true;
    const img = new Image();
    img.onload = () => {
      c._fotoCargando = false;
      const lado = img.height, ancho = c.tipo === 'imagen' ? img.width : Math.min(img.width, Math.round(lado * (c.w || 9) / (c.h || 16)));
      const cv = document.createElement('canvas'); cv.width = ancho; cv.height = lado;
      cv.getContext('2d').drawImage(img, 0, 0, ancho, lado, 0, 0, ancho, lado);
      c._foto = { de: c.tira, url: cv.toDataURL('image/jpeg', 0.7) };
      $$(`.lpa[data-fa="${c.id}"]`).forEach(g => g.style.setProperty('--fa', `url(${c._foto.url})`));
      $$(`.lpa[data-fb="${c.id}"]`).forEach(g => g.style.setProperty('--fb', `url(${c._foto.url})`));
    };
    img.onerror = () => { c._fotoCargando = false; };
    img.src = c.tira;
  }
  return null;
}
function rejillaAnim({ grupo, items, actual, tipo, ca = null, cb = null, salida = false }) {
  const fa = fotoClip(ca), fb = fotoClip(cb);
  const vars = (fa ? `--fa:url(${fa});` : '') + (fb ? `--fb:url(${fb});` : '');
  const capa = (id) => {
    if (tipo === 'tr') return `<i class="a ta-${id}"></i><i class="b tr-${id}"></i><i class="x tz-${id}"></i>`;
    if (tipo === 'tx') return `<i class="a"></i><i class="txt tx-${id} ${salida ? 'sal' : ''}">Abc</i>`;
    return `<i class="b an-${id} ${salida ? 'sal' : ''}"></i>`;
  };
  const nada = (id) => id === '' || id === 'ninguna';
  return `<div class="lpa" data-grupo="${grupo}" ${ca ? `data-fa="${ca.id}"` : ''} ${cb ? `data-fb="${cb.id}"` : ''} style="${vars}">${items.map(([id, n]) => `
    <button type="button" class="lpa-t ${String(actual) === String(id) ? 'on' : ''}" data-v="${id}">
      <span class="lpa-m ${nada(id) ? 'nada' : ''} ${tipo === 'tx' ? 'oscura' : ''}">${nada(id) ? '' : capa(id)}</span><small>${esc(n)}</small></button>`).join('')}</div>`;
}
const pestanasAnim = (lado, extra = '') => `<div class="lpa-cab" data-lado>${[['in', 'Entrada'], ['out', 'Salida']].map(([v, n]) => `<button type="button" data-l="${v}" class="${lado === v ? 'on' : ''}">${n}</button>`).join('')}${extra}</div>`;
const aplicarTodo = (on) => `<label class="lpa-todo"><input type="checkbox" data-atodo ${on ? 'checked' : ''}><i></i>Aplicar a todo</label>`;

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
        <button type="button" data-a="efecto"><i>✨</i>Efectos</button>
        <button type="button" data-a="belleza"><i>💄</i>Belleza</button>
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
      ${c.tipo === 'video' ? `<div class="cut-fila"><small>Volumen</small><input type="range" min="0" max="2" step="0.05" value="${c.vol ?? 1}" data-vol><b class="cut-val" data-vv>${Math.round((c.vol ?? 1) * 100)}%</b></div>` : ''}
      ${c.tipo === 'imagen' ? `<div class="cut-fila"><small>Duración de la foto</small>${chips('durfoto', [[2, '2 s'], [3, '3 s'], [5, '5 s'], [8, '8 s']], largo(c))}</div>` : ''}
      <p class="cut-nota">Para recortar: ponga la línea blanca donde quiere cortar, toque <b>Dividir</b> y elimine el pedazo que sobra.</p>`;
    $$('[data-a]', p).forEach(b => b.onclick = () => ({ dividir, eliminar, duplicar, izq: () => mover(-1), der: () => mover(1), vel: () => abrirTab('velocidad'), efecto: () => abrirTab('efectos'), belleza: () => abrirTab('belleza'), extraer: () => extraerDeClip(c), mute: () => { c.mute = !c.mute; pintarLinea(); pintarPanel(); sincronizar(); guardar(); } })[b.dataset.a]());
    const rz = $('[data-zoom]', p);
    rz.oninput = () => { const k = puntoZoom(c); k.z = Number(rz.value); $('[data-zv]', p).textContent = Math.round(k.z * 100) + '%'; S.dirty = true; };
    rz.onchange = () => { guardar(); pintarPanel(); };
    const re = $('[data-reenc]', p); if (re) re.onclick = () => { c.z = 1; c.x = 0; c.y = 0; c.kf = null; S.dirty = true; guardar(); pintarPanel(); };
    const rt = $('[data-todo]', p); if (rt) rt.onclick = () => { const e2 = encuadreEn(c, ltClip(c)); c.z = e2.z; c.x = e2.x; c.y = e2.y; c.kf = null; S.dirty = true; guardar(); pintarPanel(); };
    const rv = $('[data-vol]', p);
    if (rv) { rv.oninput = () => { c.vol = Number(rv.value); $('[data-vv]', p).textContent = Math.round(c.vol * 100) + '%'; sincronizar(); }; rv.onchange = guardar; }
    const g = $('[data-grupo="durfoto"]', p);
    if (g) g.onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.out = c.in + Number(v); pintarLinea(); pintarPanel(); guardar(); };
    return;
  }
  if (S.tab === 'transicion') {
    const i = S.clips.indexOf(c);
    if (!c || i < 0 || i >= S.clips.length - 1) { p.innerHTML = `<p class="cut-nota">Toque el rombo <b>◇</b> que aparece entre dos clips para ponerle una transición.</p>`; return; }
    const todas = () => { if (S.trTodo) S.clips.slice(0, -1).forEach(x => { x.trans = { ...c.trans }; }); };
    p.innerHTML = `
      ${aplicarTodo(S.trTodo)}
      ${rejillaAnim({ grupo: 'trans', tipo: 'tr', items: TRANSICIONES.map(t => [t.id, t.id === 'ninguna' ? 'Ninguno' : t.n]), actual: c.trans.tipo, ca: c, cb: S.clips[i + 1] })}
      <div class="cut-fila"><small>Duración</small>${chips('durtr', [[0.3, 'Rápida'], [0.5, 'Normal'], [0.8, 'Lenta'], [1.2, 'Muy lenta']], c.trans.dur)}</div>`;
    $('[data-grupo="trans"]', p).onclick = (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      c.trans.tipo = b.dataset.v; todas(); pintarLinea(); pintarPanel(); guardar();
      // Se ve de una vez: se reproduce el corte con la transición
      const L = lay(), fin = L.start[i] + largo(c);
      if (c.trans.tipo !== 'ninguna') { irA(Math.max(0, fin - c.trans.dur - 0.6)); reproducir(Math.min(L.total, fin + 0.5)); } else irA(S.t);
    };
    $('[data-atodo]', p).onchange = (e) => { S.trTodo = e.target.checked; if (S.trTodo) { todas(); pintarLinea(); guardar(); aviso('Transición puesta en todos los cortes ✨'); } };
    $('[data-grupo="durtr"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.trans.dur = Number(v); todas(); pintarLinea(); pintarPanel(); guardar(); irA(S.t); };
    return;
  }
  if (S.tab?.startsWith('m-')) {
    if (!m) { abrirTab(null); return; }
    if (S.tab === 'm-vol') {
      // Hoja de volumen como CapCut: 0 a 1000 (100 = normal), burbuja con el número, reducir ruido y ✓
      p.innerHTML = `
        <div class="hv-fila"><span>0</span><div class="hv-riel" data-riel><i class="hv-lleno"></i><i class="hv-marca"></i><b class="hv-num"></b><i class="hv-bola"></i></div><span>1000</span></div>
        <p class="hv-kf">${m.kfv?.length ? `◆ ${m.kfv.length} fotograma${m.kfv.length > 1 ? 's' : ''} clave: el volumen se guarda en el ◆ de la línea blanca` : 'Toque ◆ (arriba, junto a deshacer) para marcar el volumen en distintos momentos'}</p>
        <label class="hv-sw"><span><b>Reducir el ruido</b><small>Quita el ruido de fondo del audio seleccionado.</small></span><input type="checkbox" data-hvr ${m.limpio ? 'checked' : ''}><i></i></label>
        <div class="hv-pie"><b>Volumen</b><button type="button" data-hvok aria-label="Listo">✓</button></div>`;
      const riel = $('[data-riel]', p);
      // Posición del deslizador ↔ número: la mitad es 100 (volumen normal) y el resto llega hasta 1000
      const aNum = (x) => Math.round(x <= 0.5 ? x * 200 : 100 + (x - 0.5) * 1800);
      const aPos = (n) => n <= 100 ? n / 200 : 0.5 + (n - 100) / 1800;
      const mover = (e) => {
        const r = riel.getBoundingClientRect(), x = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width));
        let n = aNum(x); if (Math.abs(n - 100) < 12) n = 100; // se pega en 100
        ponerVol(m, n / 100); refrescarVol(m, true); sincronizar();
      };
      riel.onpointerdown = (e) => { riel.setPointerCapture(e.pointerId); riel.classList.add('mueve'); mover(e); };
      riel.onpointermove = (e) => { if (riel.classList.contains('mueve')) mover(e); };
      riel.onpointerup = riel.onpointercancel = () => { riel.classList.remove('mueve'); guardar(); pintarLinea(); };
      riel._aPos = aPos;
      refrescarVol(m);
      $('[data-hvr]', p).onchange = (e) => { m.limpio = e.target.checked; aplicarLimpieza(m); pintarBarra(); guardar(); };
      $('[data-hvok]', p).onclick = () => abrirTab(null);
    } else if (S.tab === 'm-fade') {
      p.innerHTML = `<label class="cut-check"><input type="checkbox" data-fi ${m.fadeIn ? 'checked' : ''}> Aparecer suave al inicio</label>
        <label class="cut-check"><input type="checkbox" data-fo ${m.fadeOut ? 'checked' : ''}> Desaparecer suave al final</label>`;
      $('[data-fi]', p).onchange = (e) => { m.fadeIn = e.target.checked; guardar(); };
      $('[data-fo]', p).onchange = (e) => { m.fadeOut = e.target.checked; guardar(); };
    } else if (S.tab === 'm-efx') {
      p.innerHTML = `<div class="cut-efs" data-mefx>${EFECTOS_AUDIO.map(([id, n, e]) => `<button type="button" data-v="${id}" class="${(m.efa || '') === id ? 'on' : ''}"><i>${e}</i><small>${n}</small></button>`).join('')}</div>
        <p class="cut-nota">Toque un efecto y dele ▶ para oírlo.</p>`;
      $('[data-mefx]', p).onclick = (e) => { const b = e.target.closest('[data-v]'); if (!b) return; m.efa = b.dataset.v; aplicarLimpieza(m); pintarPanel(); pintarBarra(); guardar(); if (!S.playing && m.efa) { irA(Math.max(m.start, Math.min(S.t, finM(m) - 3))); reproducir(Math.min(lay().total, S.t + 3)); } };
    } else if (S.tab === 'm-vel') {
      p.innerHTML = `<div class="cut-fila"><small>Velocidad</small>${chips('mvel', [[0.5, '0.5×'], [0.75, '0.75×'], [1, '1×'], [1.25, '1.25×'], [1.5, '1.5×'], [2, '2×']], m.vel || 1)}</div>
        <p class="cut-nota">Más rápido = audio más corto y voz más aguda.</p>`;
      $('[data-grupo="mvel"]', p).onclick = (e) => { if (!e.target.dataset.v) return; m.vel = Number(e.target.dataset.v); sincronizar(); pintarLinea(); pintarPanel(); guardar(); };
    }
    return;
  }
  if (S.tab === 'voz') {
    const v = S.tts ||= { voz: VOCES[0].id, lento: 1, texto: '' };
    p.innerHTML = `
      <textarea class="cut-tts-txt" data-ttxt rows="4" maxlength="3000" placeholder="Escriba lo que quiere que diga la voz…">${esc(v.texto)}</textarea>
      <div class="cut-fila"><small>Voz</small><div class="cut-chips" data-grupo="tvoz">${VOCES.map(x => `<button type="button" class="cut-chip ${v.voz === x.id ? 'on' : ''}" data-v="${x.id}">${x.n}</button>`).join('')}</div></div>
      <div class="cut-fila"><small>Velocidad</small>${chips('tvel', [[1.25, 'Lenta'], [1, 'Normal'], [0.85, 'Rápida']], v.lento)}</div>
      <div class="cut-progreso" data-tprog hidden><i></i><span></span></div>
      <div class="cut-acciones2">
        <button type="button" class="cut-btn-sec" data-toir>▶ Escuchar</button>
        <button type="button" class="cut-btn" data-tponer>＋ Agregar al video</button>
      </div>
      <p class="cut-nota">La voz se crea en su celular, gratis y sin internet después de la primera vez (la primera vez baja la voz: ${VOCES.find(x => x.id === v.voz)?.mb || 60} MB, mejor con wifi). Queda en la fila de audio donde está la línea blanca.<br><small>Voces de Piper (código abierto).</small></p>`;
    const ta = $('[data-ttxt]', p);
    ta.oninput = () => { v.texto = ta.value; };
    $('[data-grupo="tvoz"]', p).onclick = (e) => { const b = e.target.closest('[data-v]'); if (!b) return; v.voz = b.dataset.v; pintarPanel(); };
    $('[data-grupo="tvel"]', p).onclick = (e) => { if (!e.target.dataset.v) return; v.lento = Number(e.target.dataset.v); pintarPanel(); };
    $('[data-toir]', p).onclick = () => textoAVoz(false);
    $('[data-tponer]', p).onclick = () => textoAVoz(true);
    return;
  }
  if (S.tab === 'audio') {
    if (m) {
      p.innerHTML = `
        <p class="cut-nota" style="text-align:left">${m.voz ? '🎙️' : '🎵'} <b>${esc(m.nombre)}</b></p>
        <div class="cut-fila"><small>Volumen</small><input type="range" min="0" max="2" step="0.05" value="${m.vol ?? 1}" data-mvol><b class="cut-val">${Math.round((m.vol ?? 1) * 100)}%</b></div>
        <label class="cut-check"><input type="checkbox" data-limpio ${m.limpio ? 'checked' : ''}> 🎙️ Reducir ruido de fondo (voz más limpia)</label>
        <label class="cut-check"><input type="checkbox" data-fi ${m.fadeIn ? 'checked' : ''}> Aparecer suave al inicio</label>
        <label class="cut-check"><input type="checkbox" data-fo ${m.fadeOut ? 'checked' : ''}> Desaparecer suave al final</label>
        <div class="cut-acciones2">
          <button type="button" class="cut-btn-sec" data-aqui>Empezar donde está la línea</button>
          <button type="button" class="cut-btn-sec" data-cero>Empezar al inicio</button>
          <button type="button" class="cut-btn-sec" data-quitar>🗑 Quitar ${m.voz ? 'voz' : 'música'}</button>
        </div>`;
      const r = $('[data-mvol]', p);
      r.oninput = () => { m.vol = Number(r.value); $('.cut-val', p).textContent = Math.round(m.vol * 100) + '%'; sincronizar(); };
      r.onchange = guardar;
      $('[data-fi]', p).onchange = (e) => { m.fadeIn = e.target.checked; guardar(); };
      $('[data-limpio]', p).onchange = (e) => { m.limpio = e.target.checked; aplicarLimpieza(m); guardar(); };
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
        <button type="button" data-voz><i>🎙️</i>Voz en off</button>
        <button type="button" data-tts><i>🗣️</i>Texto a voz</button>
      </div>
      <p class="cut-nota"><b>Voz en off:</b> grabe su voz encima del video mientras se reproduce (con reducción de ruido).<br><b>Agregar música:</b> una canción o audio de su celular (MP3, M4A…).<br><b>Extraer audio:</b> escoja un video y se le saca solo el sonido para ponerlo en su edición.<br>Empieza donde está la línea blanca.</p>
      <label class="cut-check"><input type="checkbox" data-sil ${S.silenciar ? 'checked' : ''}> Silenciar el sonido original de los videos</label>
      ${S.musica.length ? `<p class="cut-nota">Toque una música en la fila <b>♪</b> para cambiarle el volumen o quitarla.</p>` : ''}`;
    $('[data-mus]', p).onclick = () => $('#cutMusica').click();
    $('[data-ext]', p).onclick = () => $('#cutExtraer').click();
    $('[data-voz]', p).onclick = () => vozEnOff();
    $('[data-tts]', p).onclick = () => abrirTab('voz');
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
      <div class="lbl">Animación</div>
      ${pestanasAnim(S.txLado || 'in')}
      ${(S.txLado || 'in') === 'in'
        ? rejillaAnim({ grupo: 'ai', tipo: 'tx', items: SUB.ANIM_ENTRADA.map(([v, n]) => [v, v === 'ninguna' ? 'Ninguno' : n]), actual: st.ai || 'ninguna', ca: clipBajoLinea() || S.clips[0] })
        : rejillaAnim({ grupo: 'ao', tipo: 'tx', items: SUB.ANIM_SALIDA.map(([v, n]) => [v, v === 'ninguna' ? 'Ninguno' : n]), actual: st.ao || 'ninguna', ca: clipBajoLinea() || S.clips[0], salida: true })}
      <div class="cut-fila"><small>Altura</small><input type="range" min="0.1" max="0.9" step="0.01" value="${st.pos}" data-pos></div>
      <label class="cut-check"><input type="checkbox" data-mayus ${st.mayus ? 'checked' : ''}> TODO EN MAYÚSCULAS</label>
      <div class="cut-acciones2">
        ${esSub ? '<button type="button" class="cut-btn-sec" data-nuevo>+ Texto aquí</button>' : ''}
        ${S.subs.length ? '<button type="button" class="cut-btn-sec" data-lista>Editar textos</button><button type="button" class="cut-btn-sec" data-borrar>Quitar todos</button>' : ''}
      </div>`;
    const gen = $('[data-gen]', p); if (gen) gen.onclick = () => generarSubs($('[data-idioma]', p).value, $('[data-modelo]', p).value);
    $('.cut-estilos', p).onclick = (e) => { const b = e.target.closest('[data-e]'); if (!b) return; st.estilo = b.dataset.e; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-grupo="tam"]', p).onclick = (e) => { if (!e.target.dataset.v) return; st.tam = e.target.dataset.v; pintarPanel(); S.dirty = true; guardar(); };
    // Animación para todos los textos: se ve de una vez repitiendo el texto que está más cerca
    for (const k of ['ai', 'ao']) { const g = $(`[data-grupo="${k}"]`, p); if (g) g.onclick = (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      st[k] = b.dataset.v; pintarPanel(); guardar(); verAnimTexto();
    }; }
    $('[data-lado]', p).onclick = (e) => { const b = e.target.closest('[data-l]'); if (!b) return; S.txLado = b.dataset.l; pintarPanel(); };
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
    // Se aplica sin mover la línea ni reiniciar el video (el usuario le da ▶ cuando quiera verlo)
    const cambio = () => { pintarLinea(); pintarPanel(); guardar(); irA(S.t); };
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
  if (S.tab === 'volumen') {
    if (!c || c.tipo !== 'video') { p.innerHTML = `<p class="cut-nota">Toque un video en la línea de tiempo.</p>`; return; }
    p.innerHTML = `
      <div class="cut-vel-num"><b>${Math.round((c.vol ?? 1) * 100)}</b><small>volumen del clip</small></div>
      <input type="range" class="cut-vel-rango" min="0" max="2" step="0.01" value="${c.vol ?? 1}" data-vol>
      <div class="cut-vel-marcas"><span>0</span><span>100</span><span>200</span></div>
      <label class="cut-check"><input type="checkbox" data-mute ${c.mute ? 'checked' : ''}> Silenciar este clip</label>
      <button type="button" class="cut-btn-sec" data-todos>Aplicar a todos los videos</button>`;
    const r = $('[data-vol]', p);
    r.oninput = () => { c.vol = Number(r.value); $('.cut-vel-num b', p).textContent = Math.round(c.vol * 100); if (c.vol > 0 && c.mute) { c.mute = false; $('[data-mute]', p).checked = false; } sincronizar(); };
    r.onchange = () => { pintarLinea(); guardar(); };
    $('[data-mute]', p).onchange = (e) => { c.mute = e.target.checked; sincronizar(); pintarLinea(); guardar(); };
    $('[data-todos]', p).onclick = () => { S.clips.forEach(x => { if (x.tipo === 'video') { x.vol = c.vol ?? 1; x.mute = !!c.mute; } }); sincronizar(); pintarLinea(); guardar(); aviso('Volumen puesto en todos los videos 🔈'); };
    return;
  }
  if (S.tab === 'animaciones') {
    if (!c) { p.innerHTML = `<p class="cut-nota">Toque un clip en la línea de tiempo.</p>`; return; }
    const an = c.anim || { in: '', out: '', dur: 0.5 };
    const lado = S.anLado || 'in';
    const todos = () => { if (S.anTodo) S.clips.forEach(x => { x.anim = { ...c.anim }; }); };
    p.innerHTML = `
      ${pestanasAnim(lado)}
      ${aplicarTodo(S.anTodo)}
      ${rejillaAnim({ grupo: 'anim', tipo: 'an', items: ANIMACIONES.map(a => [a.id, a.id === '' ? 'Ninguno' : a.n]), actual: an[lado] || '', cb: c, salida: lado === 'out' })}
      <div class="cut-fila"><small>Duración</small>${chips('andur', [[0.3, 'Rápida'], [0.5, 'Normal'], [0.8, 'Lenta'], [1.2, 'Muy lenta']], an.dur || 0.5)}</div>`;
    // Se ve de una vez en el video: se reproduce el inicio (entrada) o el final (salida) del clip
    const ver = () => {
      S.dirty = true;
      const L = lay(), k = S.clips.indexOf(c), d = (c.anim?.dur || 0.5) + 0.4;
      if (!c.anim?.[lado]) return;
      if (lado === 'in') { irA(L.start[k]); reproducir(L.start[k] + d); } else { const fin = L.start[k] + largo(c); irA(Math.max(L.start[k], fin - d)); reproducir(fin - 0.02); }
    };
    $('[data-lado]', p).onclick = (e) => { const b = e.target.closest('[data-l]'); if (!b) return; S.anLado = b.dataset.l; pintarPanel(); };
    $('[data-grupo="anim"]', p).onclick = (e) => { const b = e.target.closest('[data-v]'); if (!b) return; c.anim = { ...an, [lado]: b.dataset.v }; todos(); pintarPanel(); guardar(); ver(); };
    $('[data-atodo]', p).onchange = (e) => { S.anTodo = e.target.checked; if (S.anTodo && c.anim) { todos(); guardar(); aviso('Animación puesta en todos los clips ✨'); } };
    $('[data-grupo="andur"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.anim = { ...an, dur: Number(v) }; todos(); pintarPanel(); guardar(); ver(); };
    return;
  }
  if (S.tab === 'recortar') {
    if (!c) { p.innerHTML = `<p class="cut-nota">Toque un clip en la línea de tiempo.</p>`; return; }
    const z = encuadreEn(c, ltClip(c)).z;
    p.innerHTML = `
      <div class="cut-fila"><small>Acercar</small><input type="range" min="1" max="4" step="0.01" value="${z}" data-zoom><b class="cut-val" data-zv>${Math.round(z * 100)}%</b></div>
      <div class="cut-acciones2"><button type="button" class="cut-btn-sec" data-reenc>Quitar recorte</button></div>
      <p class="cut-nota">✂️ Para cortar el inicio o el final, arrastre los <b>bordes blancos</b> del clip en la línea de tiempo.<br>🔍 Para encuadrar, pellizque el video con dos dedos y arrástrelo para escoger qué parte se ve.</p>`;
    const rz = $('[data-zoom]', p);
    rz.oninput = () => { const k = puntoZoom(c); k.z = Number(rz.value); $('[data-zv]', p).textContent = Math.round(k.z * 100) + '%'; S.dirty = true; };
    rz.onchange = () => guardar();
    $('[data-reenc]', p).onclick = () => { c.z = 1; c.x = 0; c.y = 0; c.kf = null; S.dirty = true; guardar(); pintarPanel(); };
    return;
  }
  if (S.tab === 'efectos') {
    if (!c) { p.innerHTML = `<p class="cut-nota">Toque un clip en la línea de tiempo para ponerle un efecto.</p>`; return; }
    p.innerHTML = `
      <div class="cut-efs">${EFECTOS.map(e => `<button type="button" data-ef="${e.id}" class="${(c.ef || 'ninguno') === e.id ? 'on' : ''}"><i>${e.e}</i><small>${e.n}</small></button>`).join('')}</div>
      <p class="cut-nota">El efecto se pone en el clip seleccionado.</p>
      <button type="button" class="cut-btn-sec" data-todos>Aplicar a todos los clips</button>`;
    $('.cut-efs', p).onclick = (e) => { const b = e.target.closest('[data-ef]'); if (!b) return; c.ef = b.dataset.ef; pintarLinea(); pintarPanel(); guardar(); S.dirty = true; };
    $('[data-todos]', p).onclick = () => { S.clips.forEach(x => { x.ef = c.ef || 'ninguno'; }); pintarLinea(); guardar(); aviso('Efecto puesto en todos los clips ✨'); };
    return;
  }
  if (S.tab === 'filtros') {
    const f = S.filtro;
    p.innerHTML = `
      <div class="cut-fils">${FILTROS.map(x => `<button type="button" data-fil="${x.id}" class="${f.id === x.id ? 'on' : ''}"><i style="background:${x.g}"></i><small>${x.n}</small></button>`).join('')}</div>
      ${f.id !== 'ninguno' ? `<div class="cut-fila"><small>Intensidad</small><input type="range" min="0" max="1" step="0.01" value="${f.k}" data-filk><b class="cut-val">${Math.round(f.k * 100)}%</b></div>` : ''}
      <button type="button" class="cut-btn-sec" data-bel>💄 Belleza del rostro${S.belleza.on ? ' · activa' : ''}</button>`;
    $('.cut-fils', p).onclick = (e) => { const b = e.target.closest('[data-fil]'); if (!b) return; f.id = b.dataset.fil; S.dirty = true; pintarPanel(); guardar(); };
    const r = $('[data-filk]', p); if (r) { r.oninput = () => { f.k = Number(r.value); $('.cut-val', p).textContent = Math.round(f.k * 100) + '%'; S.dirty = true; }; r.onchange = guardar; }
    $('[data-bel]', p).onclick = () => abrirTab('belleza');
    return;
  }
  if (S.tab === 'ajustar') {
    const a = S.ajuste;
    const fila = (k, n, min = -1) => `<div class="cut-fila"><small>${n}</small><input type="range" min="${min}" max="1" step="0.01" value="${a[k]}" data-aj="${k}"><b class="cut-val" data-ajv="${k}">${Math.round(a[k] * 100)}</b></div>`;
    p.innerHTML = fila('brillo', 'Brillo') + fila('contraste', 'Contraste') + fila('saturacion', 'Saturación') + fila('temperatura', 'Temperatura') + fila('vineta', 'Viñeta', 0)
      + `<button type="button" class="cut-btn-sec" data-reset>Restablecer</button><p class="cut-nota">Se aplica a todo el video.</p>`;
    $$('[data-aj]', p).forEach(r => { r.oninput = () => { a[r.dataset.aj] = Number(r.value); $(`[data-ajv="${r.dataset.aj}"]`, p).textContent = Math.round(a[r.dataset.aj] * 100); S.dirty = true; }; r.onchange = guardar; });
    $('[data-reset]', p).onclick = () => { Object.keys(a).forEach(k => { a[k] = 0; }); S.dirty = true; pintarPanel(); guardar(); };
    return;
  }
  if (S.tab === 'stickers') {
    const EMO = ['😀', '😂', '😍', '🥳', '😎', '🤩', '🔥', '⭐', '❤️', '👍', '👏', '🙌', '🎉', '💯', '✨', '💥', '🎁', '🛒', '🏷️', '📢', '🆕', '✅', '📍', '➡️', '⬇️', '👇', '💰', '🍔', '🍕', '☕', '🍰', '💇', '💅', '🦷', '👗', '👟', '📸', '🎶', '🏆', '⚽'];
    p.innerHTML = `<div class="cut-emos">${EMO.map(e => `<button type="button" data-emo="${e}">${e}</button>`).join('')}</div><p class="cut-nota">Toque un sticker y aparece donde está la línea blanca. Luego lo mueve y le cambia el tamaño.</p>`;
    $('.cut-emos', p).onclick = (e) => { const b = e.target.closest('[data-emo]'); if (!b) return; if (nuevaCapa({ tipo: 'sticker', emoji: b.dataset.emo, tam: 0.28 })) aviso('Sticker agregado ' + b.dataset.emo); };
    return;
  }
  if (S.tab === 'superposicion') {
    p.innerHTML = `
      <div class="cut-acciones cut-acc-audio"><button type="button" data-sup><i>🖼️</i>Agregar foto o logo</button></div>
      <p class="cut-nota">Ponga su logo, un producto o una foto encima del video. Aparece donde está la línea blanca; luego lo mueve y le cambia el tamaño.</p>
      ${S.capas.length ? '<p class="cut-nota">Toque una capa en la última fila para editarla.</p>' : ''}`;
    $('[data-sup]', p).onclick = () => $('#cutCapaFoto').click();
    return;
  }
  if (S.tab === 'capa') {
    const k = S.capas.find(x => x.id === S.selC);
    if (!k) { p.innerHTML = `<p class="cut-nota">Toque un sticker o una foto en la fila de capas.</p>`; return; }
    const L = lay();
    p.innerHTML = `
      <p class="cut-nota" style="text-align:left">${k.tipo === 'sticker' ? k.emoji + ' <b>Sticker</b>' : '🖼 <b>' + esc(k.nombre || 'Foto') + '</b>'} · ${fmt(k.start).slice(0, 5)} → ${fmt(k.end).slice(0, 5)}</p>
      <div class="cut-fila"><small>Tamaño</small><input type="range" min="0.06" max="1" step="0.01" value="${k.tam}" data-ck="tam"></div>
      <div class="cut-fila"><small>Izquierda ↔ derecha</small><input type="range" min="0" max="1" step="0.005" value="${k.x}" data-ck="x"></div>
      <div class="cut-fila"><small>Arriba ↕ abajo</small><input type="range" min="0" max="1" step="0.005" value="${k.y}" data-ck="y"></div>
      <div class="cut-fila"><small>Duración</small>${chips('cdur', [[1, '1 s'], [2, '2 s'], [3, '3 s'], [5, '5 s'], ['todo', 'Todo el video']], '')}</div>
      <div class="cut-acciones2">
        <button type="button" class="cut-btn-sec" data-aqui>Empezar donde está la línea</button>
        <button type="button" class="cut-btn-sec" data-quitar>🗑 Quitar</button>
      </div>
      <p class="cut-nota">💡 También puede arrastrarlo con el dedo sobre el video.</p>`;
    $$('[data-ck]', p).forEach(r => { r.oninput = () => { k[r.dataset.ck] = Number(r.value); S.dirty = true; }; r.onchange = guardar; });
    $('[data-grupo="cdur"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; if (v === 'todo') { k.start = 0; k.end = L.total; } else k.end = k.start + Number(v); pintarLinea(); pintarPanel(); guardar(); };
    $('[data-aqui]', p).onclick = () => { const d = k.end - k.start; k.start = S.t; k.end = S.t + d; pintarLinea(); pintarPanel(); guardar(); };
    $('[data-quitar]', p).onclick = () => { S.capas = S.capas.filter(x => x !== k); S.selC = null; pintarLinea(); abrirTab(null); guardar(); };
    return;
  }
  if (S.tab === 'fondo') {
    const f = S.fondo;
    const COL = ['#000000', '#ffffff', '#0b0d0c', '#a6f34a', '#16b84e', '#ff3d6e', '#ffd23d', '#2f6bff', '#7a3dff', '#ff8a3d', '#f4e1c1', '#1f2a44'];
    p.innerHTML = `
      ${chips('fmodo', [['llenar', 'Llenar (recortar)'], ['desenfoque', 'Desenfocado'], ['color', 'Color']], f.modo)}
      ${f.modo === 'color' ? `<div class="cut-fila"><small>Color del fondo</small><div class="cut-colores">${COL.map(col => `<button type="button" data-fcol="${col}" class="${f.color === col ? 'on' : ''}" style="background:${col}"></button>`).join('')}</div></div>` : ''}
      <p class="cut-nota">${f.modo === 'llenar' ? 'El video llena todo el formato y lo que sobra se recorta.' : 'El video se ve completo y el espacio que sobra se rellena ' + (f.modo === 'color' ? 'con el color.' : 'con el mismo video desenfocado.')}</p>`;
    $('[data-grupo="fmodo"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; f.modo = v; S.dirty = true; pintarPanel(); guardar(); };
    const cc = $('.cut-colores', p); if (cc) cc.onclick = (e) => { const v = e.target.dataset.fcol; if (!v) return; f.color = v; S.dirty = true; pintarPanel(); guardar(); };
    return;
  }
  if (S.tab === 'portada') {
    if (!S.clips.length) { p.innerHTML = `<p class="cut-nota">Primero agregue un video.</p>`; return; }
    p.innerHTML = `
      <div class="cut-portada-v">${S.portada ? `<img src="${S.portada.url}" alt="">` : '<span>Sin portada: se usa el primer cuadro</span>'}</div>
      <button type="button" class="cut-btn" data-usar>📸 Usar el cuadro donde está la línea</button>
      ${S.portada ? '<button type="button" class="cut-btn-sec" data-quitar>Quitar portada</button>' : ''}
      <p class="cut-nota">Mueva la línea blanca al mejor momento (con textos y stickers incluidos) y tóquelo. La portada sale en el primer cuadro del video: es la que se ve en estados, TikTok y Reels.</p>`;
    $('[data-usar]', p).onclick = () => { pausar(); dibujarCuadro(S.t, fuentePrevia, vista.width, vista.height, vctx); ponerPortada(vista.toDataURL('image/jpeg', 0.88), S.t); pintarLinea(); pintarPanel(); guardarDB(); aviso('Portada lista 📸'); };
    const q = $('[data-quitar]', p); if (q) q.onclick = () => { S.portada = null; pintarLinea(); pintarPanel(); guardarDB(); };
    return;
  }
  if (S.tab === 'formato') {
    p.innerHTML = `
      <div class="cut-formatos">
        ${[['9:16', 'TikTok · Reels · Estados'], ['16:9', 'YouTube · TV'], ['1:1', 'Cuadrado'], ['4:5', 'Instagram']].map(([v, t]) => `<button type="button" data-asp="${v}" class="${S.aspecto === v ? 'on' : ''}"><span class="caja-asp" style="aspect-ratio:${v.replace(':', '/')}"></span><b>${v}</b><small>${t}</small></button>`).join('')}
      </div>
      <button type="button" class="cut-btn-sec" data-nuevo-proy style="margin-top:.8rem">📁 Mis proyectos · empezar un video nuevo</button>
      <div class="cut-acciones2"><button type="button" class="cut-btn-sec" data-editable>📦 Compartir el editable</button><button type="button" class="cut-btn-sec" data-importar>📥 Importar editable</button></div>
      <p class="cut-nota">El <b>editable</b> es su edición con los videos en un solo archivo: mándelo por WhatsApp y la otra persona lo abre aquí con "Importar editable" para seguir editándolo.</p>`;
    $('[data-nuevo-proy]', p).onclick = nuevoProyecto;
    $('[data-editable]', p).onclick = compartirEditable;
    $('[data-importar]', p).onclick = () => $('#cutImportar').click();
    $('.cut-formatos', p).onclick = (e) => { const b = e.target.closest('[data-asp]'); if (!b) return; S.aspecto = b.dataset.asp; ajustarVista(); pintarPanel(); guardar(); };
  }
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
    let buf = await decodificar(c, oc);
    if (!buf) continue;
    if (c.limpio || c.mejor || c.aislar) buf = limpiarBuffer(buf, c.fileId + '@' + oc.sampleRate);
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
    cadenaEfectos(oc, src, c).salida.connect(g).connect(oc.destination);
    src.start(ini, c.in, c.out - c.in);
  }
  if (!soloVoz) for (const m of S.musica) {
    const dur = Math.min(largoM(m), L.total - m.start);
    if (dur <= 0.05) continue;
    let buf = await decodificar(m, oc);
    if (!buf) continue;
    if (m.limpio || m.mejor || m.aislar) buf = limpiarBuffer(buf, m.fileId + '@' + oc.sampleRate);
    hay = true;
    const src = oc.createBufferSource(); src.buffer = buf;
    const g = oc.createGain(), v = m.vol ?? 1;
    const fi = m.fadeIn ? Math.min(1.5, dur / 3) : 0, fo = m.fadeOut ? Math.min(1.5, dur / 3) : 0;
    g.gain.setValueAtTime(fi ? 0 : v, m.start);
    if (fi) g.gain.linearRampToValueAtTime(v, m.start + fi);
    if (fo) { g.gain.setValueAtTime(v, m.start + dur - fo); g.gain.linearRampToValueAtTime(0, m.start + dur); }
    src.playbackRate.value = m.vel || 1;
    let sal = cadenaEfectos(oc, src, m).salida;
    if (m.kfv?.length) {
      // Fotogramas clave de volumen: la ganancia va de uno a otro (sobre el volumen base)
      const gk = oc.createGain(), base = Math.max(0.0001, v);
      gk.gain.setValueAtTime(m.kfv[0].v / base, m.start);
      for (const k of m.kfv) gk.gain.linearRampToValueAtTime(k.v / base, m.start + k.t);
      sal.connect(gk); sal = gk;
    }
    sal.connect(g).connect(oc.destination);
    src.start(m.start, m.in || 0, dur * (m.vel || 1));
  }
  if (!hay) return null;
  return oc.startRendering();
}
const audios = new Map();
async function decodificar(c, ctx) {
  const k = c.fileId + '@' + ctx.sampleRate;
  if (audios.has(k)) return audios.get(k);
  let buf;
  // Videos pesados (los del iPhone pasan de 500 MB): leer el archivo entero para sacarle el audio dejaba al
  // celular sin memoria y la pestaña se cerraba. Se decodifica solo la pista de audio, por pedazos.
  if (c.file.size > 40e6) buf = await audioPorPista(c.file, ctx);
  if (buf === undefined) {
    try { buf = await ctx.decodeAudioData(await c.file.arrayBuffer()); } catch (e) { buf = null; }
  }
  audios.set(k, buf);
  return buf;
}
// undefined = no se pudo por este camino (probar el otro) · null = el archivo no tiene sonido
async function audioPorPista(file, ctx) {
  let input;
  try {
    input = new MB.Input({ source: new MB.BlobSource(file), formats: MB.ALL_FORMATS });
    const tr = await input.getPrimaryAudioTrack();
    if (!tr) return (await input.getPrimaryVideoTrack()) ? null : undefined; // video sin sonido: no hay nada que leer
    if (!(await tr.canDecode())) return undefined;
    const rate = tr.sampleRate, nch = Math.max(1, Math.min(2, ctx.destination.channelCount || 2));
    const dur = await tr.computeDuration();
    if (!(dur > 0) || !(rate > 0)) return undefined;
    const len = Math.ceil(dur * rate) + 1;
    const out = new AudioBuffer({ length: len, numberOfChannels: nch, sampleRate: rate });
    const mezcla = nch === 1 ? new Float32Array(len) : null;
    const sink = new MB.AudioBufferSink(tr);
    for await (const w of sink.buffers()) {
      const b = w.buffer, off = Math.max(0, Math.round(w.timestamp * rate));
      if (off >= len) continue;
      const n = Math.min(b.length, len - off);
      if (mezcla) {
        for (let ch = 0; ch < b.numberOfChannels; ch++) { const d = b.getChannelData(ch); for (let i = 0; i < n; i++) mezcla[off + i] += d[i] / b.numberOfChannels; }
      } else {
        for (let ch = 0; ch < nch; ch++) out.copyToChannel(b.getChannelData(Math.min(ch, b.numberOfChannels - 1)).subarray(0, n), ch, off);
      }
    }
    if (mezcla) out.copyToChannel(mezcla, 0);
    return out;
  } catch (e) {
    return undefined;
  }
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
      trabajador.onerror = (e) => { try { trabajador.terminate(); } catch (x) { /* nada */ } trabajador = null; mal(new Error(e.message || 'Falló la transcripción')); };
      trabajador.postMessage({ tipo: 'transcribir', audio: datos, idioma, modelo }, [datos.buffer]);
    });
    const total = lay().total;
    S.segs = segs.filter(s => s.text.trim()).map(s => ({ ...s, end: s.end ?? Math.min(total, s.start + 3) }));
    S.subs = SUB.armar(S.segs, S.sub.palabras);
    poner(100, S.subs.length ? `¡Listo! ${S.subs.length} subtítulos` : 'No se entendió ninguna voz');
    contar('subtitulos', { n: S.subs.length, idioma });
    pintarLinea(); guardar();
    setTimeout(() => { if (S.tab === 'subtitulos') pintarPanel(); }, 1200);
  } catch (e) {
    poner(0, '');
    barra.hidden = true;
    aviso(e.message || 'No se pudieron generar los subtítulos', 'mal');
    if (!/no tiene sonido/.test(e.message || '')) window.LPOpinion?.error(e, { fase: 'subtitulos', idioma, modelo });
  } finally { btn.disabled = false; }
}

function editarSub(s) {
  pausar(); irA(s.start + 0.01);
  const m = $('#cutEditSub');
  m.hidden = false;
  $('textarea', m).value = s.text;
  $('[data-ini]', m).value = s.start.toFixed(1);
  $('[data-fin]', m).value = s.end.toFixed(1);
  const opc = (lista, v) => `<option value="">Igual que todos</option>` + lista.map(([k, n]) => `<option value="${k}" ${v === k ? 'selected' : ''}>${n}</option>`).join('');
  $('[data-ai]', m).innerHTML = opc(SUB.ANIM_ENTRADA, s.ai || '');
  $('[data-ao]', m).innerHTML = opc(SUB.ANIM_SALIDA, s.ao || '');
  $('[data-guardar]', m).onclick = () => {
    s.text = $('textarea', m).value.trim() || s.text;
    s.ai = $('[data-ai]', m).value || undefined; s.ao = $('[data-ao]', m).value || undefined;
    const num = (v, d) => { const n = parseFloat(String(v).replace(',', '.')); return Number.isFinite(n) ? n : d; };
    s.start = Math.max(0, num($('[data-ini]', m).value, s.start));
    s.end = Math.max(s.start + 0.2, num($('[data-fin]', m).value, s.end));
    SUB.repartir(s); S.subs.sort((a, b) => a.start - b.start);
    m.hidden = true; pintarLinea(); guardar();
  };
  $('[data-quitar]', m).onclick = () => { S.subs = S.subs.filter(x => x !== s); m.hidden = true; pintarLinea(); guardar(); };
  $('[data-cerrar]', m).onclick = () => { m.hidden = true; };
}
// Muestra la animación: reproduce el texto más cercano a la línea blanca
function verAnimTexto(s) {
  s ||= S.subs.find(x => S.t >= x.start && S.t < x.end) || S.subs.find(x => x.start >= S.t) || S.subs[0];
  if (!s) return;
  pausar(); irA(s.start); moverLinea(); reproducir();
  clearTimeout(verAnimTexto.t);
  verAnimTexto.t = setTimeout(() => { if (S.playing) pausar(); }, Math.min(4000, (s.end - s.start) * 1000 + 300));
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
    const motivo = err.message === 'memoria' ? 'el equipo se quedó sin memoria. Pruebe con 720p.' : (err.message || String(err));
    aviso('No se pudo exportar: ' + motivo, 'mal');
    $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true;
    window.LPOpinion?.error(err instanceof Error ? err : new Error(String(err)), { fase: 'exportar', res: EXP.res, fps: EXP.fps });
    contar('exporto_falla', { res: EXP.res, fps: EXP.fps, motivo: motivo.slice(0, 70) });
    let caja = $('[data-fallo]', m);
    if (!caja) { caja = document.createElement('div'); caja.dataset.fallo = ''; caja.className = 'cut-exp-info'; $('.cut-exp-op', m).prepend(caja); }
    caja.innerHTML = `<b style="color:#ff8fb8">No se pudo exportar: ${esc(motivo)}</b><br>Ya le avisamos a LocalPro. <button type="button" class="cut-btn-sec" style="margin-top:.4rem">💬 Contar qué pasó</button>`;
    $('button', caja).onclick = () => window.LPOpinion?.abrir({ tipo: 'falla', mensaje: 'Al exportar el video me salió: ' + motivo + '\n\nLo que estaba haciendo: ', datos: { fase: 'exportar', res: EXP.res, fps: EXP.fps } });
  });
  $('[data-iniciar]', m).onclick = async () => {
    $('[data-fallo]', m)?.remove();
    await invitarRegistro();
    contar('exportar', { res: EXP.res, fps: EXP.fps, seg: Math.round(lay().total) });
    intentar();
  };
}
$('#cutBtnExportar').addEventListener('click', abrirExportar);

// Del tercer video en adelante (sin cuenta) se invita a crear la cuenta gratis: no bloquea, "Ahora no" sigue exportando
function exportadosAqui() { try { return Number(localStorage.getItem('cutExportados')) || 0; } catch (e) { return 0; } }
async function invitarRegistro() {
  if (window.LP_OPINION?.sesion || exportadosAqui() < 2) return;
  try { if (sessionStorage.getItem('cutInvitado')) return; sessionStorage.setItem('cutInvitado', '1'); } catch (e) { /* nada */ }
  contar('muro_registro', { videos: exportadosAqui() });
  const si = await preguntar('Cree su cuenta gratis y siga usándolo gratis 🚀', `Ya va por su video número ${exportadosAqui() + 1}. Con su cuenta de LocalPro guarda su acceso y además tiene ventas, inventario, tienda en línea y redes para su negocio. Es gratis y toma 1 minuto.`, 'Crear mi cuenta gratis', 'Ahora no, exportar');
  if (si) { contar('clic_registro', { desde: 'invitacion' }); window.open('/registro?desde=cut-invitacion', '_blank', 'noopener'); }
}

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
      lectores.set(i, { it: sink.canvasesAtTimestamps(tiempos)[Symbol.asyncIterator](), w: sw, h: sh, ultimo: null, sink, tiempos, k: 0, fallos: 0 });
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
  pedirPantalla();

  // Modo "reproducir y grabar" (iPhone): en vez de saltar el video cuadro por cuadro (muy lento con HEVC 4K),
  // se reproduce seguido a media velocidad y se toma cada cuadro al pasar. Si el celular se atrasa, baja la velocidad.
  const tiempoReal = IOS || /[?&]tr=1/.test(location.search);
  const RT = { factor: 0.5, t: 0, desde: 0, atrasos: 0 };
  const reloj = () => RT.t + (performance.now() - RT.desde) / 1000 * RT.factor;
  const raf = () => new Promise(r => requestAnimationFrame(() => r()));
  const syncRT = (tt, tocar) => {
    S.clips.forEach((c, i) => {
      if (c.tipo !== 'video' || !c.el) return;
      const v = c.el; sonido(v, true);
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
        // Manda el video: se espera a que el video que suena llegue al cuadro que toca (sin obligarlo a saltar,
        // que es lo que dejaba la imagen quieta en videos pesados del iPhone)
        const vids = activos(t, L).filter(i => S.clips[i].tipo === 'video' && S.clips[i].el);
        S.clips.forEach((c, i) => { if (c.el && !vids.includes(i) && !c.el.paused) c.el.pause(); });
        for (let k = 0; k < vids.length; k++) {
          const i = vids[k], c = S.clips[i], v = c.el, lt = t - L.start[i], loc = tOrigen(c, lt);
          sonido(v, true);
          if (k > 0) { if (!v.paused) v.pause(); await seekEl(v, loc); continue; } // el clip que entra en una transición: cuadro exacto
          try { v.playbackRate = Math.max(0.1, Math.min(8, velEn(c, lt / largo(c)) * RT.factor)); } catch (e) { /* nada */ }
          if (v.currentTime > loc + 0.3 || loc - v.currentTime > 1.5) { v.pause(); await seekEl(v, loc); }
          if (v.paused && !v.ended) v.play().catch(() => {});
          const t0 = performance.now();
          while (v.currentTime < loc - 0.5 / fps && !v.ended && performance.now() - t0 < 2500) {
            await raf();
            if (EXP.cancelar) throw new Error('cancelado');
            if (v.paused && !v.ended) v.play().catch(() => {});
          }
          if (performance.now() - t0 >= 2500) { v.pause(); await seekEl(v, loc); } // no avanzó: se toma el cuadro exacto
          // El video va más rápido de lo que se alcanza a grabar: bajar la velocidad
          if (v.currentTime - loc > 2.5 / fps) { if (++RT.atrasos > 3 && RT.factor > 0.15) { RT.factor *= 0.8; RT.atrasos = 0; } } else RT.atrasos = 0;
        }
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
            let r = null;
            try { r = await lc.it.next(); lc.k++; } catch (err) {
              // "Decoding error": el decodificador del celular se trabó con este video (pasa en videos largos o
              // con partes dañadas). Se reintenta desde este cuadro con un lector nuevo y, si vuelve a fallar,
              // se sigue con el método de la vista previa (más lento pero seguro). El video no se cae.
              lc.fallos++;
              reportarVideo('exportar: ' + (err?.message || err) + ' (cuadro ' + n + ')', c);
              if (lc.fallos <= 2) {
                try {
                  lc.it = lc.sink.canvasesAtTimestamps(lc.tiempos.slice(lc.k))[Symbol.asyncIterator]();
                  r = await lc.it.next(); lc.k++;
                } catch (err2) { r = null; lc.el = true; }
              } else lc.el = true;
              if (lc.el) { lc.reintentar = n + fps * 2; poner(3 + (n / N) * 95, 'Una parte del video está difícil de leer: la paso por el método seguro…'); }
            }
            if (r && !r.done && r.value) lc.ultimo = r.value.canvas;
            f = lc.ultimo ? { src: lc.ultimo, w: lc.w, h: lc.h } : null;
            // Revisar los primeros cuadros: si el lector rápido los da negros pero el video sí tiene imagen, usar el método de la vista previa
            if (f && (lc.revisados = (lc.revisados || 0) + 1) <= 3 && esNegro(f.src)) {
              await seekEl(c.el, tOrigen(c, t - L.start[i]));
              if (!esNegro(c.el)) lc.el = true;
            }
          }
          // Pasada la parte dañada, se vuelve al lector rápido
          if (lc.el && lc.sink && n >= lc.reintentar) {
            try {
              const it = lc.sink.canvasesAtTimestamps(lc.tiempos.slice(lc.k))[Symbol.asyncIterator]();
              const r = await it.next();
              if (!r.done && r.value) { lc.it = it; lc.k++; lc.el = false; lc.ultimo = r.value.canvas; f = { src: lc.ultimo, w: lc.w, h: lc.h }; }
            } catch (e) { lc.reintentar = n + fps * 3; }
          } else if (lc.el && lc.sink) lc.k++;
          if (lc.el && !c.el) { try { await cargarVideo(c); } catch (e) { /* sin imagen este clip */ } }
          if (lc.el && c.el) {
            if (!tiempoReal) await seekEl(c.el, tOrigen(c, t - L.start[i]));
            else if (c.el.readyState < 2) await new Promise(r => { c.el.addEventListener('loadeddata', r, { once: true }); setTimeout(r, 800); });
            const vw = c.el.videoWidth, vh = c.el.videoHeight;
            const k = Math.min(1, Math.max(W / vw, H / vh) * 1.0001);
            if (k < 0.99 || IOS) {
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
      if (n === 0 && S.portada?.img) lctx.drawImage(S.portada.img, 0, 0, W, H);
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
    audios.clear(); limpios.clear();
    poner(99, 'Terminando el archivo…');
    await out.finalize();
  } catch (e) {
    try { await out.cancel(); } catch (e2) { /* nada */ }
    audios.clear(); limpios.clear();
    S.clips.forEach(c => { if (c.el) { c.el.pause(); try { c.el.playbackRate = 1; } catch (e3) { /* nada */ } } });
    S.exportando = false; soltarPantalla();
    motor.tamano(vistaW, vistaH); S.dirty = true;
    if (S._resizePend) { S._resizePend = false; ajustarVista(); pintarLinea(); }
    if (e.message === 'cancelado') { $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true; aviso('Exportación cancelada'); return; }
    throw e;
  }
  S.exportando = false; soltarPantalla();
  motor.tamano(vistaW, vistaH); S.dirty = true;
  if (S._resizePend) { S._resizePend = false; ajustarVista(); pintarLinea(); }

  const blob = await archivo();
  const nombre = `localprocut-${new Date().toISOString().slice(0, 10)}-${EXP.res === 2160 ? '4k' : EXP.res + 'p'}${fps}.mp4`;
  const file = new File([blob], nombre, { type: 'video/mp4' });
  if (EXP.url) URL.revokeObjectURL(EXP.url);
  const url = EXP.url = URL.createObjectURL(file);
  $('.cut-exp-run', m).hidden = true;
  const fin = $('.cut-exp-fin', m);
  fin.hidden = false;
  $('video', fin).src = url;
  $('[data-bajar]', fin).onclick = () => { contar('descargo_video'); const a = document.createElement('a'); a.href = url; a.download = nombre; document.body.appendChild(a); a.click(); a.remove(); };
  const comp = $('[data-compartir]', fin);
  comp.hidden = !(navigator.canShare && navigator.canShare({ files: [file] }));
  comp.onclick = () => { contar('compartio_video'); navigator.share({ files: [file], title: 'Mi video' }).catch(() => {}); };
  $('[data-otra]', fin).onclick = () => { $('.cut-exp-op', m).hidden = false; fin.hidden = true; };
  $('[data-editable]', fin).onclick = compartirEditable;
  // Programar en redes: el video queda guardado en el equipo y lo toma "Mis redes → Publicar" (sin volver a buscarlo).
  // Sin cuenta: crea la cuenta de creador (solo pide sus datos) y entra directo a conectar sus redes.
  $('[data-programar]', fin).onclick = async (ev) => {
    const b = ev.currentTarget; b.disabled = true;
    contar('clic_programar', { sesion: !!window.LP_OPINION?.sesion });
    await guardarParaPublicar(file);
    // En la misma ventana (en la app instalada del iPhone, otra pestaña no ve el video guardado)
    location.href = window.LP_OPINION?.sesion ? '/mis-redes/publicar' : '/creadores?desde=cut-programar';
  };
  aviso(`¡Listo! Su video quedó en ${RESOL[EXP.res].split(' ')[0]} · ${fps} fps ✅`);
  try { localStorage.setItem('cutExportados', String(exportadosAqui() + 1)); } catch (e) { /* nada */ }
  contar('exporto', { res: EXP.res, fps, seg: Math.round(L.total), mb: Math.round(blob.size / 1e5) / 10, tardo: Math.round((performance.now() - t0) / 1000) });
}

// El video exportado se guarda en el equipo (base "localprocut-publicar") para publicarlo desde Mis redes.
// Expira solo a los 2 días; Mis redes lo borra apenas lo usa.
function guardarParaPublicar(file) {
  return new Promise((ok) => {
    try {
      const r = indexedDB.open('localprocut-publicar', 1);
      r.onupgradeneeded = () => r.result.createObjectStore('v');
      r.onerror = () => ok(false);
      r.onsuccess = () => {
        try {
          const tx = r.result.transaction('v', 'readwrite');
          tx.objectStore('v').put({ file, nombre: file.name, t: Date.now() }, 'video');
          tx.oncomplete = () => ok(true); tx.onerror = tx.onabort = () => ok(false);
        } catch (e) { ok(false); }
      };
    } catch (e) { ok(false); }
  });
}

// Pantalla siempre encendida mientras exporta (si se apaga, el celular pausa el video y la exportación se queda quieta)
let candado = null;
async function pedirPantalla() {
  try { if (navigator.wakeLock && !candado) { candado = await navigator.wakeLock.request('screen'); candado.addEventListener('release', () => { candado = null; }); } } catch (e) { candado = null; }
}
function soltarPantalla() { try { candado?.release(); } catch (e) { /* nada */ } candado = null; }
/* ---------- volver al editor después de salir a otra app (WhatsApp…) ----------
   Android/iPhone sueltan la parte gráfica y el decodificador de los videos escondidos mientras la app está atrás:
   al volver sonaba el audio pero la imagen quedaba negra. Al salir se pausa; al volver se rearma todo y, si la
   vista previa sigue negra, se pasa a dibujar los videos por un lienzo 2D (camino que siempre se actualiza). */
let revisando = false;
function salioDelEditor() { if (S.playing && !S.exportando) pausar(); }
async function volvioAlEditor() {
  if (S.exportando || revisando || !S.clips.length) return;
  revisando = true;
  try {
    // 1) parte gráfica nueva si el celular la quitó
    if (!motor || motor.gl.isContextLost()) { try { if (!motor || !(await motor.recuperar(1200))) motor = new Motor(); } catch (e) { /* se reintenta en el bucle */ } }
    // 2) que cada video vuelva a decodificar su cuadro actual
    S.clips.forEach(c => { if (c.el) { c._prevT = -1; try { c.el.currentTime = Math.max(0, (c.el.currentTime || 0) + 0.001); } catch (e) { /* nada */ } } });
    ajustarVista(); irA(S.t); moverLinea();
    await new Promise(r => setTimeout(r, 900));
    // 3) si la vista sigue negra, se dibuja por lienzo 2D y, si hace falta, se recargan los videos
    if (vistaNegra()) {
      S.copia2D = true; S.clips.forEach(c => { c._prevT = -1; }); S.dirty = true;
      await new Promise(r => setTimeout(r, 600));
      if (vistaNegra()) {
        for (const c of S.clips) {
          if (!c.el || c.tipo !== 'video') continue;
          const t = c.el.currentTime || c.in || 0, src = c.el.src;
          c.el.removeAttribute('src'); c.el.load(); c.el.src = src;
          await new Promise(r => { const ok = () => { c.el.removeEventListener('loadeddata', ok); r(); }; c.el.addEventListener('loadeddata', ok); setTimeout(r, 2500); });
          try { c.el.currentTime = t; } catch (e) { /* nada */ }
        }
        motor = new Motor(); ajustarVista(); irA(S.t);
        await new Promise(r => setTimeout(r, 700));
        if (vistaNegra()) reportarVideo('Vista previa negra al volver de otra app (suena pero no se ve)', clipBajoLinea() || S.clips[0]);
      }
    }
  } finally { revisando = false; S.dirty = true; }
}
// ¿La vista previa está toda negra? (muestra de 12×12 puntos)
function vistaNegra() {
  try {
    const x = (vistaNegra.cv ||= Object.assign(document.createElement('canvas'), { width: 12, height: 12 })).getContext('2d', { willReadFrequently: true });
    x.drawImage(vista, 0, 0, 12, 12);
    const d = x.getImageData(0, 0, 12, 12).data;
    let suma = 0; for (let i = 0; i < d.length; i += 4) suma += d[i] + d[i + 1] + d[i + 2];
    return suma / (d.length / 4) < 6;
  } catch (e) { return false; }
}
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') salioDelEditor(); else setTimeout(volvioAlEditor, 250); });
window.addEventListener('pageshow', (e) => { if (e.persisted) setTimeout(volvioAlEditor, 250); });
window.addEventListener('focus', () => { if (S._salio) { S._salio = false; setTimeout(volvioAlEditor, 250); } });
window.addEventListener('blur', () => { S._salio = true; });

document.addEventListener('visibilitychange', () => {
  if (!S.exportando) return;
  if (document.visibilityState === 'visible') { pedirPantalla(); aviso('Siga en esta pantalla hasta que termine de exportar ⏳'); }
});
window.addEventListener('beforeunload', (e) => { if (S.exportando) { e.preventDefault(); e.returnValue = ''; } });

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
  async put(st, k, v) {
    try {
      const db = await this.abrir();
      await new Promise((ok, mal) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).put(v, k); tx.oncomplete = ok; tx.onerror = () => mal(tx.error); tx.onabort = () => mal(tx.error); });
      return true;
    } catch (e) {
      // Sin espacio (o modo privado): se sigue editando, pero se avisa una vez que si cierra la página se pierde
      if (!this.avisado && st === 'archivos') { this.avisado = true; aviso('Su equipo no tiene espacio para guardar una copia de los videos: si cierra o recarga la página, se pierde la edición. Exporte antes de salir.', 'mal'); }
      return false;
    }
  },
  async get(st, k) { try { const db = await this.abrir(); return await new Promise((ok) => { const r = db.transaction(st).objectStore(st).get(k); r.onsuccess = () => ok(r.result); r.onerror = () => ok(null); }); } catch (e) { return null; } },
  async borrar(st, k) { try { const db = await this.abrir(); await new Promise((ok) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).delete(k); tx.oncomplete = ok; tx.onerror = ok; }); } catch (e) { /* nada */ } },
  async todos(st) {
    try {
      const db = await this.abrir();
      return await new Promise((ok) => {
        const out = []; const r = db.transaction(st).objectStore(st).openCursor();
        r.onsuccess = () => { const cur = r.result; if (!cur) return ok(out); out.push([cur.key, cur.value]); cur.continue(); };
        r.onerror = () => ok(out);
      });
    } catch (e) { return []; }
  },
  async limpiar() { try { const db = await this.abrir(); for (const st of ['archivos', 'estado']) await new Promise((ok) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).clear(); tx.oncomplete = ok; tx.onerror = ok; }); } catch (e) { /* nada */ } },
};
let tGuardar = null;
function guardar() { registrarHist(); guardarDB(); }
/* ---------- Mis proyectos: cada video se guarda aparte (estado 'p_<id>'), los archivos se comparten por fileId ---------- */
let miniAt = 0;
function miniatura() {
  try {
    const k = 150 / Math.max(vista.width, vista.height); const c = document.createElement('canvas');
    c.width = Math.round(vista.width * k); c.height = Math.round(vista.height * k);
    c.getContext('2d').drawImage(vista, 0, 0, c.width, c.height);
    return c.toDataURL('image/jpeg', 0.6);
  } catch (e) { return null; }
}
function guardarDB() {
  clearTimeout(tGuardar);
  if (!S.clips.length) return;
  tGuardar = setTimeout(guardarYa, 400);
}
function guardarYa() {
  if (!S.clips.length) return Promise.resolve();
  if (!S.proy) { const d = new Date(); S.proy = 'p_' + uid(); S.proyNombre = 'Video del ' + d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }) + ', ' + d.toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit' }); }
  if (!S.proyMini || Date.now() - miniAt > 15000) { const m = miniatura(); if (m) { S.proyMini = m; miniAt = Date.now(); } }
  marcarAbierto(S.proy);
  return DB.put('estado', S.proy, estadoProyecto());
}
function estadoProyecto() {
  return { nombre: S.proyNombre, mini: S.proyMini || null,
      clips: S.clips.map(c => ({ id: c.id, fileId: c.fileId, nombre: c.nombre, tipo: c.tipo, in: c.in, out: c.out, dur: c.dur, mute: c.mute, vol: c.vol ?? 1, trans: c.trans, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null, ef: c.ef || 'ninguno', anim: c.anim || null, limpio: !!c.limpio })),
      musica: S.musica.map(m => ({ id: m.id, fileId: m.fileId, nombre: m.nombre, start: m.start, in: m.in, out: m.out ?? null, dur: m.dur, vol: m.vol, fadeIn: m.fadeIn, fadeOut: m.fadeOut, limpio: !!m.limpio, voz: !!m.voz, mejor: !!m.mejor, aislar: !!m.aislar, efa: m.efa || '', vel: m.vel || 1, ritmos: m.ritmos || null, kfv: m.kfv?.length ? m.kfv.map(k => ({ ...k })) : null })),
      silenciar: !!S.silenciar,
      subs: S.subs, segs: S.segs, sub: S.sub, belleza: S.belleza, aspecto: S.aspecto, fecha: Date.now(),
      capas: S.capas.map(datosCapa), filtro: S.filtro, ajuste: S.ajuste, fondo: S.fondo, portada: S.portada ? { t: S.portada.t, url: S.portada.url } : null,
  };
}
async function proyectos() {
  // El proyecto de antes (un solo video guardado como 'actual') pasa a ser uno más de la lista
  const viejo = await DB.get('estado', 'actual');
  if (viejo?.clips?.length) { await DB.put('estado', 'p_' + uid(), { nombre: 'Video del ' + new Date(viejo.fecha || Date.now()).toLocaleDateString('es-CO', { day: 'numeric', month: 'short' }), ...viejo }); }
  if (viejo) await DB.borrar('estado', 'actual');
  return (await DB.todos('estado')).filter(([k, v]) => String(k).startsWith('p_') && v?.clips?.length).sort((a, b) => (b[1].fecha || 0) - (a[1].fecha || 0));
}
async function pintarProyectos() {
  const caja = $('#cutProys'); if (!caja) return;
  const lista = await proyectos();
  caja.hidden = false;
  $('#cutVacio').classList.toggle('con-proys', !!lista.length);
  if (!lista.length) { caja.innerHTML = '<button type="button" class="cut-imp" data-imp>📥 ¿Le mandaron un editable? Importarlo</button>'; return; }
  caja.innerHTML = `<h3><span>📁 Mis proyectos</span><button type="button" class="cut-imp" data-imp>📥 Importar editable</button></h3><div class="lista">${lista.map(([k, v]) => `
    <div class="cut-proy" role="button" tabindex="0" data-abrir="${esc(k)}">${v.mini ? `<img src="${v.mini}" alt="" loading="lazy">` : '<i class="sin">🎬</i>'}
      <span>${esc(v.nombre || 'Video')}<small>${v.clips.length} ${v.clips.length === 1 ? 'clip' : 'clips'} · ${new Date(v.fecha).toLocaleDateString('es-CO', { day: 'numeric', month: 'short' })}</small></span>
      <button type="button" class="bor" data-borrar="${esc(k)}" aria-label="Borrar proyecto">🗑</button></div>`).join('')}</div>`;
}
$('#cutProys')?.addEventListener('click', async (e) => {
  if (e.target.closest('[data-imp]')) return $('#cutImportar').click();
  const b = e.target.closest('[data-borrar]');
  if (b) {
    e.stopPropagation();
    if (!(await preguntar('¿Borrar este proyecto?', 'Se borra la edición y sus videos guardados en este equipo. Esto no se puede deshacer.', 'Borrar'))) return;
    await borrarProyecto(b.dataset.borrar); return pintarProyectos();
  }
  const a = e.target.closest('[data-abrir]'); if (a) abrirProyecto(a.dataset.abrir);
});
async function borrarProyecto(k) {
  const v = await DB.get('estado', k);
  await DB.borrar('estado', k);
  if (!v) return;
  const usados = new Set();
  for (const [, o] of await DB.todos('estado')) [...(o?.clips || []), ...(o?.musica || []), ...(o?.capas || [])].forEach(x => x.fileId && usados.add(x.fileId));
  for (const x of [...(v.clips || []), ...(v.musica || []), ...(v.capas || [])]) if (x.fileId && !usados.has(x.fileId)) await DB.borrar('archivos', x.fileId);
}
// El celular a veces cierra la página cuando uno se va a otra app (le falta memoria) y al volver la recarga:
// se recuerda qué proyecto estaba abierto para abrirlo solo, como si nada hubiera pasado.
function marcarAbierto(k) { try { if (k) localStorage.setItem('lpcut_abierto', k); else localStorage.removeItem('lpcut_abierto'); } catch (e) { /* modo privado */ } }
async function recuperar() {
  let k = null; try { k = localStorage.getItem('lpcut_abierto'); } catch (e) { /* nada */ }
  await pintarProyectos();
  if (k && !S.clips.length && (await DB.get('estado', k))?.clips?.length) await abrirProyecto(k);
}
// Al salir a otra app se guarda de una vez (sin esperar), por si el celular cierra la página
function guardarAlSalir() { if (S.clips.length && !S.exportando) { clearTimeout(tGuardar); guardarYa(); } }
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') guardarAlSalir(); });
window.addEventListener('pagehide', guardarAlSalir);

/* ---------- Compartir el editable (.lpcut): la edición + sus videos en un solo archivo, armado en el celular ----------
   No pasa por el servidor. Formato: "LPCUT1\n" + 4 bytes (largo de la cabecera) + cabecera JSON + archivos uno tras otro.
   Se arma con Blob (no copia los videos en memoria) y se lee con slice (tampoco). */
const MAGIA = 'LPCUT1\n';
async function compartirEditable() {
  if (!S.clips.length) return aviso('Primero haga una edición para compartirla', 'mal');
  clearTimeout(tGuardar); await guardarYa();
  const est = estadoProyecto();
  const vistos = new Map();
  for (const o of [...S.clips, ...S.musica, ...S.capas]) if (o.fileId && o.file && !vistos.has(o.fileId)) vistos.set(o.fileId, o.file);
  const archivos = [...vistos].map(([id, f]) => ({ id, nombre: f.name || id, tipo: f.type || '', tam: f.size }));
  const cab = new TextEncoder().encode(JSON.stringify({ v: 1, app: 'LocalProCut', fecha: Date.now(), proyecto: est, archivos }));
  const largoCab = new Uint8Array(4); new DataView(largoCab.buffer).setUint32(0, cab.length);
  const nombre = (S.proyNombre || 'Mi edición').replace(/:/g, '-').replace(/\s*a\.\s*m\./g, ' am').replace(/\s*p\.\s*m\./g, ' pm').replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g, ' ').replace(/[.\s]+$/, '').trim() + '.lpcut';
  const blob = new Blob([MAGIA, largoCab, cab, ...vistos.values()], { type: 'application/octet-stream' });
  const mb = Math.round(blob.size / 1048576);
  contar('compartio_editable', { mb });
  const file = new File([blob], nombre, { type: 'application/octet-stream' });
  const texto = 'Le comparto el editable de mi video 🎬 Ábralo en LocalProCut (gratis): ' + location.origin + '/cut → Importar editable';
  if (navigator.canShare?.({ files: [file] })) {
    try { await navigator.share({ files: [file], title: nombre, text: texto }); return; } catch (e) { if (e.name === 'AbortError') return; }
  }
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 60000);
  aviso(`Editable descargado (${mb} MB) 📦 Mándelo por WhatsApp como Documento; el otro lo abre en LocalProCut con "Importar editable".`);
}
async function importarEditable(f) {
  if (!f) return;
  try {
    const magia = await f.slice(0, MAGIA.length).text();
    if (magia !== MAGIA) throw new Error('Ese archivo no es un editable de LocalProCut (.lpcut)');
    const largoCab = new DataView(await f.slice(MAGIA.length, MAGIA.length + 4).arrayBuffer()).getUint32(0);
    let pos = MAGIA.length + 4;
    const cab = JSON.parse(await f.slice(pos, pos + largoCab).text());
    pos += largoCab;
    cargandoMsg('Importando la edición…');
    // Ids nuevos para los archivos (por si ya tenía ese mismo editable importado)
    const nuevo = new Map();
    for (const a of cab.archivos) {
      const id = uid();
      const archivo = new File([f.slice(pos, pos + a.tam)], a.nombre, { type: a.tipo });
      pos += a.tam;
      if (!(await DB.put('archivos', id, archivo))) throw new Error('Su celular no tiene espacio para guardar los videos del editable');
      nuevo.set(a.id, id);
    }
    const pr = cab.proyecto;
    for (const o of [...(pr.clips || []), ...(pr.musica || []), ...(pr.capas || [])]) if (o.fileId && nuevo.has(o.fileId)) o.fileId = nuevo.get(o.fileId);
    pr.nombre = (pr.nombre || 'Edición') + ' (recibido)'; pr.fecha = Date.now();
    const k = 'p_' + uid();
    await DB.put('estado', k, pr);
    cargandoMsg(null);
    contar('importo_editable');
    if (S.clips.length) { clearTimeout(tGuardar); await guardarYa(); limpiarEditor(); }
    await abrirProyecto(k);
    aviso('Editable importado ✅ Ya puede seguir editándolo (queda en Mis proyectos)');
  } catch (e) {
    cargandoMsg(null);
    aviso(e.message || 'No se pudo abrir el editable', 'mal');
  }
}
$('#cutImportar')?.addEventListener('change', (e) => { const f = e.target.files[0]; e.target.value = ''; importarEditable(f); });
async function abrirProyecto(k) {
  const e = await DB.get('estado', k);
  if (!e?.clips?.length) return pintarProyectos();
  S.proy = k; S.proyNombre = e.nombre || 'Video'; S.proyMini = e.mini || null; marcarAbierto(k);
  $('#cutVacio').hidden = true;
  cargandoMsg('Abriendo su video…');
  Object.assign(S.sub, e.sub || {}); Object.assign(S.belleza, e.belleza || {});
  S.aspecto = e.aspecto || '9:16'; S.subs = e.subs || []; S.segs = e.segs || [];
  let faltan = 0;
  for (const d of e.clips) {
    const file = await DB.get('archivos', d.fileId);
    if (!file) { faltan++; continue; }
    const c = { ...d, file };
    try { await prepararClip(c); S.clips.push(reg(c)); } catch (err) { /* archivo dañado */ }
  }
  S.silenciar = !!e.silenciar;
  if (e.filtro) S.filtro = e.filtro;
  if (e.ajuste) S.ajuste = e.ajuste;
  if (e.fondo) S.fondo = e.fondo;
  for (const d of (e.capas || [])) {
    const k = reg({ ...d });
    if (k.tipo === 'imagen') { k.file = await DB.get('archivos', k.fileId); if (!k.file) continue; try { await cargarCapaImagen(k); } catch (err) { continue; } }
    S.capas.push(k);
  }
  if (e.portada?.url) ponerPortada(e.portada.url, e.portada.t);
  for (const d of (e.musica || [])) {
    const file = await DB.get('archivos', d.fileId);
    if (!file) continue;
    const m = reg({ ...d, file });
    try { await prepararMusica(m); S.musica.push(m); } catch (err) { /* nada */ }
  }
  cargandoMsg(null);
  if (faltan) aviso(faltan === 1 ? 'Un video no se pudo recuperar (el equipo no lo alcanzó a guardar). Agréguelo otra vez con +.' : `${faltan} videos no se pudieron recuperar (el equipo no los alcanzó a guardar). Agréguelos otra vez con +.`, 'mal');
  if (!S.clips.length) { $('#cutVacio').hidden = false; return; }
  S.sel = S.clips[0].id;
  ajustarVista(); pintarLinea(); pintarPanel();
  HIST.pila = []; HIST.pos = -1; registrarHist();
  if (S.belleza.on) activarBelleza();
}
async function nuevoProyecto() {
  if (S.clips.length && !(await preguntar('¿Empezar un video nuevo?', 'El video que tiene queda guardado en "Mis proyectos" y puede volver a abrirlo cuando quiera.', 'Empezar uno nuevo'))) return;
  pausar();
  if (S.clips.length) { clearTimeout(tGuardar); await guardarYa(); }
  limpiarEditor();
  $('#cutVacio').hidden = false; pintarProyectos();
  registrarHist();
  pintarLinea(); abrirTab(innerWidth >= 1000 ? 'editar' : null);
}
function limpiarEditor() {
  pausar();
  S.proy = null; S.proyNombre = null; S.proyMini = null; marcarAbierto(null);
  S.clips.forEach(c => { if (c.el) { c.el.pause(); c.el.remove(); } });
  S.musica.forEach(m => { if (m.el) { m.el.pause(); m.el.remove(); } });
  S.clips = []; S.subs = []; S.segs = []; S.musica = []; S.sel = null; S.selM = null; S.t = 0; S.silenciar = false;
  S.capas = []; S.selC = null; S.portada = null;
  S.filtro = { id: 'ninguno', k: 1 }; S.ajuste = { brillo: 0, contraste: 0, saturacion: 0, temperatura: 0, vineta: 0 }; S.fondo = { modo: 'llenar', color: '#000000' };
  REG.clear(); HIST.pila = []; HIST.pos = -1;
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
// Arrastrar un sticker o una foto (capa) con el dedo sobre el video
let arrCapa = null;
vista.addEventListener('pointerdown', (e) => {
  const k = S.tab === 'capa' && S.capas.find(x => x.id === S.selC && S.t >= x.start && S.t < x.end);
  if (!k) return;
  e.stopImmediatePropagation();
  if (S.playing) pausar();
  vista.setPointerCapture(e.pointerId);
  arrCapa = { k, x0: e.clientX, y0: e.clientY, kx: k.x, ky: k.y, id: e.pointerId };
}, true);
vista.addEventListener('pointermove', (e) => {
  if (!arrCapa || e.pointerId !== arrCapa.id) return;
  e.stopImmediatePropagation();
  const r = vista.getBoundingClientRect();
  arrCapa.k.x = Math.max(0, Math.min(1, arrCapa.kx + (e.clientX - arrCapa.x0) / r.width));
  arrCapa.k.y = Math.max(0, Math.min(1, arrCapa.ky + (e.clientY - arrCapa.y0) / r.height));
  S.dirty = true;
}, true);
['pointerup', 'pointercancel'].forEach(ev => vista.addEventListener(ev, (e) => {
  if (!arrCapa || e.pointerId !== arrCapa.id) return;
  e.stopImmediatePropagation();
  arrCapa = null; guardar(); pintarPanel();
}, true));
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
$('#cutZoomMas').addEventListener('click', () => zoomLinea(S.pps * 1.4));
$('#cutZoomMenos').addEventListener('click', () => zoomLinea(S.pps / 1.4));

/* ---------- zoom de la línea de tiempo con dos dedos (como CapCut) ----------
   Abrir los dedos agranda los clips (más detalle para cortar justo); cerrarlos los encoge para ver todo el video.
   La línea blanca se queda en el mismo momento del video mientras hace zoom. */
const ZOOM_MIN = 8, ZOOM_MAX = 480;
let zoomPend = null;
function zoomLinea(pps) {
  S.pps = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, pps));
  if (zoomPend) return;
  zoomPend = requestAnimationFrame(() => { zoomPend = null; pintarLinea(); moverLinea(); });
}
let pellizco = null;
const distDedos = (t) => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY);
linea.addEventListener('touchstart', (e) => {
  if (e.touches.length !== 2 || orden) return;
  clearTimeout(ordenEspera); // no confundir con "mantener presionado para mover el clip"
  if (S.playing) pausar();
  pellizco = { d0: Math.max(20, distDedos(e.touches)), pps0: S.pps };
}, { passive: true });
linea.addEventListener('touchmove', (e) => {
  if (!pellizco || e.touches.length !== 2) return;
  e.preventDefault(); // que el celular no deslice la línea ni haga zoom a la página
  zoomLinea(pellizco.pps0 * distDedos(e.touches) / pellizco.d0);
}, { passive: false });
linea.addEventListener('touchend', (e) => { if (e.touches.length < 2) pellizco = null; });
linea.addEventListener('touchcancel', () => { pellizco = null; });
// Computador: Ctrl + rueda del mouse (o pellizcar en el touchpad)
linea.addEventListener('wheel', (e) => {
  if (!e.ctrlKey) return;
  e.preventDefault();
  zoomLinea(S.pps * Math.exp(-e.deltaY * 0.01));
}, { passive: false });
window.addEventListener('resize', () => { if (S.exportando) { S._resizePend = true; return; } ajustarVista(); pintarLinea(); });
document.addEventListener('keydown', (e) => {
  const t = e.target;
  if (t && (t.closest('input, textarea, select, [contenteditable]') || t.closest('.cut-modal:not([hidden])') || t.closest('.lpo-modal'))) return;
  if ($$('.cut-modal').some(x => !x.hidden) || document.querySelector('.lpo-modal') || S.exportando) return;
  const k = e.key, ctrl = e.ctrlKey || e.metaKey;
  if (k === ' ' && !ctrl) { e.preventDefault(); if (S.playing) pausar(); else reproducir(); }
  else if (ctrl && k.toLowerCase() === 'z' && !e.shiftKey) { e.preventDefault(); deshacer(); }
  else if (ctrl && (k.toLowerCase() === 'y' || (k.toLowerCase() === 'z' && e.shiftKey))) { e.preventDefault(); rehacer(); }
  else if ((k === 'ArrowLeft' || k === 'ArrowRight') && S.clips.length) { e.preventDefault(); if (S.playing) pausar(); irA(S.t + (k === 'ArrowRight' ? 1 : -1) * (e.shiftKey ? 1 : 1 / 30)); moverLinea(); }
});
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
Object.defineProperty(window, '__motor', { get: () => motor }); // para revisar fallas desde la consola
if (window.LPOpinion) window.LPOpinion.contexto = () => {
  const L = lay();
  return {
    duracion: Math.round(L.total * 10) / 10, aspecto: S.aspecto, exportando: !!S.exportando, export: { res: EXP.res, fps: EXP.fps },
    ios: IOS, webcodecs: typeof VideoEncoder !== 'undefined', grafica: motor ? (motor.gl.isContextLost() ? 'perdida' : 'ok') : 'sin motor',
    clips: S.clips.slice(0, 20).map(c => ({ tipo: c.tipo, ext: ((c.nombre || '').match(/\.\w+$/) || [''])[0].toLowerCase(), mb: c.file ? Math.round(c.file.size / 1e5) / 10 : null, dur: Math.round((c.dur || 0) * 10) / 10, px: c.w ? c.w + 'x' + c.h : null, vel: c.vel || 1, curva: c.curva || null, trans: c.trans?.tipo, ef: c.ef || null })),
    musica: S.musica.length, subtitulos: S.subs.length, capas: S.capas.length, belleza: !!S.belleza.on, filtro: S.filtro.id, tab: S.tab,
  };
};
