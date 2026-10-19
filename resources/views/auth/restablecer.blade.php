<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <title>Nueva contraseña — LocalPro</title>
    <style>
        .codigo-input input{font-size:1.6rem;font-weight:800;letter-spacing:.55em;text-align:center;padding-left:2.6rem}
        .reenviar{background:none;border:0;padding:0;font:inherit;font-weight:700;color:var(--green-2);cursor:pointer}
        .reenviar[disabled]{color:#93a097;cursor:default}
    </style>
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <div><h2 class="serif">Cree su <em>nueva</em> contraseña.</h2><p>Escriba el código que le llegó al correo y una contraseña de mínimo 8 caracteres.</p></div>
        <small>© {{ date('Y') }} LocalProo · Todos los derechos reservados</small>
    </aside>
    <main class="auth-main">
        <div class="auth-card rise">
            <a href="{{ route('password.request') }}" class="back"><i class="fa-solid fa-arrow-left"></i> Cambiar correo</a>
            <h1 class="serif">Nueva contraseña</h1>
            <p class="sub">Le enviamos un código de 6 números a <b>{{ $email }}</b>. Vence en {{ $minutos }} minutos.</p>
            @if (session('ok'))<div class="alert alert-ok">{{ session('ok') }}</div>@endif
            @if ($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="field">
                    <label for="codigo">Código del correo</label>
                    <div class="input codigo-input"><i class="fa-solid fa-key"></i><input id="codigo" name="codigo" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="••••••" value="{{ old('codigo') }}" required autofocus></div>
                </div>
                <div class="field">
                    <label for="password">Contraseña nueva</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" data-ver="password"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repita la contraseña" autocomplete="new-password" required>
                        <button type="button" class="toggle-pass" aria-label="Mostrar contraseña" data-ver="password_confirmation"><i class="fa-regular fa-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-ink btn-block" type="submit" style="margin-top:.6rem">Guardar contraseña <i class="fa-solid fa-arrow-right"></i></button>
            </form>
            <form method="post" action="{{ route('password.email') }}" class="alt" id="form-reenviar">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                ¿No le llegó? Revise spam o <button type="submit" class="reenviar" id="btn-reenviar">reenviar el código</button>
            </form>
        </div>
    </main>
</div>
<script>
    // Ojito: mostrar u ocultar la contraseña
    document.querySelectorAll('[data-ver]').forEach(function (b) {
        b.addEventListener('click', function () {
            var p = document.getElementById(b.dataset.ver), ver = p.type === 'password';
            p.type = ver ? 'text' : 'password';
            b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
            b.innerHTML = ver ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
        });
    });
    // Solo números en el código
    var c = document.getElementById('codigo');
    c.addEventListener('input', function () { c.value = c.value.replace(/\D/g, '').slice(0, 6); });
    // Reenviar: esperar un minuto
    (function () {
        var b = document.getElementById('btn-reenviar'), s = 60, txt = b.textContent;
        b.disabled = true;
        var t = setInterval(function () {
            s--; b.textContent = txt + ' (' + s + ' s)';
            if (s <= 0) { clearInterval(t); b.disabled = false; b.textContent = txt; }
        }, 1000);
        b.textContent = txt + ' (' + s + ' s)';
    })();
</script>
</body>
</html>
