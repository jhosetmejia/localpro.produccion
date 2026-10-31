@php
    $titulo = $q !== '' ? $q . ' · LocalProo Marketplace' : ($tipo !== '' ? \App\Models\Tienda::TIPOS[$tipo][0] . ($ciudad ? ' en ' . $ciudad : '') . ' · LocalProo Marketplace' : 'LocalProo Marketplace · Todas las tiendas en un solo lugar');
    // Páginas por tipo y ciudad se indexan solas (ej. "Tiendas de ropa en Pasto"); la búsqueda libre no
    if ($q === '' && $tipo === '' && $ciudad !== '') $titulo = 'Tiendas en ' . $ciudad . ' · Compre en línea · LocalProo Marketplace';
    $desc = ($ciudad !== '' ? 'Tiendas y productos en ' . $ciudad . '. ' : '') . 'Encuentre productos de ' . number_format($totalTiendas, 0, ',', '.') . ' tiendas de ' . $nombrePais . ' y compre directo por WhatsApp. Ropa, calzado, tecnología, comida y más.';
    $url = fn (array $cambios) => route('marketplace', array_filter(array_merge(['q' => $q, 'tipo' => $tipo, 'ciudad' => $ciudad, 'orden' => $orden !== 'menor' ? $orden : null], $cambios), fn ($v) => $v !== null && $v !== ''));
    $canon = $q === '' ? $url(['orden' => null, 'q' => null]) : route('marketplace');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $titulo }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ $canon }}">
<meta name="robots" content="{{ $q !== '' || $productos->currentPage() > 1 ? 'noindex, follow' : 'index, follow' }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="LocalProo">
<meta property="og:title" content="{{ $titulo }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canon }}">
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'WebSite', 'name' => 'LocalProo Marketplace', 'url' => route('marketplace'),
        'potentialAction' => ['@type' => 'SearchAction', 'target' => route('marketplace') . '?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
    ['@type' => 'ItemList', 'name' => $titulo, 'itemListElement' => $tiendas->take(50)->values()->map(fn ($t, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $t->url_catalogo, 'name' => $t->nombre])->all()],
]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
<meta name="theme-color" content="#0b0d0c">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
/* El texto alternativo de una foto que no carga nunca se ve gigante */
img{color:transparent;font-size:0}
.foto-rota{display:grid;place-items:center;width:100%;height:100%;font-weight:800}

:root{--g:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);--g2:#16b34a;--k:#0b0d0c;--ink:#151a17;--bg:#f4f8f5;--line:#e3efe5;--mut:#5b665e;--wa:#25d366}
*{box-sizing:border-box;margin:0;padding:0}
html{background:var(--k)}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased;min-height:100vh;display:flex;flex-direction:column}
a{color:inherit;text-decoration:none}
button,select,input{font:inherit;color:inherit}
.wrap{width:min(1220px,100% - 24px);margin:0 auto}
/* Barra superior con buscador (como Mercado Libre) */
.top{position:sticky;top:0;z-index:30;background:var(--k);color:#fff;box-shadow:0 10px 30px -20px rgba(0,0,0,.6)}
.top .wrap{display:flex;align-items:center;gap:14px;height:70px}
.marca{display:flex;align-items:center;gap:9px;font-weight:800;font-size:1.1rem;white-space:nowrap}
.marca i{width:38px;height:38px;border-radius:12px;background:var(--g);color:var(--k);display:grid;place-items:center;font-style:normal;font-weight:900}
.marca small{display:block;font-size:.66rem;color:#a6f34a;font-weight:700;letter-spacing:.08em;text-transform:uppercase;line-height:1}
.busca{flex:1;display:flex;background:#fff;border-radius:14px;overflow:hidden;min-width:0}
.busca input{flex:1;border:0;outline:0;padding:0 14px;height:46px;color:var(--ink);font-weight:600;min-width:0}
.busca button{width:56px;background:var(--g);border:0;cursor:pointer;font-size:1.15rem}
.top .crear{background:rgba(255,255,255,.1);border-radius:12px;padding:.6rem .9rem;font-weight:800;font-size:.85rem;white-space:nowrap}
.top .crear:hover{background:#a6f34a;color:var(--k)}
.paisf select{height:42px;border-radius:12px;border:0;background:rgba(255,255,255,.1);color:#fff;font-weight:800;font-size:.85rem;padding:0 .6rem;max-width:170px}
.paisf select option{color:#151a17}
/* Celular: marca, país y "Venda aquí" en una fila alineada; el buscador debajo a todo el ancho */
.paisf select{appearance:none;-webkit-appearance:none;outline:0;cursor:pointer;padding:0 1.9rem 0 .8rem;background:rgba(255,255,255,.1) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23a6f34a' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right .7rem center}
.paisf select:focus-visible,.top .crear:focus-visible{outline:2px solid #a6f34a;outline-offset:2px}
.top .crear{display:inline-flex;align-items:center;gap:.35rem;height:42px}
@media(max-width:760px){
  .top .wrap{display:grid;grid-template-columns:auto minmax(0,1fr) auto;height:auto;padding:10px 0 12px;gap:10px 8px}
  .marca{grid-column:1;grid-row:1;font-size:1rem;gap:7px}
  .marca small{font-size:.6rem}
  .marca i{width:34px;height:34px;border-radius:11px}
  .paisf{grid-column:2;grid-row:1;justify-self:end;min-width:0;max-width:100%}
  .paisf select{max-width:100%;height:38px;font-size:.78rem;border-radius:11px;padding:0 1.45rem 0 .6rem;background-position:right .5rem center}
  .top .crear{grid-column:3;grid-row:1;justify-self:end;height:38px;padding:0 .65rem;font-size:.78rem;border-radius:11px;background:#a6f34a;color:var(--k)}
  .busca{grid-column:1/-1;grid-row:2;order:0}
}
@media(max-width:400px){.top .crear .ico{display:none}}
/* Portada */
.hero{background:linear-gradient(180deg,var(--k),#121a15);color:#fff;padding:26px 0 30px}
.hero h1{font-size:clamp(1.5rem,3.4vw,2.3rem);font-weight:800;line-height:1.15}
.hero h1 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p{color:#a9b5ad;margin-top:6px;font-weight:600}
.cifras{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
.cifras span{background:rgba(255,255,255,.07);border:1px solid rgba(166,243,74,.2);border-radius:999px;padding:.4rem .9rem;font-weight:700;font-size:.85rem}
.cifras b{color:#a6f34a}
/* Filtros */
.filtros{display:flex;gap:8px;overflow-x:auto;padding:16px 0 4px;scrollbar-width:none}
.filtros::-webkit-scrollbar{display:none}
.chip{flex:none;border:1.5px solid var(--line);background:#fff;border-radius:999px;padding:.5rem .95rem;font-weight:700;font-size:.86rem;white-space:nowrap}
.chip.on{background:var(--k);color:#fff;border-color:var(--k)}
.chip small{color:var(--mut);font-weight:700;margin-left:3px}
.chip.on small{color:#a6f34a}
.barra{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin:12px 0}
.barra h2{font-size:1.25rem;font-weight:800}
.barra .sel{display:flex;gap:8px;flex-wrap:wrap}
.barra select{border:1.5px solid var(--line);background:#fff;border-radius:12px;padding:.5rem .8rem;font-weight:700;font-size:.86rem}
.limpiar{font-weight:800;color:var(--g2);font-size:.86rem}
/* Tiendas */
.tiendas{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(240px,1fr);gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:thin}
.tienda{background:#fff;border:1.5px solid var(--line);border-radius:20px;padding:14px;display:flex;gap:12px;align-items:center;transition:transform .15s,box-shadow .15s}
.tienda:hover{transform:translateY(-3px);box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.tienda .lg{width:58px;height:58px;border-radius:16px;background:var(--bg);display:grid;place-items:center;overflow:hidden;flex:none;font-weight:800;font-size:1.4rem;color:var(--g2)}
.tienda .lg img{width:100%;height:100%;object-fit:cover}
.tienda b{display:block;font-weight:800;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tienda small{display:block;color:var(--mut);font-weight:600;font-size:.78rem;margin-top:2px}
.tienda .min{flex:1;min-width:0}
.ok{display:inline-block;background:#e9fbe0;color:#127a35;border-radius:999px;padding:.05rem .5rem;font-size:.68rem;font-weight:800;margin-top:4px}
/* Productos */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
@media(max-width:520px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}}
.card{background:#fff;border:1.5px solid var(--line);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;min-width:0}
.card:hover{transform:translateY(-3px);box-shadow:0 18px 30px -22px rgba(22,179,74,.9)}
.card .ph{aspect-ratio:1/1;background:#fff;display:grid;place-items:center;font-size:2.4rem;overflow:hidden;position:relative;border-bottom:1px solid var(--line)}
.card .ph img{width:100%;height:100%;object-fit:contain;transition:transform .3s}
.card:hover .ph img{transform:scale(1.04)}
.card .promo{position:absolute;right:10px;top:10px;background:#e11d48;color:#fff;border-radius:999px;padding:.2rem .55rem;font-size:.74rem;font-weight:900}
.card .b{padding:10px 12px 12px;display:flex;flex-direction:column;gap:3px;flex:1}
.card .antes{color:var(--mut);text-decoration:line-through;font-weight:600;font-size:.8rem}
.card .pr{font-weight:800;font-size:1.15rem}
.card h3{font-size:.9rem;font-weight:600;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.card .de{margin-top:auto;padding-top:6px;font-size:.76rem;color:var(--mut);font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card .de b{color:var(--g2)}
.vacio{background:#fff;border:1.5px dashed var(--line);border-radius:20px;padding:34px 18px;text-align:center;color:var(--mut);font-weight:600}
.vacio b{display:block;color:var(--ink);font-size:1.1rem;margin-bottom:4px}
/* Paginación */
.pag{display:flex;gap:8px;justify-content:center;align-items:center;margin:24px 0 8px;font-weight:800}
.pag a,.pag span{min-width:44px;height:44px;border-radius:12px;background:#fff;border:1.5px solid var(--line);display:grid;place-items:center;padding:0 14px}
.pag span.on{background:var(--k);color:#fff;border-color:var(--k)}
.pag .off{opacity:.4}
section{margin-top:22px}
/* Llamado para negocios */
.cta{margin-top:34px;background:linear-gradient(135deg,#16211a,#0f1411);border:1px solid rgba(166,243,74,.2);border-radius:24px;color:#fff;padding:22px;display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.cta b{font-size:1.2rem}
.cta p{color:#a9b5ad;font-weight:600;margin-top:4px}
.cta a{background:var(--g);color:var(--k);border-radius:999px;padding:.8rem 1.3rem;font-weight:800}
.pie{background:var(--k);color:#a9b5ad;margin-top:40px;padding:26px 0;font-size:.82rem;text-align:center}
.pie b{color:#a6f34a}
</style>
<script>
// Foto que no carga (perdida en el servidor o mala conexión): en vez del texto gigante sale la inicial del producto
document.addEventListener('error', function (e) {
    var i = e.target;
    if (!i || i.tagName !== 'IMG' || i.dataset.rota) return;
    i.dataset.rota = 1;
    var t = (i.getAttribute('alt') || '').trim();
    var s = document.createElement('span');
    s.className = 'foto-rota';
    s.textContent = t ? t.charAt(0).toUpperCase() : '🛍️';
    i.replaceWith(s);
}, true);
</script>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a class="marca" href="{{ route('marketplace') }}"><i>L</i><span>LocalProo<small>Marketplace</small></span></a>
        <form class="busca" action="{{ route('marketplace') }}" method="get" role="search">
            @if ($tipo)<input type="hidden" name="tipo" value="{{ $tipo }}">@endif
            @if ($ciudad)<input type="hidden" name="ciudad" value="{{ $ciudad }}">@endif
            <input type="search" name="q" value="{{ $q }}" placeholder="Buscar productos o tiendas…" aria-label="Buscar" maxlength="80">
            <button type="submit" aria-label="Buscar">🔍</button>
        </form>
        @if (count($paisesConTiendas) > 1)
        <form method="get" action="{{ route('marketplace') }}" class="paisf">
            <select name="pais" aria-label="País" onchange="this.form.submit()">
                @foreach ($paisesConTiendas as $cod => $n)<option value="{{ $cod }}" @selected($cod === $pais)>{{ \App\Support\Paises::LISTA[$cod][0] }}</option>@endforeach
            </select>
        </form>
        @endif
        <a class="crear" href="{{ route('registro', ['desde' => 'marketplace']) }}"><span class="ico" aria-hidden="true">🏪</span>Venda aquí</a>
    </div>
</header>

@if (!$filtrando)
<div class="hero">
    <div class="wrap">
        <h1>Las tiendas de {{ $nombrePais }} <em>en un solo lugar</em></h1>
        <p>Busque lo que necesita, compare precios y compre directo al negocio por WhatsApp.</p>
        <div class="cifras">
            <span><b>{{ number_format($totalTiendas, 0, ',', '.') }}</b> {{ $totalTiendas === 1 ? 'tienda' : 'tiendas' }}</span>
            <span><b>{{ number_format($totalProductos, 0, ',', '.') }}</b> productos</span>
            <span>💬 Compra directa por WhatsApp</span>
        </div>
    </div>
</div>
@endif

<main class="wrap">
    {{-- Filtro por tipo de negocio --}}
    @if ($tipos->count() > 1 || $tipo)
    <nav class="filtros" aria-label="Tipos de negocio">
        <a class="chip {{ $tipo === '' ? 'on' : '' }}" href="{{ $url(['tipo' => null]) }}">Todo</a>
        @foreach ($tipos as $k => $n)
            <a class="chip {{ $tipo === $k ? 'on' : '' }}" href="{{ $url(['tipo' => $k]) }}">{{ \App\Models\Tienda::TIPOS[$k][0] }}<small>{{ $n }}</small></a>
        @endforeach
    </nav>
    @endif

    {{-- Tiendas --}}
    @if ($tiendas->isNotEmpty())
    <section>
        <div class="barra"><h2>🏪 {{ $filtrando ? 'Tiendas encontradas' : 'Tiendas' }} <small style="color:var(--mut);font-size:.9rem">({{ $tiendas->count() }})</small></h2></div>
        <div class="tiendas">
            @foreach ($tiendas as $t)
                <a class="tienda" href="{{ $t->url_catalogo }}">
                    <span class="lg">@if ($t->logo_url)<img src="{{ $t->logo_url }}" alt="{{ $t->nombre }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($t->nombre, 0, 1)) }}@endif</span>
                    <span class="min">
                        <b>{{ $t->nombre }}</b>
                        <small>{{ $t->tipo_nombre }}@if ($t->ciudad) · 📍 {{ $t->ciudad }}@endif</small>
                        <span class="ok">{{ $t->n_productos }} {{ $t->n_productos === 1 ? 'producto' : 'productos' }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Productos de todas las tiendas --}}
    <section>
        <div class="barra">
            <h2>{{ $q !== '' ? 'Resultados para "' . $q . '"' : '🛍️ Productos' }} <small style="color:var(--mut);font-size:.9rem">({{ number_format($productos->total(), 0, ',', '.') }})</small></h2>
            <form class="sel" method="get" action="{{ route('marketplace') }}" id="fOrden">
                @if ($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                @if ($tipo)<input type="hidden" name="tipo" value="{{ $tipo }}">@endif
                @if ($ciudades->count() > 1 || $ciudad)
                <select name="ciudad" aria-label="Ciudad" onchange="this.form.submit()">
                    <option value="">📍 Todas las ciudades</option>
                    @foreach ($ciudades as $c)<option value="{{ $c }}" @selected($ciudad === $c)>{{ $c }}</option>@endforeach
                </select>
                @endif
                <select name="orden" aria-label="Ordenar" onchange="this.form.submit()">
                    <option value="menor" @selected($orden === 'menor')>Menor precio</option>
                    <option value="mayor" @selected($orden === 'mayor')>Mayor precio</option>
                    <option value="nuevos" @selected($orden === 'nuevos')>Más nuevos</option>
                </select>
                @if ($filtrando)<a class="limpiar" href="{{ route('marketplace') }}" style="align-self:center">✕ Quitar filtros</a>@endif
            </form>
        </div>

        @if ($productos->isEmpty())
            <div class="vacio"><b>No encontramos productos{{ $q !== '' ? ' para "' . $q . '"' : '' }}</b>Pruebe con otra palabra o quite los filtros.</div>
        @else
            <div class="grid">
                @foreach ($productos as $p)
                    <a class="card" href="{{ $p->url }}">
                        <div class="ph">
                            @if ($p->img)<img src="{{ $p->img }}" alt="{{ $p->nombre }}" loading="lazy">@else🛍️@endif
                            @if ($p->descuento)<span class="promo">-{{ $p->descuento }}%</span>@endif
                        </div>
                        <div class="b">
                            @if ($p->antes)<span class="antes">{{ $p->antes }}</span>@endif
                            <span class="pr">{{ $p->precio }}</span>
                            <h3>{{ $p->nombre }}</h3>
                            <span class="de">Vendido por <b>{{ $p->tienda }}</b>@if ($p->ciudad) · {{ $p->ciudad }}@endif</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($productos->lastPage() > 1)
            <nav class="pag" aria-label="Páginas">
                @if ($productos->onFirstPage())<span class="off">‹</span>@else<a href="{{ $productos->previousPageUrl() }}" rel="prev">‹</a>@endif
                <span class="on">{{ $productos->currentPage() }} / {{ $productos->lastPage() }}</span>
                @if ($productos->hasMorePages())<a href="{{ $productos->nextPageUrl() }}" rel="next">›</a>@else<span class="off">›</span>@endif
            </nav>
            @endif
        @endif
    </section>

    <div class="cta">
        <div><b>¿Tiene un negocio? Aparezca aquí</b><p>Cree su tienda en línea con LocalProo y sus productos salen en el Marketplace. 2 meses gratis.</p></div>
        <a href="{{ route('registro', ['desde' => 'marketplace']) }}">Crear mi tienda →</a>
    </div>
</main>

<footer class="pie">
    @if ($ciudades->isNotEmpty() || $tipos->isNotEmpty())
    <nav style="max-width:1100px;margin:0 auto 12px;display:flex;flex-wrap:wrap;gap:6px 14px;justify-content:center;font-size:.85rem">
        @foreach ($ciudades->take(30) as $c)<a href="{{ route('marketplace', ['ciudad' => $c]) }}" style="color:inherit">Tiendas en {{ $c }}</a>@endforeach
        @foreach ($tipos->keys()->take(20) as $k)<a href="{{ route('marketplace', ['tipo' => $k]) }}" style="color:inherit">{{ \App\Models\Tienda::TIPOS[$k][0] }}</a>@endforeach
    </nav>
    @endif
    <b>LocalProo</b> · Todos los derechos reservados
</footer>
</body>
</html>
