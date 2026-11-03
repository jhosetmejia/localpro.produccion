<div class="cab">
    @if ($t->logo_url)<img src="{{ $t->logo_url }}" alt="">@else<div class="ini">{{ mb_strtoupper(mb_substr($t->nombre, 0, 1)) }}</div>@endif
    <div><h1>{{ $t->nombre }}</h1><small><i class="fa-solid fa-location-dot"></i> {{ trim(implode(' · ', array_filter([$t->direccion, $t->ciudad]))) ?: 'Consultorio' }}</small></div>
</div>
