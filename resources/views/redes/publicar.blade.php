@extends('layouts.app')

@section('title', 'Mis redes · Publicar')

@push('css')
<style>
.pb{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:1rem;align-items:start}
@media (max-width:960px){.pb{grid-template-columns:minmax(0,1fr)}}
.pb-donde{display:flex;gap:.6rem;flex-wrap:wrap}
.pb-donde label{flex:1 1 160px;display:flex;gap:.6rem;align-items:center;border:2px solid #e3ebe6;border-radius:16px;padding:.7rem .9rem;cursor:pointer;font-weight:800}
.pb-donde input{width:20px;height:20px;accent-color:#16b34a}
.pb-donde label:has(input:checked){border-color:#16b34a;background:#f4fcf6}
.pb-donde label.off{opacity:.5;cursor:not-allowed}
.pb-foto{position:relative;border:2px dashed #bfeccb;border-radius:20px;background:#f7fdf8;min-height:150px;display:grid;place-items:center;text-align:center;cursor:pointer;overflow:hidden}
.pb-foto input{position:absolute;inset:0;opacity:0;cursor:pointer}
.pb-foto i{font-size:2rem;color:#16b34a}
.pb-foto img{width:100%;max-height:340px;object-fit:contain;display:block;background:#fff}
.pb-acc{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.6rem}
.pb-cuando{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center}
.pb-cuando label{display:flex;gap:.4rem;align-items:center;font-weight:800}
/* Vista previa tipo Instagram */
.pv{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;overflow:hidden;position:sticky;top:80px}
.pv-h{display:flex;gap:.6rem;align-items:center;padding:.7rem .8rem}
.pv-h img,.pv-h .ini{width:34px;height:34px;border-radius:50%;object-fit:cover;background:#e8eee9;display:grid;place-items:center;font-weight:900}
.pv-img{aspect-ratio:1;background:#f1f5f2;display:grid;place-items:center;color:#9aa69e}
.pv-img img{width:100%;height:100%;object-fit:cover}
.pv-ic{display:flex;gap:1rem;padding:.6rem .8rem;font-size:1.2rem}
.pv-t{padding:0 .8rem .9rem;white-space:pre-wrap;font-size:.9rem;word-break:break-word}
.pl li .est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.15rem .6rem;white-space:nowrap}
.est.publicada{background:#dcfce7;color:#14753a}.est.programada{background:#e7f0ff;color:#1d4fb8}.est.parcial{background:#fff5d6;color:#8a6400}.est.error{background:#ffe9f3;color:#b0173f}.est.pendiente{background:#eef1ef;color:#6b7570}
.pl .mini{width:46px;height:46px;border-radius:12px;object-fit:cover;flex:none;background:#f1f5f2}
.pp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.6rem;max-height:52vh;overflow:auto;margin-top:.7rem}
.pp-grid button{border:1.5px solid #e3ebe6;background:#fff;border-radius:16px;padding:.4rem;text-align:left;cursor:pointer}
.pp-grid button:hover{border-color:#2fd15a}
.pp-grid img,.pp-grid .sin{width:100%;aspect-ratio:1;object-fit:cover;border-radius:12px;background:#f1f5f2;display:grid;place-items:center;color:#9aa69e}
.pp-grid b{display:block;font-size:.8rem;margin-top:.3rem;line-height:1.2}
.pp-grid span{font-size:.78rem;color:#11702f;font-weight:800}
</style>
@endpush

@section('content')
@php $c0 = $cuentas->first(); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hashtag me-1"></i>Mis redes</small><h1>Publicar en <span>sus redes</span></h1>
            <span class="text-muted fw-semibold">Una foto y un texto: sale en Facebook e Instagram al tiempo.</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @include('redes._tabs')

    <div class="pb">
        <form method="post" action="{{ route('redes.publicar.guardar') }}" enctype="multipart/form-data" class="lp-box" id="fPub">
            @csrf
            <input type="hidden" name="producto_id" id="prodId" value="{{ old('producto_id') }}">
            @if ($cuentas->count() > 1)
            <div class="mb-3"><label class="form-label">Página</label>
                <select name="cuenta_id" id="cuenta" class="form-select" data-nativo>@foreach ($cuentas as $c)<option value="{{ $c->id }}" data-ig="{{ $c->ig_usuario }}" data-nom="{{ $c->page_nombre }}" data-foto="{{ $c->ig_foto ?: $c->page_foto }}">{{ $c->page_nombre }}{{ $c->ig_usuario ? ' · @' . $c->ig_usuario : '' }}</option>@endforeach</select></div>
            @else
            <input type="hidden" name="cuenta_id" id="cuenta" value="{{ $c0->id }}" data-ig="{{ $c0->ig_usuario }}" data-nom="{{ $c0->page_nombre }}" data-foto="{{ $c0->ig_foto ?: $c0->page_foto }}">
            @endif

            <label class="form-label">¿Dónde publica?</label>
            <div class="pb-donde mb-3">
                <label><input type="checkbox" name="en_facebook" value="1" id="enFb" checked> <span class="rd-ico facebook"><i class="fa-brands fa-facebook-f"></i></span> Facebook</label>
                <label id="lblIg"><input type="checkbox" name="en_instagram" value="1" id="enIg" checked> <span class="rd-ico instagram"><i class="fa-brands fa-instagram"></i></span> Instagram</label>
            </div>

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
            <li>
                @if ($p->imagen_url)<img class="mini" src="{{ $p->imagen_url }}" alt="">@else<div class="av"><i class="fa-solid fa-align-left"></i></div>@endif
                <div class="t"><b>{{ \Illuminate\Support\Str::limit($p->texto ?: 'Solo foto', 70) }}</b>
                    <small>{{ $p->en_facebook ? 'Facebook' : '' }}{{ $p->en_facebook && $p->en_instagram ? ' + ' : '' }}{{ $p->en_instagram ? 'Instagram' : '' }} ·
                        {{ $p->estado === 'programada' ? 'Se publica el ' . $p->programada_para?->locale('es')->isoFormat('D MMM h:mm a') : ($p->publicada_en ?? $p->created_at)->format('d/m/Y h:i a') }}{{ $p->user ? ' · ' . $p->user->name : '' }}</small>
                    @if ($p->error)<small class="d-block" style="color:#b0173f">{{ $p->error }}</small>@endif
                </div>
                <span class="est {{ $p->estado }}">{{ ['publicada' => 'Publicada', 'programada' => 'Programada', 'parcial' => 'Solo en una red', 'error' => 'No se publicó', 'pendiente' => 'Publicando…'][$p->estado] ?? $p->estado }}</span>
                @if ($p->fb_url)<a class="btn btn-sm btn-light" target="_blank" href="{{ $p->fb_url }}" title="Ver en Facebook"><i class="fa-brands fa-facebook-f"></i></a>@endif
                @if ($p->ig_url)<a class="btn btn-sm btn-light" target="_blank" href="{{ $p->ig_url }}" title="Ver en Instagram"><i class="fa-brands fa-instagram"></i></a>@endif
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
    var R_PROD = @json(route('redes.productos')), TIENDA_URL = @json($tienda?->catalogo_activo && $tienda?->slug ? $tienda->url_catalogo : null);
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var imgProducto = null;

    function cuenta() { var el = $('cuenta'); return el.tagName === 'SELECT' ? el.selectedOptions[0] : el; }
    function pintarCuenta() {
        var c = cuenta(), ig = c.dataset.ig;
        $('pvNom').textContent = ig ? ig : c.dataset.nom;
        $('pvAv').innerHTML = c.dataset.foto ? '<img src="' + esc(c.dataset.foto) + '" alt="">' : '<span class="ini">' + esc((c.dataset.nom || '?').charAt(0)) + '</span>';
        $('lblIg').classList.toggle('off', !ig); $('enIg').disabled = !ig; if (!ig) $('enIg').checked = false;
        $('lblIg').title = ig ? '' : 'Esta página no tiene Instagram profesional vinculado';
    }
    if ($('cuenta').tagName === 'SELECT') $('cuenta').addEventListener('change', pintarCuenta);
    pintarCuenta();

    function mostrarFoto(src) {
        $('fotoPrev').src = src || ''; $('fotoPrev').style.display = src ? '' : 'none'; $('fotoVacia').style.display = src ? 'none' : '';
        $('pvImg').innerHTML = src ? '<img src="' + esc(src) + '" alt="">' : '<i class="fa-regular fa-image" style="font-size:2.4rem"></i>';
        $('btnQuitar').style.display = src ? '' : 'none';
    }
    $('img').addEventListener('change', function () {
        var f = this.files[0]; if (!f) return;
        $('prodId').value = ''; imgProducto = null;
        var r = new FileReader(); r.onload = function (e) { mostrarFoto(e.target.result); }; r.readAsDataURL(f);
    });
    $('btnQuitar').addEventListener('click', function () { $('img').value = ''; $('prodId').value = ''; imgProducto = null; mostrarFoto(null); });

    function pintarTexto() {
        var t = $('txt').value; $('cnt').textContent = t.length.toLocaleString('es-CO') + ' / 2.200';
        $('pvTxt').innerHTML = t ? '<b>' + esc(cuenta().dataset.ig || cuenta().dataset.nom) + '</b> ' + esc(t) : '<span class="text-muted">Así se verá su publicación…</span>';
    }
    $('txt').addEventListener('input', pintarTexto); pintarTexto();
    document.querySelectorAll('[data-add]').forEach(function (b) { b.addEventListener('click', function () { var t = $('txt'); t.value = (t.value ? t.value.replace(/\s*$/, '') + ' ' : '') + b.dataset.add; pintarTexto(); t.focus(); }); });

    document.querySelectorAll('input[name=cuando]').forEach(function (r) { r.addEventListener('change', function () {
        var prog = document.querySelector('input[name=cuando]:checked').value === 'programar';
        $('prog').style.display = prog ? '' : 'none'; $('btnPub').querySelector('span').textContent = prog ? 'Programar publicación' : 'Publicar ahora';
    }); });

    // Productos
    var tp;
    function buscarProd() {
        fetch(R_PROD + '?q=' + encodeURIComponent($('ppQ').value.trim()), { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
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
        $('txt').value = '✨ ' + p.nombre + '\n💲 ' + p.precio + '\n\n📩 Escríbanos por interno para separar el suyo.' + (p.url ? '\n🛍️ Cómprelo aquí: ' + p.url : (TIENDA_URL ? '\n🛍️ Vea más en: ' + TIENDA_URL : ''));
        pintarTexto();
        if (!p.img) LP.toast('Este producto no tiene foto: súbala para poder publicar en Instagram', 'info');
    });

    $('fPub').addEventListener('submit', function (e) {
        if (!$('enFb').checked && !$('enIg').checked) { e.preventDefault(); LP.toast('Elija Facebook, Instagram o ambos', 'error'); return; }
        var hayFoto = $('img').files.length || imgProducto;
        if ($('enIg').checked && !hayFoto) { e.preventDefault(); LP.toast('Instagram necesita una foto', 'error'); return; }
        if (!hayFoto && !$('txt').value.trim()) { e.preventDefault(); LP.toast('Escriba un texto o suba una foto', 'error'); return; }
        $('btnPub').disabled = true; $('btnPub').querySelector('span').textContent = 'Publicando…';
    });
})();
</script>
@endsection
