<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Tiquete de prueba</title>
@include('pos._ticket-css')
<style>
.regla{display:flex;justify-content:space-between;font-weight:700}
.borde{border:1px solid #000;padding:1mm 0;text-align:center;font-weight:700}
.lleno{letter-spacing:0;white-space:nowrap;overflow:hidden}
@if ($embed) body{background:#fff}.tk{margin-top:0;box-shadow:0 0 0 1px #d6dfd9}.acc{display:none}@endif
</style>
</head>
<body>
@unless ($embed)
<div class="acc">
    <button class="p" onclick="window.print()">🖨 Imprimir prueba</button>
    <a class="d" href="{{ route('impresora.edit') }}">Volver</a>
</div>
@endunless
<div class="tk">
    <div class="regla"><span>|&lt;</span><span>IZQ · CENTRO · DER</span><span>&gt;|</span></div>
    <div class="borde">ESTE RECUADRO DEBE VERSE COMPLETO</div>
    <hr>
    <div class="c b big">{{ $tienda?->nombre ?: 'Mi negocio' }}</div>
    <div class="c">Tiquete de prueba · {{ now()->locale('es')->isoFormat('D MMM YYYY h:mm a') }}</div>
    <hr>
    <table>
        <tr><td>2 x Producto de ejemplo</td><td class="r">{{ dinero(24000) }}</td></tr>
        <tr><td>1 x Otro producto con un nombre largo para ver cómo baja</td><td class="r">{{ dinero(8500) }}</td></tr>
    </table>
    <hr>
    <table><tr><td class="b xl">TOTAL</td><td class="r b xl">{{ dinero(32500) }}</td></tr></table>
    <hr>
    <div class="lleno">1234567890123456789012345678901234567890123456789</div>
    <div class="c">Papel {{ $impresora['papel'] }} mm · ancho {{ $impresora['ancho'] }} mm · ajuste {{ $impresora['margen'] }} mm</div>
    <div class="c b">Si se ve completo y centrado, ¡está listo!</div>
    <div class="regla"><span>|&lt;</span><span>FIN</span><span>&gt;|</span></div>
</div>
</body>
</html>
