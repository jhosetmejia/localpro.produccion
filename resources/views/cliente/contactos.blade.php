@extends('layouts.app')

@section('title', 'Crear base de datos de clientes')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-address-book me-1"></i>Clientes</small><h1>Crear base de datos de <span>clientes</span></h1>
            <span class="text-muted fw-semibold">Empiece con los contactos de su celular: en minutos tiene una base de datos para llegar a clientes nuevos.</span></div>
        <div class="lp-quick"><a href="{{ route('clientes.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Mis clientes</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @include('cliente._contactos-celular', ['accion' => route('clientes.importar.subir'), 'extra' => 'cliente._contactos-ideas'])
</div>
@endsection
