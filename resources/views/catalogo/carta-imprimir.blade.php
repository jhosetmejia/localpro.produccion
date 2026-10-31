{{-- Carta para imprimir y compartir: 3 plantillas en hojas tamaño carta/A4 (1240x1754), descarga en PDF y JPG con QR a la carta en línea --}}
@php
    $lugar = trim(implode(', ', array_filter([$tienda->ciudad, $tienda->departamento !== $tienda->ciudad ? $tienda->departamento : null])));
    $wa = $tienda->telefono ? trim(preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', preg_replace('/\D/', '', $tienda->telefono))) : null;
    $planes = \App\Http\Controllers\catalogoController::PLANTILLAS_CARTA;
    $urlCarta = $tienda->url_carta;
    $datos = ['tienda' => ['nombre' => $tienda->nombre, 'lugar' => $lugar, 'wa' => $wa, 'logo' => $logo, 'carta' => preg_replace('#^https?://(www\.)?#', '', $urlCarta), 'url' => $urlCarta, 'desc' => $tienda->descripcion],
        'secciones' => $secciones, 'plantilla' => $plantilla, 'accion' => $accion,
        'archivo' => 'carta-' . \Illuminate\Support\Str::slug($tienda->nombre)];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Carta para imprimir · {{ $tienda->nombre }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,500&family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=Amatic+SC:wght@700&family=Caveat:wght@500;700&family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=DM+Sans:wght@400;500;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#e9ece8;font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#151a17;min-height:100vh}
/* ===== Barra de herramientas (no sale en la carta) ===== */
.barra{position:sticky;top:0;z-index:10;background:#0b0d0c;color:#fff;padding:12px max(16px,env(safe-area-inset-left));display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;box-shadow:0 8px 24px -12px rgba(0,0,0,.5)}
.barra b{font-weight:800}
.barra .pl{display:flex;gap:6px;flex-wrap:wrap}
.barra .pl a{color:#c9d3cc;text-decoration:none;font-weight:700;font-size:.88rem;border:1.5px solid #2a312d;border-radius:99px;padding:.45rem .9rem}
.barra .pl a.on{background:#a6f34a;color:#0b0d0c;border-color:#a6f34a}
.barra .acc{display:flex;gap:8px;flex-wrap:wrap}
.barra .acc a,.barra .acc button{border:0;border-radius:99px;font:800 .9rem 'Plus Jakarta Sans',sans-serif;padding:.6rem 1rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem}
.b-ver{background:#fff;color:#0b0d0c}.b-pdf{background:#ff5c5c;color:#fff}.b-jpg{background:#2fd15a;color:#0b0d0c}
.barra button:disabled{opacity:.6;cursor:wait}
.estado{width:100%;text-align:center;font-weight:700;font-size:.85rem;color:#a6f34a}
.hojas{display:flex;flex-direction:column;align-items:center;gap:22px;padding:22px 16px 60px}
.marco{box-shadow:0 18px 50px -20px rgba(0,0,0,.45);background:#fff;overflow:hidden;flex:none}
.marco>.pagina{transform-origin:0 0}
/* ===== Hoja ===== */
.pagina{width:1240px;height:1754px;position:relative;overflow:hidden;display:flex;flex-direction:column}
.cuerpo{flex:1;overflow:hidden;position:relative}
.pie{flex:none;display:flex;align-items:center;gap:26px}
.pie .qr{width:150px;height:150px;flex:none;background:#fff;padding:10px;border-radius:14px}
.pie .qr img{width:100%;height:100%;display:block}
.pie .tx{flex:1;min-width:0}
.pie .num{flex:none;font-weight:700;opacity:.6}

/* ===== Plantilla CLÁSICA: papel crema, Playfair + Cormorant, puntos hasta el precio ===== */
.clasica{background:#f6efe2;color:#2a1f17;font-family:'Cormorant Garamond',Georgia,serif;padding:70px 90px 60px}
.clasica::before{content:"";position:absolute;inset:34px;border:2px solid #b08d57;pointer-events:none}
.clasica::after{content:"";position:absolute;inset:44px;border:1px solid #b08d57;pointer-events:none}
.clasica .cab{text-align:center;padding:40px 0 30px}
.clasica .cab img{width:120px;height:120px;object-fit:contain;border-radius:50%;background:#fff;border:2px solid #b08d57;margin-bottom:18px}
.clasica .cab h1{font-family:'Playfair Display',serif;font-weight:900;font-size:86px;letter-spacing:.06em;text-transform:uppercase;line-height:1.02}
.clasica .cab .orn{color:#b08d57;font-size:34px;margin:12px 0 4px;letter-spacing:.4em}
.clasica .cab p{font-size:30px;font-style:italic;color:#6b5a48}
.clasica .cab.chica{padding:20px 0 16px}
.clasica .cab.chica h1{font-size:44px}
.clasica .sec{text-align:center;font-family:'Playfair Display',serif;font-weight:700;font-size:44px;color:#8a5a2b;margin:34px 0 18px;display:flex;align-items:center;gap:24px;text-transform:uppercase;letter-spacing:.12em}
.clasica .sec::before,.clasica .sec::after{content:"";flex:1;height:1.5px;background:#b08d57}
.clasica .it{margin:0 30px 20px}
.clasica .it .f{display:flex;align-items:baseline;gap:14px;font-size:36px;font-weight:700}
.clasica .it .f .n{flex:none;max-width:72%}
.clasica .it .f .dots{flex:1;border-bottom:3px dotted #b08d57;transform:translateY(-8px)}
.clasica .it .f .p{flex:none;color:#8a5a2b}
.clasica .it .d{font-style:italic;font-size:26px;color:#6b5a48;margin-top:2px}
.clasica .pie{border-top:1.5px solid #b08d57;padding:22px 30px 6px;color:#4a3a2c;font-size:26px}
.clasica .pie b{font-family:'Playfair Display',serif;font-size:30px;color:#2a1f17}

/* ===== Plantilla PIZARRA: tiza sobre tablero, marco de madera ===== */
.pizarra{background:radial-gradient(120% 90% at 50% 40%,#3a4441 0%,#2a3230 55%,#1d2321 100%);color:#f3f1e7;font-family:'Caveat',cursive;padding:80px 96px 70px;border:34px solid #6b4a2b;box-shadow:inset 0 0 0 6px #4e3520}
.pizarra::before{content:"";position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.035) 1px,transparent 1px);background-size:6px 6px;pointer-events:none}
.pizarra .cab{text-align:center;padding:10px 0 26px;position:relative}
.pizarra .cab img{width:120px;height:120px;object-fit:contain;border-radius:22px;background:#f3f1e7;margin-bottom:10px}
.pizarra .cab h1{font-family:'Amatic SC',cursive;font-size:120px;line-height:.95;letter-spacing:.04em}
.pizarra .cab p{font-size:40px;color:#f6d365}
.pizarra .cab .ray{height:6px;width:420px;margin:10px auto 0;background:repeating-linear-gradient(90deg,#f3f1e7 0 26px,transparent 26px 38px);opacity:.7;border-radius:4px}
.pizarra .cab.chica h1{font-size:70px}
.pizarra .sec{font-family:'Amatic SC',cursive;font-size:76px;color:#f7a8b8;margin:26px 0 10px;text-align:center;position:relative}
.pizarra .it{display:flex;justify-content:space-between;gap:24px;align-items:baseline;margin:0 20px 14px;position:relative}
.pizarra .it .n{font-size:48px;font-weight:700;line-height:1.05}
.pizarra .it .d{font-size:32px;color:#cfd6d0;font-weight:500;line-height:1.1}
.pizarra .it .p{flex:none;font-size:50px;font-weight:700;color:#f6d365}
.pizarra .pie{border-top:3px dashed rgba(243,241,231,.45);padding:22px 10px 0;font-size:38px;position:relative}
.pizarra .pie b{color:#f6d365}

/* ===== Plantilla MODERNA: fotos, bloques de color, precios en pastilla ===== */
.moderna{background:#fff;color:#141414;font-family:'DM Sans',system-ui,sans-serif;padding:0 0 50px}
.moderna .cab{background:#ff5a1f;color:#fff;padding:70px 80px 60px;display:flex;align-items:center;gap:36px;position:relative;overflow:hidden}
.moderna .cab::after{content:"";position:absolute;width:520px;height:520px;border-radius:50%;background:rgba(255,255,255,.12);right:-160px;top:-220px}
.moderna .cab img{width:150px;height:150px;object-fit:contain;border-radius:36px;background:#fff;flex:none}
.moderna .cab h1{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:92px;line-height:.95;letter-spacing:-.02em}
.moderna .cab p{font-size:32px;font-weight:500;opacity:.9;margin-top:10px}
.moderna .cab.chica{padding:36px 80px}
.moderna .cab.chica h1{font-size:54px}
.moderna .cuerpo{padding:10px 70px 0}
.moderna .sec{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:56px;margin:38px 0 18px;display:flex;align-items:center;gap:18px}
.moderna .sec::before{content:"";width:18px;height:56px;border-radius:9px;background:#ff5a1f}
.moderna .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.moderna .it{display:flex;gap:20px;align-items:center;background:#f6f3ef;border-radius:30px;padding:16px;min-height:184px}
.moderna .it .ft{width:152px;height:152px;border-radius:24px;object-fit:cover;flex:none;background:#ffe2d4}
.moderna .it .ft.l{display:grid;place-items:center;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:64px;color:#ff5a1f}
.moderna .it .tx{min-width:0;flex:1}
.moderna .it .n{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:34px;line-height:1.05}
.moderna .it .d{font-size:22px;color:#5c5650;margin-top:6px;line-height:1.25}
.moderna .it .p{display:inline-block;margin-top:10px;background:#141414;color:#fff;font-weight:700;font-size:28px;border-radius:99px;padding:6px 18px}
.moderna .pie{margin:0 70px;padding-top:24px;border-top:3px solid #141414;font-size:26px}
.moderna .pie b{font-family:'Bricolage Grotesque',sans-serif;font-size:32px}
.moderna .pie .qr{background:#f6f3ef}
</style>
</head>
<body>
<div class="barra">
    <div><b>{{ $tienda->nombre }}</b> · carta para imprimir</div>
    <div class="pl">
        @foreach ($planes as $k => [$nom, $txt])
            <a href="?plantilla={{ $k }}" class="{{ $k === $plantilla ? 'on' : '' }}" title="{{ $txt }}">{{ $nom }}</a>
        @endforeach
    </div>
    <div class="acc">
        <a class="b-ver" href="{{ $tienda->url_carta_navegador }}" target="_blank" rel="noopener">🍽️ Ver mi carta en línea</a>
        <button class="b-pdf" id="bPdf" type="button" disabled>⬇ PDF</button>
        <button class="b-jpg" id="bJpg" type="button" disabled>⬇ JPG</button>
    </div>
    <div class="estado" id="estado">Armando la carta…</div>
</div>
<div class="hojas" id="hojas"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    var D = @json($datos), T = D.tienda, P = D.plantilla, hojas = document.getElementById('hojas');
    function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; }

    // QR a la carta en línea (una sola imagen para todas las hojas)
    var qrUrl = '';
    try { var q = el('div'); new QRCode(q, { text: T.url, width: 300, height: 300, correctLevel: QRCode.CorrectLevel.M }); var c = q.querySelector('canvas'); qrUrl = c ? c.toDataURL('image/png') : ''; } catch (e) {}

    function cabecera(primera) {
        var c = el('div', 'cab' + (primera ? '' : ' chica'));
        if (primera && T.logo) { var i = el('img'); i.src = T.logo; i.alt = ''; c.appendChild(i); }
        var tx = P === 'moderna' ? el('div') : c;
        tx.appendChild(el('h1', '', T.nombre));
        if (primera) {
            if (P === 'clasica') c.appendChild(el('div', 'orn', '✦ ✦ ✦'));
            tx.appendChild(el('p', '', P === 'pizarra' ? '~ Nuestra carta ~' : (T.desc || 'Nuestra carta') + (T.lugar ? ' · ' + T.lugar : '')));
            if (P === 'pizarra') tx.appendChild(el('div', 'ray'));
        }
        if (tx !== c) c.appendChild(tx);
        return c;
    }
    function pie(n) {
        var p = el('div', 'pie');
        if (qrUrl) { var q = el('div', 'qr'), i = el('img'); i.src = qrUrl; i.alt = 'QR de la carta'; q.appendChild(i); p.appendChild(q); }
        var t = el('div', 'tx');
        var a = el('div'); a.appendChild(el('b', '', T.wa ? 'Pedidos por WhatsApp: ' + T.wa : 'Escanee y pida por WhatsApp')); t.appendChild(a);
        t.appendChild(el('div', '', 'Carta en línea: ' + T.carta));
        p.appendChild(t);
        p.appendChild(el('div', 'num', n));
        return p;
    }
    function itemEl(it) {
        var e = el('div', 'it');
        if (P === 'clasica') {
            var f = el('div', 'f'); f.appendChild(el('span', 'n', it.nombre)); f.appendChild(el('span', 'dots')); f.appendChild(el('span', 'p', it.precio)); e.appendChild(f);
            if (it.desc) e.appendChild(el('div', 'd', it.desc));
        } else if (P === 'pizarra') {
            var izq = el('div'); izq.appendChild(el('div', 'n', it.nombre)); if (it.desc) izq.appendChild(el('div', 'd', it.desc));
            e.appendChild(izq); e.appendChild(el('div', 'p', it.precio));
        } else {
            if (it.img) { var im = el('img', 'ft'); im.src = it.img; im.alt = ''; e.appendChild(im); }
            else e.appendChild(el('div', 'ft l', (it.nombre || '?').charAt(0).toUpperCase()));
            var tx = el('div', 'tx'); tx.appendChild(el('div', 'n', it.nombre)); if (it.desc) tx.appendChild(el('div', 'd', it.desc)); tx.appendChild(el('div', 'p', it.precio));
            e.appendChild(tx);
        }
        return e;
    }

    // ===== Reparte los platos en hojas: si no cabe, pasa a la siguiente =====
    var paginas = [];
    function nuevaHoja() {
        var marco = el('div', 'marco'), pg = el('div', 'pagina ' + P), cu = el('div', 'cuerpo');
        pg.appendChild(cabecera(!paginas.length)); pg.appendChild(cu); pg.appendChild(pie(''));
        marco.appendChild(pg); hojas.appendChild(marco);
        var h = { marco: marco, pg: pg, cu: cu, cont: null };
        paginas.push(h); return h;
    }
    function sobra(h) { return h.cu.scrollHeight > h.cu.clientHeight + 1; }
    function contenedor(h) {
        if (P !== 'moderna') return h.cu;
        if (!h.cont || h.cont.parentNode !== h.cu || h.cu.lastChild !== h.cont) { h.cont = el('div', 'grid'); h.cu.appendChild(h.cont); }
        return h.cont;
    }
    function armar() {
        var h = nuevaHoja();
        if (!D.secciones.length) { h.cu.appendChild(el('div', 'sec', 'Pronto nuestra carta')); }
        D.secciones.forEach(function (s) {
            var t = el('div', 'sec', s.nombre); h.cu.appendChild(t); h.cont = null;
            s.items.forEach(function (it, i) {
                var e = itemEl(it); contenedor(h).appendChild(e);
                if (sobra(h)) {
                    e.remove();
                    if (i === 0) t.remove(); // el título no se queda solo al final de la hoja
                    h = nuevaHoja(); h.cont = null;
                    h.cu.appendChild(i === 0 ? t : el('div', 'sec', s.nombre + (P === 'pizarra' ? '' : ' (sigue)')));
                    contenedor(h).appendChild(e);
                }
            });
        });
        // Quita hojas vacías y numera
        paginas = paginas.filter(function (x) { if (!x.cu.children.length) { x.marco.remove(); return false; } return true; });
        paginas.forEach(function (x, i) { x.pg.querySelector('.num').textContent = paginas.length > 1 ? (i + 1) + ' / ' + paginas.length : ''; });
        escalar();
    }
    // Vista previa: cada hoja se achica al ancho de la pantalla
    function escalar() {
        var w = Math.min(820, hojas.clientWidth - 32), k = w / 1240;
        paginas.forEach(function (x) { x.marco.style.width = w + 'px'; x.marco.style.height = (1754 * k) + 'px'; x.pg.style.transform = 'scale(' + k + ')'; });
    }
    window.addEventListener('resize', escalar);

    // ===== Descargas =====
    var esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var est = document.getElementById('estado'), bPdf = document.getElementById('bPdf'), bJpg = document.getElementById('bJpg');
    function lienzo(x) {
        var t = x.pg.style.transform; x.pg.style.transform = 'none';
        return htmlToImage.toCanvas(x.pg, { width: 1240, height: 1754, pixelRatio: 1, cacheBust: false, backgroundColor: '#ffffff' })
            .finally(function () { x.pg.style.transform = t; });
    }
    function guardar(blob, nombre) {
        var f = null; try { f = new File([blob], nombre, { type: blob.type }); } catch (e) {}
        if (esIOS && f && navigator.canShare && navigator.canShare({ files: [f] })) return navigator.share({ files: [f] }).catch(function () {});
        var a = el('a'); a.href = URL.createObjectURL(blob); a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 60000);
    }
    function ocupado(si, txt) { bPdf.disabled = bJpg.disabled = si; est.textContent = txt || ''; }
    function pdf() {
        ocupado(true, 'Creando el PDF…');
        var doc = new jspdf.jsPDF({ unit: 'px', format: [1240, 1754], hotfixes: ['px_scaling'] }), i = 0;
        (function sig() {
            if (i >= paginas.length) { guardar(doc.output('blob'), D.archivo + '.pdf'); ocupado(false, 'PDF listo ✅'); return; }
            lienzo(paginas[i]).then(function (c) { if (i) doc.addPage([1240, 1754]); doc.addImage(c.toDataURL('image/jpeg', 0.92), 'JPEG', 0, 0, 1240, 1754); i++; est.textContent = 'Creando el PDF… hoja ' + i + ' de ' + paginas.length; sig(); })
                .catch(function () { ocupado(false, 'No se pudo crear el PDF. Intente de nuevo o use Chrome.'); });
        })();
    }
    function jpg() {
        ocupado(true, 'Creando la imagen…');
        var i = 0;
        (function sig() {
            if (i >= paginas.length) { ocupado(false, paginas.length > 1 ? paginas.length + ' imágenes listas ✅ (una por hoja)' : 'Imagen lista ✅'); return; }
            lienzo(paginas[i]).then(function (c) {
                c.toBlob(function (b) { guardar(b, D.archivo + (paginas.length > 1 ? '-' + (i + 1) : '') + '.jpg'); i++; setTimeout(sig, 700); }, 'image/jpeg', 0.92);
            }).catch(function () { ocupado(false, 'No se pudo crear la imagen. Intente de nuevo o use Chrome.'); });
        })();
    }
    bPdf.onclick = pdf; bJpg.onclick = jpg;

    // Arma la carta cuando cargan las letras y las fotos
    var fotos = Array.prototype.slice.call(document.images);
    (document.fonts ? document.fonts.ready : Promise.resolve()).then(function () {
        armar();
        var imgs = Array.prototype.slice.call(hojas.querySelectorAll('img'));
        return Promise.all(imgs.map(function (im) { return im.complete ? 0 : new Promise(function (ok) { im.onload = im.onerror = ok; }); }));
    }).then(function () {
        ocupado(false, paginas.length > 1 ? 'Su carta tiene ' + paginas.length + ' hojas' : '');
        if (D.accion === 'pdf') pdf(); else if (D.accion === 'jpg') jpg();
    });
})();
</script>
</body>
</html>
