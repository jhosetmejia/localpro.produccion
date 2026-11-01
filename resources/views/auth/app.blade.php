<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="description" content="Descargue la app de LocalPro en su celular" />
    <title>Descargar la app — LocalPro</title>
    <style>
        .app-pasos{list-style:none;padding:0;margin:1rem 0 1.2rem;counter-reset:p}
        .app-pasos li{counter-increment:p;display:flex;gap:12px;align-items:center;padding:10px 0;border-top:1px solid var(--line,#eef1ef);font-weight:700}
        .app-pasos li::before{content:counter(p);flex:0 0 30px;height:30px;border-radius:50%;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-weight:900}
        .app-icono{width:76px;height:76px;border-radius:20px;box-shadow:0 14px 30px -14px rgba(22,179,74,.8);margin-bottom:.8rem}
        .btn-verde{background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c;font-weight:800}
        .app-lista{display:none;color:#16b34a;font-weight:800;margin:.4rem 0 .8rem}
        @media (display-mode: standalone){.app-lista{display:block}}
    </style>
</head>
<body>
@php $email = (string) request('email'); @endphp
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div>
            <h2 class="serif">Su trabajo, <em>en su celular.</em></h2>
            <p>Venda, cobre y revise el inventario desde la app de LocalPro.</p>
        </div>
        <small>© {{ date('Y') }} LocalProo · Todos los derechos reservados</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card rise">
            <img src="/icons/icon-192.png" alt="LocalPro" class="app-icono">
            <h1 class="serif">Descargue LocalPro</h1>
            <p class="sub">Instálela en su celular y entre con el correo y la clave que le enviaron.</p>

            <p class="app-lista"><i class="fa-solid fa-circle-check"></i> Ya tiene la app instalada.</p>

            <ol class="app-pasos">
                <li><span>Toque <b>Descargar la app</b> y confirme con <b>Instalar</b></span></li>
                <li><span>Abra LocalPro desde el ícono de su pantalla</span></li>
                <li><span>Entre con su correo y su clave</span></li>
            </ol>

            <button type="button" class="btn btn-verde btn-block lp-solo-navegador" onclick="window.lpInstalarApp && window.lpInstalarApp()">
                <i class="fa-solid fa-download"></i> Descargar la app
            </button>
            <a href="{{ route('login', $email !== '' ? ['email' => $email] : []) }}" class="btn btn-ink btn-block" style="margin-top:.7rem">
                Entrar ahora <i class="fa-solid fa-arrow-right"></i>
            </a>
            <p class="alt" style="font-size:.88rem">En iPhone: abra este link en <b>Safari</b> para poder instalarla.</p>
        </div>
    </main>
</div>
</body>
</html>
