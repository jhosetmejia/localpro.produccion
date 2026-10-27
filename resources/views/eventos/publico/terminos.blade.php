@extends('eventos.publico._layout')

@section('titulo', 'Términos de compra y reembolsos')
@section('meta')
<meta name="description" content="Términos de compra de entradas y política de reembolsos de los eventos publicados con LocalProo.">
<meta name="robots" content="index, follow">
@endsection

@section('css')
.leg h1{font-size:1.6rem;line-height:1.15;font-weight:800;margin:22px 0 4px}
.leg h3{font-size:1.1rem;font-weight:800;margin:0 0 .6rem;color:var(--c);scroll-margin-top:70px}
.leg p{color:#dcd6e8;font-size:.93rem;margin-bottom:.6rem}
.leg ul{padding-left:1.15rem;color:#dcd6e8;font-size:.93rem}
.leg li{margin:.45rem 0}
.leg strong{color:var(--tx)}
.leg a{color:var(--c);font-weight:700}
.leg .ir{display:flex;gap:.5rem;flex-wrap:wrap;margin:10px 0 0}
.leg .ir a{font-size:.85rem;text-decoration:none;border:1px solid var(--line);border-radius:999px;padding:.4rem .8rem;color:var(--tx);font-weight:700}
@endsection

@section('content')
<div class="leg">
    <h1>Términos de compra y reembolsos</h1>
    <p class="mu" style="font-size:.85rem">Eventos publicados con LocalProo · Actualizado el 27 de octubre de 2026</p>
    <div class="ir"><a href="#compra"><i class="fa-solid fa-ticket"></i> Términos de compra</a><a href="#reembolsos"><i class="fa-solid fa-rotate-left"></i> Reembolsos</a></div>

    @if ($evento && $tienda)
    <div class="aviso" style="margin-top:16px"><i class="fa-solid fa-circle-info"></i> El organizador de <b>{{ $evento->nombre }}</b> es <b>{{ $tienda->nombre }}</b>. Las dudas, cambios y reembolsos de este evento se tratan directamente con el organizador.</div>
    @endif

    <div class="card">
        @include('legal._entradas', ['correo' => $correo])
    </div>

    <p class="mu" style="font-size:.85rem">Estos términos hacen parte de las <a href="{{ route('legal.terminos') }}">condiciones de uso de LocalProo</a> y de su <a href="{{ route('legal.privacidad') }}">política de privacidad</a>.</p>

    @if ($evento)
    <a class="btn btn-l" style="margin-top:16px" href="{{ route('evento.publico', $evento->slug) }}"><i class="fa-solid fa-arrow-left"></i> Volver al evento</a>
    @endif
</div>
@endsection
