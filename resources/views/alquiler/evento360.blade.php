@extends('layouts.app')

@section('title', 'Evento 360 · ' . $a->numero)

@push('css')
<style>
.e3-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:16px}
@media (max-width:991px){.e3-grid{grid-template-columns:1fr}}
.e3-logos{display:flex;flex-wrap:wrap;gap:10px;margin:.4rem 0 .8rem}
.e3-logo{position:relative;width:96px;border:1.5px solid var(--lp-line);border-radius:16px;padding:6px;text-align:center;background:#0b0f0c}
.e3-logo img{width:100%;height:70px;object-fit:contain}
.e3-logo small{display:block;color:#cfe;font-size:.65rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.e3-logo label{position:absolute;top:-8px;right:-8px;background:#fff;border:1.5px solid var(--lp-line);border-radius:999px;padding:0 .45rem;font-size:.7rem;font-weight:800;cursor:pointer}
.e3-logo input:checked + img{opacity:.25}
.e3-qr{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem;text-align:center}
.e3-qr #qr svg{width:100%;max-width:260px;height:auto}
.e3-url{word-break:break-all;font-weight:700;font-size:.85rem;background:#f1f6f2;border-radius:12px;padding:.5rem .7rem}
@media print{
  .lp-side,.sb-topnav,.no-print,.e3-form{display:none!important}
  .e3-grid{display:block}
  .e3-qr{border:0}
  .e3-qr #qr svg{max-width:420px}
  .e3-cartel{font-size:1.6rem}
}
</style>
@endpush

@section('content')
@include('layouts.partials.alert')
<div class="lp-dash">
    <div class="lp-hello no-print">
        <div><small><i class="fa-solid fa-camera-rotate me-1"></i>{{ $a->numero }} · {{ $a->fecha->format('d/m/Y') }}</small><h1>Configurar evento <span>360</span></h1>
            <span class="text-muted fw-semibold">{{ $a->que_alquila }} · {{ $a->lugar }}. Suba el logo y la canción: los invitados escanean el QR y graban con eso ya puesto.</span></div>
        <div class="lp-quick"><a href="{{ route('alquileres.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Alquileres</a></div>
    </div>

    <div class="e3-grid">
        <form method="post" action="{{ route('alquileres.e360.guardar', $a) }}" enctype="multipart/form-data" class="lp-box e3-form">
            @csrf
            <h3>1. El evento</h3>
            <label class="form-label">Nombre del evento <span class="text-muted fw-normal">(sale arriba en la app de los invitados)</span></label>
            <input type="text" name="nombre" class="form-control mb-3" maxlength="60" required value="{{ old('nombre', $cfg['nombre'] ?? '') }}" placeholder="Ej. Boda Ana y Luis · Quince de Valentina">

            <h3>2. Logo del evento</h3>
            @if (!empty($cfg['logos']))
            <small class="text-muted">Toque <b>Quitar</b> en el que ya no quiera. El primero queda puesto; los demás el invitado los puede elegir.</small>
            <div class="e3-logos">
                @foreach ($cfg['logos'] as $i => $l)
                <div class="e3-logo">
                    <input type="checkbox" name="quitar[]" value="{{ $i }}" id="q{{ $i }}" class="d-none">
                    <img src="/storage/{{ $l['archivo'] }}" alt="{{ $l['nombre'] }}">
                    <label for="q{{ $i }}">Quitar</label>
                    <small>{{ $l['nombre'] }}</small>
                </div>
                @endforeach
            </div>
            @endif
            <input type="file" name="logos[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
            <small class="text-muted d-block mb-3">Puede subir hasta {{ \App\Http\Controllers\evento360Controller::MAX_LOGOS }} (PNG con fondo transparente queda mejor).</small>

            <h3>3. Canción</h3>
            @php $selAct = !empty($cfg['cancion']['archivo']) ? 'propia' : ((string) collect($canciones)->search(fn ($c) => ($cfg['cancion']['url'] ?? null) === $c['url'])); @endphp
            <select name="cancion_sel" class="form-select mb-2" id="cancionSel">
                <option value="">Sin canción del evento (el invitado elige)</option>
                @if (!empty($cfg['cancion']['archivo']))<option value="propia" selected>🎵 {{ $cfg['cancion']['nombre'] }} (subida para este evento)</option>@endif
                @foreach ($canciones as $id => $c)<option value="{{ $id }}" @selected($selAct === $id)>{{ $c['nombre'] }}</option>@endforeach
            </select>
            <label class="form-label small fw-bold mb-1">O suba la canción que pidió el cliente</label>
            <input type="file" name="cancion" class="form-control" accept="audio/*">
            <small class="text-muted d-block mb-3">MP3, M4A, WAV u OGG, máximo 20 MB. Si sube una, queda esa.</small>

            <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $url ? 'Guardar cambios' : 'Guardar y crear el QR' }}</button>
        </form>

        <div class="e3-qr">
            @if ($url)
            <h3 class="mb-1 e3-cartel">📸 {{ $cfg['nombre'] ?? $a->que_alquila }}</h3>
            <p class="fw-semibold text-muted mb-2 e3-cartel" style="font-size:.95rem">Escanee, grabe su video 360 y lléveselo con el logo y la canción del evento</p>
            <div id="qr" class="mb-2" data-url="{{ $url }}"></div>
            <div class="e3-url mb-3">{{ $url }}</div>
            <div class="d-grid gap-2 no-print">
                <button type="button" class="btn btn-dark fw-bold" id="qrBajar"><i class="fa-solid fa-download me-1"></i>Descargar QR</button>
                <button type="button" class="btn btn-light border fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Imprimir cartel con el QR</button>
                <button type="button" class="btn btn-light border fw-bold" id="qrCopiar"><i class="fa-solid fa-link me-1"></i>Copiar enlace</button>
                <a class="btn btn-success fw-bold" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('📸 ' . ($cfg['nombre'] ?? $a->que_alquila) . ': grabe su video 360 aquí ' . $url) }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar por WhatsApp</a>
                <a class="btn btn-light border fw-bold" href="{{ $url }}" target="_blank" rel="noopener"><i class="fa-solid fa-eye me-1"></i>Probar como invitado</a>
                @if (\Illuminate\Support\Facades\Route::has('giro360.mis') && \App\Support\TiendaActual::tienda()?->mod_360)
                <a class="btn btn-light border fw-bold" href="{{ route('giro360.mis', ['evento' => $cfg['nombre'] ?? '']) }}"><i class="fa-solid fa-users me-1"></i>Ver invitados de este evento</a>
                @endif
            </div>
            @else
            <div class="py-5 text-muted fw-semibold"><i class="fa-solid fa-qrcode fa-3x mb-3 d-block" style="color:#cfd8d2"></i>Guarde el evento y aquí le sale el <b>QR</b> para que los invitados entren.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/qrcode-generator.js') }}"></script>
<script>
(function () {
    var caja = document.getElementById('qr');
    if (caja && window.qrcode) {
        var q = qrcode(0, 'M'); q.addData(caja.dataset.url); q.make();
        caja.innerHTML = q.createSvgTag(8, 2);
        document.getElementById('qrBajar').addEventListener('click', function () {
            var a = document.createElement('a'); a.href = q.createDataURL(12, 4); a.download = 'qr-evento-360.gif'; document.body.appendChild(a); a.click(); a.remove();
        });
        var cp = document.getElementById('qrCopiar');
        cp.addEventListener('click', function () {
            var hecho = function () { cp.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enlace copiado'; setTimeout(function () { cp.innerHTML = '<i class="fa-solid fa-link me-1"></i>Copiar enlace'; }, 2000); };
            if (navigator.clipboard) navigator.clipboard.writeText(caja.dataset.url).then(hecho); else hecho();
        });
    }
    // Al tocar "Quitar" se marca el logo para quitarlo al guardar (y se puede desmarcar)
    document.querySelectorAll('.e3-logo label').forEach(function (l) {
        l.addEventListener('click', function () { setTimeout(function () { var c = document.getElementById(l.htmlFor); l.textContent = c.checked ? 'Deshacer' : 'Quitar'; }, 0); });
    });
})();
</script>
@endpush
