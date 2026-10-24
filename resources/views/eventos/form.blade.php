@extends('layouts.app')

@section('title', $evento->exists ? 'Editar evento' : 'Nuevo evento')

@push('css')
<style>
.ev-prev{width:100%;aspect-ratio:9/16;max-height:340px;border-radius:16px;border:2px dashed var(--lp-line);display:grid;place-items:center;overflow:hidden;background:#faf8fd;color:#b9a6d8}
.ev-prev img{width:100%;height:100%;object-fit:contain;background:#111}
.ev-url{display:flex;align-items:center;border:1.5px solid var(--lp-line);border-radius:12px;overflow:hidden}
.ev-url span{background:#f4f6f5;padding:.55rem .7rem;color:#6b7570;font-size:.9rem;white-space:nowrap}
.ev-url input{border:0;flex:1;min-width:0;padding:.55rem .7rem;outline:none}
</style>
@endpush

@section('content')
@php $f = fn ($d) => $d ? $d->format('Y-m-d\TH:i') : ''; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>LocalProo Eventos</small><h1>{{ $evento->exists ? 'Editar' : 'Nuevo' }} <span>evento</span></h1></div>
        <div class="lp-quick"><a href="{{ $evento->exists ? route('eventos.show', $evento) : route('eventos.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ $evento->exists ? route('eventos.update', $evento) : route('eventos.store') }}" enctype="multipart/form-data">
        @csrf @if ($evento->exists) @method('put') @endif
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="lp-box mb-3">
                    <h3><i class="fa-solid fa-star me-1"></i>El evento</h3>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label fw-bold">Nombre del evento</label>
                            <input class="form-control" name="nombre" value="{{ old('nombre', $evento->nombre) }}" maxlength="120" required placeholder="Ej: El Último Grito"></div>
                        <div class="col-md-5"><label class="form-label fw-bold">Frase o edición <small class="text-muted fw-normal">(opcional)</small></label>
                            <input class="form-control" name="subtitulo" value="{{ old('subtitulo', $evento->subtitulo) }}" maxlength="120" placeholder="Ej: El despertar"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Fecha y hora</label>
                            <input class="form-control" type="datetime-local" name="fecha_evento" value="{{ old('fecha_evento', $f($evento->fecha_evento)) }}" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Ciudad</label>
                            <input class="form-control" name="ciudad" value="{{ old('ciudad', $evento->ciudad) }}" maxlength="80"></div>
                        <div class="col-12"><label class="form-label fw-bold">Lugar <small class="text-muted fw-normal">(si aún es sorpresa, déjelo vacío)</small></label>
                            <input class="form-control" name="lugar" value="{{ old('lugar', $evento->lugar) }}" maxlength="150" placeholder="Ej: Discoteca X, Calle 18 #25-30"></div>
                        <div class="col-12"><label class="form-label fw-bold">Descripción / publicidad</label>
                            <textarea class="form-control" name="descripcion" rows="5" maxlength="5000" placeholder="¡Ya está a la venta la boletería! Asegura tu entrada y prepárate para vivir una noche inolvidable.">{{ old('descripcion', $evento->descripcion) }}</textarea></div>
                        <div class="col-12"><label class="form-label fw-bold">Dirección de la página</label>
                            <div class="ev-url"><span>localproo.com/evento/</span><input name="slug" value="{{ old('slug', $evento->slug) }}" maxlength="80" placeholder="se arma sola con el nombre" pattern="[a-z0-9]+(-[a-z0-9]+)*"></div></div>
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3><i class="fa-solid fa-ticket me-1"></i>Entrada y venta</h3>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label fw-bold">Etapa</label>
                            <input class="form-control" name="etapa" value="{{ old('etapa', $evento->etapa) }}" maxlength="60" placeholder="Primera etapa"></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Precio</label>
                            <input class="form-control" name="precio" data-dinero value="{{ old('precio', (float) $evento->precio ?: '') }}" required></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Cupo <small class="text-muted fw-normal">(vacío = sin límite)</small></label>
                            <input class="form-control" type="number" name="cupo" min="1" value="{{ old('cupo', $evento->cupo) }}"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Venta de esta etapa hasta</label>
                            <input class="form-control" type="datetime-local" name="venta_hasta" value="{{ old('venta_hasta', $f($evento->venta_hasta)) }}">
                            <small class="text-muted">Ej: 26 de octubre 11:59 p. m. Para la siguiente etapa cambie el nombre, el precio y esta fecha: las entradas ya registradas conservan su precio.</small></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Edad mínima <small class="text-muted fw-normal">(opcional)</small></label>
                            <input class="form-control" type="number" name="edad_minima" min="1" max="99" value="{{ old('edad_minima', $evento->edad_minima) }}" placeholder="Sin límite"></div>
                        <div class="col-12">
                            <input type="hidden" name="pedir_colegio" value="0">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pedir_colegio" value="1" id="pcol" @checked(old('pedir_colegio', $evento->pedir_colegio))><label class="form-check-label fw-bold" for="pcol">Pedir el colegio al registrarse</label></div>
                        </div>
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3><i class="fa-solid fa-money-bill-transfer me-1"></i>Pago y comprobante</h3>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label fw-bold">Medio de pago</label>
                            <input class="form-control" name="pago_metodo" value="{{ old('pago_metodo', $evento->pago_metodo) }}" maxlength="40" list="ev-medios" required>
                            <datalist id="ev-medios"><option value="Nequi"><option value="Daviplata"><option value="Bancolombia"><option value="Llave Bre-B"></datalist></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Número / cuenta</label>
                            <input class="form-control" name="pago_numero" value="{{ old('pago_numero', $evento->pago_numero) }}" maxlength="40" required placeholder="321 898 1691"></div>
                        <div class="col-md-4"><label class="form-label fw-bold">A nombre de <small class="text-muted fw-normal">(opcional)</small></label>
                            <input class="form-control" name="pago_titular" value="{{ old('pago_titular', $evento->pago_titular) }}" maxlength="80"></div>
                        <div class="col-md-5"><label class="form-label fw-bold">WhatsApp para el comprobante</label>
                            <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $evento->whatsapp) }}" maxlength="30" inputmode="tel" required placeholder="3218981691"></div>
                        <div class="col-md-7"><label class="form-label fw-bold">Mensaje que se escribe solo</label>
                            <input class="form-control" name="mensaje_whatsapp" value="{{ old('mensaje_whatsapp', $evento->mensaje_whatsapp ?: \App\Models\Evento::MENSAJE) }}" maxlength="255">
                            <small class="text-muted">Al final se agregan solos el nombre, el documento y el número de registro de la persona.</small></div>
                    </div>
                </div>

                <div class="lp-box mb-3">
                    <h3><i class="fa-solid fa-scale-balanced me-1"></i>Condiciones y redes</h3>
                    <div class="row g-3">
                        <div class="col-12"><textarea class="form-control" name="condiciones" rows="4" maxlength="3000">{{ old('condiciones', $evento->condiciones) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><i class="fa-brands fa-instagram me-1"></i>Instagram</label>
                            <input class="form-control" name="instagram" value="{{ old('instagram', $evento->instagram) }}" maxlength="80" placeholder="ultimo__grito"></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><i class="fa-brands fa-tiktok me-1"></i>TikTok</label>
                            <input class="form-control" name="tiktok" value="{{ old('tiktok', $evento->tiktok) }}" maxlength="80" placeholder="ultimo__grito"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="lp-box mb-3">
                    <h3>Publicación</h3>
                    <select class="form-select" name="estado" data-nativo>
                        @foreach (\App\Models\Evento::ESTADOS as $k => $n)<option value="{{ $k }}" @selected(old('estado', $evento->estado) === $k)>{{ $n }}</option>@endforeach
                    </select>
                    <small class="text-muted d-block mt-2">En borrador solo usted ve la página (vista previa).</small>
                    <label class="form-label fw-bold mt-3">Estilo de la página</label>
                    <select class="form-select" name="tema" data-nativo>
                        @foreach (\App\Models\Evento::TEMAS as $k => $n)<option value="{{ $k }}" @selected(old('tema', $evento->tema ?: 'normal') === $k)>{{ $n }}</option>@endforeach
                    </select>
                    <small class="text-muted d-block mt-1">Terror: fondo oscuro con sangre que escurre, niebla y letras de miedo. Ideal para Halloween.</small>
                    <label class="form-label fw-bold mt-3">Color del evento</label>
                    <input class="form-control form-control-color w-100" type="color" name="color" value="{{ old('color', $evento->color ?: '#a855f7') }}">
                </div>
                <div class="lp-box mb-3">
                    <h3>Logo del evento <small class="text-muted fw-normal">(opcional)</small></h3>
                    <div class="mb-2 rounded-3 d-grid" style="background:#0b0a10;min-height:120px;place-items:center;padding:10px">@if ($evento->logo_url)<img id="pv-logo" src="{{ $evento->logo_url }}" alt="" style="max-width:100%;max-height:150px">@else<img id="pv-logo" alt="" style="display:none;max-width:100%;max-height:150px"><i class="fa-regular fa-image fa-2x text-secondary" id="ic-logo"></i>@endif</div>
                    <input type="file" class="form-control" name="logo" accept="image/png,image/jpeg,image/webp" data-prev="logo">
                    <small class="text-muted d-block mt-1">Sale arriba en la página del evento. Mejor en PNG con fondo transparente.</small>
                    @if ($evento->logo_url)<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="quitar_logo" value="1" id="q-logo"><label class="form-check-label small" for="q-logo">Quitar logo</label></div>@endif
                </div>
                @foreach (['imagen' => ['Afiche principal', 'imagen_url'], 'imagen2' => ['Afiche con la información (opcional)', 'imagen2_url']] as $campo => [$tit, $url])
                <div class="lp-box mb-3">
                    <h3>{{ $tit }}</h3>
                    <div class="ev-prev mb-2">@if ($evento->$url)<img id="pv-{{ $campo }}" src="{{ $evento->$url }}" alt="">@else<img id="pv-{{ $campo }}" alt="" style="display:none"><i class="fa-regular fa-image fa-3x" id="ic-{{ $campo }}"></i>@endif</div>
                    <input type="file" class="form-control" name="{{ $campo }}" accept="image/jpeg,image/png,image/webp" data-prev="{{ $campo }}">
                    <small class="text-muted d-block mt-1">Vertical (1080×1920) se ve mejor en celular. JPG o PNG, máx. 8 MB.</small>
                    @if ($evento->$url)<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="quitar_{{ $campo }}" value="1" id="q-{{ $campo }}"><label class="form-check-label small" for="q-{{ $campo }}">Quitar imagen</label></div>@endif
                </div>
                @endforeach
                <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar evento</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
document.querySelectorAll('[data-prev]').forEach(function (i) {
    i.addEventListener('change', function () {
        var f = i.files[0]; if (!f) return;
        var p = document.getElementById('pv-' + i.dataset.prev), ic = document.getElementById('ic-' + i.dataset.prev);
        p.src = URL.createObjectURL(f); p.style.display = ''; if (ic) ic.style.display = 'none';
    });
});
</script>
@endpush
