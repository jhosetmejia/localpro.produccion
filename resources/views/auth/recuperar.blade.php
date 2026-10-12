<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <title>Recuperar contraseña — LocalPro</title>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div>
            <h2 class="serif">Tranquilo, <em>pasa</em>.</h2>
            <p>Le enviamos un enlace a su correo para que cree una contraseña nueva en segundos.</p>
        </div>
        <small>© {{ date('Y') }} LocalProo · Todos los derechos reservados</small>
    </aside>
    <main class="auth-main">
        <div class="auth-card rise">
            <a href="{{ route('login') }}" class="back"><i class="fa-solid fa-arrow-left"></i> Volver a ingresar</a>
            <h1 class="serif">Recuperar contraseña</h1>
            <p class="sub">Escriba el correo con el que entra a LocalPro.</p>
            @if (session('ok'))<div class="alert alert-ok">{{ session('ok') }}</div>@endif
            @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="email">Correo electrónico</label>
                    <div class="input"><i class="fa-regular fa-envelope"></i><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="usted@negocio.com" autocomplete="email" required autofocus></div>
                </div>
                <button class="btn btn-ink btn-block" type="submit" style="margin-top:.6rem">Enviarme el enlace <i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
