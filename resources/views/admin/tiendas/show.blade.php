@extends('layouts.app')

@section('title', $tienda->nombre)

@section('content')
@php $m = \App\Support\Paises::datos($tienda->pais); $f = fn ($v) => $m['simbolo'] . number_format((float) $v, $m['decimales'], $m['decimal'], $m['miles']); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><a href="{{ route('admin.tiendas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiendas</a></small>
            <h1>{{ $tienda->nombre }} <small style="font-size:.9rem;font-family:ui-monospace,Menlo,monospace;background:#eaf8ee;color:#11702f;border-radius:8px;padding:.15rem .5rem;vertical-align:middle">{{ $tienda->codigo }}</small></h1>
            <span class="text-muted fw-semibold"><i class="fa-solid {{ $tienda->tipo_icono }} me-1"></i>{{ $tienda->tipo_nombre }} · {{ $m['nombre'] }} ({{ $m['moneda'] }}){{ $tienda->ciudad ? ' · ' . $tienda->ciudad : '' }} · Inscrita {{ $tienda->created_at->locale('es')->isoFormat('D MMM YYYY') }}@if ($tienda->origen) · Llegó por <b>{{ str_starts_with($tienda->origen, 'blog:') ? 'el blog (' . substr($tienda->origen, 5) . ')' : $tienda->origen }}</b>@endif</span>
        </div>
        <div class="lp-quick">
            <form method="post" action="{{ route('admin.tiendas.entrar', $tienda) }}">@csrf<button class="btn btn-primary"><i class="fa-solid fa-right-to-bracket me-1"></i>Entrar a la tienda</button></form>
            <form method="post" action="{{ route('admin.tiendas.estado', $tienda) }}" data-confirm="{{ $tienda->activa ? '¿Suspender esta tienda?' : '¿Activar esta tienda?' }}" data-confirm-texto="{{ $tienda->activa ? 'Sus usuarios no podrán entrar hasta que la active de nuevo.' : 'Sus usuarios podrán volver a entrar y vender.' }}" data-confirm-ok="{{ $tienda->activa ? 'Sí, suspender' : 'Sí, activar' }}" @if($tienda->activa) data-peligro @endif>@csrf
                <button class="btn {{ $tienda->activa ? 'btn-danger' : 'btn-dark' }}"><i class="fa-solid {{ $tienda->activa ? 'fa-ban' : 'fa-circle-check' }} me-1"></i>{{ $tienda->activa ? 'Suspender' : 'Activar' }}</button>
            </form>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Vendido en total</div><div class="val">{{ $f($metricas['total']) }}</div><div class="sub">{{ $metricas['ventas'] }} ventas · este mes {{ $f($metricas['mes']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-boxes-stacked"></i></div><div class="lbl">Productos</div><div class="val">{{ $metricas['productos'] }}</div><div class="sub">{{ number_format($metricas['unidades'], 0, ',', '.') }} unidades en inventario</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-signal"></i></div><div class="lbl">Estado</div><div class="val" style="font-size:1.6rem">{{ !$tienda->onboarding_completo ? 'Configurando (paso ' . $tienda->onboarding_paso . ')' : ($tienda->activa ? 'Activa' : 'Suspendida') }}</div><div class="sub">{{ $metricas['cajas_abiertas'] }} caja(s) abierta(s)</div></div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3>Plan: {{ $tienda->plan->nombre ?? 'Sin plan' }} {!! $tienda->plan_vence ? '<small class="text-muted fw-semibold">' . ($tienda->plan_vencido ? 'venció' : 'vence') . ' el ' . $tienda->plan_vence->format('d/m/Y') . '</small>' : '' !!}</h3>
            <form method="post" action="{{ route('admin.tiendas.plan', $tienda) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-4"><label class="form-label">Plan</label><select name="plan_id" class="form-select">@foreach ($planes as $p)<option value="{{ $p->id }}" @selected((int) $tienda->plan_id === (int) $p->id)>{{ $p->nombre }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Vence</label><input type="date" name="plan_vence" class="form-control" value="{{ $tienda->plan_vence?->format('Y-m-d') }}"></div>
                <div class="col-md-3"><label class="form-label" title="Página de Facebook con su Instagram">Redes sociales</label><input type="number" min="1" max="20" name="redes_max" class="form-control" value="{{ $tienda->redes_max ?? 1 }}"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-dark">Guardar</button></div>
            </form>
            @php
                $paqCant = \App\Support\AmpliarProductos::cantidad();
                $baseProd = (int) ($tienda->plan->max_productos ?? 0);
                $usadosProd = \Illuminate\Support\Facades\DB::table('productos')->where('tienda_id', $tienda->id)->count();
            @endphp
            <div class="mb-3 p-3" style="background:#f3faf5;border:1.5px solid #cfeedd;border-radius:16px">
                <b><i class="fa-solid fa-box me-1" style="color:#16b34a"></i>Productos: {{ number_format($usadosProd, 0, ',', '.') }} de {{ $baseProd ? number_format($baseProd + (int) $tienda->productos_extra, 0, ',', '.') : 'ilimitados' }}</b>
                <small class="text-muted d-block">Plan: {{ $baseProd ? number_format($baseProd, 0, ',', '.') : 'sin límite' }} · Extra comprados: {{ number_format((int) $tienda->productos_extra, 0, ',', '.') }} · Paquete: {{ \App\Support\AmpliarProductos::oferta($tienda) }}</small>
                <form method="post" action="{{ route('admin.tiendas.productosExtra', $tienda) }}" class="d-flex gap-2 flex-wrap mt-2 align-items-center" data-confirm="¿Cambiar los productos extra?" data-confirm-texto="Se suma o se quita la cantidad de paquetes elegida al cupo de productos de la tienda." data-confirm-ok="Sí, cambiar">
                    @csrf
                    <select name="paquetes" class="form-select form-select-sm" style="width:auto">@foreach ([1, 2, 3, 5, 10] as $n)<option value="{{ $n }}">{{ $n }} paquete{{ $n > 1 ? 's' : '' }} ({{ $n * $paqCant }} productos · {{ \App\Support\AmpliarProductos::precioTexto($tienda, $n) }})</option>@endforeach</select>
                    <button name="accion" value="sumar" class="btn btn-sm btn-success"><i class="fa-solid fa-plus me-1"></i>Agregar</button>
                    @if ($tienda->productos_extra)<button name="accion" value="quitar" class="btn btn-sm btn-light"><i class="fa-solid fa-minus me-1"></i>Quitar</button>@endif
                </form>
            </div>
            <form method="post" action="{{ route('admin.tiendas.pago', $tienda) }}" class="row g-2 align-items-end" data-confirm="¿Registrar el pago?" data-confirm-texto="Se extiende el plan de la tienda según los meses indicados." data-confirm-ok="Sí, registrar">
                @csrf
                <div class="col-12"><b>Registrar pago recibido</b> <small class="text-muted">(transferencia, Nequi, efectivo)</small></div>
                @php $mc = $tienda->moneda_cobro; $usd = \App\Support\Moneda::esUsd($mc); $planesPago = $planes->where('precio', '>', 0); @endphp
                @if ($usd)<div class="col-12"><small class="fw-bold" style="color:#11702f"><i class="fa-solid fa-dollar-sign me-1"></i>Negocio de Venezuela: el plan se cobra y se registra en dólares (USD).</small></div>@endif
                <div class="col-md-5"><select name="plan_id" class="form-select" id="pp" onchange="var m=document.getElementById('pm');m.value=this.selectedOptions[0].dataset.p;m.dispatchEvent(new Event('input'))">@foreach ($planesPago as $p)<option value="{{ $p->id }}" data-p="{{ $p->precioEn($mc) }}">{{ $p->nombre }} · {{ $p->precioTexto($tienda) }}</option>@endforeach</select></div>
                <div class="col-md-3">@if ($usd)<div class="input-group"><span class="input-group-text">US$</span><input type="number" min="0" step="0.01" name="monto" id="pm" class="form-control" value="{{ optional($planesPago->first())->precioEn($mc) }}"></div>@else<input data-dinero type="number" min="0" name="monto" id="pm" class="form-control" value="{{ (int) optional($planesPago->first())->precio }}">@endif</div>
                <div class="col-md-4 d-grid"><button class="btn btn-primary">Registrar pago</button></div>
                <div class="col-12"><input name="nota" class="form-control" placeholder="Nota (opcional): ej. Nequi ref 12345"></div>
            </form>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-user-tie me-1"></i> Asesor del negocio</span>@if ($tienda->asesor)<a href="{{ route('admin.red.show', $tienda->asesor) }}" class="small">Ver asesor</a>@endif</h3>
            <form method="post" action="{{ route('admin.tiendas.asesor', $tienda) }}" class="d-flex gap-2 mb-3 align-items-start"><div class="flex-grow-1" style="min-width:0">
                @csrf
                <select name="asesor_id" class="form-select" data-buscar data-max="6" aria-label="Asesor del negocio">
                    <option value="localpro" @selected(!$tienda->asesor_id && $tienda->venta_directa_id) data-sub="{{ auth()->user()->name }} · súper administrador · {{ auth()->user()->email }}">👑 LocalPro — {{ auth()->user()->name }} (venta directa)</option>
                    <option value="" @selected(!$tienda->asesor_id && !$tienda->venta_directa_id) data-sub="Queda en la lista de Sin asesor">— Sin asignar</option>
                    @foreach ($asesores as $as)<option value="{{ $as->id }}" @selected((int) $tienda->asesor_id === $as->id) data-sub="{{ $as->cargo_nombre }}{{ $as->ciudad ? ' · ' . $as->ciudad : '' }}{{ $as->user?->email ? ' · ' . $as->user->email : '' }}">{{ $as->codigo }} · {{ $as->nombre }}</option>@endforeach
                </select>
                </div><button class="btn btn-dark">Guardar</button>
            </form>
            <div class="d-flex gap-2 flex-wrap mb-3 p-2" style="background:#f2fbf3;border:1.5px solid #cfe9d6;border-radius:14px">
                <div class="flex-grow-1 small fw-semibold" style="color:#11702f;min-width:180px"><i class="fa-solid fa-credit-card me-1"></i>Enlace de pago con Wompi: el negocio (o quien sea) paga y el plan se activa solo.</div>
                <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $tienda->telefono ? numero_whatsapp($tienda->telefono) : '' }}?text={{ rawurlencode('Hola, le escribo de LocalPro 👋 Aquí puede pagar el plan de ' . $tienda->nombre . ' en línea (tarjeta, PSE o Nequi) y se activa de una vez: ' . $tienda->url_pago) }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</a>
                <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText('{{ $tienda->url_pago }}');this.innerText='¡Copiado!'"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
                <a class="btn btn-sm btn-dark" target="_blank" href="{{ $tienda->url_pago }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
            @if (session('enlace_prueba'))
            <div class="d-flex gap-2 flex-wrap mb-3 p-2" style="background:#fff8e6;border:1.5px solid #f5d98a;border-radius:14px">
                <div class="flex-grow-1 small fw-semibold" style="color:#8a5a00;min-width:180px"><i class="fa-solid fa-flask me-1"></i>Enlace de PRUEBA por $1.500 (plan anual, sin comisiones).</div>
                <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $tienda->telefono ? numero_whatsapp($tienda->telefono) : '' }}?text={{ rawurlencode('Hola 👋 Este es el enlace para probar el pago de LocalPro ($1.500): ' . session('enlace_prueba')) }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</a>
                <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText(this.dataset.u);LP.toast('Enlace copiado','ok')" data-u="{{ session('enlace_prueba') }}"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
                <a class="btn btn-sm btn-dark" target="_blank" href="{{ session('enlace_prueba') }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </div>
            @endif
            <form method="post" action="{{ route('admin.tiendas.ventasHistoricas', $tienda) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center mb-3 p-2" style="background:#f6f8f7;border:1.5px dashed #cfd9d3;border-radius:14px" data-confirm="¿Subir estas ventas como historial de {{ $tienda->nombre }}?" data-confirm-texto="Se crean o completan los clientes y las ventas quedan con su fecha en reportes. No se mueve el inventario. Si sube el mismo archivo dos veces no se repiten." data-confirm-ok="Sí, subir">
                @csrf
                <div class="flex-grow-1 small fw-semibold" style="min-width:180px"><i class="fa-solid fa-clock-rotate-left me-1"></i>Ventas históricas (Excel): Fecha, Producto, Talla, Método de pago, Valor, Cliente, Celular, Cédula, Vendedor</div>
                <input type="file" name="archivo" accept=".xlsx,.csv" required class="form-control form-control-sm" style="max-width:240px">
                <button class="btn btn-sm btn-dark"><i class="fa-solid fa-upload me-1"></i>Subir</button>
            </form>
            @if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
            <h3><span>Pagos de plan</span><form method="post" action="{{ route('admin.tiendas.pagoPrueba', $tienda) }}" class="d-inline" data-confirm="¿Generar un pago de prueba de $1.500?" data-confirm-texto="Al pagarlo, la tienda queda con el plan anual activo. No genera comisiones y no cuenta como venta." data-confirm-ok="Sí, generar enlace">@csrf<button class="btn btn-sm btn-light"><i class="fa-solid fa-flask me-1"></i>Pago de prueba</button></form></h3>
            <ul class="lp-list">
                @forelse ($pagosPlan as $pg)
                <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="t"><b>{{ $pg->plan->nombre ?? '' }} · {{ $pg->monto_texto }}@if ($pg->es_prueba) <span class="badge bg-warning text-dark">Prueba</span>@endif</b><small>{{ $pg->created_at->format('d/m/Y') }} · {{ ucfirst($pg->metodo) }}{{ $pg->periodo_hasta ? ' · hasta ' . $pg->periodo_hasta->format('d/m/Y') : '' }}</small></div><span class="badge {{ $pg->estado === 'aprobado' ? 'bg-success' : ($pg->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($pg->estado) }}</span></li>
                @empty
                <li class="lp-empty">Sin pagos registrados.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Últimas ventas</h3>
            <ul class="lp-list">
                @forelse ($ultimas as $v)
                <li><div class="av"><i class="fa-solid fa-receipt"></i></div><div class="t"><b>{{ $v->numero }}</b><small>{{ $v->cliente_nombre ?: 'Mostrador' }} · {{ \Carbon\Carbon::parse($v->created_at)->diffForHumans() }}</small></div>@if($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif<span class="m">{{ $f($v->total) }}</span></li>
                @empty
                <li class="lp-empty">Aún no ha vendido.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3>Datos y usuarios</h3>
            <ul class="lp-list">
                <li><div class="av"><i class="fa-solid fa-phone"></i></div><div class="t"><b>{{ $tienda->telefono ?: '—' }}</b><small>Teléfono</small></div>@if($tienda->telefono)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ preg_replace('/\D/', '', $m['indicativo'] . ltrim(preg_replace('/\D/', '', $tienda->telefono), '0')) }}"><i class="fa-brands fa-whatsapp"></i></a>@endif</li>
                <li><div class="av"><i class="fa-solid fa-id-card"></i></div><div class="t"><b>{{ $tienda->nit ?: '—' }}</b><small>NIT</small></div></li>
                @foreach ($usuarios as $u)
                <li><div class="av"><i class="fa-solid fa-user"></i></div><div class="t"><b>{{ $u->name }}</b><small>{{ $u->email }}{{ $u->telefono ? ' · ' . $u->telefono : '' }}</small></div><span class="badge bg-{{ $u->hasRole('dueno') ? 'success' : 'secondary' }}">{{ $u->es_superadmin ? 'Admin' : ($u->hasRole('dueno') ? 'Dueño' : 'Vendedor') }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="lp-box mt-3">
        <h3><span>Avisos de fin de prueba <small class="text-muted fw-semibold">(constancia de notificación)</small></span></h3>
        @if ($tienda->datos_depurados)
            <p class="small fw-bold mb-2" style="color:#9b1c5a"><i class="fa-solid fa-broom me-1"></i>Información eliminada el {{ $tienda->datos_depurados_at->format('d/m/Y H:i') }} (se conservan negocio y usuarios).</p>
        @elseif ($fechaBorrado && $tienda->plan_vencido)
            <p class="small fw-bold mb-2" style="color:#9b1c5a"><i class="fa-solid fa-clock me-1"></i>Se elimina la información el {{ $fechaBorrado->format('d/m/Y') }} si no paga{{ \App\Support\AvisosPlan::primeraNotificacion($tienda) ? '' : ' (fecha tentativa: aún no se ha notificado)' }}.</p>
        @endif
        <ul class="lp-list">
            @forelse ($avisos as $a)
            <li><div class="av"><i class="fa-solid {{ $a->canal === 'correo' ? 'fa-envelope' : 'fa-display' }}"></i></div>
                <div class="t"><b>{{ \App\Models\AvisoPlan::TIPOS[$a->tipo] ?? $a->tipo }}</b>
                    <small>{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->canal === 'correo' ? 'Correo a ' . $a->destinatario : 'Visto en el sistema por ' . ($a->user->name ?? $a->destinatario) . ($a->ip ? ' (IP ' . $a->ip . ')' : '') }}{{ $a->fecha_borrado ? ' · borrado anunciado: ' . $a->fecha_borrado->format('d/m/Y') : '' }}</small></div>
                <span class="badge {{ $a->estado === 'fallido' ? 'bg-danger' : 'bg-success' }}" @if($a->estado === 'fallido') title="{{ $a->detalle }}" @endif>{{ ucfirst($a->estado) }}</span></li>
            @empty
            <li class="lp-empty">Sin avisos enviados.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
