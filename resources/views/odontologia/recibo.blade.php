<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Recibo {{ $a->numero }}</title>
<style>
body{margin:0;background:#e6f4f6;font-family:Arial,Helvetica,sans-serif;color:#000}
.t{width:76mm;margin:14px auto;background:#fff;padding:10px 12px;font-size:12px;line-height:1.4}
.c{text-align:center}.t img{max-height:48px;max-width:60mm}
.t h2{font-size:14px;margin:4px 0}.t hr{border:0;border-top:1px dashed #000;margin:7px 0}
.f{display:flex;justify-content:space-between;gap:6px}.g{font-size:15px;font-weight:bold}
.bar{text-align:center;margin:12px}.bar a,.bar button{border:0;border-radius:999px;padding:8px 14px;font-weight:bold;background:#0e7490;color:#fff;text-decoration:none;cursor:pointer;margin:2px;font-size:13px}
.bar a.wa{background:#16a34a}
@media print{body{background:#fff}.bar{display:none}.t{margin:0;width:auto}@page{size:80mm auto;margin:3mm}}
</style>
</head>
<body>
<div class="bar">
    <a href="{{ route('odonto.plan', $plan) }}" style="background:#fff;color:#000;border:1px solid #ccc">← Volver</a>
    @if ($plan->paciente?->whatsapp)<a class="wa" href="https://wa.me/{{ $plan->paciente->whatsapp }}?text={{ rawurlencode($wa) }}" target="_blank" rel="noopener">WhatsApp</a>@endif
    <button onclick="window.print()">Imprimir</button>
</div>
<div class="t">
    <div class="c">
        @if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt=""><br>@endif
        <h2>{{ $tienda->nombre }}</h2>
        @if ($tienda->nit)NIT {{ $tienda->nit }}<br>@endif
        {{ trim(implode(' · ', array_filter([$tienda->direccion, $tienda->telefono]))) }}
    </div>
    <hr>
    <div class="c"><b>RECIBO DE ABONO {{ $a->numero }}</b><br>{{ $a->created_at->format('d/m/Y h:i a') }}</div>
    <hr>
    <div>Paciente: <b>{{ $plan->paciente?->nombre_completo }}</b></div>
    @if ($plan->paciente?->documento)<div>Documento: {{ trim($plan->paciente->tipo_documento . ' ' . $plan->paciente->documento) }}</div>@endif
    <div>Tratamiento: {{ $plan->numero }} · {{ $plan->nombre }}</div>
    @if ($a->nota)<div>Nota: {{ $a->nota }}</div>@endif
    <hr>
    <div class="f"><span>Total del tratamiento</span><span>{{ dinero($plan->total) }}</span></div>
    <div class="f g"><span>ABONO</span><span>{{ dinero($a->monto) }}</span></div>
    <div class="f"><span>Forma de pago</span><span>{{ $a->metodo?->nombre }}</span></div>
    <div class="f"><b>Saldo pendiente</b><b>{{ dinero($a->saldo_despues) }}</b></div>
    <hr>
    <div>Recibió: {{ $a->user?->name }}</div>
    <div class="c" style="margin-top:8px">¡Gracias por su confianza!<br><small>LocalProo · localproo.com</small></div>
</div>
</body>
</html>
