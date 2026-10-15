@extends('layouts.app')

@section('title', 'Mis redes · Cuentas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hashtag me-1"></i>Mis redes</small><h1>Cuentas <span>conectadas</span></h1>
            <span class="text-muted fw-semibold">Las páginas de Facebook y cuentas de Instagram de su negocio.</span></div>
        @php
            $cupo = \App\Http\Controllers\redesController::cupoRedes();
            $tiendaR = \App\Support\TiendaActual::tienda();
            $sopR = preg_replace('/\D/', '', (string) \App\Models\Ajuste::valor('soporte_whatsapp'));
            $precioR = \App\Models\Ajuste::valor('precio_red_extra');
            $msjR = 'Hola LocalProo, quiero agregar otra red social a mi negocio ' . ($tiendaR?->nombre) . ' (' . ($tiendaR?->codigo ?: '#' . $tiendaR?->id) . ').';
        @endphp
        @if ($activo)
        <div class="lp-quick">
            @if ($cupo > 0)
            <a href="{{ route('redes.conectar') }}" class="btn btn-lg" style="background:#1877f2;color:#fff;font-weight:800"><i class="fa-brands fa-facebook me-1"></i>Conectar otra página</a>
            @else
            <button type="button" class="btn btn-lg" id="redExtra" style="background:#1877f2;color:#fff;font-weight:800"><i class="fa-solid fa-plus me-1"></i>Agregar otra red social</button>
            @endif
        </div>
        @endif
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @include('redes._tabs')

    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($cuentas as $c)
            <li style="{{ $c->estado === 'desconectada' ? 'opacity:.55' : '' }}">
                <div class="av" style="overflow:hidden">@if ($c->page_foto)<img src="{{ $c->page_foto }}" alt="" style="width:100%;height:100%;object-fit:cover">@else<i class="fa-brands fa-facebook-f"></i>@endif</div>
                <div class="t">
                    <b>{{ $c->page_nombre }}</b>
                    <small><span class="rd-ico facebook" style="width:16px;height:16px;font-size:.55rem"><i class="fa-brands fa-facebook-f"></i></span> Página de Facebook
                        · @if ($c->ig_usuario)<span class="rd-ico instagram" style="width:16px;height:16px;font-size:.55rem"><i class="fa-brands fa-instagram"></i></span> {{ '@' . $c->ig_usuario }}@else<span style="color:#a86b00">Sin Instagram vinculado</span>@endif
                        · {{ $c->sincronizado_en ? 'actualizada ' . $c->sincronizado_en->locale('es')->diffForHumans() : 'conectada ' . $c->created_at->format('d/m/Y') }}</small>
                    @if ($c->estado === 'error')<small class="d-block" style="color:#b0173f"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $c->error }} — vuelva a conectarla.</small>@endif
                </div>
                @if ($c->estado === 'desconectada')
                <span class="badge bg-secondary">Desconectada</span>
                @else
                <span class="badge {{ $c->estado === 'error' ? 'bg-danger' : 'bg-success' }}">{{ $c->estado === 'error' ? 'Con error' : 'Activa' }}</span>
                <form method="post" action="{{ route('redes.desconectar', $c) }}" data-confirm="¿Desconectar {{ $c->page_nombre }}?" data-confirm-texto="Ya no llegarán mensajes nuevos ni podrá publicar en ella desde LocalPro. Los chats guardados se conservan." data-confirm-ok="Sí, desconectar" data-peligro>@csrf<button class="btn btn-sm btn-outline-danger">Desconectar</button></form>
                @endif
            </li>
            @empty
            <li class="lp-empty">No hay cuentas conectadas. <a href="{{ route('redes.index') }}">Conectar ahora</a></li>
            @endforelse
        </ul>
        <p class="small text-muted mt-3 mb-1"><i class="fa-solid fa-layer-group me-1"></i>Su plan incluye <b>{{ max(1, (int) ($tiendaR?->redes_max ?? 1)) }} {{ max(1, (int) ($tiendaR?->redes_max ?? 1)) === 1 ? 'red social' : 'redes sociales' }}</b> (página de Facebook con su Instagram).@if ($cupo <= 0) Para conectar otra, solicite una red adicional.@endif Si una cuenta falla, vuelva a conectarla con <a href="{{ route('redes.conectar', ['reconectar' => 1]) }}">Reconectar</a>.</p>
        <p class="small text-muted mt-0 mb-0"><i class="fa-solid fa-circle-info me-1"></i>¿No aparece su Instagram? Debe ser <b>cuenta profesional</b> y estar <b>vinculada a la página</b> de Facebook. Vincúlela y toque "Conectar otra página" para actualizar.</p>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var abrir = function () {
        if (!window.LP || !LP.confirmar) return;
        LP.confirmar({
            titulo: '¿Desea agregar otra red social?',
            texto: 'Su plan incluye {{ max(1, (int) ($tiendaR?->redes_max ?? 1)) }} red social y ya está conectada. Cada red adicional tiene un costo extra{{ $precioR ? ' de ' . $precioR : '' }}. Escríbanos y se la activamos.',
            ok: 'Sí, quiero agregarla', cancelar: 'Ahora no'
        }).then(function (si) {
            if (!si) return;
            @if ($sopR)
            window.open('https://wa.me/{{ $sopR }}?text=' + encodeURIComponent(@json($msjR)), '_blank');
            @else
            LP.toast('Comuníquese con LocalProo para activar otra red social.', 'info');
            @endif
        });
    };
    var b = document.getElementById('redExtra');
    if (b) b.addEventListener('click', abrir);
    @if (session('red_extra')) document.addEventListener('DOMContentLoaded', function () { setTimeout(abrir, 300); }); @endif
})();
</script>
@endpush
