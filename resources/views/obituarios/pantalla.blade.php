<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $titulo }} · {{ $tienda->nombre }}</title>
    <style>
        html,body{margin:0;height:100%;background:#000;overflow:hidden}
        .slide{position:fixed;inset:0;opacity:0;transition:opacity 1.2s ease;pointer-events:none}
        .slide.on{opacity:1}
        .slide [data-obi-marco]{position:absolute;inset:0;width:100%;height:100%}
        .espera{position:fixed;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2vh;color:#f6eedb;text-align:center;
            background:radial-gradient(circle at 50% 40%,#2a241b,#0a0907 70%);font-family:'Cormorant Garamond',Georgia,serif}
        .espera img{max-width:34vw;max-height:30vh;object-fit:contain;background:rgba(255,255,255,.92);padding:2vh 3vw;border-radius:2vh}
        .espera h1{margin:0;font-size:7vh;color:#d4af37;font-weight:700}
        .espera p{margin:0;font-size:3.4vh;opacity:.8}
        .aviso{position:fixed;left:50%;bottom:2vh;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;font:600 14px system-ui;padding:8px 14px;border-radius:99px;transition:opacity .6s}
        body.sin-cursor .aviso{opacity:0}
    </style>
</head>
<body>
@forelse ($obituarios as $o)
    <div class="slide" data-obi-slide>
        <div data-obi-marco="pantalla">@include('obituarios._lienzo', ['p' => $o->plantilla ?? $plantillaDefecto, 'o' => $o, 'tienda' => $tienda])</div>
    </div>
@empty
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap">
    <div class="espera">
        @if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="">@endif
        <h1>{{ $titulo }}</h1>
        <p>{{ $tienda->nombre }}</p>
    </div>
@endforelse
<div class="aviso">Toque la pantalla para verla completa</div>
<script src="{{ asset('js/obituarios.js') }}?v=15"></script>
<script>Obi.pantalla({ rotar: {{ (int) $rotar }}, version: @json($version), actual: @json($huella) });</script>
</body>
</html>
