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

const ASPECTOS = { '9:16': [9, 16], '16:9': [16, 9], '1:1': [1, 1], '4:5': [4, 5] };
const RESOL = { 720: '720p', 1080: '1080p · Full HD', 1440: '2K', 2160: '4K' };

const S = {
  clips: [], subs: [], segs: [],
  sub: { estilo: 'tiktok', tam: 'M', pos: 0.72, color: '#a6f34a', mayus: false, palabras: 3 },
  belleza: { on: false, suave: 0.55, aclara: 0.3, brillo: 0.1, preset: 'natural' },
  aspecto: '9:16',
  t: 0, playing: false, sel: null, tab: 'editar', pps: 56,
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

/* ---------- tiempos de la línea ---------- */
function largo(c) { return Math.max(0.1, c.out - c.in); }
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
      motor.clip(k, f?.src, f?.w, f?.h, S.belleza.on ? f?.mascara : null, S.belleza);
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
    if (!c.ultDet || ahora - c.ultDet > 140) { c.ultDet = ahora; c.mascara ||= document.createElement('canvas'); BEL.mascara(v, c.mascara); }
  }
  return { src: v, w: v.videoWidth, h: v.videoHeight, mascara: c.mascara };
}
function sincronizar() {
  const L = lay();
  S.clips.forEach((c, i) => {
    if (c.tipo !== 'video' || !c.el) return;
    const v = c.el;
    const act = S.t >= L.start[i] && S.t < L.start[i] + largo(c);
    const loc = c.in + (S.t - L.start[i]);
    v.muted = !!c.mute;
    if (act && S.playing) {
      if (v.paused) { v.currentTime = loc; v.play().catch(() => {}); }
      else if (Math.abs(v.currentTime - loc) > 0.3) v.currentTime = loc;
    } else {
      if (!v.paused) v.pause();
      if (act && Math.abs(v.currentTime - loc) > 0.03 && !v.seeking) v.currentTime = loc;
    }
  });
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
  if (S.dirty && motor && !S.exportando) {
    S.dirty = false;
    if (S.clips.length) dibujarCuadro(S.t, fuentePrevia, vista.width, vista.height, vctx);
    else { vctx.fillStyle = '#000'; vctx.fillRect(0, 0, vista.width, vista.height); }
    $('#cutTiempo').textContent = `${fmt(S.t)} / ${fmt(lay().total)}`;
  }
  requestAnimationFrame(bucle);
}
function reproducir(hasta = null) {
  if (!S.clips.length) return;
  const L = lay();
  if (S.t >= L.total - 0.05) S.t = 0;
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
  cont.style.width = (L.total * S.pps + pad * 2 + 60) + 'px';
  cont.style.setProperty('--pad', pad + 'px');
  const pista = $('#cutPistaV');
  pista.innerHTML = '';
  S.clips.forEach((c, i) => {
    const b = document.createElement('div');
    b.className = 'cut-clip' + (S.sel === c.id ? ' sel' : '') + (c.mute ? ' mudo' : '');
    b.style.left = (pad + L.start[i] * S.pps) + 'px';
    b.style.width = Math.max(18, largo(c) * S.pps - 2) + 'px';
    if (c.tira) {
      b.style.backgroundImage = `url(${c.tira})`;
      const anchoTotal = (c.tipo === 'imagen' ? largo(c) : c.dur) * S.pps;
      b.style.backgroundSize = c.tipo === 'imagen' ? 'auto 100%' : `${anchoTotal}px 100%`;
      b.style.backgroundPositionX = c.tipo === 'imagen' ? '0' : `${-c.in * S.pps}px`;
      b.style.backgroundRepeat = c.tipo === 'imagen' ? 'repeat-x' : 'no-repeat';
    }
    b.innerHTML = `<span>${c.tipo === 'imagen' ? '🖼' : (c.mute ? '🔇' : '')} ${fmt(largo(c)).slice(0, 5)}</span>`;
    b.onclick = (e) => { e.stopPropagation(); S.sel = c.id; abrirTab('editar'); pintarLinea(); };
    pista.appendChild(b);
    if (i < S.clips.length - 1) {
      const tb = document.createElement('button');
      tb.type = 'button';
      tb.className = 'cut-rombo' + (c.trans.tipo !== 'ninguna' ? ' on' : '');
      tb.title = 'Transición';
      tb.style.left = (pad + (L.start[i + 1] + L.tr[i] / 2) * S.pps - 13) + 'px';
      tb.innerHTML = c.trans.tipo !== 'ninguna' ? '◆' : '◇';
      tb.onclick = (e) => { e.stopPropagation(); S.sel = c.id; abrirTab('transicion'); pintarLinea(); };
      pista.appendChild(tb);
    }
  });
  const mas = document.createElement('button');
  mas.type = 'button';
  mas.className = 'cut-mas';
  mas.style.left = (pad + L.total * S.pps + 10) + 'px';
  mas.innerHTML = '+';
  mas.onclick = () => $('#cutArchivo').click();
  pista.appendChild(mas);

  const ps = $('#cutPistaS');
  ps.innerHTML = '';
  ps.hidden = !S.subs.length;
  S.subs.forEach((s) => {
    const b = document.createElement('div');
    b.className = 'cut-sub';
    b.style.left = (pad + s.start * S.pps) + 'px';
    b.style.width = Math.max(10, (s.end - s.start) * S.pps - 2) + 'px';
    b.textContent = s.text;
    b.onclick = (e) => { e.stopPropagation(); editarSub(s); };
    ps.appendChild(b);
  });
  moverLinea();
  S.dirty = true;
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
      S.clips.push(c);
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
  const loc = c.in + (S.t - L.start[i]);
  const n = { ...c, id: uid(), in: loc, el: null, mascara: null, trans: { ...c.trans } };
  c.out = loc; c.trans = { tipo: 'ninguna', dur: 0.5 };
  S.clips.splice(i + 1, 0, n);
  const listo = () => { S.sel = n.id; pintarLinea(); pintarPanel(); guardar(); aviso('Clip dividido ✂️'); };
  if (n.tipo === 'video') cargarVideo(n).then(listo); else listo();
}
async function eliminar() {
  const c = clipSel(); if (!c) return;
  const i = S.clips.indexOf(c);
  S.clips.splice(i, 1);
  if (c.el) { c.el.pause(); c.el.remove(); }
  S.sel = S.clips[Math.min(i, S.clips.length - 1)]?.id || null;
  if (!S.clips.length) $('#cutVacio').hidden = false;
  irA(S.t); pintarLinea(); pintarPanel(); guardar();
}
function duplicar() {
  const c = clipSel(); if (!c) return;
  const i = S.clips.indexOf(c);
  const n = { ...c, id: uid(), el: null, mascara: null, trans: { ...c.trans } };
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
function abrirTab(t) {
  S.tab = t;
  $$('.cut-tab').forEach(b => b.classList.toggle('on', b.dataset.tab === t));
  pintarPanel();
}
$$('.cut-tab').forEach(b => b.addEventListener('click', () => {
  if (b.dataset.tab === 'agregar') return $('#cutArchivo').click();
  abrirTab(b.dataset.tab);
}));

function chips(nombre, opciones, actual) {
  return `<div class="cut-chips" data-grupo="${nombre}">${opciones.map(([v, t]) => `<button type="button" class="cut-chip ${String(v) === String(actual) ? 'on' : ''}" data-v="${v}">${t}</button>`).join('')}</div>`;
}

function pintarPanel() {
  const p = $('#cutPanel');
  const c = clipSel();
  if (S.tab === 'editar') {
    if (!c) { p.innerHTML = `<p class="cut-nota">Agregue videos o fotos con el botón <b>+</b> y toque un clip para editarlo.</p>`; return; }
    p.innerHTML = `
      <div class="cut-acciones">
        <button type="button" data-a="dividir"><i>✂️</i>Dividir aquí</button>
        <button type="button" data-a="eliminar"><i>🗑️</i>Eliminar</button>
        <button type="button" data-a="duplicar"><i>⧉</i>Duplicar</button>
        <button type="button" data-a="izq"><i>⬅️</i>Mover</button>
        <button type="button" data-a="der"><i>➡️</i>Mover</button>
        ${c.tipo === 'video' ? `<button type="button" data-a="mute"><i>${c.mute ? '🔈' : '🔇'}</i>${c.mute ? 'Con sonido' : 'Silenciar'}</button>` : ''}
      </div>
      ${c.tipo === 'imagen' ? `<div class="cut-fila"><small>Duración de la foto</small>${chips('durfoto', [[2, '2 s'], [3, '3 s'], [5, '5 s'], [8, '8 s']], largo(c))}</div>` : ''}
      <p class="cut-nota">Para recortar: ponga la línea blanca donde quiere cortar, toque <b>Dividir aquí</b> y elimine el pedazo que sobra.</p>`;
    $$('[data-a]', p).forEach(b => b.onclick = () => ({ dividir, eliminar, duplicar, izq: () => mover(-1), der: () => mover(1), mute: () => { c.mute = !c.mute; pintarLinea(); pintarPanel(); sincronizar(); guardar(); } })[b.dataset.a]());
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
  if (S.tab === 'subtitulos') {
    const st = S.sub;
    p.innerHTML = `
      <div class="cut-gen">
        <button type="button" class="cut-btn" data-gen>✨ ${S.subs.length ? 'Volver a generar' : 'Generar subtítulos automáticos'}</button>
        <div class="cut-gen-op">
          <select data-idioma><option value="spanish">Español</option><option value="english">Inglés</option><option value="portuguese">Portugués</option></select>
          <select data-modelo><option value="onnx-community/whisper-base">Rápido</option><option value="onnx-community/whisper-small">Más preciso</option></select>
        </div>
        <div class="cut-progreso" hidden><i></i><span></span></div>
      </div>
      <div class="cut-estilos">${SUB.ESTILOS.map(e => `<button type="button" class="est est-${e.id} ${st.estilo === e.id ? 'on' : ''}" data-e="${e.id}"><b>Hola</b><small>${e.n}</small></button>`).join('')}</div>
      <div class="cut-fila"><small>Tamaño</small>${chips('tam', [['S', 'Pequeño'], ['M', 'Mediano'], ['L', 'Grande']], st.tam)}</div>
      <div class="cut-fila"><small>Color</small><div class="cut-colores">${SUB.COLORES.map(col => `<button type="button" data-col="${col}" class="${st.color === col ? 'on' : ''}" style="background:${col}"></button>`).join('')}</div></div>
      <div class="cut-fila"><small>Palabras por subtítulo</small>${chips('pal', [[1, '1'], [2, '2'], [3, '3'], [5, '5'], [0, 'Frase']], st.palabras)}</div>
      <div class="cut-fila"><small>Altura</small><input type="range" min="0.1" max="0.9" step="0.01" value="${st.pos}" data-pos></div>
      <label class="cut-check"><input type="checkbox" data-mayus ${st.mayus ? 'checked' : ''}> TODO EN MAYÚSCULAS</label>
      <div class="cut-acciones2">
        <button type="button" class="cut-btn-sec" data-nuevo>+ Texto aquí</button>
        ${S.subs.length ? '<button type="button" class="cut-btn-sec" data-lista>Editar textos</button><button type="button" class="cut-btn-sec" data-borrar>Quitar subtítulos</button>' : ''}
      </div>`;
    $('[data-gen]', p).onclick = () => generarSubs($('[data-idioma]', p).value, $('[data-modelo]', p).value);
    $('.cut-estilos', p).onclick = (e) => { const b = e.target.closest('[data-e]'); if (!b) return; st.estilo = b.dataset.e; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-grupo="tam"]', p).onclick = (e) => { if (!e.target.dataset.v) return; st.tam = e.target.dataset.v; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-grupo="pal"]', p).onclick = (e) => { if (e.target.dataset.v == null) return; st.palabras = Number(e.target.dataset.v); if (S.segs.length) { S.subs = SUB.armar(S.segs, st.palabras); pintarLinea(); } pintarPanel(); guardar(); };
    $('.cut-colores', p).onclick = (e) => { if (!e.target.dataset.col) return; st.color = e.target.dataset.col; pintarPanel(); S.dirty = true; guardar(); };
    $('[data-pos]', p).oninput = (e) => { st.pos = Number(e.target.value); S.dirty = true; };
    $('[data-pos]', p).onchange = guardar;
    $('[data-mayus]', p).onchange = (e) => { st.mayus = e.target.checked; S.dirty = true; guardar(); };
    $('[data-nuevo]', p).onclick = () => { const s = SUB.repartir({ id: uid(), start: S.t, end: Math.min(S.t + 2, lay().total || S.t + 2), text: 'Escriba aquí' }); S.subs.push(s); S.subs.sort((a, b) => a.start - b.start); pintarLinea(); editarSub(s); };
    const l = $('[data-lista]', p); if (l) l.onclick = listaSubs;
    const q = $('[data-borrar]', p); if (q) q.onclick = async () => { if (await preguntar('¿Quitar los subtítulos?', 'Se borran todos los textos del video.', 'Quitar')) { S.subs = []; S.segs = []; pintarLinea(); pintarPanel(); guardar(); } };
    return;
  }
  if (S.tab === 'belleza') {
    const b = S.belleza;
    p.innerHTML = `
      <label class="cut-switch"><input type="checkbox" data-on ${b.on ? 'checked' : ''}><span></span> Filtro de belleza</label>
      <div class="cut-estado-bel"></div>
      <div class="cut-fila"><small>Estilo</small>${chips('preset', [['natural', 'Natural'], ['suave', 'Suave'], ['glam', 'Glam']], b.preset)}</div>
      <div class="cut-fila"><small>Suavizar piel</small><input type="range" min="0" max="1" step="0.01" value="${b.suave}" data-k="suave"></div>
      <div class="cut-fila"><small>Iluminar rostro</small><input type="range" min="0" max="1" step="0.01" value="${b.aclara}" data-k="aclara"></div>
      <div class="cut-fila"><small>Brillo general</small><input type="range" min="0" max="1" step="0.01" value="${b.brillo}" data-k="brillo"></div>
      <p class="cut-nota">Detecta los rostros y suaviza solo la piel: ojos, cejas y boca quedan nítidos.</p>`;
    const estado = $('.cut-estado-bel', p);
    const pintarEstado = () => { estado.textContent = b.on ? (BEL.listo() ? '✅ Detector de rostro activo' : '⏳ Cargando detector de rostro…') : ''; };
    pintarEstado();
    $('[data-on]', p).onchange = async (e) => { b.on = e.target.checked; S.dirty = true; guardar(); pintarEstado(); if (b.on) activarBelleza().then(pintarEstado); };
    $('[data-grupo="preset"]', p).onclick = (e) => {
      const v = e.target.dataset.v; if (!v) return;
      Object.assign(b, { natural: { suave: 0.55, aclara: 0.3, brillo: 0.1 }, suave: { suave: 0.8, aclara: 0.45, brillo: 0.2 }, glam: { suave: 1, aclara: 0.65, brillo: 0.35 } }[v], { preset: v, on: true });
      pintarPanel(); S.dirty = true; guardar(); activarBelleza().then(() => pintarPanel());
    };
    $$('input[type=range]', p).forEach(r => { r.oninput = () => { b[r.dataset.k] = Number(r.value); b.on = true; $('[data-on]', p).checked = true; S.dirty = true; }; r.onchange = () => { guardar(); activarBelleza(); }; });
    return;
  }
  if (S.tab === 'formato') {
    p.innerHTML = `
      <div class="cut-formatos">
        ${[['9:16', 'TikTok · Reels · Estados'], ['16:9', 'YouTube · TV'], ['1:1', 'Cuadrado'], ['4:5', 'Instagram']].map(([v, t]) => `<button type="button" data-asp="${v}" class="${S.aspecto === v ? 'on' : ''}"><span class="caja-asp" style="aspect-ratio:${v.replace(':', '/')}"></span><b>${v}</b><small>${t}</small></button>`).join('')}
      </div>`;
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
async function mezclarAudio(rate, canales) {
  const L = lay();
  const len = Math.max(1, Math.ceil(L.total * rate));
  const oc = new OfflineAudioContext(canales, len, rate);
  let hay = false;
  for (let i = 0; i < S.clips.length; i++) {
    const c = S.clips[i];
    if (c.tipo !== 'video' || c.mute) continue;
    const buf = await decodificar(c, oc);
    if (!buf) continue;
    hay = true;
    const src = oc.createBufferSource(); src.buffer = buf;
    const g = oc.createGain();
    const ini = L.start[i], dur = largo(c);
    const fe = i > 0 ? L.tr[i - 1] : 0, fs = L.tr[i] || 0;
    g.gain.setValueAtTime(fe ? 0 : 1, ini);
    if (fe) g.gain.linearRampToValueAtTime(1, ini + fe);
    if (fs) { g.gain.setValueAtTime(1, ini + dur - fs); g.gain.linearRampToValueAtTime(0, ini + dur); }
    src.connect(g).connect(oc.destination);
    src.start(ini, c.in, dur);
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
  const p = $('#cutPanel');
  const barra = $('.cut-progreso', p);
  const btn = $('[data-gen]', p);
  const poner = (pct, txt) => { barra.hidden = false; $('i', barra).style.width = pct + '%'; $('span', barra).textContent = txt; };
  btn.disabled = true;
  poner(3, 'Preparando el audio…');
  try {
    const audio = await mezclarAudio(16000, 1);
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
  // Desactivar lo que el equipo no puede codificar
  for (const b of $$('button', ops)) {
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
  $('[data-iniciar]', m).onclick = () => exportar().catch(err => { S.exportando = false; ajustarVista(); aviso('No se pudo exportar: ' + (err.message || err), 'mal'); $('.cut-exp-op', m).hidden = false; $('.cut-exp-run', m).hidden = true; });
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
    for (let n = 0; n < N; n++) { const t = n / fps; if (t >= L.start[i] && t < L.start[i] + largo(c)) tiempos.push(c.in + (t - L.start[i])); }
    try {
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
  S.exportando = true;
  try {
    for (let n = 0; n < N; n++) {
      if (EXP.cancelar) throw new Error('cancelado');
      const t = n / fps;
      const act = activos(t, L);
      const cuadros = new Map();
      for (const i of act) {
        const c = S.clips[i];
        let f = null;
        if (c.tipo === 'imagen') f = { src: c.bmp, w: c.w, h: c.h };
        else {
          const lc = lectores.get(i);
          if (lc.el) {
            await seekEl(c.el, c.in + (t - L.start[i]));
            f = { src: c.el, w: c.el.videoWidth, h: c.el.videoHeight };
          } else {
            const r = await lc.it.next();
            if (!r.done && r.value) lc.ultimo = r.value.canvas;
            f = lc.ultimo ? { src: lc.ultimo, w: lc.w, h: lc.h } : null;
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
      await vsrc.add(t, 1 / fps);
      if (asrc && t + segAudio > audioHasta && audioHasta < L.total) await pedazoAudio(Math.min(L.total, audioHasta + segAudio * 2));
      if (n % 5 === 0) {
        const pct = (n + 1) / N;
        const seg = (performance.now() - t0) / 1000;
        const falta = pct > 0.02 ? Math.round(seg / pct - seg) : null;
        poner(3 + pct * 95, `Exportando ${Math.round(pct * 100)}%` + (falta != null ? ` · faltan ${falta < 60 ? falta + ' s' : Math.round(falta / 60) + ' min'}` : ''));
      }
    }
    await pedazoAudio(L.total);
    poner(99, 'Terminando el archivo…');
    await out.finalize();
  } catch (e) {
    try { await out.cancel(); } catch (e2) { /* nada */ }
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
function guardar() {
  clearTimeout(tGuardar);
  tGuardar = setTimeout(() => {
    DB.put('estado', 'actual', {
      clips: S.clips.map(c => ({ id: c.id, fileId: c.fileId, nombre: c.nombre, tipo: c.tipo, in: c.in, out: c.out, dur: c.dur, mute: c.mute, trans: c.trans })),
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
    try { await prepararClip(c); S.clips.push(c); } catch (err) { /* archivo dañado */ }
  }
  cargandoMsg(null);
  if (!S.clips.length) { $('#cutVacio').hidden = false; return; }
  S.sel = S.clips[0].id;
  ajustarVista(); pintarLinea(); pintarPanel();
  if (S.belleza.on) activarBelleza();
}
$('#cutNuevo').addEventListener('click', async () => {
  if (S.clips.length && !(await preguntar('¿Empezar un video nuevo?', 'Se borra lo que tiene en la línea de tiempo.', 'Empezar de nuevo'))) return;
  pausar();
  S.clips.forEach(c => { if (c.el) { c.el.pause(); c.el.remove(); } });
  S.clips = []; S.subs = []; S.segs = []; S.sel = null; S.t = 0;
  await DB.limpiar();
  $('#cutVacio').hidden = false;
  pintarLinea(); pintarPanel();
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
abrirTab('editar');
requestAnimationFrame(bucle);
recuperar();
document.fonts.load('900 40px Montserrat');
document.fonts.load('italic 700 40px "Playfair Display"');
document.fonts.load('700 40px "Space Mono"');
window.__cut = S;
