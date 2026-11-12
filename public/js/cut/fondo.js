/* LocalProCut · fotos encima del video (capas): eliminar fondo y máscaras, como CapCut. Todo en el celular. */
// MediaPipe alojado en LocalPro (igual que belleza.js); el modelo de personas es solo datos
const MP = new URL('./vendor/mediapipe', import.meta.url).href;
const MODELO_PERSONA = 'https://storage.googleapis.com/mediapipe-models/image_segmenter/selfie_segmenter/float16/latest/selfie_segmenter.tflite';
const LADO = 1600; // las fotos se trabajan hasta este tamaño (suficiente para una capa y rápido en el celular)

function lienzo(w, h) { const c = document.createElement('canvas'); c.width = w; c.height = h; return c; }
function copiaReducida(bmp) {
  const k = Math.min(1, LADO / Math.max(bmp.width, bmp.height));
  const c = lienzo(Math.max(1, Math.round(bmp.width * k)), Math.max(1, Math.round(bmp.height * k)));
  c.getContext('2d', { willReadFrequently: true }).drawImage(bmp, 0, 0, c.width, c.height);
  return c;
}

/* Fondo liso (logos, productos en fondo blanco o de un color): toma el color de los bordes y borra todo lo que se
   parezca y esté conectado con el borde. Lo de adentro del logo del mismo color NO se borra (no toca el borde). */
export function quitarLiso(bmp, tolerancia = 0.5) {
  const c = copiaReducida(bmp), w = c.width, h = c.height;
  const x = c.getContext('2d', { willReadFrequently: true });
  const img = x.getImageData(0, 0, w, h), d = img.data;
  // Color del fondo: el más repetido en el borde (agrupado de a 16 tonos)
  const votos = new Map();
  const votar = (i) => { const k = (d[i] >> 4) << 8 | (d[i + 1] >> 4) << 4 | (d[i + 2] >> 4); votos.set(k, (votos.get(k) || 0) + 1); };
  for (let px = 0; px < w; px++) { votar(px * 4); votar(((h - 1) * w + px) * 4); }
  for (let py = 0; py < h; py++) { votar(py * w * 4); votar((py * w + w - 1) * 4); }
  let mejor = 0, n = -1; for (const [k, v] of votos) if (v > n) { n = v; mejor = k; }
  // Promedio real de los puntos del borde que caen en ese grupo
  let r = 0, g = 0, b = 0, m = 0;
  const sumar = (i) => { const k = (d[i] >> 4) << 8 | (d[i + 1] >> 4) << 4 | (d[i + 2] >> 4); if (k === mejor) { r += d[i]; g += d[i + 1]; b += d[i + 2]; m++; } };
  for (let px = 0; px < w; px++) { sumar(px * 4); sumar(((h - 1) * w + px) * 4); }
  for (let py = 0; py < h; py++) { sumar(py * w * 4); sumar((py * w + w - 1) * 4); }
  r /= m; g /= m; b /= m;
  const lim = 18 + tolerancia * 110;             // distancia de color que cuenta como fondo
  const borde = lim * 1.6;                      // zona de transición: borde suave sin halo
  const dist = (i) => { const dr = d[i] - r, dg = d[i + 1] - g, db = d[i + 2] - b; return Math.sqrt(dr * dr * 0.3 + dg * dg * 0.59 + db * db * 0.11) * 1.7; };
  // Relleno desde los bordes
  const visto = new Uint8Array(w * h), cola = new Int32Array(w * h);
  let ini = 0, fin = 0;
  const meter = (p) => { if (visto[p]) return; visto[p] = 1; if (dist(p * 4) <= lim) cola[fin++] = p; else visto[p] = 2; };
  for (let px = 0; px < w; px++) { meter(px); meter((h - 1) * w + px); }
  for (let py = 0; py < h; py++) { meter(py * w); meter(py * w + w - 1); }
  while (ini < fin) {
    const p = cola[ini++], px = p % w, py = (p - px) / w;
    d[p * 4 + 3] = 0;
    if (px > 0) meter(p - 1); if (px < w - 1) meter(p + 1);
    if (py > 0) meter(p - w); if (py < h - 1) meter(p + w);
  }
  // Borde suave: los puntos pegados a lo borrado se vuelven semitransparentes según qué tanto se parecen al fondo
  for (let p = 0; p < w * h; p++) {
    if (visto[p] !== 2) continue;
    const dd = dist(p * 4);
    if (dd < borde) d[p * 4 + 3] = Math.round(255 * Math.min(1, (dd - lim) / (borde - lim)));
  }
  x.putImageData(img, 0, 0);
  return c;
}

/* Filtro guiado: ajusta los bordes de la máscara a los bordes reales de la foto (pelo, dedos, contornos),
   así el recorte no queda "mordido" ni con escalones aunque la IA lo haya calculado más pequeño. */
function cajas(src, w, h, r) {
  const out = new Float32Array(w * h), integ = new Float64Array((w + 1) * (h + 1));
  for (let y = 0; y < h; y++) { let fila = 0; for (let x = 0; x < w; x++) { fila += src[y * w + x]; integ[(y + 1) * (w + 1) + x + 1] = integ[y * (w + 1) + x + 1] + fila; } }
  for (let y = 0; y < h; y++) {
    const y0 = Math.max(0, y - r), y1 = Math.min(h, y + r + 1);
    for (let x = 0; x < w; x++) {
      const x0 = Math.max(0, x - r), x1 = Math.min(w, x + r + 1);
      out[y * w + x] = (integ[y1 * (w + 1) + x1] - integ[y0 * (w + 1) + x1] - integ[y1 * (w + 1) + x0] + integ[y0 * (w + 1) + x0]) / ((x1 - x0) * (y1 - y0));
    }
  }
  return out;
}
export function refinarBordes(d, alfa, w, h, r = 3, eps = 1e-4) {
  const n = w * h, I = new Float32Array(n), P = new Float32Array(n), II = new Float32Array(n), IP = new Float32Array(n);
  for (let i = 0; i < n; i++) { const g = (d[i * 4] * 0.299 + d[i * 4 + 1] * 0.587 + d[i * 4 + 2] * 0.114) / 255, p = alfa[i] / 255; I[i] = g; P[i] = p; II[i] = g * g; IP[i] = g * p; }
  const mI = cajas(I, w, h, r), mP = cajas(P, w, h, r), mII = cajas(II, w, h, r), mIP = cajas(IP, w, h, r);
  const A = new Float32Array(n), B = new Float32Array(n);
  for (let i = 0; i < n; i++) { const v = mII[i] - mI[i] * mI[i], c = mIP[i] - mI[i] * mP[i]; A[i] = c / (v + eps); B[i] = mP[i] - A[i] * mI[i]; }
  const mA = cajas(A, w, h, r), mB = cajas(B, w, h, r);
  const out = new Uint8ClampedArray(n);
  for (let i = 0; i < n; i++) {
    let q = mA[i] * I[i] + mB[i];
    q = (q - 0.04) / 0.92; // lo casi transparente se vuelve transparente y lo casi opaco, opaco (sin velo)
    out[i] = Math.round(Math.max(0, Math.min(1, q)) * 255);
  }
  return out;
}
// Quita el "halo" del fondo en el borde: en los puntos semitransparentes el color se acerca al de adentro
function limpiarHalo(d, w, h) {
  for (let pas = 0; pas < 2; pas++) {
    for (let y = 1; y < h - 1; y++) for (let x = 1; x < w - 1; x++) {
      const i = (y * w + x) * 4, a = d[i + 3];
      if (a === 0 || a > 230) continue;
      let r = 0, g = 0, b = 0, n = 0;
      for (const k of [-4, 4, -w * 4, w * 4]) { const j = i + k; if (d[j + 3] > 230) { r += d[j]; g += d[j + 1]; b += d[j + 2]; n++; } }
      if (n) { const t = 1 - a / 255; d[i] = d[i] * (1 - t) + r / n * t; d[i + 1] = d[i + 1] * (1 - t) + g / n * t; d[i + 2] = d[i + 2] * (1 - t) + b / n * t; }
    }
  }
}

/* Automático (como CapCut): la IA BiRefNet recorta lo principal de la foto, sea lo que sea */
let trab = null, seq = 0;
const pendientes = new Map();
function trabajador() {
  if (trab) return trab;
  trab = new Worker(new URL('./fondo-ia.js', import.meta.url), { type: 'module' });
  trab.onmessage = (e) => {
    const m = e.data;
    if (m.tipo === 'progreso') { for (const p of pendientes.values()) p.progreso?.(m.p, m.fase); return; }
    const p = pendientes.get(m.id); if (!p) return;
    pendientes.delete(m.id);
    if (m.tipo === 'listo') p.ok(m); else p.mal(new Error(m.mensaje));
  };
  trab.onerror = (e) => { for (const p of pendientes.values()) p.mal(new Error(e.message || 'falló la IA')); pendientes.clear(); trab = null; };
  return trab;
}
export async function quitarAuto(bmp, progreso) {
  const c = copiaReducida(bmp), w = c.width, h = c.height;
  const x = c.getContext('2d', { willReadFrequently: true });
  const img = x.getImageData(0, 0, w, h);
  const id = ++seq;
  const r = await new Promise((ok, mal) => {
    pendientes.set(id, { ok, mal, progreso });
    trabajador().postMessage({ tipo: 'recortar', id, modelo: globalThis.__fondoModelo, imagen: { data: img.data, width: w, height: h } });
  });
  globalThis.__fondoMs = r.ms;
  const alfa = refinarBordes(img.data, r.alfa, w, h, Math.max(2, Math.round(Math.min(w, h) / 400)));
  let algo = 0; for (let i = 0; i < alfa.length; i++) if (alfa[i] > 128) algo++;
  if (algo < alfa.length * 0.005) throw new Error('No encontré qué recortar en la foto');
  const d = img.data;
  for (let i = 0; i < alfa.length; i++) d[i * 4 + 3] = Math.round(d[i * 4 + 3] * alfa[i] / 255);
  limpiarHalo(d, w, h);
  x.putImageData(img, 0, 0);
  return c;
}

/* Persona (IA): recorta a la gente de la foto con el segmentador de MediaPipe */
let segmentador = null, cargando = null;
function cargarPersona() {
  if (cargando) return cargando;
  cargando = (async () => {
    const v = await import(`${MP}/vision_bundle.js`);
    const fs = await v.FilesetResolver.forVisionTasks(`${MP}/wasm`);
    const op = (delegate) => ({ baseOptions: { modelAssetPath: MODELO_PERSONA, delegate }, runningMode: 'IMAGE', outputConfidenceMasks: true, outputCategoryMask: false });
    try { segmentador = await v.ImageSegmenter.createFromOptions(fs, op('GPU')); }
    catch (e) { segmentador = await v.ImageSegmenter.createFromOptions(fs, op('CPU')); }
  })().catch((e) => { cargando = null; throw e; });
  return cargando;
}
export async function quitarPersona(bmp) {
  await cargarPersona();
  const c = copiaReducida(bmp), w = c.width, h = c.height;
  const x = c.getContext('2d', { willReadFrequently: true });
  const res = segmentador.segment(c);
  const masc = res.confidenceMasks?.[0];
  if (!masc) throw new Error('No se encontró una persona en la foto');
  const conf = masc.getAsFloat32Array(), mw = masc.width, mh = masc.height;
  const img = x.getImageData(0, 0, w, h), d = img.data;
  // La máscara de la IA es pequeña (256 px): se agranda suave y se ajusta a los bordes reales de la foto
  const bruta = new Uint8ClampedArray(w * h);
  for (let py = 0; py < h; py++) {
    const fy = Math.min(mh - 1.001, py * mh / h), y0 = Math.floor(fy), ty = fy - y0;
    for (let px = 0; px < w; px++) {
      const fx = Math.min(mw - 1.001, px * mw / w), x0 = Math.floor(fx), tx = fx - x0, k = y0 * mw + x0;
      const a = (conf[k] * (1 - tx) + conf[k + 1] * tx) * (1 - ty) + (conf[k + mw] * (1 - tx) + conf[k + mw + 1] * tx) * ty;
      bruta[py * w + px] = Math.round(Math.max(0, Math.min(1, (a - 0.3) / 0.4)) * 255);
    }
  }
  res.close?.();
  const alfa = refinarBordes(d, bruta, w, h, Math.max(4, Math.round(Math.min(w, h) / 120)), 1e-3);
  let alguien = 0;
  for (let i = 0; i < alfa.length; i++) { if (alfa[i] > 128) alguien++; d[i * 4 + 3] = Math.round(d[i * 4 + 3] * alfa[i] / 255); }
  if (alguien < w * h * 0.01) throw new Error('No se encontró una persona en la foto');
  limpiarHalo(d, w, h);
  x.putImageData(img, 0, 0);
  return c;
}

/* Máscaras: dejan ver solo una forma de la foto (o todo menos la forma si se invierte), con borde difuminado */
export const FORMAS = [
  ['', 'Ninguna', '⦸'], ['circulo', 'Círculo', '⚪'], ['rect', 'Rectángulo', '▢'], ['corazon', 'Corazón', '♡'],
  ['estrella', 'Estrella', '☆'], ['lineal', 'Lineal', '▤'], ['espejo', 'Espejo', '▥'],
];
function trazar(x, forma, w, h, t) {
  // t = tamaño (0.2–1.2) de la forma respecto a la foto
  const cx = w / 2, cy = h / 2, s = Math.min(w, h) * t / 2;
  x.beginPath();
  if (forma === 'circulo') { x.ellipse(cx, cy, w * t / 2, h * t / 2, 0, 0, Math.PI * 2); }
  else if (forma === 'rect') { const rw = w * t, rh = h * t, rr = Math.min(rw, rh) * 0.12; x.roundRect(cx - rw / 2, cy - rh / 2, rw, rh, rr); }
  else if (forma === 'corazon') {
    x.moveTo(cx, cy + s * 0.95);
    x.bezierCurveTo(cx - s * 1.6, cy - s * 0.1, cx - s * 0.75, cy - s * 1.25, cx, cy - s * 0.45);
    x.bezierCurveTo(cx + s * 0.75, cy - s * 1.25, cx + s * 1.6, cy - s * 0.1, cx, cy + s * 0.95);
  } else if (forma === 'estrella') {
    for (let i = 0; i < 10; i++) { const r = i % 2 ? s * 0.45 : s, a = -Math.PI / 2 + i * Math.PI / 5; x.lineTo(cx + r * Math.cos(a), cy + r * Math.sin(a)); }
  } else if (forma === 'lineal') { x.rect(0, 0, w, h * (0.5 + (t - 0.7) * 0.8)); }       // de arriba hasta la línea
  else if (forma === 'espejo') { const bh = h * t * 0.6; x.rect(0, cy - bh / 2, w, bh); }   // franja del centro
  x.closePath();
}
export function aplicarMascara(fuente, m) {
  const w = fuente.width, h = fuente.height;
  const out = lienzo(w, h), x = out.getContext('2d');
  // La forma (con difuminado) en un lienzo aparte
  const forma = lienzo(w, h), f = forma.getContext('2d');
  const blur = Math.round((m.suave || 0) * Math.min(w, h) * 0.15);
  if (blur > 0) f.filter = `blur(${blur}px)`;
  f.fillStyle = '#000'; trazar(f, m.forma, w, h, m.tam ?? 0.8); f.fill();
  x.drawImage(fuente, 0, 0);
  x.globalCompositeOperation = m.inv ? 'destination-out' : 'destination-in';
  x.drawImage(forma, 0, 0);
  return out;
}
