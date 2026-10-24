{{-- Datos de cada acompañante (persona 2, 3…): cada uno recibe su propia entrada con QR --}}
@if (max(1, (int) ($evento->max_por_compra ?: 1)) > 1)
<div id="ev-acomp"></div>
<template id="ev-acomp-t">
    <div class="persona">
        <div class="pt"><i class="fa-solid fa-user"></i> Persona __N__</div>
        <label class="lb">Nombre completo</label>
        <input class="in" name="acompanantes[__N__][nombre]" maxlength="120" required>
        <label class="lb">Documento</label>
        <div class="dos">
            <select class="in" name="acompanantes[__N__][tipo_documento]" aria-label="Tipo de documento">
                @foreach (\App\Models\EventoAsistente::DOCUMENTOS as $k => $n)<option value="{{ $k }}">{{ $k === 'CC' ? 'Cédula' : ($k === 'TI' ? 'Tarjeta de identidad' : $n) }}</option>@endforeach
            </select>
            <input class="in" name="acompanantes[__N__][documento]" inputmode="numeric" placeholder="Número" maxlength="20" required aria-label="Número de documento">
        </div>
        <label class="lb">Correo <small class="mu" style="font-weight:500">(opcional)</small></label>
        <input class="in" type="email" name="acompanantes[__N__][email]" maxlength="150" inputmode="email">
    </div>
</template>
<script>
(function () {
    var sel = document.getElementById('cantidad'), cont = document.getElementById('ev-acomp'), tpl = document.getElementById('ev-acomp-t'), cuenta = document.getElementById('ev-cuenta');
    if (!sel || !cont || !tpl) return;
    var viejos = @json(old('acompanantes', []));
    var PRECIO = {{ (float) $evento->precio }}, PROMOS = @json($evento->promosVigentes());
    function plata(v) { return '$' + Math.round(v).toLocaleString('es-CO'); }
    function cotizar(n) {
        var mejor = { total: PRECIO * n, ahorro: 0, promo: null };
        PROMOS.forEach(function (p) {
            var b = p.compra + 1, t = 0;
            for (var i = 1; i <= n; i++) t += (i % b === 0) ? PRECIO * (100 - p.descuento) / 100 : PRECIO;
            if (PRECIO * n - t > mejor.ahorro) mejor = { total: t, ahorro: PRECIO * n - t, promo: p.texto };
        });
        return mejor;
    }
    function pintar() {
        var n = parseInt(sel.value, 10) || 1, guard = {};
        cont.querySelectorAll('input,select').forEach(function (i) { guard[i.name] = i.value; });
        cont.innerHTML = '';
        for (var k = 2; k <= n; k++) {
            var d = document.createElement('div');
            d.innerHTML = tpl.innerHTML.replace(/__N__/g, k);
            d.querySelectorAll('input,select').forEach(function (i) {
                var m = i.name.match(/\[(\d+)\]\[(\w+)\]/);
                if (guard[i.name] !== undefined) i.value = guard[i.name];
                else if (m && viejos[m[1]] && viejos[m[1]][m[2]] !== undefined) i.value = viejos[m[1]][m[2]];
            });
            cont.appendChild(d.firstElementChild);
        }
        if (cuenta) {
            var c = cotizar(n);
            cuenta.style.display = n > 1 || c.promo ? '' : 'none';
            cuenta.innerHTML = '<span>' + n + (n === 1 ? ' entrada' : ' entradas') + '</span><b>' + plata(c.total) + '</b>'
                + (c.promo ? '<small><i class="fa-solid fa-tags"></i> ' + c.promo + ' · ahorras ' + plata(c.ahorro) + '</small>' : '');
        }
    }
    sel.addEventListener('change', pintar);
    pintar();
})();
</script>
@endif
