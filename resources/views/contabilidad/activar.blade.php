@extends('layouts.app')

@section('title', 'Contabilidad')

@section('content')
<div class="lp-dash">
    <div class="lp-hello"><div><small>Para contadores y analistas</small><h1>Retenciones <span>e IVA</span></h1></div></div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box text-center" style="max-width:680px;margin:0 auto">
        <i class="fa-solid fa-calculator fa-3x mb-3" style="color:#11702f"></i>
        <h3>Cruce de facturas DIAN contra su contabilidad</h3>
        <p class="text-muted">Suba el listado de facturas que descarga de la DIAN y la base de su programa contable (Contapyme, Siigo, World Office…). LocalProo cruza las dos, le muestra qué no está contabilizado, dónde no cuadra el IVA y qué retenciones faltaron, y le deja el borrador del 350 y del IVA. Maneja varios clientes, personas naturales y empresas.</p>
        @if (auth()->user()->can('editar-negocio') || auth()->user()->es_superadmin)
        <form method="post" action="{{ route('contabilidad.activar') }}">@csrf<button class="btn btn-primary btn-lg"><i class="fa-solid fa-power-off me-1"></i>Activar contabilidad</button></form>
        @endif
        <a href="{{ route('contabilidad.guia') }}" class="btn btn-link mt-2">Ver cómo funciona</a>
    </div>
</div>
@endsection
