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
    const op = (delegate) => ({ baseOptions: { modelAssetPath: MODELO, delegate }, runningMode: 'IMAGE', numFaces: 3 });
    try { detector = await v.FaceLandmarker.createFromOptions(fs, op('GPU')); }
    catch (e) { detector = await v.FaceLandmarker.createFromOptions(fs, op('CPU')); }
    const uniq = (con) => [...new Set(con.flatMap(c => [c.start, c.end]))];
    const F = v.FaceLandmarker;
    IDX = {
      ovalo: uniq(F.FACE_LANDMARKS_FACE_OVAL),
      quitar: [F.FACE_LANDMARKS_LEFT_EYE, F.FACE_LANDMARKS_RIGHT_EYE, F.FACE_LANDMARKS_LIPS, F.FACE_LANDMARKS_LEFT_EYEBROW, F.FACE_LANDMARKS_RIGHT_EYEBROW].map(uniq),
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

// Detecta los rostros en la fuente y dibuja la máscara (blanco = piel del rostro) en "lienzo"
export function mascara(fuente, lienzo) {
  if (!detector) return false;
  let r;
  try { r = detector.detect(fuente); } catch (e) { return false; }
  const S = 192;
  if (lienzo.width !== S) { lienzo.width = S; lienzo.height = S; }
  const ctx = lienzo.getContext('2d');
  ctx.filter = 'none';
  ctx.fillStyle = '#000'; ctx.fillRect(0, 0, S, S);
  const caras = r?.faceLandmarks || [];
  if (!caras.length) return true;
  ctx.filter = 'blur(4px)';
  for (const lm of caras) {
    const pt = (i) => [lm[i].x, lm[i].y];
    ctx.fillStyle = '#fff';
    poligono(ctx, envolvente(IDX.ovalo.map(pt)), S, 1.02);
    ctx.fillStyle = '#000';
    for (const g of IDX.quitar) poligono(ctx, envolvente(g.map(pt)), S, 1.25);
  }
  ctx.filter = 'none';
  return true;
}
