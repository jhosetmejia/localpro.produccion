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
                <h3><i class="fa-solid fa-download me-1"></i>Descargar imagen</h3>
                <select class="form-select mb-2" id="os-medida">
                    @foreach ($medidas as $k => [$nom, $w, $h])
                    <option value="{{ $w / $m['base_ancho'] }}" data-k="{{ $k }}" @selected($k === $plantilla->medida)>{{ $nom }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary w-100" id="os-bajar"><i class="fa-solid fa-image me-1"></i>Descargar PNG</button>
                <small class="text-muted d-block mt-2">Para memoria USB, WhatsApp o redes. En la imagen sale el primer cuadro del video y no se mueve.</small>
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
<script src="{{ asset('js/obituarios.js') }}?v=3"></script>
<script>
(function () {
    var b = document.getElementById('os-bajar'), sel = document.getElementById('os-medida'), el = document.querySelector('[data-obi]');
    b.addEventListener('click', function () {
        var txt = b.innerHTML;
        b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Preparando…';
        el.dataset.escala = sel.value;
        var nombre = 'obituario-' + @json(\Illuminate\Support\Str::slug($obituario->nombre)) + '-' + sel.options[sel.selectedIndex].dataset.k + '.png';
        Obi.exportar(el, nombre).catch(function () { alert('No se pudo crear la imagen. Intente de nuevo o use otro navegador (Chrome).'); })
            .finally(function () { b.disabled = false; b.innerHTML = txt; });
    });
})();
</script>
@endpush
