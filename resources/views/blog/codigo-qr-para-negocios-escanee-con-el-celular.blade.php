@extends('blog._base')
@section('titulo', $a['titulo'])
@section('descripcion', $a['resumen'])
@section('volver')@endsection
@section('cuerpo')
@php
    // QR de prueba con enlaces reales: la factura de ejemplo, la tienda de muestra (Admin → Planes y cobros) y el registro.
    $qrs = [];
    $qrs[] = ['fa-receipt', 'La factura que recibe el cliente', 'Así le llega la compra por WhatsApp (datos inventados).', route('ejemplo.factura')];
    try {
        $codigos = collect(explode(',', (string) \App\Models\Ajuste::valor(\App\Http\Controllers\asesorEjemplosController::AJUSTE, 'LCT-00003')))
            ->map(fn ($c) => strtoupper(trim($c)))->filter()->unique()->values();
        $muestras = \App\Models\Tienda::whereIn('codigo', $codigos)->get()->sortBy(fn ($t) => $codigos->search($t->codigo));
        $conTienda = $muestras->first(fn ($t) => $t->slug && $t->onboarding_completo && $t->catalogo_activo);
        $conEnlaces = $muestras->first(fn ($t) => $t->slug && $t->onboarding_completo && $t->enlaces_activo);
        if ($conTienda) $qrs[] = ['fa-store', 'Tienda en línea de ' . $conTienda->nombre, 'Un negocio real hecho con LocalProo: productos con foto y botón Comprar por WhatsApp.', $conTienda->url_catalogo_navegador];
        if ($conEnlaces) $qrs[] = ['fa-link', 'Página de enlaces de ' . $conEnlaces->nombre, 'WhatsApp, redes, ubicación y tienda en una sola página.', route('enlaces.publico', $conEnlaces->slug)];
    } catch (\Throwable $e) {
        // Si no hay tiendas de muestra, el artículo sigue con los demás QR
    }
    $qrs[] = ['fa-rocket', 'Cree su cuenta gratis', 'Escanee y empiece a probar LocalProo en su negocio.', route('registro', array_filter(['tipo' => config('blog_roles.negocios.tipo'), 'desde' => 'blog:' . $slug . ':qr']))];
@endphp
<style>
.qr-prueba{background:#0b0d0c;color:#fff;border-radius:24px;padding:22px 20px;margin:26px 0}
.qr-prueba h2{color:#a6f34a;margin:0 0 6px}
.qr-prueba > p{color:#c9d6cd;margin:0 0 16px;font-size:.98rem}
.qr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
.qr-card{background:#fff;color:#151a17;border-radius:18px;padding:16px 14px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:6px}
.qr-card .qr-img{width:170px;height:170px;padding:8px;background:#fff;border:2px solid #e3efe5;border-radius:14px;display:grid;place-items:center}
.qr-card .qr-img img,.qr-card .qr-img canvas{width:100%!important;height:100%!important;image-rendering:pixelated}
.qr-card b{font-size:1rem;line-height:1.3;margin-top:4px}
.qr-card span{color:#5b665e;font-size:.86rem;line-height:1.4;flex:1}
.qr-card a.abrir{display:inline-block;background:#11702f;color:#fff;font-weight:800;border-radius:30px;padding:6px 16px;text-decoration:none;font-size:.88rem;margin-top:4px}
.qr-card .ic{color:#16b34a}
.qr-movil{display:none;background:#1c2420;border-radius:14px;padding:10px 12px;color:#e6efe8;font-size:.9rem;margin-bottom:14px}
@media (max-width:760px){.qr-movil{display:block}}
.pasos{counter-reset:p;list-style:none;padding:0}
.pasos li{counter-increment:p;position:relative;padding-left:44px;margin-bottom:12px}
.pasos li::before{content:counter(p);position:absolute;left:0;top:0;width:30px;height:30px;border-radius:50%;background:#a6f34a;color:#0b0d0c;font-weight:900;display:grid;place-items:center}
</style>

<h1>{{ $a['titulo'] }}</h1>
<div class="f">{{ $a['fecha'] }} · {{ $a['minutos'] }} min de lectura</div>

<p>El código QR es ese cuadrito de puntos que hoy está en todas partes: en el menú del restaurante, en la vitrina, en las facturas. El cliente <b>apunta la cámara de su celular</b>, le aparece un enlace y con un toque llega a donde usted quiere: su tienda, su WhatsApp o su factura. Sin escribir direcciones y sin descargar nada.</p>
<p>En LocalProo usted no tiene que buscar páginas para hacer QR: el sistema <b>ya se los da listos</b> para descargar e imprimir.</p>

<div class="qr-prueba">
    <h2>📱 Pruébelo ahora: escanee con la cámara de su celular</h2>
    <p>Abra la cámara, apunte a cualquiera de estos códigos y toque el enlace que aparece. Son ejemplos reales hechos con LocalProo.</p>
    <div class="qr-movil">¿Está leyendo desde el celular? Toque <b>Abrir</b> debajo de cada código, o abra esta página en un computador y escanéela con el celular.</div>
    <div class="qr-grid">
        @foreach ($qrs as $i => $q)
        <div class="qr-card">
            <div class="qr-img" id="qr-ej-{{ $i }}" data-url="{{ $q[3] }}" role="img" aria-label="Código QR: {{ $q[1] }}"></div>
            <b><i class="fa-solid {{ $q[0] }} ic me-1"></i>{{ $q[1] }}</b>
            <span>{{ $q[2] }}</span>
            <a class="abrir" href="{{ $q[3] }}" target="_blank" rel="noopener">Abrir</a>
        </div>
        @endforeach
    </div>
</div>

<h2>¿Cómo se escanea un código QR?</h2>
<ol class="pasos">
    <li><b>Abra la cámara</b> del celular, como si fuera a tomar una foto.</li>
    <li><b>Apunte al código</b> y espere un segundo, sin tomar la foto.</li>
    <li><b>Toque el enlace</b> que aparece en la pantalla. ¡Listo!</li>
</ol>
<p>Funciona en iPhone y en casi todos los Android. Si su celular no lo reconoce, use <b>Google Lens</b> o el ícono de cámara de la barra de búsqueda de Google.</p>

<h2>Los QR que ya le da LocalProo</h2>
<ul>
    <li><b>QR de su tienda en línea:</b> en <b>Datos del negocio</b> está el código de su tienda, con sus productos por categoría y el botón Comprar por WhatsApp. Si cambia precios o fotos, el mismo QR impreso sigue sirviendo.</li>
    <li><b>QR de Mis enlaces:</b> su página tipo Linktree (WhatsApp, Instagram, Facebook, ubicación y tienda) tiene botón <b>QR</b> para descargarlo e imprimirlo en el mostrador, el empaque o el menú.</li>
    <li><b>QR en la factura electrónica:</b> cuando su negocio factura electrónicamente, cada factura y POS electrónico lleva el código CUFE con su QR para que el cliente la consulte en la DIAN.</li>
    <li><b>QR en el certificado de los asesores:</b> los asesores comerciales de LocalProo reciben un certificado con QR para que cualquiera compruebe que es auténtico.</li>
</ul>

<div class="box">
    <b>Idea que vende:</b> ponga el QR de su tienda en la vitrina con el letrero <i>"Escanee y vea todo nuestro catálogo"</i>. Aunque el local esté cerrado, el cliente mira los productos y le escribe por WhatsApp.
</div>

<h2>Dónde ponerlo en su negocio</h2>
<ul>
    <li><b>Mostrador y caja:</b> para que sigan sus redes o guarden su WhatsApp mientras pagan.</li>
    <li><b>Bolsas, empaques y etiquetas:</b> el cliente vuelve a comprar desde la casa.</li>
    <li><b>Vitrina y puerta:</b> catálogo abierto las 24 horas.</li>
    <li><b>Mesas y menú:</b> en restaurantes y cafés, la carta en el celular.</li>
    <li><b>Tarjetas, volantes y afiches:</b> en vez de llenarlos de letras, un QR que lleva a todo.</li>
    <li><b>Ferias y eventos:</b> un pendón con el QR y la gente le compra después.</li>
</ul>

<h2>Consejos para que el QR siempre funcione</h2>
<ul>
    <li><b>Tamaño:</b> mínimo unos 3 × 3 cm; si se mira de lejos (vitrina, afiche), 10 cm o más.</li>
    <li><b>Contraste:</b> código oscuro sobre fondo claro. No lo imprima en colores pálidos ni sobre fotos.</li>
    <li><b>Margen blanco:</b> deje un borde libre alrededor; no lo pegue a otros dibujos.</li>
    <li><b>No lo deforme:</b> siempre cuadrado, sin estirarlo.</li>
    <li><b>Pruébelo antes de imprimir mil:</b> escanéelo con dos celulares distintos.</li>
    <li><b>Dígale al cliente qué gana:</b> "Escanee y vea el catálogo" funciona mejor que un QR solo.</li>
</ul>

<h2>¿Y el código de barras?</h2>
<p>Son dos cosas distintas y LocalProo maneja las dos. El <b>código de barras</b> es para sus productos: imprime las etiquetas, vende escaneando en la caja (con pistola lectora o la cámara del celular) y hace el conteo de inventario. El <b>QR</b> es para sus clientes: los lleva a su tienda, a sus redes o a su factura.</p>

<h2>En resumen</h2>
<ul>
    <li>El cliente escanea con la cámara del celular, sin descargar nada.</li>
    <li>LocalProo le da el QR de su tienda en línea y de su página de enlaces, listos para imprimir.</li>
    <li>La factura electrónica sale con su QR de la DIAN.</li>
    <li>Incluido en LocalProo, sin costo adicional.</li>
</ul>

<a class="cta" href="{{ route('registro', array_filter(['tipo' => config('blog_roles.negocios.tipo'), 'desde' => 'blog:' . $slug])) }}">Cree su cuenta gratis y descargue su QR</a>
<p style="text-align:center;color:#6b7570">¿Ya tiene LocalProo? Su QR está en <b>Datos del negocio</b> (tienda en línea) y en <b>Mis enlaces → QR</b>.</p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    if (!window.QRCode) return;
    document.querySelectorAll('.qr-img[data-url]').forEach(function (el) {
        try { new QRCode(el, { text: el.getAttribute('data-url'), width: 300, height: 300, colorDark: '#0b0d0c', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M }); } catch (e) {}
    });
})();
</script>
@endsection
