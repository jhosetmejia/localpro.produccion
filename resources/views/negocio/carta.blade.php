@extends('layouts.app')

@section('title', 'Mi carta')

@push('css')
<style>
.mc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
@media (max-width:900px){.mc-grid{grid-template-columns:minmax(0,1fr)}}
.mc-pl{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;overflow:hidden;display:flex;flex-direction:column}
.mc-mock{height:210px;padding:18px;display:flex;flex-direction:column;gap:8px;position:relative;overflow:hidden}
.mc-mock .t{font-size:1.25rem;font-weight:900;text-align:center;line-height:1.1}
.mc-mock .s{font-size:.8rem;text-align:center;opacity:.8}
.mc-mock .r{display:flex;justify-content:space-between;gap:8px;font-size:.82rem;font-weight:700}
.mc-mock .r i{flex:1;border-bottom:2px dotted currentColor;opacity:.4;transform:translateY(-4px)}
.mc-clasica{background:#f6efe2;color:#2a1f17;font-family:'Playfair Display',Georgia,serif;outline:2px solid #b08d57;outline-offset:-10px}
.mc-pizarra{background:radial-gradient(120% 90% at 50% 40%,#3a4441,#1d2321);color:#f3f1e7;border:12px solid #6b4a2b;font-family:'Caveat',cursive}
.mc-pizarra .r span:last-child{color:#f6d365}
.mc-moderna{background:#fff;color:#141414}
.mc-moderna .t{background:#ff5a1f;color:#fff;margin:-18px -18px 6px;padding:18px;text-align:left}
.mc-moderna .card{display:flex;gap:8px;align-items:center;background:#f6f3ef;border-radius:12px;padding:6px;font-size:.78rem;font-weight:700}
.mc-moderna .card b{width:34px;height:34px;border-radius:9px;background:#ffe2d4;flex:none}
.mc-moderna .card em{margin-left:auto;font-style:normal;background:#141414;color:#fff;border-radius:99px;padding:2px 8px}
.mc-b{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex:1}
.mc-b small{color:#5b665e}
.mc-acc{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-top:auto}
.mc-acc .btn{border-radius:12px;font-weight:800;font-size:.85rem}
</style>
@endpush

@section('content')
@php
    $planes = \App\Http\Controllers\catalogoController::PLANTILLAS_CARTA;
    $base = $tienda->slug ? route('catalogo.carta.imprimir', $tienda->slug) : null;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-utensils me-1"></i>Restaurante</small><h1>Mi <span>carta</span></h1>
            <span class="text-muted fw-semibold">Su carta en línea para el QR de las mesas y su carta para imprimir o compartir en redes, en PDF y JPG. Se arma sola con sus productos y precios.</span></div>
        @if ($listo)<div class="lp-quick"><a href="{{ $tienda->url_carta_navegador }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg"><i class="fa-solid fa-eye me-1"></i>Ver mi carta</a></div>@endif
    </div>
    @include('layouts.partials.lp-flash')

    @unless ($listo)
        <div class="alert border-0 fw-semibold" style="background:#fff5d6;color:#8a6400">
            Para ver y descargar su carta, active su tienda en línea en <a href="{{ route('negocio.edit') }}" style="color:inherit;text-decoration:underline">Datos del negocio</a> ("Tienda en línea visible al público").
        </div>
    @endunless
    @if ($listo && !$hayProductos)
        <div class="alert border-0 fw-semibold" style="background:#fff5d6;color:#8a6400">
            Su carta está vacía. Cree sus platos en <a href="{{ route('productos.create') }}" style="color:inherit;text-decoration:underline">Productos</a> con nombre, precio, categoría (Entradas, Platos fuertes, Bebidas…) y una foto.
        </div>
    @endif

    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-qrcode me-1"></i>Carta en línea (para el QR de las mesas)</span></h3>
        <p class="text-muted fw-semibold mb-2">Sus clientes la abren en el celular y le piden por WhatsApp: a domicilio, para recoger o en la mesa. El diseño se cambia en <a href="{{ route('negocio.edit') }}">Datos del negocio</a>.</p>
        <div class="d-flex gap-2 flex-wrap">
            <input class="form-control" style="flex:1 1 240px" value="{{ $tienda->url_carta }}" readonly onclick="this.select()">
            @if ($listo)<a class="btn btn-dark" target="_blank" rel="noopener" href="{{ $tienda->url_carta_navegador }}"><i class="fa-solid fa-utensils me-1"></i>Ver mi carta</a>@endif
            <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('🍽️ Mira nuestra carta y pide por WhatsApp: ' . $tienda->url_carta) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
    </div>

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-print me-1"></i>Carta para imprimir y para redes</span></h3>
        <p class="text-muted fw-semibold mb-3">Elija una plantilla. Cada hoja trae un QR que lleva a su carta en línea. Si tiene muchos platos, sale en varias hojas.</p>
        <div class="mc-grid">
            @foreach ($planes as $k => [$nom, $txt])
            <div class="mc-pl">
                <div class="mc-mock mc-{{ $k }}" aria-hidden="true">
                    @if ($k === 'moderna')
                        <div class="t">{{ \Illuminate\Support\Str::limit($tienda->nombre, 22) }}</div>
                        <div class="card"><b></b>Plato del día<em>$ 18.000</em></div>
                        <div class="card"><b></b>Especial de la casa<em>$ 25.000</em></div>
                        <div class="card"><b></b>Bebida natural<em>$ 6.000</em></div>
                    @else
                        <div class="t">{{ $k === 'clasica' ? mb_strtoupper(\Illuminate\Support\Str::limit($tienda->nombre, 22)) : \Illuminate\Support\Str::limit($tienda->nombre, 22) }}</div>
                        <div class="s">{{ $k === 'clasica' ? '✦ ✦ ✦' : '~ Nuestra carta ~' }}</div>
                        <div class="r"><span>Plato del día</span>@if ($k === 'clasica')<i></i>@endif<span>$ 18.000</span></div>
                        <div class="r"><span>Especial de la casa</span>@if ($k === 'clasica')<i></i>@endif<span>$ 25.000</span></div>
                        <div class="r"><span>Bebida natural</span>@if ($k === 'clasica')<i></i>@endif<span>$ 6.000</span></div>
                    @endif
                </div>
                <div class="mc-b">
                    <b>{{ $nom }}</b><small>{{ $txt }}</small>
                    @if ($listo && $base)
                    <div class="mc-acc">
                        <a class="btn btn-light" target="_blank" rel="noopener" href="{{ $base }}?plantilla={{ $k }}"><i class="fa-solid fa-eye"></i> Ver</a>
                        <a class="btn btn-danger" target="_blank" rel="noopener" href="{{ $base }}?plantilla={{ $k }}&accion=pdf"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                        <a class="btn btn-success" target="_blank" rel="noopener" href="{{ $base }}?plantilla={{ $k }}&accion=jpg"><i class="fa-solid fa-image"></i> JPG</a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        <small class="text-muted d-block mt-3">Consejo: el PDF sirve para imprimir en papel; el JPG para el estado de WhatsApp, Instagram o Facebook. Cuando cambie un precio, vuelva a descargarla: siempre sale con sus precios de hoy.</small>
    </div>
</div>
@endsection
