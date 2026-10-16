<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Listo · LocalProo</title><meta name="robots" content="noindex"></head>
<body style="margin:0;background:#f4fcf6;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<div style="max-width:460px;margin:12vh auto;padding:28px 24px;background:#fff;border-radius:22px;text-align:center">
    <div style="width:46px;height:46px;line-height:46px;margin:0 auto 14px;border-radius:14px;background:#2fd15a;color:#0b0d0c;font-weight:900;font-size:22px">L</div>
    @if ($ok)
        <h1 style="font-size:21px;margin:0 0 10px">Listo, no le volvemos a escribir</h1>
        <p style="line-height:1.55;margin:0;color:#4b524e">Retiramos {{ $correo }} de nuestros envíos. Disculpe la molestia.</p>
    @else
        <h1 style="font-size:21px;margin:0 0 10px">Enlace no válido</h1>
        <p style="line-height:1.55;margin:0;color:#4b524e">Si quiere que no le escribamos, responda el correo con la palabra "baja".</p>
    @endif
</div>
</body>
</html>
