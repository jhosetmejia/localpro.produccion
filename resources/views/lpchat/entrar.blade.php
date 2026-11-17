<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>LP Chat · Entrar</title>
<style>
body{display:flex;flex-direction:column;min-height:100dvh}
.top{background:var(--verde2);height:190px;position:absolute;inset:0 0 auto 0;z-index:0}
@media (prefers-color-scheme: dark){.top{background:#202c33}}
.caja{position:relative;z-index:1;max-width:440px;width:calc(100% - 28px);margin:48px auto 24px;background:var(--panel);border-radius:14px;box-shadow:0 6px 24px rgba(0,0,0,.12);padding:28px 24px}
.marca{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.marca img{width:52px;height:52px;border-radius:14px}
.marca h1{margin:0;font-size:1.45rem}
.marca p{margin:2px 0 0;color:var(--txt2);font-size:.9rem}
.invita{display:flex;gap:10px;align-items:center;background:var(--chip);color:var(--txt);border-radius:10px;padding:10px 12px;margin-bottom:16px;font-size:.92rem}
.invita img,.invita .av{width:40px;height:40px;border-radius:50%;object-fit:cover;background:#cfd8dc;display:grid;place-items:center;font-weight:700;color:#fff;flex:none}
.tabs{display:flex;border-bottom:1px solid var(--borde);margin-bottom:16px}
.tabs button{flex:1;background:none;border:0;padding:10px;font-weight:600;color:var(--txt2);border-bottom:3px solid transparent}
.tabs button.on{color:var(--verde2);border-color:var(--verde)}
label{display:block;font-size:.85rem;color:var(--txt2);margin:12px 0 4px}
input[type=text],input[type=password]{width:100%;padding:12px 14px;border:1px solid var(--borde);border-radius:10px;background:var(--panel2);outline:none}
input:focus{border-color:var(--verde)}
.chk{display:flex;gap:8px;align-items:flex-start;margin-top:14px;font-size:.88rem;color:var(--txt2)}
.btn{width:100%;margin-top:18px;background:var(--verde);color:#fff;border:0;border-radius:24px;padding:13px;font-weight:700;font-size:1rem}
.err{background:#fde8e8;color:#b42318;border-radius:8px;padding:10px 12px;font-size:.9rem;margin-bottom:10px}
.pie{text-align:center;color:var(--txt2);font-size:.82rem;margin:0 0 24px}
.ayuda{font-size:.8rem;color:var(--txt2);margin-top:4px}
</style>
</head>
<body>
<div class="top"></div>
<div class="caja">
    <div class="marca">
        <img src="/icons/lpchat-192.png" alt="">
        <div><h1>LP Chat</h1><p>Chatea, crea grupos y conoce gente</p></div>
    </div>
    @isset($invita)
        <div class="invita">
            @if ($invita->foto_url)<img src="{{ $invita->foto_url }}" alt="">@else<div class="av" style="background:#00a884">{{ mb_strtoupper(mb_substr($invita->nombre, 0, 1)) }}</div>@endif
            <div><b>{{ $invita->nombre }}</b> te invitó a chatear. Entra o crea tu cuenta y empiezan a hablar.</div>
        </div>
    @endisset
    @isset($grupo)
        <div class="invita"><div class="av" style="background:#00a884">👥</div><div>Te invitaron al grupo <b>{{ $grupo->nombre }}</b>. Entra o crea tu cuenta para unirte.</div></div>
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
