@extends('layouts.app')

@section('title', $tienda->nombre)

@section('content')
@php $m = \App\Support\Paises::datos($tienda->pais); $f = fn ($v) => $m['simbolo'] . number_format((float) $v, $m['decimales'], $m['decimal'], $m['miles']); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><a href="{{ route('admin.tiendas.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiendas</a></small>
            <h1>{{ $tienda->nombre }} <small style="font-size:.9rem;font-family:ui-monospace,Menlo,monospace;background:#eaf8ee;color:#11702f;border-radius:8px;padding:.15rem .5rem;vertical-align:middle">{{ $tienda->codigo }}</small></h1>
            <span class="text-muted fw-semibold"><i class="fa-solid {{ $tienda->tipo_icono }} me-1"></i>{{ $tienda->tipo_nombre }} · {{ $m['nombre'] }} ({{ $m['moneda'] }}){{ $tienda->ciudad ? ' · ' . $tienda->ciudad : '' }} · Inscrita {{ $tienda->created_at->locale('es')->isoFormat('D MMM YYYY') }}</span>
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
                <div class="col-md-5"><label class="form-label">Plan</label><select name="plan_id" class="form-select">@foreach ($planes as $p)<option value="{{ $p->id }}" @selected((int) $tienda->plan_id === (int) $p->id)>{{ $p->nombre }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Vence</label><input type="date" name="plan_vence" class="form-control" value="{{ $tienda->plan_vence?->format('Y-m-d') }}"></div>
                <div class="col-md-3 d-grid"><button class="btn btn-dark">Asignar</button></div>
            </form>
            <form method="post" action="{{ route('admin.tiendas.pago', $tienda) }}" class="row g-2 align-items-end" data-confirm="¿Registrar el pago?" data-confirm-texto="Se extiende el plan de la tienda según los meses indicados." data-confirm-ok="Sí, registrar">
                @csrf
                <div class="col-12"><b>Registrar pago recibido</b> <small class="text-muted">(transferencia, Nequi, efectivo)</small></div>
                <div class="col-md-5"><select name="plan_id" class="form-select" id="pp" onchange="document.getElementById('pm').value=this.selectedOptions[0].dataset.p">@foreach ($planes->where('precio', '>', 0) as $p)<option value="{{ $p->id }}" data-p="{{ (int) $p->precio }}">{{ $p->nombre }} · ${{ number_format($p->precio, 0, ',', '.') }}</option>@endforeach</select></div>
                <div class="col-md-3"><input data-dinero type="number" min="0" name="monto" id="pm" class="form-control" value="{{ (int) optional($planes->where('precio', '>', 0)->first())->precio }}"></div>
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
            <h3>Pagos de plan</h3>
            <ul class="lp-list">
                @forelse ($pagosPlan as $pg)
                <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="t"><b>{{ $pg->plan->nombre ?? '' }} · ${{ number_format($pg->monto, 0, ',', '.') }}</b><small>{{ $pg->created_at->format('d/m/Y') }} · {{ ucfirst($pg->metodo) }}{{ $pg->periodo_hasta ? ' · hasta ' . $pg->periodo_hasta->format('d/m/Y') : '' }}</small></div><span class="badge {{ $pg->estado === 'aprobado' ? 'bg-success' : ($pg->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($pg->estado) }}</span></li>
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
</div>
@endsection
