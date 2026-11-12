// Resumen en español que sale en la página de la ejecución en GitHub (y en el correo de aviso si algo falla)
const fs = require('fs');

class ReporteEspanol {
  constructor() { this.filas = []; }
  onTestEnd(test, r) {
    const ultimo = r.retry >= (test.retries || 0) || r.status === 'passed';
    if (!ultimo) return;
    const ruta = test.titlePath().slice(2).join(' › ');
    const msg = (r.error?.message || '').replace(/\u001b\[[0-9;]*m/g, '').split('\n').slice(0, 6).join(' ').slice(0, 600);
    this.filas.push({ ok: r.status === 'passed' || r.status === 'skipped', salto: r.status === 'skipped', ruta, equipo: test.parent.project()?.name || '', msg, repetida: r.retry > 0 });
  }
  onEnd() {
    const malas = this.filas.filter((f) => !f.ok);
    let md = malas.length
      ? `## ❌ ${malas.length} prueba(s) con fallas de ${this.filas.length}\n\n`
      : `## ✅ Todo funcionando: ${this.filas.length} pruebas pasaron\n\n`;
    md += '| | Prueba | Equipo | Qué pasó |\n|---|---|---|---|\n';
    for (const f of [...malas, ...this.filas.filter((x) => x.ok)]) {
      md += `| ${f.salto ? '⏭️' : f.ok ? (f.repetida ? '🟡' : '✅') : '❌'} | ${f.ruta.replace(/\|/g, '/')} | ${f.equipo} | ${f.ok ? (f.repetida ? 'pasó al repetirla' : '') : f.msg.replace(/\|/g, '/')} |\n`;
    }
    if (malas.length) md += '\nFotos, video y paso a paso de cada falla: descargue **informe-pruebas** abajo en esta página (Artifacts) y abra `index.html`.\n';
    if (process.env.GITHUB_STEP_SUMMARY) fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, md);
    fs.writeFileSync(__dirname + '/resumen.md', md);
  }
}
module.exports = ReporteEspanol;
