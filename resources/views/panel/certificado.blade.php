<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificado · Nivel {{ $nivel }} · LocalPro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
<style>
    *{box-sizing:border-box}
    body{margin:0;background:#0b0d0c;color:#fff;font-family:Outfit,system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:24px 16px}
    .top{width:100%;max-width:1000px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
    .top a.v{color:#a6f34a;font-weight:700;text-decoration:none}
    .btns{display:flex;gap:10px;flex-wrap:wrap}
    .b{border:0;border-radius:999px;padding:.75rem 1.3rem;font-weight:900;font-family:inherit;font-size:1rem;cursor:pointer}
    .b1{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c}
    .b2{background:#1c2a21;color:#fff;border:1.5px solid #3c5a46}
    .b3{background:#25d366;color:#0b0d0c}
    canvas{width:100%;max-width:1000px;height:auto;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.6)}
    p.n{color:#9aa69e;font-weight:500;margin-top:14px;text-align:center}
    @media print{body{background:#fff;padding:0}.top,p.n{display:none}canvas{box-shadow:none;border-radius:0;max-width:none}@page{size:landscape;margin:0}}
</style>
</head>
<body>
<div class="top">
    <a class="v" href="{{ route('panel') }}">← Volver al panel</a>
    <div class="btns">
        <button class="b b1" onclick="descargar()">⬇ Descargar imagen</button>
        <button class="b b3" onclick="compartir()">Compartir</button>
        <button class="b b2" onclick="window.print()">Imprimir / PDF</button>
    </div>
</div>
<canvas id="cv" width="2000" height="1414"></canvas>
<p class="n">Compártalo en sus redes: ¡sus clientes verán que su negocio es profesional!</p>
@php
    $datos = [
        'tienda' => $tienda?->nombre ?? 'Mi negocio',
        'nivel' => $nivel,
        'nombre' => $nombre,
        'fecha' => ucfirst(now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY')),
        'codigo' => $tienda?->codigo ?? '',
        // El logo va incrustado para que la imagen se pueda descargar sin bloqueos del navegador
        'logo' => ($tienda?->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($tienda->logo_path) && \Illuminate\Support\Facades\Storage::disk('public')->size($tienda->logo_path) < 3000000)
            ? 'data:' . \Illuminate\Support\Facades\Storage::disk('public')->mimeType($tienda->logo_path) . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($tienda->logo_path))
            : null,
        'texto' => $nivel === 1
            ? 'Por crear sus productos, compartir su tienda en línea, registrar a sus clientes y conectar sus redes sociales.'
            : 'Por armar su equipo de trabajo, poner su marca y vender con caja profesional en LocalPro.',
    ];
@endphp
<script>
var D = @json($datos);
var cv = document.getElementById('cv'), x = cv.getContext('2d'), W = cv.width, H = cv.height;

function redondo(px, py, w, h, r) { x.beginPath(); x.moveTo(px + r, py); x.arcTo(px + w, py, px + w, py + h, r); x.arcTo(px + w, py + h, px, py + h, r); x.arcTo(px, py + h, px, py, r); x.arcTo(px, py, px + w, py, r); x.closePath(); }
function estrella(cx, cy, R, r) { x.beginPath(); for (var i = 0; i < 10; i++) { var a = Math.PI / 5 * i - Math.PI / 2, rr = i % 2 ? r : R; x.lineTo(cx + Math.cos(a) * rr, cy + Math.sin(a) * rr); } x.closePath(); x.fill(); }
function ajustar(txt, max, tamMax, peso) { var t = tamMax; do { x.font = peso + ' ' + t + 'px Outfit'; t -= 4; } while (x.measureText(txt).width > max && t > 30); }
function parrafo(txt, cx, y, max, alto) { var pal = txt.split(' '), lin = ''; pal.forEach(function (p) { var prueba = lin ? lin + ' ' + p : p; if (x.measureText(prueba).width > max) { x.fillText(lin, cx, y); y += alto; lin = p; } else lin = prueba; }); x.fillText(lin, cx, y); }

function dibujar(logo) {
    // Fondo
    var g = x.createLinearGradient(0, 0, W, H); g.addColorStop(0, '#0b0d0c'); g.addColorStop(1, '#14301f');
    x.fillStyle = g; x.fillRect(0, 0, W, H);
    // Brillos
    var b = x.createRadialGradient(W * .85, H * .1, 0, W * .85, H * .1, 700); b.addColorStop(0, 'rgba(166,243,74,.25)'); b.addColorStop(1, 'rgba(166,243,74,0)');
    x.fillStyle = b; x.fillRect(0, 0, W, H);
    // Marcos
    x.lineWidth = 6; x.strokeStyle = '#2fd15a'; redondo(60, 60, W - 120, H - 120, 40); x.stroke();
    x.lineWidth = 2; x.strokeStyle = 'rgba(166,243,74,.45)'; redondo(90, 90, W - 180, H - 180, 30); x.stroke();
    x.textAlign = 'center'; x.textBaseline = 'alphabetic';
    // Marca
    x.fillStyle = '#a6f34a'; x.font = '900 64px Outfit'; x.fillText('LocalPro', W / 2, 220);
    x.fillStyle = '#9aa69e'; x.font = '700 30px Outfit'; x.fillText('C E R T I F I C A D O   D E   L O G R O', W / 2, 280);
    // Estrellas
    x.fillStyle = '#f5b301'; var n = D.nivel === 1 ? 3 : 5;
    for (var i = 0; i < n; i++) estrella(W / 2 + (i - (n - 1) / 2) * 90, 370, 36, 15);
    // Texto
    x.fillStyle = '#c9d3cc'; x.font = '500 38px Outfit'; x.fillText('LocalPro reconoce a', W / 2, 480);
    var y = 600;
    if (logo) {
        var s = 150, r = Math.min(s / logo.width, s / logo.height), w = logo.width * r, h = logo.height * r;
        x.save(); x.fillStyle = '#fff'; redondo(W / 2 - 95, 510, 190, 190, 30); x.fill(); x.restore();
        x.drawImage(logo, W / 2 - w / 2, 605 - h / 2, w, h); y = 820;
    }
    x.fillStyle = '#ffffff'; ajustar(D.tienda, W - 400, 120, '900'); x.fillText(D.tienda, W / 2, y);
    x.fillStyle = '#a6f34a'; x.font = '900 60px Outfit'; x.fillText('Nivel ' + D.nivel + ' completado', W / 2, y + 110);
    x.fillStyle = '#dfe8e2'; x.font = '700 40px Outfit'; x.fillText(D.nombre.replace(/^Nivel \d+ · /, ''), W / 2, y + 175);
    x.fillStyle = '#c9d3cc'; x.font = '500 34px Outfit'; parrafo(D.texto, W / 2, y + 250, W - 520, 48);
    // Pie
    x.strokeStyle = 'rgba(255,255,255,.25)'; x.lineWidth = 2;
    x.beginPath(); x.moveTo(330, H - 220); x.lineTo(830, H - 220); x.moveTo(W - 830, H - 220); x.lineTo(W - 330, H - 220); x.stroke();
    x.fillStyle = '#ffffff'; x.font = '700 34px Outfit'; x.fillText(D.fecha, 580, H - 240); x.fillText('Equipo LocalPro', W - 580, H - 240);
    x.fillStyle = '#9aa69e'; x.font = '500 28px Outfit'; x.fillText('Fecha', 580, H - 180); x.fillText('localproo.com', W - 580, H - 180);
    if (D.codigo) { x.font = '500 24px Outfit'; x.fillStyle = '#6b7570'; x.fillText('Código de comercio ' + D.codigo, W / 2, H - 130); }
    // Sello
    x.save(); x.translate(W / 2, H - 230); x.fillStyle = '#a6f34a'; x.beginPath(); x.arc(0, 0, 70, 0, Math.PI * 2); x.fill();
    x.fillStyle = '#0b0d0c'; x.font = '900 26px Outfit'; x.fillText('NIVEL', 0, -8); x.font = '900 50px Outfit'; x.fillText(D.nivel, 0, 42); x.restore();
}

function iniciar() {
    if (!D.logo) return dibujar(null);
    var im = new Image(); im.onload = function () { dibujar(im) }; im.onerror = function () { dibujar(null) }; im.src = D.logo;
}
(document.fonts ? document.fonts.load('900 64px Outfit').then(function () { return document.fonts.ready }) : Promise.resolve()).then(iniciar, iniciar);

var archivo = 'certificado-localpro-nivel-' + D.nivel + '.png';
function descargar() { cv.toBlob(function (bl) { var a = document.createElement('a'); a.href = URL.createObjectURL(bl); a.download = archivo; a.click(); setTimeout(function () { URL.revokeObjectURL(a.href) }, 2000); }); }
function compartir() {
    cv.toBlob(function (bl) {
        var f = new File([bl], archivo, { type: 'image/png' });
        if (navigator.canShare && navigator.canShare({ files: [f] })) navigator.share({ files: [f], title: 'Certificado LocalPro', text: '¡' + D.tienda + ' completó el Nivel ' + D.nivel + ' en LocalPro! 🏆' }).catch(function () {});
        else descargar();
    });
}
</script>
</body>
</html>
