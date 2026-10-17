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
  .filtros{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;margin:6px 0 12px}
  .filtros label{display:flex;flex-direction:column;gap:3px;font-weight:700;font-size:12px;color:#55605a}
  .filtros select,.filtros input[type=search]{padding:8px;border-radius:10px;border:1.5px solid #d6e5d9;font-size:14px;width:100%}
  .reco{background:#f4fcf6;border:1.5px solid #cdeed6;border-radius:14px;padding:10px 14px;margin:10px 0;font-size:13px;line-height:1.5}
  .reco b{color:#11702f}
  .reco ul{margin:4px 0 0 18px}
  .resumen{display:flex;gap:10px;flex-wrap:wrap;align-items:center;font-weight:700;margin-top:8px}
  .resumen span{background:#0b0d0c;color:#a6f34a;border-radius:999px;padding:4px 12px}
  .lista{max-height:320px;overflow:auto;border:1px solid #e3efe5;border-radius:12px;margin-top:8px}
  .chip{background:#f1f5f2!important;color:#0b0d0c!important;padding:7px 12px!important;font-size:13px!important}
  .p{background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c}.d{background:#0b0d0c;color:#fff}
  /* Hoja: en carta/A4 las etiquetas se acomodan juntas para gastar menos papel */
  #hoja{display:grid;gap:0;justify-content:center;margin:0 auto 30px;background:#fff}
  .et{width:50mm;height:25mm;padding:1.2mm 2mm;background:#fff;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;border:.2mm dashed #c9d8cc;break-inside:avoid;page-break-inside:avoid}
  .et .n{font-size:7.5pt;font-weight:700;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .et .o{font-size:6.8pt;display:flex;justify-content:space-between;gap:4px;white-space:nowrap}
  .et .o span:first-child{overflow:hidden;text-overflow:ellipsis}
  .et .pr{font-weight:800;font-size:8.5pt}
  .et svg{width:100%;height:11mm;flex:1 1 auto;min-height:6mm}
  .et .t{font-size:6pt;font-weight:800;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .et .ds{font-size:6.2pt;line-height:1.1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#222}
  .campos{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;padding:10px 12px;background:#f7faf8;border:1.5px solid #e3efe5;border-radius:14px;align-items:center}
  .campos b{font-size:13px;margin-right:4px}
  .campos label{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1.5px solid #d6e5d9;border-radius:999px;padding:6px 12px;font-weight:700;font-size:13px;cursor:pointer;user-select:none}
  .campos label:has(input:checked){border-color:#16b34a;background:#ecfaef;color:#11702f}
  .campos input{accent-color:#16b34a}
  body.rollo #hoja{grid-template-columns:50mm;gap:3mm}
  body.c4 #hoja{width:200mm;grid-template-columns:repeat(4,50mm)}
  body.c3 #hoja{width:198mm;grid-template-columns:repeat(3,66mm)}
  body.c3 .et{width:66mm;height:33mm}
  body.c3 .et .n{font-size:9pt}body.c3 .et svg{height:15mm}
  @media print{
    body{background:#fff}.tool,#avisoPdf{display:none!important}
    #hoja{margin:0}
    body.rollo #hoja{gap:0}
    body.rollo .et{border:0;break-after:page;page-break-after:always}
    body.rollo .et:last-child{break-after:auto;page-break-after:auto}
  }
</style>
</head>
<body>
<div class="tool">
    <h1>🏷️ Etiquetas · {{ $titulo }}</h1>
    @if (!empty($todas))
    <p style="color:#55605a;margin-bottom:6px">Puede imprimir las etiquetas de toda la tienda, o solo de una categoría o de un grupo. Elija y toque <b>Imprimir</b> o <b>Descargar PDF</b>.</p>
    <form method="get" class="filtros" id="fFiltros">
        <label>Categoría (línea)
            <select name="categoria" onchange="this.form.submit()"><option value="0">Todas</option>@foreach ($categorias as $c)<option value="{{ $c->id }}" @selected($f['categoria'] == $c->id)>{{ $c->caracteristica->nombre }}</option>@endforeach</select>
        </label>
        @if (\App\Support\TiendaActual::tienda()?->usa_tallas)
        <label>Para quién
            <select name="para" onchange="this.form.submit()"><option value="">Todos</option>@foreach (\App\Models\Producto::PARA as $k => [$ico, $nom])<option value="{{ $k }}" @selected($f['para'] === $k)>{{ $nom }}</option>@endforeach</select>
        </label>
        @endif
        <label>Cuántas de cada una
            <select name="cant" onchange="this.form.submit()"><option value="una" @selected($f['cant'] === 'una')>1 por cada talla/color</option><option value="stock" @selected($f['cant'] === 'stock')>Según existencias (una por prenda)</option></select>
        </label>
        <label>Mostrar
            <select name="stock" onchange="this.form.submit()"><option value="1" @selected($f['stock'])>Solo lo que hay en existencia</option><option value="0" @selected(!$f['stock'])>Todo, aunque esté agotado</option></select>
        </label>
        <label>Buscar producto
            <input type="search" name="q" value="{{ $f['q'] }}" placeholder="Nombre o código" onkeydown="if(event.key==='Enter'){this.form.submit()}">
        </label>
    </form>
    <div class="reco">
        <b>Recomendaciones para imprimir</b>
        <ul>
            <li><b>Impresora de etiquetas (rollo 50 × 25 mm):</b> elija "Rollo" abajo. En la ventana de imprimir ponga márgenes "Ninguno" y escala 100 %.</li>
            <li><b>Impresora normal:</b> elija "Hoja carta/A4 · 40 por hoja" (ahorra papel) y use papel adhesivo de etiquetas, o papel normal y recórtelas por las líneas.</li>
            <li><b>Para guardar en PDF:</b> toque "Descargar PDF" y en la ventana elija <b>"Guardar como PDF"</b>.</li>
            <li>Pegue la etiqueta donde no se dañe (en el marquillado o con un gancho). Luego puede venderlas escaneándolas con la cámara del celular en el POS.</li>
        </ul>
    </div>
    <div class="resumen">Referencias: <span>{{ $items->count() }}</span> Etiquetas a imprimir: <span id="totEt">0</span>
        <button type="button" class="chip" onclick="document.querySelectorAll('.cant').forEach(function(i){i.value=1});document.querySelector('.cant')&&document.querySelector('.cant').dispatchEvent(new Event('input'))">1 de cada una</button>
        <button type="button" class="chip" onclick="document.querySelectorAll('.cant').forEach(function(i){i.value=0});document.querySelector('.cant')&&document.querySelector('.cant').dispatchEvent(new Event('input'))">Ninguna</button>
    </div>
    @if (!empty($limite))<p style="color:#b45309;font-weight:700;margin-top:6px">Se muestran las primeras 1.500 referencias. Use los filtros para imprimir por partes.</p>@endif
    @if ($items->isEmpty())<p style="margin-top:10px;font-weight:700">No hay productos con ese filtro.</p>@endif
    <div class="lista">
    @endif
    <table>
        @foreach ($items as $k => $it)
        <tr><td><b>{{ $it['v']->producto->nombre }}</b> {{ $it['v']->etiqueta !== 'Única' ? '· ' . $it['v']->etiqueta : '' }}<br><small>{{ $it['v']->codigo_barras }}</small></td><td style="text-align:right"><input type="number" min="0" max="500" value="{{ $it['cant'] }}" data-k="{{ $k }}" class="cant"> etiquetas</td></tr>
        @endforeach
    </table>
    @if (!empty($todas))</div>@endif
    <div class="campos" id="campos">
        <b>¿Qué lleva la etiqueta?</b>
        <label><input type="checkbox" data-campo="nombre" checked> Nombre</label>
        <label><input type="checkbox" data-campo="precio" checked> Precio</label>
        <label><input type="checkbox" data-campo="ref" checked> Código del producto</label>
        <label><input type="checkbox" data-campo="op" checked> Talla / color</label>
        <label><input type="checkbox" data-campo="numero" checked> Número bajo las barras</label>
        <label><input type="checkbox" data-campo="desc"> Descripción</label>
        <label><input type="checkbox" data-campo="tienda"> Nombre del negocio</label>
    </div>
    <div class="row">
        <label>Papel: <select id="papel"><option value="c4">Hoja carta/A4 · 40 por hoja (4 × 10)</option><option value="c3">Hoja carta/A4 · 24 por hoja, más grandes (3 × 8)</option><option value="rollo">Rollo 50 × 25 mm (impresora de etiquetas)</option></select></label>
        <span style="flex:1"></span>
        <button class="p" onclick="window.print()">🖨 Imprimir</button>
        <button class="d" onclick="alert_pdf()">⬇ Descargar PDF</button>
        <a class="d" style="background:#f1f5f2;color:#0b0d0c" href="{{ !empty($todas) ? route('productos.index') : 'javascript:history.back()' }}">Volver</a>
    </div>
</div>
<div id="hoja"></div>
<div id="avisoPdf" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:50;align-items:center;justify-content:center"><div style="background:#fff;border-radius:18px;padding:18px 22px;max-width:340px;text-align:center;font-weight:700">En la ventana que se abre, en <b>Destino</b> elija <b>"Guardar como PDF"</b> y toque Guardar.</div></div>

<script>
(function () {
    @php
        $itemsJs = $items->map(fn ($it) => [
            'nombre' => $it['v']->producto->nombre,
            'ref' => $it['v']->producto->codigo,
            'op' => $it['v']->etiqueta !== 'Única' ? $it['v']->etiqueta : '',
            'codigo' => $it['v']->codigo_barras,
            'precio' => dinero($it['v']->precio_final),
            'desc' => (string) $it['v']->producto->descripcion,
        ])->values();
    @endphp
    var ITEMS = @json($itemsJs);
    var TIENDA = @json($negocio->nombre);
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    var estiloPagina = document.createElement('style'); document.head.appendChild(estiloPagina);
    try { var guardado = localStorage.getItem('lp_etq_papel'); if (guardado) document.getElementById('papel').value = guardado; } catch (e) {}
    // Lo que el negocio elige que salga en la etiqueta (se recuerda en este equipo)
    try { var gc = JSON.parse(localStorage.getItem('lp_etq_campos') || 'null'); if (gc) document.querySelectorAll('[data-campo]').forEach(function (i) { if (i.dataset.campo in gc) i.checked = !!gc[i.dataset.campo]; }); } catch (e) {}
    function campos() {
        var c = {}; document.querySelectorAll('[data-campo]').forEach(function (i) { c[i.dataset.campo] = i.checked; });
        try { localStorage.setItem('lp_etq_campos', JSON.stringify(c)); } catch (e) {}
        return c;
    }
    function pintar() {
        var papel = document.getElementById('papel').value || 'c4';
        document.body.classList.remove('rollo', 'c4', 'c3', 'a4');
        document.body.classList.add(papel);
        // Tamaño de página: en rollo cada página es una etiqueta; en carta, hoja carta con margen pequeño
        estiloPagina.textContent = papel === 'rollo' ? '@page{size:50mm 25mm;margin:0}' : '@page{size:letter;margin:8mm 7mm}';
        try { localStorage.setItem('lp_etq_papel', papel); } catch (e) {}
        var C = campos(), html = '', n = 0;
        document.querySelectorAll('.cant').forEach(function (inp) {
            var it = ITEMS[+inp.dataset.k], c = Math.min(500, +inp.value || 0);
            for (var i = 0; i < c; i++) {
                var linea = [C.ref ? it.ref : '', C.op ? it.op : ''].filter(Boolean).join(' · ');
                html += '<div class="et">'
                    + (C.tienda ? '<div class="t">' + esc(TIENDA) + '</div>' : '')
                    + (C.nombre ? '<div class="n">' + esc(it.nombre) + '</div>' : '')
                    + (C.desc && it.desc ? '<div class="ds">' + esc(it.desc) + '</div>' : '')
                    + (linea || C.precio ? '<div class="o"><span>' + esc(linea) + '</span>' + (C.precio ? '<span class="pr">' + esc(it.precio) + '</span>' : '') + '</div>' : '')
                    + '<svg class="bc" data-c="' + esc(it.codigo) + '"></svg></div>';
                n++;
            }
        });
        document.getElementById('hoja').innerHTML = html;
        if (window.JsBarcode) document.querySelectorAll('.bc').forEach(function (s) { JsBarcode(s, s.dataset.c, { format: 'CODE128', displayValue: C.numero, fontSize: 11, height: 40, margin: 0, textMargin: 0 }); });
    }
    var tot = document.getElementById('totEt');
    var pintarBase = pintar;
    pintar = function () { pintarBase(); if (tot) tot.textContent = document.querySelectorAll('#hoja .et').length; };
    window.alert_pdf = function () {
        var m = document.getElementById('avisoPdf'); if (m) m.style.display = 'flex';
        setTimeout(function () { if (m) m.style.display = 'none'; window.print(); }, 1600);
    };
    document.querySelectorAll('.cant, #papel, [data-campo]').forEach(function (el) { el.addEventListener('input', pintar); el.addEventListener('change', pintar); });
    pintar();
})();
</script>
</body>
</html>
