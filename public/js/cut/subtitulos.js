/* LocalProCut · subtítulos: armar frases con tiempos por palabra y dibujarlas con estilos tipo CapCut */

export const ESTILOS = [
  { id: 'tiktok', n: 'TikTok', f: '900 {s}px Montserrat', mayus: true },
  { id: 'resaltado', n: 'Palabra resaltada', f: '800 {s}px Montserrat' },
  { id: 'karaoke', n: 'Karaoke', f: '800 {s}px Montserrat' },
  { id: 'pop', n: 'Una palabra', f: '900 {s}px Montserrat', mayus: true },
  { id: 'clasico', n: 'Clásico', f: '700 {s}px Montserrat' },
  { id: 'caja', n: 'Caja negra', f: '700 {s}px Montserrat' },
  { id: 'neon', n: 'Neón', f: '800 {s}px Montserrat' },
  { id: 'maquina', n: 'Máquina de escribir', f: '700 {s}px "Space Mono"' },
  { id: 'elegante', n: 'Elegante', f: 'italic 700 {s}px "Playfair Display"' },
];

export const COLORES = ['#a6f34a', '#ffe14d', '#ff4fa3', '#39d5ff', '#ffffff'];

// Convierte segmentos {text,start,end} en subtítulos de N palabras con tiempo por palabra
export function armar(segmentos, nPalabras) {
  const subs = [];
  let id = Date.now();
  for (const seg of segmentos) {
    const pal = (seg.text || '').trim().split(/\s+/).filter(Boolean);
    if (!pal.length) continue;
    const ini = Math.max(0, seg.start), fin = Math.max(ini + 0.3, seg.end);
    const peso = pal.map(w => w.length + 2), tot = peso.reduce((a, b) => a + b, 0);
    let t = ini;
    const tiempos = pal.map((w, i) => { const s = t; t += (fin - ini) * peso[i] / tot; return { w, s, e: t }; });
    const n = nPalabras > 0 ? nPalabras : 7;
    for (let i = 0; i < tiempos.length; i += n) {
      const g = tiempos.slice(i, i + n);
      subs.push({ id: id++, start: g[0].s, end: g[g.length - 1].e, text: g.map(x => x.w).join(' '), words: g });
    }
  }
  // Que no se pisen
  subs.sort((a, b) => a.start - b.start);
  for (let i = 1; i < subs.length; i++) if (subs[i].start < subs[i - 1].end) subs[i - 1].end = subs[i].start;
  return subs;
}

// Reparte de nuevo las palabras cuando se edita el texto o los tiempos
export function repartir(sub) {
  const pal = sub.text.trim().split(/\s+/).filter(Boolean);
  const peso = pal.map(w => w.length + 2), tot = peso.reduce((a, b) => a + b, 0) || 1;
  let t = sub.start;
  sub.words = pal.map((w, i) => { const s = t; t += (sub.end - sub.start) * peso[i] / tot; return { w, s, e: t }; });
  return sub;
}

function caja(ctx, x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
}

// Dibuja el subtítulo del tiempo t sobre el canvas 2D (W×H)
export function dibujar(ctx, W, H, t, subs, op) {
  const sub = subs.find(s => t >= s.start && t < s.end);
  if (!sub || !sub.words?.length) return;
  const est = ESTILOS.find(e => e.id === op.estilo) || ESTILOS[0];
  const base = Math.min(W, H) * ({ S: 0.05, M: 0.066, L: 0.085 }[op.tam] || 0.066);
  const mayus = op.mayus || est.mayus;
  const acento = op.color || '#a6f34a';
  const words = sub.words.map(w => ({ ...w, txt: mayus ? w.w.toLocaleUpperCase('es') : w.w }));
  let cur = words.findIndex(w => t >= w.s && t < w.e);
  if (cur < 0) cur = t >= words[words.length - 1].s ? words.length - 1 : 0;
  const yC = H * (op.pos ?? 0.72);
  ctx.save();
  ctx.textAlign = 'left'; ctx.textBaseline = 'middle'; ctx.lineJoin = 'round';

  if (est.id === 'pop') {
    const w = words[cur];
    const s = base * 1.55;
    const rebote = 1 + 0.28 * Math.max(0, 1 - (t - w.s) / 0.14);
    ctx.font = est.f.replace('{s}', Math.round(s * rebote));
    const tw = ctx.measureText(w.txt).width;
    ctx.lineWidth = s * 0.2; ctx.strokeStyle = '#000';
    ctx.strokeText(w.txt, W / 2 - tw / 2, yC);
    ctx.fillStyle = cur % 2 ? acento : '#fff';
    ctx.fillText(w.txt, W / 2 - tw / 2, yC);
    ctx.restore();
    return;
  }

  let txt = words.map(w => w.txt);
  if (est.id === 'maquina') {
    const total = words.map(w => w.txt).join(' ');
    const p = Math.min(1, (t - sub.start) / Math.max(0.2, (sub.end - sub.start) * 0.7));
    const n = Math.round(total.length * p);
    let quedan = n;
    txt = words.map(w => { const v = w.txt.slice(0, Math.max(0, quedan)); quedan -= w.txt.length + 1; return v; });
  }

  ctx.font = est.f.replace('{s}', Math.round(base));
  const esp = ctx.measureText(' ').width;
  const maxW = W * 0.86;
  // Partir en renglones
  const lineas = [[]];
  let ancho = 0;
  words.forEach((w, i) => {
    const ww = ctx.measureText(words[i].txt).width;
    if (lineas[lineas.length - 1].length && ancho + esp + ww > maxW) { lineas.push([]); ancho = 0; }
    lineas[lineas.length - 1].push({ i, ww });
    ancho += (lineas[lineas.length - 1].length > 1 ? esp : 0) + ww;
  });
  const alto = base * 1.28;
  const y0 = yC - (lineas.length - 1) * alto / 2;

  lineas.forEach((ln, li) => {
    const lw = ln.reduce((a, x) => a + x.ww, 0) + esp * (ln.length - 1);
    let x = W / 2 - lw / 2;
    const y = y0 + li * alto;
    if (est.id === 'caja' || est.id === 'maquina') {
      ctx.fillStyle = est.id === 'maquina' ? 'rgba(0,0,0,.82)' : 'rgba(0,0,0,.62)';
      caja(ctx, x - base * 0.35, y - alto * 0.52, lw + base * 0.7, alto * 1.04, base * 0.25);
      ctx.fill();
    }
    for (const { i, ww } of ln) {
      const w = words[i];
      const dicho = t >= w.s;
      const actual = i === cur;
      const texto = txt[i];
      if (est.id === 'resaltado' && actual) {
        ctx.fillStyle = acento;
        caja(ctx, x - base * 0.14, y - alto * 0.5, ww + base * 0.28, alto, base * 0.2);
        ctx.fill();
        ctx.fillStyle = '#0b0d0c';
        ctx.fillText(texto, x, y);
      } else if (est.id === 'neon') {
        ctx.shadowColor = acento; ctx.shadowBlur = base * 0.6;
        ctx.fillStyle = acento; ctx.fillText(texto, x, y);
        ctx.shadowBlur = base * 0.15; ctx.fillStyle = '#fff'; ctx.fillText(texto, x, y);
        ctx.shadowBlur = 0;
      } else if (est.id === 'elegante') {
        ctx.shadowColor = 'rgba(0,0,0,.65)'; ctx.shadowBlur = base * 0.25; ctx.shadowOffsetY = base * 0.05;
        ctx.fillStyle = '#fff'; ctx.fillText(texto, x, y);
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
      } else if (est.id === 'caja' || est.id === 'maquina') {
        ctx.fillStyle = '#fff'; ctx.fillText(texto, x, y);
      } else {
        let color = '#fff', esc = 1;
        if (est.id === 'karaoke' && dicho) color = acento;
        if (est.id === 'tiktok' && actual) { color = acento; esc = 1.08; }
        ctx.save();
        if (esc !== 1) { ctx.translate(x + ww / 2, y); ctx.scale(esc, esc); ctx.translate(-(x + ww / 2), -y); }
        ctx.lineWidth = base * (est.id === 'tiktok' ? 0.22 : 0.16);
        ctx.strokeStyle = '#000';
        ctx.strokeText(texto, x, y);
        ctx.fillStyle = color;
        ctx.fillText(texto, x, y);
        ctx.restore();
      }
      x += ww + esp;
    }
  });
  ctx.restore();
}
