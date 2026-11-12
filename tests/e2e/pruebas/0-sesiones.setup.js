// Antes de todo: entra una vez como dueño y como administrador y guarda las sesiones (las demás pruebas las reusan)
const { test } = require('@playwright/test');
const { DUENO, ADMIN } = require('../ayuda');

for (const [nombre, q] of [['dueno', DUENO], ['admin', ADMIN]]) {
  test(`entrar como ${nombre}`, async ({ page }) => {
    await page.goto('/login');
    await page.fill('#email', q.correo);
    await page.fill('#password', q.clave);
    await Promise.all([page.waitForURL((u) => !/\/login/.test(u.pathname), { timeout: 30_000 }), page.click('button[type=submit]')]);
    await page.context().storageState({ path: `.sesion-${nombre}.json` });
  });
}
