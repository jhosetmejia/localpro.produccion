@extends('layouts.app')

@section('title', 'Vender más')

@push('css')
<style>
.vm-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:1.8rem 1.6rem;position:relative;overflow:hidden;margin-bottom:1rem}
.vm-hero::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;border:46px solid rgba(166,243,74,.12);right:-100px;top:-120px}
.vm-hero small{color:#9fe3b0;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem}
.vm-hero h1{font-size:clamp(1.7rem,4vw,2.5rem);font-weight:900;line-height:1.08;margin:.35rem 0 .5rem;max-width:20ch;position:relative;z-index:1}
.vm-hero h1 em{font-style:normal;color:#a6f34a}
.vm-hero p{color:#c9d3cc;max-width:52ch;font-weight:500;margin:0;position:relative;z-index:1}
.vm-num{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.1rem;position:relative;z-index:1}
.vm-num div{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:.55rem .9rem}
.vm-num b{display:block;font-size:1.35rem;font-weight:900;color:#a6f34a;line-height:1.1}
.vm-num span{font-size:.75rem;color:#b8c4bc;font-weight:700}
.vm-paso{font-size:.8rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#6b7570;margin:1.3rem .2rem .6rem}
.vm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.9rem}
.vm-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.15rem 1.2rem;display:flex;flex-direction:column;gap:.45rem;text-decoration:none;color:#0b0d0c;transition:transform .15s,box-shadow .15s,border-color .15s;position:relative}
.vm-card:hover{transform:translateY(-3px);box-shadow:0 18px 34px -22px rgba(0,0,0,.4);border-color:#bfeccb;color:#0b0d0c}
.vm-card .ic{width:48px;height:48px;border-radius:15px;display:grid;place-items:center;font-size:1.35rem;margin-bottom:.2rem}
.vm-card h3{font-size:1.08rem;font-weight:900;margin:0}
.vm-card p{color:#55605a;font-size:.9rem;font-weight:500;margin:0;flex:1}
.vm-card .ir{font-weight:800;color:#11702f;font-size:.88rem}
.vm-card .tag{position:absolute;top:14px;right:14px;font-size:.68rem;font-weight:900;border-radius:999px;padding:.18rem .55rem;background:#a6f34a;color:#0b0d0c}
.vm-card.estrella{background:linear-gradient(180deg,#f4fcf6,#fff);border-color:#bfeccb}
</style>
@endpush

@section('content')
<div class="lp-dash">
    @include('layouts.partials.lp-flash')
    <div class="vm-hero">
        <small><i class="fa-solid fa-rocket me-1"></i>Vender más</small>
        <h1>Para que venda más, tenemos <em>estas herramientas</em> para usted</h1>
        <p>Suba su base de datos, escríbales a sus clientes por WhatsApp con un toque, atienda sus redes y comparta su tienda en línea. Todo desde aquí.</p>
        <div class="vm-num">
            <div><b>{{ number_format($datos['base'], 0, ',', '.') }}</b><span>clientes con celular</span></div>
            <div><b>{{ number_format($datos['porContactar'], 0, ',', '.') }}</b><span>por contactar</span></div>
            <div><b>{{ number_format($datos['contactados'], 0, ',', '.') }}</b><span>ya contactados</span></div>
            @if ($tienda->catalogo_activo)<div><b>{{ number_format($datos['visitas'], 0, ',', '.') }}</b><span>visitas a su tienda</span></div>@endif
        </div>
    </div>

    <div class="vm-paso">1 · Arme su base de datos</div>
    <div class="vm-grid">
        <a href="{{ route('clientes.importar') }}" class="vm-card">
            <div class="ic" style="background:#e8f8ec;color:#16b34a"><i class="fa-solid fa-file-excel"></i></div>
            <h3>Importar base de datos de Excel</h3>
            <p>Suba su Excel con clientes y LocalPro encuentra solo el nombre, la cédula, el celular y el correo. Sin repetir a nadie.</p>
            <span class="ir">Subir mi Excel →</span>
        </a>
        <a href="{{ route('clientes.contactos') }}" class="vm-card">
            <div class="ic" style="background:#eef4ff;color:#2563eb"><i class="fa-solid fa-mobile-screen"></i></div>
            <h3>Contactos de su celular</h3>
            <p>Pase los contactos de su teléfono a LocalPro y conviértalos en clientes a los que les puede escribir.</p>
            <span class="ir">Subir mis contactos →</span>
        </a>
    </div>

    <div class="vm-paso">2 · Escríbales y venda</div>
    <div class="vm-grid">
        <a href="{{ route('clientes.contactar') }}" class="vm-card estrella">
            <span class="tag">NUEVO</span>
            <div class="ic" style="background:#25d366;color:#fff"><i class="fa-brands fa-whatsapp"></i></div>
            <h3>Contactar clientes · WhatsApp marketing</h3>
            <p>Cada día a usted y a su equipo les salen clientes para escribirles con un mensaje listo y el enlace de su tienda. A cada persona se le escribe una sola vez.</p>
            <span class="ir">{{ $datos['hoy'] ? $datos['hoy'] . ' contactados hoy · ' : '' }}Empezar a escribir →</span>
        </a>
        @if ($hayRedes)
        <a href="{{ route('redes.index') }}" class="vm-card">
            <div class="ic" style="background:linear-gradient(135deg,#1877f2,#dd2a7b);color:#fff"><i class="fa-solid fa-comments"></i></div>
            <h3>Facebook e Instagram</h3>
            <p>Responda los mensajes de Messenger e Instagram en un solo lugar y publique sus productos con foto, precio y enlace.</p>
            <span class="ir">Ir a Mis redes →</span>
        </a>
        @endif
    </div>

    <div class="vm-paso">3 · Que lo encuentren</div>
    <div class="vm-grid">
        @if ($tienda->catalogo_activo && $tienda->slug)
        <a href="{{ $tienda->url_catalogo_navegador }}" target="_blank" rel="noopener" class="vm-card">
            <div class="ic" style="background:#0b0d0c;color:#a6f34a"><i class="fa-solid fa-store"></i></div>
            <h3>Su tienda en línea</h3>
            <p>Su catálogo con botón de comprar por WhatsApp, que aparece en Google. Compártalo en sus estados y grupos.</p>
            <span class="ir">{{ $datos['visitas'] ? number_format($datos['visitas'], 0, ',', '.') . ' visitas · ' : '' }}Ver mi tienda →</span>
        </a>
        @else
        <a href="{{ route('negocio.edit') }}" class="vm-card">
            <div class="ic" style="background:#0b0d0c;color:#a6f34a"><i class="fa-solid fa-store"></i></div>
            <h3>Active su tienda en línea</h3>
            <p>Muestre sus productos en internet, con botón de comprar por WhatsApp y apareciendo en Google.</p>
            <span class="ir">Activarla →</span>
        </a>
        @endif
        @if ($hayEnlaces && \App\Http\Controllers\enlacesController::puedeEditar())
        <a href="{{ route('enlaces.index') }}" class="vm-card">
            <div class="ic" style="background:#fff4e5;color:#ea7a0c"><i class="fa-solid fa-link"></i></div>
            <h3>Mis enlaces</h3>
            <p>Una página con su WhatsApp, redes y promociones para la biografía de Instagram, TikTok o un código QR en el mostrador.</p>
            <span class="ir">Armar mis enlaces →</span>
        </a>
        @endif
    </div>
</div>
@endsection
