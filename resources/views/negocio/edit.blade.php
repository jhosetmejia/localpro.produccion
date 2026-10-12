@extends('layouts.app')

@section('title', 'Datos del negocio')

@section('content')
<div class="lp-dash">
    <div class="lp-hello"><div><small>Configuración</small><h1>Datos del <span>negocio</span></h1><span class="text-muted fw-semibold">Aparecen en el tiquete, la factura y el mensaje de WhatsApp.</span></div></div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('negocio.update') }}" enctype="multipart/form-data" class="lp-grid" style="grid-template-columns:1.4fr 1fr">
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
                <div class="col-12"><hr><h3 style="font-size:1rem;font-weight:800">Facturación DIAN <small class="text-muted fw-normal">(opcional, sale impreso en el tiquete)</small></h3></div>
                <div class="col-md-6"><label class="form-label">Régimen</label>
                    <select name="regimen" class="form-select">
                        <option value="">—</option>
                        @foreach (['No responsable de IVA', 'Responsable de IVA', 'Régimen simple de tributación'] as $r)<option @selected(old('regimen', $negocio->regimen) === $r)>{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">N.º resolución de facturación</label><input name="resolucion_numero" class="form-control" value="{{ old('resolucion_numero', $negocio->resolucion_numero) }}" maxlength="40"></div>
                <div class="col-md-4"><label class="form-label">Fecha resolución</label><input type="date" name="resolucion_fecha" class="form-control" value="{{ old('resolucion_fecha', $negocio->resolucion_fecha?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Vigente hasta</label><input type="date" name="resolucion_vigencia" class="form-control" value="{{ old('resolucion_vigencia', $negocio->resolucion_vigencia?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Prefijo</label><input name="resolucion_prefijo" class="form-control" value="{{ old('resolucion_prefijo', $negocio->resolucion_prefijo) }}" maxlength="10" placeholder="Ej: POS"></div>
                <div class="col-md-6"><label class="form-label">Rango desde</label><input type="number" min="1" name="resolucion_desde" class="form-control" value="{{ old('resolucion_desde', $negocio->resolucion_desde) }}"></div>
                <div class="col-md-6"><label class="form-label">Rango hasta</label><input type="number" min="1" name="resolucion_hasta" class="form-control" value="{{ old('resolucion_hasta', $negocio->resolucion_hasta) }}"></div>
                @if ($negocio->resolucion_prefijo)
                <div class="col-12"><small class="text-muted">Con prefijo, las ventas se numeran <b>{{ $negocio->resolucion_prefijo }}-{{ max($negocio->consecutivo_pos + 1, (int) $negocio->resolucion_desde) }}</b> en adelante.
                    @if ($negocio->resolucion_hasta && $negocio->consecutivo_pos >= $negocio->resolucion_hasta - 50)<b class="text-danger"> ¡Quedan pocos números del rango autorizado! Solicite una nueva resolución.</b>@endif
                    @if ($negocio->resolucion_vigencia && $negocio->resolucion_vigencia->lt(today()->addDays(30)))<b class="text-danger"> La resolución vence pronto.</b>@endif
                </small></div>
                @endif
                <div class="col-12"><small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>La factura electrónica con envío a la DIAN requiere un proveedor tecnológico; se puede conectar más adelante.</small></div>
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
                <a class="btn btn-primary flex-fill" target="_blank" href="{{ $negocio->url_catalogo }}"><i class="fa-solid fa-up-right-from-square me-1"></i>Ver</a>
            </div>
            <input type="hidden" name="catalogo_activo" value="0">
            <div class="form-check form-switch d-inline-block mt-3"><input class="form-check-input" type="checkbox" name="catalogo_activo" value="1" id="catAct" @checked(old('catalogo_activo', $negocio->catalogo_activo))><label class="form-check-label" for="catAct">Tienda en línea visible al público</label></div>
            @unless ($negocio->telefono)<div class="alert border-0 mt-2 mb-0 small" style="background:#fff5d6;color:#8a6400">Ponga el teléfono / WhatsApp del negocio para recibir los pedidos.</div>@endunless
        </div>
        <div class="lp-box">
            <h3>Caja (punto de venta)</h3>
            <input type="hidden" name="cliente_obligatorio" value="0">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="cliente_obligatorio" value="1" id="cliObl" @checked(old('cliente_obligatorio', $negocio->cliente_obligatorio))><label class="form-check-label fw-bold" for="cliObl">Pedir siempre el cliente para cobrar</label></div>
            <small class="text-muted d-block mt-1">Apagado: se puede vender como "consumidor final" (la caja recuerda poner el cliente). Encendido: no deja cobrar sin elegir o crear el cliente.</small>
        </div>
        <div class="lp-box mt-3">
            <h3>Módulos de su negocio</h3>
            @foreach ([
                'mod_medida' => ['Vender por metro, kilo o litro', 'En cada producto elige cómo se vende y en la caja vende medidas con decimales (1,5 m · 0,250 kg).', false],
                'mod_vencimiento' => ['Fechas de vencimiento', 'Lotes con fecha de vencimiento y alertas de lo que está por vencer.', true],
                'mod_recetas' => ['Recetas e insumos', 'Al vender se descuentan los ingredientes de cada receta.', true],
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
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    if (window.QRCode) new QRCode(document.getElementById('qr'), { text: @json($negocio->url_catalogo), width: 150, height: 150, colorDark: '#0b0d0c', colorLight: '#ffffff' });
</script>
@endpush
