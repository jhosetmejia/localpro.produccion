<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Cotización {{ $c->numero }} · {{ $tienda->nombre }}</title>
<style>
body{margin:0;background:#eef1ef;font-family:Arial,Helvetica,sans-serif}
.bar{max-width:820px;margin:16px auto;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;padding:0 12px}
.bar button,.bar a{border:0;border-radius:999px;padding:10px 18px;font-weight:bold;cursor:pointer;background:#0b0d0c;color:#a6f34a;text-decoration:none;font-size:14px}
.bar a{background:#25d366;color:#fff}
.wrap{padding:0 12px 30px}
@media print{body{background:#fff}.bar{display:none}.wrap{padding:0}}
</style>
</head>
<body>
@php $waNeg = $tienda->telefono ? numero_whatsapp($tienda->telefono) : null; @endphp
<div class="bar">
    @if ($waNeg)<a href="https://wa.me/{{ $waNeg }}?text={{ rawurlencode('Hola 👋 Le escribo por la cotización ' . $c->numero . '.') }}" target="_blank" rel="noopener">Responder por WhatsApp</a>@endif
    <button onclick="window.print()">Descargar PDF</button>
</div>
<div class="wrap">@include('cotizacion._hoja')</div>
</body>
</html>
