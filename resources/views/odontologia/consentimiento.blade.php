<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $c->titulo }} · {{ $p->nombre_completo }}</title>
@include('odontologia._hoja-css')
</head>
<body>
<div class="bar">
    <a href="{{ !empty($portal) ? route('paciente.portal', $portal) : route('consultorio.paciente', $p) }}" class="blanco">← Volver</a>
    <button onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<div class="hoja">
    @include('odontologia._hoja-cab', ['titulo' => 'Consentimiento informado', 'subtitulo' => $c->titulo, 'fecha' => $c->firmado_at?->format('d/m/Y h:i a')])
        @if ($c->procedimiento)<h3>Procedimiento</h3><p class="txt"><b>{{ $c->procedimiento }}</b></p>@endif
        <h3>{{ $c->titulo }}</h3>
        <p class="txt">{{ $c->texto }}</p>
        <div class="firmas">
            <div>@if ($firmaPaciente)<img src="{{ $firmaPaciente }}" alt="Firma">@endif
                <div class="linea"><b>{{ $c->firmante_nombre }}</b><span>{{ $c->firmante_relacion }}{{ $c->firmante_documento ? ' · ' . $c->firmante_documento : '' }}</span><span>Firmó el {{ $c->firmado_at?->format('d/m/Y h:i a') }}</span></div></div>
            <div>@if ($firmaProfesional)<img src="{{ $firmaProfesional }}" alt="">@else<div style="height:80px"></div>@endif
                <div class="linea"><b>{{ $c->profesional_nombre }}</b><span>Odontólogo(a){{ $c->profesional_registro ? ' · Reg. ' . $c->profesional_registro : '' }}</span></div></div>
        </div>
    </div>
    <div class="pie"><span>Firmado en pantalla · IP {{ $c->ip }} · {{ $c->integro ? 'Documento íntegro' : 'ATENCIÓN: el documento no coincide con su huella' }} · Código {{ substr((string) $c->huella, 0, 12) }}</span><span>LocalProo</span></div>
</div>
</body>
</html>
