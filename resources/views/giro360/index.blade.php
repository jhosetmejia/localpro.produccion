<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#000000">
<title>Giro 360 · LocalProo</title>
<meta name="description" content="Escanee, grabe su giro en la máquina 360 y llévese el video editado con música.">
<meta property="og:title" content="Giro 360 · LocalProo">
<meta property="og:description" content="Grabe su video 360 y llévelo editado al instante.">
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
@include('giro360._estilos')
<style>
/* Flujo del invitado: cámara a pantalla completa → una pantalla → descargar */
html,body{height:100%}
body.cam{padding:0;overflow:hidden;background:#000}
.pant{min-height:100%}
#s-cam{position:fixed;inset:0;background:#000;display:flex;flex-direction:column}
#s-cam video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.cam-top{position:relative;z-index:2;display:flex;justify-content:space-between;align-items:center;padding:calc(env(safe-area-inset-top,0px) + 14px) max(16px,env(safe-area-inset-right,0px)) 14px max(16px,env(safe-area-inset-left,0px));background:linear-gradient(rgba(0,0,0,.55),transparent)}
.cam-top .brand{color:#fff;font-size:1.05rem}
.cam-top .brand i{width:28px;height:28px;font-size:.62rem}
.redondo{appearance:none;border:0;width:46px;height:46px;border-radius:50%;background:rgba(0,0,0,.45);color:#fff;display:grid;place-items:center;cursor:pointer}
.redondo svg{width:24px;height:24px}
.cam-abajo{position:absolute;left:0;right:0;bottom:0;z-index:2;display:flex;flex-direction:column;align-items:center;gap:12px;padding:30px 16px calc(env(safe-area-inset-bottom,0px) + 26px);background:linear-gradient(transparent,rgba(0,0,0,.65))}
.cam-abajo p{margin:0;color:#fff;font-weight:600;text-align:center;text-shadow:0 1px 4px rgba(0,0,0,.6);max-width:30ch}
.rec{appearance:none;width:86px;height:86px;border-radius:50%;border:5px solid #fff;background:transparent;display:grid;place-items:center;cursor:pointer;padding:0}
.rec i{width:66px;height:66px;border-radius:50%;background:var(--accent);transition:all .2s}
.rec.on i{width:34px;height:34px;border-radius:9px;background:#ff3b30}
.rec:focus-visible{outline:3px solid var(--accent2);outline-offset:4px}
.tiempo{font:800 1rem var(--display);color:#fff;background:#ff3b30;border-radius:99px;padding:4px 12px;font-variant-numeric:tabular-nums}
.cuenta{position:absolute;inset:0;z-index:3;display:grid;place-items:center;font:800 min(40vw,220px) var(--display);color:#fff;text-shadow:0 6px 30px rgba(0,0,0,.6);pointer-events:none}
.sincam{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;justify-content:center;gap:14px;padding:0 max(24px,env(safe-area-inset-left,0px));background:var(--bg)}
.sincam p{color:var(--muted);margin:0;text-align:center}
.prev{position:relative;border-radius:22px;overflow:hidden;background:#000;display:grid;place-items:center}
.prev video{width:100%;max-height:56vh;object-fit:contain;display:block}
.prev img{position:absolute;left:50%;bottom:5%;transform:translateX(-50%);width:42%;max-height:24%;object-fit:contain;pointer-events:none}
.ajustes{position:absolute;top:10px;right:10px;display:flex;flex-direction:column;gap:8px;align-items:flex-end;max-width:calc(100% - 20px)}
.aj{appearance:none;cursor:pointer;background:rgba(0,0,0,.62);color:#fff;border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:8px 13px;font:600 .82rem var(--body);display:flex;gap:6px;align-items:center;max-width:100%;backdrop-filter:blur(4px)}
.aj b{color:var(--accent2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:42vw}
.aj:focus-visible{outline:2px solid var(--accent2);outline-offset:2px}
.hoja{position:fixed;inset:0;z-index:20;background:rgba(0,0,0,.55);display:flex;align-items:flex-end;justify-content:center}
.hoja-c{background:var(--surface);border-radius:24px 24px 0 0;width:min(520px,100%);max-height:78vh;overflow:auto;padding:18px 16px calc(env(safe-area-inset-bottom,0px) + 10px);display:flex;flex-direction:column;gap:8px}
.hoja-t{font:800 1.05rem var(--display);margin:0 0 4px}
.hoja-l{display:flex;flex-direction:column;gap:8px}
.hoja-g{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin:8px 2px 0}
.op{appearance:none;cursor:pointer;text-align:left;background:var(--surface2);color:var(--fg);border:1.5px solid transparent;border-radius:16px;padding:12px 14px;font:600 1rem var(--body);display:flex;gap:12px;align-items:center}
.op small{display:block;color:var(--muted);font-weight:500;font-size:.82rem;margin-top:2px}
.op.on{border-color:var(--accent);background:#1f3a27}
.op-fila{display:flex;gap:8px;align-items:stretch}
.op-fila .op{flex:1;min-width:0}
.oir{appearance:none;cursor:pointer;flex:none;width:58px;border-radius:16px;border:1.5px solid var(--line);background:var(--bg);color:var(--accent2);font:800 1.2rem var(--body)}
.oir.on{background:var(--accent);color:#0b0d0c;border-color:var(--accent)}
.oir:focus-visible{outline:2px solid var(--accent2);outline-offset:2px}
.op img{width:48px;height:48px;object-fit:contain;border-radius:10px;background:#0b0f0c;flex:none}
.op .ic{width:48px;height:48px;border-radius:10px;background:#0b0f0c;display:grid;place-items:center;font-size:1.4rem;flex:none}
.reg-c{gap:12px}
.reg-c .field{display:flex;flex-direction:column;gap:6px}
.reg-c .lbl{font-weight:700;font-size:.9rem}
.reg-c .note{margin:0;color:var(--muted);font-size:.88rem}
.ubic{font-size:.82rem;color:var(--muted)}
.otro{appearance:none;background:none;border:0;color:var(--muted);font:600 .95rem var(--body);text-decoration:underline;cursor:pointer;padding:8px;align-self:center}
</style>
</head>
<body class="cam">

<!-- 1. Cámara -->
<section id="s-cam">
  <video id="cam" playsinline muted autoplay></video>
  <div class="cam-top">
    <div class="brand"><i>360</i>{{ !empty($evento360) ? \Illuminate\Support\Str::limit($evento360['nombre'], 28) : 'Giro 360' }}</div>
    <button class="redondo" id="voltear" type="button" aria-label="Cambiar de cámara"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-3l-2-3H9L7 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1z"/><path d="M9 13.5a3 3 0 0 1 5.2-2M15 12.5a3 3 0 0 1-5.2 2"/><path d="M14 9.8l.3 1.8-1.8.2M10 16.2l-.3-1.8 1.8-.2"/></svg></button>
  </div>
  <div class="cuenta" id="cuenta" hidden></div>
  <div class="cam-abajo">
    <span class="tiempo" id="tiempo" hidden>0:00</span>
    <button class="rec" id="rec" type="button" aria-label="Grabar"><i></i></button>
    <p id="cam-ayuda">Ponga el celular en la máquina y toque el botón. Tiene 3 segundos.</p>
  </div>
  <div class="sincam" id="sincam" hidden>
    <div class="brand" style="justify-content:center"><i>360</i>Giro 360</div>
    <p id="sincam-txt">Toque para abrir la cámara y grabar su giro.</p>
    <button class="btn big" id="abrir-nativa" type="button">● Abrir cámara</button>
    <button class="btn ghost" id="elegir-video" type="button">Elegir un video ya grabado</button>
  </div>
  <input type="file" id="in-cam" accept="video/*" capture="environment" class="sr" tabindex="-1">
  <input type="file" id="in-gal" accept="video/*" class="sr" tabindex="-1">
</section>

<!-- 2. Una sola pantalla: logo, música y producir -->
<section id="s-listo" class="pant" hidden>
  <div class="app">
    <div class="prev">
      <video id="crudo" playsinline muted loop autoplay></video>
      <img id="logo-prev" alt="">
      <div class="ajustes">
        <button class="aj" id="b-logo" type="button"><span aria-hidden="true">🖼️</span>Logo: <b id="t-logo">…</b></button>
        <button class="aj" id="b-musica" type="button"><span aria-hidden="true">🎵</span>Música: <b id="t-musica">…</b></button>
        <button class="aj" id="b-edicion" type="button"><span aria-hidden="true">✨</span>Edición: <b id="t-edicion">Sí</b></button>
      </div>
    </div>
    <button class="btn big" id="producir" type="button">Producir video</button>
    <button class="otro" id="repetir" type="button">Grabar de nuevo</button>
  </div>
</section>

<!-- Opciones de logo, música y edición -->
<div class="hoja" id="hoja" hidden>
  <div class="hoja-c" role="dialog" aria-modal="true" aria-labelledby="hoja-t">
    <div class="hoja-t" id="hoja-t"></div>
    <div class="hoja-l" id="hoja-l"></div>
    <button class="otro" id="hoja-x" type="button">Cerrar</button>
  </div>
</div>
<!-- Registro para el segundo video (el primero es gratis) -->
<div class="hoja" id="registro" hidden>
  <form class="hoja-c reg-c" id="reg-form" role="dialog" aria-modal="true" aria-labelledby="reg-t" novalidate>
    <div class="hoja-t" id="reg-t">¿Grabamos otro? 🎬</div>
    <p class="note">El primer video fue gratis. Para seguir grabando, déjenos su nombre y celular.</p>
    <div class="field"><label class="lbl" for="reg-nombre">Nombre</label><input type="text" id="reg-nombre" autocomplete="name" placeholder="Nombre y apellido" maxlength="80"></div>
    <div class="field"><label class="lbl" for="reg-cel">Celular (WhatsApp)</label><input type="tel" id="reg-cel" inputmode="tel" autocomplete="tel" placeholder="300 123 4567" maxlength="20"></div>
    <label class="acepta"><input type="checkbox" id="reg-acepta"> <span>Autorizo el tratamiento de mis datos y de la ubicación desde donde me registro, para recibir mi video y ofertas por WhatsApp (Ley 1581 de 2012). <a href="/privacidad" target="_blank" rel="noopener">Política de datos</a></span></label>
    <div class="ubic" id="reg-ubic">📍 Al continuar, el celular le pedirá permiso de ubicación.</div>
    <div class="msg" id="reg-msg" hidden></div>
    <button class="btn big" type="submit" id="reg-ok">Continuar y grabar</button>
    <button class="otro" id="reg-x" type="button">Ahora no</button>
  </form>
</div>
<!-- Código del operador: del tercer video en adelante (cada video se paga) -->
<div class="hoja" id="codigo" hidden>
  <form class="hoja-c reg-c" id="cod-form" role="dialog" aria-modal="true" aria-labelledby="cod-t" novalidate>
    <div class="hoja-t" id="cod-t">🔒 Video <span id="cod-n">3</span></div>
    <p class="note">Ya usó su video gratis. Pague el video al operador de la máquina y escriba aquí el código que le da.</p>
    <div class="field"><label class="lbl" for="cod-in">Código</label><input type="text" id="cod-in" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Código del operador" maxlength="40"></div>
    <div class="msg" id="cod-msg" hidden></div>
    <button class="btn big" type="submit" id="cod-ok">Desbloquear video</button>
  </form>
</div>
<input type="file" id="in-logo" accept="image/*" class="sr" tabindex="-1">

<!-- 3. Produciendo -->
<section id="s-proceso" class="pant" hidden>
  <div class="app">
    <h2>Produciendo su video…</h2>
    <div class="stage"><canvas id="cv"></canvas></div>
    <div class="bar"><i id="prog"></i></div>
    <p class="note">No cierre esta pantalla. Tarda lo mismo que dura el video.</p>
  </div>
</section>

<!-- 4. Descargar -->
<section id="s-final" class="pant" hidden>
  <div class="app">
    <div class="stage"><video id="vid-final" controls playsinline loop></video></div>
    <button class="btn big" id="descargar" type="button">Descargar video</button>
    <div class="msg" id="dl-msg" hidden></div>
    <button class="otro" id="otro" type="button">Grabar otro</button>
    <footer>Hecho con <a href="https://localproo.com" target="_blank" rel="noopener">LocalProo</a></footer>
  </div>
</section>

<script>window.LOGOS_360 = {!! json_encode(array_map(fn ($x) => ['id' => $x['id'], 'nombre' => $x['nombre'], 'url' => '/storage/' . $x['archivo']], \App\Http\Controllers\giro360Controller::logosLista()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
window.CANCIONES_360 = {!! json_encode(\App\Http\Controllers\giro360Controller::cancionesJs(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
window.EVENTO_360 = {!! json_encode($evento360 ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};</script>
@verbatim
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var SEG_MAX = 15, CUENTA = 3;
  function ver(n) {
    ['cam', 'listo', 'proceso', 'final'].forEach(function (v) { $('s-' + v).hidden = v !== n; });
    document.body.classList.toggle('cam', n === 'cam'); window.scrollTo(0, 0);
  }
  function aviso(t) { var m = $('dl-msg'); m.className = 'msg err'; m.textContent = t; m.hidden = false; }

  // ===== Música: catálogo (originales de LocalProo, sin derechos) + Mis audios del celular =====
  var CATALOGO = {
    halloween: [
      { id: 'hw-lp', nombre: 'Halloween · LocalProo', url: '/audio/giro360/halloween.mp3' },
      { id: 'hw-bach', nombre: 'Tocata de terror (Bach)', url: '/audio/giro360/tocata-bach.mp3' }
    ],
    fiesta: [
      { id: 'electro', nombre: 'Electrónica · LocalProo', url: '/audio/giro360/electronica.mp3' }
    ],
    sistema: window.CANCIONES_360 || []
  };
  var TEMAS = [['sistema', 'LocalProo'], ['halloween', 'Halloween'], ['fiesta', 'Fiesta'], ['mis', 'Mis audios']];
  var temporadaHalloween = new Date() < new Date(2026, 10, 3);
  var DEF_MUSICA = temporadaHalloween ? { tema: 'halloween', id: 'hw-lp' } : { tema: 'fiesta', id: 'electro' };
  // Entró por el QR de un evento: la canción del evento va de primera y queda puesta
  var EV = window.EVENTO_360 || null;
  if (EV && EV.cancion) { CATALOGO.evento = [EV.cancion]; TEMAS.unshift(['evento', 'Del evento']); DEF_MUSICA = { tema: 'evento', id: EV.cancion.id }; }
  var misAudios = [], sel = null, musicaBlob = null, musicaNom = '', musicaBuf = null, ritmo = null;

  function idb() { return new Promise(function (ok, mal) { try { var r = indexedDB.open('giro360', 1); r.onupgradeneeded = function () { r.result.createObjectStore('k'); }; r.onsuccess = function () { ok(r.result); }; r.onerror = mal; } catch (e) { mal(e); } }); }
  function leer(k) { return idb().then(function (d) { return new Promise(function (ok) { var r = d.transaction('k').objectStore('k').get(k); r.onsuccess = function () { ok(r.result); }; r.onerror = function () { ok(null); }; }); }).catch(function () { return null; }); }
  function listaDe(t) { return t === 'mis' ? misAudios : (CATALOGO[t] || []); }

  var actx = null;
  function audioCtx() { try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {} return actx; }
  function despertar() { var a = audioCtx(); try { if (a && a.state !== 'running') a.resume(); } catch (e) {} }
  ['touchend', 'click'].forEach(function (ev) { document.addEventListener(ev, despertar, { passive: true }); });

  var pendiente = null;
  function elegir(tema, id) {
    if (tema === 'ninguna') { sel = { tema: 'ninguna' }; musicaBlob = null; musicaNom = ''; musicaBuf = null; ritmo = null; etiqueta(); return (pendiente = Promise.resolve()); }
    var l = listaDe(tema), c = l.filter(function (x) { return x.id === id; })[0] || l[0];
    if (!c) return elegir(DEF_MUSICA.tema, DEF_MUSICA.id);
    sel = { tema: tema, id: c.id }; musicaNom = c.nombre; ritmo = null; etiqueta();
    var p = (c.blob ? Promise.resolve(c.blob) : fetch(c.url).then(function (r) { if (!r.ok) throw 0; return r.blob(); }))
      .then(function (bl) {
        if (!sel || sel.id !== c.id) return;
        musicaBlob = bl;
        var a = audioCtx(); if (!a) return;
        return bl.arrayBuffer().then(function (ab) { return new Promise(function (ok) { a.decodeAudioData(ab, ok, function () { ok(null); }); }); })
          .then(function (buf) { if (sel && sel.id === c.id) { musicaBuf = buf; ritmo = buf ? analizarRitmo(buf) : null; } });
      }).catch(function () { musicaBlob = null; musicaBuf = null; });
    pendiente = p; return p;
  }

  // ===== Efecto: lo pone la canción (el video va rápido donde la música es fuerte y lento donde es suave) =====
  var PASO = 0.05;
  function analizarRitmo(buf) {
    var sr = buf.sampleRate, fin = Math.min(buf.length, Math.floor(90 * sr)), hop = Math.floor(PASO * sr), chs = [], e = [];
    for (var c = 0; c < Math.min(2, buf.numberOfChannels); c++) chs.push(buf.getChannelData(c));
    for (var i = 0; i + hop <= fin; i += hop) { var s2 = 0; for (var j = i; j < i + hop; j += 4) { var v = 0; for (var k = 0; k < chs.length; k++) v += chs[k][j]; s2 += v * v; } e.push(Math.sqrt(s2 / (hop / 4))); }
    if (e.length < 20) return null;
    function suav(a, k) { var o = []; for (var i = 0; i < a.length; i++) { var s = 0, n = 0; for (var j = Math.max(0, i - k); j <= Math.min(a.length - 1, i + k); j++) { s += a[j]; n++; } o.push(s / n); } return o; }
    e = suav(suav(e, 5), 5);
    var ord = e.slice().sort(function (a, b) { return a - b; }), lo = ord[Math.floor(ord.length * .15)], hi = ord[Math.floor(ord.length * .92)], r = new Float32Array(e.length);
    for (var q = 0; q < e.length; q++) { var x = Math.max(0, Math.min(1, (e[q] - lo) / ((hi - lo) || 1))); r[q] = 0.35 * Math.pow(3 / 0.35, Math.pow(x, 1.2)); }
    return r;
  }
  function planRitmo(D) {
    var r = ritmo, cum = new Float32Array(r.length + 1), total = 0;
    for (var i = 0; i < r.length; i++) { cum[i] = total; total += r[i] * PASO; }
    cum[r.length] = total;
    var esc = total < D ? D / total : 1, n = r.length;
    for (var k = 0; k <= r.length; k++) if (cum[k] * esc >= D) { n = k; break; }
    return { r: r, cum: cum, esc: esc, n: n, T: n * PASO };
  }
  // Sin música: rápido y luego lento
  var CLASICO = [[0, 1.2], [0.12, 3], [0.48, 3], [0.62, 0.35], [0.94, 0.35], [1, 0.6]];
  function velClasico(x) { var p = CLASICO; for (var i = 1; i < p.length; i++) if (x <= p[i][0]) { var a = p[i - 1], b = p[i], t = (x - a[0]) / (b[0] - a[0]); t = (1 - Math.cos(Math.PI * t)) / 2; return a[1] + (b[1] - a[1]) * t; } return p[p.length - 1][1]; }
  function factorClasico() { var s = 0; for (var i = 0; i < 400; i++) s += 1 / velClasico((i + .5) / 400); return s / 400; }

  // ===== Buscador de música =====
  function sinTilde(t) { return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
  function etiqueta() {
    var t = TEMAS.filter(function (x) { return sel && x[0] === sel.tema; })[0];
    $('t-musica').textContent = sel && sel.tema === 'ninguna' ? 'Sin música' : (musicaNom ? musicaNom.replace(/ · LocalProo$/, '') : '…');
  }

  // ===== Hoja de opciones (logo, música y edición) =====
  function hoja(titulo, opciones) {
    $('hoja-t').textContent = titulo; var l = $('hoja-l'); l.innerHTML = '';
    opciones.forEach(function (o) {
      if (o.grupo) { var g = document.createElement('div'); g.className = 'hoja-g'; g.textContent = o.grupo; l.appendChild(g); return; }
      var b = document.createElement('button'); b.type = 'button'; b.className = 'op' + (o.on ? ' on' : '');
      if (o.img) { var im = document.createElement('img'); im.src = o.img; im.alt = ''; b.appendChild(im); }
      else { var ic = document.createElement('span'); ic.className = 'ic'; ic.textContent = o.ic || '•'; b.appendChild(ic); }
      var tx = document.createElement('span'); tx.textContent = o.txt;
      if (o.sub) { var sm = document.createElement('small'); sm.textContent = o.sub; tx.appendChild(sm); }
      b.appendChild(tx);
      b.onclick = function () { cerrarHoja(); o.accion(); };
      if (o.oir) {
        // Fila con botón para escuchar la canción antes de elegirla
        var fila = document.createElement('div'); fila.className = 'op-fila';
        var p = document.createElement('button'); p.type = 'button'; p.className = 'oir'; p.setAttribute('aria-label', 'Escuchar ' + o.txt); p.textContent = '▶';
        p.onclick = function () { escuchar(o.oir, p); };
        fila.appendChild(b); fila.appendChild(p); l.appendChild(fila);
      } else l.appendChild(b);
    });
    $('hoja').hidden = false;
  }
  // Escuchar una canción (una a la vez); se para al cerrar o elegir
  var sonando = null, botonSonando = null;
  function pararCancion() {
    if (sonando) { try { sonando.pause(); } catch (e) {} sonando = null; }
    if (botonSonando) { botonSonando.textContent = '▶'; botonSonando.classList.remove('on'); botonSonando = null; }
  }
  function escuchar(fuente, boton) {
    var mismo = botonSonando === boton; pararCancion(); if (mismo) return;
    var src = typeof fuente === 'string' ? fuente : URL.createObjectURL(fuente);
    sonando = new Audio(src); botonSonando = boton; boton.textContent = '■'; boton.classList.add('on');
    sonando.onended = pararCancion;
    sonando.play().catch(function () { pararCancion(); });
  }
  function cerrarHoja() { pararCancion(); $('hoja').hidden = true; }
  $('hoja-x').onclick = cerrarHoja;
  $('hoja').addEventListener('click', function (e) { if (e.target === $('hoja')) cerrarHoja(); });

  $('b-musica').onclick = function () {
    var ops = [{ txt: 'Sin música', sub: 'El video sale sin sonido', ic: '🔇', on: !!(sel && sel.tema === 'ninguna'), accion: function () { elegir('ninguna'); } }];
    TEMAS.forEach(function (t) {
      var l = listaDe(t[0]); if (!l.length) return;
      ops.push({ grupo: t[1] });
      l.forEach(function (c) { ops.push({ txt: c.nombre.replace(/ · LocalProo$/, ''), ic: '🎵', oir: c.blob || c.url, on: !!(sel && sel.tema === t[0] && sel.id === c.id), accion: function () { elegir(t[0], c.id); } }); });
    });
    hoja('Cambiar música', ops);
  };

  var conEdicion = true;
  $('b-edicion').onclick = function () {
    hoja('Edición del video', [
      { txt: 'Con edición', sub: 'Velocidad curva: rápido y lento siguiendo la música', ic: '✨', on: conEdicion, accion: function () { conEdicion = true; $('t-edicion').textContent = 'Sí'; } },
      { txt: 'Sin edición', sub: 'El video tal cual, a velocidad normal', ic: '🎬', on: !conEdicion, accion: function () { conEdicion = false; $('t-edicion').textContent = 'No'; } }
    ]);
  };

  // ===== Logo: sin logo, uno de los logos de LocalProo o uno propio =====
  var LOGOS = (EV && EV.logos ? EV.logos : []).concat([{ id: 'hw2026', nombre: 'Halloween 2026', url: '/img/giro360/halloween-2026.png' }]).concat(window.LOGOS_360 || []);
  var logoImg = null, logoNom = '', logoId = null;
  function ponerLogo(src, nombre, id) {
    logoId = id || null; logoNom = nombre || '';
    if (!src) { logoImg = null; $('logo-prev').hidden = true; $('t-logo').textContent = 'Sin logo'; return; }
    var im = new Image(); im.onload = function () { logoImg = im; }; im.src = src;
    $('logo-prev').src = src; $('logo-prev').hidden = false; $('t-logo').textContent = nombre || 'Mi logo';
  }
  ponerLogo(temporadaHalloween ? LOGOS[0].url : null, temporadaHalloween ? LOGOS[0].nombre : '', temporadaHalloween ? LOGOS[0].id : null);
  $('b-logo').onclick = function () {
    var ops = [{ txt: 'Sin logo', sub: 'El video sale limpio', ic: '🚫', on: !logoImg && !logoId, accion: function () { ponerLogo(null); } }, { grupo: EV && EV.logos.length ? 'Logos del evento y de LocalProo' : 'Logos de LocalProo' }];
    LOGOS.forEach(function (L) { ops.push({ txt: L.nombre, img: L.url, on: logoId === L.id, accion: function () { ponerLogo(L.url, L.nombre, L.id); } }); });
    ops.push({ grupo: 'Su logo' });
    ops.push({ txt: logoId === 'propio' ? 'Cambiar mi logo' : 'Cargar un logo', sub: 'Una imagen del celular (PNG sin fondo queda mejor)', ic: '📤', on: logoId === 'propio', accion: function () { $('in-logo').click(); } });
    hoja('Logo del video', ops);
  };
  $('in-logo').onchange = function () { var f = this.files[0]; this.value = ''; if (f) ponerLogo(URL.createObjectURL(f), 'Mi logo', 'propio'); };

  // Arranque: Mis audios del celular (si los hay) y la canción por defecto
  // Lo que subió una persona (audios, logo, texto) queda solo en su celular y se borra a las 24 horas
  Promise.all([leer('cfg'), leer('misAudios')]).then(function (r) {
    var c = r[0] || {};
    if (c.propioT && Date.now() - c.propioT > 864e5) {
      idb().then(function (d) {
        var t = d.transaction('k', 'readwrite'), st = t.objectStore('k');
        st.delete('misAudios'); st.delete('musica'); st.delete('logo');
        delete c.propioT; c.texto = ''; if (c.musicaSel && c.musicaSel.tema === 'mis') delete c.musicaSel; st.put(c, 'cfg');
      }).catch(function () {});
      misAudios = [];
    } else misAudios = Array.isArray(r[1]) ? r[1] : [];
  });
  elegir(DEF_MUSICA.tema, DEF_MUSICA.id);
  // Logo del evento puesto desde el principio
  if (EV && EV.logos && EV.logos[0]) ponerLogo(EV.logos[0].url, EV.logos[0].nombre, EV.logos[0].id);

  // ===== 1. Cámara directa =====
  var stream = null, facing = 'environment', grabadora = null, trozos = [], grabando = false, crudo = null, durCrudo = 0, wake = null;
  function tipoCam() {
    var l = ['video/mp4;codecs=avc1', 'video/mp4', 'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm'];
    if (!window.MediaRecorder) return null;
    for (var i = 0; i < l.length; i++) if (MediaRecorder.isTypeSupported(l[i])) return l[i];
    return '';
  }
  function sinCamara(txt) { $('sincam').hidden = false; if (txt) $('sincam-txt').textContent = txt; }
  function abrirCamara() {
    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    stream = null;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || tipoCam() === null) return sinCamara();
    navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: facing, width: { ideal: 1920 }, height: { ideal: 1080 }, frameRate: { ideal: 60 } } })
      .then(function (s) { stream = s; $('cam').srcObject = s; $('cam').play().catch(function () {}); $('sincam').hidden = true; })
      .catch(function () { sinCamara('No se pudo abrir la cámara aquí. Toque para abrir la cámara del celular.'); });
  }
  $('voltear').onclick = function () { if (grabando) return; facing = facing === 'environment' ? 'user' : 'environment'; abrirCamara(); };

  var tCuenta = null, tReloj = null;
  $('rec').onclick = function () {
    despertar();
    if (grabando) return parar();
    if (tCuenta) { clearInterval(tCuenta); tCuenta = null; $('cuenta').hidden = true; return; }
    if (!stream) return;
    if (bloqueado()) return puedeGrabar(function () { $('rec').click(); });
    var n = CUENTA; $('cuenta').textContent = n; $('cuenta').hidden = false; $('cam-ayuda').textContent = 'Prepárese…';
    tCuenta = setInterval(function () { n--; if (n > 0) $('cuenta').textContent = n; else { clearInterval(tCuenta); tCuenta = null; $('cuenta').hidden = true; empezar(); } }, 1000);
  };
  function empezar() {
    var tipo = tipoCam(); trozos = [];
    try { grabadora = tipo ? new MediaRecorder(stream, { mimeType: tipo, videoBitsPerSecond: 12000000 }) : new MediaRecorder(stream); }
    catch (e) { return sinCamara('Este celular no deja grabar aquí. Toque para abrir la cámara del celular.'); }
    grabadora.ondataavailable = function (e) { if (e.data && e.data.size) trozos.push(e.data); };
    grabadora.onstop = function () {
      crudo = new Blob(trozos, { type: (grabadora.mimeType || tipo || 'video/webm').split(';')[0] });
      if (stream) stream.getTracks().forEach(function (t) { t.stop(); }); stream = null;
      mostrarListo();
    };
    var t0 = Date.now(); grabando = true; $('rec').classList.add('on'); $('rec').setAttribute('aria-label', 'Detener'); $('tiempo').hidden = false; $('cam-ayuda').textContent = 'Grabando… toque para terminar';
    try { navigator.wakeLock && navigator.wakeLock.request('screen').then(function (w) { wake = w; }).catch(function () {}); } catch (e) {}
    grabadora.start(250);
    tReloj = setInterval(function () {
      var s = (Date.now() - t0) / 1000; durCrudo = s;
      $('tiempo').textContent = '0:' + String(Math.floor(s)).padStart(2, '0') + ' / 0:' + SEG_MAX;
      if (s >= SEG_MAX) parar();
    }, 200);
  }
  function parar() {
    if (!grabando) return; grabando = false; clearInterval(tReloj);
    $('rec').classList.remove('on'); $('rec').setAttribute('aria-label', 'Grabar'); $('tiempo').hidden = true; $('cam-ayuda').textContent = 'Ponga el celular en la máquina y toque el botón. Tiene 3 segundos.';
    try { wake && wake.release(); } catch (e) {}
    try { grabadora.stop(); } catch (e) {}
  }
  // Respaldo: cámara del celular o video de la galería
  $('abrir-nativa').onclick = function () { despertar(); puedeGrabar(function () { $('in-cam').click(); }); };
  $('elegir-video').onclick = function () { despertar(); puedeGrabar(function () { $('in-gal').click(); }); };
  $('in-cam').onchange = $('in-gal').onchange = function () { var f = this.files[0]; this.value = ''; if (f) { crudo = f; durCrudo = 0; mostrarListo(); } };

  // ===== 2. Una sola pantalla =====
  function mostrarListo() {
    var v = $('crudo'); v.src = URL.createObjectURL(crudo); v.play().catch(function () {});
    ver('listo'); etiqueta();
  }
  $('repetir').onclick = function () { $('crudo').pause(); ver('cam'); abrirCamara(); };
  $('producir').onclick = function () { despertar(); $('crudo').pause(); producir(); };

  // ===== 3. Producir (en el celular: velocidad según la canción, logo y música) =====
  function tipoSalida() {
    var l = ['video/mp4;codecs=avc1.42E01F,mp4a.40.2', 'video/mp4;codecs=avc1,mp4a.40.2', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];
    if (!window.MediaRecorder) return null;
    for (var i = 0; i < l.length; i++) if (MediaRecorder.isTypeSupported(l[i])) return l[i];
    return null;
  }
  var editado = null, extEditado = 'mp4';
  function duracionDe(v) {
    // Los videos grabados en el navegador a veces no traen la duración: se busca al final
    return new Promise(function (ok) {
      if (isFinite(v.duration) && v.duration > 0) return ok(v.duration);
      var listo = false;
      v.ondurationchange = function () { if (!listo && isFinite(v.duration) && v.duration > 0) { listo = true; v.ondurationchange = null; v.currentTime = 0; setTimeout(function () { ok(v.duration); }, 120); } };
      v.currentTime = 1e6;
      setTimeout(function () { if (!listo) { listo = true; v.currentTime = 0; ok(durCrudo || 10); } }, 2500);
    });
  }
  function producir() {
    var mime = tipoSalida(), cv = $('cv');
    if (!mime || !cv.captureStream) { ver('final'); aviso('Este navegador no puede editar video. Abra el link en Chrome (Android) o Safari actualizado (iPhone).'); return; }
    ver('proceso'); $('prog').style.width = '0';
    var v = document.createElement('video'); v.muted = true; v.playsInline = true; v.setAttribute('playsinline', ''); v.preload = 'auto';
    v.src = URL.createObjectURL(crudo);
    v.onerror = function () { ver('listo'); };
    v.onloadedmetadata = function () {
      v.onloadedmetadata = null;
      Promise.all([duracionDe(v), Promise.race([pendiente || Promise.resolve(), new Promise(function (ok) { setTimeout(ok, 8000); })])]).then(function (r) {
        var dur = r[0];
        var W = v.videoWidth, H = v.videoHeight, k = Math.min(1, 1920 / Math.max(W, H));
        W = Math.round(W * k / 2) * 2; H = Math.round(H * k / 2) * 2; cv.width = W; cv.height = H;
        var ctx = cv.getContext('2d'), out = cv.captureStream(30);
        var plan = (conEdicion && ritmo && musicaBuf) ? planRitmo(dur) : null;
        var durFinal = plan ? plan.T : (conEdicion ? dur * factorClasico() : dur);
        var fuente = null, gan = null, a = audioCtx();
        if (musicaBuf && a) {
          var dest = a.createMediaStreamDestination(); gan = a.createGain(); gan.connect(dest);
          fuente = a.createBufferSource(); fuente.buffer = musicaBuf; fuente.connect(gan);
          dest.stream.getAudioTracks().forEach(function (t) { out.addTrack(t); });
        }
        var partes = [], rec;
        try { rec = new MediaRecorder(out, { mimeType: mime, videoBitsPerSecond: 10000000 }); } catch (e) { ver('listo'); return; }
        extEditado = mime.indexOf('mp4') > -1 ? 'mp4' : 'webm';
        rec.ondataavailable = function (e) { if (e.data && e.data.size) partes.push(e.data); };
        rec.onstop = function () { try { fuente && fuente.stop(); } catch (e) {} editado = new Blob(partes, { type: mime.split(';')[0] }); mostrarFinal(); };
        var fin = false, rateAnt = 0, tIni = 0, logo = logoImg;
        function dibujar() {
          ctx.drawImage(v, 0, 0, W, H);
          if (logo) {
            var lw = W * (W > H ? 0.22 : 0.42), lh = lw * logo.naturalHeight / logo.naturalWidth;
            if (lh > H * 0.24) { lh = H * 0.24; lw = lh * logo.naturalWidth / logo.naturalHeight; }
            ctx.drawImage(logo, (W - lw) / 2, H * 0.95 - lh, lw, lh);
          }
        }
        function cuadro() {
          if (fin) return;
          var x = dur ? v.currentTime / dur : 0, r;
          if (plan) {
            var tau = (performance.now() - tIni) / 1000, i = Math.min(plan.r.length - 1, Math.floor(tau / PASO));
            var esperado = Math.min(dur, plan.cum[i] * plan.esc), corr = Math.max(0.6, Math.min(1.6, 1 + (esperado - v.currentTime) * 1.2));
            r = plan.r[i] * plan.esc * corr;
          } else r = conEdicion ? velClasico(x) : 1;
          r = Math.max(0.25, Math.min(4, r));
          if (Math.abs(r - rateAnt) > 0.02) { try { v.playbackRate = r; rateAnt = r; } catch (e) {} }
          dibujar(); $('prog').style.width = Math.min(100, x * 100).toFixed(1) + '%';
          requestAnimationFrame(cuadro);
        }
        v.onended = function () { if (fin) return; fin = true; dibujar(); $('prog').style.width = '100%'; setTimeout(function () { try { rec.stop(); } catch (e) {} }, 250); };
        v.currentTime = 0; dibujar(); rec.start(250);
        v.playbackRate = Math.max(0.25, Math.min(4, plan ? plan.r[0] * plan.esc : (conEdicion ? velClasico(0) : 1)));
        v.play().then(function () {
          tIni = performance.now();
          if (fuente) {
            var t0 = a.currentTime;
            gan.gain.setValueAtTime(1, t0); gan.gain.setValueAtTime(1, t0 + Math.max(0, durFinal - 1.2)); gan.gain.linearRampToValueAtTime(0, t0 + durFinal);
            fuente.start(t0, 0);
          }
          requestAnimationFrame(cuadro);
        }).catch(function () { fin = true; try { rec.stop(); } catch (e) {} ver('listo'); });
      });
    };
  }

  // ===== Registro: el primer video es gratis; para el segundo pide nombre, celular y ubicación =====
  var URL_CLIENTE = '/360/cliente';
  function lsLeer(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function lsPoner(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function videosHechos() { return parseInt(lsLeer('giro360-videos') || '0', 10) || 0; }
  function registrado() { return lsLeer('giro360-registro') === '1'; }
  function conPase() { return lsLeer('giro360-pase') === '1'; }
  // Video 1 gratis · video 2 pide datos · video 3 en adelante pide el código del operador (uno por video)
  // En un evento (QR del evento) el alquiler ya está pago: no se pide el código del operador, solo los datos del invitado
  function bloqueado() { var n = videosHechos(); return (n >= 1 && !registrado()) || (n >= 2 && !conPase() && !EV); }
  var luegoRegistro = null;
  function pedirCodigo(seguir) {
    luegoRegistro = seguir;
    $('cod-n').textContent = videosHechos() + 1; $('cod-msg').hidden = true; $('cod-ok').disabled = false; $('cod-in').value = '';
    $('codigo').hidden = false; setTimeout(function () { $('cod-in').focus(); }, 50);
  }
  $('cod-form').onsubmit = function (e) {
    e.preventDefault();
    var c = $('cod-in').value.trim(), m = $('cod-msg');
    function error(t) { m.className = 'msg err'; m.textContent = t; m.hidden = false; $('cod-in').focus(); }
    if (!c) return error('Escriba el código.');
    $('cod-ok').disabled = true;
    var fd = new FormData(); fd.append('codigo', c);
    fetch('/360/codigo', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) {
        if (r.ok) { lsPoner('giro360-pase', '1'); $('codigo').hidden = true; var s = luegoRegistro; luegoRegistro = null; if (s) s(); return; }
        error(r.status === 429 ? 'Muchos intentos. Espere un minuto.' : (j.message || 'Código incorrecto.'));
      }); })
      .catch(function () { error('No hay señal. Revise el internet e intente otra vez.'); })
      .then(function () { $('cod-ok').disabled = false; });
  };
  function puedeGrabar(seguir) {
    if (!bloqueado()) return seguir();
    if (registrado()) return pedirCodigo(seguir);
    luegoRegistro = function () { if (bloqueado()) pedirCodigo(seguir); else seguir(); };
    $('reg-msg').hidden = true; $('reg-ok').disabled = false; $('registro').hidden = false;
    setTimeout(function () { $('reg-nombre').focus(); }, 50);
  }
  function celularOk(t) {
    t = String(t || '').trim(); var intl = /^(\+|00)/.test(t), n = t.replace(/\D/g, '').replace(/^00/, '');
    if (!intl && n.length === 10 && n[0] === '3') return true;
    if (n.length === 12 && n.indexOf('573') === 0) return true;
    if (n.indexOf('57') === 0) return false;
    return intl && n.length >= 8 && n.length <= 15;
  }
  function ubicacion() {
    return new Promise(function (ok) {
      if (!navigator.geolocation) return ok(null);
      var listo = false, t = setTimeout(function () { if (!listo) { listo = true; ok(null); } }, 12000);
      navigator.geolocation.getCurrentPosition(
        function (p) { if (listo) return; listo = true; clearTimeout(t); ok({ latitud: p.coords.latitude, longitud: p.coords.longitude, precision: Math.round(p.coords.accuracy || 0) }); },
        function () { if (listo) return; listo = true; clearTimeout(t); ok(null); },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });
  }
  $('reg-x').onclick = function () { $('registro').hidden = true; luegoRegistro = null; };
  $('reg-form').onsubmit = function (e) {
    e.preventDefault();
    var n = $('reg-nombre').value.trim(), c = $('reg-cel').value.trim(), m = $('reg-msg');
    function error(t, el) { m.className = 'msg err'; m.textContent = t; m.hidden = false; if (el) el.focus(); }
    if (n.length < 2) return error('Escriba su nombre.', $('reg-nombre'));
    if (!celularOk(c)) return error('Revise el celular: 10 números que empiecen por 3 (ej: 300 123 4567). Si es de otro país, póngalo con + y el indicativo.', $('reg-cel'));
    if (!$('reg-acepta').checked) return error('Para seguir debe aceptar el tratamiento de datos.', $('reg-acepta'));
    $('reg-ok').disabled = true; m.className = 'msg'; m.textContent = '📍 Buscando su ubicación… (toque Permitir)'; m.hidden = false;
    ubicacion().then(function (u) {
      m.textContent = 'Guardando…';
      var fd = new FormData(); fd.append('nombre', n.slice(0, 80)); fd.append('whatsapp', c.slice(0, 25)); fd.append('efecto', 'invitado'); fd.append('acepta', '1'); if (EV) fd.append('evento_token', EV.token);
      if (u) { fd.append('latitud', u.latitud); fd.append('longitud', u.longitud); fd.append('precision', u.precision); }
      return fetch(URL_CLIENTE, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (r.status === 422) { var er = new Error(j.message || 'Revise los datos.'); er.v = 1; throw er; } if (!r.ok) throw new Error('red'); return j; }); });
    }).then(function () {
      lsPoner('giro360-registro', '1'); $('registro').hidden = true;
      var s = luegoRegistro; luegoRegistro = null; if (s) s();
    }).catch(function (er) {
      $('reg-ok').disabled = false;
      if (er && er.v) return error(er.message);
      error('No hay señal para guardar. Revise el internet e intente otra vez.');
    });
  };

  // ===== 4. Descargar =====
  var esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  function mostrarFinal() { lsPoner('giro360-videos', String(videosHechos() + 1)); lsPoner('giro360-pase', '0'); var f = $('vid-final'); f.src = URL.createObjectURL(editado); f.play().catch(function () {}); $('dl-msg').hidden = true; ver('final'); }
  $('descargar').onclick = function () {
    if (!editado) return;
    var d = new Date(), nombre = 'giro360-' + d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0') + '-' + String(d.getHours()).padStart(2, '0') + String(d.getMinutes()).padStart(2, '0') + String(d.getSeconds()).padStart(2, '0') + '.' + extEditado;
    var m = $('dl-msg');
    function directo() { var a = document.createElement('a'); a.href = URL.createObjectURL(editado); a.download = nombre; document.body.appendChild(a); a.click(); a.remove(); m.className = 'msg ok'; m.textContent = 'Descargando… queda en la galería o en Descargas.'; m.hidden = false; }
    var f = null; try { f = new File([editado], nombre, { type: editado.type || 'video/mp4' }); } catch (e) {}
    if (esIOS && f && navigator.canShare && navigator.canShare({ files: [f] })) navigator.share({ files: [f] }).then(function () { m.className = 'msg ok'; m.textContent = 'Listo.'; m.hidden = false; }).catch(function (e) { if (!e || e.name !== 'AbortError') directo(); });
    else directo();
  };
  $('otro').onclick = function () { puedeGrabar(function () { $('vid-final').pause(); editado = null; crudo = null; ver('cam'); abrirCamara(); }); };

  // Si este celular ya gastó el gratis, no abre nada hasta poner datos / código
  puedeGrabar(abrirCamara);
})();
</script>
@endverbatim
</body>
</html>
