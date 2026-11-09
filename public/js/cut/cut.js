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
    try { const src = a.createMediaElementSource(el); el._g = a.createGain(); src.connect(el._g).connect(a.destination); } catch (e) { el._gFallo = true; }
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
    clips: S.clips.map(c => ({ id: c.id, in: c.in, out: c.out, mute: !!c.mute, vol: c.vol ?? 1, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null, trans: c.trans, ef: c.ef || 'ninguno', anim: c.anim || null })),
    capas: S.capas.map(datosCapa), filtro: S.filtro, ajuste: S.ajuste, fondo: S.fondo,
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
  k.bmp = await createImageBitmap(k.file, { imageOrientation: 'from-image' });
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
    const loc = m.in + (S.t - m.start);
    volumen(a, m.vol ?? 1);
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
    $('#cutClave').classList.toggle('on', !!cc?.kf?.some(q => q.t > 0.01 && Math.abs(q.t - lt) < 0.12));
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
    b.onclick = (e) => { e.stopPropagation(); if (b._recorto) { b._recorto = false; return; } if (Date.now() - (S._finOrden || 0) < 500) return; S.sel = c.id; S.selM = null; S.selC = null; S.barraClip = true; abrirTab(TABS_CLIP.includes(S.tab) ? S.tab : null); pintarLinea(); };
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
    b.innerHTML = `<span>${esc(m.nombre.replace(/\.[^.]+$/, ''))}</span>`;
    if (m.onda) { b.style.backgroundImage = `url(${m.onda})`; b.style.backgroundSize = `${largoM(m) * S.pps}px 100%`; b.style.backgroundPositionX = `${-(m.in || 0) * S.pps}px`; }
    else ondas(m);
    b.onclick = (e) => { e.stopPropagation(); S.selM = m.id; S.sel = null; S.barraClip = false; abrirTab('audio'); pintarLinea(); };
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
  const files = [...lista].filter(f => /^(video|image)\//.test(f.type) || /\.(mp4|mov|webm|m4v|jpe?g|png|webp|hei[cf])$/i.test(f.name));
  if (!files.length) return;
  $('#cutVacio').hidden = true;
  cargandoMsg(`Cargando ${files.length} ${files.length === 1 ? 'archivo' : 'archivos'}…`);
  for (const f of files) {
    const c = { id: uid(), fileId: uid(), file: f, nombre: f.name, tipo: f.type.startsWith('image/') || /\.(jpe?g|png|webp|hei[cf])$/i.test(f.name) ? 'imagen' : 'video', in: 0, out: null, dur: 0, mute: false, trans: { tipo: 'ninguna', dur: 0.5 } };
    if (c.tipo === 'imagen') { c.dur = 3; c.out = 3; }
    try {
      await prepararClip(c);
      S.clips.push(reg(c));
      await DB.put('archivos', c.fileId, f);
    } catch (e) {
      const heic = /\.hei[cf]$/i.test(f.name) || /hei[cf]/i.test(f.type);
      aviso(heic ? `"${f.name}" es una foto HEIC del iPhone y este navegador no la abre. Compártala como JPG o en el iPhone ponga Ajustes → Cámara → Formatos → Más compatible.`
        : `No se pudo abrir "${f.name}". Pruebe con otro archivo (MP4, MOV, JPG o PNG).`, 'mal');
      window.LPOpinion?.error(new Error('No abrió un archivo: ' + (e?.message || e)), { fase: 'agregar', tipo: f.type || '?', ext: (f.name.match(/\.\w+$/) || [''])[0].toLowerCase(), mb: Math.round(f.size / 1e5) / 10 });
    }
  }
  cargandoMsg(null);
  contar('agrego', { n: files.length, fotos: files.filter(f => /^image\//.test(f.type)).length });
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
const TITULOS = { volumen: 'Volumen', animaciones: 'Animaciones', recortar: 'Recortar', editar: 'Editar', audio: 'Audio', texto: 'Texto', efectos: 'Efectos', superposicion: 'Superposición', subtitulos: 'Subtítulos', filtros: 'Filtros', ajustar: 'Ajustar', stickers: 'Stickers', formato: 'Relación de aspecto', fondo: 'Fondo', velocidad: 'Velocidad', transicion: 'Transición', belleza: 'Belleza del rostro', capa: 'Capa', portada: 'Portada' };
function abrirTab(t) {
  S.tab = t;
  pintarBarra();
  $('#cutPanel').hidden = !t;
  document.body.classList.toggle('con-panel', !!t);
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
  mover: '<path d="M5 12h14M15 8l4 4-4 4M9 8l-4 4 4 4"/>',
};
const ic = (k) => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${ICS[k]}</svg>`;
function pintarBarra() {
  const c = S.barraClip && clipSel();
  const modo = c ? 'clip:' + c.id + ':' + c.tipo + ':' + (S.clips.indexOf(c) < S.clips.length - 1) + ':' + !!c.mute : 'general';
  if (barra._modo !== modo) {
    barra._modo = modo;
    if (!c) barra.innerHTML = BARRA_GENERAL;
    else {
      const v = c.tipo === 'video', hay = S.clips.indexOf(c) < S.clips.length - 1;
      const it = [
        ['acc', 'dividir', 'Dividir'], v && ['tab', 'volumen', 'Volumen'], ['tab', 'animaciones', 'Animaciones'], ['tab', 'efectos', 'Efectos'],
        ['acc', 'eliminar', 'Eliminar'], v && ['tab', 'velocidad', 'Velocidad'], ['tab', 'recortar', 'Recortar'], ['acc', 'duplicar', 'Duplicar'],
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
  if (b.dataset.volver != null) { S.barraClip = false; S.sel = null; abrirTab(null); pintarLinea(); return; }
  if (b.dataset.tab) return abrirTab(S.tab === b.dataset.tab && innerWidth < 1000 ? null : b.dataset.tab);
  const c = clipSel(); if (!c) return;
  ({ dividir, eliminar: () => { eliminar(); S.barraClip = false; S.sel = null; abrirTab(null); pintarLinea(); }, duplicar, sustituir: () => $('#cutSustituir').click(), extraer: () => extraerDeClip(c) })[b.dataset.acc]?.();
});
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
    p.innerHTML = `
      <div class="cut-trans">${TRANSICIONES.map(t => `<button type="button" class="${c.trans.tipo === t.id ? 'on' : ''}" data-t="${t.id}"><span class="ic ic-${t.id}"></span>${t.n}</button>`).join('')}</div>
      <div class="cut-fila"><small>Duración</small>${chips('durtr', [[0.3, 'Rápida'], [0.5, 'Normal'], [0.8, 'Lenta'], [1.2, 'Muy lenta']], c.trans.dur)}</div>
      <button type="button" class="cut-btn-sec" data-todas>Aplicar a todos los cortes</button>`;
    $('.cut-trans', p).onclick = (e) => {
      const b = e.target.closest('[data-t]'); if (!b) return;
      c.trans.tipo = b.dataset.t; pintarLinea(); pintarPanel(); guardar(); irA(S.t);
    };
    $('[data-grupo="durtr"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.trans.dur = Number(v); pintarLinea(); pintarPanel(); guardar(); irA(S.t); };
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
    const grid = (g, act) => `<div class="cut-efs" data-an="${g}">${ANIMACIONES.map(a => `<button type="button" data-v="${a.id}" class="${(act || '') === a.id ? 'on' : ''}"><i>${{ '': '⦸', fundido: '🌗', acercar: '🔍', alejar: '🔭', deslizar: '➡️', subir: '⬆️', girar: '🌀' }[a.id]}</i><small>${a.n}</small></button>`).join('')}</div>`;
    p.innerHTML = `
      <div class="lbl">Entrada</div>${grid('in', an.in)}
      <div class="lbl">Salida</div>${grid('out', an.out)}
      <div class="cut-fila"><small>Duración</small>${chips('andur', [[0.3, 'Rápida'], [0.5, 'Normal'], [0.8, 'Lenta'], [1.2, 'Muy lenta']], an.dur || 0.5)}</div>`;
    const ver = () => { S.dirty = true; };
    $$('[data-an]', p).forEach(g => g.onclick = (e) => { const b = e.target.closest('[data-v]'); if (!b) return; c.anim = { ...an, [g.dataset.an]: b.dataset.v }; pintarPanel(); guardar(); ver(g.dataset.an === 'out'); });
    $('[data-grupo="andur"]', p).onclick = (e) => { const v = e.target.dataset.v; if (!v) return; c.anim = { ...an, dur: Number(v) }; pintarPanel(); guardar(); ver(false); };
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
      <button type="button" class="cut-btn-sec" data-nuevo-proy style="margin-top:.8rem">Empezar un video nuevo</button>`;
    $('[data-nuevo-proy]', p).onclick = nuevoProyecto;
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
  $('[data-guardar]', m).onclick = () => {
    s.text = $('textarea', m).value.trim() || s.text;
    const num = (v, d) => { const n = parseFloat(String(v).replace(',', '.')); return Number.isFinite(n) ? n : d; };
    s.start = Math.max(0, num($('[data-ini]', m).value, s.start));
    s.end = Math.max(s.start + 0.2, num($('[data-fin]', m).value, s.end));
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
    audios.clear();
    poner(99, 'Terminando el archivo…');
    await out.finalize();
  } catch (e) {
    try { await out.cancel(); } catch (e2) { /* nada */ }
    audios.clear();
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
  aviso(`¡Listo! Su video quedó en ${RESOL[EXP.res].split(' ')[0]} · ${fps} fps ✅`);
  try { localStorage.setItem('cutExportados', String(exportadosAqui() + 1)); } catch (e) { /* nada */ }
  contar('exporto', { res: EXP.res, fps, seg: Math.round(L.total), mb: Math.round(blob.size / 1e5) / 10, tardo: Math.round((performance.now() - t0) / 1000) });
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
  async limpiar() { try { const db = await this.abrir(); for (const st of ['archivos', 'estado']) await new Promise((ok) => { const tx = db.transaction(st, 'readwrite'); tx.objectStore(st).clear(); tx.oncomplete = ok; tx.onerror = ok; }); } catch (e) { /* nada */ } },
};
let tGuardar = null;
function guardar() { registrarHist(); guardarDB(); }
function guardarDB() {
  clearTimeout(tGuardar);
  tGuardar = setTimeout(() => {
    DB.put('estado', 'actual', {
      clips: S.clips.map(c => ({ id: c.id, fileId: c.fileId, nombre: c.nombre, tipo: c.tipo, in: c.in, out: c.out, dur: c.dur, mute: c.mute, vol: c.vol ?? 1, trans: c.trans, vel: c.vel || 1, curva: c.curva || null, z: c.z || 1, x: c.x || 0, y: c.y || 0, kf: c.kf?.length ? c.kf : null, ef: c.ef || 'ninguno', anim: c.anim || null })),
      musica: S.musica.map(m => ({ id: m.id, fileId: m.fileId, nombre: m.nombre, start: m.start, in: m.in, out: m.out ?? null, dur: m.dur, vol: m.vol, fadeIn: m.fadeIn, fadeOut: m.fadeOut })),
      silenciar: !!S.silenciar,
      subs: S.subs, segs: S.segs, sub: S.sub, belleza: S.belleza, aspecto: S.aspecto, fecha: Date.now(),
      capas: S.capas.map(datosCapa), filtro: S.filtro, ajuste: S.ajuste, fondo: S.fondo, portada: S.portada ? { t: S.portada.t, url: S.portada.url } : null,
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
  if (S.clips.length && !(await preguntar('¿Empezar un video nuevo?', 'Se borra lo que tiene en la línea de tiempo.', 'Empezar de nuevo'))) return;
  pausar();
  S.clips.forEach(c => { if (c.el) { c.el.pause(); c.el.remove(); } });
  S.musica.forEach(m => { if (m.el) { m.el.pause(); m.el.remove(); } });
  S.clips = []; S.subs = []; S.segs = []; S.musica = []; S.sel = null; S.selM = null; S.t = 0; S.silenciar = false;
  S.capas = []; S.selC = null; S.portada = null;
  S.filtro = { id: 'ninguno', k: 1 }; S.ajuste = { brillo: 0, contraste: 0, saturacion: 0, temperatura: 0, vineta: 0 }; S.fondo = { modo: 'llenar', color: '#000000' };
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
$('#cutZoomMas').addEventListener('click', () => { S.pps = Math.min(240, S.pps * 1.4); pintarLinea(); });
$('#cutZoomMenos').addEventListener('click', () => { S.pps = Math.max(12, S.pps / 1.4); pintarLinea(); });
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
