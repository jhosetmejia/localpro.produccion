@extends('layouts.app')

@section('title', 'Contactar clientes nuevos')

@push('css')
<style>
.cc-top{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.7rem;margin-bottom:1rem}
.cc-top div{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.8rem 1rem}
.cc-top div.v{background:#0b0d0c;color:#fff;border-color:#0b0d0c}
.cc-top b{display:block;font-size:1.6rem;font-weight:900;line-height:1}
.cc-top .v b{color:#a6f34a}
.cc-top small{font-weight:700;color:#6b7570}.cc-top .v small{color:#b8c4bc}
.cc-barra{height:10px;border-radius:99px;background:#e3ebe6;overflow:hidden;margin:.4rem 0 1rem}
.cc-barra i{display:block;height:100%;background:linear-gradient(90deg,#a3f04a,#16b84e);transition:width .3s}
.cc-wrap{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:1rem;align-items:start}
@media (max-width:950px){.cc-wrap{grid-template-columns:minmax(0,1fr)}}
.cc-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;padding:1rem 1.1rem}
.cc-card h3{font-size:1.05rem;font-weight:900;margin:0 0 .7rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem}
.cc-item{display:flex;align-items:center;gap:.7rem;padding:.6rem .2rem;border-bottom:1px solid #eef2ef}
.cc-item:last-child{border-bottom:0}
.cc-av{width:40px;height:40px;border-radius:50%;background:#dcfce7;color:#11702f;display:grid;place-items:center;font-weight:900;flex:none}
.cc-item .t{flex:1;min-width:0}
.cc-item .t b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cc-item .t small{color:#6b7570;font-weight:600}
.cc-wa{background:#25d366;color:#fff;border:0;border-radius:999px;padding:.55rem 1rem;font-weight:800;white-space:nowrap;display:flex;align-items:center;gap:.4rem}
.cc-wa:hover{background:#1ebe5b;color:#fff}
.cc-mini{border:0;background:#f1f5f2;border-radius:999px;width:34px;height:34px;display:grid;place-items:center;color:#55605a}
.cc-item{position:relative}
.cc-item:has(.dropdown-menu.show){z-index:20}
.cc-item.hecho .cc-av,.cc-item.hecho .t{opacity:.55}
.cc-item .dropdown-menu{z-index:1050;background:#fff;border:1.5px solid #e3ebe6;border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.14);padding:.35rem;min-width:190px}
.cc-item .dropdown-item{border-radius:10px;font-weight:700;padding:.55rem .8rem}
.cc-item .dropdown{flex:none}
@media (max-width:480px){.cc-wa{padding:.5rem .8rem}.cc-item .t small{font-size:.8rem}}
.cc-item.hecho .cc-wa,.cc-wa:disabled{background:#dcfce7;color:#11702f;cursor:default}
.cc-estado{font-size:.75rem;font-weight:800;border-radius:999px;padding:.15rem .55rem;background:#dcfce7;color:#11702f}
.cc-estado.mal{background:#fde2e8;color:#b0173f}
.cc-item.hecho.malo .cc-wa,.cc-item.malo .cc-wa:disabled{background:#fde2e8;color:#b0173f}
.cc-item.malo .cc-av{background:#fde2e8;color:#b0173f}
.cc-msg{white-space:pre-wrap;background:#e7fbe9;border-radius:14px 14px 14px 4px;padding:.8rem 1rem;font-size:.92rem;line-height:1.45;border:1px solid #c9efcf}
.cc-vacio{text-align:center;padding:2rem 1rem;color:#6b7570}
.cc-vacio i{font-size:2.2rem;color:#16b84e;margin-bottom:.5rem}
</style>
@endpush

@section('content')
@php $hechos = $lista->whereNotNull('contactado_at')->count(); $total = $lista->count(); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('vender.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Vender más</a> · <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp marketing</small><h1>Contactar clientes <span>nuevos</span></h1>
            <span class="text-muted fw-semibold">Cada día le asignamos {{ $cuota }} contactos de su base de datos. Escríbales con un toque y llegue a más clientes. A cada persona se le escribe <b>una sola vez</b>: los que ya se contactaron no se le vuelven a dar a nadie del equipo.</span></div>
        <div class="lp-quick"><a href="{{ route('clientes.contactos') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-mobile-screen me-1"></i>Subir más contactos</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (!$catalogo)<div class="alert alert-warning fw-bold">Active su tienda en línea en "Mi catálogo en línea" para que el mensaje lleve el enlace de su catálogo.</div>@endif

    <div class="cc-top">
        <div class="v"><b><span id="ccHechos">{{ $hechos }}</span> / {{ $total }}</b><small>sus contactos de hoy</small></div>
        <div><b>{{ number_format($pendientesBase, 0, ',', '.') }}</b><small>contactos por escribir en la base</small></div>
        <div><b>{{ number_format($contactadosTotal, 0, ',', '.') }}</b><small>ya contactados en total</small></div>
    </div>
    <div class="cc-barra"><i id="ccBarra" style="width:{{ $total ? round($hechos / $total * 100) : 0 }}%"></i></div>

    <div class="cc-wrap">
        <div class="cc-card">
            <h3>Sus contactos de hoy
                @if ($total && $hechos >= $total)
                <form method="post" action="{{ route('clientes.contactar.mas') }}">@csrf<button class="btn btn-sm btn-dark rounded-pill fw-bold"><i class="fa-solid fa-plus me-1"></i>Pedir {{ $cuota }} más</button></form>
                @endif
            </h3>
            @forelse ($lista as $c)
            @php
                $p = $c->persona;
                $nombre = trim((string) $p?->razon_social);
                $primer = preg_match('/^contacto\s/i', $nombre) ? '' : \Illuminate\Support\Str::of($nombre)->explode(' ')->first();
                $wa = numero_whatsapp($p?->telefono);
            @endphp
             @php $txtEst = ['enviado' => 'Ya contactado', 'numero_malo' => 'Número malo', 'no_interesa' => 'No le interesa'][$c->contacto_estado] ?? 'Ya contactado'; @endphp
            <div class="cc-item {{ $c->contactado_at ? 'hecho' : '' }} {{ in_array($c->contacto_estado, ['numero_malo', 'no_interesa'], true) ? 'malo' : '' }}" data-id="{{ $c->id }}" data-wa="{{ $wa }}" data-nombre="{{ $primer }}">
                <div class="cc-av">{{ mb_strtoupper(mb_substr($nombre ?: '?', 0, 1)) }}</div>
                <div class="t"><b>{{ $nombre ?: 'Sin nombre' }}</b><small>{{ $p?->telefono }}</small>
                    <span class="cc-estado-c">@if ($c->contacto_estado)<span class="cc-estado {{ $c->contacto_estado !== 'enviado' ? 'mal' : '' }}">{{ ['enviado' => 'Enviado ✓', 'numero_malo' => 'Número malo', 'no_interesa' => 'No le interesa'][$c->contacto_estado] ?? '' }}</span>@endif</span>
                    @if ($c->contactado_at && (int) $c->contactado_por !== (int) auth()->id())<small class="d-block text-danger fw-bold">Ya le escribió {{ $c->contactadoPor->name ?? 'otra persona' }}</small>@endif
                </div>
                @if ($wa)<button type="button" class="cc-wa" data-enviar {{ $c->contactado_at ? 'disabled' : '' }}><i class="fa-brands fa-whatsapp"></i> <span>{{ $c->contactado_at ? $txtEst : 'Enviar' }}</span></button>@endif
                <div class="dropdown">
                    <button class="cc-mini" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-label="Más"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" data-marcar="numero_malo">📵 Número malo</button></li>
                        <li><button class="dropdown-item" type="button" data-marcar="no_interesa">🙅 No le interesa</button></li>
                        @if ($esJefe || !$c->contactado_at || ((int) $c->contactado_por === (int) auth()->id() && $c->contactado_at->gt(now()->subMinutes(30))))
                        <li><button class="dropdown-item" type="button" data-marcar="deshacer">↩ Quitar marca</button></li>
                        @endif
                    </ul>
                </div>
            </div>
            @empty
            <div class="cc-vacio"><i class="fa-solid fa-address-book d-block"></i><b>No hay contactos para escribir.</b><br>Suba los contactos de su celular para crear su base de datos.<br>
                <a href="{{ route('clientes.contactos') }}" class="btn btn-dark rounded-pill fw-bold mt-2">📱 Crear base de datos</a></div>
            @endforelse
        </div>

        <div>
            <div class="cc-card">
                <h3>Mensaje que se envía</h3>
                <div class="cc-msg" id="ccVista"></div>
                <small class="text-muted d-block mt-2">Se cambia solo: <b>{nombre}</b> por el nombre de cada persona, <b>{tienda}</b> por su negocio y <b>{catalogo}</b> por el enlace de su tienda en línea.</small>
                @if ($esJefe)
                <form method="post" action="{{ route('clientes.contactar.ajustes') }}" class="mt-3">
                    @csrf
                    <label class="form-label small fw-bold">Cambiar el mensaje</label>
                    <textarea name="mensaje" id="ccMensaje" rows="8" class="form-control" maxlength="1000">{{ old('mensaje', $mensaje) }}</textarea>
                    <div class="d-flex gap-2 align-items-end mt-2 flex-wrap">
                        <div><label class="form-label small fw-bold mb-1">Contactos al día por persona</label>
                            <input type="number" name="contactos_diarios" min="1" max="200" class="form-control" style="width:120px" value="{{ old('contactos_diarios', $cuota) }}"></div>
                        <button class="btn btn-primary rounded-pill fw-bold">Guardar</button>
                        <button type="button" class="btn btn-light rounded-pill" id="ccOriginal">Volver al mensaje original</button>
                    </div>
                </form>
                @else
                <textarea id="ccMensaje" class="d-none">{{ $mensaje }}</textarea>
                @endif
            </div>

            @if ($esJefe && $equipo->isNotEmpty())
            <div class="cc-card mt-3">
                <h3>Avance del equipo hoy</h3>
                @foreach ($equipo as $e)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                    <span class="fw-bold">{{ $e['nombre'] }}</span>
                    <span class="small"><b>{{ $e['hechos'] }}</b> / {{ $e['asignados'] }} hoy · {{ $e['total'] }} en total</span>
                </div>
                @endforeach
            </div>
            @endif
            <div class="cc-card mt-3 small" style="background:#fffbeb;border-color:#fde68a;color:#78501a">
                <b>Consejos:</b> escriba en horario de 9 a. m. a 7 p. m., no envíe el mismo mensaje dos veces seguidas y si alguien le pide no escribirle más, márquelo como "No le interesa" (Ley 1581 de datos personales).
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var TIENDA = @json($tienda->nombre), CATALOGO = @json($catalogo ?: ''), TOKEN = @json(csrf_token());
    var URL_MARCAR = @json(url('/clientes/__ID__/contactado'));
    var original = @json(\App\Http\Controllers\contactarController::MENSAJE);
    var caja = document.getElementById('ccMensaje');
    function armar(nombre) {
        return (caja.value || original)
            .replace(/\{nombre\}/g, nombre || '').replace(/¡Hola\s+!/, '¡Hola!').replace(/Hola\s+,/, 'Hola,')
            .replace(/\{tienda\}/g, TIENDA).replace(/\{catalogo\}/g, CATALOGO);
    }
    function vista() { document.getElementById('ccVista').textContent = armar('María'); }
    caja.addEventListener('input', vista); vista();
    var btnOrig = document.getElementById('ccOriginal');
    if (btnOrig) btnOrig.addEventListener('click', function () { caja.value = original; vista(); });

    function contar() {
        var items = document.querySelectorAll('.cc-item'), h = document.querySelectorAll('.cc-item.hecho').length;
        document.getElementById('ccHechos').textContent = h;
        document.getElementById('ccBarra').style.width = (items.length ? Math.round(h / items.length * 100) : 0) + '%';
        if (items.length && h === items.length && window.LP && LP.toast) LP.toast('¡Terminó sus contactos de hoy! 🎉 Puede pedir más.', 'ok');
    }
    function aviso(m, tipo) { if (window.LP && LP.toast) LP.toast(m, tipo || 'error'); }
    function pedir(item, metodo, cuerpo) {
        return fetch(URL_MARCAR.replace('__ID__', item.dataset.id), {
            method: metodo, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': TOKEN },
            body: cuerpo ? JSON.stringify(cuerpo) : undefined
        }).then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { d._ok = r.ok; return d; }); });
    }
    function yaContactado(item, mensaje) {
        item.classList.add('hecho');
        var b = item.querySelector('[data-enviar]');
        if (b) { b.disabled = true; b.querySelector('span').textContent = 'Ya contactado'; }
        if (mensaje) aviso(mensaje);
        contar();
    }
    function marcar(item, estado) {
        return pedir(item, 'POST', { estado: estado }).then(function (d) {
            if (d._ok) return true;
            if (d.repetido) { yaContactado(item, d.mensaje); return false; }
            aviso(d.mensaje || 'No se pudo guardar. Revise la conexión.');
            return false;
        }).catch(function () { aviso('No se pudo guardar. Revise la conexión.'); });
    }
    document.addEventListener('click', function (e) {
        var env = e.target.closest('[data-enviar]'), mk = e.target.closest('[data-marcar]');
        if (env && !env.disabled) {
            var it = env.closest('.cc-item');
            var ventana = window.open('', '_blank'); // se abre ya para que el navegador no la bloquee
            env.disabled = true;
            // Antes de escribir, se revisa que nadie del equipo le haya escrito ya a ese número
            pedir(it, 'GET').then(function (d) {
                if (d.libre === false) { if (ventana) ventana.close(); yaContactado(it, d.mensaje); return; }
                var url = 'https://wa.me/' + it.dataset.wa + '?text=' + encodeURIComponent(armar(it.dataset.nombre));
                if (ventana) ventana.location.href = url; else window.open(url, '_blank');
                yaContactado(it);
                marcar(it, 'enviado');
            }).catch(function () { if (ventana) ventana.close(); env.disabled = false; aviso('No se pudo revisar el contacto. Revise la conexión.'); });
        }
        if (mk) {
            var it2 = mk.closest('.cc-item'), est = mk.dataset.marcar;
            var NOM = { enviado: 'Ya contactado', numero_malo: 'Número malo', no_interesa: 'No le interesa' };
            var ETQ = { enviado: 'Enviado ✓', numero_malo: 'Número malo', no_interesa: 'No le interesa' };
            var antes = { hecho: it2.classList.contains('hecho'), malo: it2.classList.contains('malo'), html: it2.querySelector('.cc-estado-c') ? it2.querySelector('.cc-estado-c').innerHTML : '' };
            var b2 = it2.querySelector('[data-enviar]');
            var antesTxt = b2 ? b2.querySelector('span').textContent : '', antesDis = b2 ? b2.disabled : false;
            function pintarEstado(e) {
                it2.classList.toggle('hecho', e !== 'deshacer');
                it2.classList.toggle('malo', e === 'numero_malo' || e === 'no_interesa');
                var cont = it2.querySelector('.cc-estado-c');
                if (cont) cont.innerHTML = e === 'deshacer' ? '' : '<span class="cc-estado ' + (e !== 'enviado' ? 'mal' : '') + '">' + ETQ[e] + '</span>';
                if (b2) { b2.disabled = e !== 'deshacer'; b2.querySelector('span').textContent = e === 'deshacer' ? 'Enviar' : NOM[e]; }
            }
            pintarEstado(est);
            marcar(it2, est).then(function (ok) {
                if (ok) { if (est !== 'deshacer') aviso(est === 'numero_malo' ? '📵 Marcado como número malo' : est === 'no_interesa' ? '🙅 Marcado: no le interesa' : 'Guardado', 'ok'); }
                else {
                    // No se guardó: se deja como estaba
                    it2.classList.toggle('hecho', antes.hecho); it2.classList.toggle('malo', antes.malo);
                    var cont = it2.querySelector('.cc-estado-c'); if (cont) cont.innerHTML = antes.html;
                    if (b2) { b2.disabled = antesDis; b2.querySelector('span').textContent = antesTxt; }
                }
                contar();
            });
            contar();
        }
    });
})();
</script>
@endpush
