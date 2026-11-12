/* LocalProCut · trabajador de "Eliminar fondo" automático (como CapCut), todo en el celular.
   Modelo BiRefNet lite (licencia MIT: se puede usar en productos comerciales). Recorta cualquier cosa:
   personas, productos, logos, mascotas. ONNX Runtime y transformers.js están alojados en LocalPro;
   el modelo (solo datos) se baja de Hugging Face la primera vez y queda guardado en el equipo.
   Modelos: "ort:<url>|<lado>" corre un .onnx directo con ONNX Runtime; "tjs:<id>|<dtype>" usa transformers.js. */
const POR_DEFECTO = 'ort:https://huggingface.co/CoderViking/birefnet-lite-onnx/resolve/main/birefnet-lite-1024.onnx|1024';
const MEDIA = [0.485, 0.456, 0.406], DESV = [0.229, 0.224, 0.225];

const cargados = new Map();
const avisar = (p, fase) => self.postMessage({ tipo: 'progreso', p, fase });

// Baja el modelo con barra de avance y lo deja guardado (la segunda vez abre al instante y sin datos)
async function bajar(url) {
  const cache = await caches.open('localprocut-modelos').catch(() => null);
  let r = cache && await cache.match(url);
  if (r) return new Uint8Array(await r.arrayBuffer());
  r = await fetch(url);
  if (!r.ok) throw new Error('No se pudo bajar el modelo (' + r.status + ')');
  const total = Number(r.headers.get('content-length')) || 0;
  const partes = []; let n = 0;
  const lector = r.body.getReader();
  for (;;) {
    const { done, value } = await lector.read();
    if (done) break;
    partes.push(value); n += value.length;
    if (total) avisar(n / total);
  }
  const datos = new Uint8Array(n); let o = 0;
  for (const p of partes) { datos.set(p, o); o += p.length; }
  try { await cache?.put(url, new Response(datos, { headers: { 'content-type': 'application/octet-stream' } })); } catch (e) { /* sin espacio: se baja cada vez */ }
  return datos;
}

async function cargarOrt(url, lado) {
  const ort = await import('./vendor/piper/ort.wasm.min.mjs');
  ort.env.wasm.wasmPaths = new URL('./vendor/piper/', import.meta.url).href;
  ort.env.wasm.numThreads = self.crossOriginIsolated ? Math.min(4, navigator.hardwareConcurrency || 2) : 1;
  const datos = await bajar(url);
  avisar(1, 'preparando');
  const sesion = await ort.InferenceSession.create(datos, { executionProviders: ['wasm'], graphOptimizationLevel: 'all' });
  return {
    async correr(img, w, h) {
      // Foto al tamaño del modelo, normalizada (ImageNet), en formato NCHW
      const cv = new OffscreenCanvas(lado, lado), x = cv.getContext('2d', { willReadFrequently: true });
      x.drawImage(await createImageBitmap(new ImageData(img, w, h)), 0, 0, lado, lado);
      const d = x.getImageData(0, 0, lado, lado).data, n = lado * lado, t = new Float32Array(3 * n);
      for (let i = 0; i < n; i++) for (let c = 0; c < 3; c++) t[c * n + i] = (d[i * 4 + c] / 255 - MEDIA[c]) / DESV[c];
      const ent = {}; ent[sesion.inputNames[0]] = new ort.Tensor('float32', t, [1, 3, lado, lado]);
      const sal = await sesion.run(ent);
      const o = sal[sesion.outputNames[sesion.outputNames.length - 1]].data;
      // Máscara (sigmoide) y de vuelta al tamaño de la foto
      const m = new ImageData(lado, lado);
      for (let i = 0; i < n; i++) { const v = 255 / (1 + Math.exp(-o[i])); m.data[i * 4] = m.data[i * 4 + 1] = m.data[i * 4 + 2] = v; m.data[i * 4 + 3] = 255; }
      const cm = new OffscreenCanvas(lado, lado); cm.getContext('2d').putImageData(m, 0, 0);
      const cg = new OffscreenCanvas(w, h), xg = cg.getContext('2d', { willReadFrequently: true });
      xg.imageSmoothingQuality = 'high'; xg.drawImage(cm, 0, 0, w, h);
      const g = xg.getImageData(0, 0, w, h).data, alfa = new Uint8ClampedArray(w * h);
      for (let i = 0; i < w * h; i++) alfa[i] = g[i * 4];
      return alfa;
    },
  };
}

async function cargarTjs(id, dtype) {
  const { AutoModel, AutoProcessor, RawImage, env } = await import('./vendor/tf/transformers.min.js');
  env.allowLocalModels = false;
  env.backends.onnx.wasm.wasmPaths = {
    mjs: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.js', import.meta.url).href,
    wasm: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.wasm', import.meta.url).href,
  };
  const bytes = new Map();
  const progress_callback = (p) => {
    if (!p.file || p.status !== 'progress' || !p.total) return;
    bytes.set(p.file, [p.loaded, p.total]);
    let a = 0, t = 0; for (const [x, y] of bytes.values()) { a += x; t += y; }
    if (t) avisar(a / t);
  };
  const modelo = await AutoModel.from_pretrained(id, { device: 'wasm', dtype, progress_callback });
  const proc = await AutoProcessor.from_pretrained(id);
  return {
    async correr(img, w, h) {
      const { pixel_values } = await proc(new RawImage(img, w, h, 4).rgb());
      const { output_image } = await modelo({ input_image: pixel_values });
      const m = await RawImage.fromTensor(output_image[0].sigmoid().mul(255).to('uint8')).resize(w, h);
      return m.data;
    },
  };
}

function cargar(nombre) {
  if (!cargados.has(nombre)) {
    const [tipo, resto] = [nombre.slice(0, 3), nombre.slice(4)];
    const [a, b] = resto.split('|');
    const p = (tipo === 'ort' ? cargarOrt(a, Number(b) || 1024) : cargarTjs(a, b || 'fp32'));
    p.catch(() => cargados.delete(nombre));
    cargados.set(nombre, p);
  }
  return cargados.get(nombre);
}

self.onmessage = async (e) => {
  const m = e.data;
  if (m.tipo !== 'recortar') return;
  try {
    const mod = await cargar(m.modelo || POR_DEFECTO);
    avisar(1, 'recortando');
    const t0 = performance.now();
    const alfa = await mod.correr(m.imagen.data, m.imagen.width, m.imagen.height);
    self.postMessage({ tipo: 'listo', id: m.id, alfa, width: m.imagen.width, height: m.imagen.height, ms: Math.round(performance.now() - t0) }, [alfa.buffer]);
  } catch (err) {
    // ONNX Runtime a veces lanza un número (falta de memoria del navegador)
    const msg = typeof err === 'number' ? 'memoria insuficiente (' + err + ')' : String(err?.message || err);
    self.postMessage({ tipo: 'error', id: m.id, mensaje: msg });
  }
};
