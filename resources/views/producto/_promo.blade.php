{{-- Promoción / liquidación: precio antes → precio ahora. Sale solo en la tienda en línea como oferta. --}}
@php
    $antesVal = old('precio_antes', isset($producto) && $producto->precio_antes ? (float) $producto->precio_antes : '');
    $enPromo = old('en_promo', $antesVal !== '' && $antesVal !== null ? '1' : '') === '1';
@endphp
<div class="{{ $col ?? 'col-12' }}">
    <div class="lp-promo {{ $enPromo ? 'on' : '' }}" id="lpPromo">
        <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" id="enPromo" name="en_promo" value="1" @checked($enPromo)>
            <label class="form-check-label fw-bold" for="enPromo">🔥 Está en promoción o liquidación</label>
        </div>
        <div class="lp-promo-body" @if (!$enPromo) hidden @endif>
            <label class="form-label small fw-bold mt-2 mb-1" for="precio_antes">Precio antes ({{ moneda()['moneda'] }})</label>
            <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_antes" id="precio_antes" class="form-control" value="{{ $antesVal }}" placeholder="Ej: {{ moneda()['decimales'] ? '39.90' : '150000' }}" inputmode="decimal" @disabled(!$enPromo)>
            <small class="d-block mt-1" id="promoTxt">El <b>precio de venta</b> de arriba es el precio de ahora (el rebajado).</small>
            @error('precio_antes')<small class="text-danger d-block">{{ $message }}</small>@enderror
        </div>
    </div>
</div>
@once
<style>
.lp-promo{border:1.5px dashed #e3ebe6;border-radius:14px;padding:.7rem .9rem;margin-top:.6rem}
.lp-promo.on{border-color:#f59e0b;background:#fffbeb}
</style>
@endonce
@push('js')
<script>
(function () {
    var sw = document.getElementById('enPromo'), caja = document.getElementById('lpPromo'), cuerpo = caja.querySelector('.lp-promo-body');
    var antes = document.getElementById('precio_antes'), venta = document.getElementById('precio_venta'), txt = document.getElementById('promoTxt');
    var base = txt.innerHTML;
    function fmt(n) { return (window.LPC && LPC.fmt) ? LPC.fmt(n) : '$' + Math.round(n).toLocaleString('es-CO'); }
    function pintar() {
        caja.classList.toggle('on', sw.checked); cuerpo.hidden = !sw.checked; antes.disabled = !sw.checked;
        var a = parseFloat(antes.value) || 0, v = parseFloat(venta.value) || 0;
        if (!sw.checked || !a) { txt.innerHTML = base; txt.style.color = ''; return; }
        if (a <= v) { txt.innerHTML = 'El precio antes debe ser <b>mayor</b> que el precio de venta (' + fmt(v) + ').'; txt.style.color = '#b42318'; return; }
        txt.innerHTML = 'En la tienda saldrá: <s>' + fmt(a) + '</s> <b>' + fmt(v) + '</b> · <b style="color:#b42318">-' + Math.round((1 - v / a) * 100) + '%</b>';
        txt.style.color = '';
    }
    sw.addEventListener('change', function () { pintar(); if (sw.checked) antes.focus(); });
    antes.addEventListener('input', pintar); venta.addEventListener('input', pintar);
    pintar();
})();
</script>
@endpush
