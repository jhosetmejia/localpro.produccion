{{-- Respaldo por si el 419 llega aquí: en vez de "La página expiró", vuelve sola al formulario con un token nuevo --}}
@php
    try { $volver = url()->previous(); } catch (\Throwable $e) { $volver = '/login'; }
    if (!$volver || rtrim($volver, '/') === rtrim(url()->current(), '/')) { $volver = url('/login'); }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex" />
<meta http-equiv="refresh" content="1;url={{ $volver }}" />
<title>Un momento · LocalProo</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4fcf6;color:#151a17}p{font-weight:600}</style>
</head>
<body>
<p>Actualizando la página, un momento…</p>
<script>location.replace(@json($volver));</script>
</body>
</html>
