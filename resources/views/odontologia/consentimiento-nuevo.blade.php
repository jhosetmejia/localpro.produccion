@extends('layouts.app')

@section('title', 'Consentimiento · ' . $p->nombre_completo)

@push('css')
<style>
.cn-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:14px;align-items:start}
.cn-tipos{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:.8rem}
.cn-tipos a{border:1.5px solid var(--lp-line);border-radius:999px;padding:.35rem .8rem;font-weight:800;font-size:.8rem;text-decoration:none;color:var(--lp-ink);background:#fff}
.cn-tipos a.on{background:#0e7490;border-color:#0e7490;color:#fff}
.cn-texto{width:100%;min-height:380px;border:1.5px solid var(--lp-line);border-radius:16px;padding:.9rem 1rem;font-size:.95rem;line-height:1.6;resize:vertical}
.cn-firma{position:relative;border:2px dashed #67e8f9;border-radius:18px;background:#fcfeff;touch-action:none}
.cn-firma canvas{display:block;width:100%;height:220px;cursor:crosshair}
.cn-firma .ph{position:absolute;inset:auto 0 18px;text-align:center;color:#94a3b8;font-weight:800;pointer-events:none}
.cn-firma .ln{position:absolute;left:24px;right:24px;bottom:44px;border-top:1.5px solid #cbd5e1;pointer-events:none}
.cn-firma.lleno .ph{display:none}
.cn-lab{font-size:.75rem;font-weight:800;color:var(--lp-muted);display:block;margin:.6rem 0 .2rem}
@media (max-width:991px){.cn-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php $esMenor = $clave === 'menor'; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.paciente', $p) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p->nombre_completo }}</a></small>
            <h1>Consentimiento <span>informado</span></h1>
            <span class="text-muted fw-semibold">Léaselo o entrégueselo al paciente, resuelva sus dudas y que firme en la pantalla.</span></div>
    </div>
    @include('consultorio._nav')

    <div class="cn-tipos">
        @foreach ($plantillas as $k => [$tit])
        <a href="{{ route('odonto.consentimiento.nuevo', [$p, 'plantilla' => $k]) }}" class="{{ $clave === $k ? 'on' : '' }}">{{ $tit }}</a>
        @endforeach
    </div>

    <form method="post" action="{{ route('odonto.consentimiento.guardar', $p) }}" id="cn-form">
        @csrf
        <input type="hidden" name="plantilla" value="{{ $clave }}">
        <input type="hidden" name="firma" id="cn-firma-dato">
        <div class="cn-grid">
            <div class="cs-card">
                <h3>{{ $plantillas[$clave][0] }}</h3>
                <label class="cn-lab">Procedimiento</label>
                <div class="d-flex gap-2">
                    <input name="procedimiento" id="cn-proc" class="form-control" value="{{ $procedimiento }}" maxlength="200" style="border-radius:12px">
                    <a href="#" class="cs-btn sm text-nowrap" id="cn-act"><i class="fa-solid fa-rotate"></i>Poner en el texto</a>
                </div>
                <label class="cn-lab">Texto del consentimiento <span class="fw-normal">(lo puede ajustar antes de firmar; después ya no se cambia)</span></label>
                <textarea name="texto" class="cn-texto" maxlength="20000" required>{{ old('texto', $texto) }}</textarea>
            </div>
            <div class="d-flex flex-column gap-3">
                <div class="cs-card">
                    <h3>Quién firma</h3>
                    <label class="cn-lab">Nombre</label>
                    <input name="firmante_nombre" class="form-control" required maxlength="160" style="border-radius:12px" value="{{ old('firmante_nombre', $esMenor ? '' : $p->nombre_completo) }}" placeholder="{{ $esMenor ? 'Nombre del acudiente' : '' }}">
                    <div class="d-flex gap-2">
                        <div class="flex-fill"><label class="cn-lab">Documento</label><input name="firmante_documento" class="form-control" maxlength="40" style="border-radius:12px" value="{{ old('firmante_documento', $esMenor ? '' : trim($p->tipo_documento . ' ' . $p->documento)) }}"></div>
                        <div class="flex-fill"><label class="cn-lab">Calidad</label>
                            <select name="firmante_relacion" class="form-select" data-nativo style="border-radius:12px">
                                @foreach (['Paciente', 'Acudiente', 'Madre', 'Padre', 'Representante legal'] as $r)<option @selected(old('firmante_relacion', $esMenor ? 'Acudiente' : 'Paciente') === $r)>{{ $r }}</option>@endforeach
                            </select></div>
                    </div>
                    <label class="cn-lab">Firma <span class="fw-normal">(con el dedo en el celular o tableta, o con el mouse)</span></label>
                    <div class="cn-firma" id="cn-caja"><canvas id="cn-canvas"></canvas><div class="ln"></div><div class="ph">Firme aquí</div></div>
                    <div class="d-flex justify-content-between mt-2">
                        <button type="button" class="cs-btn sm" id="cn-borrar"><i class="fa-solid fa-eraser"></i>Borrar firma</button>
                        <small class="text-muted align-self-center">Se guarda con fecha, hora y su nombre.</small>
                    </div>
                </div>
                <button class="cs-btn prim justify-content-center" style="padding:.9rem"><i class="fa-solid fa-file-signature"></i>Guardar consentimiento firmado</button>
                <div class="cs-privado"><i class="fa-solid fa-lock"></i>Queda en la historia del paciente y no se puede modificar después de firmado.</div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var cv = document.getElementById('cn-canvas'), caja = document.getElementById('cn-caja'), ctx = cv.getContext('2d'), trazos = 0, dibujando = false, ult = null;
    function ajustar() {
        var r = cv.getBoundingClientRect(), dpr = Math.max(1, window.devicePixelRatio || 1);
        var copia = trazos ? cv.toDataURL() : null;
        cv.width = Math.round(r.width * dpr); cv.height = Math.round(r.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.lineWidth = 2.6; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0f172a';
        if (copia) { var im = new Image(); im.onload = function () { ctx.drawImage(im, 0, 0, r.width, r.height); }; im.src = copia; }
    }
    function pos(e) { var r = cv.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
    cv.addEventListener('pointerdown', function (e) { dibujando = true; ult = pos(e); cv.setPointerCapture(e.pointerId); ctx.beginPath(); ctx.arc(ult.x, ult.y, 1.2, 0, Math.PI * 2); ctx.fill(); });
    cv.addEventListener('pointermove', function (e) {
        if (!dibujando) return;
        var p = pos(e); ctx.beginPath(); ctx.moveTo(ult.x, ult.y); ctx.lineTo(p.x, p.y); ctx.stroke(); ult = p; trazos++; caja.classList.add('lleno');
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) { cv.addEventListener(ev, function () { dibujando = false; }); });
    document.getElementById('cn-borrar').addEventListener('click', function () { trazos = 0; ctx.clearRect(0, 0, cv.width, cv.height); caja.classList.remove('lleno'); });
    window.addEventListener('resize', ajustar); ajustar();

    document.getElementById('cn-form').addEventListener('submit', function (e) {
        if (trazos < 8) { e.preventDefault(); if (window.LP) LP.toast('Falta la firma: que el paciente firme en el recuadro.', 'error'); return; }
        document.getElementById('cn-firma-dato').value = cv.toDataURL('image/png');
    });
    // Cambiar el procedimiento: recarga el texto de la plantilla con el procedimiento nuevo
    document.getElementById('cn-act').addEventListener('click', function (e) {
        e.preventDefault();
        var u = new URL(window.location.href); u.searchParams.set('plantilla', {!! json_encode($clave) !!}); u.searchParams.set('procedimiento', document.getElementById('cn-proc').value);
        window.location = u.toString();
    });
})();
</script>
@endpush
