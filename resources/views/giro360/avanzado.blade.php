<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f0c">
<title>Giro 360 (operador) · LocalProo</title>
<meta name="robots" content="noindex">
<meta name="description" content="Grabe su video 360 y llévelo editado con velocidad curva, logo y música del evento.">
<meta property="og:title" content="Giro 360 · LocalProo">
<meta property="og:description" content="Grabe su video 360 y llévelo editado al instante.">
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
@include('giro360._estilos')
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
      <div class="lbl">Logo</div>
      <div class="logo-fila">
        <div class="combo" id="lcombo">
          <button type="button" class="combo-btn lcombo-btn" id="lcombo-btn" aria-haspopup="listbox" aria-expanded="false"><span class="th" id="logo-th">◎</span><span class="nm" id="logo-nom">Sin logo</span></button>
          <div class="combo-pop" id="lcombo-pop" hidden><div class="combo-lista" id="lcombo-lista" role="listbox"></div></div>
        </div>
        <button class="btn nuevo" id="logo-btn" type="button">＋ Nuevo logo</button>
        <input type="file" id="in-logo" accept="image/*" class="sr" tabindex="-1">
        <button id="logo-quitar" type="button" hidden>Quitar</button>
      </div>
      <p class="note" style="margin-top:-4px">Desde el celular o la galería del computador. PNG con fondo transparente queda mejor.</p>
      <div class="msg" id="logo-estado" role="status" aria-live="polite" hidden></div>
      {{-- Lista de logos disponibles (la usa el selector) --}}
      <div class="listas" hidden>
        <button class="mini" type="button" data-logo="/img/giro360/halloween-2026.png" data-nombre="Halloween 2026">Halloween 2026</button>
        @foreach (\App\Http\Controllers\giro360Controller::logosLista() as $lg)
        <button class="mini" type="button" data-logo="/storage/{{ $lg['archivo'] }}" data-nombre="{{ $lg['nombre'] }}">{{ $lg['nombre'] }}</button>
        @endforeach
      </div>
      <div class="musica">
        <div class="lbl">Música</div>
        <div class="combo" id="combo">
          <button type="button" class="combo-btn" id="combo-btn" aria-haspopup="listbox" aria-expanded="false"><span id="combo-txt">Elegir canción</span><small id="combo-tema"></small></button>
          <div class="combo-pop" id="combo-pop" hidden>
            <input type="search" id="combo-q" placeholder="Buscar canción…" autocomplete="off" aria-label="Buscar canción">
            <div class="combo-lista" id="combo-lista" role="listbox"></div>
          </div>
        </div>
        <div class="row" hidden>
          <div class="field"><label class="sr" for="tema">Tema</label>
            <select id="tema">
              <option value="sistema">LocalProo</option>
              <option value="halloween">Halloween</option>
              <option value="fiesta">Fiesta</option>
              <option value="mis">Mis audios</option>
              <option value="ninguna">Sin música</option>
            </select></div>
          <div class="field"><label class="sr" for="cancion">Canción</label><select id="cancion"></select></div>
        </div>
        <div class="pick">
          <div class="th">♫</div>
          <div class="tx"><div id="musica-nom">Sin canción</div><small id="musica-sub">El video sale con esta canción de fondo</small></div>
          <div class="acc">
            <button class="mini" id="musica-oir" type="button" hidden>▶ Oír</button>
            <button class="mini" id="musica-btn" type="button">Subir audio</button>
            <button class="mini" id="mis-borrar" type="button" hidden>Borrar</button>
          </div>
          <input type="file" id="in-musica" accept="audio/*" class="sr" tabindex="-1">
        </div>
        <a class="mini" href="/360/audio" style="text-decoration:none;text-align:center">♫ Sacar el audio de un video (va a Mis audios)</a>
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

    <div class="card" id="logos-360">
      @php $neg360 = \App\Http\Controllers\giro360Controller::tienda360(); @endphp
      <div class="lbl">{{ $neg360 ? 'Logos y canciones de ' . $neg360->nombre : 'Logos y canciones guardados para todos los celulares' }}</div>
      @if ($neg360)
      <p class="note">Lo que suba aquí queda en su cuenta de LocalProo y sale en todos los celulares donde entre con su usuario.</p>
      @else
      <p class="note">Solo lo que sube el administrador queda aquí y sale para todas las personas que graben. Lo que sube cualquier otra persona queda solo en su celular y se borra a las 24 horas.</p>
      @endif
      @if (session('ok'))<div class="msg ok">{{ session('ok') }}</div>@endif
      @if ($errors->any())<div class="msg err">{{ $errors->first() }}</div>@endif
      @if (\App\Http\Controllers\giro360Controller::puedeSubir())
        @php $lgs = \App\Http\Controllers\giro360Controller::logosLista(true); @endphp
        @if ($lgs)
        <div class="list">
          @foreach ($lgs as $lg)
          <div style="align-items:center">
            <span style="display:flex;gap:10px;align-items:center;min-width:0"><img src="/storage/{{ $lg['archivo'] }}" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:8px;background:#0b0f0c;flex:none"><b style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lg['nombre'] }}</b></span>
            <form method="post" action="{{ route('giro360.logos.borrar', $lg['id']) }}">@csrf<button class="mini" type="submit">Quitar</button></form>
          </div>
          @endforeach
        </div>
        @endif
        @php $cns = \App\Http\Controllers\giro360Controller::cancionesLista(true); @endphp
        @if ($cns)
        <div class="list">
          @foreach ($cns as $cn)
          <div style="align-items:center">
            <span style="display:flex;gap:10px;align-items:center;min-width:0"><span style="width:40px;height:40px;border-radius:8px;background:#0b0f0c;flex:none;display:grid;place-items:center">🎵</span><b style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $cn['nombre'] }}</b></span>
            <form method="post" action="{{ route('giro360.canciones.borrar', $cn['id']) }}">@csrf<button class="mini" type="submit">Quitar</button></form>
          </div>
          @endforeach
        </div>
        @endif
      @else
        <p class="note">¿Alquila plataformas 360? <a href="{{ route('login') }}" style="color:var(--accent2)">Entre con su cuenta de LocalProo</a> (o <a href="{{ route('registro.alquiler') }}" style="color:var(--accent2)">créela gratis</a> marcando "Alquilo plataforma 360") y sus logos y canciones quedan guardados en todos sus celulares.</p>
      @endif
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

  <a class="btn ghost" id="v-mp3" href="/360/audio">♫ Sacar el audio de un video (MP3)</a>
  <footer>Hecho con <a href="https://localproo.com" target="_blank" rel="noopener">LocalProo</a> · el video no sale de su celular</footer>
</div>
@if (\App\Http\Controllers\giro360Controller::puedeSubir())
<script>window.ADMIN_360 = { url: @json(route('giro360.logos.subir')), musica: @json(route('giro360.canciones.subir')), token: @json(csrf_token()) };</script>
@endif
<script>window.CANCIONES_360 = {!! json_encode(\App\Http\Controllers\giro360Controller::cancionesJs(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};</script>
@verbatim
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var vistas = ['inicio', 'proceso', 'resultado'];
  function ver(n) { vistas.forEach(function (v) { $('v-' + v).hidden = v !== n; }); $('v-mp3').hidden = n !== 'inicio'; window.scrollTo(0, 0); }

  // ===== Curvas de velocidad (como las de CapCut): [momento del video 0..1, velocidad] =====
  var EFECTOS = [
    { id: 'ritmo', ritmo: true, nombre: 'Al ritmo de la canción', desc: 'Rápido donde la música sube', p: [[0, 1.5], [0.2, 3], [0.4, 0.6], [0.6, 2.5], [0.8, 0.5], [1, 1]] },
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

  // ===== Al ritmo de la canción: la velocidad sigue la fuerza de la música =====
  // Se mide la energía de la canción cada 50 ms (desde el segundo elegido): fuerte = rápido (x3), suave = lento (x0.35)
  var PASO = 0.05, ritmo = null;
  function analizarRitmo(buf, desde) {
    var sr = buf.sampleRate, ini = Math.floor(Math.min(desde || 0, Math.max(0, buf.duration - 2)) * sr);
    var fin = Math.min(buf.length, ini + Math.floor(90 * sr)), hop = Math.floor(PASO * sr);
    var chs = []; for (var c = 0; c < Math.min(2, buf.numberOfChannels); c++) chs.push(buf.getChannelData(c));
    var e = [];
    for (var i = ini; i + hop <= fin; i += hop) {
      var s2 = 0;
      for (var j = i; j < i + hop; j += 4) { var v = 0; for (var c2 = 0; c2 < chs.length; c2++) v += chs[c2][j]; s2 += v * v; }
      e.push(Math.sqrt(s2 / (hop / 4)));
    }
    if (e.length < 20) return null;
    function suavizar(a, k) { var o = new Array(a.length); for (var i = 0; i < a.length; i++) { var s = 0, n = 0; for (var j = Math.max(0, i - k); j <= Math.min(a.length - 1, i + k); j++) { s += a[j]; n++; } o[i] = s / n; } return o; }
    e = suavizar(suavizar(e, 5), 5);
    var ord = e.slice().sort(function (a, b) { return a - b; }), lo = ord[Math.floor(ord.length * 0.15)], hi = ord[Math.floor(ord.length * 0.92)];
    var r = new Float32Array(e.length);
    for (var k = 0; k < e.length; k++) { var x = Math.max(0, Math.min(1, (e[k] - lo) / ((hi - lo) || 1))); r[k] = 0.35 * Math.pow(3 / 0.35, Math.pow(x, 1.2)); }
    return r;
  }
  // Plan para un video de D segundos: cuánto dura editado y en qué punto del video va cada instante
  function planRitmo(D) {
    var r = ritmo, cum = new Float32Array(r.length + 1), total = 0;
    for (var i = 0; i < r.length; i++) { cum[i] = total; total += r[i] * PASO; }
    cum[r.length] = total;
    var esc = total < D ? D / total : 1, n = r.length;
    for (var k = 0; k <= r.length; k++) if (cum[k] * esc >= D) { n = k; break; }
    return { r: r, cum: cum, esc: esc, n: n, T: n * PASO };
  }
  var ritmoPend = 0;
  function actualizarRitmo() {
    var mio = ++ritmoPend;
    if (!musicaBlob) { ritmo = null; pintarEfectos(); return; }
    try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { return; }
    prepMusica().then(function (buf) {
      if (mio !== ritmoPend) return;
      ritmo = buf ? analizarRitmo(buf, cfg.desde) : null; pintarEfectos();
    });
  }

  // ===== Ajustes guardados en este celular (IndexedDB) =====
  var cfg = { efecto: 'clasico', texto: '', desde: 0 }, logoImg = null, logoBlob = null, musicaBlob = null, musicaNom = '';
  // Logos que la persona subió en este celular (cada uno dura 24 horas desde que lo subió)
  var misLogos = [];
  function usarMiLogo(lg) { ponerLogo(lg.blob, lg.nombre); guardar('logo', { blob: lg.blob, nombre: lg.nombre, propio: true, id: lg.id }); cfg.sinLogo = false; guardar('cfg', cfg); }
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
      var pts = [];
      if (ef.ritmo && ritmo) {
        var pl = planRitmo(10);
        for (var i = 0; i <= 40; i++) { var k = Math.min(pl.r.length - 1, Math.floor(i / 40 * pl.n)); pts.push((i / 40 * 100).toFixed(1) + ',' + (34 - Math.min(max, pl.r[k] * pl.esc) / max * 30).toFixed(1)); }
      } else for (var i = 0; i <= 40; i++) { var x = i / 40; pts.push((x * 100).toFixed(1) + ',' + (34 - velocidad(ef, x) / max * 30).toFixed(1)); }
      b.innerHTML = '<svg viewBox="0 0 100 38" preserveAspectRatio="none" aria-hidden="true"><line x1="0" x2="100" y1="' + (34 - 1 / max * 30) + '" y2="' + (34 - 1 / max * 30) + '" stroke="#29402f" stroke-dasharray="3 3" fill="none"/><polyline points="' + pts.join(' ') + '" fill="none" stroke="' + (ef.id === cfg.efecto ? '#a6f34a' : '#9fbaa8') + '" stroke-width="2.2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg><b></b><small></small>';
      b.querySelector('b').textContent = ef.nombre; b.querySelector('small').textContent = ef.desc;
      b.onclick = function () { cfg.efecto = ef.id; guardar('cfg', cfg); pintarEfectos(); };
      $('fxs').appendChild(b);
    });
    var ea = efectoActual();
    if (ea.ritmo) $('fx-dur').textContent = ritmo ? 'Con esta canción, un giro de 10 s queda de ' + Math.round(planRitmo(10).T) + ' s y la velocidad sigue la música.' : 'Elija una canción para usar este efecto (sin canción se usa 360 clásico).';
    else $('fx-dur').textContent = 'Un giro de 10 s queda de ' + Math.round(10 * factorDuracion(ea)) + ' s editado.';
  }

  // Marca cuál logo y cuál canción lista están puestos
  function marcarListos() {
    document.querySelectorAll('[data-logo]').forEach(function (b) { b.classList.toggle('on', !!logoBlob && b.dataset.nombre === $('logo-nom').textContent); });
  }
  function ponerLogo(blob, nombre) {
    logoBlob = blob; logoImg = null;
    if (!blob) { $('logo-th').style.backgroundImage = ''; $('logo-th').textContent = '◎'; $('logo-nom').textContent = 'Sin logo'; $('logo-quitar').hidden = true; marcarListos(); return; }
    var u = URL.createObjectURL(blob), im = new Image();
    im.onload = function () { logoImg = im; }; im.src = u;
    $('logo-th').style.backgroundImage = 'url(' + u + ')'; $('logo-th').textContent = ''; $('logo-nom').textContent = nombre || 'Logo'; marcarListos();
  }
  function ponerMusica(blob, nombre) {
    musicaBlob = blob; musicaNom = nombre || '';
    $('musica-nom').textContent = blob ? (nombre || 'Canción') : 'Sin canción';
    $('desde-wrap').hidden = !blob; $('musica-oir').hidden = !blob;
    if (window.etiquetaCombo) etiquetaCombo();
    marcarListos();
    parar();
    actualizarRitmo();
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
  // ===== Música: tema → canción. Las de LocalProo son originales (sin derechos); Mis audios son las que se suben o se mandan desde /360/audio
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
  var misAudios = [];
  // Lo que sube la gente queda solo en su celular por 24 horas; lo del administrador queda para todos
  var VIDA_PROPIO = 864e5;
  function marcarPropio() { if (!window.ADMIN_360) { cfg.propioT = Date.now(); guardar('cfg', cfg); } }
  function listaDe(tema) { return tema === 'mis' ? misAudios : (CATALOGO[tema] || []); }
  function pintarCanciones() {
    var tema = $('tema').value, sel = $('cancion'), l = listaDe(tema);
    $('tema').querySelector('[value=mis]').textContent = 'Mis audios (' + misAudios.length + ')';
    sel.innerHTML = '';
    if (tema === 'ninguna') { sel.disabled = true; sel.innerHTML = '<option>—</option>'; }
    else if (!l.length) { sel.disabled = true; var o = document.createElement('option'); o.textContent = 'Todavía no hay audios'; sel.appendChild(o); }
    else {
      sel.disabled = false;
      l.forEach(function (c) { var o = document.createElement('option'); o.value = c.id; o.textContent = c.nombre; sel.appendChild(o); });
      if (cfg.musicaSel && cfg.musicaSel.tema === tema) sel.value = cfg.musicaSel.id;
    }
    $('mis-borrar').hidden = !(tema === 'mis' && l.length);
    etiquetaCombo();
  }
  // Pone la canción elegida (descarga la de LocalProo una vez y queda guardada en el celular)
  function elegir(tema, id, silencioso) {
    $('tema').value = tema;
    if (tema === 'ninguna') { cfg.musicaSel = { tema: 'ninguna' }; cfg.sinMusica = true; guardar('cfg', cfg); ponerMusica(null); guardar('musica', null); pintarCanciones(); return Promise.resolve(); }
    var l = listaDe(tema), c = l.filter(function (x) { return x.id === id; })[0] || l[0];
    cfg.musicaSel = c ? { tema: tema, id: c.id } : { tema: tema }; cfg.sinMusica = false; guardar('cfg', cfg);
    pintarCanciones();
    if (!c) { ponerMusica(null); return Promise.resolve(); }
    var p = c.blob ? Promise.resolve(c.blob) : fetch(c.url).then(function (r) { if (!r.ok) throw 0; return r.blob(); });
    $('musica-nom').textContent = 'Cargando…';
    return p.then(function (bl) {
      if (!cfg.musicaSel || cfg.musicaSel.id !== c.id) return;
      ponerMusica(bl, c.nombre); guardar('musica', { blob: bl, nombre: c.nombre });
      if (!silencioso) aviso('Canción lista: ' + c.nombre + '.', true);
    }).catch(function () { $('musica-nom').textContent = musicaNom || 'Sin canción'; if (!silencioso) aviso('No se pudo cargar la canción. Revise la señal e intente otra vez.'); });
  }
  // ===== Buscador de canciones (todas, agrupadas por tema) =====
  var TEMAS = [['sistema', 'LocalProo'], ['halloween', 'Halloween'], ['fiesta', 'Fiesta'], ['mis', 'Mis audios']];
  function sinTilde(t) { return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
  function pintarCombo() {
    var q = sinTilde($('combo-q').value.trim()), l = $('combo-lista'), hay = 0, ms = cfg.musicaSel || {};
    l.innerHTML = '';
    TEMAS.forEach(function (t) {
      var items = listaDe(t[0]).filter(function (c) { return !q || sinTilde(c.nombre + ' ' + t[1]).indexOf(q) > -1; });
      if (!items.length && !(t[0] === 'mis' && !q)) return;
      var h = document.createElement('div'); h.className = 'combo-grupo'; h.textContent = t[1] + (t[0] === 'mis' ? ' (' + misAudios.length + ')' : ''); l.appendChild(h);
      if (!items.length) { var v = document.createElement('div'); v.className = 'combo-vacio'; v.textContent = 'Suba un audio o sáquelo de un video.'; l.appendChild(v); }
      items.forEach(function (c) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'combo-op'; b.setAttribute('role', 'option');
        var on = ms.tema === t[0] && ms.id === c.id; b.setAttribute('aria-selected', on); b.textContent = c.nombre;
        b.onclick = function () { cerrarCombo(); elegir(t[0], c.id); };
        l.appendChild(b); hay++;
      });
    });
    if (!q || 'sin musica'.indexOf(q) > -1) {
      var n = document.createElement('button'); n.type = 'button'; n.className = 'combo-op combo-sin'; n.textContent = 'Sin música';
      n.setAttribute('aria-selected', ms.tema === 'ninguna'); n.onclick = function () { cerrarCombo(); elegir('ninguna'); }; l.appendChild(n); hay++;
    }
    if (!hay) { var z = document.createElement('div'); z.className = 'combo-vacio'; z.textContent = 'No hay canciones con "' + $('combo-q').value.trim() + '".'; l.appendChild(z); }
  }
  window.etiquetaCombo = etiquetaCombo;
  function etiquetaCombo() {
    var ms = cfg.musicaSel || {}, t = TEMAS.filter(function (x) { return x[0] === ms.tema; })[0];
    $('combo-txt').textContent = ms.tema === 'ninguna' ? 'Sin música' : (musicaNom || 'Elegir canción');
    $('combo-tema').textContent = t ? t[1] : '';
  }
  function abrirCombo() { $('combo-pop').hidden = false; $('combo-btn').setAttribute('aria-expanded', 'true'); $('combo-q').value = ''; pintarCombo(); setTimeout(function () { $('combo-q').focus(); }, 30); }
  function cerrarCombo() { $('combo-pop').hidden = true; $('combo-btn').setAttribute('aria-expanded', 'false'); }
  $('combo-btn').onclick = function () { $('combo-pop').hidden ? abrirCombo() : cerrarCombo(); };
  $('combo-q').oninput = pintarCombo;
  $('combo-q').onkeydown = function (e) {
    if (e.key === 'Escape') { cerrarCombo(); $('combo-btn').focus(); }
    if (e.key === 'Enter') { e.preventDefault(); var b = $('combo-lista').querySelector('.combo-op'); if (b) b.click(); }
  };
  document.addEventListener('click', function (e) { if (!$('combo').contains(e.target)) cerrarCombo(); });

  $('tema').onchange = function () {
    var l = listaDe(this.value);
    if (this.value === 'mis' && !l.length) { pintarCanciones(); aviso('Todavía no hay audios. Toque "Subir audio" o saque el audio de un video.', true); return; }
    elegir(this.value, l[0] && l[0].id);
  };
  $('cancion').onchange = function () { elegir($('tema').value, this.value); };
  $('mis-borrar').onclick = function () {
    var id = $('cancion').value; misAudios = misAudios.filter(function (a) { return a.id !== id; }); guardar('misAudios', misAudios);
    if (misAudios.length) elegir('mis', misAudios[0].id); else elegir('halloween', 'hw-lp');
  };

  // Logos listos (originales de LocalProo, sin derechos de autor)
  function cargarListo(b, silencioso) {
    if (!b) return;
    var esLogo = !!b.dataset.logo, url = b.dataset.logo || b.dataset.cancion, nom = b.dataset.nombre, t = b.textContent;
    b.disabled = true; b.textContent = 'Cargando…';
    fetch(url).then(function (r) { if (!r.ok) throw 0; return r.blob(); })
      .then(function (bl) {
        if (esLogo) { ponerLogo(bl, nom); guardar('logo', { blob: bl, nombre: nom }); cfg.sinLogo = false; }
        guardar('cfg', cfg);
        if (!silencioso) aviso((esLogo ? 'Logo listo: ' : 'Canción lista: ') + nom + '.', true);
      })
      .catch(function () { if (!silencioso) aviso('No se pudo cargar. Revise la señal e intente otra vez.'); })
      .then(function () { b.disabled = false; b.textContent = t; });
  }
  document.querySelectorAll('[data-logo]').forEach(function (b) { b.onclick = function () { cargarListo(b); }; });

  $('logo-btn').onclick = function () { $('in-logo').click(); };
  // Selector de logos con miniatura: Sin logo, los de LocalProo y los guardados
  function pintarLogos() {
    var l = $('lcombo-lista'); l.innerHTML = '';
    function fila(nombre, src, on, accion) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'combo-op lop'; b.setAttribute('role', 'option'); b.setAttribute('aria-selected', on);
      var t = document.createElement('span'); t.className = 'th';
      if (src) t.style.backgroundImage = 'url("' + src + '")'; else t.textContent = '🚫';
      var n = document.createElement('span'); n.textContent = nombre;
      if (src && src.indexOf('/storage/') === 0) { var g = document.createElement('small'); g.className = 'nube'; g.textContent = '☁️ Guardado en la cuenta'; n.appendChild(g); }
      if (src && src.indexOf('blob:') === 0) { var m = document.createElement('small'); m.className = 'nube'; m.textContent = '📱 Solo en este celular (24 h)'; n.appendChild(m); }
      b.appendChild(t); b.appendChild(n);
      b.onclick = function () { cerrarLogos(); accion(); };
      l.appendChild(b);
    }
    fila('Sin logo', null, !logoBlob, function () { $('logo-quitar').click(); });
    document.querySelectorAll('.listas [data-logo]').forEach(function (b) {
      fila(b.dataset.nombre, b.dataset.logo, !!logoBlob && b.dataset.nombre === $('logo-nom').textContent, function () { cargarListo(b); });
    });
    misLogos.forEach(function (lg) {
      if (!lg.url) lg.url = URL.createObjectURL(lg.blob);
      fila(lg.nombre, lg.url, !!logoBlob && lg.nombre === $('logo-nom').textContent, function () { usarMiLogo(lg); });
    });
  }
  function abrirLogos() { pintarLogos(); $('lcombo-pop').hidden = false; $('lcombo-btn').setAttribute('aria-expanded', 'true'); }
  function cerrarLogos() { $('lcombo-pop').hidden = true; $('lcombo-btn').setAttribute('aria-expanded', 'false'); }
  $('lcombo-btn').onclick = function () { $('lcombo-pop').hidden ? abrirLogos() : cerrarLogos(); };
  document.addEventListener('click', function (e) { if (!$('lcombo').contains(e.target)) cerrarLogos(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarLogos(); });
  $('musica-btn').onclick = function () { $('in-musica').click(); };
  $('in-logo').onchange = function () {
    var f = this.files[0]; this.value = ''; if (!f) return;
    var nom = (f.name || 'Logo').replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').slice(0, 40) || 'Logo';
    var A = window.ADMIN_360;
    function aMisLogos() {
      var lg = { id: 'l' + Date.now(), nombre: nom, blob: f, t: Date.now() };
      misLogos = [lg].concat(misLogos.filter(function (x) { return x.nombre !== nom; })).slice(0, 10);
      guardar('misLogos', misLogos.map(function (x) { return { id: x.id, nombre: x.nombre, blob: x.blob, t: x.t }; }));
      return lg;
    }
    if (!A) usarMiLogo(aMisLogos());
    else { ponerLogo(f, nom); guardar('logo', { blob: f, nombre: nom }); cfg.sinLogo = false; guardar('cfg', cfg); }
    var est = $('logo-estado');
    function estado(t, cls) { est.className = 'msg ' + cls; est.textContent = t; est.hidden = false; }
    if (!A) { estado('📱 "' + nom + '" quedó en la lista de Logo de este celular por 24 horas. Para guardarlo en todos sus celulares, entre con su cuenta de LocalProo (marcada como "Alquilo plataforma 360") y vuelva a subirlo.', 'err'); return; }
    estado('⏳ Subiendo "' + nom + '" para todos los celulares…', '');
    var fd = new FormData(); fd.append('nombre', nom); fd.append('logo', f); fd.append('_token', A.token);
    fetch(A.url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw new Error(j.message || 'error'); return j; }); })
      .then(function (j) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'mini on'; b.dataset.logo = j.url; b.dataset.nombre = j.nombre; b.textContent = j.nombre;
        b.onclick = function () { cargarListo(b); };
        var fila = document.querySelector('.listas'); if (fila) fila.appendChild(b);
        estado('✅ "' + j.nombre + '" subido y guardado. Ya sale en las opciones de Logo de todos sus celulares (lo ve en la lista con ☁️).', 'ok');
      })
      .catch(function (e) { aMisLogos(); estado('❌ No se subió: ' + (e.message || 'revise la señal') + '. Quedó solo en este celular; toque ＋ Nuevo logo para intentar otra vez.', 'err'); });
  };
  $('in-musica').onchange = function () {
    var f = this.files[0]; this.value = ''; if (!f) return;
    var a = { id: 'm' + Date.now(), nombre: f.name.replace(/\.[^.]+$/, '').slice(0, 40) || 'Audio', blob: f };
    var A = window.ADMIN_360;
    if (!A || !A.musica) {
      misAudios.unshift(a); misAudios = misAudios.slice(0, 20); guardar('misAudios', misAudios); marcarPropio();
      elegir('mis', a.id); aviso('Audio agregado a Mis audios: queda solo en este celular y se borra a las 24 horas.', true);
      return;
    }
    // Administrador: se guarda en el sistema y sale para todos
    aviso('⏳ Subiendo "' + a.nombre + '" para todos los celulares…', true);
    var fd = new FormData(); fd.append('nombre', a.nombre); fd.append('cancion', f); fd.append('_token', A.token);
    fetch(A.musica, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw new Error(r.status === 413 ? 'el audio pesa demasiado para el servidor' : (j.message || 'error')); return j; }); })
      .then(function (j) {
        CATALOGO.sistema.unshift({ id: j.id, nombre: j.nombre, url: j.url, blob: f });
        elegir('sistema', j.id); aviso('✅ "' + j.nombre + '" guardada: ya sale en Música → LocalProo en todos los celulares.', true);
      })
      .catch(function (e) { aviso('❌ No se subió la canción: ' + (e.message || 'revise la señal') + '.'); });
  };
  $('logo-quitar').onclick = function () { ponerLogo(null); guardar('logo', null); cfg.sinLogo = true; guardar('cfg', cfg); };
  $('texto').oninput = function () { cfg.texto = this.value; guardar('cfg', cfg); if (this.value.trim()) marcarPropio(); };
  var desdeT = 0;
  $('musica-desde').oninput = function () { cfg.desde = Math.max(0, parseFloat(this.value.replace(',', '.')) || 0); guardar('cfg', cfg); clearTimeout(desdeT); desdeT = setTimeout(actualizarRitmo, 500); };

  Promise.all([leer('cfg'), leer('logo'), leer('musica'), leer('misAudios'), leer('misLogos')]).then(function (r) {
    // Mis logos: cada uno se borra a las 24 horas de subido
    misLogos = (Array.isArray(r[4]) ? r[4] : []).filter(function (x) { return x && x.blob && Date.now() - (x.t || 0) < VIDA_PROPIO; });
    // Logo propio que se subió antes de existir la lista: se agrega
    if (r[1] && r[1].propio && !misLogos.some(function (x) { return x.nombre === r[1].nombre; })) misLogos.push({ id: 'l' + Date.now(), nombre: r[1].nombre, blob: r[1].blob, t: Date.now() });
    guardar('misLogos', misLogos);
    if (r[1] && r[1].propio && r[1].id && !misLogos.some(function (x) { return x.id === r[1].id; })) { r[1] = null; guardar('logo', null); }
    // Pasadas 24 horas, se borra lo que subió la persona (logo propio, audios y texto)
    if (r[0] && r[0].propioT && Date.now() - r[0].propioT > VIDA_PROPIO) {
      delete r[0].propioT; r[0].texto = '';
      if (r[0].musicaSel && r[0].musicaSel.tema === 'mis') { delete r[0].musicaSel; r[2] = null; guardar('musica', null); }
      r[3] = []; guardar('misAudios', []); guardar('cfg', r[0]);
    }
    misAudios = Array.isArray(r[3]) ? r[3] : [];
    // Audio que se mandó antes de existir Mis audios: se agrega a la lista
    if (r[2] && r[2].blob && !misAudios.length && !/LocalProo|Bach/.test(r[2].nombre || '')) {
      misAudios.push({ id: 'm' + Date.now(), nombre: (r[2].nombre || 'Audio').replace(/\.mp3$/i, ''), blob: r[2].blob }); guardar('misAudios', misAudios);
    }
    if (r[0]) { cfg = Object.assign(cfg, r[0]); $('texto').value = cfg.texto || ''; $('musica-desde').value = cfg.desde || 0; }
    if (r[1]) ponerLogo(r[1].blob, r[1].nombre);
    if (r[2]) ponerMusica(r[2].blob, r[2].nombre);
    pintarEfectos();
    var ms = cfg.musicaSel;
    if (ms && ms.tema) { $('tema').value = ms.tema; pintarCanciones(); if (ms.tema === 'mis' && ms.id && r[2] && !misAudios.some(function (a) { return a.id === ms.id; })) elegir('mis'); }
    else if (r[2]) { var enMis = misAudios.filter(function (a) { return r[2].nombre && a.nombre === r[2].nombre.replace(/\.mp3$/i, ''); })[0]; if (enMis) { cfg.musicaSel = { tema: 'mis', id: enMis.id }; $('tema').value = 'mis'; } pintarCanciones(); }
    else if (!cfg.sinMusica) elegir('halloween', 'hw-lp', true);
    else { $('tema').value = 'ninguna'; pintarCanciones(); }
    // Temporada de Halloween: si este celular no tiene logo, se pone el de Halloween solo
    if (new Date() < new Date(2026, 10, 3) && !r[1] && !cfg.sinLogo) cargarListo(document.querySelector('[data-logo]'), true);
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
      if (ef.ritmo && !(ritmo && musicaBlob)) ef = EFECTOS.filter(function (e) { return e.id === 'clasico'; })[0];
      var plan = ef.ritmo ? planRitmo(dur) : null;
      var durFinal = plan ? plan.T : dur * factorDuracion(ef);
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
        var fin = false, rateAnt = 0, tIni = 0;
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
          var x = dur ? v.currentTime / dur : 0, r;
          if (plan) {
            // Velocidad según la música en este instante, con corrección para no desfasarse
            var tau = (performance.now() - tIni) / 1000, k = Math.min(plan.r.length - 1, Math.floor(tau / PASO));
            var esperado = Math.min(dur, plan.cum[k] * plan.esc), corr = Math.max(0.6, Math.min(1.6, 1 + (esperado - v.currentTime) * 1.2));
            r = Math.max(0.25, Math.min(4, plan.r[k] * plan.esc * corr));
          } else r = Math.max(0.25, Math.min(4, velocidad(ef, x)));
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
        v.playbackRate = Math.max(0.25, Math.min(4, plan ? plan.r[0] * plan.esc : velocidad(ef, 0)));
        v.play().then(function () {
          // La canción arranca justo con el video para que queden sincronizados
          tIni = performance.now();
          if (fuente) {
            var t0 = actx.currentTime;
            ganancia.gain.setValueAtTime(1, t0);
            ganancia.gain.setValueAtTime(1, t0 + Math.max(0, durFinal - 1.2));
            ganancia.gain.linearRampToValueAtTime(0, t0 + durFinal);
            fuente.start(t0, Math.min(cfg.desde || 0, Math.max(0, fuente.buffer.duration - 1)));
          }
          requestAnimationFrame(cuadro);
        }).catch(function () { fin = true; try { rec.stop(); } catch (e) {} ver('inicio'); aviso('El celular no dejó reproducir el video. Intente de nuevo.'); });
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

  function celularOk(t) {
    t = String(t || '').trim(); var intl = /^(\+|00)/.test(t), n = t.replace(/\D/g, '').replace(/^00/, '');
    if (!intl && n.length === 10 && n[0] === '3') return true;
    if (n.length === 12 && n.indexOf('573') === 0) return true;
    if (n.indexOf('57') === 0) return false;
    return intl && n.length >= 8 && n.length <= 15;
  }
  $('form-cliente').onsubmit = function (e) {
    e.preventDefault();
    var n = $('c-nombre').value.trim(), w = soloNum($('c-wa').value), m = $('c-msg');
    function error(t, el) { m.className = 'msg err'; m.textContent = t; m.hidden = false; if (el) el.focus(); }
    if (n.length < 2) return error('Escriba el nombre.', $('c-nombre'));
    if (!celularOk($('c-wa').value)) return error('Escriba un celular válido: 10 números que empiecen por 3 (ej: 300 123 4567). Otro país: con + y el indicativo.', $('c-wa'));
    if (!$('c-acepta').checked) return error('Para guardar debe aceptar el tratamiento de datos.', $('c-acepta'));
    var fila = { nombre: n.slice(0, 80), whatsapp: $('c-wa').value.trim().slice(0, 25), evento: (cfg.texto || '').slice(0, 60), efecto: cfg.efecto, acepta: 1 };
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
