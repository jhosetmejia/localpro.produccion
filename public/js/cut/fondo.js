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
  let alguien = 0;
  for (let py = 0; py < h; py++) {
    const my = Math.min(mh - 1, Math.floor(py * mh / h));
    for (let px = 0; px < w; px++) {
      const a = conf[my * mw + Math.min(mw - 1, Math.floor(px * mw / w))];
      const al = Math.max(0, Math.min(1, (a - 0.35) / 0.3)); // corte suave
      if (al > 0.5) alguien++;
      d[(py * w + px) * 4 + 3] = Math.round(d[(py * w + px) * 4 + 3] * al);
    }
  }
  res.close?.();
  if (alguien < w * h * 0.01) throw new Error('No se encontró una persona en la foto');
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
