@php
    // Datos SEO: del artículo ($a), de la página por tipo de cliente ($r) o de las secciones de la vista
    $roles = config('blog_roles', []);
    $esArticulo = isset($a, $slug);
    $rolKey = $esArticulo ? ($a['rol'] ?? 'negocios') : ($rolKey ?? null);
    $rol = $rolKey ? ($roles[$rolKey] ?? null) : null;
    $tituloSeo = $esArticulo ? ($a['seo_titulo'] ?? $a['titulo'] . ' · Blog LocalProo') : ($r['seo_titulo'] ?? (str_contains($__env->yieldContent('titulo'), 'LocalProo') ? trim($__env->yieldContent('titulo')) : trim($__env->yieldContent('titulo')) . ' · Blog LocalProo'));
    $desc = $esArticulo ? ($a['descripcion'] ?? $a['resumen']) : ($r['descripcion'] ?? trim($__env->yieldContent('descripcion')));
    $imagen = asset($rol['imagen'] ?? 'img/og/blog.png');
    $kw = $esArticulo ? ($a['keywords'] ?? []) : [];
    $desde = 'blog:' . ($esArticulo ? $slug : ($rolKey ? 'para-' . $rolKey : 'inicio'));
    $urlCta = fn ($tipo) => route('registro', array_filter(['tipo' => $tipo, 'desde' => $desde]));
    $migas = [['LocalProo', url('/')], ['Blog', route('blog.index')]];
    if ($rol) $migas[] = ['Para ' . mb_strtolower($rol['nombre']), route('blog.rol', $rolKey)];
    if ($esArticulo) $migas[] = [$a['titulo'], url()->current()];
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
@include('layouts.partials.landing-head')
<title>{{ $tituloSeo }}</title>
<meta name="description" content="{{ $desc }}">
@if ($kw)<meta name="keywords" content="{{ implode(', ', $kw) }}">@endif
<link rel="canonical" href="{{ url()->current() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta property="og:type" content="{{ $esArticulo ? 'article' : 'website' }}">
<meta property="og:locale" content="es_CO">
<meta property="og:site_name" content="LocalProo">
<meta property="og:title" content="{{ $esArticulo ? $a['titulo'] : $tituloSeo }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $imagen }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $esArticulo ? $a['titulo'] : $tituloSeo }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $imagen }}">
@if ($esArticulo)
<meta property="article:published_time" content="{{ $a['publicado'] }}">
<meta property="article:modified_time" content="{{ $a['actualizado'] ?? $a['publicado'] }}">
@endif
@php
    $ld = [];
    $ld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($m, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $m[0], 'item' => $m[1]], $migas, array_keys($migas))];
    if ($esArticulo) {
        $ld[] = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $a['titulo'], 'description' => $desc,
            'image' => $imagen, 'datePublished' => $a['publicado'], 'dateModified' => $a['actualizado'] ?? $a['publicado'],
            'inLanguage' => 'es-CO', 'mainEntityOfPage' => url()->current(), 'keywords' => implode(', ', $kw),
            'author' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/')],
            'publisher' => ['@type' => 'Organization', 'name' => 'LocalProo', 'url' => url('/'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('favicon-32.png')]]];
        if (!empty($a['faq'])) {
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $a['faq'])];
        }
    }
    if (isset($r)) {
        $ld[] = ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'LocalProo', 'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web, Android, iOS', 'description' => $desc, 'url' => url()->current(), 'inLanguage' => 'es-CO',
            'offers' => ['@type' => 'Offer', 'priceCurrency' => 'COP', 'price' => '0', 'description' => 'Prueba gratis']];
    }
@endphp
@foreach ($ld as $j)<script type="application/ld+json">{!! json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
<style>
.bl{max-width:780px;margin:0 auto;padding:34px 20px 60px;line-height:1.7;color:#151a17;font-size:1.05rem}
.bl a.v{display:inline-flex;gap:.4rem;font-weight:800;color:#11702f;text-decoration:none;margin-bottom:18px}
.bl h1{font-size:clamp(1.7rem,4.5vw,2.4rem);font-weight:900;line-height:1.2;margin-bottom:.4rem}
.bl .f{color:#6b7570;font-weight:600;margin-bottom:26px;font-size:.95rem}
.bl h2{font-size:1.3rem;font-weight:900;margin:32px 0 10px}
.bl ul,.bl ol{padding-left:1.25rem}
.bl li{margin-bottom:.35rem}
.bl .box{background:#f4fcf6;border:1.5px solid #cfe9d6;border-radius:18px;padding:16px 18px;margin:18px 0}
.bl .ojo{background:#fff8e6;border:1.5px solid #f3dca0;border-radius:18px;padding:16px 18px;margin:18px 0}
.bl .tabla{width:100%;border-collapse:collapse;margin:16px 0;font-size:.97rem}
.bl .tabla th,.bl .tabla td{border:1px solid #dfe6e1;padding:10px;vertical-align:top;text-align:left}
.bl .tabla th{background:#f4fcf6}
.bl .tw{overflow-x:auto}
.bl .cta{display:block;text-align:center;background:#11702f;color:#fff;font-weight:800;border-radius:14px;padding:14px;text-decoration:none;margin:30px 0 10px}
.bl .card{display:block;border:1.5px solid #dfe6e1;border-radius:18px;padding:18px;margin:14px 0;text-decoration:none;color:inherit}
.bl .card:hover{border-color:#11702f}
.bl .card b{font-size:1.15rem;display:block;margin-bottom:4px}
.bl .migas{font-size:.85rem;color:#6b7570;margin:0 0 14px}
.bl .migas a{color:#6b7570;text-decoration:none}
.bl .migas a:hover{color:#11702f}
.bl .rolcta{background:#0b0d0c;color:#fff;border-radius:22px;padding:22px;margin:36px 0 10px}
.bl .rolcta h2{color:#a6f34a;margin:0 0 8px}
.bl .rolcta ul{margin:10px 0 16px}
.bl .rolcta a.btn-cta{display:block;text-align:center;background:#2fd15a;color:#0b0d0c;font-weight:900;border-radius:14px;padding:14px;text-decoration:none}
.bl .rolcta small{display:block;text-align:center;color:#c9d6cd;margin-top:8px}
.bl details.faq{border:1.5px solid #dfe6e1;border-radius:14px;padding:12px 16px;margin:10px 0}
.bl details.faq summary{font-weight:800;cursor:pointer}
.bl .chips a{display:inline-block;border:1.5px solid #cfe9d6;border-radius:30px;padding:4px 12px;margin:0 6px 6px 0;text-decoration:none;color:#11702f;font-weight:700;font-size:.92rem}
</style>
</head>
<body style="background:#fff">
<div class="bl">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px">
        <a class="v" style="margin:0" href="{{ url('/') }}">← LocalProo</a>
        <a class="v" style="margin:0" href="{{ route('blog.index') }}">Blog</a>
    </div>
    @if (count($migas) > 2)
    <nav class="migas" aria-label="Ruta">@foreach ($migas as $i => $m)@if ($i < count($migas) - 1)<a href="{{ $m[1] }}">{{ $m[0] }}</a> › @else<span>{{ \Illuminate\Support\Str::limit($m[0], 60) }}</span>@endif @endforeach</nav>
    @endif
    @yield('volver')
    @yield('cuerpo')

    @if ($esArticulo && !empty($a['faq']))
    <h2>Preguntas frecuentes</h2>
    @foreach ($a['faq'] as $f)
    <details class="faq"><summary>{{ $f[0] }}</summary><p style="margin:8px 0 0">{{ $f[1] }}</p></details>
    @endforeach
    @endif

    @if ($rol && $esArticulo)
    <div class="rolcta">
        <h2><i class="fa-solid {{ $rol['icono'] }} me-1"></i> {{ $rol['h1'] }}</h2>
        <div>{{ $rol['intro'] }}</div>
        <ul>@foreach (array_slice($rol['beneficios'], 0, 4) as $b)<li>{{ $b }}</li>@endforeach</ul>
        <a class="btn-cta" href="{{ $urlCta($rol['tipo']) }}">{{ $rol['cta'] }}</a>
        <small>{{ $rol['nota'] }}</small>
    </div>
    @endif

    @if ($esArticulo)
    @php
        $relacionados = collect(config('blog'))->except($slug)->filter(fn ($x) => ($x['rol'] ?? 'negocios') === $rolKey)->take(3);
        if ($relacionados->count() < 2) $relacionados = $relacionados->merge(collect(config('blog'))->except($slug)->take(3 - $relacionados->count()));
    @endphp
    @if ($relacionados->isNotEmpty())
    <h2>También le puede servir</h2>
    @foreach ($relacionados as $s2 => $x)
    <a class="card" href="{{ route('blog.show', $s2) }}"><b><i class="fa-solid {{ $x['icono'] }} me-1"></i> {{ $x['titulo'] }}</b><span>{{ $x['resumen'] }}</span></a>
    @endforeach
    @endif
    @endif

    <div class="chips" style="margin-top:28px">
        @foreach ($roles as $k => $x)<a href="{{ route('blog.rol', $k) }}"><i class="fa-solid {{ $x['icono'] }} me-1"></i>Para {{ mb_strtolower($x['nombre']) }}</a>@endforeach
    </div>
    <p style="margin-top:18px;color:#6b7570;font-size:.95rem">LocalProo · Para negocios que piensan en grande · <a href="{{ url('/') }}">localproo.com</a></p>
</div>
</body>
</html>
