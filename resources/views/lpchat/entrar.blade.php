<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>LP Chat · Entrar</title>
<style>
body{display:flex;flex-direction:column;min-height:100dvh}
.top{position:absolute;inset:0 0 auto 0;height:320px;z-index:0;background:radial-gradient(700px 300px at 50% -60px,rgba(166,243,74,.22),transparent 70%)}
.caja{position:relative;z-index:1;max-width:440px;width:calc(100% - 28px);margin:48px auto 24px;background:var(--panel2);border:1px solid var(--borde);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.5);padding:28px 24px}
.marca{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.marca img{width:52px;height:52px;border-radius:14px}
.marca h1{margin:0;font-size:1.6rem;font-family:Montserrat,Figtree,sans-serif;font-weight:900;letter-spacing:-.02em}
.marca h1 b{color:var(--verde)}
.marca p{margin:2px 0 0;color:var(--txt2);font-size:.9rem}
.invita{display:flex;gap:10px;align-items:center;background:var(--chip);color:var(--txt);border-radius:10px;padding:10px 12px;margin-bottom:16px;font-size:.92rem}
.invita img,.invita .av{width:40px;height:40px;border-radius:50%;object-fit:cover;background:#26302a;display:grid;place-items:center;font-weight:700;color:#fff;flex:none}
.tabs{display:flex;gap:6px;background:var(--panel);border:1px solid var(--borde);border-radius:14px;padding:4px;margin-bottom:16px}
.tabs button{flex:1;background:none;border:0;padding:9px;font-weight:700;color:var(--txt2);border-radius:10px}
.tabs button.on{color:#0b0d0c;background:var(--verde)}
label{display:block;font-size:.82rem;font-weight:700;color:var(--txt2);margin:12px 0 4px}
input[type=text],input[type=password]{width:100%;padding:12px 14px;border:1px solid var(--borde);border-radius:12px;background:var(--panel3);outline:none}
input:focus{border-color:var(--verde)}
.chk{display:flex;gap:8px;align-items:flex-start;margin-top:14px;font-size:.88rem;color:var(--txt2)}
.btn{width:100%;margin-top:18px;background:var(--grad);color:#0b0d0c;border:0;border-radius:14px;padding:13px;font-weight:800;font-size:1rem;box-shadow:0 10px 30px rgba(166,243,74,.2)}
.err{background:rgba(255,59,107,.12);color:#ff8aa6;border:1px solid rgba(255,59,107,.35);border-radius:8px;padding:10px 12px;font-size:.9rem;margin-bottom:10px}
.pie{text-align:center;color:var(--txt2);font-size:.82rem;margin:0 0 24px}
.ayuda{font-size:.8rem;color:var(--txt2);margin-top:4px}
</style>
</head>
<body>
<div class="top"></div>
<div class="caja">
    <div class="marca">
        <img src="/icons/lpchat-192.png" alt="">
        <div><h1>LP <b>Chat</b></h1><p>La red social nariñense · de LocalProo</p></div>
    </div>
    @isset($invita)
        <div class="invita">
            @if ($invita->foto_url)<img src="{{ $invita->foto_url }}" alt="">@else<div class="av" style="background:#16b84e">{{ mb_strtoupper(mb_substr($invita->nombre, 0, 1)) }}</div>@endif
            <div><b>{{ $invita->nombre }}</b> te invitó a chatear. Entra o crea tu cuenta y empiezan a hablar.</div>
        </div>
    @endisset
    @isset($grupo)
        <div class="invita"><div class="av" style="background:#16b84e">👥</div><div>Te invitaron al grupo <b>{{ $grupo->nombre }}</b>. Entra o crea tu cuenta para unirte.</div></div>
    @endisset
    @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
    @php $tab = session('tab', old('nombre') ? 'registro' : (isset($invita) || isset($grupo) ? 'registro' : 'entrar')); @endphp
    <div class="tabs">
        <button type="button" data-t="entrar" class="{{ $tab === 'entrar' ? 'on' : '' }}">Entrar</button>
        <button type="button" data-t="registro" class="{{ $tab === 'registro' ? 'on' : '' }}">Crear cuenta</button>
    </div>
    <form method="post" action="{{ route('lpchat.login') }}" id="f-entrar" @if ($tab !== 'entrar') hidden @endif>
        @csrf
        <input type="hidden" name="volver" value="{{ $volver ?? '' }}">
        <p style="margin:0 0 10px;font-size:.85rem;opacity:.8">🔑 Entra con el mismo correo y clave de <b>LocalProo</b>, <b>Pasto Fight Night</b> o <b>Nariño ExpoShow</b>.</p>
        <label>Correo o celular</label>
        <input type="text" name="identificador" value="{{ old('identificador') }}" autocomplete="username" required placeholder="tu@correo.com o 300 123 4567">
        <label>Clave</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button class="btn">Entrar</button>
    </form>
    <form method="post" action="{{ route('lpchat.registro') }}" id="f-registro" @if ($tab !== 'registro') hidden @endif>
        @csrf
        <input type="hidden" name="volver" value="{{ $volver ?? '' }}">
        <label>Tu nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" maxlength="80" required placeholder="Como te verán los demás">
        <label>Correo o celular</label>
        <input type="text" name="identificador" value="{{ old('identificador') }}" autocomplete="username" required placeholder="tu@correo.com o 300 123 4567">
        <div class="ayuda">Con esto te encuentran tus amigos, como en WhatsApp.</div>
        <label>Crea una clave</label>
        <input type="password" name="password" autocomplete="new-password" minlength="6" required>
        <label class="chk"><input type="checkbox" name="mayor" value="1" required> <span>Soy mayor de 18 años y acepto las <a href="{{ url('/terminos') }}" target="_blank">condiciones</a> y la <a href="{{ url('/privacidad') }}" target="_blank">política de privacidad</a>.</span></label>
        <button class="btn">Crear mi cuenta</button>
    </form>
</div>
<p class="pie">LP Chat es de <a href="{{ url('/') }}">LocalProo</a> · Si tienes cuenta de LocalProo, entra allá y vuelve: quedas conectado solo.</p>
<script>
document.querySelectorAll('.tabs button').forEach(function (b) {
    b.onclick = function () {
        document.querySelectorAll('.tabs button').forEach(function (x) { x.classList.toggle('on', x === b); });
        document.getElementById('f-entrar').hidden = b.dataset.t !== 'entrar';
        document.getElementById('f-registro').hidden = b.dataset.t !== 'registro';
    };
});
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(function () {});
</script>
</body>
</html>
