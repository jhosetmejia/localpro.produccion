{{-- Donación opcional junto al pago del plan (campaña de Navidad). Se maneja en Admin → Donaciones. --}}
@if (\App\Support\Donacion::activa())
@php $dm = \App\Support\Donacion::monto(); $did = 'don-' . ($p->id ?? uniqid()); @endphp
<label for="{{ $did }}" class="d-flex gap-2 align-items-start text-start my-2 p-2 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;cursor:pointer;font-size:.86rem;line-height:1.35;color:#14532d">
    <input type="checkbox" name="donar" value="1" id="{{ $did }}" class="form-check-input flex-shrink-0 mt-1" style="width:1.15rem;height:1.15rem" @checked(\App\Support\Donacion::marcada())>
    <span><b style="color:#15803d">🎁 Dona {{ \App\Support\Moneda::formato($dm, 'COP', true) }}</b> para recolectar ayudas para los niños más necesitados en diciembre. Junto a LocalProo realizaremos esta labor en los barrios de Pasto, Nariño.
        <a href="{{ route('donaciones') }}" target="_blank" rel="noopener" style="color:#15803d;font-weight:700" onclick="event.stopPropagation()">Más info</a>
        <small class="d-block" style="color:#4d7c5d">Se suma al valor del plan. Si no quiere donar, desmarque la casilla.</small></span>
</label>
@endif
