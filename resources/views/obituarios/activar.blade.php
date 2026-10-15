@extends('layouts.app')

@section('title', 'Obituarios')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Funerarias</small><h1>Obituarios <span>para pantallas</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box text-center" style="max-width:640px;margin:0 auto">
        <i class="fa-solid fa-dove fa-3x mb-3" style="color:#b08d57"></i>
        <h3>Muestre los obituarios en el televisor de cada sala</h3>
        <p class="text-muted">Cree sus salas de velación, elija un diseño (o ponga su propio fondo o video) y para cada fallecido solo escriba el nombre, suba la foto y elija la sala. Sale en la pantalla de la sala al instante y lo puede descargar en 4K o Full HD.</p>
        @if (auth()->user()->can('editar-negocio') || auth()->user()->es_superadmin)
        <form method="post" action="{{ route('obituarios.activar') }}">@csrf<button class="btn btn-primary btn-lg"><i class="fa-solid fa-power-off me-1"></i>Activar obituarios</button></form>
        @else
        <p class="fw-bold">Pídale al administrador del negocio que lo active en Datos del negocio → Módulos.</p>
        @endif
    </div>
</div>
@endsection
