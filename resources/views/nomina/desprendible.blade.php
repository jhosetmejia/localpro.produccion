<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Desprendible de pago — {{ $p->nombre }}</title>
<style>
    *{box-sizing:border-box} body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111;margin:0;background:#f3f5f4}
    .hoja{background:#fff;max-width:780px;margin:16px auto;padding:28px;border-radius:12px;page-break-after:always}
    .hoja:last-child{page-break-after:auto}
    h1{font-size:18px;margin:0} h2{font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#555;margin:18px 0 6px}
    .top{display:flex;justify-content:space-between;gap:16px;border-bottom:2px solid #111;padding-bottom:12px}
    .muted{color:#666;font-size:12px} table{width:100%;border-collapse:collapse;font-size:13px}
    td{padding:5px 4px;border-bottom:1px solid #eee} td.v{text-align:right;white-space:nowrap} tr.t td{font-weight:700;border-top:1px solid #111;border-bottom:0}
    .cols{display:grid;grid-template-columns:1fr 1fr;gap:20px} .neto{margin-top:16px;background:#111;color:#fff;border-radius:10px;padding:12px 16px;display:flex;justify-content:space-between;font-size:18px;font-weight:700}
    .firma{margin-top:48px;display:flex;justify-content:space-between;gap:24px;font-size:12px} .firma div{flex:1;border-top:1px solid #111;padding-top:4px}
    .imprimir{position:fixed;right:16px;bottom:16px;background:#111;color:#fff;border:0;border-radius:999px;padding:12px 20px;font-weight:700;cursor:pointer}
    @media print{body{background:#fff}.hoja{margin:0;border-radius:0;max-width:none}.imprimir{display:none}}
    @media (max-width:600px){.cols{grid-template-columns:1fr}.hoja{padding:16px}}
</style>
</head>
<body>
@foreach ($liqs as $l)
@php $r = $l->resultado ?? []; $e = $l->empleado; @endphp
<div class="hoja">
    <div class="top">
        <div><h1>{{ $tienda->razon_social ?: $tienda->nombre }}</h1><div class="muted">{{ $tienda->nit ? 'NIT ' . $tienda->nit . ($tienda->dv !== null ? '-' . $tienda->dv : '') : '' }} {{ $tienda->direccion }}</div></div>
        <div style="text-align:right"><b>Desprendible de pago</b><div class="muted">{{ $p->nombre }}<br>{{ $p->fecha_inicio->format('d/m/Y') }} a {{ $p->fecha_fin->format('d/m/Y') }} · pagado {{ $p->fecha_pago->format('d/m/Y') }}</div></div>
    </div>
    <h2>Empleado</h2>
    <table>
        <tr><td><b>{{ $e->nombre }}</b><br><span class="muted">{{ \App\Models\Empleado::DOCUMENTOS[$e->tipo_documento] ?? 'Documento' }} {{ $e->documento }}</span></td>
            <td>{{ $e->cargo ?: '—' }}<br><span class="muted">Ingreso {{ $e->fecha_ingreso?->format('d/m/Y') }}</span></td>
            <td class="v">Salario @pesos($l->salario)<br><span class="muted">{{ $r['dias_periodo'] ?? 0 }} días · {{ $e->eps ?: 'EPS —' }} · {{ $e->afp ?: 'AFP —' }}</span></td></tr>
    </table>
    <div class="cols">
        <div>
            <h2>Devengado</h2>
            <table>
                @foreach ($r['devengos'] ?? [] as $d)<tr><td>{{ $d['concepto'] }}@if($d['cantidad']) <span class="muted">({{ $d['cantidad'] + 0 }})</span>@endif</td><td class="v">@pesos($d['valor'])</td></tr>@endforeach
                <tr class="t"><td>Total devengado</td><td class="v">@pesos($l->total_devengado)</td></tr>
            </table>
        </div>
        <div>
            <h2>Descuentos</h2>
            <table>
                @forelse ($r['deducciones'] ?? [] as $d)<tr><td>{{ $d['concepto'] }}@if($d['porcentaje']) <span class="muted">({{ $d['porcentaje'] + 0 }}%)</span>@endif</td><td class="v">@pesos($d['valor'])</td></tr>@empty<tr><td class="muted">Sin descuentos</td><td></td></tr>@endforelse
                <tr class="t"><td>Total descuentos</td><td class="v">@pesos($l->total_deducido)</td></tr>
            </table>
        </div>
    </div>
    <div class="neto"><span>Neto pagado</span><span>@pesos($l->neto)</span></div>
    <p class="muted">Pago por {{ $e->forma_pago }}{{ $e->banco ? ' · ' . $e->banco : '' }}{{ $e->numero_cuenta ? ' · cuenta terminada en ' . substr($e->numero_cuenta, -4) : '' }}. Base de cotización (IBC): @pesos($l->ibc).</p>
    <div class="firma"><div>Firma del empleador</div><div>Recibí conforme: {{ $e->nombre }}</div></div>
</div>
@endforeach
<button class="imprimir" onclick="window.print()">Imprimir</button>
</body>
</html>
