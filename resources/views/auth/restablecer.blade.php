<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <title>Nueva contraseña — LocalPro</title>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div><h2 class="serif">Cree su <em>nueva</em> contraseña.</h2><p>Use una que no haya usado antes, con mínimo 8 caracteres.</p></div>
        <small>© {{ date('Y') }} LocalProo · Todos los derechos reservados</small>
    </aside>
    <main class="auth-main">
        <div class="auth-card rise">
            <h1 class="serif">Nueva contraseña</h1>
            <p class="sub">Escriba su correo y su contraseña nueva.</p>
            @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field"><label for="email">Correo electrónico</label><div class="input"><i class="fa-regular fa-envelope"></i><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required></div></div>
                <div class="field"><label for="password">Contraseña nueva</label><div class="input"><i class="fa-solid fa-lock"></i><input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required autofocus></div></div>
                <div class="field"><label for="password_confirmation">Confirmar contraseña</label><div class="input"><i class="fa-solid fa-lock"></i><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div></div>
                <button class="btn btn-ink btn-block" type="submit" style="margin-top:.6rem">Guardar y entrar <i class="fa-solid fa-arrow-right"></i></button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
