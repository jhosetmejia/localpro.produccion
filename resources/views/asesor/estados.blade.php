@extends('layouts.app')

@section('title', 'Vende más: imágenes para sus estados')

@push('css')
<style>
.es-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:1.8rem 1.6rem;position:relative;overflow:hidden;margin-bottom:1rem}
.es-hero::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;border:46px solid rgba(166,243,74,.12);right:-100px;top:-120px}
.es-hero small{color:#9fe3b0;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem}
.es-hero h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;line-height:1.08;margin:.35rem 0 .5rem;max-width:22ch;position:relative;z-index:1}
.es-hero h1 em{font-style:normal;color:#a6f34a}
.es-hero p{color:#c9d3cc;max-width:60ch;font-weight:500;margin:0;position:relative;z-index:1}
.es-pasos{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;position:relative;z-index:1}
.es-pasos span{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:999px;padding:.4rem .85rem;font-size:.82rem;font-weight:700;color:#e6ede8}
.es-pasos b{color:#a6f34a}
.es-aviso{background:#fff8e6;border:1.5px solid #f5dc9b;border-radius:18px;padding:.75rem 1rem;font-weight:600;color:#6b5414;margin-bottom:1rem}
.es-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem}
.es-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;padding:.7rem;display:flex;flex-direction:column;gap:.55rem;position:relative}
.es-card.hoy{border-color:#16b34a;box-shadow:0 12px 30px -18px rgba(22,179,74,.8)}
.es-card .tag{position:absolute;top:1.1rem;left:1.1rem;background:#a6f34a;color:#0b0d0c;font-weight:900;font-size:.7rem;border-radius:999px;padding:.2rem .6rem;z-index:2}
.es-prev{aspect-ratio:9/16;border-radius:16px;background:#0b0d0c;overflow:hidden;display:grid;place-items:center;color:#7d8a82;font-weight:700;font-size:.85rem}
.es-prev img{width:100%;height:100%;object-fit:cover;display:block;cursor:zoom-in}
.es-card h3{font-size:1rem;font-weight:900;margin:0}
.es-btns{display:grid;grid-template-columns:1fr 1fr;gap:.4rem}
.es-btns .btn{border-radius:999px;font-weight:800;font-size:.82rem;padding:.45rem .5rem}
.es-btns .btn-success,.es-btns .es-full{grid-column:1/-1}
.es-ver{position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;place-items:center;z-index:3000;padding:1rem}
.es-ver.on{display:grid}
.es-ver img{max-height:92vh;max-width:100%;border-radius:18px}
.es-toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%) translateY(20px);background:#0b0d0c;color:#fff;border-radius:999px;padding:.6rem 1.2rem;font-weight:800;opacity:0;transition:.25s;z-index:3100;pointer-events:none;text-align:center;max-width:92vw}
.es-toast.on{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="es-hero">
        <small><i class="fa-solid fa-bullhorn me-1"></i>Vende más</small>
        <h1>Suba estas imágenes a sus <em>estados</em></h1>
        <p>Hay una para cada tipo de negocio: tiendas de ropa, restaurantes, odontólogos, conjuntos y más. Cada imagen vende con los beneficios de ese sistema y puede llevar <b>su WhatsApp</b> ("Contáctanos") o ir sin número. El texto que se copia trae su enlace con el código {{ $yo->codigo }}: el negocio que se registre queda a su nombre.</p>
        <div class="es-pasos">
            <span><b>1.</b> Toque "Compartir" o descárguela con o sin su número</span>
            <span><b>2.</b> Súbala a su estado de WhatsApp o historia de Instagram</span>
            <span><b>3.</b> Pegue el texto (ya queda copiado)</span>
            <span><b>💡</b> Una distinta cada día</span>
        </div>
    </div>

    @if (!$whatsapp)
    <div class="es-aviso"><i class="fa-solid fa-circle-info me-1"></i>Sus imágenes no muestran su WhatsApp porque no lo tiene en su perfil, y es lo más importante para que le escriban. <a href="{{ route('cuenta.edit') }}" class="fw-bold">Agréguelo en Mi cuenta</a>.</div>
    @endif

    <div class="es-grid">
        @foreach ($lista as $i => $e)
        <div class="es-card {{ $e['clave'] === $sugerida ? 'hoy' : '' }}">
            @if ($e['clave'] === $sugerida)<span class="tag">⭐ Sugerida hoy</span>@endif
            <div class="es-prev" data-prev="{{ $i }}">Preparando imagen…</div>
            <h3>{{ $e['emoji'] }} {{ $e['titulo'] }}</h3>
            <div class="es-btns">
                <button type="button" class="btn btn-success" data-compartir="{{ $i }}"><i class="fa-solid fa-share-nodes me-1"></i>Compartir</button>
                <button type="button" class="btn btn-dark" data-bajar="{{ $i }}" @if (!$whatsapp) disabled @endif><i class="fa-solid fa-download me-1"></i>Con mi número</button>
                <button type="button" class="btn btn-outline-dark" data-bajar-sin="{{ $i }}"><i class="fa-solid fa-download me-1"></i>Sin número</button>
                <button type="button" class="btn btn-light es-full" style="border:1.5px solid #bfeccb" data-texto="{{ $i }}"><i class="fa-regular fa-copy me-1"></i>Copiar texto</button>
            </div>
        </div>
        @endforeach
    </div>
</div>
<div class="es-ver" id="esVer"><img alt="Vista de la imagen"></div>
<div class="es-toast" id="esToast"></div>
@endsection

@push('js')
<script>
(function () {
    var L = {!! json_encode($lista, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
    var WA = {!! json_encode($whatsapp) !!};
    var CODIGO = {!! json_encode($yo->codigo) !!};
    var W = 1080, H = 1920, F = "'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif";
    var VERDE = '#a6f34a', VERDE2 = '#16b34a', NEGRO = '#0b0d0c';
    var listas = {}, listasSin = {}; // i → dataURL jpg (con número / sin número)
    var EMO = "'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif";
    // Color y objetos de cada negocio para el fondo. st = los dos "stickers" grandes al lado del celular
    var TEMAS = {
        general: { a: '#0f3d1f', b: '#06120a', luz: 'rgba(166,243,74,.30)', em: ['🏪', '📒', '💵', '🧾', '📦', '📈'], st: ['💵', '📈'] },
        moda: { a: '#5a1250', b: '#160414', luz: 'rgba(255,120,210,.30)', em: ['👗', '👠', '👜', '🕶️', '👕', '💄', '👚'], st: ['👗', '👜'] },
        calzado: { a: '#4a3010', b: '#120b04', luz: 'rgba(255,190,90,.28)', em: ['👟', '👠', '👢', '🥾', '👞', '🩴'], st: ['👟', '👠'] },
        restaurante: { a: '#6a1d06', b: '#180703', luz: 'rgba(255,140,40,.32)', em: ['🍔', '🍟', '🍕', '🌭', '🌮', '🥤', '🍗'], st: ['🍔', '🍟'] },
        cafeteria: { a: '#4a2c16', b: '#120a05', luz: 'rgba(255,190,120,.28)', em: ['☕', '🥐', '🍩', '🍰', '🥖', '🧁'], st: ['☕', '🥐'] },
        minimercado: { a: '#0f5a22', b: '#041408', luz: 'rgba(255,220,60,.26)', em: ['🛒', '🥫', '🥖', '🍎', '🥛', '🧃', '🥚', '🍌'], st: ['🛒', '🥫'] },
        belleza: { a: '#5a1240', b: '#16040f', luz: 'rgba(255,130,190,.30)', em: ['💇', '✂️', '💅', '💄', '🪒', '💆'], st: ['✂️', '💅'] },
        odontologia: { a: '#06566b', b: '#03161c', luz: 'rgba(110,230,255,.32)', em: ['🦷', '🪥', '😁', '✨', '🦷', '🪥'], st: ['🦷', '🪥'] },
        consultorio: { a: '#0a4a6a', b: '#03121b', luz: 'rgba(120,200,255,.30)', em: ['🩺', '💊', '🩹', '📋', '❤️', '🌡️'], st: ['🩺', '💊'] },
        conjunto: { a: '#16376a', b: '#050d1b', luz: 'rgba(120,170,255,.28)', em: ['🏢', '🏠', '🔑', '🧾', '🌳', '🏊'], st: ['🏢', '🔑'] },
        instituto: { a: '#30206e', b: '#0b071b', luz: 'rgba(160,140,255,.30)', em: ['🎓', '📚', '✏️', '🏫', '📝', '📐'], st: ['🎓', '📚'] },
        contable: { a: '#123a6a', b: '#040c18', luz: 'rgba(110,180,255,.28)', em: ['🧮', '📊', '🧾', '💼', '📑', '💰'], st: ['🧮', '📊'] },
        alquiler: { a: '#561e6a', b: '#12051a', luz: 'rgba(230,140,255,.30)', em: ['🎉', '🪑', '⛺', '🎈', '🍾', '🎊'], st: ['🎈', '🪑'] },
        '360': { a: '#6a1258', b: '#180415', luz: 'rgba(255,110,220,.32)', em: ['📸', '🎥', '💃', '🕺', '✨', '🎶'], st: ['📸', '🎥'] },
        eventos: { a: '#44127a', b: '#0d041b', luz: 'rgba(180,110,255,.34)', em: ['🎟️', '🎤', '🎶', '🎉', '💃', '🔊'], st: ['🎟️', '🎤'] },
        funeraria: { a: '#1d2e46', b: '#070b13', luz: 'rgba(200,215,255,.22)', em: ['🕊️', '💐', '🕯️', '🌹', '🤍'], st: ['🕊️', '💐'], suave: true }
    };
    // Azar fijo por negocio: la imagen sale igual cada vez
    function azar(semilla) { var h = 2166136261; for (var k = 0; k < semilla.length; k++) { h ^= semilla.charCodeAt(k); h = Math.imul(h, 16777619); } return function () { h = Math.imul(h ^ (h >>> 15), 2246822507); h = Math.imul(h ^ (h >>> 13), 3266489909); h ^= h >>> 16; return (h >>> 0) / 4294967296; }; }

    function aviso(t) { var e = document.getElementById('esToast'); e.textContent = t; e.classList.add('on'); clearTimeout(aviso.t); aviso.t = setTimeout(function () { e.classList.remove('on'); }, 2600); }
    function copiar(t, ok) {
        var viejo = function () { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); if (ok) aviso(ok); } catch (e) {} a.remove(); };
        if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { if (ok) aviso(ok); }, viejo); else viejo();
    }
    function telefono(n) {
        if (!n) return '';
        if (n.length === 12 && n.indexOf('57') === 0) return '+57 ' + n.substr(2, 3) + ' ' + n.substr(5, 3) + ' ' + n.substr(8);
        if (n.length === 10 && n[0] === '3') return n.substr(0, 3) + ' ' + n.substr(3, 3) + ' ' + n.substr(6);
        return (n.length > 10 ? '+' : '') + n;
    }
    function rr(c, x, y, w, h, r) { c.beginPath(); c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r); c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath(); }
    // Escribe el texto con el tamaño más grande que quepa en el ancho
    function ajustado(c, t, x, y, maxW, tam, peso, color, alinear) {
        var s = tam; c.font = peso + ' ' + s + 'px ' + F;
        while (c.measureText(t).width > maxW && s > 12) { s -= 2; c.font = peso + ' ' + s + 'px ' + F; }
        c.fillStyle = color; c.textAlign = alinear || 'left'; c.fillText(t, x, y); c.textAlign = 'left';
        return c.measureText(t).width;
    }
    function chulo(c, x, y, r) {
        c.fillStyle = VERDE; c.beginPath(); c.arc(x, y, r, 0, Math.PI * 2); c.fill();
        c.strokeStyle = NEGRO; c.lineWidth = r * 0.28; c.lineCap = 'round'; c.lineJoin = 'round';
        c.beginPath(); c.moveTo(x - r * 0.45, y + r * 0.02); c.lineTo(x - r * 0.1, y + r * 0.38); c.lineTo(x + r * 0.48, y - r * 0.35); c.stroke();
    }

    function dibujar(c, e, conNumero) {
        // Fondo de propaganda según el negocio: color del tema + objetos del negocio regados + dos "stickers" grandes
        var t = TEMAS[e.clave] || TEMAS.general, rnd = azar(e.clave);
        var g = c.createLinearGradient(0, 0, 0, H); g.addColorStop(0, t.a); g.addColorStop(0.55, t.b); g.addColorStop(1, '#040605');
        c.fillStyle = g; c.fillRect(0, 0, W, H);
        var gl = c.createRadialGradient(540, 780, 40, 540, 780, 640); gl.addColorStop(0, t.luz); gl.addColorStop(1, 'rgba(0,0,0,0)');
        c.fillStyle = gl; c.fillRect(0, 0, W, H);
        c.save(); c.textAlign = 'center'; c.textBaseline = 'middle';
        for (var k = 0; k < 26; k++) {
            var ex = rnd() * W, ey = 120 + rnd() * 1700, es = 70 + rnd() * 110;
            c.save(); c.globalAlpha = t.suave ? 0.12 : 0.2; c.translate(ex, ey); c.rotate((rnd() - 0.5) * 0.9);
            c.font = es + 'px ' + EMO; c.fillText(t.em[k % t.em.length], 0, 0); c.restore();
        }
        c.restore();
        var os = c.createLinearGradient(0, 1050, 0, H); os.addColorStop(0, 'rgba(0,0,0,0)'); os.addColorStop(0.35, 'rgba(0,0,0,.55)'); os.addColorStop(1, 'rgba(0,0,0,.75)');
        c.fillStyle = os; c.fillRect(0, 1050, W, H - 1050);

        // Marca y para quién es
        c.textBaseline = 'alphabetic';
        c.font = '800 58px ' + F; c.fillStyle = '#fff'; c.fillText('Local', 80, 150);
        var wl = c.measureText('Local').width; c.fillStyle = VERDE; c.fillText('Proo', 80 + wl, 150);
        c.font = '800 30px ' + F; var chip = e.emoji + '  Para ' + e.titulo.toLowerCase(), wc = c.measureText(chip).width + 56;
        rr(c, 80, 182, wc, 60, 30); c.fillStyle = 'rgba(166,243,74,.16)'; c.fill(); c.strokeStyle = 'rgba(166,243,74,.5)'; c.lineWidth = 2; c.stroke();
        c.fillStyle = VERDE; c.fillText(chip, 108, 223);

        // Titular
        ajustado(c, e.linea1, 80, 335, 920, 84, '800', '#fff');
        ajustado(c, e.linea2, 80, 428, 920, 84, '800', VERDE);

        // Celular con la pantalla del sistema (se dibuja a 540×750 y se reduce para dejar espacio a los beneficios)
        var px = 270, py = 490, pw = 540, ph = 750;
        c.save(); c.translate(540, 478); c.scale(0.78, 0.78); c.translate(-540, -py);
        c.save(); c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = 60; c.shadowOffsetY = 30;
        rr(c, px, py, pw, ph, 64); c.fillStyle = '#1d2220'; c.fill(); c.restore();
        rr(c, px, py, pw, ph, 64); c.strokeStyle = '#3a423e'; c.lineWidth = 4; c.stroke();
        var sx = px + 18, sy = py + 18, sw = pw - 36, sh = ph - 36;
        c.save(); rr(c, sx, sy, sw, sh, 48); c.clip();
        c.fillStyle = '#f3f7f4'; c.fillRect(sx, sy, sw, sh);
        // Barra superior
        c.fillStyle = NEGRO; c.fillRect(sx, sy, sw, 128);
        rr(c, sx + sw / 2 - 60, sy + 14, 120, 26, 13); c.fillStyle = '#1d2220'; c.fill();
        c.fillStyle = VERDE; c.beginPath(); c.arc(sx + 42, sy + 88, 12, 0, Math.PI * 2); c.fill();
        ajustado(c, e.pantalla.titulo, sx + 66, sy + 98, sw - 100, 32, '800', '#fff');
        // Dato grande
        rr(c, sx + 22, sy + 146, sw - 44, 124, 26); c.fillStyle = VERDE; c.fill();
        ajustado(c, e.pantalla.etiqueta, sx + 46, sy + 186, sw - 92, 24, '700', '#244d0a');
        ajustado(c, e.pantalla.dato, sx + 46, sy + 248, sw - 92, 54, '900', NEGRO);
        // Filas
        var fy = sy + 290;
        e.pantalla.filas.forEach(function (f) {
            rr(c, sx + 22, fy, sw - 44, 72, 20); c.fillStyle = '#fff'; c.fill(); c.strokeStyle = '#e3ebe6'; c.lineWidth = 2; c.stroke();
            c.fillStyle = f[2] ? '#fde8cc' : '#e7fbe9'; c.beginPath(); c.arc(sx + 62, fy + 36, 18, 0, Math.PI * 2); c.fill();
            c.fillStyle = f[2] ? '#d97706' : VERDE2; c.beginPath(); c.arc(sx + 62, fy + 36, 7, 0, Math.PI * 2); c.fill();
            c.font = '800 21px ' + F; var vw = Math.min(170, c.measureText(f[1]).width + 30);
            rr(c, sx + sw - 38 - vw, fy + 17, vw, 38, 19); c.fillStyle = f[2] ? '#fde8cc' : '#e7fbe9'; c.fill();
            ajustado(c, f[1], sx + sw - 38 - vw / 2, fy + 43, vw - 20, 21, '800', f[2] ? '#b45309' : '#12813a', 'center');
            ajustado(c, f[0], sx + 96, fy + 44, sw - 44 - 96 - vw - 26, 23, '700', '#1f2a24');
            fy += 82;
        });
        // Menú inferior
        c.fillStyle = '#fff'; c.fillRect(sx, sy + sh - 80, sw, 80);
        [0, 1, 2, 3].forEach(function (k) { c.fillStyle = k === 0 ? VERDE2 : '#c7d3cb'; rr(c, sx + 50 + k * ((sw - 100) / 3) - 20, sy + sh - 52, 40, 24, 8); c.fill(); });
        c.restore();
        c.restore();
        [[175, 700, -0.25, 170], [905, 930, 0.22, 150]].forEach(function (st, k) {
            c.save(); c.translate(st[0], st[1]); c.rotate(st[2]); c.textAlign = 'center'; c.textBaseline = 'middle';
            c.shadowColor = 'rgba(0,0,0,.55)'; c.shadowBlur = 30; c.shadowOffsetY = 14;
            c.font = st[3] + 'px ' + EMO; c.fillText(t.st[k], 0, 0); c.restore();
        });
        c.textBaseline = 'alphabetic';

        // Beneficios (los del sistema + página de enlaces y tienda virtual)
        e.puntos.forEach(function (t, k) {
            var y = 1135 + k * 60;
            chulo(c, 102, y - 13, 22);
            ajustado(c, t, 144, y, 870, 38, '700', '#fff');
        });

        // Tarjeta final: "Pruébalo GRATIS" y, si se quiere, "Contáctanos" con el WhatsApp del asesor (sin nombre, sin QR ni enlace)
        if (conNumero && WA) {
            var cy = 1480;
            rr(c, 60, cy, 960, 280, 40); c.fillStyle = '#fff'; c.fill();
            ajustado(c, '🎁 Pruébalo GRATIS', 540, cy + 86, 880, 66, '900', NEGRO, 'center');
            ajustado(c, 'Contáctanos', 540, cy + 134, 880, 34, '700', '#55605a', 'center');
            rr(c, 100, cy + 158, 880, 100, 50); c.fillStyle = '#25d366'; c.fill();
            ajustado(c, '💬  ' + telefono(WA), 540, cy + 227, 820, 60, '900', '#fff', 'center');
        } else {
            rr(c, 60, 1530, 960, 180, 40); c.fillStyle = VERDE; c.fill();
            ajustado(c, '🎁 Pruébalo GRATIS', 540, 1645, 880, 82, '900', NEGRO, 'center');
        }
    }

    var lienzo = document.createElement('canvas'); lienzo.width = W; lienzo.height = H;
    var ctx = lienzo.getContext('2d');
    function generar(i) {
        if (i >= L.length) return;
        try {
            dibujar(ctx, L[i], true);
            listas[i] = lienzo.toDataURL('image/jpeg', 0.9);
            var caja = document.querySelector('[data-prev="' + i + '"]');
            caja.innerHTML = ''; var img = new Image(); img.src = listas[i]; img.alt = L[i].titulo; img.dataset.ver = i; caja.appendChild(img);
        } catch (err) { var cj = document.querySelector('[data-prev="' + i + '"]'); if (cj) cj.textContent = 'No se pudo crear la imagen'; }
        setTimeout(function () { generar(i + 1); }, 15);
    }
    var fuentes = document.fonts && document.fonts.load ? Promise.all(['900', '800', '700', '600'].map(function (p) { return document.fonts.load(p + ' 40px "Plus Jakarta Sans"'); })) : Promise.resolve();
    function sinNumero(i) { if (!listasSin[i]) { dibujar(ctx, L[i], false); listasSin[i] = lienzo.toDataURL('image/jpeg', 0.9); } return listasSin[i]; }
    Promise.race([fuentes, new Promise(function (ok) { setTimeout(ok, 2500); })]).then(function () { generar(0); });

    function archivo(i) {
        var b = atob(listas[i].split(',')[1]), a = new Uint8Array(b.length);
        for (var k = 0; k < b.length; k++) a[k] = b.charCodeAt(k);
        return new File([a], 'localpro-' + L[i].clave + '-' + CODIGO + '.jpg', { type: 'image/jpeg' });
    }
    function bajar(i, sin) {
        var a = document.createElement('a'); a.href = sin ? sinNumero(i) : listas[i]; a.download = 'localpro-' + L[i].clave + (sin ? '' : '-' + CODIGO) + '.jpg';
        document.body.appendChild(a); a.click(); a.remove();
    }
    document.addEventListener('click', function (ev) {
        var s = ev.target.closest('[data-compartir]'), d = ev.target.closest('[data-bajar]'), ds = ev.target.closest('[data-bajar-sin]'), t = ev.target.closest('[data-texto]'), v = ev.target.closest('[data-ver]'), cierre = ev.target.closest('#esVer');
        if (v) { var ver = document.getElementById('esVer'); ver.querySelector('img').src = listas[v.dataset.ver]; ver.classList.add('on'); return; }
        if (cierre) { cierre.classList.remove('on'); return; }
        var i = s ? s.dataset.compartir : (d ? d.dataset.bajar : (ds ? ds.dataset.bajarSin : (t ? t.dataset.texto : null)));
        if (i === null) return;
        if (t) { copiar(L[i].texto, '✅ Texto copiado: péguelo en su estado'); return; }
        if (!listas[i]) { aviso('Espere un momento, la imagen se está preparando'); return; }
        if (d) { bajar(i); copiar(L[i].texto); aviso('✅ Imagen descargada y texto copiado'); return; }
        if (ds) { bajar(i, true); copiar(L[i].texto); aviso('✅ Imagen sin número descargada'); return; }
        copiar(L[i].texto);
        var f = archivo(i);
        if (navigator.canShare && navigator.canShare({ files: [f] })) {
            navigator.share({ files: [f], text: L[i].texto }).then(function () { aviso('✅ ¡Listo! El texto también quedó copiado'); }, function () {});
        } else {
            bajar(i); aviso('✅ Imagen descargada y texto copiado: súbala a su estado');
        }
    });
})();
</script>
@endpush
