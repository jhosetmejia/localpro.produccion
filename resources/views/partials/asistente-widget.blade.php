{{-- KIT ASISTENTE IA (Jhoset Mejia - DiCreWeb): burbuja de ayuda con botones + IA gratis (Groq) que aprende.
     LocalProo: responde con botones y por palabras clave (precios, prueba gratis, cuenta, clave, funciones, asesor).
     Si no entiende, manda al WhatsApp de soporte (Admin → Planes y cobros). --}}
{{-- 👉 PERSONALIZAR: nombre, color, WhatsApp, links de los botones --}}
@php
  $asDatos = [
      'wa' => preg_replace('/\D/', '', (string) \App\Models\Ajuste::valor('soporte_whatsapp')),
      'u' => [
          'inicio' => url('/'),
          'login' => route('login'),
          'registro' => route('registro'),
          'clave' => route('password.request'),
          'funciones' => route('funciones'),
          'blog' => route('blog.index'),
          'plan' => auth()->check() ? route('plan.index') : route('registro'),
          'aprende' => auth()->check() ? route('aprende.index') : route('blog.index'),
      ],
      'sesion' => auth()->check(),
  ];
@endphp
<style>
#pfn-as-btn{position:fixed;right:18px;bottom:18px;z-index:9000;border:1px solid rgba(255,255,255,.25);border-radius:100px;background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c;font-weight:800;padding:.75rem 1.1rem;font-size:.95rem;box-shadow:0 8px 24px rgba(0,0,0,.45);cursor:pointer;margin:0;display:flex;align-items:center;gap:.4rem;text-transform:none;letter-spacing:0}
#pfn-as{position:fixed;right:18px;bottom:78px;z-index:9001;width:min(370px,calc(100vw - 24px));height:min(540px,calc(100vh - 110px));background:#121614;border:1px solid #26302a;border-radius:16px;box-shadow:0 18px 50px rgba(0,0,0,.6);display:none;flex-direction:column;overflow:hidden;font-family:inherit}
#pfn-as.abierto{display:flex}
#pfn-as .as-top{background:#0b0d0c;color:#fff;padding:.75rem 1rem;display:flex;justify-content:space-between;align-items:center;font-weight:700}
#pfn-as .as-top button{background:none;border:none;color:#fff;font-size:1.3rem;margin:0;padding:0 .2rem;cursor:pointer;box-shadow:none}
#pfn-as .as-msgs{flex:1;overflow-y:auto;padding:.8rem;display:flex;flex-direction:column;gap:.5rem}
#pfn-as .as-b{max-width:88%;padding:.55rem .8rem;border-radius:12px;font-size:.9rem;line-height:1.45;white-space:pre-line}
#pfn-as .as-bot{background:#1c2420;color:#eee;align-self:flex-start;border-top-left-radius:4px}
#pfn-as .as-yo{background:#2fd15a;color:#0b0d0c;align-self:flex-end;border-top-right-radius:4px}
#pfn-as .as-b a{color:#a6f34a;font-weight:700}
#pfn-as .as-chips{display:flex;flex-wrap:wrap;gap:.35rem;padding:0 .8rem .6rem}
#pfn-as .as-chips button{margin:0;padding:.35rem .65rem;font-size:.78rem;border-radius:100px;background:#1f1f23;color:#eee;border:1px solid #3a3a40;cursor:pointer;font-weight:600;text-transform:none;letter-spacing:0;box-shadow:none}
#pfn-as form{display:flex;border-top:1px solid #2a2a2e;margin:0}
#pfn-as input{flex:1;margin:0;border:none;background:#18181b;color:#fff;padding:.75rem .9rem;font-size:.9rem;border-radius:0}
#pfn-as form button{margin:0;border:none;border-radius:0;background:#2fd15a;color:#0b0d0c;padding:0 1rem;font-weight:700;cursor:pointer;box-shadow:none}
/* LocalProo: si sale el botón "Instalar LocalPro" (abajo a la derecha), la burbuja sube */
body:has(#lpInstalar) #pfn-as-btn{bottom:76px}
body:has(#lpInstalar) #pfn-as{bottom:136px}
#pfn-as .as-esc{font-size:.8rem;color:#9fb3a6;font-style:italic;padding:.2rem .3rem}
#pfn-as .as-esc i{display:inline-block;width:5px;height:5px;margin-left:2px;border-radius:50%;background:#9fb3a6;animation:asPunto 1s infinite}
#pfn-as .as-esc i:nth-child(2){animation-delay:.15s}#pfn-as .as-esc i:nth-child(3){animation-delay:.3s}
@keyframes asPunto{0%,60%,100%{opacity:.25;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}
#pfn-as form button[data-mic]{background:transparent;color:inherit;padding:0 .75rem;font-size:1.05rem}
#pfn-as form button[data-mic].grabando{background:#e31b23;color:#fff;animation:asGraba 1s infinite}
@keyframes asGraba{0%,100%{opacity:1}50%{opacity:.55}}
@media print{#pfn-as-btn,#pfn-as{display:none!important}}
@media (max-width:640px){#pfn-as-btn{bottom:14px;right:12px;padding:.65rem .95rem}#pfn-as{right:12px;bottom:68px}}
</style>
<button type="button" id="pfn-as-btn" aria-label="Hablar con Mía">💬 Habla con Mía</button>
<div id="pfn-as" role="dialog" aria-label="Asistente">
<div class="as-top"><span style="display:flex;align-items:center;gap:.55rem"><span style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c;display:flex;align-items:center;justify-content:center;font-weight:900">M</span><span style="line-height:1.15">Mía<small style="display:block;font-weight:500;font-size:.72rem;color:#9fb3a6"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#2fd15a;margin-right:4px"></span>Asistente virtual de LocalProo</small></span></span><button type="button" data-cerrar aria-label="Cerrar">×</button></div>
<div class="as-msgs" data-msgs></div>
<div class="as-chips" data-chips></div>
<form data-form><input type="text" data-in placeholder="Escribe tu pregunta…" maxlength="200" autocomplete="off"><button type="button" data-mic aria-label="Mandar nota de voz" title="Mandar nota de voz">🎤</button><button type="submit">➤</button></form>
</div>
<script>
(function () {
  var D = {!! json_encode($asDatos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}, U = D.u;
  var box = document.getElementById('pfn-as'), msgs = box.querySelector('[data-msgs]'), chips = box.querySelector('[data-chips]');
  function wa(t) { return 'https://wa.me/' + D.wa + '?text=' + encodeURIComponent(t || 'Hola, necesito ayuda'); }
  // Respuestas: texto con [texto](url) para los links (se arman con DOM, nunca con HTML del usuario).
  // 👉 Respuestas fijas de los botones ([texto](url) se vuelve link). Lo escrito lo responde la IA.
  var R = {
    cuenta: '👤 Crea tu cuenta gratis y prueba LocalProo 1 mes con todo:\n\n[Crear cuenta](' + U.registro + ') · [Iniciar sesión](' + U.login + ')',
    clave: '🔑 ¿Olvidaste tu clave?\n\n[Recuperar mi clave](' + U.clave + ')\n\nSi no te llega el correo, revisa Spam.',
    precios: D.sesion ? '💳 Mira tu plan, cuándo vence y paga en línea aquí:\n\n[Mi plan](' + U.plan + ')' : '💳 Empiezas con 1 mes gratis con todo. Después eliges el plan mensual o anual.\n\n[Crear cuenta gratis](' + U.registro + ')',
    funciones: '🧰 Caja POS, inventario, facturación DIAN, nómina, tienda en línea, redes y módulos para restaurantes, consultorios, funerarias, eventos y más.\n\n[Ver todas las funciones](' + U.funciones + ')',
    aprende: D.sesion ? '🎓 Aprende a manejar tu sistema paso a paso:\n\n[Ir a los cursos](' + U.aprende + ')' : '📚 Guías y artículos para tu negocio:\n\n[Ir al blog](' + U.blog + ')',
    humano: D.wa ? '🙋 Te atiende una persona del equipo:\n\n[Escribir por WhatsApp](' + wa('Hola, necesito ayuda con LocalProo') + ')' : '🙋 Cuéntame aquí lo que necesitas y el equipo de LocalProo lo revisa. Mientras tanto, ¿qué tipo de negocio tienes?',
    saludo: D.sesion ? '¡Hola! 😊 ¿Qué quieres hacer hoy en tu sistema? Por ejemplo: crear productos, vender en la caja, sacar reportes o facturar con la DIAN.' : '¡Hola! 😊 Qué gusto saludarte. ¿Qué tipo de negocio tienes? Tienda, restaurante, consultorio, funeraria, eventos... y te cuento cómo LocalProo te ayuda.',
    gracias: '¡Con mucho gusto! 😊 ¿Hay algo más en lo que te pueda ayudar?'
  };
  var CHIPS = D.sesion
    ? [['🎓 ¿Cómo hago…?', 'aprende'], ['💳 Mi plan', 'precios'], ['🧰 Funciones', 'funciones'], ['🙋 Hablar con alguien', 'humano']]
    : [['👤 Crear cuenta gratis', 'cuenta'], ['💳 Precios', 'precios'], ['🧰 ¿Qué hace?', 'funciones'], ['🔑 Olvidé mi clave', 'clave'], ['🙋 Hablar con alguien', 'humano']];
  var CLAVES = [
    ['clave', ['clave', 'contrasena', 'password', 'olvide']],
    ['precios', ['precio', 'cuesta', 'vale', 'plan', 'pagar', 'cobran', 'tarifa']],
    ['funciones', ['funcion', 'que hace', 'sirve', 'modulo']],
    ['aprende', ['como hago', 'como se', 'tutorial', 'curso', 'aprend']],
    ['humano', ['asesor', 'persona', 'humano', 'whatsapp', 'soporte']],
    ['cuenta', ['cuenta', 'registr', 'login', 'prueba', 'gratis']],
    ['gracias', ['gracias', 'muy amable', 'listo', 'ok gracias']],
    ['saludo', ['hola', 'buenas', 'buenos dias', 'buen dia', 'saludos', 'hey', 'que tal', 'ola']]
  ];
  function norm(t) { return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
  function burbuja(texto, quien) {
    var b = document.createElement('div'); b.className = 'as-b ' + (quien === 'yo' ? 'as-yo' : 'as-bot');
    // [texto](url) o una URL suelta (las que escribe la IA) -> link
    var re = /\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)|(https?:\/\/[^\s)]+[^\s).,;!?])/g, ult = 0, m;
    while ((m = re.exec(texto))) {
      b.appendChild(document.createTextNode(texto.slice(ult, m.index)));
      var url = m[2] || m[3], a = document.createElement('a'); a.href = url; a.textContent = m[1] || url.replace(/^https?:\/\//, '');
      if (url.indexOf('wa.me') >= 0) a.target = '_blank';
      b.appendChild(a); ult = re.lastIndex;
    }
    b.appendChild(document.createTextNode(texto.slice(ult)));
    msgs.appendChild(b); msgs.scrollTop = msgs.scrollHeight;
    HIST.push({rol: quien === 'yo' ? 'yo' : 'bot', texto: texto.slice(0, 800)}); if (HIST.length > 8) HIST.shift();
  }
  var HIST = [], IA = {!! json_encode(route('asistente.ia'), JSON_UNESCAPED_SLASHES) !!}, CSRF = {!! json_encode(csrf_token()) !!}, SESION = Math.random().toString(36).slice(2, 14) + Date.now().toString(36);
  // 2026-11-13: lo que no reconocen las palabras clave lo contesta la IA gratis (Groq); si no hay, WhatsApp.
  function preguntarIA(t, k) {
    var pens = escribiendo(); msgs.scrollTop = msgs.scrollHeight;
    var hist = HIST.slice(0, -1);
    fetch(IA, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}, body: JSON.stringify({mensaje: t, historial: hist, sesion: SESION})})
      .then(function (r) { return r.ok ? r.json() : {ok: false}; })
      .then(function (j) {
        if (j && j.ok && j.texto) { setTimeout(function () { pens.remove(); burbuja(j.texto, 'bot'); }, Math.min(1800, 300 + j.texto.length * 8)); return; }
        pens.remove();
        k ? burbuja(R[k], 'bot') : noEntendi(t);
      }).catch(function () { pens.remove(); k ? burbuja(R[k], 'bot') : noEntendi(t); });
  }
  // 2026-11-13 (Jhoset): "Mía está escribiendo…" para que la charla se sienta natural
  function escribiendo() {
    var e = document.createElement('div'); e.className = 'as-esc'; e.innerHTML = 'Mía está escribiendo<i></i><i></i><i></i>';
    msgs.appendChild(e); msgs.scrollTop = msgs.scrollHeight; return e;
  }
  // 2026-11-13 (Jhoset): no mandar de una a WhatsApp; primero seguir la charla, y solo a la tercera ofrecer una persona
  var FALLOS = 0;
  function noEntendi(t) {
    FALLOS++;
    if (FALLOS < 3 || !D.wa) {
      burbuja(FALLOS === 1
        ? 'Cuéntame un poquito más 😊. ¿Qué tipo de negocio tienes o qué quieres lograr? Por ejemplo: vender con caja, llevar el inventario, facturar con la DIAN o vender por internet.'
        : 'Te entiendo 🙌. Toca una de las opciones de abajo o escríbeme con otras palabras y te ayudo.', 'bot');
      return;
    }
    burbuja('Para no dejarte sin respuesta, una persona del equipo te ayuda con eso 🙋\n\n[Escribir por WhatsApp](' + wa('Hola, tengo una pregunta: ' + t) + ')', 'bot');
  }
  function responder(clave) { var e = escribiendo(); setTimeout(function () { e.remove(); burbuja(R[clave], 'bot'); }, 900); }
  function entender(t) {
    var n = norm(t);
    for (var i = 0; i < CLAVES.length; i++) {
      for (var j = 0; j < CLAVES[i][1].length; j++) { if (n.indexOf(CLAVES[i][1][j]) >= 0) return CLAVES[i][0]; }
    }
    return null;
  }
  CHIPS.forEach(function (c) {
    var b = document.createElement('button'); b.type = 'button'; b.textContent = c[0];
    b.onclick = function () { chips.style.display = 'none'; burbuja(c[0], 'yo'); responder(c[1]); };
    chips.appendChild(b);
  });
  var iniciado = false;
  function abrir() {
    box.classList.add('abierto');
    if (!iniciado) { iniciado = true; var e = escribiendo(); setTimeout(function () { e.remove(); burbuja(D.sesion ? '¡Hola! 👋 Soy Mía, la asistente virtual de LocalProo. ¿Qué quieres hacer hoy en tu sistema? Escríbeme o toca una opción.' : '¡Hola! 👋 Soy Mía, la asistente virtual de LocalProo. ¿Qué tipo de negocio tienes? Te cuento cómo LocalProo te ayuda.', 'bot'); }, 700); }
    setTimeout(function () { box.querySelector('[data-in]').focus(); }, 50);
  }
  // 2026-11-13 (Jhoset): link que abre el chat solo: ?mia, ?chat o #mia / #chat
  if (/[?&](mia|chat)\b/i.test(location.search) || /^#(mia|chat)$/i.test(location.hash)) { setTimeout(abrir, 600); }
  document.getElementById('pfn-as-btn').onclick = function () { box.classList.contains('abierto') ? box.classList.remove('abierto') : abrir(); };
  box.querySelector('[data-cerrar]').onclick = function () { box.classList.remove('abierto'); };
  box.querySelector('[data-form]').addEventListener('submit', function (e) {
    e.preventDefault();
    var inp = box.querySelector('[data-in]'), t = inp.value.trim(); if (!t) return;
    inp.value = ''; burbuja(t, 'yo');
    // 2026-11-13 (Jhoset): si ya está conversando escribiendo, se esconden los botones para no estorbar
    chips.style.display = 'none';
    // 2026-11-13 (Jhoset): lo escrito lo contesta la IA (natural); si la IA no responde, la respuesta fija o WhatsApp.
    preguntarIA(t, entender(t));
  });
  // 2026-11-13 (Jhoset): notas de voz: toca 🎤, habla (máx. 30 s) y toca ⏹ para enviar; Whisper (Groq) la pasa a texto y Mía responde
  (function () {
    var btn = box.querySelector('[data-mic]'), VOZ = {!! json_encode(route('asistente.voz'), JSON_UNESCAPED_SLASHES) !!};
    if (!btn) return;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) { btn.style.display = 'none'; return; }
    var rec = null, partes = [], corte = null;
    function fallo() { burbuja('No pude escuchar bien tu audio 🙈. ¿Me lo repites o me lo escribes, porfa?', 'bot'); }
    btn.onclick = function () {
      if (rec && rec.state === 'recording') { rec.stop(); return; }
      navigator.mediaDevices.getUserMedia({audio: true}).then(function (st) {
        partes = [];
        rec = new MediaRecorder(st);
        rec.ondataavailable = function (e) { if (e.data && e.data.size) partes.push(e.data); };
        rec.onstop = function () {
          clearTimeout(corte); st.getTracks().forEach(function (t) { t.stop(); });
          btn.classList.remove('grabando'); btn.textContent = '🎤';
          var tipo = rec.mimeType || 'audio/webm', blob = new Blob(partes, {type: tipo});
          if (blob.size < 1500) return;
          var ext = tipo.indexOf('mp4') >= 0 ? 'm4a' : (tipo.indexOf('ogg') >= 0 ? 'ogg' : 'webm');
          var fd = new FormData(); fd.append('audio', blob, 'nota.' + ext);
          var e = escribiendo(); e.firstChild.nodeValue = 'Mía está escuchando tu audio';
          fetch(VOZ, {method: 'POST', credentials: 'same-origin', headers: {'Accept': 'application/json'}, body: fd})
            .then(function (r) { return r.ok ? r.json() : {ok: false}; })
            .then(function (j) {
              e.remove();
              if (j && j.ok && j.texto) { chips.style.display = 'none'; burbuja('🎤 ' + j.texto, 'yo'); preguntarIA(j.texto, entender(j.texto)); } else { fallo(); }
            }).catch(function () { e.remove(); fallo(); });
        };
        rec.start(); btn.classList.add('grabando'); btn.textContent = '⏹';
        corte = setTimeout(function () { if (rec && rec.state === 'recording') rec.stop(); }, 30000);
      }).catch(function () { burbuja('Para mandarme audio, permite el micrófono en tu navegador 🎤. O si prefieres, escríbeme.', 'bot'); });
    };
  })();
})();
</script>
