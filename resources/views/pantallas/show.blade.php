@extends('layouts.app')

@section('title', $pantalla->nombre . ' · Pantallas')

@push('css')
<style>
.pt-codigo{font-size:.95rem}.pt-codigo b{font-size:1.2rem;color:#0b5a25;background:#eaf7ee;border-radius:8px;padding:.1rem .5rem;white-space:nowrap}
.pt-subir{border:2px dashed #9fd3ae;border-radius:18px;background:#f6fbf7;padding:1.2rem;text-align:center}
.pt-subir.sobre{background:#e4f5e9;border-color:#22a050}
.pt-item{display:flex;gap:.8rem;align-items:center;border:1.5px solid #e3efe5;border-radius:16px;padding:.6rem;background:#fff;margin-bottom:.6rem}
.pt-item.off{opacity:.55}
.pt-mini{width:120px;height:68px;border-radius:10px;overflow:hidden;background:#111;flex:0 0 120px;position:relative}
.pt-mini img,.pt-mini video{width:100%;height:100%;object-fit:cover;display:block}
.pt-mini .tipo{position:absolute;left:6px;bottom:6px;background:rgba(0,0,0,.6);color:#fff;border-radius:6px;font-size:.7rem;padding:0 .35rem}
.pt-item .tx{flex:1;min-width:0}
.pt-item .tx b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pt-num{width:72px}
.pt-pasos{background:#f6faf7;border:1.5px dashed #cfe0d4;border-radius:14px;padding:.7rem .9rem;font-size:.88rem}
.pt-pasos ol{padding-left:1.1rem}
@media (max-width:575px){.pt-item{flex-wrap:wrap}.pt-mini{width:100%;flex-basis:100%;height:150px}}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('pantallas.index') }}" class="text-reset">Pantallas</a></small><h1>{{ $pantalla->nombre }}</h1>
            @if ($pantalla->codigo)<div class="pt-codigo">En el TV escriba: <b>{{ $pantalla->url_corta }}</b></div>@endif</div>
        <div class="lp-quick">
            <button type="button" class="btn btn-light" data-copiar="{{ $pantalla->url_pantalla }}"><i class="fa-regular fa-copy me-1"></i>Copiar dirección</button>
            <a href="{{ $pantalla->url_pantalla }}" target="_blank" class="btn btn-dark"><i class="fa-solid fa-tv me-1"></i>Ver la pantalla</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-plus me-1"></i>Agregar imagen o video</h3>
                <form method="post" action="{{ route('pantallas.items.store', $pantalla) }}" enctype="multipart/form-data" id="pt-form">
                    @csrf
                    <label class="pt-subir d-block" id="pt-zona" for="pt-archivos">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x mb-2" style="color:#22a050"></i>
                        <div class="fw-bold">Toque aquí o arrastre sus imágenes y videos</div>
                        <div class="small text-muted">JPG, PNG, WebP, GIF, MP4 o WebM · hasta 50 MB cada uno · puede elegir varios</div>
                        <div class="small fw-bold mt-1" id="pt-elegidos"></div>
                    </label>
                    <input type="file" name="archivos[]" id="pt-archivos" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" class="d-none">
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
                        <label class="small fw-bold">Cada imagen se muestra</label>
                        <input type="number" class="form-control form-control-sm pt-num" name="segundos" min="3" max="600" value="10">
                        <span class="small">segundos</span>
                        <span class="small text-muted">(los videos duran lo que dure el video)</span>
                        <button class="btn btn-primary ms-auto" id="pt-btn" disabled><i class="fa-solid fa-upload me-1"></i>Agregar a la pantalla</button>
                    </div>
                </form>
            </div>

            <div class="lp-box">
                <h3><i class="fa-solid fa-list-ol me-1"></i>Lo que sale en la pantalla <span class="text-muted small fw-normal">(en este orden)</span></h3>
                @forelse ($items as $it)
                <div class="pt-item {{ $it->activo ? '' : 'off' }}">
                    <div class="pt-mini">
                        @if ($it->tipo === 'video')<video src="{{ $it->url }}#t=1" muted preload="metadata" playsinline></video>@else<img src="{{ $it->url }}" alt="" loading="lazy">@endif
                        <span class="tipo"><i class="fa-solid {{ $it->tipo === 'video' ? 'fa-video' : 'fa-image' }}"></i> {{ $it->tipo === 'video' ? 'Video' : 'Imagen' }}</span>
                    </div>
                    <div class="tx">
                        <b>{{ $loop->iteration }}. {{ $it->nombre ?: ($it->tipo === 'video' ? 'Video' : 'Imagen') }}</b>
                        <form method="post" action="{{ route('pantallas.items.update', $it) }}" class="d-flex align-items-center gap-2 flex-wrap mt-1">
                            @csrf @method('put')
                            @if ($it->tipo === 'imagen')
                            <input type="number" class="form-control form-control-sm pt-num" name="segundos" min="3" max="600" value="{{ $it->segundos }}"><span class="small">seg.</span>
                            @else
                            <input type="hidden" name="segundos" value="{{ $it->segundos }}"><span class="small text-muted">Dura lo que dure el video</span>
                            @endif
                            <input type="hidden" name="activo" value="0">
                            <div class="form-check form-switch small mb-0"><input class="form-check-input" type="checkbox" name="activo" value="1" id="ac-{{ $it->id }}" @checked($it->activo) onchange="this.form.submit()"><label class="form-check-label" for="ac-{{ $it->id }}">Mostrar</label></div>
                            @if ($it->tipo === 'imagen')<button class="btn btn-sm btn-light">Guardar</button>@endif
                        </form>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex gap-1">
                            <form method="post" action="{{ route('pantallas.items.mover', $it) }}">@csrf<input type="hidden" name="a" value="arriba"><button class="btn btn-sm btn-light" title="Subir" @disabled($loop->first)><i class="fa-solid fa-arrow-up"></i></button></form>
                            <form method="post" action="{{ route('pantallas.items.mover', $it) }}">@csrf<input type="hidden" name="a" value="abajo"><button class="btn btn-sm btn-light" title="Bajar" @disabled($loop->last)><i class="fa-solid fa-arrow-down"></i></button></form>
                        </div>
                        <form method="post" action="{{ route('pantallas.items.destroy', $it) }}" data-confirm="¿Quitar este archivo de la pantalla?" data-confirm-ok="Sí, quitar" data-peligro>@csrf @method('delete')<button class="btn btn-sm btn-light text-danger w-100" title="Quitar"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-photo-film fa-2x mb-2"></i>
                    <p class="mb-0">Todavía no hay nada. Agregue su primera imagen o video arriba.</p>
                </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="lp-box mb-3">
                @include('pantallas._tv-pasos')
            </div>
            <div class="lp-box">
                <h3><i class="fa-solid fa-gear me-1"></i>Ajustes</h3>
                <form method="post" action="{{ route('pantallas.update', $pantalla) }}">
                    @csrf @method('put')
                    <label class="form-label small fw-bold">Nombre</label>
                    <input class="form-control mb-2" name="nombre" maxlength="60" required value="{{ old('nombre', $pantalla->nombre) }}">
                    <label class="form-label small fw-bold">Cómo se ven las imágenes y videos</label>
                    <select class="form-select mb-2" name="ajuste">@foreach (\App\Models\Pantalla::AJUSTES as $k => $t)<option value="{{ $k }}" @selected($pantalla->ajuste === $k)>{{ $t }}</option>@endforeach</select>
                    <input type="hidden" name="activa" value="0">
                    <div class="form-check form-switch small mb-1"><input class="form-check-input" type="checkbox" name="activa" value="1" id="pt-act" @checked($pantalla->activa)><label class="form-check-label" for="pt-act">Pantalla encendida</label></div>
                    <div class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="nueva_direccion" value="1" id="pt-nd"><label class="form-check-label" for="pt-nd">Cambiar el código (si alguien que no debe lo tiene)</label></div>
                    <button class="btn btn-dark w-100">Guardar ajustes</button>
                </form>
                <form method="post" action="{{ route('pantallas.destroy', $pantalla) }}" data-confirm="¿Eliminar la pantalla «{{ $pantalla->nombre }}» y todo lo que tiene?" data-confirm-ok="Sí, eliminar" data-peligro class="mt-2 text-center">@csrf @method('delete')<button class="btn btn-sm btn-link text-danger">Eliminar pantalla</button></form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var inp = document.getElementById('pt-archivos'), zona = document.getElementById('pt-zona'), btn = document.getElementById('pt-btn'), txt = document.getElementById('pt-elegidos'), form = document.getElementById('pt-form');
    var MAX = 50 * 1024 * 1024;
    function revisar() {
        var fs = Array.prototype.slice.call(inp.files || []), grandes = fs.filter(function (f) { return f.size > MAX; });
        if (grandes.length) { LP.toast('«' + grandes[0].name + '» pesa más de 50 MB. Use un video más corto o comprímalo.', 'error'); inp.value = ''; fs = []; }
        if (fs.length > 20) { LP.toast('Máximo 20 archivos por vez.', 'error'); inp.value = ''; fs = []; }
        txt.textContent = fs.length ? (fs.length === 1 ? fs[0].name : fs.length + ' archivos elegidos') : '';
        btn.disabled = !fs.length;
    }
    inp.addEventListener('change', revisar);
    ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('sobre'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('sobre'); }); });
    zona.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; revisar(); } });
    form.addEventListener('submit', function () { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo…'; });
    document.querySelectorAll('[data-copiar]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = b.getAttribute('data-copiar');
            (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { LP.toast('Dirección copiada.'); })
                .catch(function () { LP.toast('No se pudo copiar. La dirección es: ' + t, 'error'); });
        });
    });
})();
</script>
@endpush
