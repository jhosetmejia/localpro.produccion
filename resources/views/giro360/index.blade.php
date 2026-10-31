<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f0c">
<title>Giro 360 · LocalProo</title>
<meta name="description" content="Grabe su video 360 y llévelo editado con velocidad curva, logo y música del evento.">
<meta property="og:title" content="Giro 360 · LocalProo">
<meta property="og:description" content="Grabe su video 360 y llévelo editado al instante.">
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
html,body{margin:0}
[hidden]{display:none!important}
img{max-width:100%}
/* Una sola columna tipo app de cámara, look de pista de evento (oscuro a propósito) */
:root{
  --bg:#0b0f0c; --surface:#131c16; --surface2:#1b2920; --line:#29402f;
  --fg:#f2fbf4; --muted:#9fbaa8; --accent:#2fd15a; --accent2:#a6f34a; --ok:#a6f34a; --err:#ff8a7a;
  --display:'Unbounded',system-ui,sans-serif; --body:'Figtree',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
  color-scheme:dark;
}
html,body{background:var(--bg);color:var(--fg)}
body{font-family:var(--body);font-size:16px;line-height:1.45;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));padding-block:18px 40px}
.app{max-width:520px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
header{display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{font-family:var(--display);font-weight:800;font-size:1.35rem;letter-spacing:-.01em;display:flex;align-items:center;gap:10px}
.brand i{width:34px;height:34px;border-radius:50%;background:conic-gradient(from 0deg,var(--accent),var(--accent2),var(--accent));display:grid;place-items:center;font-style:normal;font-size:.75rem;color:var(--bg)}
.chip{font-size:.8rem;font-weight:600;color:var(--muted);background:var(--surface);border:1px solid var(--line);border-radius:999px;padding:6px 12px}
.chip:focus-visible,.btn:focus-visible,.fx:focus-visible{outline:2px solid var(--accent2);outline-offset:2px}
h2{font-family:var(--display);font-weight:600;font-size:1rem;margin:0;letter-spacing:.01em}
.lbl{font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.card{background:var(--surface);border:1px solid var(--line);border-radius:22px;padding:16px;display:flex;flex-direction:column;gap:14px}
.btn{appearance:none;border:0;border-radius:16px;padding:14px 16px;font:600 1rem var(--body);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-align:center;color:var(--fg);background:var(--surface2)}
.btn:disabled{opacity:.5;cursor:default}
.btn.big{font-family:var(--display);font-size:1.15rem;padding:22px 16px;border-radius:22px;color:#0b0d0c;background:linear-gradient(120deg,var(--accent2),var(--accent) 55%,#16b34a)}
.btn.pri{background:var(--accent);color:#0b0d0c}
.btn.ghost{background:transparent;border:1px solid var(--line)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fxs{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
.fx{background:var(--surface2);border:1.5px solid transparent;border-radius:16px;padding:10px 10px 8px;cursor:pointer;text-align:left;color:var(--fg);font:inherit;display:flex;flex-direction:column;gap:4px}
.fx[aria-pressed="true"]{border-color:var(--accent);background:#1f3a27}
.fx b{font-size:.92rem}
.fx small{color:var(--muted);font-size:.75rem}
.fx svg{width:100%;height:38px;display:block}
.field{display:flex;flex-direction:column;gap:6px}
.field input[type=text],.field input[type=tel]{background:var(--bg);color:var(--fg);border:1px solid var(--line);border-radius:12px;padding:12px;font:inherit;width:100%;box-sizing:border-box}
.pick{display:flex;align-items:center;gap:12px;background:var(--surface2);border-radius:14px;padding:10px 12px;min-width:0}
.pick .th{width:44px;height:44px;border-radius:10px;background:var(--bg) center/contain no-repeat;flex:none;display:grid;place-items:center;color:var(--muted);font-size:1.2rem}
.pick .tx{flex:1;min-width:0;font-size:.9rem}
.pick .tx div{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pick .tx small{color:var(--muted)}
.pick button{flex:none}
.mini{appearance:none;border:1px solid var(--line);background:transparent;color:var(--fg);border-radius:10px;padding:7px 10px;font:600 .8rem var(--body);cursor:pointer}
.stage{position:relative;border-radius:22px;overflow:hidden;background:#000;display:grid;place-items:center;max-height:62vh}
.stage canvas,.stage video{max-width:100%;max-height:62vh;display:block}
.bar{height:8px;border-radius:99px;background:var(--surface2);overflow:hidden}
.bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:width .2s}
.note{font-size:.85rem;color:var(--muted);margin:0}
.msg{font-size:.9rem;border-radius:12px;padding:10px 12px;background:var(--surface2)}
.msg.err{color:var(--err)} .msg.ok{color:var(--ok)}
.list{display:flex;flex-direction:column;gap:6px;max-height:320px;overflow:auto}
.list div{display:flex;justify-content:space-between;gap:10px;background:var(--surface2);border-radius:10px;padding:8px 10px;font-size:.9rem}
.list span{color:var(--muted);font-variant-numeric:tabular-nums;white-space:nowrap}
.acepta{display:flex;gap:10px;align-items:flex-start;font-size:.82rem;color:var(--muted)}
.acepta input{width:20px;height:20px;flex:none;margin-top:1px;accent-color:var(--accent)}
.acepta a{color:var(--accent2)}
footer{text-align:center;font-size:.8rem;color:var(--muted)}
footer a{color:var(--muted)}
.listas{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.listas .lbl{margin-right:4px}
.listas .mini{border-color:var(--accent2);color:var(--accent2)}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
@media (prefers-reduced-motion:reduce){.bar i{transition:none}}
</style>
</head>
<body data-cliente="{{ route('giro360.cliente') }}">


<div class="app">
  <header>
    <div class="brand"><i>360</i>Giro 360</div>
    <span class="chip">localproo.com</span>
  </header>

  <!-- 1. Inicio -->
  <section id="v-inicio" class="app" style="gap:18px">
    <button class="btn big" id="btn-grabar" type="button">● Grabar video 360</button>
    <button class="btn ghost" id="btn-galeria" type="button">Elegir un video ya grabado</button>
    <input type="file" id="in-cam" accept="video/*" capture="environment" class="sr" tabindex="-1">
    <input type="file" id="in-gal" accept="video/*" class="sr" tabindex="-1">

    <div class="card">
      <div class="lbl">Velocidad curva</div>
      <div class="fxs" id="fxs"></div>
      <p class="note" id="fx-dur"></p>
    </div>

    <div class="card">
      <div class="lbl">Del evento (opcional)</div>
      <div class="pick">
        <div class="th" id="logo-th">◎</div>
        <div class="tx"><div id="logo-nom">Sin logo</div><small>PNG con fondo transparente queda mejor</small></div>
        <button class="mini" id="logo-btn" type="button">Subir</button>
        <button class="mini" id="logo-quitar" type="button" hidden>Quitar</button>
        <input type="file" id="in-logo" accept="image/*" class="sr" tabindex="-1">
      </div>
      <div class="listas">
        <span class="lbl">Logos listos</span>
        <button class="mini" type="button" data-logo="/img/giro360/halloween-2026.png" data-nombre="Halloween 2026">Halloween 2026</button>
      </div>
      <div class="pick">
        <div class="th">♫</div>
        <div class="tx"><div id="musica-nom">Sin canción</div><small id="musica-sub">El video sale con la canción de fondo</small></div>
        <button class="mini" id="musica-oir" type="button" hidden>▶ Oír</button>
        <button class="mini" id="musica-btn" type="button">Subir</button>
        <button class="mini" id="musica-quitar" type="button" hidden>Quitar</button>
        <input type="file" id="in-musica" accept="audio/*" class="sr" tabindex="-1">
      </div>
      <div class="listas">
        <span class="lbl">Canciones listas</span>
        <button class="mini" type="button" data-cancion="/audio/giro360/halloween.mp3" data-nombre="Halloween · LocalProo">Halloween</button>
      </div>
      <div class="field" id="desde-wrap" hidden>
        <label class="lbl" for="musica-desde">La canción arranca en el segundo</label>
        <input type="text" id="musica-desde" inputmode="numeric" value="0">
      </div>
      <div class="field">
        <label class="lbl" for="texto">Texto abajo del video</label>
        <input type="text" id="texto" maxlength="40" placeholder="Ej: Los XV de Valentina">
      </div>
      <p class="note">Esto queda guardado en este celular para todo el evento.</p>
    </div>
  </section>

  <!-- 2. Procesando -->
  <section id="v-proceso" class="app" hidden>
    <h2>Editando el video…</h2>
    <div class="stage"><canvas id="cv"></canvas></div>
    <div class="bar"><i id="prog"></i></div>
    <p class="note">No cierre ni cambie de pantalla mientras termina. Tarda lo mismo que dura el video.</p>
  </section>

  <!-- 3. Resultado -->
  <section id="v-resultado" class="app" hidden>
    <div class="stage"><video id="vid-final" controls playsinline loop></video></div>
    <div class="row">
      <button class="btn pri" id="dl-editado" type="button">Descargar editado</button>
      <button class="btn" id="dl-original" type="button">Descargar original</button>
    </div>
    <div class="msg" id="dl-msg" hidden></div>
    <form class="card" id="form-cliente" novalidate>
      <h2>¿A quién le enviamos el video?</h2>
      <div class="field"><label class="lbl" for="c-nombre">Nombre</label><input type="text" id="c-nombre" autocomplete="name" placeholder="Nombre y apellido"></div>
      <div class="field"><label class="lbl" for="c-wa">WhatsApp</label><input type="tel" id="c-wa" inputmode="tel" autocomplete="tel" placeholder="300 123 4567"></div>
      <label class="acepta"><input type="checkbox" id="c-acepta"> <span>Autorizo el tratamiento de mis datos para recibir mi video y ofertas por WhatsApp (Ley 1581 de 2012). <a href="/privacidad" target="_blank" rel="noopener">Política de datos</a></span></label>
      <div class="msg" id="c-msg" hidden></div>
      <button class="btn pri" type="submit" id="c-guardar">Guardar y grabar otro</button>
      <button class="btn ghost" type="button" id="btn-otro">Grabar otro sin guardar datos</button>
    </form>
  </section>

  <footer>Hecho con <a href="https://localproo.com" target="_blank" rel="noopener">LocalProo</a> · el video no sale de su celular</footer>
</div>
@verbatim
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var vistas = ['inicio', 'proceso', 'resultado'];
  function ver(n) { vistas.forEach(function (v) { $('v-' + v).hidden = v !== n; }); window.scrollTo(0, 0); }

  // ===== Curvas de velocidad (como las de CapCut): [momento del video 0..1, velocidad] =====
  var EFECTOS = [
    { id: 'clasico', nombre: '360 clásico', desc: 'Rápido y luego lento', p: [[0, 1.2], [0.12, 3], [0.48, 3], [0.62, 0.35], [0.94, 0.35], [1, 0.6]] },
    { id: 'heroe', nombre: 'Héroe', desc: 'Rápido, lento, rápido', p: [[0, 1], [0.25, 3], [0.42, 0.3], [0.58, 0.3], [0.75, 3], [1, 1]] },
    { id: 'bala', nombre: 'Bala', desc: 'Frena de golpe en el medio', p: [[0, 1.4], [0.4, 2], [0.5, 0.25], [0.6, 2], [1, 1.4]] },
    { id: 'montaje', nombre: 'Montaje', desc: 'Sube y baja dos veces', p: [[0, 0.7], [0.22, 3], [0.45, 0.5], [0.7, 3], [1, 0.6]] },
    { id: 'lento', nombre: 'Todo lento', desc: 'Cámara lenta suave', p: [[0, 0.5], [1, 0.5]] },
    { id: 'normal', nombre: 'Sin efecto', desc: 'Velocidad normal', p: [[0, 1], [1, 1]] }
  ];
  function velocidad(ef, x) {
    var p = ef.p; if (x <= p[0][0]) return p[0][1];
    for (var i = 1; i < p.length; i++) {
      if (x <= p[i][0]) {
        var a = p[i - 1], b = p[i], t = (x - a[0]) / (b[0] - a[0] || 1);
        t = (1 - Math.cos(Math.PI * t)) / 2; // curva suave
        return a[1] + (b[1] - a[1]) * t;
      }
    }
    return p[p.length - 1][1];
  }
  // Cuánto dura el video editado por cada segundo grabado
  function factorDuracion(ef) { var s = 0, n = 400; for (var i = 0; i < n; i++) s += 1 / velocidad(ef, (i + .5) / n); return s / n; }

  // ===== Ajustes guardados en este celular (IndexedDB) =====
  var cfg = { efecto: 'clasico', texto: '', desde: 0 }, logoImg = null, logoBlob = null, musicaBlob = null, musicaNom = '';
  function idb() {
    return new Promise(function (ok, mal) {
      try { var r = indexedDB.open('giro360', 1); r.onupgradeneeded = function () { r.result.createObjectStore('k'); }; r.onsuccess = function () { ok(r.result); }; r.onerror = mal; } catch (e) { mal(e); }
    });
  }
  function guardar(k, v) { idb().then(function (d) { var t = d.transaction('k', 'readwrite'); if (v == null) t.objectStore('k').delete(k); else t.objectStore('k').put(v, k); }).catch(function () {}); }
  function leer(k) { return idb().then(function (d) { return new Promise(function (ok) { var r = d.transaction('k').objectStore('k').get(k); r.onsuccess = function () { ok(r.result); }; r.onerror = function () { ok(null); }; }); }).catch(function () { return null; }); }

  function efectoActual() { return EFECTOS.filter(function (e) { return e.id === cfg.efecto; })[0] || EFECTOS[0]; }
  function pintarEfectos() {
    var max = 3.2;
    $('fxs').innerHTML = '';
    EFECTOS.forEach(function (ef) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'fx'; b.setAttribute('aria-pressed', ef.id === cfg.efecto);
      var pts = []; for (var i = 0; i <= 40; i++) { var x = i / 40; pts.push((x * 100).toFixed(1) + ',' + (34 - velocidad(ef, x) / max * 30).toFixed(1)); }
      b.innerHTML = '<svg viewBox="0 0 100 38" preserveAspectRatio="none" aria-hidden="true"><line x1="0" x2="100" y1="' + (34 - 1 / max * 30) + '" y2="' + (34 - 1 / max * 30) + '" stroke="#29402f" stroke-dasharray="3 3" fill="none"/><polyline points="' + pts.join(' ') + '" fill="none" stroke="' + (ef.id === cfg.efecto ? '#a6f34a' : '#9fbaa8') + '" stroke-width="2.2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg><b></b><small></small>';
      b.querySelector('b').textContent = ef.nombre; b.querySelector('small').textContent = ef.desc;
      b.onclick = function () { cfg.efecto = ef.id; guardar('cfg', cfg); pintarEfectos(); };
      $('fxs').appendChild(b);
    });
    var f = factorDuracion(efectoActual());
    $('fx-dur').textContent = 'Un giro de 10 s queda de ' + Math.round(10 * f) + ' s editado.';
  }

  function ponerLogo(blob, nombre) {
    logoBlob = blob; logoImg = null;
    if (!blob) { $('logo-th').style.backgroundImage = ''; $('logo-th').textContent = '◎'; $('logo-nom').textContent = 'Sin logo'; $('logo-quitar').hidden = true; return; }
    var u = URL.createObjectURL(blob), im = new Image();
    im.onload = function () { logoImg = im; }; im.src = u;
    $('logo-th').style.backgroundImage = 'url(' + u + ')'; $('logo-th').textContent = ''; $('logo-nom').textContent = nombre || 'Logo'; $('logo-quitar').hidden = false;
  }
  function ponerMusica(blob, nombre) {
    musicaBlob = blob; musicaNom = nombre || '';
    $('musica-nom').textContent = blob ? (nombre || 'Canción') : 'Sin canción';
    $('musica-quitar').hidden = !blob; $('desde-wrap').hidden = !blob; $('musica-oir').hidden = !blob;
    parar();
  }
  // Escuchar la canción elegida
  var oido = null;
  function parar() { if (oido) { oido.pause(); URL.revokeObjectURL(oido.src); oido = null; } $('musica-oir').textContent = '▶ Oír'; }
  $('musica-oir').onclick = function () {
    if (oido) return parar();
    if (!musicaBlob) return;
    oido = new Audio(URL.createObjectURL(musicaBlob)); oido.currentTime = cfg.desde || 0;
    oido.onended = parar; oido.play().catch(parar); this.textContent = '■ Parar';
  };
  // Logos y canciones listas (originales de LocalProo, sin derechos de autor)
  function cargarListo(b, silencioso) {
    if (!b) return;
    var esLogo = !!b.dataset.logo, url = b.dataset.logo || b.dataset.cancion, nom = b.dataset.nombre, t = b.textContent;
    b.disabled = true; b.textContent = 'Cargando…';
    fetch(url).then(function (r) { if (!r.ok) throw 0; return r.blob(); })
      .then(function (bl) {
        if (esLogo) { ponerLogo(bl, nom); guardar('logo', { blob: bl, nombre: nom }); cfg.sinLogo = false; }
        else { ponerMusica(bl, nom); guardar('musica', { blob: bl, nombre: nom }); cfg.sinMusica = false; }
        guardar('cfg', cfg);
        if (!silencioso) aviso((esLogo ? 'Logo listo: ' : 'Canción lista: ') + nom + '.', true);
      })
      .catch(function () { if (!silencioso) aviso('No se pudo cargar. Revise la señal e intente otra vez.'); })
      .then(function () { b.disabled = false; b.textContent = t; });
  }
  document.querySelectorAll('[data-logo],[data-cancion]').forEach(function (b) { b.onclick = function () { cargarListo(b); }; });

  $('logo-btn').onclick = function () { $('in-logo').click(); };
  $('musica-btn').onclick = function () { $('in-musica').click(); };
  $('in-logo').onchange = function () { var f = this.files[0]; if (!f) return; ponerLogo(f, f.name); guardar('logo', { blob: f, nombre: f.name }); this.value = ''; };
  $('in-musica').onchange = function () { var f = this.files[0]; if (!f) return; ponerMusica(f, f.name); guardar('musica', { blob: f, nombre: f.name }); this.value = ''; };
  $('logo-quitar').onclick = function () { ponerLogo(null); guardar('logo', null); cfg.sinLogo = true; guardar('cfg', cfg); };
  $('musica-quitar').onclick = function () { ponerMusica(null); guardar('musica', null); cfg.sinMusica = true; guardar('cfg', cfg); };
  $('texto').oninput = function () { cfg.texto = this.value; guardar('cfg', cfg); };
  $('musica-desde').oninput = function () { cfg.desde = Math.max(0, parseFloat(this.value.replace(',', '.')) || 0); guardar('cfg', cfg); };

  Promise.all([leer('cfg'), leer('logo'), leer('musica')]).then(function (r) {
    if (r[0]) { cfg = Object.assign(cfg, r[0]); $('texto').value = cfg.texto || ''; $('musica-desde').value = cfg.desde || 0; }
    if (r[1]) ponerLogo(r[1].blob, r[1].nombre);
    if (r[2]) ponerMusica(r[2].blob, r[2].nombre);
    pintarEfectos();
    // Temporada de Halloween: si este celular no tiene logo o canción, se ponen los de Halloween solos
    if (new Date() < new Date(2026, 10, 3)) {
      if (!r[1] && !cfg.sinLogo) cargarListo(document.querySelector('[data-logo]'), true);
      if (!r[2] && !cfg.sinMusica) cargarListo(document.querySelector('[data-cancion]'), true);
    }
  });
  pintarEfectos();

  // ===== Grabar / elegir =====
  var actx = null, original = null, editado = null, extEditado = 'mp4';
  function prepararAudio() { try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); actx.resume(); } catch (e) {} }
  $('btn-grabar').onclick = function () { prepararAudio(); $('in-cam').click(); };
  $('btn-galeria').onclick = function () { prepararAudio(); $('in-gal').click(); };
  $('in-cam').onchange = $('in-gal').onchange = function () { var f = this.files[0]; this.value = ''; if (f) editar(f); };

  function formato() {
    var l = ['video/mp4;codecs=avc1.42E01F,mp4a.40.2', 'video/mp4;codecs=avc1,mp4a.40.2', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];
    if (!window.MediaRecorder) return null;
    for (var i = 0; i < l.length; i++) if (MediaRecorder.isTypeSupported(l[i])) return l[i];
    return null;
  }

  function editar(file) {
    original = file; editado = null;
    var mime = formato(), cv = $('cv');
    if (!mime || !cv.captureStream) { ver('inicio'); aviso('Este navegador no puede editar video. Abra el link en Chrome (Android) o Safari actualizado (iPhone).'); return; }
    ver('proceso'); $('prog').style.width = '0';
    var ef = efectoActual();
    var v = document.createElement('video'); v.muted = true; v.playsInline = true; v.setAttribute('playsinline', ''); v.preload = 'auto';
    v.src = URL.createObjectURL(file);
    v.onerror = function () { ver('inicio'); aviso('No se pudo abrir ese video. Pruebe grabarlo de nuevo.'); };
    v.onloadedmetadata = function () {
      var W = v.videoWidth, H = v.videoHeight, k = Math.min(1, 1920 / Math.max(W, H));
      W = Math.round(W * k / 2) * 2; H = Math.round(H * k / 2) * 2;
      cv.width = W; cv.height = H;
      var ctx = cv.getContext('2d'), dur = v.duration;
      var durFinal = dur * factorDuracion(ef);
      var stream = cv.captureStream(30);
      var fuente = null, ganancia = null;
      listoParaEditar().then(prepMusica).then(function (buf) {
        if (buf && actx) {
          var dest = actx.createMediaStreamDestination();
          ganancia = actx.createGain(); ganancia.connect(dest);
          fuente = actx.createBufferSource(); fuente.buffer = buf; fuente.connect(ganancia);
          dest.stream.getAudioTracks().forEach(function (t) { stream.addTrack(t); });
        }
        var trozos = [], rec;
        try { rec = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 10000000 }); }
        catch (e) { ver('inicio'); aviso('No se pudo empezar a grabar el video editado.'); return; }
        extEditado = mime.indexOf('mp4') > -1 ? 'mp4' : 'webm';
        rec.ondataavailable = function (e) { if (e.data && e.data.size) trozos.push(e.data); };
        rec.onstop = function () {
          try { if (fuente) fuente.stop(); } catch (e) {}
          editado = new Blob(trozos, { type: mime.split(';')[0] });
          mostrarResultado();
        };
        var fin = false, rateAnt = 0;
        function dibujar() {
          ctx.drawImage(v, 0, 0, W, H);
          var lw = 0, lh = 0;
          if (logoImg) {
            lw = W * (W > H ? 0.22 : 0.42); lh = lw * logoImg.naturalHeight / logoImg.naturalWidth;
            if (lh > H * 0.24) { lh = H * 0.24; lw = lh * logoImg.naturalWidth / logoImg.naturalHeight; }
          }
          var fs = Math.round(Math.min(W, H) * 0.055), base = H - H * 0.05;
          if (cfg.texto) {
            ctx.font = '800 ' + fs + 'px Unbounded, Figtree, sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
            ctx.lineWidth = fs * 0.16; ctx.strokeStyle = 'rgba(0,0,0,.55)'; ctx.lineJoin = 'round';
            ctx.strokeText(cfg.texto, W / 2, base); ctx.fillStyle = '#fff'; ctx.fillText(cfg.texto, W / 2, base);
            base -= fs * 1.3;
          }
          if (logoImg) ctx.drawImage(logoImg, (W - lw) / 2, base - lh, lw, lh);
        }
        function cuadro() {
          if (fin) return;
          var x = dur ? v.currentTime / dur : 0;
          var r = Math.max(0.25, Math.min(4, velocidad(ef, x)));
          if (Math.abs(r - rateAnt) > 0.02) { try { v.playbackRate = r; rateAnt = r; } catch (e) {} }
          dibujar();
          $('prog').style.width = Math.min(100, x * 100).toFixed(1) + '%';
          requestAnimationFrame(cuadro);
        }
        v.onended = function () {
          if (fin) return; fin = true; dibujar(); $('prog').style.width = '100%';
          setTimeout(function () { try { rec.stop(); } catch (e) {} }, 250);
        };
        v.currentTime = 0;
        dibujar();
        rec.start(250);
        if (fuente) {
          var t0 = actx.currentTime;
          ganancia.gain.setValueAtTime(1, t0);
          ganancia.gain.setValueAtTime(1, t0 + Math.max(0, durFinal - 1.2));
          ganancia.gain.linearRampToValueAtTime(0, t0 + durFinal);
          fuente.start(t0, Math.min(cfg.desde || 0, Math.max(0, fuente.buffer.duration - 1)));
        }
        v.playbackRate = Math.max(0.25, velocidad(ef, 0));
        v.play().then(function () { requestAnimationFrame(cuadro); }).catch(function () { fin = true; try { rec.stop(); } catch (e) {} ver('inicio'); aviso('El celular no dejó reproducir el video. Intente de nuevo.'); });
      });
    };
  }

  function listoParaEditar() {
    var esperas = [];
    if (logoBlob && !logoImg) esperas.push(new Promise(function (ok) {
      var im = new Image(); im.onload = function () { logoImg = im; ok(); }; im.onerror = ok; im.src = URL.createObjectURL(logoBlob); setTimeout(ok, 3000);
    }));
    if (musicaBlob) esperas.push(new Promise(function (ok) {
      try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { return ok(); }
      var n = 0; (function intento() {
        if (actx.state === 'running' || n++ > 15) return ok();
        actx.resume().catch(function () {}); setTimeout(intento, 100);
      })();
    }));
    return Promise.all(esperas);
  }
  // Si el audio quedó dormido, se despierta con cualquier toque
  ['touchend', 'click'].forEach(function (ev) { document.addEventListener(ev, function () { try { if (actx && actx.state !== 'running') actx.resume(); } catch (e) {} }, { passive: true }); });

  var musicaCache = { blob: null, buf: null };
  function prepMusica() {
    if (!musicaBlob || !actx) return Promise.resolve(null);
    if (musicaCache.blob === musicaBlob && musicaCache.buf) return Promise.resolve(musicaCache.buf);
    return musicaBlob.arrayBuffer().then(function (ab) {
      return new Promise(function (ok) { actx.decodeAudioData(ab, function (b) { musicaCache = { blob: musicaBlob, buf: b }; ok(b); }, function () { ok(null); }); });
    }).catch(function () { return null; });
  }

  function mostrarResultado() {
    var fv = $('vid-final'); fv.src = URL.createObjectURL(editado); fv.play().catch(function () {});
    $('dl-msg').hidden = true; $('c-msg').hidden = true; $('c-nombre').value = ''; $('c-wa').value = ''; $('c-acepta').checked = false;
    ver('resultado');
  }

  // ===== Descargas: en iPhone se abre "Compartir" (Guardar video va a Fotos); en Android se descarga directo =====
  var esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  function nombreBase() { var d = new Date(); return 'giro360-' + d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0') + '-' + String(d.getHours()).padStart(2, '0') + String(d.getMinutes()).padStart(2, '0') + String(d.getSeconds()).padStart(2, '0'); }
  function bajar(blob, nombre, msgEl) {
    msgEl.hidden = true;
    function directo() {
      var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = nombre;
      document.body.appendChild(a); a.click(); a.remove();
      msgEl.className = 'msg ok'; msgEl.textContent = 'Descargando… queda en Descargas del celular.'; msgEl.hidden = false;
    }
    var f = null; try { f = new File([blob], nombre, { type: blob.type || 'video/mp4' }); } catch (e) {}
    if (esIOS && f && navigator.canShare && navigator.canShare({ files: [f] })) {
      navigator.share({ files: [f] }).then(function () { msgEl.className = 'msg ok'; msgEl.textContent = 'Listo.'; msgEl.hidden = false; })
        .catch(function (e) { if (e && e.name === 'AbortError') return; directo(); });
    } else directo();
  }
  $('dl-editado').onclick = function () { if (editado) bajar(editado, nombreBase() + '-editado.' + extEditado, $('dl-msg')); };
  $('dl-original').onclick = function () {
    if (!original) return;
    var ext = (original.name.split('.').pop() || 'mp4').toLowerCase(); if (['mp4', 'webm', 'mov'].indexOf(ext) < 0) ext = 'mp4';
    bajar(original, nombreBase() + '-original.' + ext, $('dl-msg'));
  };

  // ===== Clientes (nombre + WhatsApp): se guardan en LocalPro =====
  var URL_CLIENTE = document.body.dataset.cliente;
  function soloNum(s) { return (s || '').replace(/\D/g, ''); }
  function pendientes() { try { return JSON.parse(localStorage.getItem('giro360-pendientes') || '[]'); } catch (e) { return []; } }
  function enviar(fila) {
    return fetch(URL_CLIENTE, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(fila) })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) { var e = new Error(j.message || 'error'); e.status = r.status; throw e; } return j; }); });
  }
  // Si no hubo señal, se reintenta solo después
  function reintentar() {
    var l = pendientes(); if (!l.length || !navigator.onLine) return;
    var f = l[0];
    enviar(f).then(function () { var r = pendientes(); r.shift(); try { localStorage.setItem('giro360-pendientes', JSON.stringify(r)); } catch (e) {} reintentar(); })
      .catch(function (e) { if (e.status === 422) { var r = pendientes(); r.shift(); try { localStorage.setItem('giro360-pendientes', JSON.stringify(r)); } catch (x) {} } });
  }
  setInterval(reintentar, 30000); window.addEventListener('online', reintentar); setTimeout(reintentar, 3000);

  $('form-cliente').onsubmit = function (e) {
    e.preventDefault();
    var n = $('c-nombre').value.trim(), w = soloNum($('c-wa').value), m = $('c-msg');
    function error(t, el) { m.className = 'msg err'; m.textContent = t; m.hidden = false; if (el) el.focus(); }
    if (n.length < 2) return error('Escriba el nombre.', $('c-nombre'));
    if (w.length < 7) return error('Escriba un WhatsApp válido (mínimo 7 números).', $('c-wa'));
    if (!$('c-acepta').checked) return error('Para guardar debe aceptar el tratamiento de datos.', $('c-acepta'));
    var fila = { nombre: n.slice(0, 80), whatsapp: w.slice(0, 15), evento: (cfg.texto || '').slice(0, 60), efecto: cfg.efecto, acepta: 1 };
    $('c-guardar').disabled = true;
    enviar(fila).then(function () { ver('inicio'); aviso('Datos de ' + n + ' guardados.', true); })
      .catch(function (er) {
        if (er.status === 422) { error(er.message); return; }
        // Sin señal: queda en el celular y se envía apenas haya internet
        var l = pendientes(); l.push(fila); try { localStorage.setItem('giro360-pendientes', JSON.stringify(l)); } catch (x) {}
        ver('inicio'); aviso('Datos de ' + n + ' guardados. Se envían solos apenas haya señal.', true);
      })
      .then(function () { $('c-guardar').disabled = false; });
  };
  $('btn-otro').onclick = function () { ver('inicio'); };

  // Aviso en la pantalla de inicio
  function aviso(t, ok) {
    var m = document.getElementById('aviso');
    if (!m) { m = document.createElement('div'); m.id = 'aviso'; $('v-inicio').insertBefore(m, $('v-inicio').firstChild); }
    m.className = 'msg ' + (ok ? 'ok' : 'err'); m.textContent = t; m.hidden = false;
    clearTimeout(aviso.t); aviso.t = setTimeout(function () { m.hidden = true; }, 6000);
  }
})();
</script>
@endverbatim
</body>
</html>
