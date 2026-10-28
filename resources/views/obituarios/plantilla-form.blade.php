@extends('layouts.app')

@section('title', $plantilla->exists ? 'Editar plantilla' : 'Nueva plantilla')

@push('css')
<style>
.pf-prev{position:sticky;top:90px}
.pf-libre{border:1.5px dashed #9fd3ae;background:#f6fbf7;border-radius:14px;padding:.8rem}
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
.pf-rango{display:grid;grid-template-columns:110px 1fr 52px;align-items:center;gap:.6rem;margin-top:.35rem}
.pf-rango label{font-size:.82rem;font-weight:700;margin:0}
.pf-rango output{font-size:.8rem;color:#6b7570;text-align:right}
.pf-logo-box{display:flex;gap:.8rem;align-items:center;border:1.5px dashed #cfe0d4;border-radius:14px;padding:.7rem;margin-top:.6rem}
.pf-logo-prev{width:120px;height:80px;flex:0 0 120px;border-radius:10px;display:grid;place-items:center;overflow:hidden;
  background:repeating-conic-gradient(#e9ecea 0 25%,#fff 0 50%) 0 0/16px 16px}
.pf-logo-prev img{max-width:100%;max-height:100%;object-fit:contain}
.pf-aviso{border:2px solid #f5b544;background:#fff8e8;border-radius:12px;padding:.6rem .8rem;margin-top:.6rem;font-size:.85rem}
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
                    <input class="form-control" name="nombre" maxlength="60" required value="{{ old('nombre', $plantilla->nombre) }}" placeholder="Ej: Principal 4K">
                </div>

                <div class="lp-box mb-3">
                    <h3>Diseño</h3>
                    @foreach (['Sin diseño (para su propia imagen)' => 'ninguno', 'Tarjetas prediseñadas' => 'tarjeta', 'Clásicos (3 columnas)' => 'clasico'] as $grupoTit => $grupo)
                    <div class="small fw-bold text-muted mb-1 {{ $grupo === 'ninguno' ? '' : 'mt-3' }}">{{ $grupoTit }}</div>
                    <div class="pf-op">
                        @foreach ($P::DISENOS as $k => [$nom, $desc, $ac, $tx, $bg])
                        @continue(($k === 'ninguno' ? 'ninguno' : (in_array($k, $P::TARJETAS, true) ? 'tarjeta' : 'clasico')) !== $grupo)
                        <div><input type="radio" name="diseno" id="d-{{ $k }}" value="{{ $k }}" @checked(old('diseno', $plantilla->diseno) === $k)><label for="d-{{ $k }}" style="background:{{ $bg }};color:{{ $tx }}" title="{{ $desc }}">{{ $nom }}</label></div>
                        @endforeach
                    </div>
                    @endforeach
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
                    <div class="pf-ayuda mt-2">Ramos de rosas (o lirios y hojas en las tarjetas) junto a la foto y en las esquinas, con los colores del diseño.</div>
                    @php $fl = $plantilla->flores_datos; @endphp
                    <label class="form-label small fw-bold mt-3 mb-1">Flores junto a la foto</label>
                    <div class="pf-chips">
                        @foreach ($P::FLOR_POS as $k => $nom)
                        <input type="radio" name="flor_pos" id="fp-{{ $k }}" value="{{ $k }}" @checked(old('flor_pos', $fl['pos']) === $k)><label for="fp-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <div class="pf-rango"><label for="pf-fx">Mover ← →</label><input type="range" class="form-range" id="pf-fx" name="flor_x" min="-400" max="400" step="5" value="{{ old('flor_x', $fl['x']) }}"><output data-de="pf-fx"></output></div>
                    <div class="pf-rango"><label for="pf-fy">Mover ↑ ↓</label><input type="range" class="form-range" id="pf-fy" name="flor_y" min="-400" max="400" step="5" value="{{ old('flor_y', $fl['y']) }}"><output data-de="pf-fy"></output></div>
                    <div class="pf-rango"><label for="pf-ft">Tamaño</label><input type="range" class="form-range" id="pf-ft" name="flor_tam" min="40" max="200" step="5" value="{{ old('flor_tam', $fl['tam']) }}"><output data-de="pf-ft" data-suf="%"></output></div>
                    <label class="form-label small fw-bold mt-3 mb-1">Flores de las esquinas</label>
                    <div class="pf-chips">
                        @foreach ($P::FLOR_ESQ as $k => $nom)
                        <input type="radio" name="flor_esq" id="fe-{{ $k }}" value="{{ $k }}" @checked(old('flor_esq', $fl['esq']) === $k)><label for="fe-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-light mt-2" id="pf-flor-reset"><i class="fa-solid fa-rotate-left me-1"></i>Volver al lugar original</button>
                </div>

                <div class="lp-box mb-3">
                    <h3>Animación de fondo</h3>
                    <div class="pf-chips">
                        @foreach ($P::ANIMACIONES as $k => $nom)
                        <input type="radio" name="animacion" id="a-{{ $k }}" value="{{ $k }}" @checked(old('animacion', $plantilla->animacion) === $k)><label for="a-{{ $k }}">{{ $nom }}</label>
                        @endforeach
                    </div>
                    <div class="pf-ayuda mt-2">Se mueve en el televisor y en el video que descargue. Va encima del fondo, también del video.</div>
                    @php $an = $plantilla->anim_datos; @endphp
                    <div class="small fw-bold mt-3 mb-1">¿Cuántos?</div>
                    <div class="pf-chips mb-1">
                        @foreach ([50 => 'Pocos', 100 => 'Normal', 220 => 'Muchos', 400 => 'Muchísimos'] as $v => $nom)
                        <button type="button" class="btn btn-sm btn-light" data-cant="{{ $v }}">{{ $nom }}</button>
                        @endforeach
                    </div>
                    <div class="pf-rango"><label for="pf-ac">Cantidad</label><input type="range" class="form-range" id="pf-ac" name="anim_cant" min="20" max="400" step="10" value="{{ old('anim_cant', $an['cant']) }}"><output data-de="pf-ac" data-suf="%"></output></div>
                    <div class="pf-rango"><label for="pf-at">Tamaño</label><input type="range" class="form-range" id="pf-at" name="anim_tam" min="40" max="300" step="10" value="{{ old('anim_tam', $an['tam']) }}"><output data-de="pf-at" data-suf="%"></output></div>
                    <div class="pf-rango"><label for="pf-av">Velocidad</label><input type="range" class="form-range" id="pf-av" name="anim_vel" min="30" max="300" step="10" value="{{ old('anim_vel', $an['vel']) }}"><output data-de="pf-av" data-suf="%"></output></div>
                </div>

                <div class="lp-box mb-3">
                    <h3>Textos y colores</h3>
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label small fw-bold">Título</label><input class="form-control" name="titulo" maxlength="60" value="{{ old('titulo', $plantilla->titulo) }}" placeholder="Obituario"></div>

                        <div class="col-12"><label class="form-label small fw-bold">Frase destacada <span class="text-muted fw-normal">(arriba a la derecha, en las tarjetas prediseñadas)</span></label><input class="form-control" name="cita" maxlength="160" value="{{ old('cita', $plantilla->cita) }}" placeholder="Ej: Tu recuerdo vivirá por siempre en nuestros corazones"></div>
                        @php $pieModo = old('pie_modo', $plantilla->pie_modo ?: 'frase'); @endphp
                        <div class="col-sm-5"><label class="form-label small fw-bold">Pie de la tarjeta</label>
                            <select class="form-select" name="pie_modo" id="pf-pie-m">@foreach ($P::PIE_MODOS as $k => $t)<option value="{{ $k }}" @selected($pieModo === $k)>{{ $t }}</option>@endforeach</select></div>
                        <div class="col-sm-7" data-pie-campo="nombre"><label class="form-label small fw-bold">Nombre en el pie <span class="text-muted fw-normal">(vacío = el del negocio)</span></label><input class="form-control" name="pie_nombre" maxlength="80" value="{{ old('pie_nombre', $plantilla->pie_nombre) }}" placeholder="{{ $tienda->nombre }}"></div>
                        <div class="col-12" data-pie-campo="frase"><label class="form-label small fw-bold">Frase del pie</label><input class="form-control" name="pie" maxlength="150" value="{{ old('pie', $plantilla->pie) }}" placeholder="Ej: Un homenaje de amor"></div>
                        <div class="col-12" data-pie-campo="eslogan"><label class="form-label small fw-bold">Eslogan</label><input class="form-control" name="pie_eslogan" maxlength="150" value="{{ old('pie_eslogan', $plantilla->pie_eslogan) }}" placeholder="Ej: Excelencia en servicios... Más humana"></div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="mostrar_logo" value="1" id="pf-logo" @checked(old('mostrar_logo', $plantilla->mostrar_logo))>
                        <label class="form-check-label" for="pf-logo">Mostrar logo y nombre de la funeraria</label>
                    </div>
                    <div class="mt-2" style="max-width:340px"><label class="form-label small fw-bold">Fondo del logo</label>
                        <select class="form-select" name="logo_fondo">@foreach ($P::LOGO_FONDOS as $k => $t)<option value="{{ $k }}" @selected(old('logo_fondo', $plantilla->logo_fondo ?: 'sin') === $k)>{{ $t }}</option>@endforeach</select>
                        <div class="pf-ayuda mt-1">Si sube el logo en PNG sin fondo, elija "Sin fondo": se ve tal cual sobre la tarjeta.</div></div>
                    <div class="mt-3"><label class="form-label small fw-bold">Dónde sale el lugar de velación <span class="text-muted fw-normal">(ej: "Sala 2")</span></label>
                        <select class="form-select" name="velacion_pos" style="max-width:340px">@foreach ($P::VELACION_POS as $k => $t)<option value="{{ $k }}" @selected(old('velacion_pos', $plantilla->velacion_pos ?: 'foto') === $k)>{{ $t }}</option>@endforeach</select>
                        <div class="pf-rango"><label for="pf-vx">Mover ← →</label><input type="range" class="form-range" id="pf-vx" name="velacion_x" min="-400" max="400" step="5" value="{{ old('velacion_x', (int) $plantilla->velacion_x) }}"><output data-de="pf-vx"></output></div>
                        <div class="pf-rango"><label for="pf-vy">Mover ↑ ↓</label><input type="range" class="form-range" id="pf-vy" name="velacion_y" min="-400" max="400" step="5" value="{{ old('velacion_y', (int) $plantilla->velacion_y) }}"><output data-de="pf-vy"></output></div>
                        <div class="pf-ayuda mt-1"><i class="fa-solid fa-hand-pointer"></i> También puede <b>arrastrarlo</b> con el dedo o el mouse en la vista previa (el recuadro verde punteado).</div>
                        <button type="button" class="btn btn-sm btn-link px-0" id="pf-v0">Volver al lugar de siempre</button>
                    </div>
                    @php $logoObi = $tienda->obituarios_logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($tienda->obituarios_logo_path) : null; @endphp
                    <div class="pf-logo-box">
                        <div class="pf-logo-prev" id="pf-logo-prev">@if ($logoObi ?: $tienda->logo_url)<img src="{{ $logoObi ?: $tienda->logo_url }}" alt="">@else<small class="text-muted">Sin logo</small>@endif</div>
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold mb-1" for="pf-logo-file">Logo para los obituarios</label>
                            <input type="file" class="form-control form-control-sm" name="logo_obi" id="pf-logo-file" accept="image/png,image/webp,image/jpeg">
                            <div class="pf-ayuda mt-1">Mejor un <b>PNG sin fondo</b>. Sirve para todas las plantillas. @if (!$logoObi)Ahora se usa el logo de Datos del negocio.@endif</div>
                            @if ($logoObi)<div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="quitar_logo_obi" value="1" id="pf-qlogo"><label class="form-check-label" for="pf-qlogo">Volver al logo de Datos del negocio</label></div>@endif
                        </div>
                    </div>
                    <div class="pf-aviso" id="pf-logo-aviso" hidden>
                        <b><i class="fa-solid fa-triangle-exclamation me-1"></i>Este logo tiene fondo</b> y se ve como un recuadro encima del obituario. ¿Qué quiere hacer?
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-dark" id="pf-logo-quitar"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Quitar el fondo automáticamente</button>
                            <button type="button" class="btn btn-sm btn-light" id="pf-logo-otro"><i class="fa-solid fa-upload me-1"></i>Subir otro logo sin fondo</button>
                            <button type="button" class="btn btn-sm btn-link" id="pf-logo-dejar">Dejarlo así</button>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="colores_propios" value="1" id="pf-col" @checked(old('colores_propios', $plantilla->color_acento || $plantilla->color_texto))>
                        <label class="form-check-label" for="pf-col">Usar mis colores</label>
                    </div>
                    <div class="d-flex gap-3 mt-2" id="pf-colores">
                        <label class="small fw-bold d-flex align-items-center gap-2">Detalles <input type="color" name="color_acento" value="{{ old('color_acento', $plantilla->acento) }}"></label>
                        <label class="small fw-bold d-flex align-items-center gap-2">Letras <input type="color" name="color_texto" value="{{ old('color_texto', $plantilla->texto_color) }}"></label>
                    </div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="aplicar_todas" value="1" id="pf-todas" checked>
                    <label class="form-check-label small" for="pf-todas"><b>Usar el mismo logo, título y pie en todas mis plantillas</b> (el diseño y los colores de cada una no cambian)</label>
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
                <input type="hidden" name="libre" id="pf-libre-in" form="pf-form">
                <input type="hidden" name="mover" id="pf-mover-in" form="pf-form">
                <div class="pf-libre mt-3" id="pf-mover" hidden>
                    <b><i class="fa-solid fa-up-down-left-right me-1"></i>Mover y agrandar los elementos</b>
                    <div class="pf-ayuda mb-2">Toque en la vista previa el logo, la foto, el nombre, los datos… y <b>arrástrelo</b> a su lugar (queda con un recuadro azul). Con la barra lo agranda o lo achica. Se guarda con la plantilla. El lugar de velación se mueve aparte (recuadro verde).</div>
                    <div class="d-flex gap-2 align-items-center flex-wrap mb-1">
                        <label class="small fw-bold" for="mv-bloque">Elemento</label>
                        <select class="form-select form-select-sm" id="mv-bloque" style="max-width:280px">@foreach ($P::MOVER_BLOQUES as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select>
                    </div>
                    <div class="pf-rango"><label for="mv-s">Tamaño</label><input type="range" class="form-range" id="mv-s" min="30" max="300" step="5" value="100"><output id="mv-s-v">100%</output></div>
                    <div class="d-flex gap-1 flex-wrap mt-1">
                        <button type="button" class="btn btn-sm btn-light" data-mv-mover="0,-1" title="Subir"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-mv-mover="0,1" title="Bajar"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-mv-mover="-1,0" title="Izquierda"><i class="fa-solid fa-arrow-left"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-mv-mover="1,0" title="Derecha"><i class="fa-solid fa-arrow-right"></i></button>
                        <button type="button" class="btn btn-sm btn-link" id="mv-uno">Este a su lugar</button>
                        <button type="button" class="btn btn-sm btn-link ms-auto" id="mv-todo">Todo como al principio</button>
                    </div>
                </div>
                <div class="pf-libre mt-3" id="pf-libre" hidden>
                    <b><i class="fa-solid fa-up-down-left-right me-1"></i>Acomode los textos sobre su imagen</b>
                    <div class="pf-ayuda mb-2">Toque un bloque en la vista previa (la foto, el nombre, los datos…) y <b>arrástrelo</b> a su lugar. Con las barras lo agranda o lo hace más ancho. Se guarda con la plantilla.</div>
                    <div class="d-flex gap-2 align-items-center flex-wrap mb-1">
                        <label class="small fw-bold" for="pl-bloque">Bloque</label>
                        <select class="form-select form-select-sm" id="pl-bloque" style="max-width:280px">@foreach ($P::LIBRE_BLOQUES as $k => $b)<option value="{{ $k }}">{{ $b[0] }}</option>@endforeach</select>
                        <div class="form-check form-switch small mb-0"><input class="form-check-input" type="checkbox" id="pl-ver"><label class="form-check-label" for="pl-ver">Mostrar</label></div>
                    </div>
                    <div class="pf-rango"><label for="pl-s">Tamaño</label><input type="range" class="form-range" id="pl-s" min="20" max="300" step="5"><output id="pl-s-v"></output></div>
                    <div class="pf-rango"><label for="pl-w">Ancho</label><input type="range" class="form-range" id="pl-w" min="5" max="100" step="1"><output id="pl-w-v"></output></div>
                    <div class="d-flex gap-1 flex-wrap mt-1">
                        <button type="button" class="btn btn-sm btn-light" data-pl-mover="0,-1" title="Subir"><i class="fa-solid fa-arrow-up"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-pl-mover="0,1" title="Bajar"><i class="fa-solid fa-arrow-down"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-pl-mover="-1,0" title="Izquierda"><i class="fa-solid fa-arrow-left"></i></button>
                        <button type="button" class="btn btn-sm btn-light" data-pl-mover="1,0" title="Derecha"><i class="fa-solid fa-arrow-right"></i></button>
                        <button type="button" class="btn btn-sm btn-link ms-auto" id="pl-reset">Acomodar como al principio</button>
                    </div>
                </div>
                <div class="pf-ayuda mt-2">@if ($ejemplo)Con los datos del último obituario.@else Con datos de ejemplo.@endif La letra se acomoda sola según cuántos familiares tenga cada obituario.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/obituarios.js') }}?v=15"></script>
<script>
(function () {
    var form = document.getElementById('pf-form'), el = document.querySelector('[data-obi]');
    var MEDIDAS = @json($P::MEDIDAS), BASE = @json($P::BASE), DISENOS = @json($P::DISENOS), TARJETAS = @json($P::TARJETAS);
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
            anim_cant: +f.anim_cant.value, anim_tam: +f.anim_tam.value, anim_vel: +f.anim_vel.value, propios: propios,
            flores: { pos: val('flor_pos'), esq: val('flor_esq'), x: +f.flor_x.value, y: +f.flor_y.value, tam: +f.flor_tam.value },
            acento: propios ? f.color_acento.value : dis[2], texto: propios ? f.color_texto.value : dis[3],
            logo: f.mostrar_logo.checked, logo_fondo: f.logo_fondo.value, titulo: f.titulo.value, frase: '', pie: '', cita: f.cita.value, tarjeta: TARJETAS.indexOf(val('diseno')) !== -1
        });
        // Lugar de velación: dónde sale y cuánto se movió
        ['vel-foto', 'vel-debajo', 'vel-datos'].forEach(function (c) { el.classList.remove(c); });
        el.classList.add('vel-' + (f.velacion_pos.value || 'foto'));
        el.style.setProperty('--vx', (+f.velacion_x.value || 0) + 'px'); el.style.setProperty('--vy', (+f.velacion_y.value || 0) + 'px');
        el.classList.toggle('vel-movido', !!(+f.velacion_x.value || +f.velacion_y.value));
        el.classList.toggle('mueve-vel', val('diseno') !== 'ninguno');
        var modo = f.pie_modo.value, conPie = modo !== 'ninguno';
        var conFrase = modo === 'frase' || modo === 'ambos', conEsl = modo === 'eslogan' || modo === 'ambos';
        document.querySelectorAll('[data-pie-campo]').forEach(function (c) { var k = c.dataset.pieCampo; c.hidden = k === 'nombre' ? !conPie : k === 'frase' ? !conFrase : !conEsl; });
        el.querySelectorAll('[data-fila="pie_bloque"]').forEach(function (n) { n.hidden = !conPie; });
        el.querySelectorAll('[data-o="pie_nombre"]').forEach(function (n) { var t = f.pie_nombre.value.trim() || n.dataset.defecto || ''; n.textContent = t; n.hidden = !conPie || !t; });
        el.querySelectorAll('[data-o="pie"]').forEach(function (n) { var t = conFrase ? f.pie.value.trim() : ''; n.textContent = t; n.hidden = !t; });
        el.querySelectorAll('[data-o="pie_eslogan"]').forEach(function (n) { var t = conEsl ? f.pie_eslogan.value.trim() : ''; n.textContent = t; n.hidden = !t; });
        if (window.pintarLibre) pintarLibre();
        if (window.pintarMover) pintarMover();
    }
    // El degradado apagado no se envía (el campo queda deshabilitado)
    document.getElementById('pf-imagen').addEventListener('change', function () { if (this.files[0]) { imagen = URL.createObjectURL(this.files[0]); aplicar(); } });
    document.getElementById('pf-video').addEventListener('change', function () {
        var file = this.files[0]; if (!file) return;
        if (file.size > 50 * 1024 * 1024) { LP.toast('El video pesa más de 50 MB. Use uno más corto o comprímalo.', 'error'); this.value = ''; return; }
        video = URL.createObjectURL(file); aplicar();
    });
    function numeros() {
        document.querySelectorAll('output[data-de]').forEach(function (o) {
            var v = document.getElementById(o.dataset.de).value;
            o.textContent = v + (o.dataset.suf || 'px');
        });
    }
    document.getElementById('pf-flor-reset').addEventListener('click', function () {
        form.elements.flor_x.value = 0; form.elements.flor_y.value = 0; form.elements.flor_tam.value = 100; numeros(); aplicar();
    });
    document.querySelectorAll('[data-cant]').forEach(function (b) {
        b.addEventListener('click', function () {
            form.elements.anim_cant.value = b.dataset.cant;
            if (val('animacion') === 'ninguna') { var p = document.getElementById('a-petalos'); if (p) p.checked = true; }
            numeros(); aplicar();
        });
    });
    // ===== Logo de los obituarios =====
    var lf = document.getElementById('pf-logo-file'), aviso = document.getElementById('pf-logo-aviso'), archivoLogo = null;
    function ponerLogo(fileOBlob) {
        var url = URL.createObjectURL(fileOBlob);
        document.getElementById('pf-logo-prev').innerHTML = '<img src="' + url + '" alt="">';
        Obi.logo(el, url, form.elements.mostrar_logo.checked);
    }
    function tieneFondo(file) {
        return Obi.tieneTransparencia(URL.createObjectURL(file)).then(function (t) { return !t; });
    }
    lf.addEventListener('change', function () {
        var file = lf.files[0]; if (!file) return;
        archivoLogo = file; ponerLogo(file);
        tieneFondo(file).then(function (con) { aviso.hidden = !con; });
    });
    document.getElementById('pf-logo-otro').addEventListener('click', function () { aviso.hidden = true; lf.value = ''; lf.click(); });
    document.getElementById('pf-logo-dejar').addEventListener('click', function () { aviso.hidden = true; });
    document.getElementById('pf-logo-quitar').addEventListener('click', function () {
        var b = this, img = document.querySelector('#pf-logo-prev img');
        if (!archivoLogo && !img) return;
        b.disabled = true;
        (archivoLogo ? Promise.resolve(archivoLogo) : fetch(img.src).then(function (r) { return r.blob(); }))
        .then(function (f) { return Obi.quitarFondo(f); }).then(function (blob) {
            var nuevo = new File([blob], 'logo-sin-fondo.png', { type: 'image/png' });
            var dt = new DataTransfer(); dt.items.add(nuevo); lf.files = dt.files;
            archivoLogo = nuevo; ponerLogo(nuevo); aviso.hidden = true;
        }).catch(function () { LP.toast('No se pudo quitar el fondo. Suba un PNG sin fondo.', 'error'); }).finally(function () { b.disabled = false; });
    });
    // El logo que ya tiene: si trae fondo, se avisa
    var actual = document.querySelector('#pf-logo-prev img');
    if (actual) Obi.tieneTransparencia(actual.src).then(function (t) { aviso.hidden = t; });
    form.addEventListener('input', numeros);
    numeros();
    document.getElementById('pf-v0').addEventListener('click', function () { form.elements.velacion_x.value = 0; form.elements.velacion_y.value = 0; form.elements.velacion_x.dispatchEvent(new Event('input', { bubbles: true })); form.elements.velacion_y.dispatchEvent(new Event('input', { bubbles: true })); });
    // "Ninguna": la imagen de la funeraria sola, sin mezclarse con un diseño (pide subir la imagen y no la oscurece)
    form.querySelectorAll('[name=diseno]').forEach(function (r) {
        r.addEventListener('change', function () {
            if (r.value !== 'ninguno' || !r.checked) return;
            var fd = form.querySelector('[name=fondo_tipo]:checked');
            if (!fd || fd.value === 'diseno') { var fi = document.getElementById('f-imagen'); if (fi) fi.checked = true; }
            form.elements.oscurecer.value = 0;
            var ad = form.querySelector('[name=adornos][value=ninguno]'); if (ad) ad.checked = true;
            aplicar();
        });
    });
    // ===== "Ninguna": acomodar a mano cada bloque sobre la imagen =====
    var LIB = @json(['h' => $plantilla->libreDatos('h'), 'v' => $plantilla->libreDatos('v'), 'c' => $plantilla->libreDatos('c')]);
    var FAB = @json($P::libreFabrica());
    var plSel = document.getElementById('pl-bloque'), plVer = document.getElementById('pl-ver'), plS = document.getElementById('pl-s'), plW = document.getElementById('pl-w');
    function orient() { return (MEDIDAS[val('medida')] || MEDIDAS.fhd)[3]; }
    function esLibre() { return val('diseno') === 'ninguno'; }
    window.pintarLibre = function () {
        var on = esLibre(), o = orient();
        document.getElementById('pf-libre').hidden = !on;
        el.classList.toggle('editando', on);
        el.style.touchAction = on ? 'none' : '';
        Object.keys(LIB[o]).forEach(function (k) {
            var b = LIB[o][k];
            el.querySelectorAll('[data-bloque="' + k + '"]').forEach(function (n) {
                n.style.setProperty('--l', b.x + '%'); n.style.setProperty('--t', b.y + '%');
                n.style.setProperty('--w', b.w + '%'); n.style.setProperty('--s', (b.s / 100).toString());
                if (b.v) n.removeAttribute('data-oculto'); else n.setAttribute('data-oculto', '');
                n.classList.toggle('elegido', on && k === plSel.value);
            });
        });
        var b = LIB[o][plSel.value];
        plVer.checked = !!b.v; plS.value = b.s; plW.value = b.w;
        document.getElementById('pl-s-v').textContent = b.s + '%'; document.getElementById('pl-w-v').textContent = Math.round(b.w) + '%';
        document.getElementById('pf-libre-in').value = JSON.stringify(LIB);
    };
    plSel.addEventListener('change', pintarLibre);
    plVer.addEventListener('change', function () { LIB[orient()][plSel.value].v = plVer.checked; pintarLibre(); });
    plS.addEventListener('input', function () { LIB[orient()][plSel.value].s = +plS.value; pintarLibre(); });
    plW.addEventListener('input', function () { LIB[orient()][plSel.value].w = +plW.value; pintarLibre(); });
    document.querySelectorAll('[data-pl-mover]').forEach(function (bt) {
        bt.addEventListener('click', function () {
            var d = bt.dataset.plMover.split(','), b = LIB[orient()][plSel.value];
            b.x = Math.max(-20, Math.min(120, b.x + (+d[0]) * 0.5)); b.y = Math.max(-20, Math.min(120, b.y + (+d[1]) * 0.5)); pintarLibre();
        });
    });
    document.getElementById('pl-reset').addEventListener('click', function () { LIB[orient()] = JSON.parse(JSON.stringify(FAB[orient()])); pintarLibre(); });
    // Lugar de velación: arrastrarlo en la vista previa en cualquier diseño (mueve las barras ← → y ↑ ↓)
    el.addEventListener('pointerdown', function (e) {
        if (esLibre()) return;
        var n = e.target.closest('.vp-foto, .vp-debajo, .vp-datos'); if (!n || !el.contains(n)) return;
        e.preventDefault();
        var f = form.elements, esc = el.getBoundingClientRect().width / el.offsetWidth || 1;
        var x0 = e.clientX, y0 = e.clientY, vx = +f.velacion_x.value || 0, vy = +f.velacion_y.value || 0;
        el.classList.add('vel-arrastrando');
        function poner(campo, v) { campo.value = Math.max(-400, Math.min(400, Math.round(v / 5) * 5)); campo.dispatchEvent(new Event('input', { bubbles: true })); }
        function mover(ev) { poner(f.velacion_x, vx + (ev.clientX - x0) / esc); poner(f.velacion_y, vy + (ev.clientY - y0) / esc); }
        function soltar() { el.classList.remove('vel-arrastrando'); window.removeEventListener('pointermove', mover); window.removeEventListener('pointerup', soltar); window.removeEventListener('pointercancel', soltar); }
        window.addEventListener('pointermove', mover); window.addEventListener('pointerup', soltar); window.addEventListener('pointercancel', soltar);
        e.stopImmediatePropagation();
    });
    // ===== Todos los diseños (menos "Ninguna"): mover y agrandar el logo, la foto, el nombre, los datos… =====
    var MOV = @json(collect(['h', 'v', 'c'])->mapWithKeys(fn ($o) => [$o => (object) $plantilla->moverDatos($o)]));
    var mvSel = document.getElementById('mv-bloque'), mvS = document.getElementById('mv-s');
    function mvBloque(k) { var o = orient(); if (!MOV[o] || Array.isArray(MOV[o])) MOV[o] = {}; return MOV[o][k] || (MOV[o][k] = { x: 0, y: 0, s: 100 }); }
    window.pintarMover = function () {
        var on = !esLibre(), o = orient(), m = MOV[o] || {};
        document.getElementById('pf-mover').hidden = !on;
        el.classList.toggle('mueve-todo', on);
        // Solo los elementos que tiene este diseño
        var hay = {};
        el.querySelectorAll('[data-mover]').forEach(function (n) {
            var b = m[n.dataset.mover];
            n.style.setProperty('--mx', (b ? b.x : 0) + 'px'); n.style.setProperty('--my', (b ? b.y : 0) + 'px'); n.style.setProperty('--ms', ((b ? b.s : 100) / 100).toString());
            n.classList.toggle('mv-elegido', on && n.dataset.mover === mvSel.value);
            if (n.getClientRects().length) hay[n.dataset.mover] = true;
        });
        [].forEach.call(mvSel.options, function (op) { op.hidden = !hay[op.value]; });
        if (!hay[mvSel.value]) { var p = [].find.call(mvSel.options, function (op) { return hay[op.value]; }); if (p) { mvSel.value = p.value; return pintarMover(); } }
        var s = (m[mvSel.value] || {}).s || 100;
        mvS.value = s; document.getElementById('mv-s-v').textContent = s + '%';
        document.getElementById('pf-mover-in').value = JSON.stringify(MOV);
    };
    mvSel.addEventListener('change', pintarMover);
    mvS.addEventListener('input', function () { mvBloque(mvSel.value).s = +mvS.value; pintarMover(); });
    document.querySelectorAll('[data-mv-mover]').forEach(function (bt) {
        bt.addEventListener('click', function () { var d = bt.dataset.mvMover.split(','), b = mvBloque(mvSel.value); b.x = Math.max(-1500, Math.min(1500, b.x + (+d[0]) * 10)); b.y = Math.max(-1500, Math.min(1500, b.y + (+d[1]) * 10)); pintarMover(); });
    });
    document.getElementById('mv-uno').addEventListener('click', function () { var o = orient(); if (MOV[o]) delete MOV[o][mvSel.value]; pintarMover(); });
    document.getElementById('mv-todo').addEventListener('click', function () { MOV[orient()] = {}; pintarMover(); });
    el.addEventListener('pointerdown', function (e) {
        if (esLibre()) return;
        var n = e.target.closest('[data-mover]'); if (!n || !el.contains(n)) return;
        e.preventDefault();
        mvSel.value = n.dataset.mover; pintarMover();
        var b = mvBloque(n.dataset.mover), esc = el.getBoundingClientRect().width / el.offsetWidth || 1, x0 = e.clientX, y0 = e.clientY, bx = b.x, by = b.y;
        function mover(ev) { b.x = Math.max(-1500, Math.min(1500, Math.round(bx + (ev.clientX - x0) / esc))); b.y = Math.max(-1500, Math.min(1500, Math.round(by + (ev.clientY - y0) / esc))); pintarMover(); }
        function soltar() { window.removeEventListener('pointermove', mover); window.removeEventListener('pointerup', soltar); window.removeEventListener('pointercancel', soltar); }
        window.addEventListener('pointermove', mover); window.addEventListener('pointerup', soltar); window.addEventListener('pointercancel', soltar);
    });
    // Arrastrar en la vista previa (con el dedo o el mouse)
    el.addEventListener('pointerdown', function (e) {
        if (!esLibre()) return;
        var n = e.target.closest('[data-bloque]'); if (!n || !el.contains(n)) return;
        e.preventDefault();
        plSel.value = n.dataset.bloque; pintarLibre();
        var o = orient(), b = LIB[o][plSel.value], r = el.getBoundingClientRect(), x0 = e.clientX, y0 = e.clientY, bx = b.x, by = b.y;
        function mover(ev) {
            b.x = Math.max(-20, Math.min(120, Math.round((bx + (ev.clientX - x0) / r.width * 100) * 10) / 10));
            b.y = Math.max(-20, Math.min(120, Math.round((by + (ev.clientY - y0) / r.height * 100) * 10) / 10));
            pintarLibre();
        }
        function soltar() { window.removeEventListener('pointermove', mover); window.removeEventListener('pointerup', soltar); window.removeEventListener('pointercancel', soltar); }
        window.addEventListener('pointermove', mover); window.addEventListener('pointerup', soltar); window.addEventListener('pointercancel', soltar);
    });

    form.addEventListener('input', aplicar);
    form.addEventListener('change', aplicar);
    aplicar();
})();
</script>
@endpush
