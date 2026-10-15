@extends('layouts.app')

@section('title', 'Mis enlaces')

@push('css')
<style>
.me-url{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;background:#f4fcf6;border:1.5px solid #cfe9d6;border-radius:16px;padding:.7rem .9rem}
.me-url code{font-size:1rem;font-weight:800;color:#11702f;word-break:break-all}
.me-card{background:#fff;border:1.5px solid #e3efe5;border-radius:20px;padding:1.2rem;margin-bottom:1.2rem}
.me-card h2{font-size:1.1rem;font-weight:800;margin-bottom:.9rem}
.me-row{display:flex;align-items:center;gap:.7rem;border:1.5px solid #e3efe5;border-radius:16px;padding:.7rem .8rem;margin-bottom:.6rem;background:#fff}
.me-row.off{opacity:.5}
.me-row .ic{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:#f4fcf6;color:#11702f;flex:0 0 38px}
.me-row .tx{flex:1;min-width:0}
.me-row .tx b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.me-row .tx small{display:block;color:#6b7570;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.me-row .acc{display:flex;gap:.25rem;flex-wrap:wrap;justify-content:flex-end}
.me-sep{background:#f7f7f4}
.me-temas{display:grid;grid-template-columns:repeat(4,1fr);gap:.6rem}
.me-tema input{display:none}
.me-tema span{display:flex;align-items:flex-end;justify-content:center;height:84px;border-radius:14px;border:3px solid transparent;font-weight:800;font-size:.78rem;padding-bottom:.4rem;cursor:pointer;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08)}
.me-tema input:checked + span{border-color:#2fd15a;box-shadow:0 0 0 3px rgba(47,209,90,.25)}
.me-tel{position:sticky;top:90px}
.me-marco{width:100%;max-width:340px;margin:0 auto;aspect-ratio:9/18.5;border-radius:38px;border:10px solid #0b0d0c;overflow:hidden;box-shadow:0 24px 50px -24px rgba(0,0,0,.5);background:#0b0d0c}
.me-marco iframe{width:100%;height:100%;border:0;background:#fff}
.me-stat{display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem}
.me-stat div{background:#f4fcf6;border-radius:14px;padding:.7rem;text-align:center}
.me-stat b{display:block;font-size:1.4rem}
.me-stat small{color:#6b7570;font-weight:700}
#qr canvas,#qr img{margin:0 auto;display:block}
@media (max-width:575px){.me-temas{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
@php $tasa = $visitas > 0 ? round($clics / $visitas * 100) : 0; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Mi negocio</small><h1>Mis <span>enlaces</span></h1>
            <p class="text-muted mb-0">Un solo enlace para su Instagram, TikTok o WhatsApp con todo lo de su negocio.</p></div>
        <div class="lp-quick">
            <a href="{{ $url }}" target="_blank" class="btn btn-dark"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver mi página</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="me-url mb-4">
        <span class="badge {{ $tienda->enlaces_activo ? 'bg-success' : 'bg-secondary' }}">{{ $tienda->enlaces_activo ? 'Publicada' : 'Sin publicar' }}</span>
        <code id="me-link">{{ $url }}</code>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-light" id="me-copiar"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/?text={{ rawurlencode('Encuentre todo de ' . $tienda->nombre . ' aquí: ' . $url) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#me-qr"><i class="fa-solid fa-qrcode me-1"></i>QR</button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">

            {{-- Agregar --}}
            <div class="me-card">
                <h2><i class="fa-solid fa-plus me-1"></i>Agregar</h2>
                <form method="post" action="{{ route('enlaces.store') }}">
                    @csrf
                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="tipo" id="t-enlace" value="enlace" checked>
                        <label class="btn btn-outline-dark btn-sm" for="t-enlace"><i class="fa-solid fa-link me-1"></i>Enlace</label>
                        <input type="radio" class="btn-check" name="tipo" id="t-titulo" value="titulo">
                        <label class="btn btn-outline-dark btn-sm" for="t-titulo"><i class="fa-solid fa-heading me-1"></i>Título</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6"><input class="form-control" name="titulo" maxlength="80" required placeholder="Texto del botón (ej: Pida por WhatsApp)"></div>
                        <div class="col-md-6 me-solo-enlace"><input class="form-control" name="url" maxlength="500" placeholder="Enlace, número de WhatsApp o correo"></div>
                    </div>
                    <div class="me-solo-enlace">
                        <div class="row g-2 mt-1 align-items-center">
                            <div class="col-md-6"><select class="form-select" name="icono">@foreach (\App\Models\Enlace::ICONOS as $k => $txt)<option value="{{ $k }}">Ícono: {{ $txt }}</option>@endforeach</select></div>
                            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="destacado" value="1" id="n-dest"><label class="form-check-label" for="n-dest">Destacar (se mueve para llamar la atención)</label></div></div>
                        </div>
                        <details class="mt-2"><summary class="small fw-bold" style="cursor:pointer">Programar (opcional)</summary>
                            <div class="row g-2 mt-1">
                                <div class="col-md-6"><label class="small text-muted">Aparece desde</label><input type="datetime-local" class="form-control" name="desde"></div>
                                <div class="col-md-6"><label class="small text-muted">Se oculta después de</label><input type="datetime-local" class="form-control" name="hasta"></div>
                            </div>
                        </details>
                    </div>
                    <button class="btn btn-primary mt-3"><i class="fa-solid fa-plus me-1"></i>Agregar</button>
                </form>
            </div>

            {{-- Lista --}}
            <div class="me-card">
                <h2><i class="fa-solid fa-list me-1"></i>Sus enlaces <small class="text-muted fw-normal">({{ $enlaces->count() }})</small></h2>
                @forelse ($enlaces as $i => $e)
                <div class="me-row {{ $e->visible ? '' : 'off' }} {{ $e->tipo === 'titulo' ? 'me-sep' : '' }}">
                    <div class="ic"><i class="{{ $e->tipo === 'titulo' ? 'fa-solid fa-heading' : $e->icono_final }}"></i></div>
                    <div class="tx">
                        <b>{{ $e->titulo }} @if ($e->destacado)<i class="fa-solid fa-star text-warning small"></i>@endif</b>
                        <small>
                            @if ($e->tipo === 'titulo') Título de sección @else {{ $e->url }} · <b class="d-inline">{{ $e->clics }}</b> clics @endif
                            @if ($e->desde || $e->hasta) · <i class="fa-regular fa-clock"></i> {{ $e->desde?->format('d/m H:i') ?? '…' }} – {{ $e->hasta?->format('d/m H:i') ?? '…' }}@endif
                        </small>
                    </div>
                    <div class="acc">
                        <form method="post" action="{{ route('enlaces.mover', [$e, 'arriba']) }}">@csrf<button class="btn btn-sm btn-light" title="Subir" @disabled($i === 0)><i class="fa-solid fa-arrow-up"></i></button></form>
                        <form method="post" action="{{ route('enlaces.mover', [$e, 'abajo']) }}">@csrf<button class="btn btn-sm btn-light" title="Bajar" @disabled($i === $enlaces->count() - 1)><i class="fa-solid fa-arrow-down"></i></button></form>
                        <form method="post" action="{{ route('enlaces.alternar', $e) }}">@csrf<button class="btn btn-sm btn-light" title="{{ $e->activo ? 'Ocultar' : 'Mostrar' }}"><i class="fa-solid {{ $e->activo ? 'fa-eye' : 'fa-eye-slash' }}"></i></button></form>
                        <button class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#ed-{{ $e->id }}" title="Editar"><i class="fa-solid fa-pen"></i></button>
                        <form method="post" action="{{ route('enlaces.destroy', $e) }}" data-confirm="¿Eliminar «{{ $e->titulo }}»?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('delete')<button class="btn btn-sm btn-light" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </div>
                <div class="collapse" id="ed-{{ $e->id }}">
                    <form method="post" action="{{ route('enlaces.update', $e) }}" class="border rounded-4 p-3 mb-3">
                        @csrf @method('put')
                        <input type="hidden" name="tipo" value="{{ $e->tipo }}">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="small text-muted">Texto</label><input class="form-control" name="titulo" maxlength="80" required value="{{ $e->titulo }}"></div>
                            @if ($e->tipo === 'enlace')
                            <div class="col-md-6"><label class="small text-muted">Enlace</label><input class="form-control" name="url" maxlength="500" required value="{{ $e->url }}"></div>
                            <div class="col-md-6"><label class="small text-muted">Ícono</label><select class="form-select" name="icono">@foreach (\App\Models\Enlace::ICONOS as $k => $txt)<option value="{{ $k }}" @selected((string) $e->icono === (string) $k)>{{ $txt }}</option>@endforeach</select></div>
                            <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="destacado" value="1" id="d-{{ $e->id }}" @checked($e->destacado)><label class="form-check-label" for="d-{{ $e->id }}">Destacar</label></div></div>
                            <div class="col-md-6"><label class="small text-muted">Aparece desde</label><input type="datetime-local" class="form-control" name="desde" value="{{ $e->desde?->format('Y-m-d\TH:i') }}"></div>
                            <div class="col-md-6"><label class="small text-muted">Se oculta después de</label><input type="datetime-local" class="form-control" name="hasta" value="{{ $e->hasta?->format('Y-m-d\TH:i') }}"></div>
                            @endif
                        </div>
                        <button class="btn btn-primary btn-sm mt-3">Guardar cambios</button>
                    </form>
                </div>
                @empty
                <p class="text-muted mb-0">Aún no tiene enlaces. Agregue el primero: su WhatsApp, su ubicación, su menú o su promoción del mes.</p>
                @endforelse
                @if ($tienda->enlaces_catalogo && $tienda->catalogo_activo)
                <p class="small text-muted mt-2 mb-0"><i class="fa-solid fa-store me-1"></i>Al final sale solo el botón <b>"Ver nuestra tienda en línea"</b>.</p>
                @endif
            </div>

            {{-- Perfil y diseño --}}
            <div class="me-card">
                <h2><i class="fa-solid fa-palette me-1"></i>Perfil y diseño</h2>
                <form method="post" action="{{ route('enlaces.perfil') }}" enctype="multipart/form-data">
                    @csrf @method('put')
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="enlaces_activo" value="1" id="p-act" @checked($tienda->enlaces_activo)>
                        <label class="form-check-label fw-bold" for="p-act">Página publicada (cualquiera con el enlace la puede ver)</label>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        @php $fotoUrl = $tienda->enlaces_foto_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($tienda->enlaces_foto_path) : $tienda->logo_url; @endphp
                        @if ($fotoUrl)<img src="{{ $fotoUrl }}" alt="" width="64" height="64" style="border-radius:50%;object-fit:cover">@endif
                        <div class="flex-grow-1">
                            <label class="small text-muted">Foto de perfil (si no sube una, usa el logo del negocio)</label>
                            <input type="file" class="form-control" name="foto" accept="image/png,image/jpeg,image/webp">
                            @if ($tienda->enlaces_foto_path)<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="p-qf"><label class="form-check-label small" for="p-qf">Quitar foto y usar el logo</label></div>@endif
                        </div>
                    </div>
                    <label class="small text-muted">Descripción corta</label>
                    <textarea class="form-control mb-3" name="enlaces_bio" rows="2" maxlength="200" placeholder="Ej: Ropa para dama en Pasto 🛍️ Envíos a todo Colombia">{{ $tienda->enlaces_bio }}</textarea>

                    <label class="small text-muted d-block mb-1">Tema</label>
                    <div class="me-temas mb-3">
                        @foreach (\App\Models\Enlace::TEMAS as $k => $t)
                        <label class="me-tema"><input type="radio" name="enlaces_tema" value="{{ $k }}" @checked(($tienda->enlaces_tema ?: 'localproo') === $k)><span style="background:{{ $t[1] }};color:{{ $t[2] }}">{{ $t[0] }}</span></label>
                        @endforeach
                    </div>

                    <label class="small text-muted d-block mb-1">Botones</label>
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        @foreach (\App\Models\Enlace::BOTONES as $k => $txt)
                        <input type="radio" class="btn-check" name="enlaces_boton" id="b-{{ $k }}" value="{{ $k }}" @checked(($tienda->enlaces_boton ?: 'pildora') === $k)>
                        <label class="btn btn-outline-dark btn-sm" for="b-{{ $k }}" style="border-radius:{{ ['pildora' => '999px', 'redondeado' => '10px', 'cuadrado' => '2px', 'contorno' => '999px'][$k] }}">{{ $txt }}</label>
                        @endforeach
                    </div>

                    <label class="small text-muted d-block mb-1">Redes (salen como íconos arriba)</label>
                    <div class="row g-2 mb-3">
                        @foreach (\App\Models\Enlace::REDES as $k => [$nombre, $ico, $ayuda])
                        <div class="col-md-6"><div class="input-group"><span class="input-group-text" style="width:42px;justify-content:center"><i class="{{ $ico }}"></i></span><input class="form-control" name="redes[{{ $k }}]" maxlength="200" value="{{ $redes[$k] ?? '' }}" placeholder="{{ $nombre }}: {{ $ayuda }}"></div></div>
                        @endforeach
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="enlaces_catalogo" value="1" id="p-cat" @checked($tienda->enlaces_catalogo)>
                        <label class="form-check-label" for="p-cat">Mostrar el botón de mi tienda en línea</label>
                    </div>
                    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar perfil</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="me-tel">
                <div class="me-card">
                    <h2 class="d-flex justify-content-between align-items-center"><span><i class="fa-solid fa-chart-simple me-1"></i>Estadísticas</span>
                        <form method="post" action="{{ route('enlaces.reiniciar') }}" data-confirm="¿Poner las estadísticas en cero?" data-confirm-ok="Sí, reiniciar">@csrf<button class="btn btn-sm btn-light" title="Reiniciar"><i class="fa-solid fa-rotate-left"></i></button></form>
                    </h2>
                    <div class="me-stat">
                        <div><b>{{ number_format($visitas, 0, ',', '.') }}</b><small>Visitas</small></div>
                        <div><b>{{ number_format($clics, 0, ',', '.') }}</b><small>Clics</small></div>
                        <div><b>{{ $tasa }}%</b><small>Clics/visita</small></div>
                    </div>
                    @php $top = $enlaces->where('tipo', 'enlace')->sortByDesc('clics')->first(); @endphp
                    @if ($top && $top->clics > 0)<p class="small text-muted mt-2 mb-0">El más visitado: <b>{{ $top->titulo }}</b> ({{ $top->clics }} clics).</p>@endif
                </div>
                <div class="me-marco"><iframe src="{{ $url }}?vista=1" title="Vista previa" loading="lazy"></iframe></div>
                <p class="text-center small text-muted mt-2">Vista previa. Se actualiza al guardar.</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="me-qr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content" style="border-radius:22px">
        <div class="modal-body text-center p-4">
            <h5 class="fw-bold mb-3">Código QR</h5>
            <div id="qr" class="mb-3"></div>
            <p class="small text-muted">Imprímalo en el mostrador, el empaque o el menú.</p>
            <button type="button" class="btn btn-primary w-100" id="qr-bajar"><i class="fa-solid fa-download me-1"></i>Descargar</button>
        </div>
    </div></div>
</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    var url = @json($url);
    document.getElementById('me-copiar').addEventListener('click', function () {
        var b = this;
        navigator.clipboard.writeText(url).then(function () { b.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copiado'; setTimeout(function () { b.innerHTML = '<i class="fa-regular fa-copy me-1"></i>Copiar'; }, 1600); });
    });
    // En "Título" no se pide enlace ni ícono
    var solo = document.querySelectorAll('.me-solo-enlace');
    document.querySelectorAll('input[name=tipo]').forEach(function (r) {
        r.addEventListener('change', function () {
            var t = document.querySelector('input[name=tipo]:checked').value === 'titulo';
            solo.forEach(function (el) { el.style.display = t ? 'none' : ''; });
        });
    });
    var hecho = false;
    document.getElementById('me-qr').addEventListener('shown.bs.modal', function () {
        if (hecho || typeof QRCode === 'undefined') return;
        new QRCode(document.getElementById('qr'), { text: url, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
        hecho = true;
    });
    document.getElementById('qr-bajar').addEventListener('click', function () {
        var c = document.querySelector('#qr canvas');
        if (!c) return;
        var a = document.createElement('a');
        a.href = c.toDataURL('image/png');
        a.download = 'qr-mis-enlaces.png';
        a.click();
    });
})();
</script>
@endpush
