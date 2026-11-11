/* LocalProCut · trabajador de texto a voz (Piper en el navegador, sin servidor)
   El texto se pasa a fonemas (espeak-ng) y una red neuronal (ONNX) genera la voz. Todo corre en el celular.
   El código está alojado en LocalPro (vendor/piper); las voces (solo datos) se bajan de Hugging Face la primera vez
   y quedan guardadas en el equipo. Licencias en vendor/piper/LEEME.txt */
import { createPiperPhonemize } from './vendor/piper/piper_phonemize.mjs';
import * as ort from './vendor/piper/ort.wasm.min.mjs';

const base = (f) => new URL('./vendor/piper/' + f, import.meta.url).href;
ort.env.wasm.numThreads = 1;
ort.env.wasm.wasmPaths = { mjs: base('ort-wasm-simd-threaded.mjs'), wasm: base('ort-wasm-simd-threaded.wasm') };

const HF = 'https://huggingface.co/rhasspy/piper-voices/resolve/main/';
const CACHE = 'lpcut-voces-v1';
let sesion = null, vozActual = null, config = null;
let fonemasWasm = null, fonemasData = null;

async function bajar(url, avance) {
  let cache = null;
  try { cache = await caches.open(CACHE); const hit = await cache.match(url); if (hit) return await hit.arrayBuffer(); } catch (e) { cache = null; }
  const r = await fetch(url);
  if (!r.ok) throw new Error('No se pudo bajar la voz (' + r.status + ')');
  const total = Number(r.headers.get('content-length')) || 0;
  let buf;
  if (r.body && total && avance) {
    const lector = r.body.getReader(); const partes = []; let llevo = 0, ultimo = -1;
    for (;;) {
      const { done, value } = await lector.read(); if (done) break;
      partes.push(value); llevo += value.length;
      const pct = Math.floor(llevo / total * 100); if (pct !== ultimo) { ultimo = pct; avance(pct); }
    }
    const u = new Uint8Array(llevo); let o = 0; for (const p of partes) { u.set(p, o); o += p.length; }
    buf = u.buffer;
  } else buf = await r.arrayBuffer();
  try { if (cache) await cache.put(url, new Response(buf.slice(0), { headers: { 'content-type': 'application/octet-stream' } })); } catch (e) { /* sin espacio: se baja otra vez la próxima */ }
  return buf;
}

async function preparar(voz) {
  if (sesion && vozActual === voz.id) return;
  const ruta = HF + voz.ruta;
  config = JSON.parse(new TextDecoder().decode(await bajar(ruta + '.json')));
  const modelo = await bajar(ruta, (pct) => self.postMessage({ tipo: 'descarga', pct }));
  self.postMessage({ tipo: 'cargando' });
  if (sesion) { try { await sesion.release(); } catch (e) { /* nada */ } }
  sesion = await ort.InferenceSession.create(new Uint8Array(modelo), { executionProviders: ['wasm'] });
  vozActual = voz.id;
}

async function fonemas(texto) {
  if (!fonemasWasm) {
    const [w, d] = await Promise.all([fetch(base('piper_phonemize.wasm')), fetch(base('piper_phonemize.data'))]);
    fonemasWasm = await w.arrayBuffer(); fonemasData = await d.arrayBuffer();
  }
  return new Promise((ok, mal) => {
    let listo = false;
    createPiperPhonemize({
      wasmBinary: fonemasWasm,
      getPreloadedPackage: () => fonemasData.slice(0),
      locateFile: (u) => base(u.split('/').pop()),
      print: (linea) => { if (listo) return; try { const j = JSON.parse(linea); listo = true; ok(j.phoneme_ids); } catch (e) { /* otra salida */ } },
      printErr: (m) => { if (!listo && /error/i.test(m)) { listo = true; mal(new Error(m)); } },
    }).then((mod) => {
      mod.callMain(['-l', config.espeak.voice, '--input', JSON.stringify([{ text: texto }]), '--espeak_data', '/espeak-ng-data']);
      setTimeout(() => { if (!listo) mal(new Error('No se pudo leer el texto')); }, 15000);
    }).catch(mal);
  });
}

// Pedazos de máximo ~300 letras, cortando en puntos o comas
function pedazos(t) {
  const frases = t.replace(/\s+/g, ' ').trim().match(/[^.!?¡¿…]+[.!?…]*|[¡¿][^.!?]*[.!?]*/g) || [t];
  const out = []; let cur = '';
  for (const f of frases) {
    if ((cur + ' ' + f).trim().length > 300 && cur) { out.push(cur.trim()); cur = ''; }
    if (f.length > 300) { for (const p of f.split(/(?<=,)\s*/)) { if ((cur + ' ' + p).length > 300 && cur) { out.push(cur.trim()); cur = ''; } cur += ' ' + p; } } else cur += ' ' + f;
  }
  if (cur.trim()) out.push(cur.trim());
  return out;
}

self.onmessage = async (e) => {
  const m = e.data;
  if (m.tipo !== 'hablar') return;
  try {
    await preparar(m.voz);
    const sr = config.audio.sample_rate, inf = config.inference || {};
    const partes = pedazos(m.texto), audios = [];
    const silencio = new Float32Array(Math.round(sr * 0.22));
    for (let i = 0; i < partes.length; i++) {
      self.postMessage({ tipo: 'hablando', i: i + 1, n: partes.length });
      const ids = await fonemas(partes[i]);
      const feeds = {
        input: new ort.Tensor('int64', BigInt64Array.from(ids.map(BigInt)), [1, ids.length]),
        input_lengths: new ort.Tensor('int64', BigInt64Array.from([BigInt(ids.length)]), [1]),
        scales: new ort.Tensor('float32', Float32Array.from([inf.noise_scale ?? 0.667, (inf.length_scale ?? 1) * (m.lento || 1), inf.noise_w ?? 0.8]), [3]),
      };
      if (Object.keys(config.speaker_id_map || {}).length) feeds.sid = new ort.Tensor('int64', BigInt64Array.from([0n]), [1]);
      const r = await sesion.run(feeds);
      audios.push((r.output || Object.values(r)[0]).data);
      if (i < partes.length - 1) audios.push(silencio);
    }
    const largo = audios.reduce((a, b) => a + b.length, 0), pcm = new Float32Array(largo);
    let o = 0; for (const a of audios) { pcm.set(a, o); o += a.length; }
    self.postMessage({ tipo: 'listo', pcm, sr }, [pcm.buffer]);
  } catch (err) {
    self.postMessage({ tipo: 'error', mensaje: String(err?.message || err) });
  }
};
