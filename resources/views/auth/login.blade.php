<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <meta name="description" content="Ingrese a LocalPRO" />
    <title>Ingresar — LocalPRO</title>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div>
            <h2 class="serif">Bienvenido de nuevo a <em>su negocio</em>.</h2>
            <p>Retome donde lo dejó: ventas, compras e inventario siempre al día.</p>
            <ul class="auth-list">
                <li><span><i class="fa-solid fa-check"></i></span> Ventas y comprobantes al instante</li>
                <li><span><i class="fa-solid fa-check"></i></span> Inventario que se actualiza solo</li>
                <li><span><i class="fa-solid fa-check"></i></span> Acceso desde cualquier dispositivo</li>
            </ul>
        </div>
        <small>Desarrollado por Jhoset Mejía - DiCreWeb</small>
    </aside>

    <main class="auth-main">
        <div class="auth-card rise">
            <a href="{{ url('/') }}" class="back"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
            <h1 class="serif">Ingresar</h1>
            <p class="sub">Escriba su correo y contraseña para entrar al sistema.</p>

            @if (session('registro_ok'))
                <div class="alert alert-ok">{{ session('registro_ok') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ url('/login') }}" method="post" novalidate>
                @csrf
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="input">
                        <i class="fa-regular fa-envelope"></i>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="usted@negocio.com" autocomplete="email" autofocus required />
                    </div>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required />
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" onclick="var p=document.getElementById('password');p.type=p.type==='password'?'text':'password'"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div style="text-align:right;margin:-.3rem 0 .4rem"><a href="{{ route('password.request') }}" style="font-size:.88rem;font-weight:700;color:var(--green-2)">¿Olvidó su contraseña?</a></div>
                <button class="btn btn-ink btn-block" type="submit" style="margin-top:.6rem">Iniciar sesión <i class="fa-solid fa-arrow-right"></i></button>
            </form>

            <p class="alt">¿No tiene cuenta? <a href="{{ route('registro') }}">Crear cuenta</a></p>
        </div>
    </main>
</div>
</body>
</html>
