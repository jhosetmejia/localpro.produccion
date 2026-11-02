<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>QR de la carta · {{ $tienda->nombre }}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{margin:0;background:#e9efe9;font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#0b0d0c}
.bar{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;align-items:center;padding:14px;background:#0b0d0c;position:sticky;top:0;z-index:5}
.bar button,.bar a,.bar select,.bar input{border:0;border-radius:999px;padding:10px 16px;font:800 14px 'Plus Jakarta Sans',sans-serif;text-decoration:none;cursor:pointer}
.bar .p{background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c}
.bar .d{background:#2a2f2c;color:#fff}
.bar select,.bar input{background:#fff;color:#0b0d0c}
.bar input{width:180px}
.bar label{color:#b8c4bc;font-weight:700;font-size:13px}
.hoja{width:215.9mm;min-height:279.4mm;margin:16px auto;background:#fff;padding:10mm;display:grid;gap:8mm;box-shadow:0 10px 30px rgba(0,0,0,.12);align-content:start}
.hoja.f1{grid-template-columns:1fr}
.hoja.f4{grid-template-columns:1fr 1fr}
.hoja.f6{grid-template-columns:1fr 1fr 1fr}
.qr{border:2px solid #0b0d0c;border-radius:8mm;padding:7mm 6mm 4mm;display:flex;flex-direction:column;align-items:center;text-align:center;break-inside:avoid;page-break-inside:avoid}
.qr .logo{max-height:22mm;max-width:60%;object-fit:contain;margin-bottom:3mm}
.qr .nom{font-weight:800;font-size:20px;line-height:1.15;margin-bottom:1mm}
.qr .t{font-weight:800;font-size:16px;margin:3mm 0 1mm}
.qr .s{font-weight:600;font-size:12px;color:#444}
.qr .codigo{position:relative;width:100%;max-width:62mm;aspect-ratio:1}
.qr .codigo svg{width:100%;height:100%;display:block}
.qr .centro{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:24%;aspect-ratio:1;background:#fff;border-radius:18%;padding:5%;display:grid;place-items:center}
.qr .centro img{max-width:100%;max-height:100%;object-fit:contain}
.qr .mesa{margin-top:2mm;font-weight:800;font-size:15px;border:2px solid #0b0d0c;border-radius:999px;padding:1mm 5mm}
.qr .lp{margin-top:auto;padding-top:4mm;font-size:8.5px;font-weight:700;color:#8a948e;letter-spacing:.02em}
.qr .lp b{color:#5b665e}
/* Una grande: todo más grande */
.f1 .qr{padding:16mm 12mm 8mm;border-width:3px;border-radius:12mm}
.f1 .qr .logo{max-height:40mm}
.f1 .qr .nom{font-size:34px}
.f1 .qr .codigo{max-width:120mm}
.f1 .qr .t{font-size:28px}
.f1 .qr .s{font-size:18px}
.f1 .qr .mesa{font-size:24px}
.f1 .qr .lp{font-size:12px;padding-top:10mm}
.f6 .qr{padding:5mm 4mm 3mm;border-radius:6mm}
.f6 .qr .logo{max-height:14mm}
.f6 .qr .nom{font-size:14px}
.f6 .qr .t{font-size:12px}
.f6 .qr .s{font-size:9.5px}
.f6 .qr .mesa{font-size:12px}
.f6 .qr .lp{font-size:7px}
@page{size:letter;margin:0}
@media print{body{background:#fff}.bar{display:none}.hoja{margin:0;box-shadow:none}}
@media (max-width:900px){.hoja{width:100%;min-height:0;margin:0}}
</style>
</head>
<body>
<div class="bar">
    <button class="p" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <label>Tamaño
        <select id="formato">
            <option value="f1">1 grande por hoja</option>
            <option value="f4" selected>4 por hoja (mesas)</option>
            <option value="f6">6 por hoja (pequeños)</option>
        </select>
    </label>
    <label>Mesas <input id="mesas" placeholder="Ej: 1-10 (opcional)" maxlength="40"></label>
    <a class="d" href="{{ route('carta.index') }}">Volver</a>
</div>
<div id="hojas"></div>

<script src="{{ asset('js/qrcode-generator.js') }}?v=1"></script>
<script>
(function () {
    var URL = @json($url), NOMBRE = @json($tienda->nombre), LOGO = @json($tienda->logo_url ?: '');
    // QR con corrección alta (H): aguanta el logo en el centro y que se ensucie un poco en la mesa
    var qr = qrcode(0, 'H'); qr.addData(URL); qr.make();
    var n = qr.getModuleCount(), m = 2, path = '';
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (qr.isDark(r, c)) path += 'M' + (c + m) + ' ' + (r + m) + 'h1v1h-1z';
    var SVG = '<svg viewBox="0 0 ' + (n + 2 * m) + ' ' + (n + 2 * m) + '" shape-rendering="crispEdges" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#fff"/><path d="' + path + '" fill="#000"/></svg>';
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (x) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[x]; }); };

    function tarjeta(mesa) {
        return '<div class="qr">' +
            (LOGO ? '<img class="logo" src="' + esc(LOGO) + '" alt="">' : '') +
            '<div class="nom">' + esc(NOMBRE) + '</div>' +
            '<div class="codigo">' + SVG + (LOGO ? '<div class="centro"><img src="' + esc(LOGO) + '" alt=""></div>' : '') + '</div>' +
            '<div class="t">Escanee y vea nuestra carta</div>' +
            '<div class="s">Pida desde su celular por WhatsApp</div>' +
            (mesa ? '<div class="mesa">Mesa ' + esc(mesa) + '</div>' : '') +
            '<div class="lp">Hecho con <b>LocalProo</b> · localproo.com</div></div>';
    }
    // "1-10" o "1,2,5" o "Terraza 1, Terraza 2"
    function listaMesas(txt) {
        txt = (txt || '').trim(); if (!txt) return [];
        var out = [];
        txt.split(',').forEach(function (p) {
            p = p.trim(); var r = p.match(/^(\d+)\s*-\s*(\d+)$/);
            if (r) { var a = +r[1], b = +r[2]; if (b >= a && b - a < 100) for (var i = a; i <= b; i++) out.push(String(i)); }
            else if (p) out.push(p);
        });
        return out.slice(0, 120);
    }
    function pintar() {
        var f = document.getElementById('formato').value, porHoja = { f1: 1, f4: 4, f6: 6 }[f];
        var mesas = listaMesas(document.getElementById('mesas').value);
        var items = mesas.length ? mesas : new Array(porHoja).fill('');
        var html = '';
        for (var i = 0; i < items.length; i += porHoja) {
            html += '<div class="hoja ' + f + '">' + items.slice(i, i + porHoja).map(tarjeta).join('') + '</div>';
        }
        document.getElementById('hojas').innerHTML = html;
        try { localStorage.setItem('lpQrFormato', f); } catch (e) {}
    }
    try { var g = localStorage.getItem('lpQrFormato'); if (g) document.getElementById('formato').value = g; } catch (e) {}
    document.getElementById('formato').addEventListener('change', pintar);
    document.getElementById('mesas').addEventListener('input', pintar);
    pintar();
})();
</script>
</body>
</html>
