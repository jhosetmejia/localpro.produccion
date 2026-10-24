@extends('layouts.app')

@section('title', 'Eventos')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>LocalProo Eventos</small><h1>Venda entradas <span>con QR</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box text-center" style="max-width:640px;margin:0 auto">
        <i class="fa-solid fa-ticket fa-3x mb-3" style="color:#a855f7"></i>
        <h3>Página de su evento, registro y entradas con QR</h3>
        <p class="text-muted">Cree el evento con su afiche y precio y comparta el enlace. Cada persona se registra, le paga por Nequi y le manda el comprobante por WhatsApp. Usted aprueba el pago y el sistema le genera su entrada con QR, que se escanea en la puerta con el celular.</p>
        @if ($puedeActivar)
        <form method="post" action="{{ route('eventos.activar') }}">@csrf<button class="btn btn-primary btn-lg"><i class="fa-solid fa-power-off me-1"></i>Activar eventos</button></form>
        @else
        <p class="fw-bold">Pídale al administrador del negocio que lo active en Datos del negocio → Módulos.</p>
        @endif
    </div>
</div>
@endsection
