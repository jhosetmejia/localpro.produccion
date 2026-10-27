@extends('layouts.app')

@section('title', 'Pantallas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Para su negocio</small><h1>Pantallas <span>sin cables ni USB</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box text-center" style="max-width:660px;margin:0 auto">
        <i class="fa-solid fa-tv fa-3x mb-3" style="color:#22a050"></i>
        <h3>Maneje los televisores de su negocio desde LocalProo</h3>
        <p class="text-muted">Promociones, menú del día, precios o videos de su marca en el televisor del local. Abra la pantalla una sola vez en el TV con un código y deje que se actualice sola: desde su computador o celular le agrega imágenes o videos y salen uno tras otro. Sin memorias USB ni cables.</p>
        @if (auth()->user()->can('editar-negocio') || auth()->user()->es_superadmin)
        <form method="post" action="{{ route('pantallas.activar') }}">@csrf<button class="btn btn-primary btn-lg"><i class="fa-solid fa-power-off me-1"></i>Activar pantallas</button></form>
        @else
        <p class="fw-bold">Pídale al administrador del negocio que lo active.</p>
        @endif
    </div>
</div>
@endsection
