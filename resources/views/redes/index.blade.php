@extends('layouts.app')

@section('title', 'Mis redes · Mensajes')

@push('css')
<style>
.rc-tabs{display:flex;gap:.6rem;overflow-x:auto;margin:0 0 1rem;padding-bottom:.2rem;scrollbar-width:none}
.rc-tab{display:flex;align-items:center;gap:.6rem;padding:.55rem .9rem .55rem .55rem;border-radius:18px;border:1.5px solid #e3ebe6;background:#fff;color:#151a17;text-decoration:none;white-space:nowrap;flex:none;transition:all .15s}
.rc-tab:hover{border-color:#2fd15a}
.rc-tab.on{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.rc-tab img,.rc-tab .rc-ini{width:38px;height:38px;border-radius:12px;object-fit:cover;flex:none}
.rc-tab .rc-ini{display:grid;place-items:center;background:#e8f8ec;color:#16b34a;font-weight:900}
.rc-tx{display:flex;flex-direction:column;line-height:1.15}
.rc-tx b{font-weight:800;font-size:.95rem}
.rc-tx small{font-weight:600;font-size:.76rem;opacity:.7}
.rc-tab.on .rc-tx small{color:#a6f34a;opacity:1}
.rc-nl{min-width:22px;height:22px;border-radius:99px;background:#d61f69;color:#fff;font-weight:800;font-size:.75rem;display:grid;place-items:center;padding:0 .4rem}
.ib{display:grid;grid-template-columns:340px minmax(0,1fr);background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;overflow:hidden;height:calc(100vh - 230px);min-height:520px}
.ib-l{border-right:1.5px solid #eef3ef;display:flex;flex-direction:column;min-height:0}
.ib-lh{padding:.8rem;border-bottom:1.5px solid #eef3ef}
.ib-lh input{border-radius:999px}
.ib-fil{display:flex;gap:.3rem;overflow-x:auto;padding-top:.6rem;scrollbar-width:none}
.ib-fil button{border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.25rem .7rem;font-weight:800;font-size:.76rem;white-space:nowrap;cursor:pointer}
.ib-fil button.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.ib-lista{overflow-y:auto;flex:1}
.ib-c{display:flex;gap:.7rem;padding:.75rem .85rem;border-bottom:1px solid #f1f5f2;cursor:pointer;align-items:center}
.ib-c:hover{background:#f7fbf8}.ib-c.on{background:#ecfaef}
.ib-av{position:relative;flex:none}
.ib-av img,.ib-av .ini{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#e8eee9;display:grid;place-items:center;font-weight:900;color:#33403a}
.ib-av .rd-ico{position:absolute;right:-3px;bottom:-3px;width:18px;height:18px;border:2px solid #fff;border-radius:50%;font-size:.6rem}
.ib-ct{flex:1;min-width:0}
.ib-ct b{display:flex;justify-content:space-between;gap:.4rem;font-size:.92rem}
.ib-ct b span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ib-ct b small{color:#9aa69e;font-weight:600;font-size:.72rem;flex:none}
.ib-ct p{margin:0;color:#6b7570;font-size:.83rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ib-c.nl .ib-ct p{color:#151a17;font-weight:800}
.ib-nl{background:#16b34a;color:#fff;border-radius:999px;font-size:.7rem;font-weight:900;min-width:20px;height:20px;display:grid;place-items:center;padding:0 .35rem}
.ib-et{font-size:.65rem;font-weight:800;border-radius:999px;padding:.05rem .45rem;color:#fff;margin-left:.3rem}
.ib-r{display:flex;flex-direction:column;min-height:0;background:#f4f8f5}
.ib-vacio{flex:1;display:grid;place-items:center;text-align:center;color:#6b7570;padding:2rem}
.ib-vacio i{font-size:3rem;color:#cfe9d6}
.ib-h{display:flex;gap:.7rem;align-items:center;padding:.7rem .9rem;background:#fff;border-bottom:1.5px solid #eef3ef;flex-wrap:wrap}
.ib-h .t{flex:1;min-width:140px}.ib-h .t b{display:block}.ib-h .t small{color:#6b7570;font-weight:600}
.ib-h select{width:auto;border-radius:999px;font-weight:800;font-size:.82rem;padding:.3rem 2rem .3rem .8rem}
.ib-back{display:none;border:0;background:#f1f5f2;border-radius:12px;width:38px;height:38px}
.ib-msgs{flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.35rem}
.ib-m{max-width:72%;padding:.55rem .8rem;border-radius:18px;background:#fff;align-self:flex-start;box-shadow:0 1px 2px rgba(0,0,0,.05);white-space:pre-wrap;word-break:break-word;font-size:.93rem}
.ib-m.yo{align-self:flex-end;background:#0b0d0c;color:#fff;border-bottom-right-radius:6px}
.ib-m:not(.yo){border-bottom-left-radius:6px}
.ib-m img{max-width:240px;border-radius:12px;display:block;margin-bottom:.3rem}
.ib-m small{display:block;font-size:.68rem;color:#9aa69e;margin-top:.2rem;text-align:right}
.ib-m.yo small{color:#9fe3b0}
.ib-24{background:#fff5d6;color:#8a6400;font-weight:700;font-size:.85rem;padding:.6rem .9rem;border-top:1.5px solid #f5e2a6}
.ib-24.no{background:#ffe9f3;color:#9b1c5a;border-color:#ffc3de}
.ib-rap{display:flex;gap:.35rem;overflow-x:auto;padding:.5rem .8rem 0;background:#fff;scrollbar-width:none}
.ib-rap button{border:1.5px solid #cfe9d6;background:#f4fcf6;border-radius:999px;padding:.25rem .7rem;font-size:.78rem;font-weight:700;white-space:nowrap;cursor:pointer}
.ib-f{display:flex;gap:.5rem;align-items:flex-end;padding:.6rem .8rem .8rem;background:#fff}
.ib-f textarea{flex:1;resize:none;border:1.5px solid #e3ebe6;border-radius:20px;padding:.6rem .9rem;max-height:140px;outline:none}
.ib-f textarea:focus{border-color:#2fd15a}
.ib-f .b{width:46px;height:46px;border-radius:50%;border:0;display:grid;place-items:center;flex:none;cursor:pointer;font-size:1.05rem}
.ib-f .env{background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c}
.ib-f .prod{background:#f1f5f2;color:#0b0d0c}
.ib-f .b:disabled{opacity:.4;cursor:not-allowed}
.pp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.6rem;max-height:52vh;overflow:auto;margin-top:.7rem}
.pp-grid button{border:1.5px solid #e3ebe6;background:#fff;border-radius:16px;padding:.4rem;text-align:left;cursor:pointer}
.pp-grid button:hover{border-color:#2fd15a}
.pp-grid img,.pp-grid .sin{width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;background:#f1f5f2;display:grid;place-items:center;color:#9aa69e}
.pp-grid b{display:block;font-size:.8rem;margin-top:.3rem;line-height:1.2}
.pp-grid span{font-size:.78rem;color:#11702f;font-weight:800}
@media (max-width:860px){
  .ib{grid-template-columns:minmax(0,1fr);height:calc(100vh - 200px)}
  .ib.chat .ib-l{display:none}.ib:not(.chat) .ib-r{display:none}
  .ib-back{display:grid;place-items:center}
  .ib-m{max-width:86%}
}
</style>
@endpush

@section('content')
@php $et = \App\Models\RedConversacion::ETIQUETAS; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hashtag me-1"></i>Mis redes</small><h1>Mensajes de <span>sus redes</span></h1>
            @php $sel = $cuentaSel ? $cuentas->firstWhere('id', $cuentaSel) : null; @endphp
            <span class="text-muted fw-semibold">{{ $sel ? $sel->page_nombre . ($sel->ig_usuario ? ' · @' . $sel->ig_usuario : '') : $cuentas->map(fn ($c) => $c->page_nombre . ($c->ig_usuario ? ' · @' . $c->ig_usuario : ''))->implode(' — ') }}</span></div>
        <form method="post" action="{{ route('redes.sincronizar') }}" class="lp-quick">@csrf<button class="btn btn-light btn-lg"><i class="fa-solid fa-rotate me-1"></i>Actualizar</button></form>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @include('redes._tabs')

    {{-- Estado real de la conexión: así el negocio sabe por qué no le llega un mensaje --}}
    @foreach ($cuentas as $c)
        @if ($c->estado === 'error')
        <div class="alert alert-danger d-flex align-items-center gap-2 flex-wrap"><i class="fa-solid fa-triangle-exclamation"></i>
            <span><b>{{ $c->page_nombre }}</b>: {{ $c->error ?: 'la conexión con Meta falló.' }}</span>
            @can('editar-negocio')<a href="{{ $c->es_correo ? route('redes.cuentas') . '#correo' : route('redes.conectar') }}" class="btn btn-sm btn-dark ms-auto">Volver a conectar</a>@endcan
        </div>
        @elseif ($c->error)
        <div class="alert alert-warning small mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i><b>{{ $c->page_nombre }}</b>: {{ $c->error }}
            <span class="text-muted">· última revisión {{ $c->sincronizado_en?->locale('es')->diffForHumans() ?? 'pendiente' }}</span></div>
        @endif
    @endforeach
    @if (!config('services.meta.en_vivo'))
    <div class="alert alert-light border small mb-3"><i class="fa-solid fa-clock me-1"></i>Los mensajes nuevos de sus clientes se revisan automáticamente cada 2 minutos. Si espera uno, toque <b>Actualizar</b>.</div>
    @endif

    @if ($cuentas->count() > 1)
    {{-- Una pestaña por cuenta conectada: cada una muestra solo sus chats --}}
    <div class="rc-tabs" role="tablist">
        @foreach ($cuentas as $c)
        @php $nl = (int) ($sinLeerPorCuenta[$c->id] ?? 0); @endphp
        <a href="{{ route('redes.index', ['cuenta' => $c->id]) }}" class="rc-tab {{ (int) $cuentaSel === (int) $c->id ? 'on' : '' }}" role="tab">
            @if ($c->page_foto || $c->ig_foto)<img src="{{ $c->ig_foto ?: $c->page_foto }}" alt="" loading="lazy">@else<span class="rc-ini">{{ mb_substr($c->page_nombre, 0, 1) }}</span>@endif
            <span class="rc-tx"><b>{{ $c->es_correo ? '✉️ Correo' : $c->page_nombre }}</b><small>{{ $c->es_correo ? $c->page_nombre : ($c->ig_usuario ? '@' . $c->ig_usuario : 'Facebook') }}</small></span>
            @if ($nl)<span class="rc-nl">{{ $nl }}</span>@endif
        </a>
        @endforeach
    </div>
    @endif

    <div class="ib" id="ib">
        <div class="ib-l">
            <div class="ib-lh">
                <input id="ibQ" class="form-control" placeholder="Buscar chat…" value="{{ $q }}">
                <div class="ib-fil" id="ibFil">
                    @foreach (['todos' => 'Todos', 'sin_leer' => 'Sin leer', 'facebook' => 'Facebook', 'instagram' => 'Instagram'] + ($cuentas->contains(fn ($c) => $c->es_correo) ? ['correo' => 'Correo'] : []) + collect($et)->map(fn ($e) => $e[0])->all() as $k => $v)
                    <button type="button" data-f="{{ $k }}" class="{{ $filtro === $k ? 'on' : '' }}">{{ $v }}</button>
                    @endforeach
                </div>
            </div>
            <div class="ib-lista" id="ibLista"></div>
        </div>
        <div class="ib-r" id="ibR">
            <div class="ib-vacio" id="ibVacio"><div><i class="fa-regular fa-comments"></i><p class="mt-2 mb-0 fw-bold">Elija un chat para ver los mensajes</p><small>Los mensajes nuevos llegan solos.</small></div></div>
            <div id="ibChat" style="display:none;flex:1;min-height:0;flex-direction:column">
                <div class="ib-h">
                    <button class="ib-back" id="ibBack" type="button"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="ib-av" id="hAv"></div>
                    <div class="t"><b id="hNom"></b><small id="hSub"></small></div>
                    <select id="hEt" class="form-select" data-nativo><option value="">Sin etiqueta</option>@foreach ($et as $k => $e)<option value="{{ $k }}">{{ $e[0] }}</option>@endforeach</select>
                    <button class="btn btn-sm btn-dark" id="hCli" type="button"><i class="fa-solid fa-user-plus me-1"></i>Guardar cliente</button>
                    <a class="btn btn-sm btn-light" id="hVerCli" style="display:none" href="#"><i class="fa-solid fa-user me-1"></i>Ver cliente</a>
                </div>
                <div class="ib-msgs" id="ibMsgs"></div>
                <div class="ib-24" id="ib24" style="display:none"></div>
                <div class="ib-rap" id="ibRap">
                    @php
                        $rap = array_values(array_filter([
                            '¡Hola! 👋 Gracias por escribirnos. ¿En qué le podemos ayudar?',
                            'Sí, lo tenemos disponible ✅ ¿Qué talla necesita?',
                            $tienda?->direccion ? '📍 Estamos en ' . $tienda->direccion . ($tienda->ciudad ? ', ' . $tienda->ciudad : '') . '.' : null,
                            $tienda?->telefono ? '📲 Escríbanos por WhatsApp al ' . $tienda->telefono . ' y le atendemos de una.' : null,
                            $tienda?->catalogo_activo && $tienda->slug ? '🛍️ Vea todos nuestros productos aquí: ' . $tienda->url_catalogo : null,
                            '¡Gracias por su compra! 🙌',
                        ]));
                    @endphp
                    @foreach ($rap as $r)<button type="button" data-t="{{ $r }}">{{ \Illuminate\Support\Str::limit($r, 34) }}</button>@endforeach
                </div>
                <div class="ib-f">
                    <button class="b prod" id="ibProd" type="button" title="Enviar un producto"><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }}"></i></button>
                    <textarea id="ibTxt" rows="1" maxlength="1900" placeholder="Escriba un mensaje…"></textarea>
                    <button class="b env" id="ibEnv" type="button" title="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="lpx-ov" id="ppOv" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <div class="lpx-md" style="width:min(640px,100%)">
        <div class="d-flex justify-content-between align-items-center"><h4 class="m-0">Enviar un producto</h4><button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('ppOv').style.display='none'">✕</button></div>
        <input id="ppQ" class="form-control mt-2" placeholder="Buscar producto…" autocomplete="off">
        <div class="pp-grid" id="ppGrid"></div>
    </div>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var CSRF = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : @json(csrf_token());
    var R = { lista: @json(route('redes.lista')), base: @json(url('/mis-redes/chats')) + '/', prod: @json(route('redes.productos')), cliente: @json(url('/clientes')) + '/' };
    @php $etJs = collect($et)->map(fn ($e) => ['n' => $e[0], 'c' => $e[1]]); @endphp
    var ET = @json($etJs);
    var filtro = @json($filtro), actual = @json($actual), cuenta = @json($cuentaSel), ultimo = 0, conv = null, tl, tm;
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var av = function (c) { return (c.foto ? '<img src="' + esc(c.foto) + '" alt="" onerror="this.outerHTML=\'<span class=ini>' + esc((c.nombre || '?').charAt(0)) + '</span>\'">' : '<span class="ini">' + esc((c.nombre || '?').charAt(0).toUpperCase()) + '</span>') + '<span class="rd-ico ' + c.plataforma + '"><i class="' + (c.plataforma === 'correo' ? 'fa-solid fa-envelope' : 'fa-brands fa-' + (c.plataforma === 'instagram' ? 'instagram' : 'facebook-f')) + '"></i></span>'; };
    var json = function (url, opt) { return fetch(url, Object.assign({ headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF } }, opt || {})).then(function (r) { return r.json().then(function (d) { if (!r.ok) throw d; return d; }); }); };

    function cargarLista() {
        json(R.lista + '?ver=' + encodeURIComponent(filtro) + '&q=' + encodeURIComponent($('ibQ').value.trim()) + (cuenta ? '&cuenta=' + cuenta : '')).then(function (l) {
            $('ibLista').innerHTML = l.length ? l.map(function (c) {
                var e = c.etiqueta && ET[c.etiqueta] ? '<span class="ib-et" style="background:' + ET[c.etiqueta].c + '">' + esc(ET[c.etiqueta].n) + '</span>' : '';
                return '<div class="ib-c' + (c.id === actual ? ' on' : '') + (c.no_leidos ? ' nl' : '') + '" data-id="' + c.id + '"><div class="ib-av">' + av(c) + '</div><div class="ib-ct"><b><span>' + esc(c.nombre) + e + '</span><small>' + esc(c.cuando || '') + '</small></b><p>' + esc(c.texto || '') + '</p></div>' + (c.no_leidos ? '<span class="ib-nl">' + c.no_leidos + '</span>' : '') + '</div>';
            }).join('') : '<div class="text-center text-muted p-4"><i class="fa-regular fa-comment-dots" style="font-size:2rem;color:#cfe9d6"></i><p class="mt-2 mb-0">No hay chats aquí todavía.<br><small>Cuando le escriban a su página o a su Instagram aparecerán aquí.</small></p></div>';
        }).catch(function () {});
    }
    $('ibLista').addEventListener('click', function (e) { var c = e.target.closest('.ib-c'); if (c) abrir(+c.dataset.id); });
    $('ibFil').addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; filtro = b.dataset.f; document.querySelectorAll('#ibFil button').forEach(function (x) { x.classList.toggle('on', x === b); }); cargarLista(); });
    var tq; $('ibQ').addEventListener('input', function () { clearTimeout(tq); tq = setTimeout(cargarLista, 300); });

    function pintarConv(c) {
        conv = c;
        $('hAv').innerHTML = av(c); $('hNom').textContent = c.nombre;
        $('hSub').textContent = c.plataforma === 'correo' ? 'Correo · ' + (c.correo || '') + (c.asunto ? ' · ' + c.asunto : '') : (c.plataforma === 'instagram' ? 'Instagram' : 'Facebook Messenger') + (c.usuario ? ' · @' + c.usuario : '');
        $('hEt').value = c.etiqueta || '';
        $('hCli').style.display = c.cliente_id ? 'none' : ''; $('hVerCli').style.display = c.cliente_id ? '' : 'none';
        if (c.cliente_id) $('hVerCli').href = R.cliente + c.cliente_id;
        var b = $('ib24');
        var plazo = c.horas > 24 ? '7 días' : '24 horas';
        if (!c.puede) { b.style.display = ''; b.className = 'ib-24 no'; b.innerHTML = '⏰ Pasaron más de ' + plazo + ' desde el último mensaje del cliente. Meta no deja responder por aquí hasta que vuelva a escribir. Si tiene su WhatsApp, escríbale por allá.'; }
        else if (c.plataforma !== 'correo' && c.vence && (c.horas > 24 ? /hora|minuto|1 día/.test(c.vence) : /hora|minuto/.test(c.vence) && !/día/.test(c.vence))) { b.style.display = ''; b.className = 'ib-24'; b.textContent = '⏳ Puede responder hasta ' + c.vence + ' (después Meta no deja contestar).'; }
        else b.style.display = 'none';
        $('ibTxt').disabled = !c.puede; $('ibEnv').disabled = !c.puede; $('ibProd').disabled = !c.puede;
    }
    function pintarMensajes(lista, agregar) {
        var box = $('ibMsgs'), abajo = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
        var html = lista.map(function (m) {
            return '<div class="ib-m' + (m.yo ? ' yo' : '') + '">' + (m.img ? '<a href="' + esc(m.img) + '" target="_blank"><img src="' + esc(m.img) + '" alt=""></a>' : '') + (m.adj ? '<a href="' + esc(m.adj) + '" target="_blank">📎 Ver archivo</a>' : '') + (m.texto ? esc(m.texto) : '') + '<small>' + esc(m.hora || '') + (m.yo && m.quien ? ' · ' + esc(m.quien) : '') + '</small></div>';
        }).join('');
        if (agregar) box.insertAdjacentHTML('beforeend', html); else box.innerHTML = html || '<div class="text-center text-muted small mt-3">Sin mensajes guardados todavía.</div>';
        if (lista.length) ultimo = lista[lista.length - 1].id;
        if (!agregar || abajo) box.scrollTop = box.scrollHeight;
    }
    function abrir(id) {
        actual = id; ultimo = 0;
        $('ibVacio').style.display = 'none'; $('ibChat').style.display = 'flex'; $('ib').classList.add('chat');
        $('ibMsgs').innerHTML = '<div class="text-center text-muted mt-4">Cargando…</div>';
        document.querySelectorAll('.ib-c').forEach(function (x) { x.classList.toggle('on', +x.dataset.id === id); if (+x.dataset.id === id) { x.classList.remove('nl'); var n = x.querySelector('.ib-nl'); if (n) n.remove(); } });
        json(R.base + id).then(function (d) { pintarConv(d.conv); pintarMensajes(d.mensajes, false); }).catch(function () { LP.toast('No se pudo abrir el chat', 'error'); });
        try { history.replaceState(null, '', '?c=' + id); } catch (x) {}
    }
    function refrescarChat() {
        if (!actual) return;
        json(R.base + actual + '?desde=' + ultimo).then(function (d) { if (d.mensajes.length) pintarMensajes(d.mensajes, true); pintarConv(d.conv); }).catch(function () {});
    }
    $('ibBack').addEventListener('click', function () { $('ib').classList.remove('chat'); actual = null; cargarLista(); });

    function enviar(datos) {
        if (!conv) return;
        $('ibEnv').disabled = true;
        return json(R.base + conv.id + '/enviar', { method: 'POST', body: JSON.stringify(datos) })
            .then(function () { $('ibTxt').value = ''; $('ibTxt').style.height = ''; refrescarChat(); cargarLista(); })
            .catch(function (e) { LP.toast((e && e.message) || 'No se pudo enviar', 'error'); })
            .finally(function () { $('ibEnv').disabled = conv && !conv.puede; });
    }
    $('ibEnv').addEventListener('click', function () { var t = $('ibTxt').value.trim(); if (t) enviar({ texto: t }); });
    $('ibTxt').addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('ibEnv').click(); } });
    $('ibTxt').addEventListener('input', function () { this.style.height = 'auto'; this.style.height = Math.min(140, this.scrollHeight) + 'px'; });
    $('ibRap').addEventListener('click', function (e) { var b = e.target.closest('button'); if (!b) return; var t = $('ibTxt'); t.value = (t.value ? t.value + ' ' : '') + b.dataset.t; t.focus(); t.dispatchEvent(new Event('input')); });

    $('hEt').addEventListener('change', function () { if (conv) json(R.base + conv.id + '/etiqueta', { method: 'POST', body: JSON.stringify({ etiqueta: this.value }) }).then(cargarLista); });
    $('hCli').addEventListener('click', function () {
        if (!conv) return;
        LP.pedir({ titulo: 'Guardar como cliente', texto: '¿Cuál es el WhatsApp de ' + conv.nombre + '? (opcional, para enviarle facturas y promociones)', placeholder: 'Ej. 3001234567', ok: 'Guardar cliente' }).then(function (tel) {
            if (tel === null || tel === undefined) return;
            json(R.base + conv.id + '/cliente', { method: 'POST', body: JSON.stringify({ nombre: conv.nombre, telefono: String(tel).trim() }) })
                .then(function () { LP.toast('Cliente guardado ✅', 'ok'); refrescarChat(); cargarLista(); })
                .catch(function (e) { LP.toast((e && e.message) || 'No se pudo guardar', 'error'); });
        });
    });

    // Enviar un producto: foto + nombre, precio y enlace a la tienda en línea
    var tp;
    function buscarProd() {
        json(R.prod + '?q=' + encodeURIComponent($('ppQ').value.trim())).then(function (l) {
            $('ppGrid').innerHTML = l.length ? l.map(function (p, i) { return '<button type="button" data-i="' + i + '">' + (p.img ? '<img src="' + esc(p.img) + '" alt="">' : '<span class="sin"><i class="fa-solid fa-image"></i></span>') + '<b>' + esc(p.nombre) + '</b><span>' + esc(p.precio) + '</span></button>'; }).join('') : '<p class="text-muted">No encontré productos.</p>';
            $('ppGrid')._l = l;
        });
    }
    $('ibProd').addEventListener('click', function () { $('ppOv').style.display = 'flex'; $('ppOv').classList.add('on'); $('ppQ').value = ''; buscarProd(); setTimeout(function () { $('ppQ').focus(); }, 50); });
    $('ppQ').addEventListener('input', function () { clearTimeout(tp); tp = setTimeout(buscarProd, 250); });
    $('ppGrid').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]'); if (!b) return;
        var p = $('ppGrid')._l[+b.dataset.i];
        $('ppOv').style.display = 'none';
        enviar({ imagen: p.img || null, texto: p.nombre + ' — ' + p.precio + (p.url ? '\n👉 Véalo aquí: ' + p.url : '') });
    });

    cargarLista();
    if (actual) abrir(actual);
    setInterval(cargarLista, 10000);
    setInterval(refrescarChat, 5000);
})();
</script>
@endsection
