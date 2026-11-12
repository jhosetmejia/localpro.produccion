// LocalProCut en el celular: subir videos y una foto, reproducir, mover la línea, salir a otra app y volver,
// agregar un mp4 que solo tiene sonido y exportar el video final. También el arranque sin WebGL2 (iPhone viejo).
const path = require('path');
const { test, expect } = require('../ayuda');

const media = (f) => path.join(__dirname, '..', 'media', f);
const VIDEOS = ['clip1.mp4', 'clip2.mp4', 'clip3.mp4'].map(media);

// ¿Qué tan clara está la vista previa? (0 = negra)
const luzVista = (page) => page.evaluate(() => {
  const v = document.getElementById('cutLienzo');
  const c = Object.assign(document.createElement('canvas'), { width: 16, height: 16 });
  const x = c.getContext('2d'); x.drawImage(v, 0, 0, 16, 16);
  const d = x.getImageData(0, 0, 16, 16).data; let s = 0;
  for (let i = 0; i < d.length; i += 4) s += d[i] + d[i + 1] + d[i + 2];
  return s / (d.length / 4);
});
const abrirEditor = async (page) => {
  await page.goto('/cut');
  await page.waitForFunction(() => window.__cut && window.__motor, null, { timeout: 30_000 });
};
const estado = (page) => page.evaluate(() => JSON.stringify({
  clips: window.__cut.clips.map((c) => ({ tipo: c.tipo, nombre: c.nombre, el: !!c.el, rs: c.el?.readyState, dormido: !!c.el?._dormido, err: c.el?.error?.code || null })),
  cargando: document.getElementById('cutCargando')?.hidden === false ? document.querySelector('#cutCargando span')?.textContent : null,
  avisos: [...document.querySelectorAll('#cutToasts *')].map((x) => x.textContent).filter(Boolean).slice(-3),
}));
const subir = async (page, archivos, total) => {
  await page.setInputFiles('#cutArchivo', archivos);
  try {
    await page.waitForFunction((n) => window.__cut.clips.length >= n && !window.__cut.clips.some((c) => c.tipo === 'video' && !c.el), total, { timeout: 90_000 });
  } catch (e) { throw new Error('Los archivos no terminaron de cargar en el editor: ' + await estado(page)); }
};
const irA = (page, t) => page.evaluate((t) => { const l = document.getElementById('cutLinea'); l.scrollLeft = t * window.__cut.pps; l.dispatchEvent(new Event('scroll')); }, t);

test.describe('LocalProCut', () => {
  test.setTimeout(5 * 60_000);

  test('editar, salir y volver, y exportar', async ({ page }) => {
    await abrirEditor(page);
    await subir(page, [...VIDEOS, media('foto.jpg')], 4);
    expect(await page.evaluate(() => window.__cut.clips.map((c) => c.tipo))).toEqual(['video', 'video', 'video', 'imagen']);
    await expect.poll(() => luzVista(page), { message: 'La vista previa quedó negra después de subir los videos' }).toBeGreaterThan(20);

    // Celular con varios videos pesados: los clips lejos de la línea se duermen para no agotar los decodificadores
    await expect.poll(() => page.evaluate(() => window.__cut.clips[2].el._dormido === true), { message: 'El clip lejano debería estar dormido' }).toBe(true);
    await irA(page, 7); // encima del tercer video
    await expect.poll(() => page.evaluate(() => { const v = window.__cut.clips[2].el; return !v._dormido && v.readyState >= 2; }), { message: 'El tercer video no despertó al poner la línea encima' }).toBe(true);
    await expect.poll(() => page.evaluate(() => window.__cut.clips[0].el._dormido === true)).toBe(true);
    await expect.poll(() => luzVista(page), { message: 'Vista previa negra en el tercer video' }).toBeGreaterThan(20);

    // Reproducir desde el inicio: la línea avanza y se ve imagen
    await irA(page, 0);
    await page.locator('#cutPlay').dispatchEvent('click');
    await page.waitForTimeout(2500);
    const t = await page.evaluate(() => window.__cut.t);
    expect(t, 'La reproducción no avanzó').toBeGreaterThan(0.8);
    expect(await luzVista(page), 'Vista previa negra mientras reproduce').toBeGreaterThan(20);
    await page.locator('#cutPlay').dispatchEvent('click');

    // Salir a otra app (WhatsApp) y volver: no debe quedar negra (falla E7)
    await page.evaluate(() => {
      const poner = (v) => { Object.defineProperty(document, 'visibilityState', { configurable: true, get: () => v }); Object.defineProperty(document, 'hidden', { configurable: true, get: () => v === 'hidden' }); document.dispatchEvent(new Event('visibilitychange')); };
      poner('hidden'); window.dispatchEvent(new Event('blur'));
      return new Promise((r) => setTimeout(() => { poner('visible'); window.dispatchEvent(new Event('focus')); r(); }, 1500));
    });
    await page.waitForTimeout(3000);
    await expect.poll(() => luzVista(page), { message: 'Vista previa negra al volver de otra app' }).toBeGreaterThan(20);

    // Un .mp4 sin imagen (nota de voz) entra como audio
    await page.setInputFiles('#cutArchivo', [media('solo-audio.mp4')]);
    await expect.poll(() => page.evaluate(() => window.__cut.musica.length), { message: 'El mp4 de solo sonido no entró como audio' }).toBe(1);
    expect(await page.evaluate(() => window.__cut.clips.length)).toBe(4);

    // Transición entre el clip 1 y 2 (pasa por el motor de mezcla)
    await page.evaluate(() => { window.__cut.clips[0].trans = { tipo: 'disolver', dur: 0.5 }; window.__cut.dirty = true; });

    // Exportar
    await page.locator('#cutBtnExportar').dispatchEvent('click');
    const hoja = page.locator('#cutExportar');
    await expect(hoja).toBeVisible();
    await hoja.locator('[data-res] .cut-chip').first().dispatchEvent('click').catch(() => {});
    await hoja.locator('[data-fps] [data-v="30"]').dispatchEvent('click');
    await hoja.locator('[data-iniciar]').dispatchEvent('click');
    await expect(hoja.locator('.cut-exp-fin'), 'El video no terminó de exportar').toBeVisible({ timeout: 4 * 60_000 });
    const dur = await hoja.locator('.cut-exp-fin video').evaluate((v) => new Promise((r) => { if (v.readyState >= 1) r(v.duration); else { v.onloadedmetadata = () => r(v.duration); setTimeout(() => r(v.duration), 8000); } }));
    expect(dur, 'El video exportado salió sin duración').toBeGreaterThan(8);
  });

  test('logo encima del video: opacidad, croma y máscara (botones como CapCut)', async ({ page }) => {
    await abrirEditor(page);
    await subir(page, [VIDEOS[0]], 1);
    await page.setInputFiles('#cutCapaFoto', [media('logo.png')]);
    await expect.poll(() => page.evaluate(() => window.__cut.capas.length), { message: 'El logo no entró como capa' }).toBe(1);
    const barra = page.locator('.cut-barra, #cutBarra').first();
    const boton = (tab) => page.locator(`[data-tab="${tab}"]`).first();
    const panel = page.locator('#cutPanelCuerpo');
    // Los tres botones están aparte en la barra
    for (const t of ['f-fondo', 'f-opacidad', 'f-mascara']) await expect(boton(t), `Falta el botón ${t}`).toHaveCount(1);
    // Opacidad
    await boton('f-opacidad').dispatchEvent('click');
    await panel.locator('[data-opr="0.5"]').dispatchEvent('click');
    expect(await page.evaluate(() => window.__cut.capas[0].op)).toBe(0.5);
    // Croma: se borra el blanco de afuera y queda el blanco de adentro del logo
    await boton('f-fondo').dispatchEvent('click');
    await panel.locator('[data-fcroma]').dispatchEvent('click');
    await expect.poll(() => page.evaluate(() => !!window.__cut.capas[0]._sinFondo), { message: 'No quitó el fondo del logo' }).toBe(true);
    const alfa = await page.evaluate(() => { const c = window.__cut.capas[0]._sinFondo, x = c.getContext('2d'); const a = (px, py) => x.getImageData(Math.round(px * c.width), Math.round(py * c.height), 1, 1).data[3]; return { esquina: a(0.02, 0.02), logo: a(0.2, 0.5), adentro: a(0.5, 0.5) }; });
    expect(alfa, 'El fondo de afuera debe quedar transparente y el logo completo').toEqual({ esquina: 0, logo: 255, adentro: 255 });
    // Máscara
    await boton('f-mascara').dispatchEvent('click');
    await panel.locator('[data-grupo="cmasc"] [data-v="corazon"]').dispatchEvent('click');
    expect(await page.evaluate(() => window.__cut.capas[0].masc?.forma)).toBe('corazon');
    await panel.locator('[data-minv]').dispatchEvent('click');
    expect(await page.evaluate(() => window.__cut.capas[0].masc.inv)).toBe(true);
    await page.waitForTimeout(800);
    expect(await luzVista(page)).toBeGreaterThan(20);
  });

  test('eliminar fondo automático con IA (BiRefNet)', async ({ page, vigia }) => {
    test.setTimeout(8 * 60_000);
    await abrirEditor(page);
    await subir(page, [VIDEOS[0]], 1);
    await page.setInputFiles('#cutCapaFoto', [media('logo.png')]);
    await expect.poll(() => page.evaluate(() => window.__cut.capas.length)).toBe(1);
    await page.locator('[data-tab="f-fondo"]').first().dispatchEvent('click');
    const t0 = Date.now();
    await page.locator('#cutPanelCuerpo [data-fauto]').dispatchEvent('click');
    page.on('console', (m) => { if (/fondo|onnx|ort|transformers|webgpu/i.test(m.text())) console.log('  [consola]', m.text().slice(0, 300)); });
    vigia.permitir(/Eliminar fondo/);
    await expect.poll(() => page.evaluate(() => { const k = window.__cut.capas[0]; return k._sinFondo || k._fondoErr ? 'fin' : 'trabajando'; }), { timeout: 7 * 60_000, intervals: [2000], message: 'La IA se quedó trabajando' }).toBe('fin');
    const res = await page.evaluate(() => { const k = window.__cut.capas[0]; return k._sinFondo ? 'listo' : 'error: ' + k._fondoErr + ' | ' + (k._fondoDet || ''); });
    console.log('Resultado IA:', res);
    expect(res).toBe('listo');
    console.log('IA lista en', Math.round((Date.now() - t0) / 1000), 's');
    const alfa = await page.evaluate(() => { const c = window.__cut.capas[0]._sinFondo, x = c.getContext('2d'); const a = (px, py) => x.getImageData(Math.round(px * c.width), Math.round(py * c.height), 1, 1).data[3]; return { esquina: a(0.03, 0.03), logo: a(0.25, 0.5) }; });
    console.log('alfa', JSON.stringify(alfa));
    expect(alfa.esquina, 'La esquina (fondo) debe quedar transparente').toBeLessThan(40);
    expect(alfa.logo, 'El logo debe quedar').toBeGreaterThan(200);
  });

  test('arranca en celulares sin WebGL2 (usa WebGL1)', async ({ page }) => {
    await page.addInitScript(() => {
      const g = HTMLCanvasElement.prototype.getContext;
      HTMLCanvasElement.prototype.getContext = function (t, o) { return t === 'webgl2' ? null : g.call(this, t, o); };
    });
    await abrirEditor(page);
    expect(await page.evaluate(() => window.__motor.v1)).toBe(true);
    await subir(page, [VIDEOS[0]], 1);
    await expect.poll(() => luzVista(page), { message: 'Con WebGL1 la vista previa quedó negra' }).toBeGreaterThan(20);
  });
});
