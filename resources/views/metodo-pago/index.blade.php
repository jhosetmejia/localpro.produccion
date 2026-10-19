@extends('layouts.app')

@section('title', 'Métodos de pago')

@section('content')
<div class="lp-dash">
    <div class="lp-hello"><div><small>Configuración</small><h1>Métodos de <span>pago</span></h1><span class="text-muted fw-semibold">Las formas de pago que aparecen en el POS.</span></div></div>
    @include('layouts.partials.lp-flash')

    <div class="lp-grid" style="grid-template-columns:1fr 1.4fr">
        @can('crear-metodo-pago')
        <div class="lp-box">
            <h3>Nuevo método</h3>
            <form method="post" action="{{ route('metodos-pago.store') }}">
                @csrf
                <label class="form-label">Nombre</label>
                <input name="nombre" class="form-control mb-3" maxlength="40" placeholder="Ej: Bancolombia QR, Addi, Sistecrédito" required>
                <label class="form-label">Orden en el POS</label>
                <input name="orden" type="number" min="0" class="form-control mb-3" value="10">
                @if (\App\Support\TiendaActual::tienda()?->pais === 'VE')
                <label class="form-label">¿En qué moneda paga el cliente?</label>
                <select name="moneda" class="form-select mb-3" data-nativo><option value="">Dólares (US$)</option><option value="VES">Bolívares (Bs) · se pasa a dólares con la tasa del día</option></select>
                @endif
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="es_efectivo" value="1" id="ef">
                    <label class="form-check-label" for="ef">Es efectivo <small class="text-muted d-block">Cuenta en el cuadre de caja y puede dar cambio.</small></label>
                </div>
                <button class="btn btn-primary w-100"><i class="fa-solid fa-plus me-1"></i>Crear</button>
            </form>
        </div>
        @endcan

        <div class="lp-box">
            <h3>Métodos del negocio</h3>
            <ul class="lp-list">
                @foreach ($metodos as $m)
                <li>
                    <div class="av" style="{{ $m->es_efectivo ? 'background:var(--lp-grad);color:#0b0d0c' : '' }}"><i class="fa-solid {{ $m->es_efectivo ? 'fa-money-bill-wave' : 'fa-mobile-screen-button' }}"></i></div>
                    <div class="t"><b>{{ $m->nombre }}</b><small>{{ $m->es_efectivo ? 'Efectivo · cuadra en caja' : 'Electrónico' }}{{ $m->moneda === 'VES' ? ' · en bolívares' : '' }}</small></div>
                    <span class="badge {{ $m->activo ? 'bg-success' : 'bg-secondary' }}">{{ $m->activo ? 'Activo' : 'Inactivo' }}</span>
                    @can('editar-metodo-pago')
                    <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#m{{ $m->id }}"><i class="fa-solid fa-pen"></i></button>
                    @endcan
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    @can('editar-metodo-pago')
    @foreach ($metodos as $m)
    <div class="modal fade" id="m{{ $m->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('metodos-pago.update', $m) }}" style="border-radius:22px">
            @csrf @method('PUT')
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Editar método</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-0">
                <label class="form-label">Nombre</label>
                <input name="nombre" class="form-control mb-3" value="{{ $m->nombre }}" maxlength="40" required>
                <label class="form-label">Orden</label>
                <input name="orden" type="number" min="0" class="form-control mb-3" value="{{ $m->orden }}">
                @if (\App\Support\TiendaActual::tienda()?->pais === 'VE')
                <label class="form-label small fw-bold">¿En qué moneda paga el cliente?</label>
                <select name="moneda" class="form-select mb-3" data-nativo><option value="">Dólares (US$)</option><option value="VES" @selected($m->moneda === 'VES')>Bolívares (Bs) · se pasa a dólares con la tasa del día</option></select>
                @endif
                @if (!\App\Support\TiendaActual::tienda()?->pais || \App\Support\TiendaActual::tienda()->pais === 'CO')
                <label class="form-label small fw-bold">Cómo se reporta en la factura DIAN</label>
                <select name="medio_dian" class="form-select mb-3">@foreach (\App\Support\Dian\Catalogos::MEDIOS_PAGO as $k => $n)<option value="{{ $k }}" @selected(($m->medio_dian ?: 'ZZZ') === $k)>{{ $n }}</option>@endforeach</select>
                @endif
                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="es_efectivo" value="1" id="e{{ $m->id }}" @checked($m->es_efectivo)><label class="form-check-label" for="e{{ $m->id }}">Es efectivo</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" id="a{{ $m->id }}" @checked($m->activo)><label class="form-check-label" for="a{{ $m->id }}">Activo en el POS</label></div>
            </div>
            <div class="modal-footer border-0"><button class="btn btn-primary w-100">Guardar</button></div>
        </form></div>
    </div>
    @endforeach
    @endcan
</div>
@endsection
