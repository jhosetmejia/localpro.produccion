<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f0c">
<title>Audio a MP3 · Giro 360 · LocalProo</title>
<meta name="description" content="Saque el audio de un video del celular en MP3 y úselo como canción del video 360.">
<link rel="icon" href="/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
@include('giro360._estilos')
</head>
<body>
<div class="app">
  <header>
    <a class="brand" href="/360" style="color:inherit;text-decoration:none"><i>360</i>Giro 360</a>
    <a class="chip" href="/360" style="text-decoration:none">← Volver al 360</a>
  </header>

  <section class="card">
    <h2>Audio a MP3</h2>
    <p class="note">Elija un video guardado en el celular (por ejemplo, uno que guardó en TikTok con Compartir → Guardar video). Se convierte aquí mismo, sin subirlo a internet.</p>
    <button class="btn big" id="elegir" type="button">Elegir video</button>
    <input type="file" id="archivo" accept="video/*,audio/*" class="sr" tabindex="-1">
    <div id="prog-wrap" hidden><div class="bar"><i id="prog"></i></div><p class="note" id="estado">Convirtiendo…</p></div>
    <div id="listo" hidden style="display:flex;flex-direction:column;gap:12px">
      <div class="pick"><div class="th">♫</div><div class="tx"><div id="nombre">audio.mp3</div><small id="dur"></small></div></div>
      <audio id="audio" controls style="width:100%"></audio>
      <button class="btn pri" id="mandar" type="button">Mandar audio a opciones del 360</button>
      <button class="btn" id="bajar" type="button">Descargar MP3</button>
    </div>
    <div class="msg" id="msg" hidden></div>
  </section>

  <footer>Hecho con <a href="https://localproo.com" target="_blank" rel="noopener">LocalProo</a> · use solo audios que tenga permiso de usar</footer>
</div>

@verbatim
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var blob = null, nom = 'audio', actx = null;
  function aviso(t, ok) { var m = $('msg'); m.className = 'msg ' + (ok ? 'ok' : 'err'); m.textContent = t; m.hidden = false; }

  // Misma base del celular que usa la página del 360
  function idb() {
    return new Promise(function (ok, mal) {
      try { var r = indexedDB.open('giro360', 1); r.onupgradeneeded = function () { r.result.createObjectStore('k'); }; r.onsuccess = function () { ok(r.result); }; r.onerror = mal; } catch (e) { mal(e); }
    });
  }
  function leer(d, k) { return new Promise(function (ok) { var r = d.transaction('k').objectStore('k').get(k); r.onsuccess = function () { ok(r.result); }; r.onerror = function () { ok(null); }; }); }

  function cargarLame() {
    if (window.lamejs) return Promise.resolve();
    return new Promise(function (ok, mal) { var s = document.createElement('script'); s.src = '/js/lame.min.js'; s.onload = ok; s.onerror = mal; document.head.appendChild(s); });
  }
  function aMp3(buf, progreso) {
    return new Promise(function (ok) {
      var canales = Math.min(2, buf.numberOfChannels), enc = new lamejs.Mp3Encoder(canales, buf.sampleRate, 160);
      function pcm(ch) { var d = buf.getChannelData(ch), o = new Int16Array(d.length); for (var i = 0; i < d.length; i++) { var v = Math.max(-1, Math.min(1, d[i])); o[i] = v < 0 ? v * 32768 : v * 32767; } return o; }
      var izq = pcm(0), der = canales > 1 ? pcm(1) : null, partes = [], i = 0, paso = 1152 * 60;
      (function tanda() {
        var fin = Math.min(izq.length, i + paso);
        for (; i < fin; i += 1152) {
          var m = der ? enc.encodeBuffer(izq.subarray(i, i + 1152), der.subarray(i, i + 1152)) : enc.encodeBuffer(izq.subarray(i, i + 1152));
          if (m.length) partes.push(new Uint8Array(m));
        }
        progreso(i / izq.length);
        if (i < izq.length) return setTimeout(tanda, 0);
        var u = enc.flush(); if (u.length) partes.push(new Uint8Array(u));
        ok(new Blob(partes, { type: 'audio/mpeg' }));
      })();
    });
  }

  $('elegir').onclick = function () { try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); actx.resume(); } catch (e) {} $('archivo').click(); };
  $('archivo').onchange = function () {
    var file = this.files[0]; this.value = ''; if (!file) return;
    $('msg').hidden = true; $('listo').hidden = true; $('prog-wrap').hidden = false; $('prog').style.width = '0'; $('estado').textContent = 'Leyendo el video…';
    $('elegir').disabled = true;
    nom = (file.name || 'audio').replace(/\.[^.]+$/, '').slice(0, 40) || 'audio';
    try { if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
    Promise.all([cargarLame(), file.arrayBuffer()]).then(function (r) {
      return new Promise(function (ok, mal) { actx.decodeAudioData(r[1], ok, mal); });
    }).then(function (buf) {
      $('estado').textContent = 'Convirtiendo a MP3…';
      $('dur').textContent = Math.round(buf.duration) + ' segundos';
      return aMp3(buf, function (p) { $('prog').style.width = Math.round(p * 100) + '%'; });
    }).then(function (b) {
      blob = b; $('audio').src = URL.createObjectURL(b); $('nombre').textContent = nom + '.mp3';
      $('prog-wrap').hidden = true; $('listo').hidden = false;
    }).catch(function () {
      $('prog-wrap').hidden = true; aviso('No se pudo sacar el audio de ese archivo. Pruebe con otro video (MP4) o use Chrome.');
    }).then(function () { $('elegir').disabled = false; $('elegir').textContent = 'Elegir otro video'; });
  };

  // Mandar a las opciones del 360: queda como canción del evento en este celular y abre el 360
  $('mandar').onclick = function () {
    if (!blob) return;
    var b = this; b.disabled = true;
    idb().then(function (d) {
      return leer(d, 'cfg').then(function (cfg) {
        cfg = cfg || { efecto: 'clasico', texto: '', desde: 0 }; cfg.sinMusica = false; cfg.desde = 0;
        return new Promise(function (ok, mal) {
          var t = d.transaction('k', 'readwrite'), st = t.objectStore('k');
          st.put({ blob: blob, nombre: nom + '.mp3' }, 'musica'); st.put(cfg, 'cfg');
          t.oncomplete = ok; t.onerror = mal; t.onabort = mal;
        });
      });
    }).then(function () {
      aviso('Listo. Abriendo el 360 con esta canción…', true);
      location.href = '/360';
    }).catch(function () {
      b.disabled = false; aviso('Este navegador no dejó guardar el audio. Descárguelo y súbalo en el 360 con el botón Subir.');
    });
  };

  // Descargar: en iPhone abre Compartir (Guardar en Archivos); en Android descarga directo
  $('bajar').onclick = function () {
    if (!blob) return;
    var nombre = nom + '.mp3', esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    function directo() { var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = nombre; document.body.appendChild(a); a.click(); a.remove(); aviso('Descargando… queda en Descargas del celular.', true); }
    var f = null; try { f = new File([blob], nombre, { type: 'audio/mpeg' }); } catch (e) {}
    if (esIOS && f && navigator.canShare && navigator.canShare({ files: [f] })) navigator.share({ files: [f] }).catch(function (e) { if (!e || e.name !== 'AbortError') directo(); });
    else directo();
  };
})();
</script>
@endverbatim
</body>
</html>
