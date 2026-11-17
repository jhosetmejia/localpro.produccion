<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>LP Chat</title>
</head>
<body style="display:grid;place-items:center;min-height:100dvh;padding:20px">
<div style="max-width:420px;text-align:center;background:var(--panel);border-radius:14px;padding:28px 22px;box-shadow:0 6px 24px rgba(0,0,0,.1)">
    <img src="/icons/lpchat-192.png" alt="" style="width:64px;height:64px;border-radius:16px">
    <h1 style="font-size:1.25rem">{{ $titulo }}</h1>
    <p style="color:var(--txt2)">{{ $texto }}</p>
    @if (! empty($volver))<a href="{{ $volver }}" style="display:inline-block;margin-top:8px;background:var(--verde);color:#fff;border-radius:22px;padding:10px 22px;text-decoration:none;font-weight:700">Volver</a>@endif
</div>
</body>
</html>
