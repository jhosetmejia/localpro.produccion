@extends('layouts.app')

@section('title', $doc->numero)

@section('content')
@php [$txt, $col, $bg] = $doc->estado_info; @endphp
<div class="lp-dash" style="max-width:900px;margin:0 auto">
    <div class="lp-hello"><div><small><a href="{{ route('nomina.electronica', ['mes' => $doc->mes]) }}">Nómina electrónica {{ $doc->mes }}</a></small><h1>{{ $doc->numero }} <span class="badge fs-6 align-middle" style="color:{{ $col }};background:{{ $bg }}">{{ $txt }}</span></h1></div></div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box mb-3">
        <ul class="lp-list">
            <li><div class="t"><b>{{ $doc->nombre_tipo }}</b><small>{{ $doc->empleado?->nombre }} · {{ $doc->empleado?->documento }}</small></div><span class="m">@pesos($doc->total)</span></li>
            <li><div class="t"><b>Devengado / descuentos</b><small>Ambiente: {{ $doc->ambiente }} · Proveedor: {{ $doc->proveedor ?: '—' }} · Intentos: {{ $doc->intentos }}</small></div><span class="m">@pesos($doc->devengado) / @pesos($doc->deducido)</span></li>
            @if ($doc->referencia)<li><div class="t"><b>Ajusta a</b><small>{{ $doc->referencia->numero }}</small></div></li>@endif
        </ul>
        @if ($doc->cune)<p class="small mt-2 mb-0 text-break"><b>CUNE:</b> {{ $doc->cune }}</p>@endif
        @if ($doc->ultimo_error)<div class="alert alert-danger small mt-2 mb-0">{{ $doc->ultimo_error }}</div>@endif
    </div>
    <details class="lp-box"><summary class="fw-bold" style="cursor:pointer">Datos enviados (técnico)</summary>
        <pre class="small mt-2" style="white-space:pre-wrap">{{ json_encode($doc->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
</div>
@endsection
