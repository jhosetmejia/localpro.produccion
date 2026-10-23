@extends('layouts.app')

@section('title', 'Conectar Facebook')

@php
    $wa = preg_replace('/\D/', '', (string) $soporte);
    $msg = "¡Hola LocalPro! 👋 Quiero conectar Facebook e Instagram en mi negocio *" . $tienda->nombre . "* (" . $tienda->codigo . ").\nEl enlace de mi perfil de Facebook (el que administra la página) es: ";
@endphp

@section('content')
<div class="lp-dash">
    <div class="lp-box" style="max-width:640px;margin:0 auto;border-radius:26px;padding:1.6rem">
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:.6rem">
            <span style="width:46px;height:46px;border-radius:14px;background:#1877f2;color:#fff;display:grid;place-items:center;font-size:1.4rem"><i class="fa-brands fa-facebook-f"></i></span>
            <span style="width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af);color:#fff;display:grid;place-items:center;font-size:1.4rem"><i class="fa-brands fa-instagram"></i></span>
        </div>
        <h2 style="font-weight:900;font-size:1.5rem;margin:.2rem 0 .4rem">Le activamos la conexión en minutos</h2>
        <p class="text-muted fw-semibold">Facebook está terminando de aprobar la conexión de LocalPro para todos los negocios. Mientras tanto, la activamos negocio por negocio: así no le sale el aviso de "La app no está disponible".</p>
        <ol class="fw-semibold" style="line-height:1.7">
            <li>Toque el botón y envíenos el <b>enlace de su perfil de Facebook</b> (el de la persona que administra la página del negocio).</li>
            <li>Le llegará una invitación de Facebook: acéptela en <b>developers.facebook.com/requests</b> (le enviamos el paso a paso).</li>
            <li>Vuelva aquí y toque <b>Conectar Facebook e Instagram</b>. ¡Listo!</li>
        </ol>
        @if ($wa)
        <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="btn btn-success btn-lg rounded-pill fw-bold w-100"><i class="fa-brands fa-whatsapp me-1"></i>Pedir mi acceso por WhatsApp</a>
        @else
        <a href="{{ route('redes.conectar') }}" class="btn btn-primary btn-lg rounded-pill fw-bold w-100"><i class="fa-brands fa-facebook-f me-1"></i>Conectar Facebook e Instagram</a>
        @endif
        <a href="{{ route('redes.index') }}" class="btn btn-light rounded-pill w-100 mt-2">Volver</a>
    </div>
</div>
@endsection
