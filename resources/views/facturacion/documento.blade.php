@extends('layouts.app')

@section('title', 'Documento ' . $doc->numero)

@section('content')
@php $p = $doc->payload ?? []; @endphp
<div class="lp-dash">
    <div class="lp-hello"><div><small>{{ $doc->nombre_tipo }}</small><h1>{{ $doc->numero }}</h1>
        <span class="fw-bold px-3 py-1 rounded-pill" style="color:{{ $doc->estado_info[1] }};background:{{ $doc->estado_info[2] }}">{{ $doc->estado_info[0] }}</span></div>
        <div class="lp-quick"><a href="{{ route('facturacion.index') }}" class="btn btn-ghost"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
            @if ($doc->venta)<a href="{{ route('pos.ticket', $doc->venta) }}" class="btn btn-primary" target="_blank"><i class="fa-solid fa-receipt me-1"></i>Ver representación gráfica</a>@endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-grid">
        <div class="lp-box">
            <h3>Datos del documento</h3>
            <table class="table table-sm">
                <tr><th>Fecha</th><td>{{ $doc->created_at->format('d/m/Y h:i a') }}</td></tr>
                <tr><th>Total</th><td>{{ dinero($doc->total) }}</td></tr>
                <tr><th>Ambiente</th><td>{{ $doc->ambiente === 'produccion' ? 'Producción' : 'Pruebas' }}</td></tr>
                <tr><th>Proveedor</th><td>{{ $doc->proveedor ?: '—' }}</td></tr>
                <tr><th>Intentos</th><td>{{ $doc->intentos }}</td></tr>
                @if ($doc->referencia)<tr><th>Corrige a</th><td>{{ $doc->referencia->numero }} · {{ $p['referencia']['motivo'] ?? $p['motivo'] ?? '' }}</td></tr>@endif
                <tr><th>{{ $doc->tipo === 'fe' ? 'CUFE' : 'CUDE' }}</th><td style="word-break:break-all;font-size:.8rem">{{ $doc->cufe ?: 'Lo asigna el proveedor al enviarlo' }}</td></tr>
                @if ($doc->ultimo_error)<tr><th>Mensaje</th><td style="color:#b42318">{{ $doc->ultimo_error }}</td></tr>@endif
            </table>
            <div class="d-flex gap-2 flex-wrap">
                @if ($doc->pdf_url)<a href="{{ $doc->pdf_url }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm"><i class="fa-regular fa-file-pdf me-1"></i>PDF</a>@endif
                @if ($doc->xml_url)<a href="{{ $doc->xml_url }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm"><i class="fa-solid fa-code me-1"></i>XML</a>@endif
                @if ($doc->qr)<a href="{{ $doc->qr }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="fa-solid fa-qrcode me-1"></i>Consultar en la DIAN</a>@endif
                @if (in_array($doc->estado, ['error', 'rechazado', 'pendiente'], true))
                <form method="post" action="{{ route('facturacion.reintentar', $doc) }}">@csrf<button class="btn btn-primary btn-sm"><i class="fa-solid fa-rotate me-1"></i>Enviar de nuevo</button></form>
                @endif
            </div>
        </div>
        <div class="lp-box">
            <h3>Totales</h3>
            @if (!empty($p['totales']))
            <table class="table table-sm">
                <tr><th>Base gravable</th><td class="text-end">{{ dinero($p['totales']['base']) }}</td></tr>
                <tr><th>IVA</th><td class="text-end">{{ dinero($p['totales']['iva']) }}</td></tr>
                @if ($p['totales']['inc'] > 0)<tr><th>Impoconsumo</th><td class="text-end">{{ dinero($p['totales']['inc']) }}</td></tr>@endif
                <tr><th>Total</th><td class="text-end fw-bold">{{ dinero($p['totales']['total']) }}</td></tr>
            </table>
            <h3 class="mt-3">Cliente</h3>
            <p class="mb-0">{{ $p['adquiriente']['nombre'] ?? 'Consumidor final' }} · {{ $p['adquiriente']['numero'] ?? '' }}@if (!empty($p['adquiriente']['email'])) · {{ $p['adquiriente']['email'] }}@endif</p>
            @else
            <p class="text-muted">El detalle se arma al enviarlo al proveedor.</p>
            @endif
        </div>
    </div>
</div>
@endsection
