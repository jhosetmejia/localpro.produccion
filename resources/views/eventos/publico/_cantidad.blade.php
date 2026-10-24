{{-- Cuántas entradas compra y promociones vigentes. Los acompañantes se llenan en _acompanantes. --}}
@php
    $maxC = max(1, min(20, (int) ($evento->max_por_compra ?: 1)));
    if ($evento->cupo) $maxC = max(1, min($maxC, (int) $evento->quedan));
    $promosV = $evento->promosVigentes();
@endphp
@if ($promosV)
<div class="promos">
    @foreach ($promosV as $pr)<div class="promo"><i class="fa-solid fa-tags"></i> <b>{{ $pr['texto'] }}</b>@if ($pr['hasta']) <small class="mu">· hasta el {{ \Illuminate\Support\Carbon::parse($pr['hasta'])->locale('es')->isoFormat('D [de] MMMM') }}</small>@endif</div>@endforeach
</div>
@endif
@if ($maxC > 1)
<label class="lb" for="cantidad">¿Cuántas entradas?</label>
<select class="in" id="cantidad" name="cantidad">
    @for ($i = 1; $i <= $maxC; $i++)<option value="{{ $i }}" @selected((int) old('cantidad', 1) === $i)>{{ $i }} {{ $i === 1 ? 'entrada' : 'entradas' }}</option>@endfor
</select>
<div class="cuenta" id="ev-cuenta" style="display:none"></div>
@else
<input type="hidden" name="cantidad" value="1">
@endif
