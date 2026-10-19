@extends('layouts.app')

@section('title', 'Planes y cobros')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Planes y <span>cobros</span></h1></div>
        <div class="lp-kpi" style="padding:.8rem 1.2rem"><div class="lbl">Ingresos este mes</div><div class="val" style="font-size:1.8rem">${{ number_format($ingresosMes, 0, ',', '.') }}</div></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-box mb-3" style="{{ $wompi ? '' : 'background:#fff5d6;border-color:#f3e0a6' }}">
        <h3><span><i class="fa-solid fa-credit-card me-1"></i> Cobro en línea con Wompi: {{ $wompi ? 'activo ✅' : 'sin configurar' }}</span></h3>
        @unless ($wompi)
        <p class="mb-0 small">Mientras tanto, el botón "Pagar" de las tiendas les abre un WhatsApp con usted y usted registra el pago en la ficha de cada tienda. Para activar Wompi agregue en el archivo <code>.env</code> del servidor: <code>WOMPI_PUBLIC_KEY</code>, <code>WOMPI_INTEGRITY_SECRET</code>, <code>WOMPI_PRIVATE_KEY</code> y <code>WOMPI_EVENTS_SECRET</code>.</p>
        @else
        <p class="mb-0 small">{{ \App\Support\Wompi::esPruebas() ? '⚠️ Llaves de PRUEBAS (sandbox): no entra dinero real.' : 'Recibiendo pagos reales.' }} El dueño paga desde "Mi plan" y el asesor o usted pueden enviar el enlace de pago de cada negocio por WhatsApp. El plan se activa al volver de Wompi; los pendientes se revisan solos cada 10 minutos{{ config('services.wompi.private_key') ? '' : ' (falta WOMPI_PRIVATE_KEY para esto)' }}.</p>
        @endunless
    </div>

    <form method="post" action="{{ route('admin.planes.guardar') }}">
        @csrf
        <div class="lp-box mb-3">
            <h3>Planes (precios en COP · y en US$ para Venezuela)</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Nombre</th><th style="width:150px">Precio COP</th><th style="width:120px" title="Lo que paga un negocio de Venezuela">Precio US$</th><th style="width:120px">Días</th><th style="width:110px">Productos máx.</th><th style="width:110px">Personas máx.</th><th>Descripción</th><th>Activo</th><th class="text-end">Tiendas</th></tr></thead>
                    <tbody>
                    @foreach ($planes as $p)
                    <tr>
                        <td><input name="planes[{{ $p->id }}][nombre]" class="form-control" value="{{ $p->nombre }}" required></td>
                        <td><input data-dinero type="number" min="0" name="planes[{{ $p->id }}][precio]" class="form-control" value="{{ (int) $p->precio }}"></td>
                        <td><input type="number" step="0.01" min="0" name="planes[{{ $p->id }}][precio_usd]" class="form-control" value="{{ $p->precio_usd }}" placeholder="{{ round($p->precio / \App\Support\Moneda::tasa(), 2) }}"></td>
                        <td><input type="number" min="1" name="planes[{{ $p->id }}][dias]" class="form-control" value="{{ $p->dias }}" placeholder="No vence"></td>
                        <td><input type="number" min="1" name="planes[{{ $p->id }}][max_productos]" class="form-control" value="{{ $p->max_productos }}" placeholder="Sin límite"></td>
                        <td><input type="number" min="1" name="planes[{{ $p->id }}][max_usuarios]" class="form-control" value="{{ $p->max_usuarios }}" placeholder="Sin límite"></td>
                        <td><input name="planes[{{ $p->id }}][descripcion]" class="form-control" value="{{ $p->descripcion }}"></td>
                        <td><input type="checkbox" class="form-check-input" name="planes[{{ $p->id }}][activo]" value="1" @checked($p->activo)></td>
                        <td class="text-end">{{ $p->tiendas_count }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td><input name="nuevo[nombre]" class="form-control" placeholder="+ Nuevo plan"></td>
                        <td><input data-dinero type="number" min="0" name="nuevo[precio]" class="form-control" placeholder="0"></td>
                        <td><input type="number" step="0.01" min="0" name="nuevo[precio_usd]" class="form-control" placeholder="US$"></td>
                        <td><input type="number" min="1" name="nuevo[dias]" class="form-control" placeholder="30"></td>
                        <td colspan="5"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-md-5"><label class="form-label">Su WhatsApp de soporte (con indicativo)</label><input name="soporte_whatsapp" class="form-control" value="{{ $soporte }}" placeholder="573001234567"></div>
                <div class="col-md-2"><label class="form-label" title="Lo que paga el negocio por cada paquete de productos extra">Precio paquete extra</label><input data-dinero type="number" min="0" name="productos_paquete_precio" class="form-control" value="{{ $paqPrecio }}"></div>
                <div class="col-md-2"><label class="form-label" title="Paquete extra para negocios de Venezuela">Paquete extra US$</label><input type="number" step="0.01" min="0" name="productos_paquete_precio_usd" class="form-control" value="{{ $paqPrecioUsd }}"></div>
                <div class="col-md-2"><label class="form-label" title="Wompi solo cobra en pesos: un pago de US$55 se cobra a esta tasa">Tasa: pesos por US$1</label><input type="number" min="1" step="1" name="tasa_usd_cop" class="form-control" value="{{ (int) $tasa }}"></div>
                <div class="col-md-2"><label class="form-label">Productos por paquete</label><input type="number" min="1" name="productos_paquete_cantidad" class="form-control" value="{{ $paqCantidad }}"></div>
                <div class="col-md-3 text-end"><button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button></div>
                <div class="col-12"><div style="border:1.5px solid #cfeedd;background:#f3faf5;border-radius:18px;padding:1rem 1.1rem">
                    <b><i class="fa-solid fa-eye me-1" style="color:#16b34a"></i>Tiendas de ejemplo para los asesores</b>
                    <label class="form-label small fw-bold mt-2 mb-1">Códigos LCT separados por coma (ej. una de ropa, un restaurante…)</label>
                    <input name="ejemplos_tiendas" class="form-control" value="{{ $ejemplos }}" placeholder="LCT-00003, LCT-00010">
                    <small class="text-muted d-block mt-1">Los asesores ven su tienda en línea y su página de enlaces en "Ejemplos para mostrar" (la factura es de ejemplo, con datos inventados). Use tiendas con fotos bonitas y enlaces publicados. <a href="{{ route('asesor.ejemplos') }}" target="_blank">Ver cómo queda</a></small>
                </div></div>
                <div class="col-12"><div style="border:1.5px solid #cfe0ff;background:#f5f9ff;border-radius:18px;padding:1rem 1.1rem">
                    <b><i class="fa-brands fa-facebook me-1" style="color:#1877f2"></i>Conexión con Facebook e Instagram (Mis redes)</b>
                    <div class="form-check form-switch mt-2"><input type="hidden" name="meta_en_vivo" value="0"><input class="form-check-input" type="checkbox" name="meta_en_vivo" value="1" id="metaVivo" @checked($metaEnVivo)><label class="form-check-label fw-semibold" for="metaVivo">Meta ya aprobó la app y está <b>en vivo</b> (todos los negocios pueden conectar)</label></div>
                    <label class="form-label small fw-bold mt-2 mb-1">Mientras tanto, negocios con acceso de prueba (códigos LCT separados por coma)</label>
                    <input name="meta_tiendas_prueba" class="form-control" value="{{ $metaPrueba }}" placeholder="LCT-00003, LCT-00007">
                    <small class="text-muted d-block mt-1">Antes de poner el código aquí, agregue el perfil de Facebook del dueño en developers.facebook.com → su app LocalProo → Roles de la app → Evaluadores, y que acepte la invitación. Los demás negocios ven una pantalla para pedir su acceso por WhatsApp.</small>
                </div></div>
            </div>
        </div>
    </form>

    <div class="lp-box">
        <h3>Últimos pagos</h3>
        <ul class="lp-list">
            @forelse ($pagos as $pg)
            <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="t"><b>{{ $pg->tienda->nombre ?? '—' }} · {{ $pg->plan->nombre ?? '' }}</b><small>{{ $pg->created_at->format('d/m/Y H:i') }} · {{ ucfirst($pg->metodo) }} · {{ $pg->referencia }}</small></div>
                <span class="badge {{ $pg->estado === 'aprobado' ? 'bg-success' : ($pg->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($pg->estado) }}</span><span class="m">{{ $pg->monto_texto }}</span></li>
            @empty
            <li class="lp-empty">Aún no hay pagos.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
