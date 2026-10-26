@extends('layouts.app')

@section('title', 'Cotización ' . $c->numero)

@push('css')
<style>
.ct-acc{display:flex;gap:.45rem;flex-wrap:wrap;align-items:center}
.ct-acc .btn{border-radius:14px;font-weight:700}
.ct-acc form{display:inline}
.ct-wrap{background:#eef1ef;border-radius:24px;padding:22px 12px}
@media print{
  body *{visibility:hidden!important}
  #ctHoja,#ctHoja *{visibility:visible!important}
  #ctHoja{position:absolute;left:0;top:0;width:100%}
  .ct-wrap{background:none;padding:0}
}
</style>
@endpush

@section('content')
@php
    $wa = $c->cliente_telefono ? numero_whatsapp($c->cliente_telefono) : null;
    $waUrl = 'https://wa.me/' . ($wa ?: '') . '?text=' . rawurlencode($whatsapp);
    [$etq, $col, $fondo] = $c->vencida ? ['Vencida', '#6b7570', '#eef1ef'] : (\App\Models\Cotizacion::ESTADOS[$c->estado] ?? \App\Models\Cotizacion::ESTADOS['pendiente']);
    $alq = $tienda->mod_alquiler && \Illuminate\Support\Facades\Route::has('alquileres.create');
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-file-signature me-1"></i>Cotizaciones</small>
            <h1>Cotización <span>{{ $c->numero }}</span></h1>
            <span class="fw-bold" style="color:{{ $col }};background:{{ $fondo }};border-radius:999px;padding:.2rem .7rem;font-size:.85rem">{{ $etq }}</span>
            <span class="text-muted fw-semibold ms-1">{{ $c->cliente_nombre }} · {{ dinero($c->total) }}</span></div>
        <div class="lp-quick"><a href="{{ route('cotizaciones.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Cotizaciones</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    @if (session('aceptarIgual'))
    <div class="lp-box mb-3" style="border-color:#f3b4b4;background:#fff6f6">
        <b><i class="fa-solid fa-triangle-exclamation me-1" style="color:#9b1c1c"></i>No le alcanza para esas fechas:</b> {{ session('aceptarIgual') }}.
        <div class="mt-2 d-flex gap-2 flex-wrap">
            <form method="post" action="{{ route('cotizaciones.estado', $c) }}">@csrf<input type="hidden" name="estado" value="aceptada"><input type="hidden" name="confirmado" value="1"><button class="btn btn-danger btn-sm">Aceptarla igual</button></form>
            <a href="{{ route('cotizaciones.edit', $c) }}" class="btn btn-light btn-sm">Cambiar cantidades</a>
        </div>
    </div>
    @endif

    <div class="lp-box mb-3">
        <div class="ct-acc">
            <button type="button" class="btn btn-dark" onclick="window.print()"><i class="fa-solid fa-file-pdf me-1"></i>Descargar PDF / Imprimir</button>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-success"><i class="fa-brands fa-whatsapp me-1"></i>Enviar por WhatsApp</a>
            <button type="button" class="btn btn-light" id="copiar" data-url="{{ $c->url_publica }}"><i class="fa-solid fa-link me-1"></i>Copiar enlace</button>
            <a href="{{ route('cotizaciones.edit', $c) }}" class="btn btn-light"><i class="fa-solid fa-pen me-1"></i>Editar</a>
            <a href="{{ route('cotizaciones.create', ['copiar' => $c->id]) }}" class="btn btn-light"><i class="fa-solid fa-copy me-1"></i>Duplicar</a>
        </div>
        <div class="ct-acc mt-2 pt-2" style="border-top:1px dashed #e3ebe6">
            <span class="fw-bold text-muted me-1" style="font-size:.85rem">¿Qué dijo el cliente?</span>
            @if ($c->estado !== 'aceptada')
            <form method="post" action="{{ route('cotizaciones.estado', $c) }}">@csrf<input type="hidden" name="estado" value="aceptada"><button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-check me-1"></i>Aceptó</button></form>
            @endif
            @if ($c->estado !== 'rechazada')
            <form method="post" action="{{ route('cotizaciones.estado', $c) }}">@csrf<input type="hidden" name="estado" value="rechazada"><button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-xmark me-1"></i>No aceptó</button></form>
            @endif
            @if ($c->estado !== 'pendiente')
            <form method="post" action="{{ route('cotizaciones.estado', $c) }}">@csrf<input type="hidden" name="estado" value="pendiente"><button class="btn btn-sm btn-light"><i class="fa-solid fa-rotate-left me-1"></i>Volver a pendiente</button></form>
            @endif
            @if ($alq)
            <a href="{{ route('cotizaciones.alquiler', $c) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-calendar-days me-1"></i>{{ $c->alquiler_id ? 'Ver el alquiler' : 'Pasar a alquiler' }}</a>
            @endif
            <form method="post" action="{{ route('cotizaciones.destroy', $c) }}" class="ms-auto" data-confirm="¿Eliminar la cotización {{ $c->numero }}? No se puede deshacer." data-confirm-ok="Sí, eliminar" data-peligro>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
        </div>
        @if ($c->evento_fecha && $c->estado !== 'aceptada')
        <small class="text-muted d-block mt-2"><i class="fa-solid fa-circle-info me-1"></i>Mientras no la marque como <b>Aceptó</b>, lo cotizado no queda apartado para el {{ $c->evento_texto }}.</small>
        @endif
    </div>

    <div class="ct-wrap">@include('cotizacion._hoja')</div>
</div>
@endsection

@push('js')
<script>
document.getElementById('copiar').addEventListener('click', function () {
    var b = this, u = b.dataset.url;
    (navigator.clipboard ? navigator.clipboard.writeText(u) : Promise.reject()).then(function () {
        b.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enlace copiado';
    }).catch(function () { b.outerHTML = '<input class="form-control" style="max-width:320px" readonly value="' + u.replace(/"/g, '&quot;') + '" onclick="this.select()">'; });
});
</script>
@endpush
