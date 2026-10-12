<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="robots" content="noindex">
    <title>Únete como asesor — LocalPro</title>
    <style>
        .in{width:100%;padding:.85rem 1rem;border-radius:14px;border:2px solid var(--line);background:#fafdfb;font:inherit;font-weight:600;color:var(--ink)}
        select.in{appearance:auto}
        .in:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 5px rgba(47,209,90,.18)}
        .dos{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        @media (max-width:480px){.dos{grid-template-columns:1fr}}
        .listo{text-align:center;padding:10px 0}
        .listo .ico{width:72px;height:72px;border-radius:22px;background:var(--grad);display:grid;place-items:center;font-size:1.8rem;margin:0 auto 1rem}
    </style>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div>
            <h2 class="serif">Gana con LocalPro <em>desde tu celular</em>.</h2>
            <p>{{ $invita->nombre }} te invita a su equipo de asesores. Inscribe negocios, gana comisión por cada uno y cada año cuando renuevan.</p>
            <ul class="auth-list">
                <li><span><i class="fa-solid fa-check"></i></span> Sin inversión</li>
                <li><span><i class="fa-solid fa-check"></i></span> Tu propio panel, enlace y comisiones</li>
                <li><span><i class="fa-solid fa-check"></i></span> Curso gratis con certificado</li>
                <li><span><i class="fa-solid fa-check"></i></span> Puedes crecer a líder</li>
            </ul>
        </div>
        <small>Desarrollado por Jhoset Mejía - DiCreWeb</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card rise" style="width:min(480px,100%)">
            @if ($enviado)
                <div class="listo">
                    <div class="ico">🎉</div>
                    <h1 class="serif" style="font-size:2rem">¡Listo, {{ strtok($enviado, ' ') }}!</h1>
                    <p class="sub">Tu solicitud le llegó a <b>{{ $invita->nombre }}</b>. Apenas te apruebe podrás entrar con tu correo y clave.</p>
                    <a href="{{ route('login') }}" class="btn btn-amber btn-block">Ir a ingresar</a>
                </div>
            @else
            <h1 class="serif" style="font-size:2.2rem">Únete como asesor</h1>
            <p class="sub">Te invita <b>{{ $invita->nombre }}</b> ({{ $invita->cargo_nombre }}). Llena tus datos, haz un curso corto de 10 minutos y recibe tu <b>certificado de Asesor Comercial LocalPro</b>.</p>

            @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif

            <form method="post" novalidate>
                @csrf
                <div class="field"><label>Nombre completo</label><input class="in" name="name" value="{{ old('name') }}" maxlength="80" required autocomplete="name"></div>
                <div class="dos">
                    <div class="field"><label>Cédula</label><input class="in" name="documento" value="{{ old('documento') }}" maxlength="20" inputmode="numeric" required></div>
                    <div class="field"><label>WhatsApp</label><input class="in" name="telefono" value="{{ old('telefono') }}" maxlength="20" inputmode="tel" required></div>
                </div>
                @include('layouts.partials.ubicacion', ['pais' => $invita->pais ?: 'CO', 'departamento' => $invita->departamento, 'ciudad' => $invita->ciudad, 'clase' => 'in', 'envoltura' => 'field', 'etiqueta' => ''])
                <div class="field"><label>Zona / comuna donde trabajarás <span style="font-weight:500;opacity:.7">(opcional)</span></label><input class="in" name="zona" value="{{ old('zona', $invita->zona) }}" maxlength="60" placeholder="Ej: Comuna 10"></div>
                @include('layouts.partials.pago', ['banco' => '', 'pais' => $invita->pais ?: 'CO', 'modo' => 'in', 'titulo' => 'Dónde te pagamos'])
                <div class="field"><label>Correo (para entrar)</label><input class="in" type="email" name="email" value="{{ old('email') }}" maxlength="120" required autocomplete="email"></div>
                <div class="dos">
                    <div class="field"><label>Clave</label><input class="in" type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8"></div>
                    <div class="field"><label>Repite la clave</label><input class="in" type="password" name="password_confirmation" required autocomplete="new-password"></div>
                </div>
                <button class="btn btn-amber btn-block" type="submit" style="margin-top:.4rem">Inscribirme y empezar el curso <i class="fa-solid fa-arrow-right"></i></button>
            </form>
            @endif
        </div>
    </main>
</div>
@include('layouts.partials.lp-ui')
</body>
</html>
