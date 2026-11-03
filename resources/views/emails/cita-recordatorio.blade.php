<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#ecfeff;font-family:Arial,Helvetica,sans-serif;color:#083344">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ecfeff;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0e7490;padding:20px 26px;color:#ffffff;font-weight:900;font-size:18px">{{ $t->nombre }}</td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 6px;color:#3d6470;font-size:14px">Hola {{ $c->paciente?->nombres }},</p>
        <h1 style="margin:0 0 14px;font-size:21px">Le recordamos su cita de mañana</h1>
        <div style="border:1.5px solid #a5f3fc;border-radius:16px;padding:16px;text-align:center;background:#f5feff">
            <div style="font-size:18px;font-weight:bold">{{ ucfirst($c->fecha->translatedFormat('l j \d\e F')) }}</div>
            <div style="font-size:22px;font-weight:bold;color:#0e7490;margin-top:4px">{{ $c->hora_corta }}</div>
            @if ($c->motivo)<div style="color:#3d6470;font-size:13px;margin-top:6px">{{ $c->motivo }}</div>@endif
        </div>
        <p style="color:#3d6470;font-size:14px;margin:16px 0 4px">{{ trim(implode(' · ', array_filter([$t->direccion, $t->ciudad]))) }}</p>
        <p style="text-align:center;margin:20px 0 8px"><a href="{{ $c->url_confirmar }}" style="display:inline-block;background:#0891b2;color:#ffffff;font-weight:bold;text-decoration:none;padding:13px 24px;border-radius:12px">Confirmar o cancelar mi cita</a></p>
        <p style="color:#5b7f89;font-size:12px;line-height:1.5;margin:18px 0 0">Si no puede asistir, cancélela con el botón para que otro paciente pueda usar la hora. ¡Gracias!</p>
    </td></tr>
</table>
<p style="color:#5b7f89;font-size:11px;margin-top:12px">Enviado por {{ $t->nombre }} con LocalProo</p>
</td></tr>
</table>
</body>
</html>
