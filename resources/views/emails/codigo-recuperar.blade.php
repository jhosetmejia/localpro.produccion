<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4fcf6;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4fcf6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0b0d0c;padding:20px 26px">
        <span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:10px;background:#2fd15a;color:#0b0d0c;font-weight:900">L</span>
        <span style="color:#ffffff;font-weight:900;font-size:18px;vertical-align:middle;margin-left:8px">LocalProo</span>
    </td></tr>
    <tr><td style="padding:28px 26px">
        <h1 style="margin:0 0 10px;font-size:22px">Hola{{ $nombre ? ', ' . $nombre : '' }}</h1>
        <p style="line-height:1.55;margin:0 0 18px">Pidió recuperar su contraseña de LocalProo. Escriba este código en la página donde lo pidió:</p>
        <div style="text-align:center;margin:0 0 18px">
            <span style="display:inline-block;background:#f4fcf6;border:2px solid #2fd15a;border-radius:16px;padding:14px 26px;font-size:34px;font-weight:900;letter-spacing:10px;color:#0b0d0c">{{ $codigo }}</span>
        </div>
        <p style="line-height:1.55;margin:0 0 14px;color:#6b7570;text-align:center">El código vence en <b>{{ $minutos }} minutos</b>.</p>
        <p style="text-align:center;margin:0 0 18px">
            <a href="{{ route('password.codigo', ['email' => $email]) }}" style="display:inline-block;background:#2fd15a;color:#0b0d0c;font-weight:900;text-decoration:none;border-radius:14px;padding:13px 26px">Escribir el código</a>
        </p>
        <hr style="border:0;border-top:1px solid #e3efe5;margin:18px 0">
        <p style="color:#8a948d;font-size:12px;line-height:1.5;margin:0">
            Si usted no pidió este código, ignore este correo: su contraseña sigue igual. Nunca le compartimos ni le pedimos este código por teléfono o WhatsApp.
        </p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
