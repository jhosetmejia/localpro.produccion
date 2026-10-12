@php
    $tImp = \App\Support\TiendaActual::tienda();
    $impActual = old('impuesto', isset($producto) ? ($producto->impuesto ?: 'ninguno') : \App\Support\Dian\Facturador::impuestoPorDefecto($tImp));
@endphp
@if (!$tImp?->pais || $tImp->pais === 'CO')
<div class="{{ $col ?? 'col-12' }}">
    <label for="impuesto" class="form-label">Impuesto <small class="text-muted fw-normal">(el precio ya lo incluye)</small></label>
    <select name="impuesto" id="impuesto" class="form-select">
        @foreach (\App\Support\Dian\Catalogos::IMPUESTOS as $k => $i)<option value="{{ $k }}" @selected($impActual === $k)>{{ $i[0] }}</option>@endforeach
    </select>
    <small class="text-muted">Si no es responsable de IVA deje "Sin impuesto". En la factura el IVA sale separado del precio.</small>
    @error('impuesto')<small class="text-danger d-block">{{ $message }}</small>@enderror
</div>
@endif
