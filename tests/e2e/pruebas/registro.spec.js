// Una persona nueva se registra como lo haría desde el celular y llega a los pasos del negocio
const { test, expect } = require('../ayuda');

test('registrarse y entrar al panel', async ({ page }, info) => {
  const correo = `nuevo-${info.project.name.toLowerCase()}-${Date.now()}@prueba.localproo.com`;
  await page.goto('/registro');
  await page.fill('#name', 'Persona Prueba');
  await page.fill('#email', correo);
  await page.selectOption('#pais', 'CO');
  // Departamento y ciudad: el primero de cada lista
  const dep = page.locator('select[name=departamento]');
  await expect(dep).toBeVisible();
  await dep.selectOption({ index: 1 });
  const ciudad = page.locator('select[name=ciudad]');
  await expect.poll(async () => ciudad.locator('option').count()).toBeGreaterThan(1);
  await ciudad.selectOption({ index: 1 });
  await page.fill('#telefono', '3001234567');
  await page.fill('#password', 'ClavePrueba123');
  await page.fill('#password_confirmation', 'ClavePrueba123');
  await page.locator('input[name=acepto]').check({ force: true });
  await page.waitForTimeout(2200); // el freno a robots pide mínimo 2 s llenando el formulario
  await Promise.all([page.waitForURL((u) => !/\/registro/.test(u.pathname), { timeout: 30_000 }), page.locator('button[type=submit]').last().click()]);
  await expect(page.locator('body')).not.toContainText(/Ya existe una cuenta|no es válido|Server Error/);
  // Quedó con sesión: el panel o los primeros pasos abren
  const res = await page.goto('/');
  expect(res.status()).toBeLessThan(500);
  expect(page.url()).not.toMatch(/\/login/);
});
