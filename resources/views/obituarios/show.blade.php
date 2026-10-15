@extends('layouts.app')

@section('title', 'Obituario')

@push('css')
<style>
.os-prev [data-obi-marco]{border-radius:14px;box-shadow:0 20px 50px -30px rgba(0,0,0,.6);background:#111}
</style>
@endpush

@section('content')
@php
    $m = $plantilla->medida_datos;
    $medidas = collect(\App\Models\ObituarioPlantilla::MEDIDAS)->filter(fn ($x) => $x[3] === $m['orientacion']);
    $texto = "En memoria de *{$obituario->nombre}*"
        . ($obituario->sala ? "\nVelación: {$obituario->sala->nombre} · {$tienda->nombre}" : "\n{$tienda->nombre}")
        . ($obituario->fecha_texto ? "\nExequias: {$obituario->fecha_texto}" . ($obituario->hora_texto ? ", {$obituario->hora_texto}" : '') : '')
        . ($obituario->lugar_exequias ? "\nLugar: {$obituario->lugar_exequias}" : '')
        . ($obituario->destino_final ? "\nDestino final: {$obituario->destino_final}" : '');
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Obituario @if ($obituario->sala)· {{ $obituario->sala->nombre }}@endif</small><h1>{{ \Illuminate\Support\Str::title(mb_strtolower($obituario->nombre)) }}</h1>
            <p class="mb-0">@if ($obituario->en_pantalla)<span class="badge bg-success">En pantalla</span>@else<span class="badge bg-secondary">No está en pantalla</span>@endif
                <small class="text-muted ms-1">Plantilla: {{ $plantilla->nombre }} · {{ $m['nombre'] }}</small></p></div>
        <div class="lp-quick">
            <a href="{{ route('obituarios.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Obituarios</a>
            <a href="{{ route('obituarios.edit', $obituario) }}" class="btn btn-dark"><i class="fa-solid fa-pen me-1"></i>Editar</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="row g-4">
        <div class="col-xl-9 os-prev">
            <div data-obi-marco>@include('obituarios._lienzo', ['p' => $plantilla, 'o' => $obituario, 'tienda' => $tienda])</div>
        </div>
        <div class="col-xl-3">
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-download me-1"></i>Descargar</h3>
                <label class="form-label small fw-bold mb-1">Medida</label>
                <select class="form-select mb-3" id="os-medida">
                    @foreach ($medidas as $k => [$nom, $w, $h])
                    <option value="{{ $w / $m['base_ancho'] }}" data-k="{{ $k }}" @selected($k === $plantilla->medida)>{{ $nom }}</option>
                    @endforeach
                </select>
                <div class="small fw-bold mb-1"><i class="fa-regular fa-image me-1"></i>Imagen</div>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-primary flex-grow-1" data-img="png">PNG</button>
                    <button class="btn btn-primary flex-grow-1" data-img="jpg">JPG</button>
                </div>
                <div class="small fw-bold mb-1"><i class="fa-solid fa-film me-1"></i>Video <span class="text-muted fw-normal">(con la animación y el video de fondo)</span></div>
                <div class="d-flex gap-2 mb-2">
                    <select class="form-select" id="os-vformato"></select>
                    <select class="form-select" id="os-vseg" style="max-width:110px">
                        @foreach ([5, 10, 15, 30, 60] as $sg)<option value="{{ $sg }}" @selected($sg === 10)>{{ $sg }} seg</option>@endforeach
                    </select>
                </div>
                <button class="btn btn-dark w-100" id="os-video"><i class="fa-solid fa-video me-1"></i>Grabar y descargar video</button>
                <div class="progress mt-2" id="os-vprog" style="height:8px;display:none"><div class="progress-bar bg-success" style="width:0"></div></div>
                <small class="text-muted d-block mt-2" id="os-vayuda">El video se graba en tiempo real: deje esta pestaña abierta mientras termina. Sale máximo en Full HD.</small>
            </div>
            @if ($obituario->sala)
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-tv me-1"></i>{{ $obituario->sala->nombre }}</h3>
                <a class="btn btn-dark w-100" target="_blank" href="{{ $obituario->sala->url_pantalla }}"><i class="fa-solid fa-up-right-from-square me-1"></i>Abrir la pantalla de la sala</a>
                <small class="text-muted d-block mt-2">El televisor de la sala se actualiza solo en menos de un minuto.</small>
            </div>
            @endif
            <div class="lp-box">
                <a class="btn btn-success w-100 mb-2" target="_blank" href="https://wa.me/?text={{ rawurlencode($texto) }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar datos por WhatsApp</a>
                <form method="post" action="{{ route('obituarios.estado', $obituario) }}" class="mb-2">@csrf<button class="btn btn-light w-100">@if ($obituario->estado === 'activo')<i class="fa-solid fa-flag-checkered me-1"></i>Finalizar (sacar de la pantalla)@else<i class="fa-solid fa-rotate-left me-1"></i>Volver a mostrar@endif</button></form>
                <form method="post" action="{{ route('obituarios.destroy', $obituario) }}" data-confirm="¿Eliminar el obituario de {{ $obituario->nombre }}?" data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('delete')<button class="btn btn-link text-danger w-100">Eliminar</button></form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/obituarios.js') }}?v=4"></script>
<script>
(function () {
    var sel = document.getElementById('os-medida'), el = document.querySelector('[data-obi]');
    var base = @json('obituario-' . \Illuminate\Support\Str::slug($obituario->nombre));
    function nombre() { return base + '-' + sel.options[sel.selectedIndex].dataset.k; }
    function ocupado(b, si, txt) { b.disabled = si; b.innerHTML = si ? '<i class="fa-solid fa-spinner fa-spin me-1"></i>' + txt : b.dataset.txt; }

    document.querySelectorAll('[data-img]').forEach(function (b) {
        b.dataset.txt = b.innerHTML;
        b.addEventListener('click', function () {
            ocupado(b, true, '');
            el.dataset.escala = sel.value;
            Obi.exportar(el, nombre(), b.dataset.img)
                .catch(function () { alert('No se pudo crear la imagen. Intente de nuevo o use Chrome.'); })
                .finally(function () { ocupado(b, false); });
        });
    });

    var vf = document.getElementById('os-vformato'), vb = document.getElementById('os-video'), prog = document.getElementById('os-vprog');
    var formatos = Obi.formatosVideo();
    formatos.forEach(function (f, i) { var o = document.createElement('option'); o.value = i; o.textContent = f.nombre; vf.appendChild(o); });
    vb.dataset.txt = vb.innerHTML;
    if (!formatos.length) {
        vf.innerHTML = '<option>No disponible en este navegador</option>'; vf.disabled = true; vb.disabled = true;
        document.getElementById('os-vayuda').textContent = 'Este navegador no puede grabar video. Use Chrome o Edge en un computador.';
    }
    vb.addEventListener('click', function () {
        var seg = +document.getElementById('os-vseg').value;
        ocupado(vb, true, 'Grabando…');
        prog.style.display = ''; prog.firstElementChild.style.width = '0';
        el.dataset.escala = sel.value;
        Obi.video(el, { formato: formatos[+vf.value], segundos: seg, nombre: nombre(), progreso: function (p) { prog.firstElementChild.style.width = Math.round(p * 100) + '%'; } })
            .catch(function () { alert('No se pudo grabar el video. Intente con el otro formato o use Chrome.'); })
            .finally(function () { ocupado(vb, false); prog.style.display = 'none'; });
    });
})();
</script>
@endpush
