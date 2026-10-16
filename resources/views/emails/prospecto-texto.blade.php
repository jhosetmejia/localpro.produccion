{{ $p->saludo() }}:

@if ($paso === 1)
Le escribo de LocalProo. Hicimos una herramienta para contadores que llevan varios clientes: sube el listado de facturas de la DIAN y el auxiliar contable, y en minutos le muestra lo que falta contabilizar, las retenciones y el reteIVA que faltaron o sobraron, el borrador del 350 y el IVA del periodo.

También trae el calendario tributario por último dígito del NIT de cada cliente, con recordatorio por correo 3 días antes y el día del vencimiento.

Puede probarlo 3 meses gratis, con una empresa de ejemplo ya cargada, sin subir nada suyo:
{{ $registro }}

¿Le muestro en 15 minutos cómo funciona con uno de sus clientes? Respóndame con el día que le quede bien.
@elseif ($paso === 2)
Le dejo un dato sobre el correo anterior: el sistema trae una empresa de ejemplo con el mes ya cargado, así que en 5 minutos ve el cruce DIAN vs. contabilidad funcionando sin subir nada suyo.

{{ $registro }}

Si le sirve, lo revisamos juntos esta semana en 15 minutos.
@else
No quiero llenarle la bandeja. Si el cierre mensual de sus clientes no le quita tiempo, lo entiendo y no le escribo más.

Si sí se lo quita, respóndame con un día y le muestro la herramienta en 15 minutos. Queda el enlace por si prefiere verla solo:
{{ $registro }}
@endif

{{ $firma }}
LocalProo · https://localproo.com

--
Le escribimos porque su correo está publicado como contacto de {{ $p->empresa }}@if($p->fuente) en {{ $p->fuente }}@endif.
Si no desea recibir más correos, entre aquí y no le volvemos a escribir: {{ $baja }}
