{{-- Tallas estándar para tocar. Uso: @include('producto._tallas', ['para' => 'id-del-input']) --}}
<div class="lp-tallas" data-para="{{ $para }}">
    @foreach (\App\Support\Tallas::GRUPOS as $grupo => $tallas)
    <div class="lp-tg">
        <button type="button" class="lp-tg-nom" data-grupo="{{ implode(',', $tallas) }}" title="Agregar todas">{{ $grupo }}</button>
        @foreach ($tallas as $t)<button type="button" data-talla="{{ $t }}">{{ $t === 'ÚNICA' ? 'Única' : $t }}</button>@endforeach
    </div>
    @endforeach
</div>
<div class="small text-muted mt-1">Toque las tallas que tiene (o el nombre del grupo para agregarlas todas). Si no está, escríbala.</div>
@once
<style>
.lp-tallas{display:flex;flex-direction:column;gap:6px;margin-top:8px}
.lp-tg{display:flex;flex-wrap:wrap;gap:5px;align-items:center}
.lp-tg button{background:#fff;border:1.5px solid #e3ebe6;border-radius:10px;min-width:38px;padding:.28rem .5rem;font-size:.8rem;font-weight:800;cursor:pointer;line-height:1.2}
.lp-tg button.on{border-color:#16b34a;background:#ecfbf0;color:#11702f;box-shadow:0 0 0 2px rgba(22,179,74,.18)}
.lp-tg .lp-tg-nom{background:transparent;border:0;min-width:74px;text-align:left;color:#6b7a71;font-weight:700;padding-left:0;text-decoration:underline dotted}
</style>
<script>
window.LPTallas = (function () {
    var S = @json(\App\Support\Tallas::SINONIMOS), G = @json(\App\Support\Tallas::GRUPOS), orden = [];
    Object.keys(G).forEach(function (g) { G[g].forEach(function (t) { if (orden.indexOf(t) === -1) orden.push(t); }); });
    var sinTilde = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    function normalizar(t) {
        t = String(t || '').trim().replace(/\s+/g, ' ').toUpperCase().replace(/^(TALLA|TALL|T\.)\s*(?=\S)/, '');
        if (!t) return '';
        var k = sinTilde(t);
        if (k === 'UNICA' || k === 'TALLA UNICA') return 'ÚNICA';
        return S[k] || t;
    }
    function lista(txt) {
        var vistos = {};
        return String(txt || '').split(/\s*(?:[,;\/\n]|\s+y\s+)\s*/i).map(normalizar)
            .filter(function (x) { if (!x || vistos[x]) return false; vistos[x] = 1; return true; });
    }
    function ordenar(l) {
        return l.slice().sort(function (a, b) {
            var ia = orden.indexOf(a), ib = orden.indexOf(b);
            if (ia === -1 && ib === -1) return 0;
            if (ia === -1) return 1; if (ib === -1) return -1;
            // Números en orden numérico; letras en su orden XS…XXXL
            if (/^\d+$/.test(a) && /^\d+$/.test(b)) return +a - +b;
            return ia - ib;
        });
    }
    function texto(l) { return l.map(function (t) { return t === 'ÚNICA' ? 'Única' : t; }).join(', '); }
    function iniciar(caja) {
        var input = document.getElementById(caja.dataset.para); if (!input) return;
        function marcar() {
            var l = lista(input.value);
            caja.querySelectorAll('[data-talla]').forEach(function (b) { b.classList.toggle('on', l.indexOf(b.dataset.talla) !== -1); });
        }
        function poner(l) { input.value = texto(ordenar(l)); input.dispatchEvent(new Event('input', { bubbles: true })); marcar(); }
        caja.addEventListener('click', function (e) {
            var b = e.target.closest('[data-talla]'), g = e.target.closest('[data-grupo]'), l = lista(input.value);
            if (b) { var i = l.indexOf(b.dataset.talla); if (i === -1) l.push(b.dataset.talla); else l.splice(i, 1); poner(l); }
            if (g) {
                var ts = g.dataset.grupo.split(','), todas = ts.every(function (t) { return l.indexOf(t) !== -1; });
                l = todas ? l.filter(function (t) { return ts.indexOf(t) === -1; }) : l.concat(ts.filter(function (t) { return l.indexOf(t) === -1; }));
                poner(l);
            }
        });
        input.addEventListener('input', marcar);
        input.addEventListener('blur', function () {
            var nuevo = texto(lista(input.value));
            if (nuevo !== input.value.trim()) { input.value = nuevo; input.dispatchEvent(new Event('input', { bubbles: true })); }
        });
        marcar();
    }
    document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('.lp-tallas').forEach(iniciar); });
    return { normalizar: normalizar, lista: lista };
})();
</script>
@endonce
