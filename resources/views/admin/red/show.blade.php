@extends('layouts.app')

@section('title', $a->nombre)

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><a href="{{ route('admin.red.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Red comercial</a></small>
            <h1>{{ $a->nombre }}</h1>
            <span class="text-muted fw-semibold"><i class="fa-solid {{ $a->cargo_icono }} me-1"></i>{{ $a->cargo_nombre }}{{ $a->ciudad ? ' · ' . $a->ciudad : '' }}{{ $a->departamento ? ' · ' . $a->departamento : '' }} · Código <b>{{ $a->codigo }}</b>{{ $a->activo ? '' : ' · INACTIVO' }}</span>
        </div>
        @if ($a->user?->telefono)<div class="lp-quick"><a class="btn btn-success" target="_blank" href="https://wa.me/{{ numero_whatsapp($a->user->telefono) }}"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</a></div>@endif
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Por pagarle</div><div class="val">{{ $f($pendiente) }}</div>
            <div class="sub">@if ($pendiente > 0)<form method="post" action="{{ route('admin.red.pagar') }}" class="d-inline" data-confirm="¿Marcar como pagado {{ $f($pendiente) }}?" data-confirm-texto="Todas las comisiones pendientes de {{ $a->nombre }} quedan como pagadas hoy." data-confirm-ok="Sí, ya le pagué">@csrf<input type="hidden" name="asesor_id" value="{{ $a->id }}"><button class="btn btn-sm btn-dark mt-1">Marcar todo como pagado</button></form>@else Al día @endif{{ $a->banco ? ' · Pagar a: ' . $a->banco : '' }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Negocios propios</div><div class="val">{{ $negocios->count() }}</div><div class="sub">Enlace: {{ $a->enlace }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Equipo directo</div><div class="val">{{ $equipo->count() }}</div><div class="sub">{{ $a->padre ? 'Responde a ' . $a->padre->nombre : 'Responde a LocalPro' }}</div></div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3>Datos</h3>
            <form method="post" action="{{ route('admin.red.update', $a) }}" class="row g-3">
                @csrf @method('PUT')
                @include('admin.red._form', ['a' => $a])
                <div class="col-12"><input type="hidden" name="activo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" id="act" @checked(old('activo', $a->activo))><label class="form-check-label fw-bold" for="act">Activo (puede entrar y ganar comisiones)</label></div></div>
                <div class="col-12"><button class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
        <div>
            <div class="lp-box mb-3">
                <h3>Negocios de {{ strtok($a->nombre, ' ') }}</h3>
                <ul class="lp-list">
                    @forelse ($negocios as $t)
                    <li><div class="av"><i class="fa-solid fa-store"></i></div><div class="t"><b><a href="{{ route('admin.tiendas.show', $t) }}" class="text-decoration-none" style="color:inherit">{{ $t->nombre }}</a></b><small>{{ $t->plan->nombre ?? 'Sin plan' }}{{ $t->plan_vence ? ' · vence ' . $t->plan_vence->format('d/m/Y') : '' }}</small></div></li>
                    @empty
                    <li class="lp-empty">Sin negocios todavía.</li>
                    @endforelse
                </ul>
                @if ($sinAsesor->isNotEmpty())
                <form method="post" class="d-flex gap-2 mt-2" onsubmit="this.action=this.querySelector('select').value">
                    @csrf
                    <input type="hidden" name="asesor_id" value="{{ $a->id }}">
                    <select class="form-select" required>
                        <option value="">+ Pasarle un negocio sin asesor…</option>
                        @foreach ($sinAsesor as $t)<option value="{{ route('admin.tiendas.asesor', $t) }}">{{ $t->nombre }}</option>@endforeach
                    </select>
                    <button class="btn btn-dark">Asignar</button>
                </form>
                @endif
            </div>
            @if ($equipo->isNotEmpty())
            <div class="lp-box">
                <h3>Su equipo</h3>
                <ul class="lp-list">
                    @foreach ($equipo as $h)
                    <li><div class="av"><i class="fa-solid {{ $h->cargo_icono }}"></i></div><div class="t"><b><a href="{{ route('admin.red.show', $h) }}" class="text-decoration-none" style="color:inherit">{{ $h->nombre }}</a></b><small>{{ $h->cargo_nombre }} · {{ $h->tiendas_count }} negocio(s)</small></div></li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>

    <div class="lp-box">
        <h3>Comisiones</h3>
        <ul class="lp-list">
            @forelse ($comisiones as $c)
            <li><div class="av"><i class="fa-solid fa-coins"></i></div><div class="t"><b>{{ $f($c->monto) }} · {{ $c->tienda->nombre ?? '' }}</b><small>{{ $c->concepto_nombre }} · {{ $c->tipo === 'nueva' ? 'venta nueva' : 'renovación' }} · {{ $c->created_at->format('d/m/Y') }}{{ $c->pagada_at ? ' · pagada ' . $c->pagada_at->format('d/m/Y') : '' }}</small></div>
                <span class="badge {{ $c->estado === 'pagada' ? 'bg-success' : ($c->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($c->estado) }}</span></li>
            @empty
            <li class="lp-empty">Sin comisiones.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
