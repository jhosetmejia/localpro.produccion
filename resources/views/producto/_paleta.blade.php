{{-- Paleta de colores estándar. Uso: @include('producto._paleta', ['para' => 'id-del-input']) --}}
<div class="lp-paleta" data-para="{{ $para }}">
    @foreach (\App\Support\Colores::PALETA as $nombre => $fondo)
    <button type="button" data-color="{{ $nombre }}" title="{{ ucfirst(mb_strtolower($nombre)) }}"><i style="background:{{ $fondo }}"></i>{{ ucfirst(mb_strtolower($nombre)) }}</button>
    @endforeach
</div>
<div class="small text-muted mt-1">Toque los colores que tiene. Si no está en la lista, escríbalo.</div>
@once
<style>
.lp-paleta{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.lp-paleta button{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid #e3ebe6;border-radius:999px;padding:.28rem .7rem .28rem .35rem;font-size:.8rem;font-weight:700;cursor:pointer;line-height:1.2}
.lp-paleta button i{width:18px;height:18px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:none}
.lp-paleta button.on{border-color:#16b34a;background:#ecfbf0;box-shadow:0 0 0 2px rgba(22,179,74,.18)}
.lp-paleta button.on::after{content:"✓";color:#16b34a;font-weight:900}
</style>
<script>
window.LPColores = (function () {
    var S = @json(\App\Support\Colores::SINONIMOS), P = Object.keys(@json(\App\Support\Colores::PALETA));
    var sinTilde = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    function normalizar(c) {
        c = String(c || '').trim().replace(/\s+/g, ' ').toUpperCase();
        if (!c) return '';
        var k = sinTilde(c);
        if (S[k]) return S[k];
        for (var i = 0; i < P.length; i++) if (sinTilde(P[i]) === k) return P[i];
        return c;
    }
    function lista(t) {
        var vistos = {};
        return String(t || '').split(/\s*(?:[,;\/\n]|\s+y\s+)\s*/i).map(normalizar)
            .filter(function (x) { if (!x || vistos[x]) return false; vistos[x] = 1; return true; });
    }
    function bonito(c) { return c.charAt(0) + c.slice(1).toLowerCase(); }
    function iniciar(caja) {
        var input = document.getElementById(caja.dataset.para); if (!input) return;
        function marcar() {
            var l = lista(input.value);
            caja.querySelectorAll('[data-color]').forEach(function (b) { b.classList.toggle('on', l.indexOf(b.dataset.color) !== -1); });
        }
        caja.addEventListener('click', function (e) {
            var b = e.target.closest('[data-color]'); if (!b) return;
            var l = lista(input.value), i = l.indexOf(b.dataset.color);
            if (i === -1) l.push(b.dataset.color); else l.splice(i, 1);
            input.value = l.map(bonito).join(', ');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            marcar();
        });
        input.addEventListener('input', marcar);
        // Al salir del campo se deja escrito como en la paleta (negra → Negro)
        input.addEventListener('blur', function () {
            var nuevo = lista(input.value).map(bonito).join(', ');
            if (nuevo !== input.value.trim()) { input.value = nuevo; input.dispatchEvent(new Event('input', { bubbles: true })); }
        });
        marcar();
    }
    document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('.lp-paleta').forEach(iniciar); });
    return { normalizar: normalizar, lista: lista };
})();
</script>
@endonce
