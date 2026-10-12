<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Certificado {{ $a->certificado }} · LocalPro</title>
    @php
        $fecha = $a->curso_aprobado_at;
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fechaTexto = $fecha->day . ' de ' . $meses[$fecha->month - 1] . ' de ' . $fecha->year;
        $verificar = route('certificado.verificar', $a->certificado);
        $linkedin = 'https://www.linkedin.com/profile/add?' . http_build_query([
            'startTask' => 'CERTIFICATION_NAME', 'name' => 'Asesor Comercial LocalPro', 'organizationName' => 'LocalPro',
            'issueYear' => $fecha->year, 'issueMonth' => $fecha->month, 'certUrl' => $verificar, 'certId' => $a->certificado,
        ]);
    @endphp
    <style>
        .w{max-width:1000px}
        .cert-wrap{overflow:hidden}
        .cert{position:relative;background:#fff;border-radius:22px;aspect-ratio:1.414/1;width:100%;padding:4.2% 5.5%;display:flex;flex-direction:column;justify-content:space-between;border:1.5px solid var(--line);overflow:hidden;container-type:inline-size}
        .cert::before{content:"";position:absolute;inset:2.2%;border:2px solid #16b34a;border-radius:16px;pointer-events:none}
        .cert::after{content:"";position:absolute;right:-12%;bottom:-30%;width:45%;aspect-ratio:1;border-radius:50%;background:var(--g);opacity:.18}
        .cert .franja{position:absolute;left:0;top:0;bottom:0;width:2.2%;background:var(--g)}
        .c-top{display:flex;justify-content:space-between;align-items:center;position:relative;z-index:1}
        .c-brand{display:flex;align-items:center;gap:.6em;font-weight:800;font-size:2.6cqi}
        .c-brand b{width:2em;height:2em;border-radius:.6em;background:var(--k);color:var(--lime);display:grid;place-items:center}
        .c-cod{font-weight:800;color:var(--mut);font-size:1.5cqi;letter-spacing:.08em}
        .c-mid{text-align:center;position:relative;z-index:1}
        .c-mid small{display:block;font-weight:800;letter-spacing:.3em;text-transform:uppercase;color:#16b34a;font-size:1.6cqi}
        .c-mid h1{font-size:5.4cqi;margin:.2em 0 .15em;letter-spacing:-.03em;line-height:1}
        .c-mid .otorga{color:var(--mut);font-weight:600;font-size:1.8cqi;margin:0}
        .c-mid .nombre{font-size:4.6cqi;font-weight:800;margin:.35em 0 .1em;letter-spacing:-.02em}
        .c-mid .cc{color:var(--mut);font-weight:700;font-size:1.6cqi}
        .c-mid .linea{width:42%;height:2px;background:var(--g);margin:.6em auto}
        .c-mid .texto{max-width:78%;margin:0 auto;font-size:1.75cqi;font-weight:500}
        .c-bot{display:flex;justify-content:space-between;align-items:flex-end;position:relative;z-index:1;gap:3%}
        .firma{text-align:center;font-size:1.5cqi;font-weight:700;min-width:26%}
        .firma .raya{border-top:2px solid var(--k);padding-top:.4em;margin-top:2.4em}
        .firma span{display:block;color:var(--mut);font-weight:600}
        .sello{width:11cqi;height:11cqi;border-radius:50%;background:var(--k);color:var(--lime);display:grid;place-items:center;text-align:center;font-weight:800;font-size:1.25cqi;line-height:1.15;box-shadow:0 0 0 .5cqi #fff,0 0 0 .8cqi #16b34a}
        .sello i{font-size:3cqi;display:block;margin-bottom:.15em}
        .qr{text-align:center;font-size:1.1cqi;color:var(--mut);font-weight:700}
        .qr #qr{width:9cqi;height:9cqi;margin:0 auto .3em}
        .qr #qr img,.qr #qr canvas{width:100%!important;height:100%!important}
        .acciones{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:16px}
        .valido{display:flex;gap:12px;align-items:center;background:#e8f8ec;color:#11702f;border-radius:18px;padding:14px 16px;font-weight:700;margin-bottom:14px}
        @page{size:A4 landscape;margin:0}
        @media print{
            body{background:#fff}
            .top,.no-print{display:none!important}
            .w{max-width:none;padding:0}
            .cert{border:0;border-radius:0;width:297mm;height:210mm;aspect-ratio:auto}
            .cert,.cert *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
        }
    </style>
</head>
<body>
<div class="w">
    @if ($propio) @include('curso._top') @else
    <div class="top"><a href="{{ url('/') }}" class="brand"><b>L</b>LocalPro</a></div>
    @endif

    @if ($felicitar)
    <div class="hero no-print">
        <small>¡Aprobaste!</small>
        <h1>Felicitaciones, <em>{{ strtok($a->nombre, ' ') }}</em> 🎉</h1>
        <p>Ya eres Asesor Comercial LocalPro. Descarga tu certificado para tu hoja de vida y entra a tu panel: ahí está tu código y tu enlace para inscribir negocios.</p>
    </div>
    @endif

    @unless ($propio)
    <div class="valido no-print"><i class="fa-solid fa-circle-check fa-lg"></i> Certificado válido, emitido por LocalPro el {{ $fechaTexto }}.</div>
    @endunless

    <div class="cert-wrap">
        <div class="cert" id="cert">
            <div class="franja"></div>
            <div class="c-top">
                <div class="c-brand"><b>L</b>LocalPro</div>
                <div class="c-cod">N.º {{ $a->certificado }}</div>
            </div>
            <div class="c-mid">
                <small>Certificado</small>
                <h1>Asesor Comercial</h1>
                <p class="otorga">LocalPro certifica que</p>
                <div class="nombre">{{ $a->nombre }}</div>
                @if ($a->documento)<div class="cc">C.C. {{ $a->documento }}</div>@endif
                <div class="linea"></div>
                <p class="texto">aprobó el <b>Curso Básico de Asesores Comerciales LocalPro</b>, en el que conoció el sistema de ventas, inventario y caja para negocios, el proceso de inscripción de clientes y el modelo comercial de la empresa.</p>
            </div>
            <div class="c-bot">
                <div class="firma"><div class="raya">LocalPro</div><span>{{ $a->ciudad ? $a->ciudad . ', ' : '' }}{{ $fechaTexto }}</span></div>
                <div class="sello"><div><i class="fa-solid fa-award"></i>ASESOR<br>CERTIFICADO</div></div>
                <div class="qr"><div id="qr"></div>Verifíquelo en<br>localproo.com/certificado</div>
            </div>
        </div>
    </div>

    @if ($propio)
    <div class="acciones no-print">
        <button class="btn btn-g" onclick="window.print()"><i class="fa-solid fa-file-arrow-down"></i> Descargar en PDF</button>
        <a class="btn btn-l" target="_blank" href="{{ $linkedin }}"><i class="fa-brands fa-linkedin"></i> Agregar a LinkedIn</a>
        <a class="btn btn-w" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Me certifiqué como Asesor Comercial LocalPro! 🎉 ' . $verificar) }}"><i class="fa-brands fa-whatsapp"></i> Compartir</a>
        <a class="btn btn-k" href="{{ $panel }}"><i class="fa-solid fa-gauge"></i> Ir a mi panel</a>
    </div>
    <p class="no-print" style="text-align:center;color:var(--mut);font-weight:600;font-size:.85rem;margin-top:12px">Para el PDF: en la ventana de impresión elija "Guardar como PDF". Cualquiera puede verificar su certificado con el código <b>{{ $a->certificado }}</b>.</p>
    @endif
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    try { new QRCode(document.getElementById('qr'), { text: @json($verificar), width: 256, height: 256, colorDark: '#0b0d0c', colorLight: '#ffffff' }); } catch (e) {}
</script>
</body>
</html>
