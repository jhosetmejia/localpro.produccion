@php
    $fecha = fn ($d) => $d ? $d->copy()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') : '';
    $ultimoDia = $fechaBorrado?->copy()->subDay();
    $pesos = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f4fcf6;font-family:Arial,Helvetica,sans-serif;color:#151a17">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4fcf6;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:22px;overflow:hidden">
    <tr><td style="background:#0b0d0c;padding:20px 26px">
        <span style="display:inline-block;width:32px;height:32px;line-height:32px;text-align:center;border-radius:10px;background:#2fd15a;color:#0b0d0c;font-weight:900">L</span>
        <span style="color:#ffffff;font-weight:900;font-size:18px;vertical-align:middle;margin-left:8px">LocalProo</span>
    </td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 6px;color:#6b7570;font-size:13px;font-weight:bold">{{ $t->nombre }} @if($t->codigo)· {{ $t->codigo }}@endif</p>

        @if ($tipo === 'por_vencer')
            <h1 style="margin:0 0 12px;font-size:22px">Su prueba gratis termina el {{ $fecha($t->plan_vence) }}</h1>
            <p style="line-height:1.55;margin:0 0 14px">Esperamos que LocalProo le esté ayudando a ordenar su negocio. Para seguir vendiendo sin interrupciones, active su plan antes de esa fecha.</p>
            <p style="line-height:1.55;margin:0 0 14px">Si la prueba termina sin pago, el sistema se bloquea y tendrá <b>{{ \App\Console\Commands\DepurarPruebas::diasGracia() }} días</b> para pagar; después se elimina la información del negocio (productos, imágenes, ventas, clientes).</p>
        @elseif ($tipo === 'vencida')
            <h1 style="margin:0 0 12px;font-size:22px">Su prueba gratis terminó</h1>
            <p style="line-height:1.55;margin:0 0 14px">Su cuenta está bloqueada hasta que active un plan. Toda su información sigue guardada <b>hasta el {{ $fecha($ultimoDia) }}</b>.</p>
            <div style="background:#ffe9f3;border-radius:14px;padding:14px 16px;margin:0 0 16px;color:#9b1c5a;line-height:1.5">
                <b>Importante:</b> si no activa un plan, el <b>{{ $fecha($fechaBorrado) }}</b> se eliminarán de forma definitiva los productos, imágenes, videos, inventario, ventas, clientes y demás registros de su negocio. No se podrán recuperar.
            </div>
        @elseif ($tipo === 'aviso_final')
            <h1 style="margin:0 0 12px;font-size:22px">Último aviso: su información se elimina el {{ $fecha($fechaBorrado) }}</h1>
            <p style="line-height:1.55;margin:0 0 14px">Le escribimos porque su prueba gratis terminó el {{ $fecha($t->plan_vence) }} y aún no ha activado un plan.</p>
            <div style="background:#ffe9f3;border-radius:14px;padding:14px 16px;margin:0 0 16px;color:#9b1c5a;line-height:1.5">
                Si paga antes del <b>{{ $fecha($ultimoDia) }}</b> conserva todo. Después de esa fecha se eliminan de forma definitiva sus productos, imágenes, ventas, clientes y demás registros.
            </div>
        @elseif ($tipo === 'datos_eliminados')
            <h1 style="margin:0 0 12px;font-size:22px">Se eliminó la información de su negocio</h1>
            <p style="line-height:1.55;margin:0 0 14px">Como su prueba gratis terminó y no se activó un plan en el plazo avisado, eliminamos los productos, imágenes, ventas, clientes y demás registros de {{ $t->nombre }}, tal como lo indican las condiciones de uso.</p>
            <p style="line-height:1.55;margin:0 0 14px">Su cuenta y sus datos de contacto siguen activos: si activa un plan, puede volver a usar LocalProo empezando con el negocio vacío.</p>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 18px">
            <tr>
            @foreach ($planes as $p)
                <td style="padding:4px" width="{{ (int) (100 / max(1, $planes->count())) }}%">
                    <div style="border:2px solid {{ $p->dias >= 365 ? '#2fd15a' : '#e3efe5' }};border-radius:16px;padding:14px;text-align:center">
                        <div style="font-weight:bold;color:#6b7570;font-size:13px">{{ $p->nombre }}</div>
                        <div style="font-size:22px;font-weight:900;margin:4px 0">{{ $pesos($p->precio) }}</div>
                        <div style="color:#6b7570;font-size:12px">{{ $p->dias >= 365 ? 'por año' : ($p->dias >= 28 ? 'por mes' : $p->dias . ' días') }}</div>
                    </div>
                </td>
            @endforeach
            </tr>
        </table>
        <p style="text-align:center;margin:0 0 8px">
            <a href="{{ $urlPago }}" style="display:inline-block;background:#2fd15a;color:#0b0d0c;font-weight:900;text-decoration:none;border-radius:14px;padding:14px 28px">{{ $tipo === 'datos_eliminados' ? 'Activar mi plan' : 'Pagar mi plan ahora' }}</a>
        </p>
        <p style="text-align:center;color:#6b7570;font-size:12px;margin:0 0 18px">Pago seguro en línea: tarjeta, PSE, Nequi o Bancolombia.</p>

        <hr style="border:0;border-top:1px solid #e3efe5;margin:18px 0">
        <p style="color:#8a948d;font-size:11.5px;line-height:1.5;margin:0">
            Este es un aviso de notificación enviado a los correos registrados del negocio {{ $t->nombre }}@if($t->nit) (NIT {{ $t->nit }})@endif, conforme a las
            <a href="{{ route('legal.terminos') }}" style="color:#8a948d">Condiciones de uso de LocalProo</a>@if($aceptoEl) aceptadas al crear la cuenta el {{ $aceptoEl->format('d/m/Y') }}@endif.
            Al finalizar la prueba gratis sin activar un plan, la información del negocio se elimina para no ocupar el servidor; se conservan los datos de contacto (negocio y usuarios).
            Queda constancia de este envío ({{ now()->format('d/m/Y H:i') }}). Si cree que se trata de un error, responda este correo o escríbanos.
        </p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
