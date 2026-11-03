<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Plan de tratamiento {{ $plan->numero }} · {{ $p->nombre_completo }}</title>
@include('odontologia._hoja-css')
</head>
<body>
<div class="bar">
    <a href="{{ !empty($portal) ? route('paciente.portal', $portal) : route('odonto.plan', $plan) }}" class="blanco">← Volver</a>
    @if (empty($portal) && $p->whatsapp)<a id="whatsapp" class="wa" href="https://wa.me/{{ $p->whatsapp }}?text={{ rawurlencode($wa) }}" target="_blank" rel="noopener">Enviar por WhatsApp</a>@endif
    <button onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<div class="hoja">
    @include('odontologia._hoja-cab', ['titulo' => $plan->estado === 'propuesto' ? 'Presupuesto odontológico' : 'Plan de tratamiento', 'subtitulo' => $plan->numero . ' · ' . $plan->nombre, 'fecha' => $plan->created_at->format('d/m/Y')])
        <h3>Procedimientos</h3>
        <table>
            <thead><tr><th>Pieza</th><th>Procedimiento</th><th class="r">Cant.</th><th class="r">Valor</th><th class="r">Total</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($plan->items as $it)
            <tr>
                <td>@if ($it->pieza)<span class="pz">{{ $it->pieza }}{{ $it->caras ? ' ' . $it->caras : '' }}</span>@else — @endif</td>
                <td>{{ $it->descripcion }}</td>
                <td class="r">{{ $it->cantidad }}</td>
                <td class="r">{{ dinero($it->valor) }}</td>
                <td class="r"><b>{{ dinero($it->total) }}</b></td>
                <td>{{ $it->estado === 'hecho' ? 'Hecho ' . $it->hecho_at?->format('d/m/Y') : 'Pendiente' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        <div class="tot">
            <div><span>Subtotal</span><span>{{ dinero($plan->subtotal) }}</span></div>
            @if ($plan->descuento > 0)<div><span>Descuento</span><span>− {{ dinero($plan->descuento) }}</span></div>@endif
            <div class="g"><span>Total</span><span>{{ dinero($plan->total) }}</span></div>
            @if ($plan->abonado > 0)
            <div><span>Abonado</span><span>{{ dinero($plan->abonado) }}</span></div>
            <div><b>Saldo</b><b>{{ dinero($plan->saldo) }}</b></div>
            @endif
        </div>
        @if ($plan->notas)<h3>Observaciones</h3><p class="txt">{{ $plan->notas }}</p>@endif
        <h3>Condiciones</h3>
        <p class="txt">Este presupuesto se basa en la valoración clínica del día y puede cambiar si durante el tratamiento aparecen hallazgos nuevos; cualquier cambio se le informará antes de hacerlo. Los abonos se aplican al saldo de este plan.</p>
        <div class="firmas">
            <div>@if ($firma)<img src="{{ $firma }}" alt="">@endif<div class="linea"><b>{{ $plan->profesional?->name }}</b>
                <span>{{ $plan->profesional?->especialidad ?: 'Odontólogo(a)' }}{{ $plan->profesional?->registro_profesional ? ' · Reg. ' . $plan->profesional->registro_profesional : '' }}</span></div></div>
            <div><div style="height:80px"></div><div class="linea"><b>{{ $p->nombre_completo }}</b><span>Acepto el plan de tratamiento{{ $plan->aprobado_at ? ' · aprobado el ' . $plan->aprobado_at->format('d/m/Y') : '' }}</span></div></div>
        </div>
    </div>
    <div class="pie"><span>Impreso el {{ now()->format('d/m/Y h:i a') }}</span><span>Hecho con LocalProo · localproo.com</span></div>
</div>
</body>
</html>
