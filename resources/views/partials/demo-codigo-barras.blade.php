{{-- Demostración del código de barras: etiquetas de ejemplo (iguales a las que imprime LocalPro, CODE128)
     y una caja de prueba que las escanea con la cámara usando el mismo escáner del POS (public/js/escaner.js).
     Se usa en el blog y en Ejemplos de los asesores. Incluir una sola vez por página. --}}
@php
    $demoItems = [
        ['P011302', 'Camiseta básica algodón', 'M · Negro', 45000],
        ['P020401', 'Jean clásico tiro alto', '32 · Azul oscuro', 89000],
        ['P0315', 'Gorra bordada', 'Talla única', 35000],
    ];
@endphp
<style>
.cbd{background:#0b0d0c;color:#fff;border-radius:24px;padding:20px 18px;margin:22px 0}
.cbd h3{color:#a6f34a;font-weight:900;font-size:1.2rem;margin:0 0 6px}
.cbd > p{color:#c9d6cd;margin:0 0 14px;font-size:.95rem;line-height:1.5}
.cbd-et{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:16px}
.cbd-e{background:#fff;color:#0b0d0c;border-radius:10px;padding:10px 12px 8px;display:flex;flex-direction:column;gap:2px;font-family:Arial,Helvetica,sans-serif;box-shadow:0 6px 18px -10px rgba(0,0,0,.6)}
.cbd-e .t{font-size:.66rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#444}
.cbd-e .n{font-weight:700;font-size:.9rem;line-height:1.15}
.cbd-e .o{display:flex;justify-content:space-between;font-size:.8rem}
.cbd-e .o b{font-size:.92rem}
.cbd-e svg{width:100%;height:64px;margin-top:4px}
.cbd-caja{background:#fff;color:#151a17;border-radius:18px;padding:14px}
.cbd-caja .cab{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px}
.cbd-caja .cab b{font-size:1rem}
.cbd-btn{border:0;border-radius:999px;padding:.7rem 1.3rem;font-weight:900;font-size:1rem;cursor:pointer;background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c}
.cbd-lim{border:0;background:none;color:#6b7570;font-weight:700;cursor:pointer;font-size:.85rem}
.cbd-lista{min-height:46px}
.cbd-vacio{color:#7b857f;font-size:.9rem;padding:10px 0;text-align:center}
.cbd-row{display:flex;justify-content:space-between;align-items:center;background:#f4f8f5;border-radius:10px;padding:.5rem .7rem;margin-top:.35rem;font-weight:700;font-size:.92rem}
.cbd-row small{display:block;color:#6b7570;font-weight:600;font-size:.78rem}
.cbd-row.nuevo{animation:cbdIn .5s ease}
@keyframes cbdIn{from{background:#dcfce7}to{background:#f4f8f5}}
.cbd-tot{display:flex;justify-content:space-between;background:#0b0d0c;color:#fff;border-radius:12px;padding:.65rem .8rem;margin-top:.6rem;font-weight:900}
.cbd-tot span:last-child{color:#a6f34a}
.cbd-msg{font-size:.85rem;font-weight:700;color:#b0173f;min-height:1.2em;margin-top:6px}
.cbd-como{color:#c9d6cd;font-size:.86rem;margin:12px 0 0;line-height:1.5}
.cbd-como b{color:#fff}
</style>

<div class="cbd" id="cbdDemo">
    <h3>📷 Pruébelo: escanee estas etiquetas con la cámara del celular</h3>
    <p>Así son las etiquetas que imprime LocalPro para cada talla y color. Toque <b>Escanear con la cámara</b>, apunte a una etiqueta y verá cómo cae a la caja con su precio, como en una venta real.</p>

    <div class="cbd-et">
        @foreach ($demoItems as $d)
        <div class="cbd-e">
            <div class="t">Mi tienda</div>
            <div class="n">{{ $d[1] }}</div>
            <div class="o"><span>{{ $d[2] }}</span><b>${{ number_format($d[3], 0, ',', '.') }}</b></div>
            <svg class="cbd-bc" data-c="{{ $d[0] }}" role="img" aria-label="Código de barras {{ $d[0] }}"></svg>
        </div>
        @endforeach
    </div>

    <div class="cbd-caja">
        <div class="cab"><b>🛒 Caja de prueba</b><button type="button" class="cbd-btn" id="cbdEscanear">📷 Escanear con la cámara</button></div>
        <div class="cbd-lista" id="cbdLista"><div class="cbd-vacio">Aquí aparecen los productos que escanee.</div></div>
        <div class="cbd-tot"><span>Total</span><span id="cbdTotal">$0</span></div>
        <div class="d-flex justify-content-between align-items-center" style="display:flex;justify-content:space-between;align-items:center">
            <div class="cbd-msg" id="cbdMsg"></div>
            <button type="button" class="cbd-lim" id="cbdLimpiar">Vaciar</button>
        </div>
    </div>

    <p class="cbd-como"><b>¿Cómo probarlo?</b> Abra esta página en el celular y apunte a las etiquetas en la pantalla de un computador (o imprímalas). ¿Solo tiene el celular? Pídale a alguien que le muestre las etiquetas en su pantalla. En la tienda funciona igual con la etiqueta pegada en la prenda, y también sirve una pistola lectora.</p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('js/escaner.js') }}?v=1"></script>
<script>
(function () {
    var ITEMS = {};
    @foreach ($demoItems as $d)
    ITEMS[@json($d[0])] = { nombre: @json($d[1]), op: @json($d[2]), precio: {{ (int) $d[3] }} };
    @endforeach
    var carrito = [];
    var lista = document.getElementById('cbdLista'), total = document.getElementById('cbdTotal'), msg = document.getElementById('cbdMsg');
    function plata(v) { return '$' + Math.round(v).toLocaleString('es-CO'); }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function pintar(nuevo) {
        if (!carrito.length) { lista.innerHTML = '<div class="cbd-vacio">Aquí aparecen los productos que escanee.</div>'; total.textContent = '$0'; return; }
        lista.innerHTML = carrito.map(function (r) {
            return '<div class="cbd-row' + (r.codigo === nuevo ? ' nuevo' : '') + '"><span>' + r.cant + ' × ' + esc(r.nombre) + '<small>' + esc(r.op) + ' · ' + esc(r.codigo) + '</small></span><span>' + plata(r.precio * r.cant) + '</span></div>';
        }).join('');
        total.textContent = plata(carrito.reduce(function (s, r) { return s + r.precio * r.cant; }, 0));
    }
    function agregar(codigo) {
        codigo = String(codigo || '').trim().toUpperCase();
        var it = ITEMS[codigo];
        if (!it) { msg.textContent = 'El código ' + codigo + ' no es de este ejemplo. Escanee una de las etiquetas de arriba.'; return; }
        msg.textContent = '';
        var r = carrito.find(function (x) { return x.codigo === codigo; });
        if (r) r.cant++; else carrito.push({ codigo: codigo, nombre: it.nombre, op: it.op, precio: it.precio, cant: 1 });
        pintar(codigo);
    }
    if (window.JsBarcode) document.querySelectorAll('.cbd-bc').forEach(function (s) {
        try { JsBarcode(s, s.dataset.c, { format: 'CODE128', displayValue: true, fontSize: 13, height: 46, margin: 0, textMargin: 2 }); } catch (e) {}
    });
    document.getElementById('cbdEscanear').addEventListener('click', function () {
        if (!window.LPEscaner) { msg.textContent = 'No se pudo cargar el escáner. Revise la conexión.'; return; }
        LPEscaner.abrir({ titulo: 'Escanee una etiqueta de ejemplo', continuo: true, alLeer: agregar });
    });
    document.getElementById('cbdLimpiar').addEventListener('click', function () { carrito = []; msg.textContent = ''; pintar(); });
})();
</script>
