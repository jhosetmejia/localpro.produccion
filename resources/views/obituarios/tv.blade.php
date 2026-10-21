<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pantalla de sala · LocalProo</title>
    <style>
        html,body{margin:0;height:100%;background:radial-gradient(circle at 50% 35%,#1d2a22,#07100b 70%);color:#fff;font-family:system-ui,Segoe UI,Roboto,sans-serif}
        main{min-height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2.4vh;padding:3vh 16px;box-sizing:border-box;text-align:center}
        h1{margin:0;font-size:clamp(22px,4.4vh,44px)}
        p{margin:0;opacity:.8;font-size:clamp(14px,2.4vh,22px)}
        .err{color:#ffb4a8;font-weight:600}
        #codigo{width:min(80vw,460px);font-size:clamp(34px,8vh,80px);letter-spacing:.3em;text-align:center;padding:1vh 0;border-radius:2vh;border:3px solid #3ac46a;background:#0d1a12;color:#fff;outline:none}
        .teclas{display:grid;grid-template-columns:repeat(3,1fr);gap:1.4vh;width:min(80vw,460px)}
        .teclas button{font-size:clamp(24px,5vh,48px);padding:1.4vh 0;border-radius:1.6vh;border:0;background:#1f3a29;color:#fff;cursor:pointer}
        .teclas button:focus,.teclas button:hover{background:#3ac46a;color:#06210f;outline:4px solid #fff}
        .teclas .ok{background:#22a050}
        .marca{opacity:.5;font-size:14px}
    </style>
</head>
<body>
<main>
    <h1>Escriba el código de la sala</h1>
    <p>Lo encuentra en LocalProo → Obituarios → Salas</p>
    @if ($error)<p class="err">Ese código no existe. Revíselo e intente otra vez.</p>@endif
    <form method="get" action="{{ route('obituarios.tv') }}" id="f">
        <input id="codigo" name="c" inputmode="numeric" pattern="[0-9]*" maxlength="8" autocomplete="off" autofocus>
    </form>
    <div class="teclas">
        @foreach ([1,2,3,4,5,6,7,8,9] as $n)<button type="button" data-n="{{ $n }}">{{ $n }}</button>@endforeach
        <button type="button" data-borrar>⌫</button>
        <button type="button" data-n="0">0</button>
        <button type="button" class="ok" data-ok>OK</button>
    </div>
    <p class="marca">LocalProo · Pantallas de sala</p>
</main>
<script>
(function () {
    var i = document.getElementById('codigo'), f = document.getElementById('f');
    document.querySelectorAll('.teclas button').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.dataset.n !== undefined) i.value = (i.value + b.dataset.n).slice(0, 8);
            else if (b.hasAttribute('data-borrar')) i.value = i.value.slice(0, -1);
            else if (i.value.length >= 4) f.submit();
        });
    });
    f.addEventListener('submit', function (e) { if (i.value.replace(/\D/g, '').length < 4) e.preventDefault(); });
})();
</script>
</body>
</html>
