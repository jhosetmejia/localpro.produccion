@php
    $titulo = ['previo' => '¡Su separado está por vencer!', 'hoy' => 'Hoy vence su separado', 'vencido' => 'Su separado ya venció'][$tipo];
    $color = ['previo' => '#f59e0b', 'hoy' => '#ea580c', 'vencido' => '#dc2626'][$tipo];
    $nombre = \Illuminate\Support\Str::words($s->cliente_nombre, 1, '');
@endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#fff7ed;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:{{ $color }};padding:20px 26px;color:#ffffff;font-weight:900;font-size:18px">🛍️ {{ $t->nombre }}</td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 6px;color:#6b7280;font-size:14px">Hola {{ $nombre }} 👋</p>
        <h1 style="margin:0 0 12px;font-size:22px">{{ $titulo }}</h1>
        <p style="margin:0 0 16px;font-size:15px;line-height:1.5">
            @if ($tipo === 'previo')
                Le recordamos que tiene hasta el <b>{{ $s->vence_en->format('d/m/Y') }}</b> para terminar de pagar su separado. ¡Ya casi es suyo!
            @elseif ($tipo === 'hoy')
                Hoy <b>{{ $s->vence_en->format('d/m/Y') }}</b> es el último día para pagar su separado. Acérquese a la tienda y lléveselo.
            @else
                El plazo de su separado terminó el <b>{{ $s->vence_en->format('d/m/Y') }}</b>. Acérquese cuanto antes a la tienda a pagar para no perderlo.
            @endif
        </p>
        <div style="border:1.5px solid #fed7aa;border-radius:16px;padding:16px;background:#fffbf5">
            <div style="font-size:13px;color:#6b7280">Separado {{ $s->numero }}</div>
            @foreach ($s->items as $i)
            <div style="font-size:14px;margin-top:4px">• {{ $i->descripcion }}{{ $i->talla ? ' · Talla ' . $i->talla : '' }}{{ $i->color ? ' · ' . $i->color : '' }}</div>
            @endforeach
            <table role="presentation" width="100%" style="margin-top:12px;font-size:14px">
                <tr><td style="color:#6b7280">Total</td><td align="right">{{ dinero($s->total) }}</td></tr>
                <tr><td style="color:#6b7280">Ya abonó</td><td align="right">{{ dinero($s->abonado) }}</td></tr>
                <tr><td style="font-weight:bold;font-size:16px;padding-top:6px">Le falta</td><td align="right" style="font-weight:bold;font-size:20px;color:{{ $color }};padding-top:6px">{{ dinero($s->saldo) }}</td></tr>
            </table>
        </div>
        @if (trim(implode('', [$t->direccion, $t->ciudad])))
        <p style="color:#374151;font-size:14px;margin:16px 0 4px">📍 {{ trim(implode(' · ', array_filter([$t->direccion, $t->ciudad]))) }}</p>
        @endif
        <p style="text-align:center;margin:20px 0 8px"><a href="{{ $url }}" style="display:inline-block;background:{{ $color }};color:#ffffff;font-weight:bold;text-decoration:none;padding:13px 24px;border-radius:12px">Ver mi separado y mis abonos</a></p>
        @if ($t->telefono)
        <p style="text-align:center;margin:0 0 8px"><a href="https://wa.me/{{ numero_whatsapp($t->telefono) }}?text={{ rawurlencode('Hola, quiero pagar mi separado ' . $s->numero) }}" style="color:#16a34a;font-weight:bold;font-size:14px;text-decoration:none">Escribirle a la tienda por WhatsApp</a></p>
        @endif
        <p style="color:#6b7280;font-size:12px;line-height:1.5;margin:18px 0 0">Si ya pagó, no tenga en cuenta este mensaje. ¡Gracias por su compra!</p>
    </td></tr>
</table>
<p style="color:#9ca3af;font-size:11px;margin-top:12px">Enviado por {{ $t->nombre }} con LocalProo</p>
</td></tr>
</table>
</body>
</html>
