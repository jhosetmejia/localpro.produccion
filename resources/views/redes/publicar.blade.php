@extends('layouts.app')

@section('title', 'Mis redes · Publicar')

@push('css')
<style>
.pb{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:1rem;align-items:start}
@media (max-width:960px){.pb{grid-template-columns:minmax(0,1fr)}}
.pb-tipo{display:flex;gap:.4rem;background:#f1f5f2;border-radius:999px;padding:.3rem;margin-bottom:1rem}
.pb-tipo label{flex:1;text-align:center;border-radius:999px;padding:.6rem .8rem;font-weight:800;cursor:pointer;color:#33403a}
.pb-tipo input{display:none}
.pb-tipo label:has(input:checked){background:#0b0d0c;color:#a6f34a}
.pb-donde{display:flex;gap:.6rem;flex-wrap:wrap}
.pb-donde label{flex:1 1 150px;display:flex;gap:.6rem;align-items:center;border:2px solid #e3ebe6;border-radius:16px;padding:.7rem .9rem;cursor:pointer;font-weight:800}
.pb-donde input{width:20px;height:20px;accent-color:#16b34a}
.pb-donde label:has(input:checked){border-color:#16b34a;background:#f4fcf6}
.pb-donde label.off{opacity:.5;cursor:not-allowed}
.pb-donde small{display:block;font-weight:600;color:#6b7570;font-size:.72rem}
.pb-foto{position:relative;border:2px dashed #bfeccb;border-radius:20px;background:#f7fdf8;min-height:150px;display:grid;place-items:center;text-align:center;cursor:pointer;overflow:hidden}
.pb-foto input{position:absolute;inset:0;opacity:0;cursor:pointer}
.pb-foto i{font-size:2rem;color:#16b34a}
.pb-foto img,.pb-foto video{width:100%;max-height:340px;object-fit:contain;display:block;background:#0b0d0c}
.pb-foto img{background:#fff}
.pb-acc{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem}
.pb-cuando{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center}
.pb-cuando label{display:flex;gap:.4rem;align-items:center;font-weight:800}
.pb-barra{height:10px;border-radius:999px;background:#e8efe9;overflow:hidden;margin-top:.6rem}
.pb-barra div{height:100%;width:0;background:linear-gradient(90deg,#16b34a,#a6f34a);transition:width .2s}
.pb-info{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem}
.pb-info span{background:#f1f5f2;border-radius:999px;padding:.2rem .7rem;font-size:.78rem;font-weight:800;color:#33403a}
.pb-info span.ok{background:#dcfce7;color:#14753a}.pb-info span.ojo{background:#fff5d6;color:#8a6400}
.pb-yt{border:1.5px solid #ffd4d4;background:#fff7f7;border-radius:18px;padding:.9rem 1rem;margin-top:1rem}
.pb-portada{display:flex;gap:.8rem;align-items:center;margin-top:.8rem}
.pb-portada canvas{width:96px;border-radius:12px;background:#0b0d0c;flex:none}
.rd-ico.youtube{background:#ff0000}
/* Vista previa */
.pv{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;overflow:hidden;position:sticky;top:80px}
.pv-h{display:flex;gap:.6rem;align-items:center;padding:.7rem .8rem}
.pv-h img,.pv-h .ini{width:34px;height:34px;border-radius:50%;object-fit:cover;background:#e8eee9;display:grid;place-items:center;font-weight:900}
.pv-img{aspect-ratio:1;background:#f1f5f2;display:grid;place-items:center;color:#9aa69e}
.pv-img.vert{aspect-ratio:9/16;max-height:70vh;margin:0 auto;background:#0b0d0c}
.pv-img img,.pv-img video{width:100%;height:100%;object-fit:cover}
.pv-ic{display:flex;gap:1rem;padding:.6rem .8rem;font-size:1.2rem}
.pv-t{padding:0 .8rem .9rem;white-space:pre-wrap;font-size:.9rem;word-break:break-word}
.pl li .est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.15rem .6rem;white-space:nowrap}
.est.publicada{background:#dcfce7;color:#14753a}.est.programada{background:#e7f0ff;color:#1d4fb8}.est.parcial{background:#fff5d6;color:#8a6400}.est.error{background:#ffe9f3;color:#b0173f}.est.pendiente,.est.procesando{background:#eef1ef;color:#6b7570}
.pl .mini{width:46px;height:46px;border-radius:12px;object-fit:cover;flex:none;background:#f1f5f2}
.pl .mini-v{position:relative;flex:none}.pl .mini-v::after{content:"\f04b";font-family:"Font Awesome 6 Free";font-weight:900;position:absolute;inset:0;display:grid;place-items:center;color:#fff;font-size:.8rem;text-shadow:0 1px 4px #000}
.pp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.6rem;max-height:52vh;overflow:auto;margin-top:.7rem}
.pp-grid button{border:1.5px solid #e3ebe6;background:#fff;border-radius:16px;padding:.4rem;text-align:left;cursor:pointer}
.pp-grid button:hover{border-color:#2fd15a}
.pp-grid img,.pp-grid .sin{width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;background:#f1f5f2;display:grid;place-items:center;color:#9aa69e}
.pp-grid b{display:block;font-size:.8rem;margin-top:.3rem;line-height:1.2}
.pp-grid span{font-size:.78rem;color:#11702f;font-weight:800}
</style>
@endpush

@section('content')
@php
    $c0 = $cuentas->first();
    $hayMeta = $cuentas->isNotEmpty();
    $hayYt = $canales->isNotEmpty();
    $tipoIni = old('tipo', $hayMeta ? 'foto' : 'video');
    $estados = ['publicada' => 'Publicada', 'programada' => 'Programada', 'parcial' => 'Solo en algunas redes', 'error' => 'No se publicó', 'pendiente' => 'En cola…', 'procesando' => 'Subiendo…'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hashtag me-1"></i>Mis redes</small><h1>Publicar en <span>sus redes</span></h1>
            <span class="text-muted fw-semibold">Una foto o un video: sale en Facebook, Instagram y YouTube al tiempo, ahora o a la hora que programe.</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @include('redes._tabs')

    <div class="pb">
        <form method="post" action="{{ route('redes.publicar.guardar') }}" enctype="multipart/form-data" class="lp-box" id="fPub">
            @csrf
            <input type="hidden" name="producto_id" id="prodId" value="{{ old('producto_id') }}">
            <input type="hidden" name="subida" id="subida">
            <input type="hidden" name="duracion" id="vDur">
            <input type="hidden" name="ancho" id="vAncho">
            <input type="hidden" name="alto" id="vAlto">
            <input type="hidden" name="portada_data" id="portadaData">

            <div class="pb-tipo">
                <label><input type="radio" name="tipo" value="foto" @checked($tipoIni === 'foto') @disabled(!$hayMeta)><i class="fa-regular fa-image me-1"></i>Foto</label>
                <label><input type="radio" name="tipo" value="video" @checked($tipoIni === 'video')><i class="fa-solid fa-clapperboard me-1"></i>Video · Reel</label>
            </div>

            @if ($hayMeta)
                @if ($cuentas->count() > 1)
                <div class="mb-3"><label class="form-label">Página</label>
                    <select name="cuenta_id" id="cuenta" class="form-select" data-nativo>@foreach ($cuentas as $c)<option value="{{ $c->id }}" data-ig="{{ $c->ig_usuario }}" data-nom="{{ $c->page_nombre }}" data-foto="{{ $c->foto_url }}">{{ $c->page_nombre }}{{ $c->ig_usuario ? ' · @' . $c->ig_usuario : '' }}</option>@endforeach</select></div>
                @else
                <input type="hidden" name="cuenta_id" id="cuenta" value="{{ $c0->id }}" data-ig="{{ $c0->ig_usuario }}" data-nom="{{ $c0->page_nombre }}" data-foto="{{ $c0->foto_url }}">
                @endif
            @endif

            <label class="form-label">¿Dónde publica?</label>
            <div class="pb-donde mb-3">
                @if ($hayMeta)
                <label><input type="checkbox" name="en_facebook" value="1" id="enFb" checked> <span class="rd-ico facebook"><i class="fa-brands fa-facebook-f"></i></span> <span>Facebook<small data-solo-video>Reel o video</small></span></label>
                <label id="lblIg"><input type="checkbox" name="en_instagram" value="1" id="enIg" checked> <span class="rd-ico instagram"><i class="fa-brands fa-instagram"></i></span> <span>Instagram<small data-solo-video>Reel</small></span></label>
                @endif
                @if ($hayYt)
                <label id="lblYt"><input type="checkbox" name="en_youtube" value="1" id="enYt" @checked(old('en_youtube', true))> <span class="rd-ico youtube"><i class="fa-brands fa-youtube"></i></span> <span>YouTube<small id="ytTipo">Short o video normal</small></span></label>
                @endif
            </div>
            @if ($hayYt && $canales->count() > 1)
            <div class="mb-3" data-solo-video><label class="form-label">Canal de YouTube</label>
                <select name="yt_cuenta_id" class="form-select" data-nativo>@foreach ($canales as $ch)<option value="{{ $ch->id }}">{{ $ch->page_nombre }}</option>@endforeach</select></div>
            @elseif ($hayYt)
            <input type="hidden" name="yt_cuenta_id" value="{{ $canales->first()->id }}">
            @endif
            @if (!$hayYt)
                @can('editar-negocio')
                <p class="small text-muted mb-3" data-solo-video><i class="fa-brands fa-youtube me-1" style="color:#ff0000"></i>¿También en YouTube? <a href="{{ route('redes.cuentas') }}#youtube">Conecte su canal</a> y sus videos salen allá como Short o video normal.</p>
                @endcan
            @endif
            @if (!$hayMeta)
            <p class="small text-muted mb-3"><i class="fa-brands fa-facebook me-1" style="color:#1877f2"></i>Para publicar también en Facebook e Instagram, <a href="{{ route('redes.cuentas') }}">conecte su página</a>.</p>
            @endif

            {{-- Foto --}}
            <div data-solo-foto>
                <label class="form-label">Foto</label>
                <label class="pb-foto" id="foto">
                    <input type="file" name="imagen" id="img" accept="image/jpeg,image/png,image/webp" data-max-kb="7500">
                    <div id="fotoVacia"><i class="fa-regular fa-image"></i><div class="fw-bold mt-1">Toque para subir una foto</div><small class="text-muted">JPG o PNG · Instagram exige foto</small></div>
                    <img id="fotoPrev" alt="" style="display:none">
                </label>
                <div class="pb-acc">
                    <button type="button" class="btn btn-dark btn-sm" id="btnProd"><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }} me-1"></i>Publicar un producto</button>
                    <button type="button" class="btn btn-light btn-sm" id="btnQuitar" style="display:none"><i class="fa-solid fa-xmark me-1"></i>Quitar foto</button>
                </div>
            </div>

            {{-- Video --}}
            <div data-solo-video>
                <label class="form-label">Video</label>
                <label class="pb-foto" id="vZona">
                    <input type="file" id="vArchivo" accept="video/mp4,video/quicktime,.mp4,.mov" data-sin-comprimir data-libre>
                    <div id="vVacio"><i class="fa-solid fa-film"></i><div class="fw-bold mt-1">Toque para subir su video</div><small class="text-muted">MP4 o MOV · máximo {{ $videoMaxMb }} MB · vertical para Reels y Shorts</small></div>
                    <video id="vPrev" muted playsinline controls style="display:none"></video>
                </label>
                <a href="{{ route('cut') }}" class="d-inline-flex align-items-center gap-1 mt-2 fw-bold small" style="color:#16a34a"><i class="fa-solid fa-scissors"></i> ¿Quiere editar el video antes? Ábralo en LocalProCut (gratis)</a>
                <div class="pb-barra" id="vBarra" style="display:none"><div></div></div>
                <div class="small fw-bold mt-1" id="vEstado"></div>
                <div class="pb-info" id="vInfo"></div>
                <div class="pb-portada" id="vPortada" style="display:none">
                    <canvas id="vCanvas"></canvas>
                    <div class="flex-grow-1"><label class="form-label small fw-bold mb-1">Portada: mueva para elegir el cuadro</label>
                        <input type="range" class="form-range" id="vCuadro" min="0" max="100" value="10"></div>
                </div>
            </div>

            @if ($hayYt)
            <div class="pb-yt" id="ytOpc" data-solo-video>
                <b><i class="fa-brands fa-youtube me-1" style="color:#ff0000"></i>Para YouTube</b>
                <div class="mt-2"><label class="form-label small fw-bold mb-1 d-flex justify-content-between">Título <small class="text-muted" id="ytCnt">0 / 100</small></label>
                    <input name="titulo" id="ytTitulo" class="form-control" maxlength="100" value="{{ old('titulo') }}" placeholder="Ej. Así quedan las chaquetas de cuero 🔥"></div>
                <div class="mt-2"><label class="form-label small fw-bold mb-1">Quién lo puede ver</label>
                    <select name="yt_privacidad" class="form-select" data-nativo>@foreach (\App\Models\RedPublicacion::PRIVACIDAD as $k => $t)<option value="{{ $k }}" @selected(old('yt_privacidad', 'public') === $k)>{{ $t }}</option>@endforeach</select></div>
                <small class="text-muted d-block mt-2">El texto de abajo va como descripción. Si el video es vertical y dura máximo 3 minutos, YouTube lo muestra como <b>Short</b>; si no, como <b>video normal</b>.</small>
            </div>
            @endif

            <div class="mt-3"><label class="form-label d-flex justify-content-between">Texto <small class="text-muted" id="cnt">0 / 2.200</small></label>
                <textarea name="texto" id="txt" class="form-control" rows="6" maxlength="2200" placeholder="Ej. ¡Llegaron las chaquetas de cuero! 🔥 Tallas S a XL. Escríbanos por interno 📩">{{ old('texto') }}</textarea>
                <div class="pb-acc">
                    @foreach (['🔥 ¡Nuevo!', '💥 Promo', '📦 Envíos a todo el país', '📩 Escríbanos por interno', '#moda #ropa'] as $t)<button type="button" class="btn btn-light btn-sm" data-add="{{ $t }}">{{ $t }}</button>@endforeach
                </div>
            </div>

            <div class="mt-3 pb-cuando">
                <label><input type="radio" name="cuando" value="ahora" checked> Publicar ahora</label>
                <label><input type="radio" name="cuando" value="programar"> Programar</label>
                <input type="datetime-local" name="programada_para" id="prog" class="form-control" style="max-width:240px;display:none" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" value="{{ now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i') }}">
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-3" id="btnPub"><i class="fa-regular fa-paper-plane me-1"></i><span>Publicar ahora</span></button>
        </form>

        <div>
            <div class="pv mb-3">
                <div class="pv-h"><span id="pvAv"></span><b id="pvNom"></b></div>
                <div class="pv-img" id="pvImg"><i class="fa-regular fa-image" style="font-size:2.4rem"></i></div>
                <div class="pv-ic"><i class="fa-regular fa-heart"></i><i class="fa-regular fa-comment"></i><i class="fa-regular fa-paper-plane"></i></div>
                <div class="pv-t" id="pvTxt"><span class="text-muted">Así se verá su publicación…</span></div>
            </div>
        </div>
    </div>

    <div class="lp-box mt-3 pl">
        <h3><span><i class="fa-solid fa-clock-rotate-left me-1"></i> Publicaciones</span></h3>
        <ul class="lp-list">
            @forelse ($lista as $p)
            <li data-pub="{{ $p->id }}" data-est="{{ $p->estado }}">
                @if ($p->es_video)
                    <span class="mini-v">@if ($p->portada_url)<img class="mini" src="{{ $p->portada_url }}" alt="">@else<span class="mini d-grid" style="place-items:center;background:#0b0d0c"></span>@endif</span>
                @elseif ($p->imagen_url)<img class="mini" src="{{ $p->imagen_url }}" alt="">@else<div class="av"><i class="fa-solid fa-align-left"></i></div>@endif
                <div class="t"><b>{{ \Illuminate\Support\Str::limit($p->titulo ?: ($p->texto ?: ($p->es_video ? 'Video' : 'Solo foto')), 70) }}</b>
                    <small>{{ $p->es_video ? '🎬 ' : '' }}{{ $p->redes_texto }}{{ $p->en_youtube && $p->duracion ? ($p->es_short ? ' (Short)' : ' (video normal)') : '' }} ·
                        {{ $p->estado === 'programada' ? 'Se publica el ' . $p->programada_para?->locale('es')->isoFormat('D MMM h:mm a') : ($p->publicada_en ?? $p->created_at)->format('d/m/Y h:i a') }}{{ $p->user ? ' · ' . $p->user->name : '' }}</small>
                    <small class="d-block" data-err style="color:#b0173f">{{ $p->error }}</small>
                </div>
                <span class="est {{ $p->estado }}" data-est-txt>{{ $estados[$p->estado] ?? $p->estado }}</span>
                <span class="d-flex gap-1" data-links>
                    @if ($p->fb_url)<a class="btn btn-sm btn-light" target="_blank" href="{{ $p->fb_url }}" title="Ver en Facebook"><i class="fa-brands fa-facebook-f"></i></a>@endif
                    @if ($p->ig_url)<a class="btn btn-sm btn-light" target="_blank" href="{{ $p->ig_url }}" title="Ver en Instagram"><i class="fa-brands fa-instagram"></i></a>@endif
                    @if ($p->yt_url)<a class="btn btn-sm btn-light" target="_blank" href="{{ $p->yt_url }}" title="Ver en YouTube"><i class="fa-brands fa-youtube" style="color:#ff0000"></i></a>@endif
                </span>
                @if (in_array($p->estado, ['error', 'parcial'], true))<form method="post" action="{{ route('redes.reintentar', $p) }}">@csrf<button class="btn btn-sm btn-dark">Reintentar</button></form>@endif
                @if (in_array($p->estado, ['programada', 'error'], true))<form method="post" action="{{ route('redes.publicacion.borrar', $p) }}" data-confirm="¿Eliminar esta publicación?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>@endif
            </li>
            @empty
            <li class="lp-empty">Aún no ha publicado desde LocalPro. ¡Haga la primera!</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="lpx-ov" id="ppOv" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <div class="lpx-md" style="width:min(640px,100%)">
        <div class="d-flex justify-content-between align-items-center"><h4 class="m-0">Publicar un producto</h4><button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('ppOv').style.display='none'">✕</button></div>
        <input id="ppQ" class="form-control mt-2" placeholder="Buscar producto…" autocomplete="off">
        <div class="pp-grid" id="ppGrid"></div>
    </div>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var CFG = {!! json_encode([
        'prod' => route('redes.productos'),
        'subir' => route('redes.video.subir'),
        'estado' => route('redes.publicaciones.estado'),
        'tienda' => $tienda?->catalogo_activo && $tienda?->slug ? $tienda->url_catalogo : null,
        'maxMb' => $videoMaxMb,
        'parteMb' => $parteMb,
        'canal' => $canales->first()?->page_nombre,
        'canalFoto' => $canales->first()?->page_foto,
        'estados' => $estados,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var imgProducto = null, video = { listo: false, subiendo: false, url: null };

    function tipo() { var r = document.querySelector('input[name=tipo]:checked'); return r ? r.value : 'video'; }
    function marcado(id) { return $(id) ? $(id).checked && !$(id).disabled : false; }

    // ---------- Cuenta y vista previa ----------
    function cuenta() {
        var el = $('cuenta');
        if (!el) return { dataset: { nom: CFG.canal || '', foto: CFG.canalFoto || '' } };
        return el.tagName === 'SELECT' ? el.selectedOptions[0] : el;
    }
    function pintarCuenta() {
        var c = cuenta(), ig = c.dataset.ig;
        $('pvNom').textContent = ig ? ig : c.dataset.nom;
        $('pvAv').innerHTML = c.dataset.foto ? '<img src="' + esc(c.dataset.foto) + '" alt="">' : '<span class="ini">' + esc((c.dataset.nom || '?').charAt(0)) + '</span>';
        if ($('lblIg')) {
            $('lblIg').classList.toggle('off', !ig); $('enIg').disabled = !ig; if (!ig) $('enIg').checked = false;
            $('lblIg').title = ig ? '' : 'Esta página no tiene Instagram profesional vinculado';
        }
    }
    if ($('cuenta') && $('cuenta').tagName === 'SELECT') $('cuenta').addEventListener('change', pintarCuenta);
    pintarCuenta();

    function pintarTipo() {
        var v = tipo() === 'video';
        document.querySelectorAll('[data-solo-video]').forEach(function (e) { e.style.display = v ? '' : 'none'; });
        document.querySelectorAll('[data-solo-foto]').forEach(function (e) { e.style.display = v ? 'none' : ''; });
        if ($('lblYt')) { $('lblYt').classList.toggle('off', !v); $('enYt').disabled = !v; $('lblYt').title = v ? '' : 'YouTube es solo para videos'; }
        pintarYt();
        if (v) mostrarVideoPrevia(); else mostrarFoto($('fotoPrev').getAttribute('src') || imgProducto);
    }
    document.querySelectorAll('input[name=tipo]').forEach(function (r) { r.addEventListener('change', pintarTipo); });

    function pintarYt() { if ($('ytOpc')) $('ytOpc').style.display = tipo() === 'video' && marcado('enYt') ? '' : 'none'; }
    if ($('enYt')) $('enYt').addEventListener('change', pintarYt);
    if ($('ytTitulo')) { var tc = function () { $('ytCnt').textContent = $('ytTitulo').value.length + ' / 100'; }; $('ytTitulo').addEventListener('input', tc); tc(); }

    // ---------- Foto ----------
    function mostrarFoto(src) {
        $('fotoPrev').src = src || ''; $('fotoPrev').style.display = src ? '' : 'none'; $('fotoVacia').style.display = src ? 'none' : '';
        if (!src) $('fotoPrev').removeAttribute('src');
        $('pvImg').className = 'pv-img';
        $('pvImg').innerHTML = src ? '<img src="' + esc(src) + '" alt="">' : '<i class="fa-regular fa-image" style="font-size:2.4rem"></i>';
        $('btnQuitar').style.display = src ? '' : 'none';
    }
    $('img').addEventListener('change', function () {
        var f = this.files[0]; if (!f) return;
        $('prodId').value = ''; imgProducto = null;
        var r = new FileReader(); r.onload = function (e) { mostrarFoto(e.target.result); }; r.readAsDataURL(f);
    });
    $('btnQuitar').addEventListener('click', function () { $('img').value = ''; $('prodId').value = ''; imgProducto = null; mostrarFoto(null); });

    // ---------- Video: se sube por partes apenas lo elige ----------
    function mostrarVideoPrevia() {
        var vert = (+$('vAlto').value || 0) > (+$('vAncho').value || 0);
        $('pvImg').className = 'pv-img' + (video.url && vert ? ' vert' : '');
        $('pvImg').innerHTML = video.url ? '<video src="' + esc(video.url) + '" muted loop autoplay playsinline></video>' : '<i class="fa-solid fa-film" style="font-size:2.4rem"></i>';
    }
    function chip(txt, cls) { return '<span class="' + (cls || '') + '">' + esc(txt) + '</span>'; }
    function seg(n) { n = Math.round(n); return n >= 60 ? Math.floor(n / 60) + ' min ' + (n % 60 ? (n % 60) + ' s' : '') : n + ' s'; }
    function pintarInfo() {
        var d = +$('vDur').value, w = +$('vAncho').value, h = +$('vAlto').value, out = [];
        if (!d) { $('vInfo').innerHTML = video.url ? chip('No se pudo leer el video en este navegador; igual se puede publicar', 'ojo') : ''; return; }
        var vert = h > w, short = d <= 180 && h >= w;
        out.push(chip(seg(d))); out.push(chip(vert ? 'Vertical' : (h === w ? 'Cuadrado' : 'Horizontal')));
        if ($('enFb')) out.push(chip('Facebook: ' + (vert && d >= 3 && d <= 90 ? 'Reel' : 'video de la página'), vert && d <= 90 ? 'ok' : ''));
        if ($('enIg')) out.push(chip('Instagram: Reel', vert ? 'ok' : 'ojo'));
        if ($('enYt')) { out.push(chip('YouTube: ' + (short ? 'Short' : 'video normal'), 'ok')); $('ytTipo').textContent = short ? 'Saldrá como Short' : 'Saldrá como video normal'; }
        if (!vert && $('enIg')) out.push(chip('Para Reels lo ideal es vertical 9:16', 'ojo'));
        $('vInfo').innerHTML = out.join('');
    }
    function portada() {
        var v = $('vPrev'), cv = $('vCanvas');
        if (!v.videoWidth) return;
        var esc2 = Math.min(1, 720 / Math.max(v.videoWidth, v.videoHeight));
        cv.width = Math.round(v.videoWidth * esc2); cv.height = Math.round(v.videoHeight * esc2);
        try { cv.getContext('2d').drawImage(v, 0, 0, cv.width, cv.height); $('portadaData').value = cv.toDataURL('image/jpeg', 0.85); } catch (e) { $('portadaData').value = ''; }
    }
    $('vCuadro').addEventListener('input', function () { var v = $('vPrev'); if (v.duration) v.currentTime = v.duration * this.value / 100; });
    $('vPrev').addEventListener('seeked', portada);
    $('vPrev').addEventListener('loadedmetadata', function () {
        var v = this;
        $('vDur').value = v.duration && isFinite(v.duration) ? v.duration.toFixed(2) : '';
        $('vAncho').value = v.videoWidth || ''; $('vAlto').value = v.videoHeight || '';
        $('vPortada').style.display = v.videoWidth ? '' : 'none';
        if (v.duration) v.currentTime = Math.min(1, v.duration / 10);
        pintarInfo(); mostrarVideoPrevia();
    });
    $('vPrev').addEventListener('error', function () { pintarInfo(); });

    function token() { var a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', s = ''; for (var i = 0; i < 32; i++) s += a.charAt(Math.floor(Math.random() * a.length)); return s; }
    function estado(t, color) { $('vEstado').textContent = t; $('vEstado').style.color = color || ''; }
    function barra(p) { $('vBarra').style.display = ''; $('vBarra').firstElementChild.style.width = Math.round(p * 100) + '%'; }

    async function subir(f) {
        var tam = CFG.parteMb * 1024 * 1024, partes = Math.max(1, Math.ceil(f.size / tam)), id = token(), i = 0;
        video = { listo: false, subiendo: true, url: video.url };
        $('subida').value = '';
        while (i < partes) {
            var fd = new FormData();
            fd.append('subida', id); fd.append('parte', i); fd.append('partes', partes); fd.append('total', f.size);
            fd.append('archivo', f.slice(i * tam, Math.min(f.size, (i + 1) * tam)), 'parte.bin');
            var r = null, j = null;
            for (var intento = 0; intento < 4; intento++) {
                try {
                    r = await fetch(CFG.subir, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
                    j = await r.json().catch(function () { return {}; });
                    if (r.ok || r.status === 409 || r.status === 422) break;
                } catch (e) { r = null; }
                estado('Se cortó el internet, reintentando…', '#8a6400');
                await new Promise(function (ok) { setTimeout(ok, 2000 * (intento + 1)); });
            }
            if (r && r.status === 409 && j && typeof j.desde === 'number') { i = j.desde; continue; }
            if (!r || !r.ok) {
                video.subiendo = false;
                var m = (j && (j.msg || (j.errors && Object.values(j.errors)[0][0]))) || 'No se pudo subir el video. Revise su internet e intente de nuevo.';
                estado(m, '#b0173f'); LP.toast(m, 'error'); return;
            }
            i++;
            barra(i / partes);
            estado('Subiendo video… ' + Math.round(i / partes * 100) + ' %');
        }
        video.listo = true; video.subiendo = false;
        $('subida').value = id;
        estado('✓ Video listo para publicar', '#11702f');
    }

    $('vArchivo').addEventListener('change', function () {
        var f = this.files[0]; if (!f) return;
        if (f.size > CFG.maxMb * 1024 * 1024) { this.value = ''; LP.toast('El video pesa más de ' + CFG.maxMb + ' MB. Expórtelo más liviano (1080p) e intente de nuevo.', 'error'); return; }
        if (!/\.(mp4|mov)$/i.test(f.name) && !/^video\/(mp4|quicktime)/.test(f.type)) { this.value = ''; LP.toast('Use un video MP4 o MOV.', 'error'); return; }
        if (video.url) URL.revokeObjectURL(video.url);
        video.url = URL.createObjectURL(f);
        $('vDur').value = ''; $('vAncho').value = ''; $('vAlto').value = ''; $('portadaData').value = '';
        $('vPrev').src = video.url; $('vPrev').style.display = ''; $('vVacio').style.display = 'none';
        barra(0); estado('Subiendo video… 0 %');
        if (!$('ytTitulo') || !$('ytTitulo').value) { if ($('ytTitulo')) { $('ytTitulo').value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').slice(0, 100); $('ytTitulo').dispatchEvent(new Event('input')); } }
        mostrarVideoPrevia();
        subir(f);
    });

    // ---------- Texto ----------
    function pintarTexto() {
        var t = $('txt').value; $('cnt').textContent = t.length.toLocaleString('es-CO') + ' / 2.200';
        var c = cuenta();
        $('pvTxt').innerHTML = t ? '<b>' + esc(c.dataset.ig || c.dataset.nom) + '</b> ' + esc(t) : '<span class="text-muted">Así se verá su publicación…</span>';
    }
    $('txt').addEventListener('input', pintarTexto); pintarTexto();
    document.querySelectorAll('[data-add]').forEach(function (b) { b.addEventListener('click', function () { var t = $('txt'); t.value = (t.value ? t.value.replace(/\s*$/, '') + ' ' : '') + b.dataset.add; pintarTexto(); t.focus(); }); });

    function textoBoton() {
        var prog = document.querySelector('input[name=cuando]:checked').value === 'programar';
        $('prog').style.display = prog ? '' : 'none'; $('btnPub').querySelector('span').textContent = prog ? 'Programar publicación' : 'Publicar ahora';
    }
    document.querySelectorAll('input[name=cuando]').forEach(function (r) { r.addEventListener('change', textoBoton); });

    // ---------- Productos ----------
    var tp;
    function buscarProd() {
        fetch(CFG.prod + '?q=' + encodeURIComponent($('ppQ').value.trim()), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
            $('ppGrid').innerHTML = l.length ? l.map(function (p, i) { return '<button type="button" data-i="' + i + '">' + (p.img ? '<img src="' + esc(p.img) + '" alt="">' : '<span class="sin"><i class="fa-solid fa-image"></i></span>') + '<b>' + esc(p.nombre) + '</b><span>' + esc(p.precio) + '</span></button>'; }).join('') : '<p class="text-muted">No encontré productos.</p>';
            $('ppGrid')._l = l;
        });
    }
    $('btnProd').addEventListener('click', function () { $('ppOv').style.display = 'flex'; $('ppOv').classList.add('on'); $('ppQ').value = ''; buscarProd(); setTimeout(function () { $('ppQ').focus(); }, 50); });
    $('ppQ').addEventListener('input', function () { clearTimeout(tp); tp = setTimeout(buscarProd, 250); });
    $('ppGrid').addEventListener('click', function (e) {
        var b = e.target.closest('button[data-i]'); if (!b) return;
        var p = $('ppGrid')._l[+b.dataset.i];
        $('ppOv').style.display = 'none';
        $('prodId').value = p.id; $('img').value = ''; imgProducto = p.img;
        mostrarFoto(p.img);
        $('txt').value = '✨ ' + p.nombre + '\n💲 ' + p.precio + '\n\n📩 Escríbanos por interno para separar el suyo.' + (p.url ? '\n🛍️ Cómprelo aquí: ' + p.url : (CFG.tienda ? '\n🛍️ Vea más en: ' + CFG.tienda : ''));
        pintarTexto();
        if (!p.img) LP.toast('Este producto no tiene foto: súbala para poder publicar en Instagram', 'info');
    });

    // ---------- Enviar ----------
    $('fPub').addEventListener('submit', function (e) {
        var fb = marcado('enFb'), ig = marcado('enIg'), yt = marcado('enYt');
        var no = function (m) { e.preventDefault(); LP.toast(m, 'error'); };
        if (tipo() === 'video') {
            if (!fb && !ig && !yt) return no('Elija dónde publicar el video');
            if (video.subiendo) return no('Espere a que el video termine de subir');
            if (!video.listo) return no('Suba el video primero');
            if (yt && $('ytTitulo') && !$('ytTitulo').value.trim() && !$('txt').value.trim()) return no('Escriba el título para YouTube');
        } else {
            if (!fb && !ig) return no('Elija Facebook, Instagram o ambos');
            var hayFoto = $('img').files.length || imgProducto;
            if (ig && !hayFoto) return no('Instagram necesita una foto');
            if (!hayFoto && !$('txt').value.trim()) return no('Escriba un texto o suba una foto');
        }
        // La foto no viaja si es un video (y al revés)
        if (tipo() === 'video') { $('img').disabled = true; } else { $('portadaData').value = ''; }
        $('btnPub').disabled = true; $('btnPub').querySelector('span').textContent = 'Enviando…';
    });

    pintarTipo();

    // ---------- La lista se actualiza sola mientras hay videos subiendo ----------
    function revisarLista() {
        var vivos = Array.prototype.filter.call(document.querySelectorAll('[data-pub]'), function (li) { return ['pendiente', 'procesando'].indexOf(li.dataset.est) >= 0; });
        if (!vivos.length) return;
        fetch(CFG.estado + '?ids=' + vivos.map(function (li) { return li.dataset.pub; }).join(','), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (d) {
                var cambio = false;
                vivos.forEach(function (li) {
                    var p = d[li.dataset.pub]; if (!p || p.estado === li.dataset.est) return;
                    li.dataset.est = p.estado;
                    var s = li.querySelector('[data-est-txt]'); s.className = 'est ' + p.estado; s.textContent = CFG.estados[p.estado] || p.estado;
                    li.querySelector('[data-err]').textContent = p.error || '';
                    var l = '';
                    if (p.fb_url) l += '<a class="btn btn-sm btn-light" target="_blank" href="' + esc(p.fb_url) + '"><i class="fa-brands fa-facebook-f"></i></a>';
                    if (p.ig_url) l += '<a class="btn btn-sm btn-light" target="_blank" href="' + esc(p.ig_url) + '"><i class="fa-brands fa-instagram"></i></a>';
                    if (p.yt_url) l += '<a class="btn btn-sm btn-light" target="_blank" href="' + esc(p.yt_url) + '"><i class="fa-brands fa-youtube" style="color:#ff0000"></i></a>';
                    li.querySelector('[data-links]').innerHTML = l;
                    if (['publicada', 'parcial', 'error'].indexOf(p.estado) >= 0) cambio = true;
                });
                if (cambio) LP.toast('Se actualizó el estado de sus publicaciones', 'ok');
            }).catch(function () {}).finally(function () { setTimeout(revisarLista, 15000); });
    }
    setTimeout(revisarLista, 10000);
})();
</script>
@endsection
