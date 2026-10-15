@extends('layouts.app')

@section('title', $plantilla->exists ? 'Editar plantilla' : 'Nueva plantilla')

@push('css')
<style>
.pf-prev{position:sticky;top:90px}
.pf-prev [data-obi-marco]{border-radius:14px;box-shadow:0 20px 50px -30px rgba(0,0,0,.6);background:#111}
.pf-op{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem}
.pf-op input{display:none}
.pf-op label{display:flex;flex-direction:column;justify-content:flex-end;min-height:76px;border-radius:14px;border:3px solid transparent;padding:.45rem .55rem;cursor:pointer;font-weight:800;font-size:.78rem;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08);line-height:1.15}
.pf-op input:checked + label{border-color:#2fd15a;box-shadow:0 0 0 3px rgba(47,209,90,.25)}
.pf-lista{display:flex;flex-direction:column;gap:.4rem}
.pf-lista input{display:none}
.pf-lista label{display:flex;align-items:center;gap:.6rem;border:1.5px solid #e3efe5;border-radius:12px;padding:.5rem .7rem;cursor:pointer;font-size:.88rem}
.pf-lista label i{width:22px;text-align:center;color:#6b7570}
.pf-lista input:checked + label{border-color:#2fd15a;background:#f4fcf6;font-weight:700}
.pf-chips{display:flex;flex-wrap:wrap;gap:.4rem}
.pf-chips input{display:none}
.pf-chips label{border:1.5px solid #e3efe5;border-radius:999px;padding:.35rem .8rem;cursor:pointer;font-size:.85rem;font-weight:600}
.pf-chips input:checked + label{border-color:#2fd15a;background:#f4fcf6}
.pf-ayuda{font-size:.8rem;color:#6b7570}
input[type=color]{width:48px;height:38px;padding:2px;border-radius:10px}
@media (max-width:575px){.pf-op{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
@php
    $P = \App\Models\ObituarioPlantilla::class;
    $iconMed = ['h' => 'fa-tv', 'v' => 'fa-mobile-screen', 'c' => 'fa-square'];
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Obituarios · Plantillas</small><h1>{{ $plantilla->exists ? 'Editar' : 'Nueva' }} <span>plantilla</span></h1></div>
        <div class="lp-quick"><a href="{{ route('obituarios.plantillas') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Plantillas</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-5">
            <form method="post" enctype="multipart/form-data" id="pf-form" action="{{ $plantilla->exists ? route('obituarios.plantillas.update', $plantilla) : route('obituarios.plantillas.store') }}">
                @csrf
                @if ($plantilla->exists) @method('put') @endif

                <div class="lp-box mb-3">
                    <label class="form-label small fw-bold">Nombre de la plantilla</label>
                    <input class="form-control" name="nombre" maxlength="60" required value="{{ old('nombre', $plantilla->nombre) }}" placeholder="Ej: Sala principal 4K">
                </div>

                <div class="lp-box mb-3">
                    <h3>Diseño</h3>
                    <div class="pf-op">
                        @foreach ($P::DISENOS as $k => [$nom, $desc, $ac, $tx, $bg])
                        <div><input type="radio" name="diseno" id="d-{{ $k }}" value="{{ $k }}" @checked(old('diseno', $plantilla->diseno) === $k)><label for="d-{{ $k }}" style="background:{{ $bg }};color:{{ $tx }}" title="{{ $desc }}">{{ $nom }}</label></div>
                        @endforeach
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Medida de la pantalla</h3>
                    <div class="pf-lista">
                        @foreach ($P::MEDIDAS as $k => [$nom, $w, $h, $o])
                        <div><input type="radio" name="medida" id="m-{{ $k }}" value="{{ $k }}" @checked(old('medida', $plantilla->medida) === $k)><label for="m-{{ $k }}"><i class="fa-solid {{ $iconMed[$o] }}"></i>{{ $nom }}</label></div>
                        @endforeach
                    </div>
                    <div class="pf-ayuda mt-2">Si no sabe, deje Full HD: se ve bien en casi todos los televisores. Si el televisor es 4K, elija 4K para que se vea más nítido.</div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Fondo</h3>
                    <div class="pf-chips mb-3">
                        @foreach ($P::FONDOS as $k => $nom)
                        <input type="radio" name="fondo_tipo" id="f-{{ $k }}" value="{{ $k }}" @checked(old('fondo_tipo', $plantilla->fondo_tipo) === $k)><label for="f-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <div data-fondo="color">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="color" name="fondo_color" value="{{ old('fondo_color', $plantilla->fondo_color ?: '#2a241b') }}">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="pf-deg" @checked(old('fondo_color2', $plantilla->fondo_color2))><label class="form-check-label small" for="pf-deg">Degradado con</label></div>
                            <input type="color" name="fondo_color2" id="pf-c2" value="{{ old('fondo_color2', $plantilla->fondo_color2 ?: '#0a0907') }}">
                        </div>
                    </div>
                    <div data-fondo="imagen">
                        <input type="file" class="form-control" name="imagen" id="pf-imagen" accept="image/png,image/jpeg,image/webp">
                        <div class="pf-ayuda mt-1">@if ($plantilla->fondo_url)Ya tiene una imagen; suba otra solo si la quiere cambiar. @endif Ideal del mismo tamaño de la pantalla (ej. 1920×1080). Máximo 8 MB.</div>
                    </div>
                    <div data-fondo="video">
                        <input type="file" class="form-control" name="video" id="pf-video" accept="video/mp4,video/webm">
                        <div class="pf-ayuda mt-1">@if ($plantilla->video_url)Ya tiene un video; suba otro solo si lo quiere cambiar. @endif MP4 corto (10 a 30 segundos) que se repite sin parar: nubes, velas, flores, cielo. Máximo 50 MB. Sin sonido.</div>
                    </div>
                    <div data-fondo="imagen video" class="mt-3">
                        <label class="form-label small fw-bold">Oscurecer el fondo para que se lea el texto: <span id="pf-osc-v">{{ old('oscurecer', $plantilla->oscurecer) }}</span>%</label>
                        <input type="range" class="form-range" name="oscurecer" min="0" max="80" step="5" value="{{ old('oscurecer', $plantilla->oscurecer) }}">
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Flores y adornos</h3>
                    <div class="pf-chips">
                        @foreach ($P::ADORNOS as $k => $nom)
                        <input type="radio" name="adornos" id="ad-{{ $k }}" value="{{ $k }}" @checked(old('adornos', $plantilla->adornos ?: 'muchos') === $k)><label for="ad-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <div class="pf-ayuda mt-2">Ramos de rosas alrededor de la foto y en las esquinas, con los colores del diseño.</div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Animación de fondo</h3>
                    <div class="pf-chips">
                        @foreach ($P::ANIMACIONES as $k => $nom)
                        <input type="radio" name="animacion" id="a-{{ $k }}" value="{{ $k }}" @checked(old('animacion', $plantilla->animacion) === $k)><label for="a-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <div class="pf-ayuda mt-2">Se mueve en el televisor. Va encima del fondo, también del video.</div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Textos y colores</h3>
                    <div class="row g-2">
                        <div class="col-sm-5"><label class="form-label small fw-bold">Título</label><input class="form-control" name="titulo" maxlength="60" value="{{ old('titulo', $plantilla->titulo) }}" placeholder="Obituario"></div>
                        <div class="col-sm-7"><label class="form-label small fw-bold">Frase</label><input class="form-control" name="frase" maxlength="120" value="{{ old('frase', $plantilla->frase) }}" placeholder="{{ $P::FRASE }}"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Mensaje de la funeraria <span class="text-muted fw-normal">(opcional)</span></label><input class="form-control" name="pie" maxlength="150" value="{{ old('pie', $plantilla->pie) }}" placeholder="Ej: Realice la compra de sus bonos de flores en recepción"></div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="mostrar_logo" value="1" id="pf-logo" @checked(old('mostrar_logo', $plantilla->mostrar_logo))>
                        <label class="form-check-label" for="pf-logo">Mostrar logo y nombre de la funeraria</label>
                    </div>
                    @unless ($tienda->logo_url)<div class="pf-ayuda">Aún no tiene logo: súbalo en <a href="{{ route('negocio.edit') }}">Datos del negocio</a>. Mientras tanto sale el nombre.</div>@endunless
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="colores_propios" value="1" id="pf-col" @checked(old('colores_propios', $plantilla->color_acento || $plantilla->color_texto))>
                        <label class="form-check-label" for="pf-col">Usar mis colores</label>
                    </div>
                    <div class="d-flex gap-3 mt-2" id="pf-colores">
                        <label class="small fw-bold d-flex align-items-center gap-2">Detalles <input type="color" name="color_acento" value="{{ old('color_acento', $plantilla->acento) }}"></label>
                        <label class="small fw-bold d-flex align-items-center gap-2">Letras <input type="color" name="color_texto" value="{{ old('color_texto', $plantilla->texto_color) }}"></label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-lg flex-grow-1"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar plantilla</button>
                    <button class="btn btn-dark btn-lg" name="volver" value="nuevo" title="Guardar y crear un obituario con esta plantilla"><i class="fa-solid fa-plus me-1"></i>Guardar y usar</button>
                </div>
            </form>
        </div>

        <div class="col-xl-7">
            <div class="pf-prev">
                <div class="d-flex align-items-center mb-2"><b>Vista previa</b><small class="text-muted ms-2" id="pf-med">{{ $plantilla->medida_datos['nombre'] }}</small></div>
                <div data-obi-marco>@include('obituarios._lienzo', ['p' => $plantilla, 'o' => $ejemplo, 'tienda' => $tienda])</div>
                <div class="pf-ayuda mt-2">@if ($ejemplo)Con los datos del último obituario.@else Con datos de ejemplo.@endif La letra se acomoda sola según cuántos familiares tenga cada obituario.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/obituarios.js') }}?v=2"></script>
<script>
(function () {
    var form = document.getElementById('pf-form'), el = document.querySelector('[data-obi]');
    var MEDIDAS = @json($P::MEDIDAS), BASE = @json($P::BASE), DISENOS = @json($P::DISENOS);
    var imagen = @json($plantilla->fondo_url), video = @json($plantilla->video_url);
    var frasePorDefecto = @json($P::FRASE);

    function val(n) { var c = form.querySelector('[name="' + n + '"]:checked'); return c ? c.value : (form.elements[n] ? form.elements[n].value : ''); }
    function aplicar() {
        var f = form.elements, med = MEDIDAS[val('medida')] || MEDIDAS.fhd, base = BASE[med[3]], dis = DISENOS[val('diseno')] || DISENOS.clasico;
        var tipo = val('fondo_tipo'), propios = f.colores_propios.checked;
        document.querySelectorAll('[data-fondo]').forEach(function (b) { b.style.display = b.dataset.fondo.split(' ').indexOf(tipo) !== -1 ? '' : 'none'; });
        document.getElementById('pf-colores').style.display = propios ? '' : 'none';
        document.getElementById('pf-c2').disabled = !document.getElementById('pf-deg').checked;
        document.getElementById('pf-osc-v').textContent = f.oscurecer.value;
        document.getElementById('pf-med').textContent = med[0];
        if (!propios) { f.color_acento.value = dis[2]; f.color_texto.value = dis[3]; }
        Obi.estilo(el, {
            diseno: val('diseno'), o: med[3], w: base[0], h: base[1], escala: med[1] / base[0],
            fondo_tipo: tipo, color1: f.fondo_color.value, color2: document.getElementById('pf-deg').checked ? f.fondo_color2.value : '',
            imagen: imagen, video: video, oscurecer: +f.oscurecer.value, anim: val('animacion'), adornos: val('adornos'),
            acento: propios ? f.color_acento.value : dis[2], texto: propios ? f.color_texto.value : dis[3],
            logo: f.mostrar_logo.checked, titulo: f.titulo.value, frase: f.frase.value, pie: f.pie.value
        });
    }
    // El degradado apagado no se envía (el campo queda deshabilitado)
    document.getElementById('pf-imagen').addEventListener('change', function () { if (this.files[0]) { imagen = URL.createObjectURL(this.files[0]); aplicar(); } });
    document.getElementById('pf-video').addEventListener('change', function () {
        var file = this.files[0]; if (!file) return;
        if (file.size > 50 * 1024 * 1024) { alert('El video pesa más de 50 MB. Use uno más corto o comprímalo.'); this.value = ''; return; }
        video = URL.createObjectURL(file); aplicar();
    });
    form.addEventListener('input', aplicar);
    form.addEventListener('change', aplicar);
    aplicar();
})();
</script>
@endpush
