<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="description" content="Cree su cuenta en LocalPRO" />
    <title>Crear cuenta — LocalPRO</title>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPRO</a>
        <div>
            <h2 class="serif">Ordene su negocio <em>desde hoy</em>.</h2>
            <p>Cree su cuenta y nuestro equipo la activa con los módulos que necesita.</p>
            <ul class="auth-list">
                <li><span><i class="fa-solid fa-check"></i></span> Registro en menos de un minuto</li>
                <li><span><i class="fa-solid fa-check"></i></span> Sin instalar programas</li>
                <li><span><i class="fa-solid fa-check"></i></span> Acompañamiento para empezar</li>
            </ul>
        </div>
        <small style="color:#8fa6a3">Desarrollado por Jhoset Mejía - DiCreWeb</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card rise">
            <a href="{{ url('/') }}" class="back"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
            <h1 class="serif">Crear cuenta</h1>
            <p class="sub">Complete sus datos. Le avisaremos cuando su acceso esté activo.</p>

            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('registro') }}" method="post" novalidate>
                @csrf
                <div class="field">
                    <label for="name">Nombre completo</label>
                    <div class="input">
                        <i class="fa-regular fa-user"></i>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Su nombre" autocomplete="name" required />
                    </div>
                </div>
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="input">
                        <i class="fa-regular fa-envelope"></i>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="usted@negocio.com" autocomplete="email" required />
                    </div>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required />
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="var p=document.getElementById('password');p.type=p.type==='password'?'text':'password'"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repita la contraseña" autocomplete="new-password" required />
                    </div>
                </div>
                <button class="btn btn-amber btn-block" type="submit" style="margin-top:.6rem">Crear cuenta <i class="fa-solid fa-arrow-right"></i></button>
            </form>

            <p class="alt">¿Ya tiene cuenta? <a href="{{ route('login') }}">Ingresar</a></p>
        </div>
    </main>
</div>
</body>
</html>
