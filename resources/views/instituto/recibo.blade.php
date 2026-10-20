<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recibo {{ $p->numero }} · {{ $inst->nombre }}</title>
<style>
    body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#f4f8f5;margin:0;padding:16px;color:#151a17}
    .r{max-width:440px;margin:0 auto;background:#fff;border-radius:20px;padding:22px;box-shadow:0 20px 50px -30px rgba(0,0,0,.4)}
    .cab{display:flex;gap:12px;align-items:center}.cab img{width:52px;height:52px;object-fit:contain;border-radius:10px}
    h1{font-size:1.15rem;margin:0}.m{color:#5b665e;font-size:.85rem}
    .tot{font-size:1.8rem;font-weight:800;margin:10px 0}
    table{width:100%;border-collapse:collapse;font-size:.9rem;margin-top:10px}td{padding:6px 0;border-bottom:1px dashed #e3efe5}td:last-child{text-align:right;white-space:nowrap;padding-left:10px}
    .an{background:#ffe9f3;color:#9b1c5a;border-radius:10px;padding:8px;font-weight:700;text-align:center;margin-bottom:10px}
    .bt{display:flex;gap:8px;flex-wrap:wrap;max-width:440px;margin:14px auto 0}
    .bt a,.bt button{flex:1;border:0;border-radius:999px;padding:.75rem 1rem;font-weight:800;text-align:center;text-decoration:none;cursor:pointer;font-size:.95rem}
    .k{background:#0b0d0c;color:#fff}.w{background:#25d366;color:#fff}.l{background:#fff;color:#151a17;border:1.5px solid #e3efe5!important}
    @media print{.bt,.anular{display:none}body{background:#fff;padding:0}.r{box-shadow:none}}
</style>
</head>
<body>
@php
    $e = $p->estudiante;
    $tel = numero_whatsapp($e->contacto);
    $txt = "Recibo de pago " . $p->numero . " · " . $inst->nombre . "\n" . $e->nombre . "\nValor: " . dinero($p->valor) . "\nFecha: " . $p->fecha->format('d/m/Y') . "\n";
    foreach ($p->cargos as $c) { $txt .= "• " . $c->concepto . ": " . dinero($c->pivot->valor) . "\n"; }
    $saldo = $e->saldo();
    $txt .= ($saldo > 0 ? "Saldo pendiente: " . dinero($saldo) . "\n" : "¡Quedó al día!\n") . "¡Gracias por su pago!";
@endphp
<div class="r">
    @if ($p->anulado)<div class="an">RECIBO ANULADO</div>@endif
    <div class="cab">@if ($inst->logo_url)<img src="{{ $inst->logo_url }}" alt="">@endif<div><h1>{{ $inst->nombre }}</h1>
        <div class="m">{{ $inst->nit ? 'NIT ' . $inst->nit . ' · ' : '' }}{{ $inst->direccion }}{{ $inst->ciudad ? ', ' . $inst->ciudad : '' }}</div></div></div>
    <hr style="border:0;border-top:1.5px solid #e3efe5;margin:14px 0">
    <div class="m">Recibo de caja <b>{{ $p->numero }}</b> · {{ $p->fecha->format('d/m/Y') }}</div>
    <div><b>{{ $e->nombre }}</b> — {{ $e->tipo_doc }} {{ $e->documento }}</div>
    <div class="m">{{ $e->programa->nombre ?? '' }}</div>
    <div class="tot">{{ dinero($p->valor) }}</div>
    <div class="m">{{ $p->metodo }}{{ $p->referencia ? ' · ref. ' . $p->referencia : '' }}</div>
    <table>
        @foreach ($p->cargos as $c)<tr><td>{{ $c->concepto }}</td><td>{{ dinero($c->pivot->valor) }}</td></tr>@endforeach
        @if ($p->saldo_favor > 0)<tr><td>Saldo a favor</td><td>{{ dinero($p->saldo_favor) }}</td></tr>@endif
    </table>
    @unless ($p->anulado)<p class="m" style="margin-top:10px">{{ $saldo > 0 ? 'Saldo pendiente después de este pago: ' . dinero($saldo) : 'Al día con el instituto ✅' }}</p>@endunless
    <p class="m" style="margin-top:14px">Recibió: {{ $p->user->name ?? '—' }}</p>
</div>
<div class="bt">
    <button class="k" onclick="window.print()">Imprimir</button>
    @if ($tel && !$p->anulado)<a class="w" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($txt) }}">Enviar por WhatsApp</a>@endif
    <a class="l" href="{{ route('instituto.estudiante', $p->estudiante_id) }}">Volver</a>
</div>
@if (!$p->anulado && $admin)
<form method="post" action="{{ route('instituto.pago.anular', $p) }}" class="anular" style="max-width:440px;margin:10px auto;text-align:center" data-confirm="¿Anular el recibo {{ $p->numero }}?" data-confirm-texto="Los cobros que pagaba vuelven a quedar pendientes." data-confirm-ok="Sí, anular" data-peligro>
    @csrf <button style="background:none;border:0;color:#b0206a;font-weight:700;cursor:pointer">Anular recibo</button>
</form>
@endif
@include('layouts.partials.lp-ui')
</body>
</html>
