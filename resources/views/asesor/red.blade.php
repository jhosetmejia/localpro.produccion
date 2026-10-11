@extends('layouts.app')

@section('title', 'Mi red')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-sitemap me-1"></i>Red comercial</small><h1>Mi <span>red</span></h1>
            <span class="text-muted fw-semibold">{{ $total }} negocio(s) en su red · toque cada persona para abrir o cerrar</span></div>
        <div class="lp-quick"><button type="button" class="mr-btn" data-ver-red><span class="mr-pulso"></span> Ver mi red</button></div>
    </div>
    <div class="lp-box">
        <div class="rn-ley">
            <span style="--c:#16b34a">Plan al día</span><span style="--c:#f2b705">Vence pronto</span><span style="--c:#e0245e">Vencido</span><span style="--c:#b8c2bc">Plan gratis</span>
        </div>
        <ul class="rn-tree">
            @foreach ($raices as $n)
                @include('red._nodo', ['n' => $n, 'hijos' => $hijos, 'negocios' => $negocios, 'admin' => false, 'nivel' => 0])
            @endforeach
        </ul>
    </div>
    @include('red._mapa', ['raices' => $raices, 'hijos' => $hijos, 'negocios' => $negocios, 'admin' => false, 'raiz' => null])
</div>
@endsection
