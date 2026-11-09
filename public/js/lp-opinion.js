/* LocalPro · botón "Opinar / reportar falla" para todo el sistema (panel y LocalProCut).
   - La persona cuenta qué falla o qué idea tiene (con captura opcional) → Admin → Opiniones y fallas.
   - Los errores de JavaScript que le salen se reportan solos (agrupados, sin datos personales).
   Uso: <script>window.LP_OPINION = { modulo: 'cut', tema: 'oscuro', boton: false, sesion: false }</script>
        <script src="/js/lp-opinion.js"></script>
   API: LPOpinion.abrir({ tipo, mensaje }) · LPOpinion.error(err, datos) · LPOpinion.contexto = () => ({ ... }) */
(function () {
  'use strict';
  if (window.LPOpinion) return;
  var C = window.LP_OPINION || {};
  var MODULO = C.modulo || 'panel';
  var OSCURO = C.tema === 'oscuro';
  var recientes = [];          // últimos errores (van pegados al reporte manual)
  var enviados = 0;            // errores automáticos enviados en esta página (máximo 6)
  var vistos = {};

  function token() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }
  function ruta() { return location.pathname; }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function contexto() {
    var d = {
      pagina: ruta(),
      pantalla: (screen && screen.width ? screen.width + 'x' + screen.height : '') + ' · ventana ' + innerWidth + 'x' + innerHeight + ' · dpr ' + (window.devicePixelRatio || 1),
      idioma: navigator.language,
      memoria: navigator.deviceMemory ? navigator.deviceMemory + ' GB' : undefined,
      nucleos: navigator.hardwareConcurrency || undefined,
      tactil: (navigator.maxTouchPoints || 0) > 0,
      app: (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true,
      conexion: navigator.connection ? (navigator.connection.effectiveType || '') : undefined,
      hora: new Date().toISOString(),
    };
    try { if (typeof api.contexto === 'function') d.modulo = api.contexto(); } catch (e) { d.modulo = 'no se pudo leer: ' + e.message; }
    return d;
  }

  function enviarJSON(url, cuerpo) {
    return fetch(url, {
      method: 'POST', credentials: 'same-origin', keepalive: true,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token(), 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(cuerpo),
    });
  }

  /* ---------- errores que se reportan solos ---------- */
  function ruido(msg, archivo) {
    msg = String(msg || '');
    if (!msg || msg === 'Script error.' || msg === 'Script error') return true;           // de otro dominio: no dice nada
    if (/ResizeObserver loop|Non-Error promise rejection captured|AbortError|The user aborted|cancelado|Load failed$|Failed to fetch$|NetworkError when attempting/i.test(msg)) return true;
    if (archivo && !/^\//.test(archivo) && archivo.indexOf(location.origin) !== 0) return true; // extensiones del navegador, anuncios, etc.
    return false;
  }
  function reportarError(msg, donde, pila, extra) {
    var clave = msg + '|' + donde;
    recientes.push({ msg: String(msg).slice(0, 300), donde: donde, cuando: new Date().toLocaleTimeString() });
    if (recientes.length > 8) recientes.shift();
    if (vistos[clave] || enviados >= 6) return;
    vistos[clave] = 1;
    try { var ss = JSON.parse(sessionStorage.getItem('lpoErr') || '{}'); if (ss[clave]) return; ss[clave] = 1; sessionStorage.setItem('lpoErr', JSON.stringify(ss)); } catch (e) { /* sin sessionStorage */ }
    enviados++;
    var datos = contexto();
    if (extra) datos.extra = extra;
    enviarJSON('/opinion/error', {
      mensaje: String(msg).slice(0, 1900), donde: donde ? String(donde).slice(0, 290) : null,
      pila: pila ? String(pila).slice(0, 5800) : null, modulo: MODULO, pagina: ruta(),
      datos: JSON.stringify(datos).slice(0, 19000),
    }).catch(function () {});
  }
  window.addEventListener('error', function (e) {
    if (!e || e.target && e.target !== window && !e.message) return; // fallas de carga de imágenes/videos: no son errores del código
    var archivo = e.filename || '';
    if (ruido(e.message, archivo)) return;
    var donde = archivo ? archivo.replace(location.origin, '').replace(/\?.*$/, '') + ':' + (e.lineno || 0) + ':' + (e.colno || 0) : '';
    reportarError(e.message, donde, e.error && e.error.stack);
  }, true);
  window.addEventListener('unhandledrejection', function (e) {
    var r = e && e.reason, msg = r && r.message ? r.message : String(r);
    var pila = r && r.stack ? String(r.stack) : '';
    var m = pila.match(/(https?:\/\/[^\s)]+):(\d+):(\d+)/);
    var archivo = m ? m[1] : '';
    if (ruido(msg, archivo || null)) return;
    reportarError('Promesa sin atender: ' + msg, archivo ? archivo.replace(location.origin, '').replace(/\?.*$/, '') + ':' + m[2] : '', pila);
  });

  /* ---------- estilos ---------- */
  var V = OSCURO
    ? { f: '#151a17', f2: '#0b0d0c', b: '#2b332e', t: '#f3f7f4', m: '#9aa6a0', v: '#a6f34a', vt: '#0b0d0c', chip: '#1f2622' }
    : { f: '#ffffff', f2: '#f5f8f6', b: '#dfe7e1', t: '#151a17', m: '#5b665e', v: '#16b84e', vt: '#ffffff', chip: '#f5f8f6' };
  var css = ''
    + '.lpo-fab{position:fixed;left:14px;bottom:calc(env(safe-area-inset-bottom,0px) + 14px);z-index:1040;border:0;border-radius:999px;background:#0b0d0c;color:#fff;font:700 .85rem/1 "Plus Jakarta Sans",Figtree,system-ui,sans-serif;padding:.65rem .95rem;box-shadow:0 8px 24px rgba(0,0,0,.25);display:flex;align-items:center;gap:.4rem;cursor:pointer;opacity:.92}'
    + '.lpo-fab:hover{opacity:1}.lpo-fab b{color:#a6f34a}'
    + '@media (max-width:640px){.lpo-fab span{display:none}.lpo-fab{padding:.62rem .7rem;font-size:1.05rem}}'
    // En el computador el menú lateral (240 px) tiene abajo la cuenta: el botón va justo al lado, sin taparla
    + '@media (min-width:992px){body.sb-nav-fixed:not(.sb-sidenav-toggled) .lpo-fab{left:254px}}'
    + '@media print{.lpo-fab{display:none!important}}'
    + '.lpo-modal{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;display:flex;align-items:flex-end;justify-content:center}'
    + '@media (min-width:700px){.lpo-modal{align-items:center}}'
    + '.lpo-hoja{position:relative;background:' + V.f + ';color:' + V.t + ';border:1px solid ' + V.b + ';border-radius:22px 22px 0 0;width:100%;max-width:480px;max-height:92dvh;overflow-y:auto;padding:18px 18px calc(env(safe-area-inset-bottom,0px) + 18px);font-family:"Plus Jakarta Sans",Figtree,system-ui,sans-serif;box-shadow:0 20px 50px rgba(0,0,0,.35)}'
    + '@media (min-width:700px){.lpo-hoja{border-radius:22px}}'
    + '.lpo-hoja h3{margin:0 44px .3rem 0;font-size:1.1rem;font-weight:800}'
    + '.lpo-hoja p{margin:.2rem 0 .8rem;color:' + V.m + ';font-size:.88rem}'
    + '.lpo-x{position:absolute;top:10px;right:10px;width:36px;height:36px;border-radius:50%;border:1px solid ' + V.b + ';background:' + V.f2 + ';color:' + V.t + ';font-size:1.1rem;line-height:1;cursor:pointer}'
    + '.lpo-tipos{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-bottom:.7rem}'
    + '.lpo-tipos button{border:1.5px solid ' + V.b + ';background:' + V.chip + ';color:' + V.t + ';border-radius:14px;padding:.6rem .3rem;font-family:inherit;font-weight:700;font-size:.8rem;line-height:1.15;display:flex;flex-direction:column;align-items:center;gap:.25rem;cursor:pointer}'
    + '.lpo-tipos button i{font-style:normal;font-size:1.35rem}'
    + '.lpo-tipos button.on{border-color:' + V.v + ';box-shadow:0 0 0 2px ' + V.v + '33}'
    + '.lpo-hoja textarea,.lpo-hoja input[type=text]{width:100%;box-sizing:border-box;background:' + V.f2 + ';border:1px solid ' + V.b + ';color:' + V.t + ';border-radius:12px;padding:.65rem;font-family:inherit;font-weight:500;font-size:.95rem;line-height:1.4}'
    + '.lpo-hoja textarea{min-height:110px;resize:vertical}'
    + '.lpo-lbl{font-size:.78rem;font-weight:700;color:' + V.m + ';margin:.7rem 0 .3rem}'
    + '.lpo-foto{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}'
    + '.lpo-foto label{border:1px dashed ' + V.b + ';border-radius:12px;padding:.55rem .8rem;font-weight:700;font-size:.85rem;cursor:pointer}'
    + '.lpo-foto img{height:54px;max-width:160px;object-fit:cover;border-radius:8px;border:1px solid ' + V.b + '}'
    + '.lpo-foto button{border:0;background:none;color:' + V.m + ';font-size:.8rem;cursor:pointer;text-decoration:underline}'
    + '.lpo-fila{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;flex-wrap:wrap}'
    + '.lpo-btn{border:0;border-radius:999px;padding:.7rem 1.2rem;font-family:inherit;font-weight:800;font-size:.92rem;line-height:1;background:' + V.v + ';color:' + V.vt + ';cursor:pointer}'
    + '.lpo-btn:disabled{opacity:.6}'
    + '.lpo-sec{border:1px solid ' + V.b + ';border-radius:999px;padding:.65rem 1rem;font-family:inherit;font-weight:700;font-size:.85rem;line-height:1;background:' + V.f2 + ';color:' + V.t + ';cursor:pointer}'
    + '.lpo-nota{font-size:.76rem;color:' + V.m + ';margin-top:.6rem}'
    + '.lpo-ok{text-align:center;padding:1.2rem .5rem}.lpo-ok i{font-style:normal;font-size:2.6rem;display:block;margin-bottom:.4rem}'
    + '.lpo-err{color:#e0336f;font-weight:700;font-size:.85rem;margin-top:.5rem}'
    + '.lpo-mias{display:flex;flex-direction:column;gap:.5rem}'
    + '.lpo-mia{border:1px solid ' + V.b + ';border-radius:14px;padding:.6rem .75rem;font-size:.86rem}'
    + '.lpo-mia small{color:' + V.m + ';font-weight:700}'
    + '.lpo-mia .r{margin-top:.4rem;padding:.45rem .6rem;border-radius:10px;background:' + V.f2 + ';border-left:3px solid ' + V.v + '}'
    + '.lpo-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;opacity:0}';
  function estilos() {
    if (document.getElementById('lpoCss')) return;
    var s = document.createElement('style'); s.id = 'lpoCss'; s.textContent = css; document.head.appendChild(s);
  }

  /* ---------- ventana ---------- */
  var TIPOS = [['falla', '🐞', 'Algo falla'], ['idea', '💡', 'Tengo una idea'], ['otro', '💬', 'Otra cosa']];
  var AYUDA = {
    falla: 'Cuéntenos qué estaba haciendo, qué esperaba que pasara y qué pasó. Si puede, agregue una captura de pantalla.',
    idea: '¿Qué le gustaría que LocalPro hiciera o hiciera mejor?',
    otro: 'Escríbanos lo que quiera: dudas, comentarios, felicitaciones…',
  };
  var foto = null, fotoUrl = null;

  function cerrar(m) { if (fotoUrl) { URL.revokeObjectURL(fotoUrl); fotoUrl = null; } foto = null; m.remove(); document.removeEventListener('keydown', m._esc); }

  function abrir(op) {
    op = op || {};
    estilos();
    var viejo = document.querySelector('.lpo-modal'); if (viejo) cerrar(viejo);
    var tipo = op.tipo || 'falla';
    var m = document.createElement('div');
    m.className = 'lpo-modal';
    m.innerHTML = '<div class="lpo-hoja" role="dialog" aria-modal="true" aria-label="Opinar">'
      + '<button type="button" class="lpo-x" aria-label="Cerrar">✕</button>'
      + '<h3>¿Cómo le está yendo con LocalPro?</h3>'
      + '<p>Lo leemos todo y con eso vamos mejorando. ' + (C.sesion ? '' : 'No necesita cuenta.') + '</p>'
      + '<div class="lpo-cuerpo"></div></div>';
    document.body.appendChild(m);
    var cuerpo = m.querySelector('.lpo-cuerpo');
    m.querySelector('.lpo-x').onclick = function () { cerrar(m); };
    m.addEventListener('click', function (e) { if (e.target === m) cerrar(m); });
    m._esc = function (e) { if (e.key === 'Escape') cerrar(m); };
    document.addEventListener('keydown', m._esc);

    function formulario() {
      cuerpo.innerHTML = '<div class="lpo-tipos">' + TIPOS.map(function (t) { return '<button type="button" data-t="' + t[0] + '" class="' + (t[0] === tipo ? 'on' : '') + '"><i>' + t[1] + '</i>' + t[2] + '</button>'; }).join('') + '</div>'
        + '<textarea maxlength="3000" placeholder="' + esc(AYUDA[tipo]) + '"></textarea>'
        + '<div class="lpo-lbl">Captura de pantalla (opcional)</div>'
        + '<div class="lpo-foto"><label>📷 Agregar imagen<input type="file" accept="image/*" hidden></label><span data-prev></span></div>'
        + (C.sesion ? '' : '<div class="lpo-lbl">Su WhatsApp o correo (opcional, para contarle cuando lo arreglemos)</div><input type="text" maxlength="120" data-contacto autocomplete="tel">')
        + '<input type="text" name="web" class="lpo-hp" tabindex="-1" autocomplete="off" aria-hidden="true">'
        + '<div class="lpo-err" hidden></div>'
        + '<div class="lpo-fila">' + (C.sesion ? '<button type="button" class="lpo-sec" data-mias>Mis reportes</button>' : '') + '<button type="button" class="lpo-btn" data-enviar>Enviar</button></div>'
        + '<div class="lpo-nota">Junto con su mensaje enviamos datos técnicos (navegador, tamaño de pantalla y errores recientes de esta página) para encontrar la falla más rápido. Sus videos y archivos no se envían.</div>';
      var ta = cuerpo.querySelector('textarea');
      if (op.mensaje) ta.value = op.mensaje;
      setTimeout(function () { ta.focus(); }, 50);
      cuerpo.querySelector('.lpo-tipos').onclick = function (e) {
        var b = e.target.closest('[data-t]'); if (!b) return;
        tipo = b.dataset.t;
        [].forEach.call(cuerpo.querySelectorAll('.lpo-tipos button'), function (x) { x.classList.toggle('on', x === b); });
        ta.placeholder = AYUDA[tipo];
      };
      var inp = cuerpo.querySelector('.lpo-foto input'), prev = cuerpo.querySelector('[data-prev]');
      function pintarFoto() {
        if (fotoUrl) { URL.revokeObjectURL(fotoUrl); fotoUrl = null; }
        if (!foto) { prev.innerHTML = ''; return; }
        fotoUrl = URL.createObjectURL(foto);
        prev.innerHTML = '<img alt="" src="' + fotoUrl + '"> <button type="button" data-quitar>Quitar</button>';
        prev.querySelector('[data-quitar]').onclick = function () { foto = null; inp.value = ''; pintarFoto(); };
      }
      inp.onchange = function () {
        var f = inp.files && inp.files[0]; if (!f) return;
        achicar(f).then(function (g) { foto = g; pintarFoto(); });
      };
      pintarFoto();
      var err = cuerpo.querySelector('.lpo-err');
      var mias = cuerpo.querySelector('[data-mias]'); if (mias) mias.onclick = verMias;
      var btn = cuerpo.querySelector('[data-enviar]');
      btn.onclick = function () {
        var txt = ta.value.trim();
        err.hidden = true;
        if (txt.length < 4) { err.textContent = 'Cuéntenos un poquito qué pasó.'; err.hidden = false; ta.focus(); return; }
        btn.disabled = true; btn.textContent = 'Enviando…';
        var fd = new FormData();
        fd.append('tipo', tipo); fd.append('mensaje', txt); fd.append('modulo', MODULO); fd.append('pagina', ruta());
        fd.append('web', cuerpo.querySelector('.lpo-hp').value);
        var ct = cuerpo.querySelector('[data-contacto]'); if (ct && ct.value.trim()) fd.append('contacto', ct.value.trim());
        var datos = contexto(); datos.errores = recientes.slice();
        if (op.datos) datos.extra = op.datos;
        fd.append('datos', JSON.stringify(datos).slice(0, 29000));
        if (foto) fd.append('foto', foto, foto.name || 'captura.jpg');
        fetch('/opinion', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token(), 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { r: r, j: j }; }); })
          .then(function (x) {
            if (x.r.ok && x.j.ok) { listo(x.j.mensaje); return; }
            var msg = x.r.status === 429 ? 'Ha enviado varios mensajes seguidos. Espere un minuto y vuelva a intentar.'
              : (x.j.errors ? Object.values(x.j.errors)[0][0] : (x.j.message || 'No se pudo enviar. Revise su conexión e intente otra vez.'));
            err.textContent = msg; err.hidden = false; btn.disabled = false; btn.textContent = 'Enviar';
          })
          .catch(function () { err.textContent = 'No se pudo enviar. Revise su conexión a internet e intente otra vez.'; err.hidden = false; btn.disabled = false; btn.textContent = 'Enviar'; });
      };
    }
    function listo(msg) {
      foto = null;
      cuerpo.innerHTML = '<div class="lpo-ok"><i>' + (tipo === 'falla' ? '🛠️' : '🙌') + '</i><b>' + esc(msg || '¡Gracias!') + '</b>'
        + (C.sesion ? '<p>La respuesta le aparece aquí mismo, en <b>Mis reportes</b>.</p>' : '') + '</div>'
        + '<div class="lpo-fila">' + (C.sesion ? '<button type="button" class="lpo-sec" data-mias>Mis reportes</button>' : '') + '<button type="button" class="lpo-btn" data-cerrar>Listo</button></div>';
      cuerpo.querySelector('[data-cerrar]').onclick = function () { cerrar(m); };
      var b = cuerpo.querySelector('[data-mias]'); if (b) b.onclick = verMias;
    }
    function verMias() {
      cuerpo.innerHTML = '<p>Cargando…</p>';
      fetch('/opinion/mias', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) throw 0; return r.json(); })
        .then(function (lista) {
          cuerpo.innerHTML = '<div class="lpo-mias">' + (lista.length ? lista.map(function (o) {
            return '<div class="lpo-mia"><small>' + o.tipo + ' ' + esc(o.fecha) + ' · ' + esc(o.estado) + (o.resuelto ? ' ✅' : '') + '</small><div>' + esc(o.mensaje) + '</div>'
              + (o.respuesta ? '<div class="r"><b>LocalPro:</b> ' + esc(o.respuesta) + '</div>' : '') + '</div>';
          }).join('') : '<p>Todavía no nos ha escrito nada.</p>') + '</div>'
            + '<div class="lpo-fila"><button type="button" class="lpo-btn" data-nuevo>Escribir algo nuevo</button></div>';
          cuerpo.querySelector('[data-nuevo]').onclick = formulario;
        })
        .catch(function () { cuerpo.innerHTML = '<p>No se pudieron cargar sus reportes. Intente más tarde.</p><div class="lpo-fila"><button type="button" class="lpo-btn" data-nuevo>Volver</button></div>'; cuerpo.querySelector('[data-nuevo]').onclick = formulario; });
    }
    formulario();
  }

  // Las capturas del celular pesan: se achican a 1600 px en JPEG antes de enviarlas
  function achicar(f) {
    return new Promise(function (ok) {
      if (!/^image\//.test(f.type) || f.size < 1.2e6 || !window.createImageBitmap) return ok(f);
      createImageBitmap(f).then(function (bmp) {
        var k = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas'); c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        c.toBlob(function (b) { ok(b ? new File([b], 'captura.jpg', { type: 'image/jpeg' }) : f); }, 'image/jpeg', 0.85);
      }).catch(function () { ok(f); });
    });
  }

  function boton() {
    if (C.boton === false || document.querySelector('.lpo-fab')) return;
    estilos();
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'lpo-fab'; b.title = 'Opinar o reportar una falla';
    b.setAttribute('aria-label', 'Opinar o reportar una falla');
    b.innerHTML = '💬<span><b>Opinar</b> · ¿algo falla?</span>';
    b.onclick = function () { abrir(); };
    document.body.appendChild(b);
  }

  var api = {
    abrir: abrir,
    // Para errores que el código atrapa (ej. "no se pudo exportar"): se reportan igual, con datos del módulo
    error: function (err, datos) {
      var msg = err && err.message ? err.message : String(err);
      var pila = err && err.stack ? String(err.stack) : '';
      var m = pila.match(/(https?:\/\/[^\s)]+):(\d+):(\d+)/);
      reportarError(msg, m ? m[1].replace(location.origin, '').replace(/\?.*$/, '') + ':' + m[2] : (datos && datos.donde) || '', pila, datos || null);
    },
    contexto: null,
  };
  window.LPOpinion = api;
  // Latido del contador de visitas (Admin → Visitas y uso → "Ahora mismo") mientras la página está a la vista
  setInterval(function () {
    if (document.hidden) return;
    fetch('/visita/latido', { method: 'POST', credentials: 'same-origin', keepalive: true, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token() } }).catch(function () {});
  }, 120000);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boton); else boton();
})();
