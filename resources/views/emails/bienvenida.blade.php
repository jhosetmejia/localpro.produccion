@php
    $fecha = fn ($d) => $d ? $d->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : '';
    $btn = 'display:inline-block;background:#2fd15a;color:#0b0d0c;font-weight:900;text-decoration:none;border-radius:14px;padding:14px 28px';
    $pasos = $contador
        ? [['Agregue sus empresas clientes', 'Con su NIT y datos, listas para llevar su contabilidad.'], ['Cargue la información', 'Facturas, gastos y extractos: LocalProo le ayuda a ordenarlos.'], ['Prepare las declaraciones', 'IVA, retenciones y recordatorios de vencimientos.']]
        : [['Ponga la cara de su negocio', 'Suba su logo y su WhatsApp: salen en tiquetes y en su tienda en línea.'], ['Suba sus productos con foto', 'Nombre, precio y foto. Lo que tiene foto se vende más.'], ['Active su tienda en línea', 'Sus clientes le compran por WhatsApp a cualquier hora. Compártala en sus redes.'], ['Haga su primera venta', 'Abra la caja, busque el producto y cobre. El inventario se descuenta solo.']];
@endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4fcf6;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<div style="display:none;max-height:0;overflow:hidden">Su cuenta de LocalProo está lista. Así empieza a vender más desde hoy.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4fcf6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0b0d0c;padding:20px 26px">
        <span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:10px;background:#2fd15a;color:#0b0d0c;font-weight:900">L</span>
        <span style="color:#ffffff;font-weight:900;font-size:18px;vertical-align:middle;margin-left:8px">LocalProo</span>
    </td></tr>
    <tr><td style="background:#0b0d0c;background-image:linear-gradient(135deg,#0b0d0c 0%,#123a20 100%);padding:6px 26px 30px">
        <p style="margin:0;color:#a6f34a;font-weight:bold;font-size:13px;letter-spacing:1px;text-transform:uppercase">¡Bienvenido a la familia LocalProo!</p>
        <h1 style="margin:8px 0 10px;color:#ffffff;font-size:28px;line-height:1.15">Hola {{ $nombre }}, su cuenta ya está lista 🎉</h1>
        <p style="margin:0;color:#c9d3cc;line-height:1.55;font-size:15px">Desde hoy maneja {{ $contador ? 'su oficina contable' : 'su negocio' }} desde el celular: {{ $contador ? 'clientes, contabilidad, declaraciones y recordatorios' : 'ventas, caja, inventario, clientes y su tienda en línea' }}, todo en un solo lugar.</p>
    </td></tr>
    <tr><td style="padding:26px">

        @if ($t?->plan_vence)
        <div style="background:#e8f8ec;border-radius:16px;padding:16px 18px;margin:0 0 20px;line-height:1.5">
            <b style="color:#11702f;font-size:16px">🎁 Tiene prueba gratis con todo hasta el {{ $fecha($t->plan_vence) }}</b><br>
            <span style="color:#3d5244">Úselo sin límites y sin tarjeta. Le avisamos antes de que termine.</span>
        </div>
        @endif

        <p style="margin:0 0 6px;font-weight:bold;font-size:16px">Sus datos para entrar</p>
        <p style="margin:0 0 14px;line-height:1.55;color:#3d5244">Correo: <b style="color:#151a17">{{ $user->email }}</b><br>Contraseña: la que creó al registrarse (por seguridad no se la enviamos).</p>
        <p style="text-align:center;margin:0 0 24px"><a href="{{ route('login') }}" style="{{ $btn }}">Entrar a LocalProo</a></p>

        <p style="margin:0 0 10px;font-weight:bold;font-size:16px">{{ count($pasos) }} pasos para empezar con todo</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px">
            @foreach ($pasos as $i => [$tit, $txt])
            <tr>
                <td width="44" valign="top" style="padding:0 0 12px"><span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:50%;background:#0b0d0c;color:#a6f34a;font-weight:900">{{ $i + 1 }}</span></td>
                <td valign="top" style="padding:4px 0 12px;line-height:1.45"><b>{{ $tit }}</b><br><span style="color:#5b665e;font-size:14px">{{ $txt }}</span></td>
            </tr>
            @endforeach
        </table>

        <div style="border:2px solid #e3efe5;border-radius:16px;padding:16px 18px;margin:0 0 16px;line-height:1.55">
            <b>🔐 ¿Olvidó su contraseña?</b><br>
            <span style="color:#3d5244">En la pantalla de ingreso toque <b>"¿Olvidó su contraseña?"</b>, escriba este correo y le llega un <b>código de 6 números</b> para crear una nueva. Guarde este correo: es la llave de su cuenta.</span><br>
            <a href="{{ route('password.request') }}" style="color:#11702f;font-weight:bold">Recuperar mi contraseña</a>
        </div>

        @if ($t?->codigo && !$contador)
        <div style="background:#0b0d0c;color:#ffffff;border-radius:16px;padding:16px 18px;margin:0 0 16px;line-height:1.55">
            <b style="color:#a6f34a">Su código de negocio: {{ $t->codigo }}</b><br>
            Recomiende LocalProo a otros emprendedores con su enlace y gane beneficios por cada uno que se una:<br>
            <a href="{{ \App\Support\Referidos::enlace($t) }}" style="color:#a6f34a;font-weight:bold">{{ \App\Support\Referidos::enlace($t) }}</a>
        </div>
        @endif

        <p style="margin:0 0 18px;line-height:1.55;color:#3d5244">¿Necesita ayuda? @if ($soporte)Escríbanos por WhatsApp al <a href="https://wa.me/{{ $soporte }}" style="color:#11702f;font-weight:bold">+{{ $soporte }}</a> o responda este correo.@else Responda este correo y le ayudamos.@endif Estamos para que le vaya bien.</p>

        <p style="margin:0;font-weight:bold">¡Muchos éxitos con {{ $t?->nombre ?: 'su negocio' }}! 💚</p>
        <p style="margin:2px 0 0;color:#5b665e">El equipo de LocalProo</p>

        <hr style="border:0;border-top:1px solid #e3efe5;margin:22px 0 14px">
        <p style="color:#8a948d;font-size:11.5px;line-height:1.5;margin:0">Recibió este correo porque creó una cuenta en LocalProo con {{ $user->email }}. Si no fue usted, respóndanos y la revisamos. <a href="{{ route('legal.terminos') }}" style="color:#8a948d">Condiciones de uso</a> · <a href="{{ route('legal.privacidad') }}" style="color:#8a948d">Privacidad</a></p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
