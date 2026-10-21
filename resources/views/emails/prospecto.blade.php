<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#1d211f;font-size:15px;line-height:1.6">
<div style="max-width:560px;padding:18px 16px">
<p style="margin:0 0 14px">{{ $p->saludo() }}:</p>
@if ($paso === 1)
<p style="margin:0 0 14px">Le escribo de LocalProo. Hicimos una herramienta para contadores que llevan varios clientes: sube el listado de facturas de la DIAN y el auxiliar contable, y en minutos le muestra <b>lo que falta contabilizar</b>, las <b>retenciones y el reteIVA</b> que faltaron o sobraron, el <b>borrador del 350</b> y el IVA del periodo.</p>
<p style="margin:0 0 14px">También trae el <b>calendario tributario por último dígito del NIT</b> de cada cliente, con recordatorio por correo 3 días antes y el día del vencimiento.</p>
<p style="margin:0 0 14px">Puede probarlo <b>2 meses gratis</b>, con una empresa de ejemplo ya cargada, sin subir nada suyo: <a href="{{ $registro }}" style="color:#118a3a">crear mi cuenta de contador</a>.</p>
<p style="margin:0 0 14px">¿Le muestro en 15 minutos cómo funciona con uno de sus clientes? Respóndame con el día que le quede bien.</p>
@elseif ($paso === 2)
<p style="margin:0 0 14px">Le dejo un dato sobre el correo anterior: el sistema trae una empresa de ejemplo con el mes ya cargado, así que en 5 minutos ve el cruce DIAN vs. contabilidad funcionando sin subir nada suyo.</p>
<p style="margin:0 0 14px"><a href="{{ $registro }}" style="color:#118a3a">Probarlo gratis</a></p>
<p style="margin:0 0 14px">Si le sirve, lo revisamos juntos esta semana en 15 minutos.</p>
@else
<p style="margin:0 0 14px">No quiero llenarle la bandeja. Si el cierre mensual de sus clientes no le quita tiempo, lo entiendo y no le escribo más.</p>
<p style="margin:0 0 14px">Si sí se lo quita, respóndame con un día y le muestro la herramienta en 15 minutos. Queda el <a href="{{ $registro }}" style="color:#118a3a">enlace</a> por si prefiere verla solo.</p>
@endif
<p style="margin:0 0 22px">{{ $firma }}<br><a href="https://localproo.com" style="color:#118a3a">localproo.com</a></p>
<p style="margin:0;color:#8a928e;font-size:12px;line-height:1.5">Le escribimos porque su correo está publicado como contacto de {{ $p->empresa }}. Si no desea recibir más correos, <a href="{{ $baja }}" style="color:#8a928e">dese de baja aquí</a> y no le volvemos a escribir.</p>
</div>
</body>
</html>
