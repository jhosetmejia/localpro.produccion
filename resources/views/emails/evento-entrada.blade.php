@php $c = $ev->color ?: '#a855f7'; @endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4f2f8;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2f8;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0b0d0c;padding:20px 26px;color:#fff">
        <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:{{ $c }};font-weight:bold">{{ $tienda?->nombre }}</div>
        <div style="font-size:22px;font-weight:900;margin-top:4px">{{ $ev->nombre }}</div>
        @if ($ev->subtitulo)<div style="color:#cfc7dc;margin-top:2px">{{ $ev->subtitulo }}</div>@endif
    </td></tr>
    <tr><td style="padding:26px">
        <h1 style="margin:0 0 12px;font-size:21px">¡Hola, {{ \Illuminate\Support\Str::of($a->nombre)->explode(' ')->first() }}! Tu pago fue aprobado 🎉</h1>
        @if (($cantidad ?? 1) > 1)<p style="line-height:1.55;margin:0 0 12px">Compraste <b>{{ $cantidad }} entradas</b>: cada persona tiene su propio QR. Las ves todas en "Mis entradas" y puedes enviarle a cada acompañante la suya.</p>@endif
        <p style="line-height:1.55;margin:0 0 14px">Tu entrada para <b>{{ $ev->nombre }}</b> ya está lista con su código QR.</p>
        @if ($ev->fecha_texto)<p style="line-height:1.55;margin:0 0 6px"><b>Fecha:</b> {{ ucfirst($ev->fecha_texto) }}</p>@endif
        @if ($ev->lugar)<p style="line-height:1.55;margin:0 0 14px"><b>Lugar:</b> {{ $ev->lugar }}@if ($ev->ciudad), {{ $ev->ciudad }}@endif</p>@endif
        <p style="margin:22px 0;text-align:center"><a href="{{ $url }}" style="background:{{ $c }};color:#fff;text-decoration:none;font-weight:bold;padding:14px 26px;border-radius:999px;display:inline-block">Ver mi entrada y el QR</a></p>
        <div style="background:#fff5d6;border-radius:14px;padding:14px 16px;margin:0 0 14px;color:#7a5200;line-height:1.5">
            <b>No compartas tu QR:</b> es único e intransferible. Solo sirve para entrar una vez y en la puerta revisan tu documento.
        </div>
        <p style="line-height:1.55;margin:0 0 6px;color:#555">Te recomendamos <b>descargarla en PDF</b> desde "Mis entradas" para no tener problemas de internet en la entrada.</p>
        <p style="line-height:1.55;margin:14px 0 0;color:#888;font-size:13px">Para entrar a "Mis entradas" usa tu correo ({{ $a->email }}) y la clave que creaste al registrarte.</p>
    </td></tr>
    <tr><td style="padding:16px 26px;background:#faf9fc;color:#888;font-size:12px;text-align:center">Entradas con LocalProo · localproo.com</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
