{{-- 2026-11-13 (Jhoset): panel del asistente (compartido PFN / NES).
     Variables: $tab, $pendientes, $todas, $conocimiento, $stats, $rutas (ensenar, calificar, conocimiento, base) --}}
<style>
.as-adm{max-width:1100px;margin:0 auto}
.as-adm .tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin:1rem 0}
.as-adm .tabs a{padding:.5rem .9rem;border-radius:100px;border:1px solid #d4d4d8;text-decoration:none;color:#0A0A0F;font-weight:700;font-size:.9rem}
.as-adm .tabs a.on{background:#7C3AED;border-color:#7C3AED;color:#fff}
.as-adm .stats{display:flex;gap:.6rem;flex-wrap:wrap;margin:.6rem 0}
.as-adm .stats div{border:1px solid #d4d4d8;border-radius:10px;padding:.5rem .9rem;font-size:.85rem}
.as-adm .stats b{font-size:1.2rem;display:block}
.as-adm .it{border:1px solid #e4e4e7;border-radius:12px;padding:.8rem 1rem;margin:.6rem 0;background:#fff}
.as-adm .q{font-weight:700;margin-bottom:.35rem}
.as-adm .r{white-space:pre-line;font-size:.9rem;opacity:.85;margin-bottom:.5rem}
.as-adm .meta{font-size:.75rem;opacity:.6;margin-bottom:.4rem}
.as-adm textarea{width:100%;min-height:70px;margin:.3rem 0;padding:.5rem;border-radius:8px;font-size:.9rem;background:#fff;color:#0A0A0F;border:1px solid #d4d4d8}
.as-adm input[type=text]{width:100%;margin:.3rem 0;padding:.45rem .5rem;border-radius:8px;font-size:.9rem;background:#fff;color:#0A0A0F;border:1px solid #d4d4d8}
.as-adm .btns{display:flex;gap:.4rem;flex-wrap:wrap}
.as-adm .btns button{margin:0;padding:.4rem .8rem;font-size:.82rem;border-radius:8px;border:1px solid #d4d4d8;background:#f4f4f5;color:#0A0A0F;cursor:pointer;font-weight:700;text-transform:none;letter-spacing:0}
.as-adm .btns .ok{background:#16a34a;border-color:#16a34a;color:#fff}
.as-adm .btns .no{background:#dc2626;border-color:#dc2626;color:#fff}
.as-adm .tag{display:inline-block;font-size:.72rem;font-weight:700;padding:.1rem .5rem;border-radius:100px;margin-left:.3rem}
.as-adm .t-bien{background:rgba(46,204,113,.15);color:#15803d}.as-adm .t-mal{background:rgba(229,72,77,.15);color:#ff6b6b}.as-adm .t-sin{background:rgba(245,197,66,.15);color:#a16207}
.as-adm .msg{font-size:.8rem;margin-left:.4rem}
</style>
<div class="as-adm">
<h1>🤖 Asistente de la página</h1>
<p style="opacity:.75;margin:.2rem 0">Aquí ves lo que pregunta la gente y cómo responde el asistente. Lo que respondas en <strong>Sin respuesta</strong> o al corregir una respuesta <strong>mala</strong> queda guardado en <strong>Lo que aprendió</strong> y el asistente lo usa desde ese momento.</p>
<div class="stats">
<div><b>{{ $stats['hoy'] }}</b>preguntas hoy</div>
<div><b>{{ $stats['total'] }}</b>en total</div>
<div><b>{{ $stats['bien'] }}</b>👍 bien</div>
<div><b>{{ $stats['mal'] }}</b>👎 mal</div>
<div><b>{{ $conocimiento->where('activo', true)->count() }}</b>🧠 aprendidas</div>
</div>
<div class="tabs">
<a href="{{ $rutas['base'] }}?tab=pendientes" class="{{ $tab === 'pendientes' ? 'on' : '' }}">❓ Sin respuesta ({{ $pendientes->count() }})</a>
<a href="{{ $rutas['base'] }}?tab=todas" class="{{ $tab === 'todas' ? 'on' : '' }}">💬 Conversaciones</a>
<a href="{{ $rutas['base'] }}?tab=aprendio" class="{{ $tab === 'aprendio' ? 'on' : '' }}">🧠 Lo que aprendió ({{ $conocimiento->count() }})</a>
</div>

@if ($tab === 'pendientes')
<p style="opacity:.75">Preguntas que el asistente no supo responder. Escribe la respuesta correcta y dale <strong>Enseñar</strong>: la próxima vez la responde solo.</p>
{{-- 2026-11-18 (Jhoset): copiar todas las preguntas sin respuesta para que Claude las responda, y pegar sus respuestas de una vez (igual que NES y PFN) --}}
@if ($pendientes->isNotEmpty())
<div data-lote style="border:2px dashed #16a34a;border-radius:12px;padding:.8rem 1rem;margin:.6rem 0;">
<div style="font-weight:800;margin-bottom:.3rem">🤝 Responder todas con Claude</div>
<div style="opacity:.85;font-size:.9rem">1) Dale <b>Copiar preguntas para responder</b> y pégalas en el chat de Claude. 2) Claude te devuelve un bloque con las respuestas. 3) Pégalo aquí abajo y dale <b>Enseñar todas</b>. Si a alguna no le pudo responder, te lo dice en el chat para que tú le enseñes.</div>
<div class="btns" style="margin:.4rem 0"><button type="button" class="ok" data-lote-copiar>📋 Copiar preguntas para responder</button><span class="msg" data-lote-msg1></span></div>
<textarea data-lote-txt placeholder="Pega aquí lo que te devolvió Claude (bloques que empiezan con #número)" style="min-height:110px;width:100%"></textarea>
<div class="btns"><button type="button" class="ok" data-lote-ensenar>🧠 Enseñar todas</button><span class="msg" data-lote-msg2></span></div>
</div>
<script>
(function () {
  var CSRF = @json(csrf_token());
  var URL_ENS = @json($rutas['ensenar']);
  var caja = document.querySelector('[data-lote]'); if (!caja) return;
  function msg(sel, t, ok) { var m = caja.querySelector(sel); m.textContent = t; m.style.color = ok ? '#3ecf6e' : '#ff5a5a'; }
  function norm(t) { return (t || '').toLowerCase().replace(/\s+/g, ' ').trim(); }
  function items() { return Array.prototype.slice.call(document.querySelectorAll('.it[data-pend]')); }
  function copiar(t) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(t);
    return new Promise(function (ok, no) {
      var ta = document.createElement('textarea'); ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy') ? ok() : no(); } catch (e) { no(); } document.body.removeChild(ta);
    });
  }
  caja.querySelector('[data-lote-copiar]').addEventListener('click', function () {
    var vistos = {}, partes = [];
    items().forEach(function (it) {
      var p = it.querySelector('[data-p]').value.trim(), k = norm(p); if (!p || vistos[k]) return; vistos[k] = 1;
      var r = it.querySelector('.r'), fecha = (it.querySelector('.meta') || {}).textContent || '';
      partes.push('#' + it.getAttribute('data-id') + ' (' + fecha.trim() + ')\nP: ' + p + (r ? '\n' + r.textContent.trim().replace(/^Respondió:/, 'Mía respondió:') : ''));
    });
    if (!partes.length) { msg('[data-lote-msg1]', 'No hay preguntas pendientes.', false); return; }
    var t = 'Claude: estas son las preguntas que Mía (asistente de LocalProo, localproo.com) no supo responder. Respóndelas con la información oficial de LocalProo (funciones, planes, precios, cómo se usa) '
      + 'y devuélveme UN solo bloque para pegar en "Enseñar todas", con este formato por cada una:\n#número\nR: respuesta corta y cálida como la diría Mía\n'
      + 'Las que no sepas responder no las pongas en el bloque: dímelas aparte para enseñarle yo.\n\n'
      + partes.join('\n\n');
    copiar(t).then(function () { msg('[data-lote-msg1]', '✓ Copiadas ' + partes.length + ' preguntas. Pégalas en el chat de Claude.', true); },
      function () { msg('[data-lote-msg1]', 'No se pudo copiar. Intenta de nuevo.', false); });
  });
  caja.querySelector('[data-lote-ensenar]').addEventListener('click', function () {
    var b = this, txt = caja.querySelector('[data-lote-txt]').value, lote = [];
    txt.split(/^\s*#(?=\d+)/m).forEach(function (blq) {
      var m = blq.match(/^(\d+)[^\n]*\n([\s\S]*)$/); if (!m) return;
      var cuerpo = m[2], mm = cuerpo.match(/^\s*R\s*:\s*([\s\S]*)$/m);
      if (!mm) return;
      var resp = mm[1].replace(/\n\s*(P|Mía respondió)\s*:[\s\S]*$/, '').trim();
      var it = document.querySelector('.it[data-pend][data-id="' + m[1] + '"]'); if (!it || !resp) return;
      lote.push({it: it, id: +m[1], pregunta: it.querySelector('[data-p]').value.trim(), respuesta: resp});
    });
    if (!lote.length) { msg('[data-lote-msg2]', 'No encontré respuestas. Pega el bloque tal cual te lo dio Claude (#número y R: …).', false); return; }
    b.disabled = true; var hechas = 0, fallas = 0;
    (function sig(i) {
      if (i >= lote.length) {
        b.disabled = false;
        msg('[data-lote-msg2]', '✓ Mía aprendió ' + hechas + (fallas ? ' · ' + fallas + ' no se guardaron' : '') + '.', !fallas);
        return;
      }
      var x = lote[i]; msg('[data-lote-msg2]', 'Enseñando ' + (i + 1) + ' de ' + lote.length + '…', true);
      fetch(URL_ENS, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: JSON.stringify({mensaje_id: x.id, pregunta: x.pregunta, respuesta: x.respuesta})})
        .then(function (r) { return r.json(); })
        .then(function (j) { if (j.ok) { hechas++; x.it.style.display = 'none'; } else fallas++; sig(i + 1); })
        .catch(function () { fallas++; sig(i + 1); });
    })(0);
  });
})();
</script>
@endif
@forelse ($pendientes as $p)
<div class="it" data-id="{{ $p->id }}" data-pend>
<div class="meta">{{ \Carbon\Carbon::parse($p->created_at)->timezone('America/Bogota')->format('d/m h:i A') }}</div>
<div class="q">❓ {{ $p->pregunta }}</div>
@if ($p->respuesta)<div class="r">Respondió: {{ $p->respuesta }}</div>@endif
<input type="text" data-p value="{{ $p->pregunta }}" title="Pregunta (puedes dejarla más clara)">
<textarea data-r placeholder="Escribe la respuesta correcta…"></textarea>
<div class="btns"><button type="button" class="ok" data-ensenar>🧠 Enseñar</button><button type="button" data-ignorar>Ignorar</button><span class="msg" data-msg></span></div>
</div>
@empty
<p>✅ No hay preguntas pendientes.</p>
@endforelse
@elseif ($tab === 'todas')
<p style="opacity:.75">Últimas 200 preguntas. Marca 👍 si respondió bien o 👎 si respondió mal; si está mal, escribe la respuesta correcta y enséñale.</p>
@forelse ($todas as $p)
<div class="it" data-id="{{ $p->id }}">
<div class="meta">{{ \Carbon\Carbon::parse($p->created_at)->timezone('America/Bogota')->format('d/m h:i A') }}
@if ($p->sin_respuesta)<span class="tag t-sin">no supo</span>@endif
@if ($p->calificacion === 'bien')<span class="tag t-bien">👍 bien</span>@elseif ($p->calificacion === 'mal')<span class="tag t-mal">👎 mal</span>@endif</div>
<div class="q">🙋 {{ $p->pregunta }}</div>
<div class="r">🤖 {{ $p->respuesta ?: '(sin respuesta de la IA)' }}</div>
<div class="btns"><button type="button" data-cal="bien">👍 Bien</button><button type="button" class="no" data-cal="mal">👎 Mal</button><span class="msg" data-msg></span></div>
<div data-corregir style="display:none">
<input type="text" data-p value="{{ $p->pregunta }}">
<textarea data-r placeholder="¿Cuál era la respuesta correcta?"></textarea>
<div class="btns"><button type="button" class="ok" data-ensenar>🧠 Enseñar</button></div>
</div>
</div>
@empty
<p>Todavía no hay conversaciones.</p>
@endforelse
@else
<p style="opacity:.75">Esto es lo que el equipo le ha enseñado. Lo puedes corregir o borrar.</p>
@forelse ($conocimiento as $k)
<div class="it" data-kid="{{ $k->id }}">
<input type="text" data-p value="{{ $k->pregunta }}">
<textarea data-r>{{ $k->respuesta }}</textarea>
<div class="btns"><button type="button" class="ok" data-guardar>💾 Guardar</button><button type="button" class="no" data-borrar>🗑 Borrar</button><span class="msg" data-msg></span></div>
</div>
@empty
<p>Todavía no le has enseñado nada. Empieza por la pestaña ❓ Sin respuesta.</p>
@endforelse
@endif
</div>
<script>
(function () {
  var CSRF = {!! json_encode(csrf_token()) !!}, R = {!! json_encode($rutas, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!};
  function post(url, data) {
    return fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}, body: JSON.stringify(data)}).then(function (r) { return r.json(); });
  }
  function aviso(box, t, ok) { var m = box.querySelector('[data-msg]'); if (m) { m.textContent = t; m.style.color = ok ? '#2ecc71' : '#ff6b6b'; } }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    var it = b.closest('.it'); if (!it) return;
    if (b.hasAttribute('data-ensenar')) {
      var p = it.querySelector('[data-p]').value.trim(), r = it.querySelector('[data-r]').value.trim();
      if (!r) { aviso(it, 'Escribe la respuesta primero', false); return; }
      b.disabled = true;
      post(R.ensenar, {mensaje_id: +it.getAttribute('data-id') || null, pregunta: p, respuesta: r}).then(function (j) {
        if (j.ok) { it.style.opacity = '.45'; aviso(it, '✓ Aprendido. Desde ya lo responde así.', true); } else { b.disabled = false; aviso(it, 'No se guardó', false); }
      }).catch(function () { b.disabled = false; aviso(it, 'No se guardó', false); });
    } else if (b.hasAttribute('data-ignorar')) {
      post(R.calificar, {mensaje_id: +it.getAttribute('data-id'), ignorar: true}).then(function () { it.remove(); });
    } else if (b.hasAttribute('data-cal')) {
      var c = b.getAttribute('data-cal');
      post(R.calificar, {mensaje_id: +it.getAttribute('data-id'), calificacion: c}).then(function () {
        aviso(it, c === 'bien' ? '✓ Marcada bien' : 'Marcada mal: escribe abajo la respuesta correcta', c === 'bien');
        var cor = it.querySelector('[data-corregir]'); if (cor) cor.style.display = c === 'mal' ? '' : 'none';
      });
    } else if (b.hasAttribute('data-guardar') || b.hasAttribute('data-borrar')) {
      var borrar = b.hasAttribute('data-borrar');
      post(R.conocimiento, {id: +it.getAttribute('data-kid'), pregunta: it.querySelector('[data-p]').value, respuesta: it.querySelector('[data-r]').value, borrar: borrar}).then(function (j) {
        if (borrar) it.remove(); else aviso(it, j.ok ? '✓ Guardado' : 'No se guardó', j.ok);
      });
    }
  });
})();
</script>
