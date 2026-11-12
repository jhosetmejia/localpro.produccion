/* LocalProCut · trabajador de "Eliminar fondo" automático (como CapCut), todo en el celular.
   Modelo BiRefNet lite (licencia MIT: se puede usar en productos comerciales). Recorta cualquier cosa:
   personas, productos, logos, mascotas. transformers.js y ONNX Runtime están alojados en LocalPro;
   el modelo (solo datos, ~115 MB) se baja de Hugging Face la primera vez y queda guardado en el equipo. */
import { AutoModel, AutoProcessor, RawImage, env } from './vendor/tf/transformers.min.js';

env.allowLocalModels = false;
env.backends.onnx.wasm.wasmPaths = {
  mjs: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.js', import.meta.url).href,
  wasm: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.wasm', import.meta.url).href,
};

const MODELO = 'onnx-community/BiRefNet_lite-ONNX';
let modelo = null, proc = null, cargando = null;

async function gpu() {
  try { return !!(navigator.gpu && await navigator.gpu.requestAdapter()); } catch (e) { return false; }
}
function cargar() {
  if (cargando) return cargando;
  cargando = (async () => {
    const bytes = new Map();
    const avance = (p) => {
      if (!p.file) return;
      if (p.status === 'progress' && p.total) bytes.set(p.file, [p.loaded, p.total]);
      let a = 0, t = 0; for (const [x, y] of bytes.values()) { a += x; t += y; }
      if (t) self.postMessage({ tipo: 'progreso', p: a / t });
    };
    const conGpu = await gpu();
    // Con tarjeta gráfica (WebGPU) va en fp16, más rápido; sin ella, en el procesador (WASM)
    const intentos = conGpu ? [{ device: 'webgpu', dtype: 'fp16' }, { device: 'wasm', dtype: 'fp32' }] : [{ device: 'wasm', dtype: 'fp16' }, { device: 'wasm', dtype: 'fp32' }];
    let ultimo;
    for (const op of intentos) {
      try {
        modelo = await AutoModel.from_pretrained(MODELO, { ...op, progress_callback: avance });
        proc = await AutoProcessor.from_pretrained(MODELO);
        return op;
      } catch (e) { ultimo = e; modelo = null; }
    }
    throw ultimo || new Error('No se pudo cargar el modelo');
  })().catch((e) => { cargando = null; throw e; });
  return cargando;
}

self.onmessage = async (e) => {
  const m = e.data;
  if (m.tipo !== 'recortar') return;
  try {
    await cargar();
    self.postMessage({ tipo: 'progreso', p: 1, fase: 'recortando' });
    const { data, width, height } = m.imagen; // ImageData (RGBA)
    const img = new RawImage(data, width, height, 4).rgb();
    const { pixel_values } = await proc(img);
    const { output_image } = await modelo({ input_image: pixel_values });
    const masc = await RawImage.fromTensor(output_image[0].sigmoid().mul(255).to('uint8')).resize(width, height);
    self.postMessage({ tipo: 'listo', id: m.id, alfa: masc.data, width, height }, [masc.data.buffer]);
  } catch (err) {
    self.postMessage({ tipo: 'error', id: m.id, mensaje: String(err?.message || err) });
  }
};
