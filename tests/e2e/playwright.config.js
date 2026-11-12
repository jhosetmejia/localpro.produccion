// Pruebas automáticas del navegador: un Chrome de verdad entra a LocalPro y lo usa como una persona.
// En GitHub corren contra una copia limpia del sistema (base vacía + datos de prueba), nunca contra producción.
// Correr a mano: cd tests/e2e && npm install && LP_URL=http://127.0.0.1:8000 npx playwright test
const { defineConfig, devices } = require('@playwright/test');

const URL = process.env.LP_URL || 'http://127.0.0.1:8000';

module.exports = defineConfig({
  testDir: './pruebas',
  timeout: 90_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 1 : 0, // una repetición en GitHub para no alarmar por una demora suelta
  reporter: [['list'], ['html', { outputFolder: 'informe', open: 'never' }], ['./reporte-espanol.js']],
  use: {
    baseURL: URL,
    locale: 'es-CO',
    timezoneId: 'America/Bogota',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    // Chrome de Google (no Chromium): trae los códecs H.264/AAC que usan los videos de los celulares
    channel: 'chrome',
    launchOptions: { args: ['--enable-unsafe-swiftshader', '--use-angle=swiftshader', '--autoplay-policy=no-user-gesture-required'] },
  },
  projects: [
    { name: 'sesiones', testMatch: /\.setup\.js$/ },
    // Celular Android (como la mayoría de la gente): todo
    { name: 'Android', use: { ...devices['Pixel 7'], channel: 'chrome' }, dependencies: ['sesiones'] },
    // Computador: caja, registro y páginas públicas (el recorrido completo y el editor van en el celular)
    { name: 'Computador', use: { viewport: { width: 1366, height: 800 }, channel: 'chrome' }, dependencies: ['sesiones'], testIgnore: /(cut|recorrido)\./ },
  ],
});
