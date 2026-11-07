/* LocalProCut · trabajador de transcripción (Whisper en el navegador, sin servidor) */
// transformers.js 3.8.1 y ONNX Runtime alojados en LocalPro (no se ejecuta código de servidores externos).
// Los modelos de voz (solo datos) se descargan de Hugging Face la primera vez.
import { pipeline, env } from './vendor/tf/transformers.min.js';

env.allowLocalModels = false;
env.backends.onnx.wasm.wasmPaths = {
  mjs: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.js', import.meta.url).href,
  wasm: new URL('./vendor/tf/ort-wasm-simd-threaded.jsep.wasm', import.meta.url).href,
};

let asr = null;
let modeloActual = null;
const bytes = new Map();

self.onmessage = async (e) => {
  const m = e.data;
  if (m.tipo !== 'transcribir') return;
  try {
    if (!asr || modeloActual !== m.modelo) {
      bytes.clear();
      let ultimo = -1;
      asr = await pipeline('automatic-speech-recognition', m.modelo, {
        dtype: 'q4',
        device: 'auto',
        progress_callback: (p) => {
          if (!p.file) return;
          if (p.status === 'progress' && p.total) bytes.set(p.file, [p.loaded, p.total]);
          if (p.status === 'done' && bytes.has(p.file)) { const b = bytes.get(p.file); bytes.set(p.file, [b[1], b[1]]); }
          let a = 0, t = 0;
          for (const [l, tt] of bytes.values()) { a += l; t += tt; }
          const pct = t ? Math.floor(a / t * 100) : 0;
          if (pct !== ultimo) { ultimo = pct; self.postMessage({ tipo: 'descarga', pct }); }
        },
      });
      modeloActual = m.modelo;
    }
    self.postMessage({ tipo: 'escuchando' });
    const r = await asr(m.audio, {
      chunk_length_s: 30,
      stride_length_s: 5,
      language: m.idioma,
      task: 'transcribe',
      return_timestamps: true,
    });
    const res = Array.isArray(r) ? r[0] : r;
    const segmentos = (res.chunks || []).map(c => ({ text: c.text, start: c.timestamp?.[0] ?? 0, end: c.timestamp?.[1] ?? null }));
    self.postMessage({ tipo: 'listo', segmentos, texto: res.text });
  } catch (err) {
    self.postMessage({ tipo: 'error', msg: String(err?.message || err) });
  }
};
