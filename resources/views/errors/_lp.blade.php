{{-- Pantalla de error con el estilo de LocalProo: ventana emergente y regreso automático a un lugar seguro --}}
@php
    try { $logueado = auth()->check(); } catch (\Throwable $e) { $logueado = false; }
    try { $destino = $logueado ? route('panel') : url('/'); } catch (\Throwable $e) { $destino = '/'; }
    $destinoTxt = $logueado ? 'Ir a mi panel' : 'Ir al inicio';
    $msg = isset($exception) ? trim((string) $exception->getMessage()) : '';
    // Los mensajes técnicos o en inglés no se muestran
    if ($msg === '' || preg_match('/^(User |This action|Unauthorized|Forbidden|Not Found|Server Error|Service Unavailable|Too Many|The route|No query results)/i', $msg)) {
        $msg = '';
    }
    $segundos = $segundos ?? 8;
    // Si el error sale en la misma página a la que se iba a llevar, no se redirige (evita un ciclo)
    try { $ciclo = rtrim($destino, '/') === rtrim(url()->current(), '/'); } catch (\Throwable $e) { $ciclo = false; }
    if ($ciclo && $logueado) { $destino = route('logout'); $destinoTxt = 'Cerrar sesión'; }
    $auto = !$ciclo;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex" />
<title>{{ $titulo }} · LocalProo</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet" />
<style>
*{box-sizing:border-box}
html,body{margin:0;height:100%}
body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4fcf6;color:#151a17;overflow:hidden}
.fondo{position:fixed;inset:0;background:radial-gradient(60% 50% at 15% 10%,rgba(166,243,74,.35),transparent 70%),radial-gradient(50% 50% at 90% 90%,rgba(47,209,90,.25),transparent 70%),#f4fcf6}
.marca{position:fixed;top:20px;left:22px;display:flex;align-items:center;gap:.55rem;font-weight:900;font-size:1.15rem;color:#0b0d0c;text-decoration:none;z-index:1}
.marca span{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);color:#0b0d0c}
.codigo{position:fixed;right:-10px;bottom:-40px;font-size:clamp(9rem,30vw,20rem);font-weight:900;color:rgba(22,179,74,.08);letter-spacing:-.04em;line-height:1;user-select:none}
.ov{position:fixed;inset:0;z-index:2;background:rgba(11,13,12,.45);display:flex;align-items:center;justify-content:center;padding:18px;opacity:0;transition:opacity .2s}
.ov.on{opacity:1}
.md{background:#fff;border-radius:26px;width:min(430px,100%);padding:26px 24px 22px;box-shadow:0 40px 90px -30px rgba(0,0,0,.55);transform:translateY(16px) scale(.97);transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.ov.on .md{transform:none}
.ic{width:58px;height:58px;border-radius:19px;display:grid;place-items:center;font-size:1.7rem;margin-bottom:14px;background:#e8f8ec}
.md.peligro .ic{background:#ffe9f3}
.cd{display:inline-block;font-size:.75rem;font-weight:800;letter-spacing:.06em;color:#11702f;background:#e8f8ec;border-radius:99px;padding:.2rem .6rem;margin-bottom:8px}
.md.peligro .cd{color:#9b1c5a;background:#ffe9f3}
.md h1{font-size:1.35rem;font-weight:900;margin:0 0 6px;line-height:1.25}
.md p{color:#5b665e;font-weight:500;margin:0 0 18px;line-height:1.5;font-size:.97rem}
.md .extra{background:#f4fcf6;border-radius:14px;padding:.65rem .8rem;color:#11702f;font-weight:600;font-size:.9rem;margin:-6px 0 18px}
.bt{display:flex;gap:10px}
.bt a,.bt button{flex:1;height:52px;border:0;border-radius:16px;font-weight:800;font-size:1rem;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;text-decoration:none}
.no{background:#f1f5f2;color:#151a17}
.si{background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);color:#0b0d0c}
.cuenta{margin-top:12px;text-align:center;color:#8a948d;font-size:.83rem;font-weight:600}
.barra{height:4px;border-radius:9px;background:#eef3ef;overflow:hidden;margin-top:8px}
.barra i{display:block;height:100%;width:100%;background:#2fd15a;transform-origin:left;animation:baja {{ $segundos }}s linear forwards}
@keyframes baja{to{transform:scaleX(0)}}
</style>
</head>
<body>
<div class="fondo"></div>
<a class="marca" href="{{ $destino }}"><span>L</span>LocalProo</a>
<div class="codigo" aria-hidden="true">{{ $codigo }}</div>

<div class="ov" id="ov">
    <div class="md {{ !empty($peligro) ? 'peligro' : '' }}" role="alertdialog" aria-modal="true" aria-labelledby="t">
        <div class="ic">{{ $icono }}</div>
        <div class="cd">ERROR {{ $codigo }}</div>
        <h1 id="t">{{ $titulo }}</h1>
        <p>{{ $texto }}</p>
        @if ($msg)
            <div class="extra">{{ $msg }}</div>
        @endif
        <div class="bt">
            <button type="button" class="no" onclick="history.length > 1 ? history.back() : location.href='{{ $destino }}'">Volver</button>
            <a class="si" href="{{ $destino }}">{{ $destinoTxt }}</a>
        </div>
        @if ($auto)
        <div class="cuenta">Le llevamos a {{ $logueado ? 'su panel' : 'la página de inicio' }} en <b id="s">{{ $segundos }}</b> s</div>
        <div class="barra"><i></i></div>
        @endif
    </div>
</div>
<script>
(function () {
    var ov = document.getElementById('ov'), s = document.getElementById('s'), n = {{ (int) $segundos }};
    requestAnimationFrame(function () { ov.classList.add('on'); });
    if (!s) return;
    var t = setInterval(function () {
        n--; if (s) s.textContent = Math.max(n, 0);
        if (n <= 0) { clearInterval(t); location.replace(@json($destino)); }
    }, 1000);
})();
</script>
</body>
</html>
