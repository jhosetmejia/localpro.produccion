/* LocalProCut · detector de rostros (MediaPipe Face Landmarker) y máscara de piel del rostro */
const MP = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21';
const MODELO = 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task';

let detector = null;
let cargando = null;
let IDX = null;

export function listo() { return !!detector; }

export function cargar() {
  if (cargando) return cargando;
  cargando = (async () => {
    const v = await import(`${MP}/vision_bundle.mjs`);
    const fs = await v.FilesetResolver.forVisionTasks(`${MP}/wasm`);
    const op = (delegate) => ({ baseOptions: { modelAssetPath: MODELO, delegate }, runningMode: 'VIDEO', numFaces: 3, minTrackingConfidence: 0.4 });
    try { detector = await v.FaceLandmarker.createFromOptions(fs, op('GPU')); }
    catch (e) { detector = await v.FaceLandmarker.createFromOptions(fs, op('CPU')); }
    const uniq = (con) => [...new Set(con.flatMap(c => [c.start, c.end]))];
    const F = v.FaceLandmarker;
    IDX = {
      ovalo: uniq(F.FACE_LANDMARKS_FACE_OVAL),
      // Ojos + cejas de cada lado juntos (así queda protegida toda la zona de las gafas) y la boca
      ojos: [uniq([...F.FACE_LANDMARKS_LEFT_EYE, ...F.FACE_LANDMARKS_LEFT_EYEBROW]), uniq([...F.FACE_LANDMARKS_RIGHT_EYE, ...F.FACE_LANDMARKS_RIGHT_EYEBROW])],
      boca: uniq(F.FACE_LANDMARKS_LIPS),
    };
    return true;
  })().catch((e) => { cargando = null; throw e; });
  return cargando;
}

function envolvente(pts) {
  const p = pts.slice().sort((a, b) => a[0] - b[0] || a[1] - b[1]);
  const cruz = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
  const abajo = [], arriba = [];
  for (const q of p) { while (abajo.length >= 2 && cruz(abajo[abajo.length - 2], abajo[abajo.length - 1], q) <= 0) abajo.pop(); abajo.push(q); }
  for (let i = p.length - 1; i >= 0; i--) { const q = p[i]; while (arriba.length >= 2 && cruz(arriba[arriba.length - 2], arriba[arriba.length - 1], q) <= 0) arriba.pop(); arriba.push(q); }
  return abajo.slice(0, -1).concat(arriba.slice(0, -1));
}

function poligono(ctx, pts, S, crecer) {
  const cx = pts.reduce((a, q) => a + q[0], 0) / pts.length, cy = pts.reduce((a, q) => a + q[1], 0) / pts.length;
  ctx.beginPath();
  pts.forEach((q, i) => {
    const x = (cx + (q[0] - cx) * crecer) * S, y = (cy + (q[1] - cy) * crecer) * S;
    i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
  });
  ctx.closePath();
  ctx.fill();
}

// Copia reducida del cuadro para detectar rápido (los puntos salen en proporción 0..1, sirven igual)
const chico = document.createElement('canvas');
// Puntos de piel limpia: mejillas, frente y mentón
const PIEL = [50, 280, 205, 425, 117, 346, 151, 108, 337, 199];
let ts = 0;
export let ultimoMs = 0;
function reducir(fuente) {
  const w = fuente.videoWidth || fuente.width, h = fuente.videoHeight || fuente.height;
  if (!w || !h) return null;
  const k = Math.min(1, 384 / Math.max(w, h));
  const cw = Math.max(2, Math.round(w * k)), ch = Math.max(2, Math.round(h * k));
  if (chico.width !== cw || chico.height !== ch) { chico.width = cw; chico.height = ch; }
  chico.getContext('2d').drawImage(fuente, 0, 0, cw, ch);
  return chico;
}
// Detecta los rostros en la fuente (modo video: sigue la cara cuadro a cuadro) y dibuja la máscara en "lienzo"
export function mascara(fuente, lienzo) {
  if (!detector) return false;
  let r;
  const t0 = performance.now();
  try {
    const img = reducir(fuente);
    if (!img) return false;
    ts = Math.max(ts + 1, Math.round(t0));
    r = detector.detectForVideo(img, ts);
  } catch (e) { return false; }
  ultimoMs = ultimoMs * 0.8 + (performance.now() - t0) * 0.2;
  const S = 192;
  if (lienzo.width !== S) { lienzo.width = S; lienzo.height = S; }
  const ctx = lienzo.getContext('2d');
  ctx.filter = 'none';
  ctx.fillStyle = '#000'; ctx.fillRect(0, 0, S, S);
  const caras = r?.faceLandmarks || [];
  if (!caras.length) return true;
  ctx.filter = 'blur(3px)';
  for (const lm of caras) {
    const pt = (i) => [lm[i].x, lm[i].y];
    ctx.fillStyle = '#fff';
    poligono(ctx, envolvente(IDX.ovalo.map(pt)), S, 0.97);
    ctx.fillStyle = '#000';
    for (const g of IDX.ojos) poligono(ctx, envolvente(g.map(pt)), S, 1.45);
    poligono(ctx, envolvente(IDX.boca.map(pt)), S, 1.2);
  }
  ctx.filter = 'none';
  // Tono de piel de la persona: promedio de mejillas, frente y mentón (se suaviza entre cuadros)
  try {
    const lm = caras[0], W = chico.width, H = chico.height;
    const d = chico.getContext('2d', { willReadFrequently: true }).getImageData(0, 0, W, H).data;
    let r = 0, g = 0, b = 0, n = 0;
    for (const i of PIEL) {
      const cx = Math.round(lm[i].x * W), cy = Math.round(lm[i].y * H);
      for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) {
        const x = cx + dx, y = cy + dy;
        if (x < 0 || y < 0 || x >= W || y >= H) continue;
        const o = (y * W + x) * 4; r += d[o]; g += d[o + 1]; b += d[o + 2]; n++;
      }
    }
    if (n) {
      const t = [r / n / 255, g / n / 255, b / n / 255];
      const prev = lienzo._tono;
      lienzo._tono = prev ? prev.map((v, k) => v * 0.7 + t[k] * 0.3) : t;
    }
  } catch (e) { /* sin tono: se suaviza igual */ }
  return true;
}
