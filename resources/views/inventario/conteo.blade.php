@extends('layouts.app')

@section('title', $conteo->nombre)

@push('css')
<style>
.cn-panel{position:sticky;top:72px;z-index:20;background:rgba(244,248,245,.94);backdrop-filter:blur(10px);padding:.4rem 0 .8rem;margin-bottom:.4rem}
.cn-nums{display:grid;grid-template-columns:1.3fr repeat(4,1fr);gap:10px}
.cn-n{background:#fff;border:1.5px solid var(--lp-line);border-radius:18px;padding:.7rem .9rem}
.cn-n small{display:block;color:var(--lp-muted);font-weight:700;font-size:.72rem;white-space:nowrap}
.cn-n b{display:block;font-size:1.3rem;font-weight:900;line-height:1.15}
.cn-n.prog{background:var(--lp-black);border-color:var(--lp-black);color:#fff}
.cn-n.prog small{color:#a8b3ab}.cn-n.prog b{color:var(--lp-lime)}
.cn-n .bar{height:6px;border-radius:999px;background:rgba(255,255,255,.15);overflow:hidden;margin-top:.4rem}
.cn-n .bar i{display:block;height:100%;background:var(--lp-grad);transition:width .3s}
.cn-n.falta b{color:#c8102e}.cn-n.sobra b{color:#0b7fd1}
.cn-acc{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.cn-scan{flex:none;border:0;border-radius:999px;background:var(--lp-grad);color:#0b0d0c;font-weight:900;padding:.75rem 1.4rem;font-size:1rem;box-shadow:0 12px 24px -14px rgba(22,179,74,.9)}
.cn-cod{flex:1;min-width:200px;border-radius:999px;border:1.5px solid var(--lp-line);padding:.7rem 1.1rem;font-weight:700}
.cn-cod:focus{outline:0;border-color:var(--lp-green)}
.cn-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;align-items:center}
.cn-chip{border:1.5px solid var(--lp-line);background:#fff;border-radius:999px;padding:.35rem .85rem;font-weight:800;font-size:.8rem;cursor:pointer}
.cn-chip.on{background:var(--lp-black);border-color:var(--lp-black);color:#fff}
.cn-buscar{flex:1;min-width:160px;border-radius:999px;border:1.5px solid var(--lp-line);padding:.35rem .9rem;font-size:.85rem}
.cn-ciego{display:flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:700;color:var(--lp-muted);cursor:pointer;margin-left:auto}
.cn-fin-btn{flex:none;border:0;border-radius:999px;background:var(--lp-black);color:#fff;font-weight:900;padding:.75rem 1.3rem}
.cn-fin-btn i{color:var(--lp-lime)}
.cn-resultado{background:#fff;border:2px solid var(--lp-black);border-radius:24px;padding:1.1rem 1.2rem;margin:.6rem 0 1rem}
.cn-resultado h3{font-size:1.1rem;font-weight:900;margin:0}.cn-resultado p{color:var(--lp-muted);margin:.1rem 0 .8rem;font-size:.85rem}
.cn-rg{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.cn-rg .r{border:1.5px solid var(--lp-line);border-radius:18px;padding:.8rem;text-align:left;background:#fafcfb;transition:.15s}
.cn-rg .r:hover,.cn-rg .r.on{transform:translateY(-2px);box-shadow:0 12px 22px -16px rgba(0,0,0,.5)}
.cn-rg .r b{display:block;font-size:1.8rem;font-weight:900;line-height:1}
.cn-rg .r span{display:block;font-weight:800;margin-top:.2rem}.cn-rg .r small{color:var(--lp-muted);font-size:.72rem}
.cn-rg .ok b{color:#14753a}.cn-rg .menos b{color:#c8102e}.cn-rg .sin b{color:#8a5a00}.cn-rg .mas b{color:#0b6fb8}
@media (max-width:767px){.cn-rg{grid-template-columns:1fr 1fr}}
.cn-ultimo{margin-top:8px;font-weight:800;font-size:.88rem;min-height:1.3em}
.cn-ultimo.ok{color:#14753a}.cn-ultimo.mal{color:#c8102e}

.cn-prod{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:.8rem 1rem;margin-bottom:10px}
.cn-cab{display:flex;gap:.75rem;align-items:center;margin-bottom:.4rem}
.cn-foto{flex:none;width:44px;height:44px;border-radius:12px;background:#eef3ef;overflow:hidden;display:grid;place-items:center;font-weight:900;color:#16b84e}
.cn-foto img{width:100%;height:100%;object-fit:cover}
.cn-cab b{display:block;line-height:1.2}.cn-cab small{color:var(--lp-muted);font-weight:600}
.cn-var{display:grid;grid-template-columns:1fr auto auto 78px;gap:.6rem;align-items:center;padding:.45rem 0;border-top:1px dashed #edf2ee}
.cn-var .det{font-weight:800}.cn-var .det small{display:block;color:var(--lp-muted);font-weight:600;font-size:.72rem}
.cn-sis{font-size:.8rem;color:var(--lp-muted);font-weight:700;white-space:nowrap;text-align:right}
.cn-sis b{color:var(--lp-ink);font-size:.95rem}
.cn-step{display:flex;align-items:center;border:1.5px solid var(--lp-line);border-radius:999px;overflow:hidden;background:#fafcfb}
.cn-step button{border:0;background:none;width:38px;height:38px;font-size:1.15rem;font-weight:900;color:var(--lp-ink)}
.cn-step button:active{background:#e7f6ec}
.cn-step input{width:54px;border:0;background:none;text-align:center;font-weight:900;font-size:1.05rem;-moz-appearance:textfield}
.cn-step input::-webkit-outer-spin-button,.cn-step input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.cn-step input::placeholder{color:#c3ccc6;font-weight:700}
.cn-dif{font-weight:900;font-size:.78rem;border-radius:999px;padding:.25rem .5rem;text-align:center;white-space:nowrap}
.cn-dif.ok{background:#dcfce7;color:#14753a}.cn-dif.menos{background:#fde2e8;color:#b0173f}.cn-dif.mas{background:#dbeefe;color:#0b6fb8}.cn-dif.nada{background:#f1f4f2;color:#9aa49e}
.cn-var.guardando .cn-step{border-color:#ffcc33}.cn-var.flash{animation:cnflash 1s}
@keyframes cnflash{0%{background:#eafbe9}100%{background:transparent}}
body.ciego .cn-sis,body.ciego .cn-dif,body.ciego .cn-n.falta,body.ciego .cn-n.sobra{visibility:hidden}

.cn-fin{background:linear-gradient(135deg,#0b0d0c,#1d2a21);color:#fff;border-radius:24px;padding:1.3rem 1.4rem;margin-top:1rem}
.cn-fin h3{color:#fff;font-size:1.15rem;font-weight:900;margin:0 0 .3rem}
.cn-fin p{color:#b8c4bc;margin:0 0 .9rem;font-size:.9rem}
.cn-fin label{display:flex;gap:.5rem;align-items:flex-start;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:14px;padding:.6rem .8rem;margin-bottom:.5rem;cursor:pointer;font-size:.9rem}
.cn-fin label:has(input:checked){border-color:var(--lp-green)}
.cn-fin .cn-res{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:1rem}
.cn-fin .cn-res div{background:rgba(255,255,255,.06);border-radius:14px;padding:.6rem .8rem}
.cn-fin .cn-res small{display:block;color:#a8b3ab;font-size:.72rem;font-weight:700}
.cn-fin .cn-res b{font-size:1.15rem}
.cn-cerrado{background:#dcfce7;border:1.5px solid #b6efc8;border-radius:20px;padding:1rem 1.2rem;margin-bottom:1rem;color:#14532d}
.cn-cerrado.cancelado{background:#f1f4f2;border-color:#e0e6e2;color:#55605a}

@media (max-width:991px){.cn-nums{grid-template-columns:repeat(3,1fr)}.cn-n.prog{grid-column:1/-1}}
@media (max-width:575px){.cn-panel{top:64px}.cn-nums{grid-template-columns:repeat(2,1fr)}.cn-nums .cn-n.sis{display:none}
  .cn-var{grid-template-columns:1fr auto;row-gap:.3rem}.cn-sis{text-align:left;grid-column:1}.cn-dif{grid-column:2;grid-row:2}.cn-step{grid-column:2;grid-row:1}.cn-fin .cn-res{grid-template-columns:1fr 1fr}}
/* Planilla impresa para contar en papel */
@media print{
  .lp-side,.sb-topnav,.inv-tabs,.cn-acc,.cn-chips,.cn-ultimo,.cn-fin,.lp-quick,.cn-step button,#layoutSidenav_nav{display:none!important}
  .cn-panel{position:static;background:none}.cn-prod{break-inside:avoid;border-color:#999}
  .cn-step{border:0}.cn-step input{border-bottom:1.5px solid #000;border-radius:0;width:70px}
}
</style>
@endpush

@section('content')
@php
    $puedeAjustar = auth()->user()->can('ajustar-inventario');
    $abierto = $conteo->abierto;
    $catNom = $conteo->categoria?->caracteristica?->nombre;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('inventario.conteos.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Conteo físico</a></small>
            <h1 style="font-size:clamp(1.5rem,3vw,2.1rem)">{{ $conteo->nombre }}</h1>
            <small class="text-muted text-transform-none" style="text-transform:none;letter-spacing:0;color:var(--lp-muted)!important">{{ $catNom ? 'Solo ' . $catNom : 'Toda la tienda' }} · abierto el {{ $conteo->created_at->format('d/m/Y h:i a') }}{{ $conteo->user ? ' por ' . $conteo->user->name : '' }}</small></div>
        <div class="lp-quick"><button type="button" class="btn btn-light border" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Imprimir planilla</button></div>
    </div>

    @if ($conteo->estado === 'aplicado')
    <div class="cn-cerrado"><b><i class="fa-solid fa-circle-check me-1"></i>Inventario ajustado el {{ $conteo->aplicado_at?->format('d/m/Y h:i a') }}{{ $conteo->aplicador ? ' por ' . $conteo->aplicador->name : '' }}.</b>
        <div class="small mt-1">{{ $conteo->resumen['ajustadas'] ?? 0 }} tallas/colores corregidas · faltaban {{ cantidad($conteo->resumen['faltan'] ?? 0) }} prendas · sobraban {{ cantidad($conteo->resumen['sobran'] ?? 0) }}. Los cambios quedaron en <a href="{{ route('inventario.movimientos') }}">Movimientos</a>.</div></div>
    @elseif ($conteo->estado === 'cancelado')
    <div class="cn-cerrado cancelado"><b>Este conteo se canceló.</b> No cambió el inventario.</div>
    @endif

    <div class="cn-panel">
        <div class="cn-nums">
            <div class="cn-n prog"><small>Contadas</small><b><span id="nCont">{{ $resumen['contadas'] }}</span> <span style="font-size:.85rem;color:#a8b3ab">de {{ $resumen['refs'] }} tallas/colores</span></b><div class="bar"><i id="nBar" style="width:{{ $resumen['refs'] ? round($resumen['contadas'] / $resumen['refs'] * 100, 1) : 0 }}%"></i></div></div>
            <div class="cn-n"><small>Prendas contadas</small><b id="nFis">{{ cantidad($resumen['fisico']) }}</b></div>
            <div class="cn-n sis"><small>El sistema dice (de esas)</small><b id="nSis">{{ cantidad($resumen['sistema_contadas']) }}</b></div>
            <div class="cn-n falta"><small>Faltan</small><b id="nFal">{{ cantidad($resumen['faltan']) }}</b></div>
            <div class="cn-n sobra"><small>Sobran</small><b id="nSob">{{ cantidad($resumen['sobran']) }}</b></div>
        </div>
        @if ($abierto)
        <div class="cn-acc">
            <button type="button" class="cn-scan" id="btnScan"><i class="fa-solid fa-camera me-1"></i>Escanear prendas</button>
            <input type="text" id="cod" class="cn-cod" placeholder="O escriba / lea con pistola el código y Enter" autocomplete="off" inputmode="text">
            <button type="button" class="cn-fin-btn" id="btnTermine"><i class="fa-solid fa-flag-checkered me-1"></i>Terminé</button>
        </div>
        <div class="cn-ultimo" id="ultimo"></div>
        @endif
        <div class="cn-chips">
            <button type="button" class="cn-chip on" data-f="todas">Todas</button>
            <button type="button" class="cn-chip" data-f="ok">✓ Están</button>
            <button type="button" class="cn-chip" data-f="menos">Faltan</button>
            <button type="button" class="cn-chip" data-f="sin">No aparecieron</button>
            <button type="button" class="cn-chip" data-f="mas">Sobran</button>
            <input type="search" class="cn-buscar" id="buscar" placeholder="Buscar producto, talla o color…">
            <label class="cn-ciego" title="Para que quien cuenta no se deje llevar por lo que dice el sistema"><input type="checkbox" id="ciego"> Contar a ciegas</label>
        </div>
    </div>

    <div class="cn-resultado" id="resultado" style="display:none">
        <h3><i class="fa-solid fa-flag-checkered me-1"></i>Resultado del conteo</h3>
        <p>Toque un grupo para ver cuáles son.</p>
        <div class="cn-rg">
            <button type="button" class="r ok" data-ir="ok"><b id="rOk">0</b><span>✓ Están completas</span><small>Lo que hay es lo que dice el sistema</small></button>
            <button type="button" class="r menos" data-ir="menos"><b id="rMenos">0</b><span>Faltan prendas</span><small>Se encontraron menos de las del sistema</small></button>
            <button type="button" class="r sin" data-ir="sin"><b id="rSin">0</b><span>No aparecieron</span><small>No se escaneó ninguna (el sistema dice que hay)</small></button>
            <button type="button" class="r mas" data-ir="mas"><b id="rMas">0</b><span>Sobran</span><small>Hay más de las que dice el sistema</small></button>
        </div>
    </div>

    <div id="lista">
        @foreach ($grupos as $pid => $items)
        @php $p = $items->first()->variante->producto; @endphp
        <div class="cn-prod" data-prod="{{ $pid }}">
            <div class="cn-cab">
                <div class="cn-foto">@if ($p->img_path)<img src="{{ asset('storage/productos/' . rawurlencode($p->img_path)) }}" alt="" loading="lazy">@else{{ mb_strtoupper(mb_substr($p->nombre, 0, 1)) }}@endif</div>
                <div><b>{{ $p->nombre }}</b><small>{{ $p->codigo }}</small></div>
            </div>
            @foreach ($items as $i)
            @php $v = $i->variante; $det = trim($v->talla . ' ' . $v->color); @endphp
            <div class="cn-var" data-item="{{ $i->id }}" data-sis="{{ $i->stock_sistema }}" data-q="{{ mb_strtolower($p->nombre . ' ' . $p->codigo . ' ' . $det . ' ' . $v->codigo_barras) }}">
                <div class="det">{{ $det ?: 'Única' }}<small>{{ $v->codigo_barras }}</small></div>
                <div class="cn-sis">Sistema: <b>{{ cantidad($i->stock_sistema) }}</b></div>
                <div class="cn-step">
                    @if ($abierto)<button type="button" data-mas="-1" aria-label="Menos">−</button>@endif
                    <input type="number" inputmode="decimal" min="0" step="any" value="{{ $i->contado !== null ? (float) $i->contado : '' }}" placeholder="—" aria-label="Cantidad contada" {{ $abierto ? '' : 'disabled' }}>
                    @if ($abierto)<button type="button" data-mas="1" aria-label="Más">+</button>@endif
                </div>
                <div class="cn-dif nada">—</div>
            </div>
            @endforeach
        </div>
        @endforeach
        <div class="lp-empty" id="nada" style="display:none">No hay tallas/colores con ese filtro.</div>
    </div>

    @if ($abierto && $puedeAjustar)
    <form class="cn-fin" method="post" action="{{ route('inventario.conteos.aplicar', $conteo) }}" id="fAplicar">
        @csrf
        <h3>¿Terminó de contar?</h3>
        <p>Al ajustar, el inventario del sistema queda igual a lo que contaron. Cada cambio queda anotado en Movimientos.</p>
        <div class="cn-res">
            <div><small>Faltan (a costo)</small><b id="vFal">{{ dinero($resumen['valor_faltan']) }}</b></div>
            <div><small>Sobran (a costo)</small><b id="vSob">{{ dinero($resumen['valor_sobran']) }}</b></div>
            <div><small>Sin contar</small><b><span id="nSin">{{ $resumen['refs'] - $resumen['contadas'] }}</span> tallas/colores</b></div>
        </div>
        <div id="opSin">
            <label><input type="radio" name="sin_contar" value="dejar" checked> <span><b>Lo que no contamos, dejarlo como está</b><br><small style="color:#a8b3ab">Úselo si solo contó una parte de la tienda.</small></span></label>
            <label><input type="radio" name="sin_contar" value="cero"> <span><b>Lo que no contamos, ponerlo en 0</b><br><small style="color:#a8b3ab">Úselo si contó todo y lo que no apareció es porque no está.</small></span></label>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-check me-1"></i>Ajustar inventario</button>
            <button type="button" class="btn btn-outline-light" id="btnCancelar">Cancelar conteo</button>
        </div>
    </form>
    <form method="post" action="{{ route('inventario.conteos.cancelar', $conteo) }}" id="fCancelar" class="d-none">@csrf</form>
    @elseif ($abierto)
    <div class="lp-box mt-3 text-center"><b>Cuando terminen de contar,</b> el dueño o administrador revisa las diferencias y ajusta el inventario.</div>
    @endif
</div>
@endsection

@push('js')
<script src="{{ asset('js/escaner.js') }}?v=1"></script>
<script>
(function () {
    var URL_CONTAR = @json(route('inventario.conteos.contar', $conteo)), CSRF = @json(csrf_token()), ABIERTO = @json($abierto);
    var lista = document.getElementById('lista'), filtro = 'todas';
    function num(v) { return v === '' || v === null || v === undefined ? null : parseFloat(v); }
    function fmt(n) { return (Math.round(n * 1000) / 1000).toLocaleString('es-CO'); }
    function money(n) { return '$' + (LP && LP.formatoDinero ? LP.formatoDinero(Math.round(n)) : Math.round(n).toLocaleString('es-CO')); }

    // Diferencia de cada fila
    function pintar(fila) {
        var inp = fila.querySelector('input'), c = num(inp.value), s = parseFloat(fila.dataset.sis), d = fila.querySelector('.cn-dif');
        // "No apareció": no se contó, o se contó 0 teniendo el sistema algo
        fila.dataset.est = (c === null || c === 0) && s > 0 ? 'sin' : (c === null ? 'sincero' : (Math.abs(c - s) < 0.0005 ? 'ok' : (c < s ? 'menos' : 'mas')));
        if (c === null) { d.className = 'cn-dif nada'; d.textContent = '—'; return; }
        var x = Math.round((c - s) * 1000) / 1000;
        d.className = 'cn-dif ' + (x === 0 ? 'ok' : (x < 0 ? 'menos' : 'mas'));
        d.textContent = x === 0 ? '✓ Cuadra' : (x < 0 ? 'Faltan ' + fmt(-x) : 'Sobran ' + fmt(x));
    }
    function aplicarFiltro() {
        if (document.getElementById('resultado').style.display !== 'none') contarGrupos();
        var t = document.getElementById('buscar').value.trim().toLowerCase(), hay = 0;
        lista.querySelectorAll('.cn-prod').forEach(function (p) {
            var n = 0;
            p.querySelectorAll('.cn-var').forEach(function (f) {
                var ok = (filtro === 'todas' || f.dataset.est === filtro) && (!t || f.dataset.q.indexOf(t) !== -1);
                f.style.display = ok ? '' : 'none'; if (ok) n++;
            });
            p.style.display = n ? '' : 'none'; hay += n;
        });
        document.getElementById('nada').style.display = hay ? 'none' : '';
    }
    function resumen(r) {
        if (!r) return;
        document.getElementById('nCont').textContent = r.contadas;
        document.getElementById('nBar').style.width = (r.refs ? r.contadas / r.refs * 100 : 0) + '%';
        document.getElementById('nFis').textContent = fmt(r.fisico);
        document.getElementById('nSis').textContent = fmt(r.sistema_contadas);
        document.getElementById('nFal').textContent = fmt(r.faltan);
        document.getElementById('nSob').textContent = fmt(r.sobran);
        var e;
        if ((e = document.getElementById('vFal'))) e.textContent = money(r.valor_faltan);
        if ((e = document.getElementById('vSob'))) e.textContent = money(r.valor_sobran);
        if ((e = document.getElementById('nSin'))) e.textContent = r.refs - r.contadas;
    }
    function enviar(datos) {
        return fetch(URL_CONTAR, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(datos) })
            .then(function (r) { return r.json(); });
    }

    lista.querySelectorAll('.cn-var').forEach(pintar);
    aplicarFiltro();

    // Escribir o usar − / + : se guarda solo
    var tiempos = {};
    function guardar(fila) {
        var id = fila.dataset.item, inp = fila.querySelector('input');
        pintar(fila); fila.classList.add('guardando');
        clearTimeout(tiempos[id]);
        tiempos[id] = setTimeout(function () {
            enviar({ item: +id, contado: num(inp.value) }).then(function (r) {
                fila.classList.remove('guardando');
                if (!r.ok) { LP.toast(r.msg || 'No se pudo guardar', 'error'); return; }
                resumen(r.resumen);
            }).catch(function () { fila.classList.remove('guardando'); LP.toast('Sin conexión: no se guardó. Intente de nuevo.', 'error'); });
        }, 450);
    }
    if (ABIERTO) {
        lista.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mas]'); if (!b) return;
            var fila = b.closest('.cn-var'), inp = fila.querySelector('input');
            inp.value = Math.max(0, (num(inp.value) || 0) + parseInt(b.dataset.mas, 10));
            guardar(fila);
        });
        lista.addEventListener('input', function (e) { if (e.target.matches('.cn-var input')) guardar(e.target.closest('.cn-var')); });
    }

    // Escanear: cada lectura suma 1 a esa talla/color
    var ultimo = document.getElementById('ultimo');
    function leer(codigo) {
        codigo = String(codigo || '').trim(); if (!codigo) return;
        enviar({ codigo: codigo }).then(function (r) {
            var est = document.getElementById('lpEscEstado');
            if (!r.ok) {
                ultimo.className = 'cn-ultimo mal'; ultimo.textContent = '✕ ' + r.msg;
                if (est) { est.textContent = '✕ ' + r.msg; est.className = 'estado mal'; }
                return;
            }
            var it = r.item, fila = lista.querySelector('[data-item="' + it.id + '"]');
            var txt = '✓ ' + it.nombre + (it.detalle ? ' · ' + it.detalle : '') + ' → ' + fmt(it.contado);
            ultimo.className = 'cn-ultimo ok'; ultimo.textContent = txt;
            if (est) { est.textContent = txt; est.className = 'estado ok'; }
            if (fila) {
                fila.querySelector('input').value = it.contado; pintar(fila);
                fila.classList.remove('flash'); void fila.offsetWidth; fila.classList.add('flash');
            } else { setTimeout(function () { location.reload(); }, 1200); } // talla nueva agregada al conteo
            resumen(r.resumen); aplicarFiltro();
        }).catch(function () { ultimo.className = 'cn-ultimo mal'; ultimo.textContent = '✕ Sin conexión. Vuelva a escanear esa prenda.'; });
    }
    var btn = document.getElementById('btnScan');
    if (btn) btn.addEventListener('click', function () { LPEscaner.abrir({ titulo: 'Escanee prenda por prenda', continuo: true, alLeer: leer }); });
    var cod = document.getElementById('cod');
    if (cod) cod.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); leer(cod.value); cod.value = ''; } });

    // Filtros
    function elegir(f) {
        document.querySelectorAll('.cn-chip').forEach(function (x) { x.classList.toggle('on', x.dataset.f === f); });
        document.querySelectorAll('.cn-rg .r').forEach(function (x) { x.classList.toggle('on', x.dataset.ir === f); });
        filtro = f; aplicarFiltro();
    }
    document.querySelectorAll('.cn-chip').forEach(function (c) { c.addEventListener('click', function () { elegir(c.dataset.f); }); });

    // "Terminé": resultado en 4 grupos (están, faltan, no aparecieron, sobran)
    function contarGrupos() {
        var g = { ok: 0, menos: 0, sin: 0, mas: 0 };
        lista.querySelectorAll('.cn-var').forEach(function (f) { if (g[f.dataset.est] !== undefined) g[f.dataset.est]++; });
        document.getElementById('rOk').textContent = g.ok; document.getElementById('rMenos').textContent = g.menos;
        document.getElementById('rSin').textContent = g.sin; document.getElementById('rMas').textContent = g.mas;
        return g;
    }
    var res = document.getElementById('resultado');
    function mostrarResultado() {
        contarGrupos(); res.style.display = '';
        document.body.classList.remove('ciego');
        var g = contarGrupos(); elegir(g.menos + g.sin + g.mas ? (g.sin ? 'sin' : (g.menos ? 'menos' : 'mas')) : 'ok');
        window.scrollTo({ top: res.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
    }
    var bt = document.getElementById('btnTermine');
    if (bt) bt.addEventListener('click', mostrarResultado);
    document.querySelectorAll('.cn-rg .r').forEach(function (b) { b.addEventListener('click', function () { elegir(b.dataset.ir); }); });
    // Conteo cerrado: el resultado se ve de una vez
    if (!ABIERTO) { res.style.display = ''; contarGrupos(); }
    document.getElementById('buscar').addEventListener('input', aplicarFiltro);

    // Contar a ciegas (se recuerda en este dispositivo)
    var ciego = document.getElementById('ciego');
    try { ciego.checked = localStorage.getItem('lpConteoCiego') === '1'; } catch (e) {}
    function ponerCiego() { document.body.classList.toggle('ciego', ciego.checked); try { localStorage.setItem('lpConteoCiego', ciego.checked ? '1' : '0'); } catch (e) {} }
    ciego.addEventListener('change', ponerCiego); ponerCiego();

    // Ajustar / cancelar con la ventana de LocalPro
    var fA = document.getElementById('fAplicar');
    if (fA) fA.addEventListener('submit', function (e) {
        if (fA._ok) return; e.preventDefault();
        var cero = fA.querySelector('input[name=sin_contar]:checked').value === 'cero';
        LP.confirmar({ titulo: '¿Ajustar el inventario?', ok: 'Sí, ajustar',
            texto: 'Faltan ' + document.getElementById('nFal').textContent + ' y sobran ' + document.getElementById('nSob').textContent + ' prendas.' +
                (cero ? ' Lo que no se contó quedará en 0.' : ' Lo que no se contó queda como está.') + ' El conteo se cierra y no se puede deshacer.' })
            .then(function (ok) { if (ok) { fA._ok = true; fA.submit(); } });
    });
    var bc = document.getElementById('btnCancelar');
    if (bc) bc.addEventListener('click', function () {
        LP.confirmar({ titulo: '¿Cancelar este conteo?', texto: 'Se pierde lo contado y el inventario no cambia.', ok: 'Sí, cancelar', peligro: true })
            .then(function (ok) { if (ok) document.getElementById('fCancelar').submit(); });
    });
})();
</script>
@endpush
