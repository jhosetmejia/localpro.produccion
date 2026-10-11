<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recibo {{ $p->numero }} · {{ $conj->nombre }}</title>
<style>
    body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#f4f8f5;margin:0;padding:16px;color:#151a17}
    .r{max-width:440px;margin:0 auto;background:#fff;border-radius:20px;padding:22px;box-shadow:0 20px 50px -30px rgba(0,0,0,.4)}
    h1{font-size:1.2rem;margin:0}.m{color:#5b665e;font-size:.85rem}
    .tot{font-size:1.8rem;font-weight:800;margin:10px 0}
    table{width:100%;border-collapse:collapse;font-size:.9rem;margin-top:10px}td{padding:6px 0;border-bottom:1px dashed #e3efe5}td:last-child{text-align:right}
    .an{background:#ffe9f3;color:#9b1c5a;border-radius:10px;padding:8px;font-weight:700;text-align:center;margin-bottom:10px}
    .bt{display:flex;gap:8px;flex-wrap:wrap;max-width:440px;margin:14px auto 0}
    .bt a,.bt button{flex:1;border:0;border-radius:999px;padding:.75rem 1rem;font-weight:800;text-align:center;text-decoration:none;cursor:pointer;font-size:.95rem}
    .k{background:#0b0d0c;color:#fff}.w{background:#25d366;color:#fff}.l{background:#fff;color:#151a17;border:1.5px solid #e3efe5!important}
    @media print{.bt{display:none}body{background:#fff;padding:0}.r{box-shadow:none}}
</style>
</head>
<body>
@php
    $tel = numero_whatsapp($p->unidad->contacto);
    $txt = "Recibo de pago " . $p->numero . " · " . $conj->nombre . "\n" . $p->unidad->nombre . "\nValor: " . dinero($p->valor) . "\nFecha: " . $p->fecha->format('d/m/Y') . "\n";
    foreach ($p->cargos as $c) { $txt .= "• " . $c->concepto . ": " . dinero($c->pivot->valor) . "\n"; }
    $txt .= "¡Gracias por su pago!";
@endphp
<div class="r">
    @if ($p->anulado)<div class="an">RECIBO ANULADO</div>@endif
    <h1>{{ $conj->nombre }}</h1>
    <div class="m">{{ $conj->nit ? 'NIT ' . $conj->nit . ' · ' : '' }}{{ $conj->direccion }}{{ $conj->ciudad ? ', ' . $conj->ciudad : '' }}</div>
    <hr style="border:0;border-top:1.5px solid #e3efe5;margin:14px 0">
    <div class="m">Recibo de caja <b>{{ $p->numero }}</b> · {{ $p->fecha->format('d/m/Y') }}</div>
    <div><b>{{ $p->unidad->nombre }}</b> — {{ $p->unidad->persona }}</div>
    <div class="tot">{{ dinero($p->valor) }}</div>
    <div class="m">{{ $p->metodo }}{{ $p->referencia ? ' · ref. ' . $p->referencia : '' }}</div>
    <table>
        @foreach ($p->cargos as $c)<tr><td>{{ $c->concepto }}</td><td>{{ dinero($c->pivot->valor) }}</td></tr>@endforeach
        @if ($p->saldo_favor > 0)<tr><td>Saldo a favor</td><td>{{ dinero($p->saldo_favor) }}</td></tr>@endif
    </table>
    <p class="m" style="margin-top:14px">Recibió: {{ $p->user->name ?? '—' }}</p>
</div>
<div class="bt">
    <button class="k" onclick="window.print()">Imprimir</button>
    @if ($tel && !$p->anulado)<a class="w" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($txt) }}">Enviar por WhatsApp</a>@endif
    <a class="l" href="{{ route('conjunto.unidad', $p->unidad_id) }}">Volver</a>
</div>
@if (!$p->anulado)
<form method="post" action="{{ route('conjunto.pago.anular', $p) }}" style="max-width:440px;margin:10px auto;text-align:center" data-confirm="¿Anular el recibo {{ $p->numero }}?" data-confirm-texto="Los cobros que pagaba vuelven a quedar pendientes." data-confirm-ok="Sí, anular" data-peligro>
    @csrf <button style="background:none;border:0;color:#b0206a;font-weight:700;cursor:pointer">Anular recibo</button>
</form>
@endif
@include('layouts.partials.lp-ui')
</body>
</html>
