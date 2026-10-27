@extends('layouts.app')

@section('title', 'Datos del negocio')

@section('content')
<div class="lp-dash">
    <div class="lp-hello"><div><small>Configuración</small><h1>Datos del <span>negocio</span></h1><span class="text-muted fw-semibold">Aparecen en el tiquete, la factura y el mensaje de WhatsApp.</span></div></div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('negocio.update') }}" enctype="multipart/form-data" class="ng-grid">
        @csrf @method('PUT')
        <div class="lp-box">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">Nombre del negocio</label><input name="nombre" class="form-control" value="{{ old('nombre', $negocio->nombre) }}" required maxlength="80"></div>
                <div class="col-md-4"><label class="form-label">NIT</label><input name="nit" class="form-control" value="{{ old('nit', $negocio->nit) }}" maxlength="30"></div>
                <div class="col-md-12"><label class="form-label">Tipo de negocio</label>
                    <select name="tipo_negocio" class="form-select">
                        @foreach ($tipos as $k => $t)<option value="{{ $k }}" @selected(old('tipo_negocio', $negocio->tipo_negocio) === $k)>{{ $t[0] }}{{ $t[2] ? ' (tallas y colores)' : '' }}</option>@endforeach
                    </select>
                </div>
                @include('layouts.partials.ubicacion', ['pais' => $negocio->pais, 'departamento' => $negocio->departamento, 'ciudad' => $negocio->ciudad])
                <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $negocio->direccion) }}" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Teléfono / WhatsApp</label><input name="telefono" class="form-control" value="{{ old('telefono', $negocio->telefono) }}" maxlength="30"></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input name="email" type="email" class="form-control" value="{{ old('email', $negocio->email) }}" maxlength="120"></div>
                <div class="col-12">
                    <label class="form-label">Dirección de su catálogo en línea</label>
                    <div class="input-group"><span class="input-group-text" style="border-radius:12px 0 0 12px">{{ url('/t') }}/</span><input name="slug" class="form-control" value="{{ old('slug', $negocio->slug) }}" pattern="[a-z0-9-]+" maxlength="60" required></div>
                    <small class="text-muted">Solo minúsculas, números y guiones. Ej: moda-urbana-pasto</small>
                </div>
                <div class="col-12"><hr>
                    @if (!$negocio->pais || $negocio->pais === 'CO')
                    <a href="{{ route('facturacion.index') }}" class="d-flex align-items-center gap-2 p-3 text-decoration-none" style="background:#f2fbf3;border-radius:16px;color:#11702f;font-weight:700"><i class="fa-solid fa-file-invoice-dollar fa-lg"></i><span>Facturación DIAN: RUT, IVA, resoluciones, POS electrónico y factura electrónica <i class="fa-solid fa-arrow-right ms-1"></i></span></a>
                    @endif
                </div>
                <div class="col-12"><label class="form-label">Mensaje al pie del tiquete</label><input name="mensaje_tiquete" class="form-control" value="{{ old('mensaje_tiquete', $negocio->mensaje_tiquete) }}" maxlength="255"></div>
            </div>
        </div>
        <div>
        <div class="lp-box text-center mb-3">
            <h3>Tienda en línea</h3>
            <p class="text-muted small">Su página web con sus productos por categorías y botón <b>Comprar</b> por WhatsApp. Compártala en Instagram, WhatsApp o Facebook.</p>
            @php $tG = \App\Support\TiendaActual::tienda(); @endphp
            @if ($tG?->en_google)
            <div class="small fw-bold mb-2" style="color:#11702f"><i class="fa-brands fa-google me-1"></i>Aparece en los resultados de Google</div>
            @else
            <div class="small mb-2 p-2" style="background:#f2fbf3;border-radius:12px;color:#11702f"><i class="fa-brands fa-google me-1"></i>Con un plan pago su tienda también <b>aparece en Google</b>. <a href="{{ route('plan.index') }}" class="fw-bold" style="color:#11702f">Pásate a Pro</a></div>
            @endif
            <div id="qr" class="d-flex justify-content-center mb-2"></div>
            <input class="form-control mb-2 text-center" id="urlCat" value="{{ $negocio->url_catalogo }}" readonly onclick="this.select()">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dark flex-fill" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('urlCat').value); this.textContent='¡Copiado!'"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
                <a class="btn btn-primary flex-fill" target="_blank" rel="noopener" href="{{ $negocio->url_catalogo_navegador }}"><i class="fa-solid fa-up-right-from-square me-1"></i>Ver</a>
            </div>
            <input type="hidden" name="catalogo_activo" value="0">
            <div class="form-check form-switch d-inline-block mt-3"><input class="form-check-input" type="checkbox" name="catalogo_activo" value="1" id="catAct" @checked(old('catalogo_activo', $negocio->catalogo_activo))><label class="form-check-label" for="catAct">Tienda en línea visible al público</label></div>
            <div class="mt-2">
                <label class="form-label small fw-bold mb-1">¿Qué productos se muestran en la tienda en línea?</label>
                @php $agot = (bool) old('catalogo_agotados', $negocio->catalogo_agotados ?? true); @endphp
                <div class="form-check"><input class="form-check-input" type="radio" name="catalogo_agotados" value="0" id="agot0" @checked(!$agot)><label class="form-check-label" for="agot0"><b>Solo los que tienen existencias</b> <small class="text-muted d-block">Lo agotado se oculta solo y vuelve a salir cuando entra mercancía. Se ve más ordenado.</small></label></div>
                <div class="form-check mt-1"><input class="form-check-input" type="radio" name="catalogo_agotados" value="1" id="agot1" @checked($agot)><label class="form-check-label" for="agot1"><b>Todos, también los agotados</b> <small class="text-muted d-block">Los agotados salen al final con la etiqueta "Agotado".</small></label></div>
            </div>
            @if ($negocio->usa_carta)
            <div class="mt-3 pt-3" style="border-top:1px dashed #dfe7e1">
                <label class="form-label small fw-bold mb-1"><i class="fa-solid fa-utensils me-1"></i>Carta virtual de su restaurante</label>
                <small class="text-muted d-block mb-2">Su menú con diseño de restaurante y pedido por WhatsApp (domicilio, para recoger o en la mesa). Póngala en un QR en las mesas o compártala. Los productos desactivados salen como "Agotado por hoy".</small>
                <div class="row g-2">
                    @foreach (\App\Models\Tienda::CARTA_TEMAS as $k => [$nom, $txt, $fondo, $acento])
                    <div class="col-6">
                        <label class="d-block p-2 h-100" style="border:2px solid {{ $negocio->carta_tema_actual === $k ? '#16b34a' : '#e3ebe6' }};border-radius:14px;cursor:pointer">
                            <input type="radio" name="carta_tema" value="{{ $k }}" class="form-check-input me-1" @checked(old('carta_tema', $negocio->carta_tema_actual) === $k)>
                            <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:{{ $fondo }};border:3px solid {{ $acento }};vertical-align:middle"></span>
                            <b class="small">{{ $nom }}</b>
                            <small class="text-muted d-block" style="font-size:.72rem">{{ $txt }}</small>
                            <a href="{{ $negocio->url_carta_navegador }}?tema={{ $k }}" target="_blank" rel="noopener" class="small fw-bold" style="color:#11702f">Ver cómo queda →</a>
                        </label>
                    </div>
                    @endforeach
                </div>
                <a class="btn btn-dark w-100 mt-2" target="_blank" rel="noopener" href="{{ $negocio->url_carta_navegador }}"><i class="fa-solid fa-utensils me-1"></i>Ver mi carta virtual</a>
                <input class="form-control mt-2 text-center small" value="{{ $negocio->url_carta }}" readonly onclick="this.select()">
            </div>
            @endif
            @unless ($negocio->telefono)<div class="alert border-0 mt-2 mb-0 small" style="background:#fff5d6;color:#8a6400">Ponga el teléfono / WhatsApp del negocio para recibir los pedidos.</div>@endunless
        </div>
        <div class="lp-box">
            <h3>Caja (punto de venta)</h3>
            <input type="hidden" name="cliente_obligatorio" value="0">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="cliente_obligatorio" value="1" id="cliObl" @checked(old('cliente_obligatorio', $negocio->cliente_obligatorio))><label class="form-check-label fw-bold" for="cliObl">Pedir siempre el cliente para cobrar</label></div>
            <small class="text-muted d-block mt-1">Apagado: se puede vender como "consumidor final" (la caja recuerda poner el cliente). Encendido: no deja cobrar sin elegir o crear el cliente.</small>
        </div>
        @if (!in_array($negocio->tipo_negocio, ['contable', 'instituto', 'conjunto', 'eventos'], true))
        <div class="lp-box mt-3">
            <h3>¿Qué hace su negocio?</h3>
            <small class="text-muted d-block mb-2">Cámbielo cuando quiera: no se borra nada, solo se muestra o se esconde en el menú.</small>
            @foreach (\App\Models\Tienda::ACTIVIDADES as $clave => [$nombre, $icono, $desc])
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="actividad" value="{{ $clave }}" id="act_{{ $clave }}" @checked(old('actividad', $negocio->actividad_actual) === $clave)>
                <label class="form-check-label fw-bold" for="act_{{ $clave }}"><i class="fa-solid {{ $icono }} me-1" style="color:var(--lp-green-2,#16b34a)"></i>{{ $nombre }}</label>
                <small class="text-muted d-block">{{ $desc }}@if ($clave === 'alquila'). Entra directo a Alquileres; la caja de venta y los separados quedan escondidos.@elseif ($clave === 'ambos'). Alquiler sale en el menú junto a la caja de venta.@endif</small>
            </div>
            @endforeach
        </div>
        @endif
        <div class="lp-box mt-3">
            <h3 class="d-flex align-items-center">Módulos de su negocio @if (\Illuminate\Support\Facades\Route::has('menu.edit'))<a href="{{ route('menu.edit') }}" class="btn btn-sm btn-light ms-auto"><i class="fa-solid fa-sliders me-1"></i>Personalizar menú</a>@endif</h3>
            @foreach ([
                'mod_servicios' => ['Servicios (sin inventario)', 'Para lo que no se acaba ni lleva stock: cortes, consultas, reparaciones, asesorías, clases, lavados… En Productos podrá crear y administrar sus servicios aparte; siempre están disponibles para vender o agendar.', false],
                'mod_medida' => ['Vender por metro, kilo o litro', 'En cada producto elige cómo se vende y en la caja vende medidas con decimales (1,5 m · 0,250 kg).', false],
                'mod_vencimiento' => ['Fechas de vencimiento', 'Lotes con fecha de vencimiento y alertas de lo que está por vencer.', true],
                'mod_recetas' => ['Recetas e insumos', 'Al vender se descuentan los ingredientes de cada receta.', true],
                'mod_pedidos' => ['Pedidos: mesero, caja y cocina', 'Para restaurantes, cafeterías, granjas y parques: el mesero toma el pedido, caja lo cobra, cada estación (cocina, cafetería, entradas…) lo ve en su pantalla y avisa cuando está listo.', false],
                'mod_contable' => ['Contabilidad: retenciones e IVA', 'Para contadores y analistas: cruce de facturas de la DIAN con la contabilidad, borrador del 350 y del IVA, certificados de retención.', false],
                'mod_eventos' => ['Eventos y venta de entradas', 'Página de su evento para compartir, registro de compradores, pago por Nequi con comprobante por WhatsApp, aprobación del pago y entrada con QR que se valida en la puerta.', false],
                'mod_obituarios' => ['Obituarios (funerarias)', 'Salas de velación y obituarios con foto para los televisores de cada sala. Se enciende solo si el negocio es funeraria.', false],
                'mod_pantallas' => ['Pantallas (televisores del negocio)', 'Muestre promociones, menú, precios o videos en los televisores del local. Sin cables ni USB: se maneja desde LocalProo.', false],
                'mod_consultorio' => ['Consultorio (pacientes e historia clínica)', 'Para psicología, odontología, estética, fisioterapia, nutrición o medicina: pacientes, agenda de citas, historia clínica cifrada, fórmulas e incapacidades.', false],
            ] as $k => [$tit, $txt, $pronto])
            <input type="hidden" name="{{ $k }}" value="0">
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="{{ $k }}" value="1" id="{{ $k }}" @checked(old($k, $negocio->$k))><label class="form-check-label fw-bold" for="{{ $k }}">{{ $tit }}@if ($pronto) <span class="badge rounded-pill" style="background:#fef3c7;color:#92600a;font-size:.65rem">Muy pronto</span>@endif</label>
                <small class="text-muted d-block">{{ $txt }}</small></div>
            @endforeach
        </div>
        <div class="lp-box text-center">
            <h3>Logo</h3>
            <div style="width:160px;height:160px;margin:0 auto 1rem;border-radius:24px;border:2px dashed var(--lp-line);display:grid;place-items:center;overflow:hidden;background:#fafdfb">
                @if ($negocio->logo_url)
                    <img id="logoPrev" src="{{ $negocio->logo_url }}" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain">
                @else
                    <img id="logoPrev" alt="" style="max-width:100%;max-height:100%;object-fit:contain;display:none"><i id="logoIcon" class="fa-regular fa-image fa-3x" style="color:#bfeccb"></i>
                @endif
            </div>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control mb-2" onchange="var f=this.files[0];if(f){var i=document.getElementById('logoPrev');i.src=URL.createObjectURL(f);i.style.display='';var c=document.getElementById('logoIcon');if(c)c.style.display='none';}">
            <small class="text-muted d-block mb-2">PNG o JPG, máximo 2 MB. Mejor en blanco y negro para la impresora térmica.</small>
            @if ($negocio->logo_url)
            <div class="form-check d-inline-block"><input class="form-check-input" type="checkbox" name="quitar_logo" value="1" id="ql"><label class="form-check-label" for="ql">Quitar logo</label></div>
            @endif
            <button class="btn btn-primary w-100 mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios</button>
        </div>
        </div>
    </form>
</div>
<style>
.ng-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem;align-items:start}
.ng-grid > div{min-width:0}
@media (min-width:1100px){.ng-grid{grid-template-columns:minmax(0,1.4fr) minmax(0,1fr)}}
.ng-grid .input-group{flex-wrap:nowrap}
.ng-grid .input-group-text{max-width:55%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.85rem}
</style>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    if (window.QRCode) new QRCode(document.getElementById('qr'), { text: @json($negocio->url_catalogo), width: 150, height: 150, colorDark: '#0b0d0c', colorLight: '#ffffff' });
</script>
@endpush
