<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $tienda->nombre }}</title>
<meta name="robots" content="noindex, follow">
<meta property="og:title" content="{{ $tienda->nombre }}">
@if ($foto)<meta property="og:image" content="{{ $foto }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px 16px;font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:radial-gradient(circle at 20% 0%,#dcfce7,transparent 45%),#f4f8f5;color:#0b0d0c}
.c{width:100%;max-width:420px;background:#fff;border:1.5px solid #e3ebe6;border-radius:28px;padding:28px 22px;text-align:center;box-shadow:0 30px 60px -40px rgba(0,0,0,.35)}
.av{width:92px;height:92px;border-radius:50%;margin:0 auto 12px;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-size:2.2rem;font-weight:800;overflow:hidden;border:4px solid #fff;box-shadow:0 10px 24px -12px rgba(0,0,0,.4)}
.av img{width:100%;height:100%;object-fit:cover}
h1{font-size:1.45rem;margin:0 0 6px;font-weight:800}
p{color:#5b665e;font-weight:500;margin:0 0 18px;line-height:1.5}
a.b{display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;font-weight:800;border-radius:999px;padding:14px 18px;margin-top:10px}
a.wa{background:#25d366;color:#fff}a.wa:hover{background:#1ebe5b}
a.ct{background:#0b0d0c;color:#fff}a.ct:hover{background:#16b34a}
small{display:block;margin-top:18px;color:#9aa59e;font-size:.75rem}
small b{color:#16b34a}
</style>
</head>
<body>
<div class="c">
    <div class="av">@if ($foto)<img src="{{ $foto }}" alt="{{ $tienda->nombre }}">@else{{ mb_strtoupper(mb_substr($tienda->nombre, 0, 1)) }}@endif</div>
    <h1>{{ $tienda->nombre }}</h1>
    <p>Estamos dejando lista esta página con todos nuestros enlaces. ¡Muy pronto! Mientras tanto, escríbanos o mire nuestros productos.</p>
    @if ($wa)<a class="b wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Escríbanos por WhatsApp</a>@endif
    @if ($catalogo)<a class="b ct" href="{{ $catalogo }}"><i class="fa-solid fa-store"></i> Ver nuestra tienda en línea</a>@endif
    <small>Hecho con <b>LocalPro</b></small>
</div>
</body>
</html>
