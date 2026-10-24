@extends('eventos.publico._layout')

@section('titulo', 'Mi entrada · ' . $evento->nombre)
@section('meta')<meta name="robots" content="noindex, nofollow">@endsection

@section('css')
.tk{background:#fff;color:#111;border-radius:26px;overflow:hidden;margin:20px 0 14px;box-shadow:0 30px 60px -30px var(--c)}
.tk .cab{background:linear-gradient(135deg,var(--c),#0b0a10);color:#fff;padding:20px 22px}
.tk .cab small{opacity:.85;letter-spacing:.14em;text-transform:uppercase;font-weight:800;font-size:.72rem}
.tk .cab h1{font-size:1.45rem;line-height:1.15;margin-top:4px}
.tk .cuerpo{padding:20px 22px;text-align:center}
.tk #qr{display:inline-block;padding:4px;background:#fff;border-radius:16px;border:2px solid #eee}
.tk #qr img,.tk #qr canvas{display:block;width:240px;height:240px}
.tk .cod{font-family:ui-monospace,monospace;font-weight:800;letter-spacing:.08em;margin-top:8px;font-size:1.05rem}
.tk .corte{border-top:2px dashed #ddd;margin:0 22px;position:relative}
.tk .filas{padding:16px 22px 22px;text-align:left;display:grid;gap:.55rem}
.tk .filas div{display:flex;justify-content:space-between;gap:1rem;font-size:.92rem}
.tk .filas span{color:#666}.tk .filas b{text-align:right}
.usada{background:#fee2e2;color:#991b1b;font-weight:800;border-radius:12px;padding:.6rem;margin-top:12px}
@endsection

@section('content')
@if ($entrada->estado !== 'aprobada')
<div class="card" style="margin-top:20px">
    <span class="badge {{ $entrada->estado === 'pendiente' ? 'b-wait' : 'b-bad' }}">{{ $entrada->estado_texto }}</span>
    <h2 style="margin-top:.7rem">Tu entrada aún no está lista</h2>
    <p class="mu">{{ $entrada->estado === 'pendiente' ? 'Cuando el organizador apruebe tu pago, aquí verás tu QR.' : 'Esta entrada no es válida. Escríbele al organizador si crees que es un error.' }}</p>
    @if (in_array($entrada->estado, ['pendiente', 'rechazada'], true))<a class="btn btn-l" style="margin-top:14px" href="{{ route('evento.pago', $evento->slug) }}">Ver cómo pagar</a>@endif
</div>
@else
<div class="tk" id="tk">
    <div class="cab">
        <small>{{ $tienda?->nombre }}</small>
        <h1>{{ $evento->nombre }}</h1>
        @if ($evento->subtitulo)<div style="opacity:.85">{{ $evento->subtitulo }}</div>@endif
    </div>
    <div class="cuerpo">
        <div id="qr" aria-label="Código QR de la entrada"></div>
        <div class="cod">{{ $entrada->codigo }}</div>
        @if ($entrada->ingreso_at)<div class="usada"><i class="fa-solid fa-circle-check"></i> Ya ingresaste el {{ $entrada->ingreso_at->locale('es')->isoFormat('D [de] MMMM [a las] h:mm a') }}</div>@endif
    </div>
    <div class="corte"></div>
    <div class="filas">
        <div><span>Nombre</span><b>{{ $entrada->nombre_titular }}</b></div>
        <div><span>Documento</span><b>{{ $entrada->documento_titular }}</b></div>
        @if ($evento->fecha_evento)<div><span>Fecha</span><b>{{ ucfirst($evento->fecha_evento->locale('es')->isoFormat('ddd D [de] MMMM · h:mm a')) }}</b></div>@endif
        @if ($evento->lugar)<div><span>Lugar</span><b>{{ $evento->lugar }}</b></div>@endif
        <div><span>Entrada</span><b>{{ $entrada->etapa ?: 'General' }}</b></div>
    </div>
</div>

<div class="aviso"><i class="fa-solid fa-triangle-exclamation"></i> <b>No compartas tu QR:</b> es único e intransferible. Solo sirve para entrar una vez y en la puerta revisan tu documento.</div>

<div class="card">
    <p><i class="fa-solid fa-door-open" style="color:var(--c)"></i> <b>Presenta esta entrada en la entrada del evento.</b></p>
    <p class="mu" style="margin-top:.4rem">Te recomendamos descargarla en PDF para que no tengas problemas de internet.</p>
    <button class="btn btn-c" id="pdf" style="margin-top:14px"><i class="fa-solid fa-file-pdf"></i> Descargar en PDF</button>
</div>
@endif
<a class="btn btn-l" href="{{ route('mis-entradas.index') }}"><i class="fa-solid fa-arrow-left"></i> Mis entradas</a>
@endsection

@section('js')
@if ($entrada->estado === 'aprobada')
@php $pdf = [
        'codigo' => $entrada->codigo, 'evento' => $evento->nombre, 'sub' => $evento->subtitulo, 'org' => $tienda?->nombre,
        'nombre' => $entrada->nombre_titular, 'doc' => $entrada->documento_titular,
        'fecha' => $evento->fecha_evento ? ucfirst($evento->fecha_evento->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY · h:mm a')) : '',
        'lugar' => trim(($evento->lugar ?? '') . ($evento->ciudad ? ', ' . $evento->ciudad : ''), ', '),
        'etapa' => $entrada->etapa ?: 'General', 'color' => $evento->color ?: '#a855f7',
        'archivo' => 'entrada-' . $evento->slug . '.pdf',
    ]; @endphp
{{-- El QR se dibuja con una librería guardada en el sitio (no depende de otro servidor); el PDF se carga al tocar el botón --}}
<script src="{{ asset('js/qrcode-generator.js') }}?v=1"></script>
<script>
(function () {
    var D = @json($pdf);
    var caja = document.getElementById('qr'), lienzo = null;
    // Dibuja el QR en un canvas (nítido en pantalla y para el PDF)
    try {
        var qr = qrcode(0, 'H'); qr.addData(D.codigo); qr.make();
        var n = qr.getModuleCount(), borde = 4, celda = 12, lado = (n + borde * 2) * celda;
        lienzo = document.createElement('canvas'); lienzo.width = lado; lienzo.height = lado;
        var cx = lienzo.getContext('2d'); cx.fillStyle = '#fff'; cx.fillRect(0, 0, lado, lado); cx.fillStyle = '#000';
        for (var f = 0; f < n; f++) for (var c = 0; c < n; c++) if (qr.isDark(f, c)) cx.fillRect((c + borde) * celda, (f + borde) * celda, celda, celda);
        caja.appendChild(lienzo);
    } catch (e) { caja.textContent = D.codigo; }

    function qrImagen() { return lienzo ? lienzo.toDataURL('image/png') : null; }
    function rgb(h) { h = h.replace('#', ''); return [parseInt(h.substr(0, 2), 16), parseInt(h.substr(2, 2), 16), parseInt(h.substr(4, 2), 16)]; }

    var LIB_PDF = ['https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js'];
    function cargarPdf() {
        return new Promise(function (ok, mal) {
            if (window.jspdf) return ok(); var i = 0;
            (function intentar() { if (i >= LIB_PDF.length) return mal(); var s = document.createElement('script'); s.src = LIB_PDF[i++]; s.onload = ok; s.onerror = intentar; document.head.appendChild(s); })();
        });
    }
    document.getElementById('pdf').addEventListener('click', function () {
        var b = this, txt = b.innerHTML;
        b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparando PDF…';
        cargarPdf().then(function () { armarPdf(); b.disabled = false; b.innerHTML = txt; })
            .catch(function () { b.disabled = false; b.innerHTML = 'Revise su internet e intente de nuevo'; });
    });
    function armarPdf() {
        var doc = new window.jspdf.jsPDF({ unit: 'mm', format: 'a5' }), W = 148, c = rgb(D.color);
        doc.setFillColor(c[0], c[1], c[2]); doc.rect(0, 0, W, 34, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold'); doc.setFontSize(9); doc.text((D.org || '').toUpperCase(), 12, 11);
        doc.setFontSize(18); doc.text(doc.splitTextToSize(D.evento, W - 24), 12, 20);
        if (D.sub) { doc.setFont('helvetica', 'normal'); doc.setFontSize(10); doc.text(D.sub, 12, 30); }
        var img = qrImagen();
        if (img) doc.addImage(img, 'PNG', (W - 80) / 2, 42, 80, 80);
        doc.setTextColor(20, 20, 20); doc.setFont('courier', 'bold'); doc.setFontSize(14);
        doc.text(D.codigo, W / 2, 130, { align: 'center' });
        var y = 142;
        [['Nombre', D.nombre], ['Documento', D.doc], ['Fecha', D.fecha], ['Lugar', D.lugar], ['Entrada', D.etapa]].forEach(function (f) {
            if (!f[1]) return;
            doc.setFont('helvetica', 'normal'); doc.setFontSize(9); doc.setTextColor(110, 110, 110); doc.text(f[0], 12, y);
            doc.setFont('helvetica', 'bold'); doc.setFontSize(11); doc.setTextColor(20, 20, 20);
            var l = doc.splitTextToSize(String(f[1]), W - 52); doc.text(l, 40, y); y += 7 * l.length;
        });
        doc.setFillColor(255, 245, 214); doc.roundedRect(10, y + 2, W - 20, 18, 3, 3, 'F');
        doc.setTextColor(122, 82, 0); doc.setFont('helvetica', 'bold'); doc.setFontSize(9.5);
        doc.text('No compartas tu QR: es único e intransferible.', W / 2, y + 9, { align: 'center' });
        doc.setFont('helvetica', 'normal'); doc.text('Presenta esta entrada y tu documento en la puerta.', W / 2, y + 15, { align: 'center' });
        doc.setTextColor(150, 150, 150); doc.setFontSize(8); doc.text('Entradas con LocalProo · localproo.com', W / 2, 203, { align: 'center' });
        doc.save(D.archivo);
    }
})();
</script>
@endif
@endsection
