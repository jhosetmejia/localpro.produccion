<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado de retención · {{ $tercero?->nombre ?: $nit }} · {{ $anio }}</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;color:#111;max-width:820px;margin:30px auto;padding:0 20px;font-size:14px}
        h1{font-size:20px;text-align:center;margin:0 0 4px}
        .sub{text-align:center;color:#555;margin-bottom:24px}
        table{width:100%;border-collapse:collapse;margin:14px 0}
        th,td{border:1px solid #bbb;padding:7px 9px}
        th{background:#f1f1f1;text-align:left}
        td.n{text-align:right}
        .firma{margin-top:70px;display:flex;gap:60px}
        .firma div{flex:1;border-top:1px solid #333;padding-top:6px;font-size:12px}
        .nota{font-size:11px;color:#555;margin-top:20px}
        .imprimir{position:fixed;top:12px;right:12px}
        @media print{.imprimir{display:none}body{margin:0}}
    </style>
</head>
<body>
<button class="imprimir" onclick="window.print()">Imprimir / guardar PDF</button>
<h1>CERTIFICADO DE RETENCIÓN EN LA FUENTE</h1>
<div class="sub">Año gravable {{ $anio }}</div>
<table>
    <tr><th style="width:35%">Agente de retención</th><td>{{ $cliente->nombre }}</td></tr>
    <tr><th>NIT</th><td>{{ $cliente->nit_completo }}</td></tr>
    <tr><th>Ciudad donde se practicó</th><td>{{ $cliente->ciudad ?: '—' }}</td></tr>
    <tr><th>Retenido</th><td>{{ $tercero?->nombre ?: '—' }}</td></tr>
    <tr><th>NIT / cédula del retenido</th><td>{{ $nit }}</td></tr>
</table>
@php $C = \App\Support\Retenciones::CONCEPTOS; $tb = 0; $tr = 0; $ti = 0; $tc = 0; @endphp
<table>
    <thead><tr><th>Concepto</th><th class="n">Base sometida</th><th class="n">Retención en la fuente</th><th class="n">Retención de IVA</th><th class="n">Retención de ICA</th></tr></thead>
    <tbody>
    @forelse ($por as $k => $x)
    @php $tb += $x['base']; $tr += $x['ret']; $ti += $x['rete_iva']; $tc += $x['rete_ica']; @endphp
    <tr><td>{{ $C[$k][0] ?? $k }}</td><td class="n">${{ number_format($x['base'], 0, ',', '.') }}</td><td class="n">${{ number_format($x['ret'], 0, ',', '.') }}</td><td class="n">${{ number_format($x['rete_iva'], 0, ',', '.') }}</td><td class="n">${{ number_format($x['rete_ica'], 0, ',', '.') }}</td></tr>
    @empty
    <tr><td colspan="5">No hay retenciones a este tercero en los meses cargados de {{ $anio }}.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr><th>Total</th><th class="n">${{ number_format($tb, 0, ',', '.') }}</th><th class="n">${{ number_format($tr, 0, ',', '.') }}</th><th class="n">${{ number_format($ti, 0, ',', '.') }}</th><th class="n">${{ number_format($tc, 0, ',', '.') }}</th></tr></tfoot>
</table>
<p>Las retenciones aquí certificadas fueron declaradas y consignadas oportunamente a nombre de la Dirección de Impuestos y Aduanas Nacionales (DIAN) / del municipio correspondiente.</p>
<p>Fecha de expedición: {{ now()->format('d/m/Y') }}</p>
<div class="firma"><div>Firma del agente de retención o representante legal</div><div>Contador público · T.P.</div></div>
<p class="nota">Certificado expedido de acuerdo con el artículo 381 del Estatuto Tributario. Elaborado con los meses cargados en LocalProo; verifique que estén todos los meses del año antes de entregarlo. De acuerdo con el artículo 10 del Decreto 836 de 1991 (compilado en el DUR 1625 de 2016), los certificados pueden emitirse sin firma autógrafa cuando se expiden por medios electrónicos.</p>
</body>
</html>
