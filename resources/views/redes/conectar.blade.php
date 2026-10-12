@extends('layouts.app')

@section('title', 'Mis redes')

@push('css')
<style>
.cx-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:2rem 1.6rem;position:relative;overflow:hidden;margin-bottom:1rem}
.cx-hero::after{content:"";position:absolute;width:300px;height:300px;border-radius:50%;border:44px solid rgba(166,243,74,.12);right:-90px;top:-110px}
.cx-hero small{color:#9fe3b0;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem}
.cx-hero h1{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;line-height:1.1;margin:.4rem 0 .6rem;max-width:22ch;position:relative;z-index:1}
.cx-hero h1 em{font-style:normal;color:#2fd15a}
.cx-hero p{color:#c9d3cc;max-width:46ch;font-weight:500;position:relative;z-index:1}
.cx-redes{display:flex;gap:.6rem;margin:1.2rem 0;position:relative;z-index:1}
.cx-redes span{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;font-size:1.6rem;color:#fff}
.cx-redes .f{background:#1877f2}.cx-redes .i{background:linear-gradient(45deg,#f9a825,#e91e63 55%,#8e24aa)}
.cx-btn{display:inline-flex;align-items:center;gap:.6rem;background:#1877f2;color:#fff;font-weight:900;border-radius:999px;padding:1rem 1.6rem;text-decoration:none;font-size:1.05rem;position:relative;z-index:1;box-shadow:0 14px 30px -12px rgba(24,119,242,.8)}
.cx-btn:hover{color:#fff;filter:brightness(1.08)}
.cx-ben{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem;margin-bottom:1rem}
.cx-ben div{background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:1.1rem}
.cx-ben i{font-size:1.4rem;color:#16b34a}
.cx-ben b{display:block;margin:.5rem 0 .2rem}
.cx-ben p{color:#5b665e;margin:0;font-size:.9rem}
.cx-req li{margin-bottom:.4rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="cx-hero">
        <small>Mis redes</small>
        <h1>Responda sus chats y publique en <em>Facebook e Instagram</em> desde LocalPro.</h1>
        <p>Todos los mensajes de sus clientes en un solo lugar, junto a sus productos y precios. Sin saltar de app en app.</p>
        <div class="cx-redes"><span class="f"><i class="fa-brands fa-facebook-f"></i></span><span class="i"><i class="fa-brands fa-instagram"></i></span></div>
        @can('editar-negocio')
            @if ($activo)
            <a href="{{ route('redes.conectar') }}" class="cx-btn"><i class="fa-brands fa-facebook"></i> Conectar Facebook e Instagram</a>
            @else
            <div class="alert alert-warning mb-0" style="max-width:520px;position:relative;z-index:1"><b>Muy pronto.</b> Estamos activando la conexión con Facebook e Instagram para todos los negocios.</div>
            @endif
        @else
        <div class="alert alert-light mb-0" style="max-width:520px;position:relative;z-index:1">El dueño del negocio debe conectar las cuentas desde su usuario.</div>
        @endcan
    </div>

    <div class="cx-ben">
        <div><i class="fa-regular fa-comments"></i><b>Todos los chats juntos</b><p>Messenger e Instagram Direct en una sola bandeja, con aviso de los no leídos.</p></div>
        <div><i class="fa-solid fa-shirt"></i><b>Envíe productos en un toque</b><p>Busque el producto y mándele al cliente la foto, el precio y el enlace para comprar.</p></div>
        <div><i class="fa-regular fa-paper-plane"></i><b>Publique en las dos redes</b><p>Una foto, un texto y listo: sale en Facebook e Instagram, ahora o a la hora que programe.</p></div>
        <div><i class="fa-solid fa-user-plus"></i><b>Convierta chats en clientes</b><p>Guarde a quien le escribe como cliente y márquelo: nuevo, interesado, hizo pedido.</p></div>
    </div>

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-list-check me-1"></i> Antes de conectar, revise</span></h3>
        <ol class="cx-req mb-0">
            <li>Tenga una <b>página de Facebook</b> de su negocio y que usted sea <b>administrador</b>.</li>
            <li>Su <b>Instagram</b> debe ser <b>cuenta profesional</b> (de empresa o creador) y estar <b>vinculada a esa página</b> de Facebook. <span class="text-muted">En Instagram: Configuración → Cuenta → Cambiar a cuenta profesional, y luego en la página de Facebook: Configuración → Instagram → Conectar.</span></li>
            <li>Al conectar, Facebook le preguntará qué páginas y cuentas autoriza: <b>elija su negocio y acepte todos los permisos</b>.</li>
        </ol>
    </div>
</div>
@endsection
