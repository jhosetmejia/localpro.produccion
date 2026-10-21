@php
    $antesTx = $p->precioAntesTexto($tienda);
    $promo = $antesTx && \App\Models\Plan::promoVigente();
    $diasPromo = \App\Models\Plan::promoDiasRestantes();
    $hastaPromo = \Carbon\Carbon::parse(\App\Models\Plan::PROMO_HASTA)->locale('es');
@endphp
@if ($promo)
<div class="lp-promo-fin">
    <i class="fa-solid fa-clock"></i>
    <div><b>Precio especial solo hasta el {{ $hastaPromo->isoFormat('D [de] MMMM') }}</b>{{ $diasPromo > 0 ? ' (quedan ' . $diasPromo . ' días)' : ' (¡último día!)' }}.<br>Desde el 1 de {{ $hastaPromo->copy()->addDay()->isoFormat('MMMM') }} el plan pasa a {{ $antesTx }}. ¡Asegúrelo ya!</div>
</div>
@endif
