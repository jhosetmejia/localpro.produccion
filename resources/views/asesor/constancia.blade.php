<!DOCTYPE html>
<html lang="es">
<head>
    @include('curso._head')
    <title>Constancia {{ $codigo }} · LocalPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
    @php
        $hoy = now();
        $doc = (string) $a->documento;
        if ($publico && strlen($doc) > 4) $doc = str_repeat('•', strlen($doc) - 4) . substr($doc, -4);
        $esLider = ($a->nivel ?? 0) > 1 || $equipo > 0;
    @endphp
    <style>
        .w{max-width:860px}
        .hoja{background:#fff;border:1.5px solid var(--line);border-radius:18px;padding:56px 60px;position:relative;overflow:hidden;font-size:15px;line-height:1.65}
        .hoja .franja{position:absolute;left:0;top:0;right:0;height:8px;background:var(--g)}
        .h-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:34px}
        .h-brand{display:flex;align-items:center;gap:.6em;font-weight:800;font-size:1.3rem}
        .h-brand b{width:2em;height:2em;border-radius:.6em;background:var(--k);color:var(--lime);display:grid;place-items:center}
        .h-cod{font-weight:800;color:var(--mut);font-size:.8rem;letter-spacing:.08em;text-align:right}
        .titulo{text-align:center;margin-bottom:26px}
        .titulo h1{font-size:1.35rem;letter-spacing:.06em;text-transform:uppercase;margin:0}
        .titulo p{margin:.3em 0 0;color:var(--mut);font-weight:700;letter-spacing:.2em;text-transform:uppercase;font-size:.78rem}
        .hoja p{margin:0 0 14px;text-align:justify}
        .hoja ul{margin:0 0 14px;padding-left:22px}
        .hoja li{margin-bottom:3px}
        .hoja h3{font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin:18px 0 6px;color:#16b34a}
        .nota{border:1.5px solid #e3efe5;background:#f6f8f7;border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.5;color:#3b453f;margin-top:18px;text-align:justify}
        .nota b{display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.06em;font-size:11px}
        .pie{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-top:30px}
        .firma{min-width:240px}
        .firma .autografo span{font-family:'Great Vibes',cursive;font-size:2.4rem;color:#0f2a1a;display:inline-block;transform:rotate(-4deg);line-height:1}
        .firma .raya{border-top:2px solid var(--k);padding-top:.35em;font-weight:800}
        .firma small{display:block;color:var(--mut);font-weight:600}
        .qr{text-align:center;font-size:.7rem;color:var(--mut);font-weight:700}
        .qr #qr{width:110px;height:110px;margin:0 auto 4px}
        .qr #qr img,.qr #qr canvas{width:100%!important;height:100%!important}
        .acciones{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:16px}
        .valido{display:flex;gap:12px;align-items:center;background:#e8f8ec;color:#11702f;border-radius:18px;padding:14px 16px;font-weight:700;margin-bottom:14px}
        @media (max-width:640px){.hoja{padding:34px 22px;font-size:14px}.pie{flex-direction:column;align-items:center;text-align:center}}
        @page{size:A4 portrait;margin:0}
        @media print{
            body{background:#fff}
            .top,.no-print{display:none!important}
            .w{max-width:none;padding:0}
            .hoja{border:0;border-radius:0;width:210mm;min-height:297mm;padding:20mm 20mm 16mm;font-size:12.5pt}
            .hoja,.hoja *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
        }
    </style>
</head>
<body>
<div class="w">
    <div class="top">
        <a href="{{ $publico ? url('/') : route('asesor.certificados') }}" class="brand"><b>L</b>LocalPro{{ $publico ? '' : ' · Certificados' }}</a>
        @unless ($publico)<a href="{{ route('asesor.certificados') }}" class="salir"><i class="fa-solid fa-arrow-left"></i> Volver</a>@endunless
    </div>

    @if ($publico)
    <div class="valido no-print"><i class="fa-solid fa-circle-check fa-lg"></i> Constancia válida: {{ $a->nombre }} es asesor(a) activo(a) de LocalPro. Información al {{ \App\Support\CertificadoAsesor::fechaTexto($hoy) }}.</div>
    @endif

    <div class="hoja">
        <div class="franja"></div>
        <div class="h-top">
            <div class="h-brand"><b>L</b>LocalPro</div>
            <div class="h-cod">N.º {{ $codigo }}<br>localproo.com</div>
        </div>

        <div class="titulo">
            <h1>Constancia de experiencia</h1>
            <p>Asesor Comercial Independiente</p>
        </div>

        <p>LocalPro, plataforma de software para la gestión de negocios (localproo.com), hace constar que <b>{{ $a->nombre }}</b>, identificado(a) con cédula de ciudadanía <b>N.º {{ $doc }}</b>, hace parte de su red comercial como <b>Asesor(a) Comercial Independiente{{ $a->cargo_nombre ? ' · ' . $a->cargo_nombre : '' }}</b> desde el <b>{{ \App\Support\CertificadoAsesor::fechaTexto($desde) }}</b> hasta la fecha{{ $a->ciudad ? ', en ' . $a->ciudad : '' }}, bajo una vinculación comercial independiente remunerada exclusivamente por comisiones sobre ventas efectivas.</p>

        <h3>Actividades que desarrolla</h3>
        <ul>
            <li>Búsqueda y contacto de negocios interesados en el software (prospección comercial).</li>
            <li>Presentación y demostración del sistema LocalPro: caja (POS), inventario, reportes y tienda en línea.</li>
            <li>Inscripción de clientes, acompañamiento en sus primeros pasos y seguimiento hasta la compra del plan.</li>
            <li>Atención de objeciones, cierre de ventas y gestión de renovaciones.</li>
            @if ($esLider)<li>Vinculación, formación y acompañamiento de un equipo de asesores comerciales.</li>@endif
        </ul>

        @if ($negocios > 0 || $equipo > 0)
        <h3>Resultados</h3>
        <ul>
            @if ($negocios > 0)<li>{{ $negocios }} negocio{{ $negocios === 1 ? '' : 's' }} inscrito{{ $negocios === 1 ? '' : 's' }} en LocalPro con su código de asesor.</li>@endif
            @if ($equipo > 0)<li>{{ $equipo }} asesor{{ $equipo === 1 ? '' : 'es' }} activo{{ $equipo === 1 ? '' : 's' }} en su equipo comercial.</li>@endif
        </ul>
        @endif

        <h3>Formación aprobada en LocalPro</h3>
        <ul>
            @if ($a->curso_aprobado_at)<li>Curso Básico de Asesores Comerciales LocalPro ({{ $a->curso_aprobado_at->format('d/m/Y') }}{{ $a->certificado ? ' · certificado ' . $a->certificado : '' }}).</li>@endif
            @foreach ($cursos as $c)
            <li>{{ $c['titulo'] }} ({{ $c['fecha']->format('d/m/Y') }}).</li>
            @endforeach
        </ul>

        <p>Se expide a solicitud del interesado{{ $a->ciudad ? ', en ' . $a->ciudad : '' }}, el {{ \App\Support\CertificadoAsesor::fechaTexto($hoy) }}.</p>

        <div class="pie">
            <div class="firma">
                <div class="autografo"><span>Jhoset Mejía</span></div>
                <div class="raya">Jhoset Mejía</div>
                <small>Director General · LocalPro</small>
            </div>
            <div class="qr"><div id="qr"></div>Escanee para verificar<br>N.º {{ $codigo }}</div>
        </div>

        <div class="nota"><b>Nota importante</b>{{ \App\Support\CertificadoAsesor::NOTA_LEGAL }}</div>
    </div>

    @unless ($publico)
    <div class="acciones no-print">
        <button class="btn btn-g" onclick="window.print()"><i class="fa-solid fa-file-arrow-down"></i> Descargar en PDF</button>
        <a class="btn btn-l" href="{{ route('asesor.certificados') }}"><i class="fa-solid fa-arrow-left"></i> Volver a certificados</a>
    </div>
    <p class="no-print" style="text-align:center;color:var(--mut);font-weight:600;font-size:.85rem;margin-top:12px">Para el PDF: en la ventana de impresión elija "Guardar como PDF". La empresa que la reciba puede escanear el QR para confirmar que es real.</p>
    @endunless
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    try { new QRCode(document.getElementById('qr'), { text: {!! json_encode($verificar) !!}, width: 256, height: 256, colorDark: '#0b0d0c', colorLight: '#ffffff' }); } catch (e) {}
</script>
</body>
</html>
