<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiquetas · {{ $titulo }}</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#eef4ef;color:#000}
  .tool{max-width:900px;margin:16px auto;background:#fff;border-radius:18px;padding:16px;font-size:14px}
  .tool h1{font-size:18px;margin-bottom:10px}
  .tool table{width:100%;border-collapse:collapse}
  .tool td{padding:6px 4px;border-bottom:1px solid #e3efe5}
  .tool input[type=number]{width:70px;padding:6px;border:1.5px solid #d6e5d9;border-radius:8px}
  .tool .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
  .tool select{padding:8px;border-radius:10px;border:1.5px solid #d6e5d9}
  .tool button,.tool a{border:0;border-radius:999px;padding:10px 18px;font-weight:700;cursor:pointer;text-decoration:none;font-size:14px}
  .p{background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c}.d{background:#0b0d0c;color:#fff}
  #hoja{display:flex;flex-wrap:wrap;gap:0;justify-content:center;margin:0 auto 30px}
  .et{width:50mm;height:25mm;padding:1.2mm 2mm;background:#fff;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;border:1px dashed #c9d8cc;page-break-inside:avoid}
  .et .n{font-size:7.5pt;font-weight:700;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .et .o{font-size:7pt;display:flex;justify-content:space-between}
  .et .pr{font-weight:800;font-size:8.5pt}
  .et svg{width:100%;height:11mm}
  body.a4 #hoja{width:210mm;justify-content:flex-start;padding:8mm 5mm}
  body.a4 .et{width:66mm;height:33mm}
  body.a4 .et .n{font-size:9pt}body.a4 .et svg{height:15mm}
  @media print{
    body{background:#fff}.tool{display:none}
    .et{border:0}
    @page{margin:0}
    body:not(.a4) .et{page-break-after:always}
  }
</style>
</head>
<body>
<div class="tool">
    <h1>🏷️ Etiquetas · {{ $titulo }}</h1>
    <table>
        @foreach ($items as $k => $it)
        <tr><td><b>{{ $it['v']->producto->nombre }}</b> {{ $it['v']->etiqueta !== 'Única' ? '· ' . $it['v']->etiqueta : '' }}<br><small>{{ $it['v']->codigo_barras }}</small></td><td style="text-align:right"><input type="number" min="0" max="500" value="{{ $it['cant'] }}" data-k="{{ $k }}" class="cant"> etiquetas</td></tr>
        @endforeach
    </table>
    <div class="row">
        <label>Papel: <select id="papel"><option value="rollo">Rollo 50 × 25 mm (impresora de etiquetas)</option><option value="a4">Hoja carta/A4 (3 columnas)</option></select></label>
        <label><input type="checkbox" id="conPrecio" checked> Mostrar precio</label>
        <span style="flex:1"></span>
        <button class="p" onclick="window.print()">🖨 Imprimir</button>
        <a class="d" href="javascript:history.back()">Volver</a>
    </div>
</div>
<div id="hoja"></div>

<script>
(function () {
    @php
        $itemsJs = $items->map(fn ($it) => [
            'nombre' => $it['v']->producto->nombre,
            'op' => $it['v']->etiqueta !== 'Única' ? $it['v']->etiqueta : '',
            'codigo' => $it['v']->codigo_barras,
            'precio' => dinero($it['v']->precio_final),
        ])->values();
    @endphp
    var ITEMS = @json($itemsJs);
    var TIENDA = @json($negocio->nombre);
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    function pintar() {
        document.body.classList.toggle('a4', document.getElementById('papel').value === 'a4');
        var conPrecio = document.getElementById('conPrecio').checked, html = '', n = 0;
        document.querySelectorAll('.cant').forEach(function (inp) {
            var it = ITEMS[+inp.dataset.k], c = Math.min(500, +inp.value || 0);
            for (var i = 0; i < c; i++) {
                html += '<div class="et"><div class="n">' + esc(it.nombre) + '</div><div class="o"><span>' + esc(it.op) + '</span>' + (conPrecio ? '<span class="pr">' + esc(it.precio) + '</span>' : '') + '</div><svg class="bc" data-c="' + esc(it.codigo) + '"></svg></div>';
                n++;
            }
        });
        document.getElementById('hoja').innerHTML = html;
        if (window.JsBarcode) document.querySelectorAll('.bc').forEach(function (s) { JsBarcode(s, s.dataset.c, { format: 'CODE128', displayValue: true, fontSize: 11, height: 40, margin: 0, textMargin: 0 }); });
    }
    document.querySelectorAll('.cant, #papel, #conPrecio').forEach(function (el) { el.addEventListener('input', pintar); el.addEventListener('change', pintar); });
    pintar();
})();
</script>
</body>
</html>
