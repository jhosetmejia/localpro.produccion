{{-- Datos fiscales del documento (tiquete y factura web): impuestos, resolución, CUFE/CUDE y QR de la DIAN --}}
@php
    $docE = $venta->documento;
    $tipoDoc = $venta->tipo_doc ?? 'tiquete';
    $nombreDoc = ['fe' => 'Factura electrónica de venta', 'pos_e' => 'Documento equivalente electrónico POS'][$tipoDoc] ?? null;
    $resol = $tipoDoc === 'fe'
        ? [$negocio->fe_resolucion_numero, $negocio->fe_resolucion_fecha, $negocio->fe_prefijo, $negocio->fe_desde, $negocio->fe_hasta, $negocio->fe_resolucion_vigencia]
        : [$negocio->resolucion_numero, $negocio->resolucion_fecha, $negocio->resolucion_prefijo, $negocio->resolucion_desde, $negocio->resolucion_hasta, $negocio->resolucion_vigencia];
    $ad = $venta->adquiriente;
    $resps = \App\Support\Dian\Facturador::responsabilidades($negocio);
    $qrId = 'qr' . $venta->id . ($web ?? false ? 'w' : 't');
@endphp
<div class="fis" style="font-size:{{ ($web ?? false) ? '.82rem' : '10px' }};line-height:1.35">
    @if ($nombreDoc)<div style="text-align:center;font-weight:800;margin-bottom:4px">{{ mb_strtoupper($nombreDoc) }} N.º {{ $venta->numero }}</div>@endif
    @if ($negocio->razon_social && $negocio->razon_social !== $negocio->nombre)<div style="text-align:center">{{ $negocio->razon_social }}</div>@endif
    @if ($negocio->nit && !($web ?? false))<div style="text-align:center">NIT {{ $negocio->nit }}@if ($negocio->dv !== null)-{{ $negocio->dv }}@endif @if ($resps) · {{ implode(', ', $resps) }}@endif</div>@endif
    @if (($web ?? false) && $resps)<div style="text-align:center">{{ implode(', ', $resps) }}</div>@endif
    @if ($ad && ($ad['numero'] ?? '') !== \App\Support\Dian\Catalogos::CONSUMIDOR_FINAL['numero'])
        <div style="margin-top:4px">Adquiriente: <b>{{ $ad['nombre'] }}</b> · {{ ($ad['tipo_doc'] ?? '') === '31' ? 'NIT' : 'Doc.' }} {{ $ad['numero'] }}@if (!empty($ad['dv']))-{{ $ad['dv'] }}@endif</div>
    @endif
    @if (!empty($venta->impuestos_detalle))
    <table style="width:100%;margin-top:6px;border-collapse:collapse">
        <tr style="font-weight:700"><td>Impuesto</td><td style="text-align:right">Base</td><td style="text-align:right">Valor</td></tr>
        @foreach ($venta->impuestos_detalle as $im)
        <tr><td>{{ $im['nombre'] }}</td><td style="text-align:right">{{ dinero($im['base'], false) }}</td><td style="text-align:right">{{ dinero($im['valor'], false) }}</td></tr>
        @endforeach
        <tr style="font-weight:700"><td>Total impuestos</td><td></td><td style="text-align:right">{{ dinero($venta->impuestos, false) }}</td></tr>
    </table>
    @elseif ($negocio->regimen === 'No responsable de IVA')
    <div style="text-align:center;margin-top:4px">No responsable de IVA</div>
    @endif
    @if ($resol[0] && ($tipoDoc !== 'tiquete' || $negocio->resolucion_prefijo))
    <div style="text-align:center;margin-top:6px">Resolución DIAN N.º {{ $resol[0] }}@if ($resol[1]) del {{ $resol[1]->format('d/m/Y') }}@endif
        @if ($resol[2] && $resol[3])<br>Autoriza del {{ $resol[2] }}-{{ $resol[3] }} al {{ $resol[2] }}-{{ $resol[4] }}@endif
        @if ($resol[5])<br>Vigencia hasta {{ $resol[5]->format('d/m/Y') }}@endif
    </div>
    @endif
    @if ($docE)
    <div style="text-align:center;margin-top:6px">
        @if ($docE->cufe)
            <div style="font-weight:700">{{ $tipoDoc === 'fe' ? 'CUFE' : 'CUDE' }}:</div>
            <div style="word-break:break-all;font-size:{{ ($web ?? false) ? '.7rem' : '8px' }}">{{ $docE->cufe }}</div>
            @if ($docE->qr)<div id="{{ $qrId }}" style="display:flex;justify-content:center;margin:6px 0"></div>@endif
            @if ($docE->estado === 'simulado')<div style="font-weight:700">PRUEBA · SIN VALIDEZ FISCAL</div>@endif
        @else
            <div style="font-weight:700">{{ $docE->estado === 'rechazado' ? 'Documento rechazado por la DIAN' : 'Pendiente de validación DIAN' }}</div>
        @endif
        <div>Representación gráfica · Software LocalPro</div>
    </div>
    @if ($docE->qr)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>try { new QRCode(document.getElementById(@json($qrId)), { text: @json($docE->qr), width: {{ ($web ?? false) ? 120 : 110 }}, height: {{ ($web ?? false) ? 120 : 110 }}, correctLevel: QRCode.CorrectLevel.M }); } catch (e) {}</script>
    @endif
    @endif
</div>
