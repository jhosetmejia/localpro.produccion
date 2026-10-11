@extends('layouts.app')

@section('title', 'Comunicados')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1><span>Comunicados</span></h1>
            <span class="text-muted fw-semibold">Escriba el aviso y compártalo por WhatsApp en el grupo del conjunto. {{ $contactos }} unidades tienen WhatsApp registrado.</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-box mb-3">
        <form method="post" action="{{ route('conjunto.comunicados.store') }}" class="row g-2">
            @csrf
            <div class="col-12"><input name="titulo" class="form-control" maxlength="120" required placeholder="Título (ej. Corte de agua el sábado)"></div>
            <div class="col-12"><textarea name="mensaje" class="form-control" rows="4" maxlength="3000" required placeholder="Mensaje para los residentes"></textarea></div>
            <div class="col-12"><button class="btn btn-primary">Publicar comunicado</button></div>
        </form>
    </div>
    @foreach ($lista as $c)
    @php $txt = '📢 *' . $c->titulo . "*\n" . $conj->nombre . "\n\n" . $c->mensaje . "\n\n— Administración"; @endphp
    <div class="lp-box mb-2">
        <h3 class="mb-1"><span>{{ $c->titulo }}</span><small class="text-muted">{{ $c->created_at->locale('es')->isoFormat('D MMM YYYY') }}</small></h3>
        <p style="white-space:pre-line" class="mb-2">{{ $c->mensaje }}</p>
        <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/?text={{ rawurlencode($txt) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir por WhatsApp</a>
    </div>
    @endforeach
</div>
@endsection
