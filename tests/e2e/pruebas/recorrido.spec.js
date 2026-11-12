// Recorrido por TODAS las páginas del sistema (sin parámetros): públicas, del panel del negocio y de administración.
// La lista sale sola de las rutas de Laravel (rutas.json lo arma GitHub con "php artisan route:list"), así que
// cada página nueva queda probada sin escribir nada. Falla si da error 500, error de JavaScript o falla reportada.
const fs = require('fs');
const path = require('path');
const { test, expect } = require('../ayuda');

const archivo = path.join(__dirname, '..', 'rutas.json');
const rutas = fs.existsSync(archivo) ? JSON.parse(fs.readFileSync(archivo, 'utf8')) : [];

// GET que cambian algo o que no son páginas (descargas, salir, cambiar de negocio…): no se visitan
const NO_VISITAR = /logout|salir|impersonar|suplantar|volver-admin|exportar|descargar|excel|csv|pdf|\.xml|zip|respaldo|backup|sitemap|robots|manifest|webhook|callback|oauth|conectar|desconectar|wompi|meta\/|estado-|latido|cron|qr\.png|\/ticket\/|imprimir-todo|eliminar|borrar|activar|desactivar|aprobar|rechazar|cancelar|marcar|cambiar-tienda|entrar-como|^api\//i;

const mw = (r) => (Array.isArray(r.middleware) ? r.middleware : String(r.middleware || '').split(/[\n,]/)).map((x) => String(x).trim());
const sinParametros = (r) => !/[{]/.test(r.uri);
const visitable = (r) => sinParametros(r) && !NO_VISITAR.test(r.uri) && !NO_VISITAR.test(r.name || '') && /GET/.test(r.method);
const conAuth = (r) => mw(r).some((m) => /^auth\b|Authenticate/.test(m));
const esAdmin = (r) => /^admin(\/|$)/.test(r.uri) || /^admin\./.test(r.name || '');

const grupos = {
  publicas: { sesion: null, lista: rutas.filter((r) => visitable(r) && !conAuth(r) && !esAdmin(r)) },
  negocio: { sesion: '.sesion-dueno.json', lista: rutas.filter((r) => visitable(r) && conAuth(r) && !esAdmin(r)) },
  administracion: { sesion: '.sesion-admin.json', lista: rutas.filter((r) => visitable(r) && esAdmin(r)) },
};

test('la lista de páginas se armó', () => {
  expect(rutas.length, 'Falta tests/e2e/rutas.json (lo arma GitHub)').toBeGreaterThan(50);
});

for (const [grupo, { sesion, lista }] of Object.entries(grupos)) {
  test.describe(`Páginas ${grupo}`, () => {
    if (sesion) test.use({ storageState: path.join(__dirname, '..', sesion) });
    for (const r of lista) {
      const url = '/' + r.uri.replace(/^\//, '');
      test(`${url}${r.name ? ' (' + r.name + ')' : ''}`, async ({ page }) => {
        const res = await page.goto(url, { waitUntil: 'domcontentloaded' });
        const codigo = res ? res.status() : 0;
        expect(codigo, `${url} respondió ${codigo}`).toBeLessThan(500);
        // Dejar que corra el JavaScript de la página un momento
        await page.waitForLoadState('load', { timeout: 15_000 }).catch(() => {});
        await page.waitForTimeout(600);
        // Página en blanco = algo se rompió al pintar
        const texto = (await page.locator('body').innerText().catch(() => '')).trim();
        expect(texto.length, `${url} quedó en blanco`).toBeGreaterThan(20);
        expect(texto, `${url} muestra un error de Laravel`).not.toMatch(/Whoops|Server Error|Undefined (variable|array key|index)|ErrorException|SQLSTATE/);
      });
    }
  });
}
