{{-- Ayuda para que el nombre del producto salga bien en Google. Va debajo del campo "nombre". --}}
@php
    $tSeo = \App\Support\TiendaActual::tienda();
    $sufijoSeo = ' | ' . ($tSeo->nombre ?? '') . ($tSeo?->ciudad ? ', ' . $tSeo->ciudad : '');
@endphp
<div class="lp-seo" id="lpSeo">
    <div class="lp-seo-g">
        <small>Así puede salir en Google</small>
        <div class="t" id="seoT"></div>
        <div class="u">{{ preg_replace('#^https?://#', '', $tSeo?->url_catalogo ?? url('/')) }} › …</div>
    </div>
    <div class="lp-seo-tip" id="seoTip"></div>
</div>
@once
<style>
.lp-seo{margin-top:.5rem;border:1.5px solid #e3ebe6;border-radius:14px;padding:.6rem .8rem;background:#fff}
.lp-seo-g small{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#8a948e}
.lp-seo-g .t{color:#1a0dab;font-size:1.02rem;line-height:1.3;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:Arial,sans-serif}
.lp-seo-g .u{color:#188038;font-size:.78rem;font-family:Arial,sans-serif}
.lp-seo-tip{margin-top:.4rem;font-size:.8rem;font-weight:700}
.lp-seo-tip.ok{color:#11702f}.lp-seo-tip.mal{color:#b45309}
</style>
@endonce
<script>
(function () {
    var inp = document.getElementById('nombre'), t = document.getElementById('seoT'), tip = document.getElementById('seoTip');
    if (!inp) return;
    var sufijo = @json($sufijoSeo);
    function bonito(s) {
        s = s.trim().replace(/\s+/g, ' ');
        if (s && (s === s.toUpperCase() || s === s.toLowerCase())) {
            s = s.toLowerCase().split(' ').map(function (p) { return p.length <= 2 && /[a-z]/.test(p) && !/^(y|o|e|u|a|de|la|el|en|al|lo|su)$/.test(p) && inp.value === inp.value.toUpperCase() ? p.toUpperCase() : p; }).join(' ');
        }
        return s.charAt(0).toUpperCase() + s.slice(1);
    }
    function revisar() {
        var v = inp.value.trim(), palabras = v ? v.split(/\s+/).length : 0, consejos = [];
        t.textContent = (v ? bonito(v) : 'Nombre del producto') + sufijo;
        if (!v) { tip.textContent = ''; return; }
        if (palabras < 3) consejos.push('Muy corto: diga qué es + un detalle + para quién. Ej: "Chaqueta de cuero café para dama".');
        if (v.length > 70) consejos.push('Muy largo: Google lo corta. Mejor menos de 60 letras.');
        if (/[a-záéíóúñ]/i.test(v) && v === v.toUpperCase() && v.length > 6) consejos.push('Escríbalo normal, no todo en MAYÚSCULAS (en la tienda se arregla solo).');
        if (/\s(XXX?L|XS|S|M|L|XL|\d{1,2})$/i.test(v)) consejos.push('No ponga la talla al final del nombre: va en Tallas.');
        if (/[|]|,.*,/.test(v)) consejos.push('Evite "|" y listas con comas; use un nombre corto y claro.');
        tip.className = 'lp-seo-tip ' + (consejos.length ? 'mal' : 'ok');
        tip.textContent = consejos.length ? '💡 ' + consejos[0] : '✓ Buen nombre para que lo encuentren en Google.';
    }
    inp.addEventListener('input', revisar);
    revisar();
})();
</script>
