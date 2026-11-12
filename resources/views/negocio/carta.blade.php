@extends('layouts.app')

@section('title', 'Mi carta')

@push('css')
<style>
@font-face{font-family:'Bebas Neue';font-weight:400;font-display:swap;src:url('/fonts/carta/bebas-neue-400.woff2') format('woff2')}
.mc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
@media (max-width:1100px){.mc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:560px){.mc-grid{grid-template-columns:minmax(0,1fr)}}
.mc-pl{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;overflow:hidden;display:flex;flex-direction:column}
.mc-mock{height:210px;padding:18px;display:flex;flex-direction:column;gap:8px;position:relative;overflow:hidden}
.mc-mock .t{font-size:1.25rem;font-weight:900;text-align:center;line-height:1.1}
.mc-mock .s{font-size:.8rem;text-align:center;opacity:.8}
.mc-mock .r{display:flex;justify-content:space-between;gap:8px;font-size:.82rem;font-weight:700}
.mc-mock .r i{flex:1;border-bottom:2px dotted currentColor;opacity:.4;transform:translateY(-4px)}
.mc-clasica{background:#f6efe2;color:#2a1f17;font-family:'Playfair Display',Georgia,serif;outline:2px solid #b08d57;outline-offset:-10px}
.mc-pizarra{background:radial-gradient(120% 90% at 50% 40%,#3a4441,#1d2321);color:#f3f1e7;border:12px solid #6b4a2b;font-family:'Caveat',cursive}
.mc-pizarra .r span:last-child{color:#f6d365}
.mc-moderna{background:#fff;color:#141414}
.mc-pro{background:#121212;color:#f4f1ea;font-family:'Bebas Neue','Arial Narrow',sans-serif;letter-spacing:.02em}
.mc-pro .t{font-size:2.2rem;line-height:.9}
.mc-pro .s{display:inline-block;align-self:center;background:#f7c51e;color:#121212;font-weight:700;padding:1px 10px;opacity:1}
.mc-pro .r{font-size:.95rem}
.mc-pro .r span:last-child{color:#f7c51e}
.mc-moderna .t{background:#ff5a1f;color:#fff;margin:-18px -18px 6px;padding:18px;text-align:left}
.mc-moderna .card{display:flex;gap:8px;align-items:center;background:#f6f3ef;border-radius:12px;padding:6px;font-size:.78rem;font-weight:700}
.mc-moderna .card b{width:34px;height:34px;border-radius:9px;background:#ffe2d4;flex:none}
.mc-navidad{background:radial-gradient(90% 60% at 50% 0%,#9b1b2a,#6d0d18 60%,#4a0810);color:#fbf1df;font-family:'Playfair Display',Georgia,serif;outline:2px solid #d9b45a;outline-offset:-10px}
.mc-navidad .s{color:#f2c96b;font-style:italic}
.mc-navidad .r span:last-child{color:#f2c96b}
.mc-menu-navidad{background:#fbf5ea;color:#1d3b2a;font-family:'Playfair Display',Georgia,serif;border-top:16px solid #1f5a3a;box-shadow:inset 0 3px 0 #c1121f}
.mc-menu-navidad .t{color:#c1121f;font-style:italic}
.mc-menu-navidad .r{flex-direction:column;align-items:center;gap:0;text-align:center}
.mc-menu-navidad .r span:last-child{color:#c1121f}
.mc-nav-tag{position:absolute;right:10px;bottom:6px;font-size:1.6rem}
.mc-navidad-aviso{background:linear-gradient(135deg,#7a0f1c,#0f2a1d);color:#fff;border-radius:18px;padding:14px 16px;margin-bottom:14px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.mc-navidad-aviso b{color:#ffd166}
.mc-navidad-aviso a{margin-left:auto}
.mc-moderna .card em{margin-left:auto;font-style:normal;background:#141414;color:#fff;border-radius:99px;padding:2px 8px}
.mc-b{padding:14px 16px 16px;display:flex;flex-direction:column;gap:8px;flex:1}
.mc-b small{color:#5b665e}
.mc-acc{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-top:auto}
.mc-acc .btn{border-radius:12px;font-weight:800;font-size:.85rem}
.mc-ia{background:linear-gradient(135deg,#0b0d0c,#18231c);color:#f4f7f5;border-radius:24px;padding:22px;margin-bottom:1rem;position:relative;overflow:hidden}
.mc-ia::after{content:"";position:absolute;right:-60px;top:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,#a6f34a55,transparent 70%)}
.mc-ia h3{font-weight:900;font-size:1.35rem;margin:0 0 4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.mc-ia h3 .nuevo{background:#a6f34a;color:#0b0d0c;font-size:.7rem;font-weight:900;border-radius:999px;padding:3px 10px;letter-spacing:.04em}
.mc-ia p{color:#b9c6be;font-weight:600;margin:0 0 14px;max-width:760px;position:relative;z-index:1}
.mc-ia .btn-nueva{background:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);color:#0b0d0c;font-weight:900;border:0;border-radius:999px;padding:.7rem 1.4rem;position:relative;z-index:1}
.mc-ia-pasos{display:grid;gap:14px;margin-top:16px;position:relative;z-index:1}
.mc-ia-paso{background:#fff;color:#151a17;border-radius:18px;padding:16px}
.mc-ia-paso h4{font-size:1rem;font-weight:900;margin:0 0 6px;display:flex;gap:10px;align-items:center}
.mc-ia-paso h4 b{flex:none;width:28px;height:28px;border-radius:50%;background:#0b0d0c;color:#a6f34a;display:grid;place-items:center;font-size:.85rem}
.mc-ia-paso textarea{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.82rem;background:#f6f8f7}
.mc-ia-ias{display:flex;gap:6px;flex-wrap:wrap}
.mc-ia-ias a{border:1.5px solid #e3ebe6;border-radius:999px;padding:.3rem .8rem;font-weight:800;font-size:.85rem;color:#151a17;text-decoration:none;background:#fff}
.mc-ia-ias a:hover{border-color:#2fd15a}
.mc-ia-lista{background:#fff;color:#151a17;border-radius:18px;padding:12px;display:grid;grid-template-columns:minmax(0,260px) 1fr;gap:16px;position:relative;z-index:1}
@media (max-width:700px){.mc-ia-lista{grid-template-columns:1fr}}
.mc-pub{background:#a6f34a1f;border:1.5px solid #a6f34a66;border-radius:14px;padding:10px 12px}
.mc-dis{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
.mc-dis-c{background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;overflow:hidden;display:flex;flex-direction:column}
.mc-dis-c iframe{width:100%;height:300px;border:0;border-bottom:1.5px solid #e3ebe6;background:#fff;pointer-events:none}
.mc-dis-c .b{padding:12px;display:flex;flex-direction:column;gap:6px;flex:1}
.mc-dis-c .b .acc{display:grid;grid-template-columns:1fr 1.6fr;gap:6px;margin-top:auto}
.mc-dis-c .b .acc .btn{border-radius:12px;font-weight:800;font-size:.85rem}
.mc-ia-lista iframe{width:100%;height:340px;border:1.5px solid #e3ebe6;border-radius:14px;background:#fff}
</style>
@endpush

@section('content')
@php
    $planes = \App\Http\Controllers\catalogoController::PLANTILLAS_CARTA;
    $base = $tienda->slug ? route('catalogo.carta.imprimir', $tienda->slug) : null;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-utensils me-1"></i>Restaurante</small><h1>Mi <span>carta</span></h1>
            <span class="text-muted fw-semibold">Su carta en línea para el QR de las mesas y su carta para imprimir o compartir en redes, en PDF y JPG. Se arma sola con sus productos y precios.</span></div>
        @if ($listo)<div class="lp-quick"><a href="{{ $tienda->url_carta_navegador }}" target="_blank" rel="noopener" class="btn btn-dark btn-lg"><i class="fa-solid fa-eye me-1"></i>Ver mi carta</a></div>@endif
    </div>
    @include('layouts.partials.lp-flash')

    @unless ($listo)
        <div class="alert border-0 fw-semibold" style="background:#fff5d6;color:#8a6400">
            Para ver y descargar su carta, active su tienda en línea en <a href="{{ route('negocio.edit') }}" style="color:inherit;text-decoration:underline">Datos del negocio</a> ("Tienda en línea visible al público").
        </div>
    @endunless
    @if ($listo && !$hayProductos)
        <div class="alert border-0 fw-semibold" style="background:#fff5d6;color:#8a6400">
            Su carta está vacía. Cree sus platos en <a href="{{ route('productos.create') }}" style="color:inherit;text-decoration:underline">Productos</a> con nombre, precio, categoría (Entradas, Platos fuertes, Bebidas…) y una foto.
        </div>
    @endif

    {{-- Carta personalizada hecha con la IA del negocio --}}
    <div class="mc-ia" id="carta-ia">
        <h3><i class="fa-solid fa-wand-magic-sparkles" style="color:#a6f34a"></i> Nueva carta con tu IA <span class="nuevo">NUEVO</span></h3>
        <p>Tu propia IA (ChatGPT, Claude o Gemini, la de tu celular) te diseña la carta a tu gusto. La pegas aquí y LocalPro le pone tus platos y precios solos: si cambias un precio, la carta se actualiza sola.</p>

        @if ($cartaIa)
        <div class="mc-ia-lista mb-3">
            <iframe sandbox title="Vista de su carta personalizada" srcdoc="{{ $cartaIa }}" loading="lazy"></iframe>
            <div class="d-flex flex-column gap-2">
                <div class="fw-bold fs-5">Su carta personalizada</div>
                @if ($cartaIaAuto)
                <small class="fw-bold" style="color:#16b34a"><i class="fa-solid fa-rotate me-1"></i>Platos y precios automáticos: se actualizan solos.</small>
                @else
                <small class="fw-bold" style="color:#b7791f"><i class="fa-solid fa-triangle-exclamation me-1"></i>Esta carta tiene los precios escritos a mano: si cambia un precio, hágala de nuevo con "Hacer otra carta".</small>
                @endif
                <small class="text-muted fw-semibold">Guardada {{ optional($cartaIaFecha)->diffForHumans() }}.</small>
                @if ($cartaIaAuto)
                    @if ($cartaIaCompartir === null)
                    <form method="post" action="{{ route('carta.ia.compartir') }}" class="mc-pub">
                        @csrf
                        <div class="fw-bold mb-1"><i class="fa-solid fa-earth-americas me-1"></i>¿Quiere que su diseño sea público?</div>
                        <small class="d-block text-muted fw-semibold mb-2">Otros negocios podrían usarlo como plantilla con <b>sus</b> platos y precios (los suyos no se comparten). Saldrá como "Diseño de {{ $tienda->nombre }}".</small>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-success btn-sm fw-bold" name="compartir" value="si">Sí, que sea público</button>
                            <button class="btn btn-light border btn-sm fw-bold" name="compartir" value="no">No, solo mío</button>
                        </div>
                    </form>
                    @elseif ($cartaIaCompartir)
                    <form method="post" action="{{ route('carta.ia.compartir') }}" class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf
                        <small class="fw-bold" style="color:#16b34a"><i class="fa-solid fa-earth-americas me-1"></i>Su diseño es público: otros negocios lo pueden usar.</small>
                        <button class="btn btn-link btn-sm p-0 fw-bold" name="compartir" value="no">Dejarlo solo mío</button>
                    </form>
                    @else
                    <form method="post" action="{{ route('carta.ia.compartir') }}" class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf
                        <small class="text-muted fw-semibold"><i class="fa-solid fa-lock me-1"></i>Su diseño es solo suyo.</small>
                        <button class="btn btn-link btn-sm p-0 fw-bold" name="compartir" value="si">Hacerlo público</button>
                    </form>
                    @endif
                @endif
                @if ($listo)
                <form method="post" action="{{ route('carta.ia.qr') }}" class="form-check form-switch m-0">
                    @csrf
                    <input type="hidden" name="en_qr" value="{{ $cartaIaQr ? 0 : 1 }}">
                    <input class="form-check-input" type="checkbox" role="switch" id="mcIaQr" @checked($cartaIaQr) onchange="this.form.submit()">
                    <label class="form-check-label fw-bold" for="mcIaQr">Usar esta carta en el QR de las mesas</label>
                    <div class="small text-muted">Trae un botón "Pedir en línea" para que le sigan pidiendo.</div>
                </form>
                @endif
                @if ($listo)
                <input class="form-control" value="{{ route('catalogo.carta.ia', $tienda->slug) }}" readonly onclick="this.select()">
                @endif
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-dark fw-bold" target="_blank" rel="noopener" href="{{ $listo ? route('catalogo.carta.ia', $tienda->slug) : route('carta.ia.ver') }}"><i class="fa-solid fa-eye me-1"></i>Ver</a>
                    @if ($listo)
                    <a class="btn btn-success fw-bold" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('🍽️ Mira nuestra carta: ' . route('catalogo.carta.ia', $tienda->slug)) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
                    @endif
                    <form method="post" action="{{ route('carta.ia.borrar') }}" data-confirm="¿Borrar su carta personalizada?" data-confirm-ok="Sí, borrar" data-peligro>
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger fw-bold"><i class="fa-regular fa-trash-can me-1"></i>Borrar</button>
                    </form>
                </div>
                <small class="text-muted">Para imprimirla o guardarla en PDF: tóquela en <b>Ver</b> y use <b>Imprimir</b> del navegador (Ctrl + P).</small>
            </div>
        </div>
        @endif

        <button type="button" class="btn-nueva" data-bs-toggle="collapse" data-bs-target="#mcIaPasos" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}"><i class="fa-solid fa-plus me-1"></i>{{ $cartaIa ? 'Hacer otra carta' : 'Nueva carta' }}</button>

        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="mcIaPasos">
            <div class="mc-ia-pasos">
                <div class="mc-ia-paso">
                    <h4><b>1</b> Abra su IA: las instrucciones ya van escritas</h4>
                    <small class="text-muted d-block mb-2">Toque su IA, cambie donde dice <b>[ESCRIBA AQUÍ EL ESTILO…]</b> por cómo quiere su carta (rústica, elegante, moderna, con sus colores…) y envíe. Si no le gusta, pídale cambios.</small>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <a class="btn btn-dark fw-bold mc-ia-abrir" data-base="https://chatgpt.com/?q=" href="https://chatgpt.com/?q={{ rawurlencode($instruccionesIa) }}" target="_blank" rel="noopener"><i class="fa-solid fa-robot me-1"></i>Crear con ChatGPT</a>
                        <a class="btn btn-dark fw-bold mc-ia-abrir" href="https://claude.ai/new?q={{ rawurlencode($instruccionesIa) }}" target="_blank" rel="noopener"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Crear con Claude</a>
                        <a class="btn btn-light border fw-bold mc-ia-copiar-abrir" href="https://gemini.google.com/app" target="_blank" rel="noopener"><i class="fa-brands fa-google me-1"></i>Gemini (copia y pega)</a>
                    </div>
                    <details>
                        <summary class="small fw-bold" style="cursor:pointer">Ver o copiar las instrucciones</summary>
                        <textarea class="form-control my-2" rows="7" readonly id="mcIaTexto">{{ $instruccionesIa }}</textarea>
                        <button type="button" class="btn btn-sm btn-dark fw-bold" id="mcIaCopiar"><i class="fa-regular fa-copy me-1"></i>Copiar instrucciones</button>
                    </details>
                </div>
                <div class="mc-ia-paso">
                    <h4><b>2</b> Copie lo que le respondió su IA</h4>
                    <small class="text-muted fw-semibold">Cuando le guste el diseño, toque <b>Copiar</b> en la respuesta (copia todo el código) y vuelva aquí.</small>
                </div>
                <form class="mc-ia-paso" method="post" action="{{ route('carta.ia.guardar') }}">
                    @csrf
                    <h4><b>3</b> Péguelo aquí y guarde</h4>
                    @if ($errors->any())<div class="alert alert-danger py-2 fw-semibold">{{ $errors->first() }}</div>@endif
                    <textarea class="form-control mb-2" name="html" id="mcIaHtml" rows="6" placeholder="<!DOCTYPE html> ... </html>" required>{{ old('html') }}</textarea>
                    <div class="mc-pub mb-2" style="background:#f4fcf6;border-color:#cdeed6">
                        <div class="fw-bold mb-1"><i class="fa-solid fa-earth-americas me-1"></i>¿Quiere que su diseño sea público?</div>
                        <small class="d-block text-muted fw-semibold mb-2">Si dice que sí, otros negocios lo verán en "Diseños de otros negocios" y lo podrán usar con <b>sus</b> platos y precios. Sus platos, precios y datos no se comparten.</small>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="compartir" id="mcPubSi" value="si" required @checked(old('compartir') === 'si')>
                            <label class="form-check-label fw-bold" for="mcPubSi">Sí, que sea público</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="compartir" id="mcPubNo" value="no" required @checked(old('compartir') === 'no')>
                            <label class="form-check-label fw-bold" for="mcPubNo">No, solo mío</label>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-dark fw-bold" id="mcIaPegar"><i class="fa-regular fa-paste me-1"></i>Pegar</button>
                        <button class="btn btn-success fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar mi carta</button>
                    </div>
                    <small class="text-muted d-block mt-2">Por seguridad, LocalPro le quita los programas (JavaScript) al código: la carta se ve igual, solo que no corre nada raro.</small>
                </form>
            </div>
        </div>
    </div>

    {{-- Diseños que otros negocios hicieron públicos (como las plantillas de Canva) --}}
    @if (count($disenosPublicos))
    <div class="lp-box mb-3" id="disenos">
        <h3><span><i class="fa-solid fa-swatchbook me-1"></i>Diseños de otros negocios</span></h3>
        <p class="text-muted fw-semibold mb-3">Cartas que otros negocios hicieron con su IA y quisieron compartir. Aquí las ve con <b>sus</b> platos y precios: si le gusta una, tóquela y queda como su carta personalizada.</p>
        <div class="mc-dis">
            @foreach ($disenosPublicos as $d)
            <div class="mc-dis-c">
                <iframe sandbox title="Diseño de {{ $d->autor }}" src="{{ route('carta.disenos.ver', $d->id) }}" loading="lazy" tabindex="-1"></iframe>
                <div class="b">
                    <div class="fw-bold">Diseño de {{ $d->autor }}</div>
                    <small class="text-muted fw-semibold">{{ $d->ciudad ? $d->ciudad . ' · ' : '' }}{{ $d->usos === 1 ? 'Lo usa 1 negocio' : ($d->usos ? 'Lo usan ' . $d->usos . ' negocios' : 'Nuevo') }}</small>
                    <div class="acc">
                        <a class="btn btn-light border" target="_blank" rel="noopener" href="{{ route('carta.disenos.ver', $d->id) }}"><i class="fa-solid fa-eye me-1"></i>Ver</a>
                        <form method="post" action="{{ route('carta.disenos.usar', $d->id) }}" @if ($cartaIa) data-confirm="Este diseño reemplaza su carta personalizada actual. ¿Usarlo?" data-confirm-ok="Sí, usarlo" @endif>
                            @csrf
                            <button class="btn btn-success w-100"><i class="fa-solid fa-check me-1"></i>Usar este</button>
                        </form>
                    </div>
                    @if (auth()->user()->es_superadmin)
                    <form method="post" action="{{ route('carta.disenos.ocultar', $d->id) }}" data-confirm="¿Sacar este diseño de la galería?" data-confirm-ok="Sí, ocultar" data-peligro>
                        @csrf
                        <button class="btn btn-link btn-sm text-danger p-0 fw-bold">Ocultar de la galería (admin)</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-qrcode me-1"></i>Carta en línea (para el QR de las mesas)</span></h3>
        <p class="text-muted fw-semibold mb-2">Sus clientes la abren en el celular y le piden por WhatsApp: a domicilio, para recoger o en la mesa. El diseño se cambia en <a href="{{ route('negocio.edit') }}">Datos del negocio</a>.</p>
        <div class="d-flex gap-2 flex-wrap">
            <input class="form-control" style="flex:1 1 240px" value="{{ $tienda->url_carta }}" readonly onclick="this.select()">
            @if ($listo)<a class="btn btn-dark" target="_blank" rel="noopener" href="{{ $tienda->url_carta_navegador }}"><i class="fa-solid fa-utensils me-1"></i>Ver mi carta</a>@endif
            @if ($listo)<a class="btn btn-warning fw-bold" target="_blank" rel="noopener" href="{{ route('carta.qr') }}"><i class="fa-solid fa-qrcode me-1"></i>Imprimir QR para las mesas</a>@endif
            <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('🍽️ Mira nuestra carta y pide por WhatsApp: ' . $tienda->url_carta) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
    </div>

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-print me-1"></i>Carta para imprimir y para redes</span></h3>
        <p class="text-muted fw-semibold mb-3">Elija una plantilla (hay dos de Navidad 🎄, gratis para todos). Cada hoja trae un QR que lleva a su carta en línea. Si tiene muchos platos, sale en varias hojas.</p>
        <div class="mc-navidad-aviso">
            <span style="font-size:1.8rem">🎄</span>
            <div><b>¡Llegó la Navidad a su carta!</b><div class="small" style="opacity:.85">Use la <b>Carta navideña</b> o el <b>Menú navideño</b> para imprimir y para el estado de WhatsApp. Y si quiere, ponga también su carta en línea en modo Navidad.</div></div>
            @if ($listo)<a class="btn btn-light btn-sm fw-bold" target="_blank" rel="noopener" href="{{ $tienda->url_carta_navegador }}?tema=navidad">Ver mi carta en línea navideña</a>@endif
        </div>
        <div class="mc-grid">
            @foreach ($planes as $k => [$nom, $txt])
            <div class="mc-pl">
                <div class="mc-mock mc-{{ $k }}" aria-hidden="true">
                    @if ($k === 'moderna')
                        <div class="t">{{ \Illuminate\Support\Str::limit($tienda->nombre, 22) }}</div>
                        <div class="card"><b></b>Plato del día<em>$ 18.000</em></div>
                        <div class="card"><b></b>Especial de la casa<em>$ 25.000</em></div>
                        <div class="card"><b></b>Bebida natural<em>$ 6.000</em></div>
                    @elseif ($k === 'navidad' || $k === 'menu-navidad')
                        <div class="t">{{ $k === 'navidad' ? mb_strtoupper(\Illuminate\Support\Str::limit($tienda->nombre, 22)) : 'Menú Navideño' }}</div>
                        <div class="s">{{ $k === 'navidad' ? '✦ ¡Feliz Navidad! ✦' : mb_strtoupper(\Illuminate\Support\Str::limit($tienda->nombre, 22)) }}</div>
                        <div class="r"><span>{{ $k === 'navidad' ? 'Pavo navideño' : 'PAVO NAVIDEÑO' }}</span>@if ($k === 'navidad')<i></i>@endif<span>$ 38.000</span></div>
                        <div class="r"><span>{{ $k === 'navidad' ? 'Natilla y buñuelos' : 'NATILLA Y BUÑUELOS' }}</span>@if ($k === 'navidad')<i></i>@endif<span>$ 12.000</span></div>
                        <span class="mc-nav-tag">{{ $k === 'navidad' ? '🎄' : '🎁' }}</span>
                    @else
                        <div class="t">{{ $k === 'pro' ? 'MENÚ' : ($k === 'clasica' ? mb_strtoupper(\Illuminate\Support\Str::limit($tienda->nombre, 22)) : \Illuminate\Support\Str::limit($tienda->nombre, 22)) }}</div>
                        <div class="s">{{ $k === 'clasica' ? '✦ ✦ ✦' : ($k === 'pro' ? 'ENTRADAS' : '~ Nuestra carta ~') }}</div>
                        <div class="r"><span>{{ $k === 'pro' ? 'PLATO DEL DÍA' : 'Plato del día' }}</span>@if ($k !== 'pizarra')<i></i>@endif<span>$ 18.000</span></div>
                        <div class="r"><span>{{ $k === 'pro' ? 'ESPECIAL DE LA CASA' : 'Especial de la casa' }}</span>@if ($k !== 'pizarra')<i></i>@endif<span>$ 25.000</span></div>
                        <div class="r"><span>{{ $k === 'pro' ? 'BEBIDA NATURAL' : 'Bebida natural' }}</span>@if ($k !== 'pizarra')<i></i>@endif<span>$ 6.000</span></div>
                    @endif
                </div>
                <div class="mc-b">
                    <b>{{ $nom }}</b><small>{{ $txt }}</small>
                    @if ($listo && $base)
                    <div class="mc-acc">
                        <a class="btn btn-light" href="{{ route('carta.imprimir', ['plantilla' => $k]) }}"><i class="fa-solid fa-eye"></i> Ver</a>
                        <a class="btn btn-danger" href="{{ route('carta.imprimir', ['plantilla' => $k, 'accion' => 'pdf']) }}"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                        <a class="btn btn-success" href="{{ route('carta.imprimir', ['plantilla' => $k, 'accion' => 'jpg']) }}"><i class="fa-solid fa-image"></i> JPG</a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        <small class="text-muted d-block mt-3">Consejo: el PDF sirve para imprimir en papel; el JPG para el estado de WhatsApp, Instagram o Facebook. Cuando cambie un precio, vuelva a descargarla: siempre sale con sus precios de hoy.</small>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var texto = document.getElementById('mcIaTexto');
    function copiar(cb) {
        var ok = function () { if (cb) cb(); };
        if (navigator.clipboard) navigator.clipboard.writeText(texto.value).then(ok, function () { texto.select(); document.execCommand('copy'); ok(); });
        else { texto.select(); document.execCommand('copy'); ok(); }
    }
    document.getElementById('mcIaCopiar').addEventListener('click', function () {
        var b = this; copiar(function () { b.innerHTML = '<i class="fa-solid fa-check me-1"></i>¡Copiado!'; });
    });
    // Por si la IA no recibe el texto en el enlace, también queda copiado para pegarlo
    document.querySelectorAll('.mc-ia-abrir, .mc-ia-copiar-abrir').forEach(function (a) {
        a.addEventListener('click', function () { copiar(); });
    });
    var pegar = document.getElementById('mcIaPegar');
    pegar.addEventListener('click', function () {
        var area = document.getElementById('mcIaHtml');
        if (!navigator.clipboard || !navigator.clipboard.readText) { area.focus(); pegar.textContent = 'Mantenga presionado y toque Pegar'; return; }
        navigator.clipboard.readText().then(function (t) {
            area.value = t; pegar.innerHTML = '<i class="fa-solid fa-check me-1"></i>Pegado';
        }, function () { area.focus(); pegar.textContent = 'Mantenga presionado y toque Pegar'; });
    });
})();
</script>
@endpush
