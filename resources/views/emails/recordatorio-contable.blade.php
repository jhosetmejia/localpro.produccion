@php $fecha = fn ($d) => ucfirst($d->copy()->locale('es')->isoFormat('dddd D [de] MMMM')); @endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4fcf6;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4fcf6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0b0d0c;padding:20px 26px">
        <span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:10px;background:#2fd15a;color:#0b0d0c;font-weight:900">L</span>
        <span style="color:#ffffff;font-weight:900;font-size:18px;vertical-align:middle;margin-left:8px">LocalProo · Contabilidad</span>
    </td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 6px;color:#6b7570;font-size:13px;font-weight:bold">{{ $nombre }}</p>
        <h1 style="margin:0 0 14px;font-size:21px">Vencimientos tributarios de sus clientes</h1>
        @foreach ($lista as $v)
        <div style="border:1px solid #e3efe5;border-left:4px solid {{ $v['dias'] === 0 ? '#d93025' : '#f5b544' }};border-radius:12px;padding:12px 14px;margin:0 0 10px">
            <div style="font-size:12px;font-weight:bold;color:{{ $v['dias'] === 0 ? '#d93025' : '#8a5a00' }}">{{ $v['dias'] === 0 ? 'VENCE HOY' : 'Vence el ' . $fecha($v['fecha']) }}</div>
            <div style="font-weight:bold;margin:2px 0">{{ $v['nombre'] }} · {{ $v['periodo'] }} ({{ $v['formulario'] }})</div>
            <div style="color:#6b7570;font-size:13px">{{ $v['cliente']->nombre }} · NIT {{ $v['cliente']->nit }}</div>
        </div>
        @endforeach
        <p style="text-align:center;margin:20px 0 8px"><a href="{{ route('contabilidad.calendario') }}" style="display:inline-block;background:#0b0d0c;color:#a6f34a;font-weight:bold;text-decoration:none;padding:12px 22px;border-radius:12px">Abrir mi calendario</a></p>
        <hr style="border:0;border-top:1px solid #e3efe5;margin:18px 0">
        <p style="color:#8a948d;font-size:11.5px;line-height:1.5;margin:0">
            Fechas según el último dígito del NIT ({{ \App\Support\CalendarioTributario::FUENTE }}). Verifique siempre en el calendario oficial de la DIAN.
            Cuando marque el mes como "Presentado en la DIAN" deja de recibir el aviso. Para no recibir avisos de un cliente, apague "Recordarme sus vencimientos por correo" en sus datos.
        </p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
