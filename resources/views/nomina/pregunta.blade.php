@extends('layouts.app')

@section('title', 'Nómina')

@section('content')
<div class="lp-dash" style="max-width:900px;margin:0 auto">
    <div class="lp-hello"><div><small>Nómina</small><h1>¿Su negocio tiene <span>empleados</span>?</h1><span class="text-muted fw-semibold">Elija cómo quiere manejar la nómina. Puede cambiarlo cuando quiera.</span></div></div>
    @include('layouts.partials.lp-flash')

    @php $opciones = [
        ['no', 'fa-user', 'No tengo empleados', 'No uso nómina por ahora. El menú de nómina queda disponible para activarla después.', 'btn-dark'],
        ['interna', 'fa-file-invoice-dollar', 'Nómina sin DIAN', 'Liquido salarios, novedades, seguridad social y desprendibles, pero no transmito a la DIAN (lo hace mi contador u otro sistema).', 'btn-dark'],
        ['electronica', 'fa-building-columns', 'Nómina electrónica DIAN', 'Todo lo anterior y además envío cada mes el documento soporte de nómina electrónica a la DIAN.', 'btn-primary'],
    ]; @endphp
    <div class="lp-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
        @foreach ($opciones as [$v, $ic, $tit, $txt, $btn])
        <form method="post" action="{{ route('nomina.modo') }}" class="lp-box d-flex flex-column" style="{{ $tienda->nomina_modo === $v ? 'outline:2px solid var(--lp-lime)' : '' }}">
            @csrf
            <input type="hidden" name="nomina_modo" value="{{ $v }}">
            <div class="fs-2 mb-2"><i class="fa-solid {{ $ic }}"></i></div>
            <h3>{{ $tit }}</h3>
            <p class="text-muted flex-grow-1">{{ $txt }}</p>
            <button class="btn {{ $btn }} w-100">{{ $tienda->nomina_modo === $v ? 'Opción actual' : 'Elegir' }}</button>
        </form>
        @endforeach
    </div>
    <p class="text-muted small mt-3"><i class="fa-solid fa-circle-info me-1"></i>La nómina electrónica es obligatoria para los empleadores que quieran deducir los pagos laborales en su declaración de renta (Resolución DIAN 000013 de 2021). Se transmite dentro de los 10 primeros días hábiles del mes siguiente.</p>
</div>
@endsection
