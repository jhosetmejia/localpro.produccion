@php
    $grupos = config('funciones.grupos', []);
    $faq = config('funciones.faq', []);
    $blog = config('blog', []);
    $total = collect($grupos)->sum(fn ($g) => count($g['items']));
    $titulo = 'Funciones de LocalProo: caja POS, inventario, tienda en línea y más';
    $desc = 'Conozca todas las funciones de LocalProo: caja POS, inventario con código de barras, tienda en línea en Google, WhatsApp, reportes, factura electrónica DIAN, nómina y herramientas para restaurantes, consultorios, alquileres, institutos y más.';
    $imagen = asset('img/og/negocios.png');
    $ico = fn ($i) => ($i === 'fa-whatsapp' ? 'fa-brands ' : 'fa-solid ') . $i;
    $ld = [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'LocalProo', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Funciones', 'item' => route('funciones')],
        ]],
        ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'LocalProo', 'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web, Android, iOS', 'description' => $desc, 'url' => url('/'), 'inLanguage' => 'es-CO', 'image' => $imagen,
            'featureList' => collect($grupos)->flatMap(fn ($g) => array_column($g['items'], 1))->values()->all(),
            'offers' => ['@type' => 'Offer', 'priceCurrency' => 'COP', 'price' => '0', 'description' => 'Prueba gratis']],
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)],
    ];
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    @include('layouts.partials.landing-head')
    <title>{{ $titulo }}</title>
    <meta name="description" content="{{ $desc }}" />
    <meta name="keywords" content="software para negocios, sistema POS, programa de inventario, punto de venta en el celular, tienda en línea para negocios, factura electrónica DIAN, software para restaurantes, software para tiendas de ropa, LocalProo" />
    <link rel="canonical" href="{{ route('funciones') }}" />
    <meta name="robots" content="index,follow,max-image-preview:large" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="es_CO" />
    <meta property="og:site_name" content="LocalProo" />
    <meta property="og:title" content="{{ $titulo }}" />
    <meta property="og:description" content="{{ $desc }}" />
    <meta property="og:url" content="{{ route('funciones') }}" />
    <meta property="og:image" content="{{ $imagen }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $titulo }}" />
    <meta name="twitter:description" content="{{ $desc }}" />
    <meta name="twitter:image" content="{{ $imagen }}" />
    @foreach ($ld as $j)<script type="application/ld+json">{!! json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach
    <style>
        .fn-hero{padding:56px 0 18px}
        .fn-hero h1{font-size:clamp(2rem,5.5vw,3.3rem);line-height:1.1;margin:.5rem 0 .8rem}
        .fn-hero p{max-width:720px;font-size:1.08rem;line-height:1.65;color:#4b5550}
        .fn-migas{font-size:.88rem;color:#6b7570;margin-bottom:10px}
        .fn-migas a{color:#6b7570;text-decoration:none}
        .fn-chips{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-bottom:1px solid #e6ece8;padding:10px 0}
        .fn-chips .wrap{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none}
        .fn-chips .wrap::-webkit-scrollbar{display:none}
        .fn-chips a{flex:none;display:inline-flex;align-items:center;gap:6px;border:1.5px solid #dfe6e1;border-radius:30px;padding:7px 14px;font-weight:700;font-size:.9rem;color:#151a17;text-decoration:none;background:#fff}
        .fn-chips a:hover{border-color:#11702f;color:#11702f}
        .fn-grupo{padding:46px 0 10px;scroll-margin-top:64px}
        .fn-grupo h2{font-size:clamp(1.5rem,3.6vw,2.1rem);margin:.3rem 0 .4rem}
        .fn-grupo > .wrap > p{color:#5b6560;margin-bottom:20px;max-width:680px}
        .fn-lista{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
        .fn-item{border:1.5px solid #e2e9e4;border-radius:20px;padding:20px;background:#fff;display:flex;flex-direction:column}
        .fn-item .ic{width:44px;height:44px;border-radius:14px;background:#eafaf0;color:#11702f;display:grid;place-items:center;font-size:1.15rem;margin-bottom:12px}
        .fn-item h3{font-size:1.1rem;margin:0 0 6px;line-height:1.3}
        .fn-item p{margin:0;color:#4b5550;line-height:1.6;font-size:.97rem}
        .fn-item ul{margin:10px 0 0;padding-left:1.1rem;color:#4b5550;font-size:.94rem;line-height:1.55}
        .fn-item li{margin-bottom:3px}
        .fn-item .guia{margin-top:auto;padding-top:12px;font-weight:800;color:#11702f;text-decoration:none;font-size:.93rem}
        .fn-item .guia:hover{text-decoration:underline}
        .fn-faq{max-width:820px}
        .fn-faq details{border:1.5px solid #e2e9e4;border-radius:16px;padding:14px 18px;margin:10px 0;background:#fff}
        .fn-faq summary{font-weight:800;cursor:pointer}
        .fn-faq p{margin:8px 0 0;color:#4b5550;line-height:1.6}
        @media (max-width:560px){.fn-lista{grid-template-columns:1fr}.fn-hero{padding-top:34px}}
    </style>
</head>
<body>

<header class="nav">
    <div class="wrap">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <nav class="nav-links">
            <a class="link" href="{{ route('funciones') }}">Funciones</a>
            <a class="link" href="{{ route('marketplace') }}">Marketplace</a>
            <a class="link link-blog" href="{{ route('blog.index') }}">Blog</a>
            <div class="nav-cta">
                <a class="btn btn-ghost" href="{{ route('login') }}">Ingresar</a>
                <a class="btn btn-ink" href="{{ route('registro', ['desde' => 'funciones']) }}">Crear cuenta</a>
            </div>
        </nav>
    </div>
</header>

<main>
    <section class="fn-hero">
        <div class="wrap">
            <nav class="fn-migas" aria-label="Ruta"><a href="{{ url('/') }}">LocalProo</a> › <span>Funciones</span></nav>
            <span class="eyebrow"><i></i> {{ $total }} herramientas en un solo sistema</span>
            <h1 class="serif">Todas las funciones de <span class="grad-text">LocalProo</span></h1>
            <p>Caja POS, inventario, tienda en línea en Google, clientes por WhatsApp, reportes, factura electrónica y herramientas especiales para cada tipo de negocio. Revíselas con calma: cada una tiene su explicación y, en muchas, una guía paso a paso.</p>
            <div class="hero-actions" style="margin-top:18px">
                <a class="btn btn-amber" href="{{ route('registro', ['desde' => 'funciones']) }}"><i class="fa-solid fa-gift"></i> Probar gratis</a>
                <a class="btn btn-ghost" href="{{ url('/') }}"><i class="fa-solid fa-house"></i> Volver al inicio</a>
            </div>
        </div>
    </section>

    <nav class="fn-chips" aria-label="Secciones">
        <div class="wrap">
            @foreach ($grupos as $id => $g)
            <a href="#{{ $id }}"><i class="fa-solid {{ $g['icono'] }}"></i> {{ $g['titulo'] }}</a>
            @endforeach
            <a href="#preguntas"><i class="fa-solid fa-circle-question"></i> Preguntas</a>
        </div>
    </nav>

    @foreach ($grupos as $id => $g)
    <section class="fn-grupo" id="{{ $id }}">
        <div class="wrap">
            <span class="eyebrow"><i></i> {{ count($g['items']) }} funciones</span>
            <h2 class="serif">{{ $g['titulo'] }}</h2>
            <p>{{ $g['intro'] }}</p>
            <div class="fn-lista">
                @foreach ($g['items'] as $f)
                @php [$icono, $nombre, $texto, $detalles, $guia] = $f; @endphp
                <article class="fn-item">
                    <div class="ic"><i class="{{ $ico($icono) }}"></i></div>
                    <h3>{{ $nombre }}</h3>
                    <p>{{ $texto }}</p>
                    @if ($detalles)
                    <ul>@foreach ($detalles as $d)<li>{{ $d }}</li>@endforeach</ul>
                    @endif
                    @if ($guia && isset($blog[$guia]))
                    <a class="guia" href="{{ route('blog.show', $guia) }}">Leer la guía completa <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </article>
                @endforeach
            </div>
        </div>
    </section>
    @endforeach

    <section class="fn-grupo" id="preguntas">
        <div class="wrap">
            <span class="eyebrow">Preguntas frecuentes</span>
            <h2 class="serif">Lo que más nos preguntan</h2>
            <div class="fn-faq">
                @foreach ($faq as $f)
                <details><summary>{{ $f[0] }}</summary><p>{{ $f[1] }}</p></details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="wrap"><div class="box">
            <h2 class="serif">Pruebe <span class="grad-text">todas las funciones</span> gratis.</h2>
            <p>Cree su cuenta en un minuto y empiece a vender hoy.</p>
            <div class="hero-actions">
                <a class="btn btn-amber" href="{{ route('registro', ['desde' => 'funciones']) }}">Crear cuenta</a>
                <a class="btn btn-ink" href="{{ route('login') }}">Ingresar</a>
            </div>
        </div></div>
    </section>
</main>

<footer class="footer">
    <div class="wrap">
        <span>© {{ date('Y') }} LocalProo · Todos los derechos reservados</span>
        <span style="display:flex;gap:14px;flex-wrap:wrap"><a href="{{ route('funciones') }}">Funciones</a><a href="{{ route('blog.index') }}">Blog</a><a href="{{ route('legal.privacidad') }}">Privacidad</a><a href="{{ route('legal.terminos') }}">Condiciones</a><a href="{{ route('legal.eliminar') }}">Eliminar mis datos</a></span>
    </div>
</footer>

</body>
</html>
