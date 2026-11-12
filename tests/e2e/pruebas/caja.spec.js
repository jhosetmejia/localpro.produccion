// Caja (POS): abrir caja, vender 2 camisetas + 1 gorra en efectivo, revisar total y cambio, y cerrar la caja cuadrada
const path = require('path');
const { test, expect } = require('../ayuda');

test.use({ storageState: path.join(__dirname, '..', '.sesion-dueno.json') });

// Los campos de plata ($) se escriben en un campo visible que LocalPro pone al lado del original (lp-ui.js)
const plata = (page, sel, v) => page.locator(`${sel} + [data-dinero-vista], ${sel}:not([type=hidden])`).first().fill(String(v));
const num = (t) => Number(String(t).replace(/[^\d]/g, '')); // "$90.000" → 90000

test('abrir caja, vender en efectivo y cerrar cuadrada', async ({ page }) => {
  // 1) Abrir caja (si ya estaba abierta, el POS abre directo)
  await page.goto('/pos');
  if (/\/cajas\/abrir/.test(page.url())) {
    await plata(page, 'input[name=monto_inicial]', 50000);
    await Promise.all([page.waitForURL(/\/pos/), page.locator('form button.btn-primary').click()]);
  }
  await expect(page.locator('#grid .card').first()).toBeVisible();

  // 2) Agregar productos tocando las tarjetas (como en el celular)
  const camiseta = page.locator('#grid .card', { hasText: 'Camiseta E2E' });
  const gorra = page.locator('#grid .card', { hasText: 'Gorra E2E' });
  await expect(camiseta).toBeVisible();
  await camiseta.click();
  await camiseta.click();
  await gorra.click();
  await expect.poll(async () => num(await page.locator('#tot').innerText())).toBe(90000);

  // 3) Cobrar en efectivo con un billete de 100.000 → cambio 10.000
  await page.locator('#payBtn').click();
  // Si la tienda sugiere pedir el cliente, se sigue sin cliente
  const sinCli = page.locator('#ovSinCli.on #scSeguir');
  if (await sinCli.isVisible({ timeout: 1500 }).catch(() => false)) await sinCli.click();
  await expect(page.locator('#ovPay')).toBeVisible();
  const efectivo = page.locator('#methods .mt', { hasText: 'Efectivo' });
  if (!(await efectivo.evaluate((b) => b.classList.contains('sel')))) await efectivo.click();
  await plata(page, '#prows input[data-id]', 100000);
  await expect.poll(async () => num(await page.locator('#pChg').innerText())).toBe(10000);
  await page.locator('#pOk').click();
  await expect(page.locator('#ovOk')).toBeVisible();
  await expect(page.locator('#okNum')).not.toBeEmpty();
  expect(num(await page.locator('#okChg').innerText())).toBe(10000);
  await page.locator('#okNew').click();

  // 4) La venta aparece en el historial
  await page.goto('/pos/ventas');
  await expect(page.locator('body')).toContainText(/90\.000/);

  // 5) Cerrar caja con el efectivo esperado: base + lo vendido en efectivo hoy
  await page.goto('/cajas/cerrar');
  const texto = await page.locator('body').innerText();
  const esperado = texto.match(/(?:esperado|debe haber|deber[ií]a)[^\d$]*\$?\s?([\d.]+)/i);
  const contado = esperado ? num(esperado[1]) : 140000;
  await plata(page, '#contado', contado);
  if (await page.locator('#contadoBs').count()) await plata(page, '#contadoBs', 0).catch(() => {});
  await Promise.all([page.waitForURL((u) => !/\/cajas\/cerrar/.test(u.pathname)), page.locator('form button.btn-dark').click()]);
  await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/);
});
