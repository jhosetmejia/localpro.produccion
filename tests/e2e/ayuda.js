// Ayudas para todas las pruebas: vigila las fallas de la página y entra como dueño del negocio de prueba.
const base = require('@playwright/test');

const DUENO = { correo: 'dueno-e2e@prueba.localproo.com', clave: 'PruebaE2E-2026' };
const ADMIN = { correo: process.env.LP_ADMIN_EMAIL || 'admin-e2e@prueba.localproo.com', clave: process.env.LP_ADMIN_PASSWORD || 'AdminE2E-2026' };

// Mensajes de la consola que no son fallas de LocalPro (otros sitios, cosas del navegador de pruebas)
const RUIDO = [
  /fonts\.(googleapis|gstatic)\.com/i,
  /favicon/i,
  /ERR_BLOCKED_BY_CLIENT|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED/i,
  /Content Security Policy.*(huggingface|googleapis|gstatic)/i,
  /GPU stall due to ReadPixels/i,
  /WebGL: INVALID_OPERATION: delete/i,
  /Automatic fallback to software WebGL/i,
];

/**
 * test(): igual al de Playwright, pero cada prueba sale en rojo si la página tuvo
 *  - un error de JavaScript sin atender,
 *  - una falla que LocalPro se reportó a sí mismo (lo mismo que llega a Admin → Opiniones y fallas),
 *  - una respuesta 500 del servidor,
 *  - un error en la consola que no sea ruido conocido.
 * Use permitir(/texto/) dentro de la prueba para aceptar una falla esperada.
 */
const test = base.test.extend({
  vigia: [async ({ page }, use, info) => {
    const fallas = [];
    const permitidas = [];
    const permitida = (t) => permitidas.some((r) => r.test(t)) || RUIDO.some((r) => r.test(t));
    page.on('pageerror', (e) => { const t = 'Error de JavaScript: ' + e.message + (e.stack ? '\n' + e.stack.split('\n').slice(1, 4).join('\n') : ''); if (!permitida(t)) fallas.push(t); });
    page.on('console', (m) => {
      if (m.type() !== 'error') return;
      const loc = m.location()?.url || '';
      const t = 'Consola: ' + m.text() + (loc ? ' (' + loc.replace(/^https?:\/\/[^/]+/, '') + ')' : '');
      if (!permitida(t) && !permitida(loc)) fallas.push(t);
    });
    page.on('response', (r) => {
      if (r.status() >= 500 && r.url().startsWith(info.project.use.baseURL || 'http://127.0.0.1')) {
        const t = `El servidor respondió ${r.status()} en ${r.request().method()} ${new URL(r.url()).pathname}`;
        if (!permitida(t)) fallas.push(t);
      }
    });
    // Las fallas que el propio LocalPro detecta y manda a Admin → Opiniones y fallas
    await page.route('**/opinion/error', async (ruta) => {
      try {
        const d = JSON.parse(ruta.request().postData() || '{}');
        const t = 'LocalPro reportó una falla: ' + d.mensaje + (d.donde ? ' (' + d.donde + ')' : '');
        if (!permitida(t)) fallas.push(t);
      } catch (e) { /* nada */ }
      await ruta.fulfill({ status: 200, contentType: 'application/json', body: '{"ok":true}' });
    });
    await use({ fallas, permitir: (r) => permitidas.push(r) });
    if (fallas.length) {
      await info.attach('fallas.txt', { body: fallas.join('\n\n'), contentType: 'text/plain' });
      throw new Error('La página tuvo ' + fallas.length + ' falla(s):\n- ' + [...new Set(fallas)].slice(0, 8).join('\n- '));
    }
  }, { auto: true }],
});

async function entrar(page, quien = DUENO) {
  await page.goto('/login');
  await page.fill('#email', quien.correo);
  await page.fill('#password', quien.clave);
  await Promise.all([page.waitForURL((u) => !/\/login/.test(u.pathname)), page.click('button[type=submit]')]);
}

module.exports = { test, expect: base.expect, entrar, DUENO, ADMIN };
