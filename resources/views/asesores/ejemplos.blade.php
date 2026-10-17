@extends('layouts.app')

@section('title', 'Ejemplos para mostrar')

@push('css')
<style>
.ej-hero{background:linear-gradient(120deg,#0b0d0c,#163b22);color:#fff;border-radius:24px;padding:1.4rem 1.5rem;margin-bottom:1rem}
.ej-hero small{color:#a6f34a;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.72rem}
.ej-hero h2{font-weight:900;margin:.3rem 0 .4rem;font-size:clamp(1.4rem,4vw,2rem)}
.ej-hero p{color:#c6d2ca;margin:0;max-width:720px;font-weight:600}
.ej-tienda{background:#fff;border:1px solid #e3efe5;border-radius:22px;padding:1.1rem;margin-bottom:1rem;box-shadow:0 8px 26px -18px rgba(0,0,0,.35)}
.ej-cab{display:flex;gap:.8rem;align-items:center;margin-bottom:.9rem}
.ej-logo{width:52px;height:52px;border-radius:16px;background:#eef6f0;display:grid;place-items:center;overflow:hidden;flex:none;font-size:1.3rem;color:#16b34a}
.ej-logo img{width:100%;height:100%;object-fit:cover}
.ej-cab b{font-size:1.15rem;display:block;line-height:1.2}
.ej-cab small{color:#6b7570;font-weight:600}
.ej-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:.7rem}
.ej-item{border:1.5px solid #e3efe5;border-radius:18px;padding:.9rem;display:flex;flex-direction:column;gap:.5rem;background:#fbfdfb}
.ej-item .ic{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c;font-size:1.1rem}
.ej-item b{font-size:1rem}
.ej-item p{margin:0;color:#5b665e;font-size:.85rem;font-weight:600;flex:1}
.ej-item .acc{display:flex;gap:.4rem;flex-wrap:wrap}
.ej-item .acc a{flex:1;text-align:center;border-radius:999px;padding:.5rem .7rem;font-weight:800;font-size:.85rem;text-decoration:none;white-space:nowrap}
.ej-ver{background:#0b0d0c;color:#fff}.ej-ver:hover{color:#a6f34a}
.ej-wa{background:#25d366;color:#fff}.ej-wa:hover{color:#fff;filter:brightness(1.05)}
.ej-no{color:#9aa39d;font-size:.82rem;font-weight:700}
.ej-sec{font-weight:900;font-size:1.3rem;margin:1.6rem 0 .3rem}
.ej-sub{color:#5b665e;font-weight:600;margin-bottom:.9rem}
.mk-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:.9rem}
.mk{background:#fff;border:1px solid #e3efe5;border-radius:22px;padding:1rem;box-shadow:0 8px 26px -18px rgba(0,0,0,.35)}
.mk h4{font-weight:900;font-size:1.05rem;margin:0 0 .2rem}
.mk .d{color:#5b665e;font-size:.85rem;font-weight:600;margin-bottom:.7rem}
.mk-pant{background:#f4f8f5;border-radius:16px;padding:.7rem;position:relative;font-size:.8rem}
.mk-pant::before{content:"Ejemplo";position:absolute;top:8px;right:10px;background:#0b0d0c;color:#a6f34a;font-size:.62rem;font-weight:800;padding:.1rem .5rem;border-radius:99px;letter-spacing:.05em;text-transform:uppercase}
.mk-row{display:flex;justify-content:space-between;align-items:center;background:#fff;border-radius:10px;padding:.45rem .6rem;margin-top:.35rem;font-weight:700}
.mk-row small{color:#6b7570;font-weight:600}
.mk-tot{display:flex;justify-content:space-between;align-items:center;background:#0b0d0c;color:#fff;border-radius:12px;padding:.6rem .7rem;margin-top:.5rem;font-weight:900}
.mk-tot span:last-child{color:#a6f34a;font-size:1.05rem}
.mk-chip{display:inline-block;border-radius:99px;padding:.1rem .5rem;font-size:.7rem;font-weight:800}
.mk-ok{background:#dcfce7;color:#14753a}.mk-low{background:#fef3c7;color:#92600a}.mk-zero{background:#fde2e8;color:#b0173f}
.mk-kpis{display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-top:.2rem}
.mk-kpi{background:#fff;border-radius:12px;padding:.5rem .6rem}
.mk-kpi small{display:block;color:#6b7570;font-weight:700;font-size:.7rem}
.mk-kpi b{font-size:1rem}
.mk-bars{display:flex;align-items:flex-end;gap:5px;height:70px;background:#fff;border-radius:12px;padding:.5rem .6rem;margin-top:.4rem}
.mk-bars i{flex:1;background:linear-gradient(180deg,#a6f34a,#16b34a);border-radius:5px 5px 2px 2px}
.mk-tiq{background:#fff;border-radius:6px;max-width:220px;margin:0 auto;padding:.7rem .8rem;font-family:'Courier New',monospace;font-size:.72rem;line-height:1.35;box-shadow:0 4px 14px -8px rgba(0,0,0,.3)}
.mk-tiq hr{border:0;border-top:1px dashed #999;margin:.35rem 0}
.mk-tiq .c{text-align:center}.mk-tiq .r{display:flex;justify-content:space-between}
.mk-chat{background:#fff;border-radius:12px;padding:.5rem;margin-top:.35rem}
.mk-msg{background:#eef1ef;border-radius:12px 12px 12px 3px;padding:.35rem .55rem;max-width:80%;margin-bottom:.3rem;font-weight:600}
.mk-msg.yo{background:#dcfce7;margin-left:auto;border-radius:12px 12px 3px 12px}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-eye me-1"></i>Red comercial</small><h1>Ejemplos para <span>mostrar</span></h1><span class="text-muted fw-semibold">Así queda un negocio con LocalPro</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="ej-hero">
        <small>Venda mostrando</small>
        <h2>El cliente compra lo que ve 👀</h2>
        <p>Abra estos ejemplos en su celular durante la visita, o envíelos por WhatsApp. Son negocios reales hechos con LocalPro: la tienda en línea, la página de enlaces y la factura que reciben los clientes.</p>
    </div>

    @forelse ($ejemplos as $e)
    @php
        $t = $e['tienda'];
        $wa = fn ($txt, $url) => 'https://wa.me/?text=' . rawurlencode($txt . ' ' . $url);
    @endphp
    <div class="ej-tienda">
        <div class="ej-cab">
            <div class="ej-logo">@if ($t->logo_url)<img src="{{ $t->logo_url }}" alt="">@else<i class="fa-solid {{ $e['tipo'][1] ?? 'fa-store' }}"></i>@endif</div>
            <div><b>{{ $t->nombre }}</b><small>{{ $e['tipo'][0] ?? 'Negocio' }}{{ $t->ciudad ? ' · ' . $t->ciudad : '' }}{{ $e['catalogo'] ? ' · ' . $e['productos'] . ' productos en línea' : '' }}</small></div>
        </div>
        <div class="ej-grid">
            <div class="ej-item">
                <div class="ic"><i class="fa-solid fa-globe"></i></div>
                <b>Tienda en línea</b>
                <p>Productos con foto por categorías y botón Comprar por WhatsApp. Sale en Google.</p>
                @if ($e['catalogo'])
                <div class="acc"><a href="{{ $e['catalogo'] }}" target="_blank" rel="noopener" class="ej-ver"><i class="fa-solid fa-eye me-1"></i>Ver ejemplo</a><a href="{{ $wa('Mire cómo queda la tienda en línea de un negocio con LocalPro 👉', $e['catalogo']) }}" target="_blank" rel="noopener" class="ej-wa"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</a></div>
                @else<span class="ej-no">Este ejemplo aún no tiene la tienda en línea activa.</span>@endif
            </div>
            <div class="ej-item">
                <div class="ic"><i class="fa-solid fa-link"></i></div>
                <b>Mis enlaces (tipo Linktree)</b>
                <p>Una sola página con WhatsApp, Instagram, Facebook, ubicación y tienda. Para poner en la bio de Instagram.</p>
                @if ($e['enlaces'])
                <div class="acc"><a href="{{ $e['enlaces'] }}" target="_blank" rel="noopener" class="ej-ver"><i class="fa-solid fa-eye me-1"></i>Ver ejemplo</a><a href="{{ $wa('Así queda la página de enlaces de un negocio con LocalPro 👉', $e['enlaces']) }}" target="_blank" rel="noopener" class="ej-wa"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</a></div>
                @else<span class="ej-no">Este ejemplo aún no tiene su página de enlaces publicada.</span>@endif
            </div>
            <div class="ej-item">
                <div class="ic"><i class="fa-solid fa-receipt"></i></div>
                <b>Factura que recibe el cliente</b>
                <p>Cada venta le llega al cliente por WhatsApp con el logo del negocio. También se imprime en tiquete de 80 mm.</p>
                @if ($e['factura'])
                <div class="acc"><a href="{{ $e['factura'] }}" target="_blank" rel="noopener" class="ej-ver"><i class="fa-solid fa-eye me-1"></i>Ver ejemplo</a><a href="{{ $wa('Así le llega la factura al cliente con LocalPro 👉', $e['factura']) }}" target="_blank" rel="noopener" class="ej-wa"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</a></div>
                @else<span class="ej-no">Este ejemplo aún no tiene ventas.</span>@endif
            </div>
        </div>
    </div>
    @empty
    <div class="lp-box mb-3"><b>Pronto habrá ejemplos aquí.</b> <span class="text-muted">LocalPro está preparando negocios de muestra.</span>@if ($esAdmin) <a href="{{ route('admin.planes.index') }}">Elegir tiendas de ejemplo</a>@endif</div>
    @endforelse

    @if ($esAdmin)
    <p class="small text-muted"><i class="fa-solid fa-gear me-1"></i>Usted elige las tiendas de ejemplo en <a href="{{ route('admin.planes.index') }}">Planes y cobros</a>.</p>
    @endif

    <div class="ej-sec">Lo que el negocio usa por dentro</div>
    <div class="ej-sub">Así se ven las pantallas que usa el dueño y sus vendedores (datos de ejemplo).</div>

    <div class="mk-grid">
        <div class="mk">
            <h4><i class="fa-solid fa-cash-register me-1" style="color:#16b34a"></i>Caja (POS)</h4>
            <div class="d">Vende en segundos desde el celular o el computador. Escanea el código con la cámara.</div>
            <div class="mk-pant">
                <div class="mk-row"><span>Camiseta básica · M · Negro</span><span>$45.000</span></div>
                <div class="mk-row"><span>Jean clásico · 32</span><span>$89.000</span></div>
                <div class="mk-row"><span>Gorra urbana</span><span>$35.000</span></div>
                <div class="mk-tot"><span>Total · Nequi</span><span>$169.000</span></div>
            </div>
        </div>
        <div class="mk">
            <h4><i class="fa-solid fa-boxes-stacked me-1" style="color:#16b34a"></i>Inventario</h4>
            <div class="d">Sabe cuánto le queda de cada producto, por talla y color. Avisa lo que se está acabando.</div>
            <div class="mk-pant">
                <div class="mk-row"><span>Camiseta básica<br><small>S · M · L · XL</small></span><span class="mk-chip mk-ok">24 unidades</span></div>
                <div class="mk-row"><span>Jean clásico<br><small>30 · 32 · 34</small></span><span class="mk-chip mk-low">3 unidades</span></div>
                <div class="mk-row"><span>Chaqueta jean<br><small>M · L</small></span><span class="mk-chip mk-zero">Agotado</span></div>
            </div>
        </div>
        <div class="mk">
            <h4><i class="fa-solid fa-chart-line me-1" style="color:#16b34a"></i>Ventas y ganancias</h4>
            <div class="d">El dueño ve cuánto vendió y cuánto ganó. Los vendedores solo ven sus ventas y su meta.</div>
            <div class="mk-pant">
                <div class="mk-kpis">
                    <div class="mk-kpi"><small>Vendido hoy</small><b>$1.240.000</b></div>
                    <div class="mk-kpi"><small>Ganancia hoy</small><b style="color:#16b34a">$438.000</b></div>
                </div>
                <div class="mk-bars"><i style="height:40%"></i><i style="height:65%"></i><i style="height:50%"></i><i style="height:80%"></i><i style="height:60%"></i><i style="height:95%"></i><i style="height:72%"></i></div>
            </div>
        </div>
        <div class="mk">
            <h4><i class="fa-solid fa-print me-1" style="color:#16b34a"></i>Tiquete de 80 mm</h4>
            <div class="d">Con el logo y los datos del negocio. Si no hay impresora, va por WhatsApp.</div>
            <div class="mk-pant">
                <div class="mk-tiq">
                    <div class="c"><b>MI TIENDA</b><br>NIT 900.123.456-7<br>Pasto · 300 123 4567</div>
                    <hr>
                    <div>Venta #1024 · 17/10/2026</div>
                    <hr>
                    <div class="r"><span>1 Camiseta M</span><span>45.000</span></div>
                    <div class="r"><span>1 Jean 32</span><span>89.000</span></div>
                    <hr>
                    <div class="r"><b>TOTAL</b><b>134.000</b></div>
                    <div class="r"><span>Efectivo</span><span>150.000</span></div>
                    <div class="r"><span>Cambio</span><span>16.000</span></div>
                    <hr>
                    <div class="c">¡Gracias por su compra!</div>
                </div>
            </div>
        </div>
        <div class="mk">
            <h4><i class="fa-solid fa-file-invoice me-1" style="color:#16b34a"></i>Facturación electrónica DIAN</h4>
            <div class="d">POS electrónico o factura electrónica con CUFE y código QR, cuando el negocio la necesite.</div>
            <div class="mk-pant">
                <div class="mk-row"><span>FE-1024 · Cliente con NIT</span><span class="mk-chip mk-ok">Aceptada</span></div>
                <div class="mk-row"><span>POS-3310</span><span class="mk-chip mk-ok">Aceptada</span></div>
                <div class="mk-row"><span>Nota crédito NCE-12</span><span class="mk-chip mk-low">Enviando</span></div>
                <div class="mk-row"><span><small>IVA 19 % separado por producto</small></span><span><i class="fa-solid fa-qrcode"></i></span></div>
            </div>
        </div>
        <div class="mk">
            <h4><i class="fa-solid fa-comments me-1" style="color:#16b34a"></i>Mis redes</h4>
            <div class="d">Responde los mensajes de Facebook e Instagram en un solo lugar y publica sus productos.</div>
            <div class="mk-pant">
                <div class="mk-chat">
                    <div class="mk-msg"><i class="fa-brands fa-instagram me-1" style="color:#e1306c"></i>Hola, ¿tienen el jean en talla 30?</div>
                    <div class="mk-msg yo">¡Sí! Le envío foto y precio 👇</div>
                    <div class="mk-msg yo">👖 Jean clásico · $89.000 · localproo.com/t/mi-tienda</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
