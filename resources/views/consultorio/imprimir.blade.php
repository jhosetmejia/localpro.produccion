<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
@php
    $titulos = ['historia' => 'Historia clínica', 'completa' => 'Historia clínica completa', 'formula' => 'Fórmula médica', 'incapacidad' => 'Certificado de incapacidad'];
    $etiquetas = ['motivo' => 'Motivo de consulta', 'enfermedad_actual' => 'Enfermedad actual', 'analisis' => 'Análisis', 'plan' => 'Plan y conducta'];
    foreach (\App\Support\Consultorio::plantillas() as $pl) foreach ($pl['campos'] as $f) $etiquetas[$f[0]] ??= $f[1];
    $sexo = ['F' => 'Femenino', 'M' => 'Masculino', 'I' => 'Intersexual'][$p->sexo] ?? '—';
@endphp
<title>{{ $titulos[$doc] }} · {{ $p->nombre_completo }}</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:0;background:#eef1ef;font-size:13px;line-height:1.45}
.hoja{background:#fff;max-width:820px;margin:20px auto;padding:34px 40px;box-shadow:0 10px 30px -18px rgba(0,0,0,.4)}
.cab{display:flex;gap:16px;align-items:center;border-bottom:2px solid #111;padding-bottom:12px;margin-bottom:14px}
.cab img{max-height:64px;max-width:140px;object-fit:contain}
.cab .neg b{font-size:17px;display:block}.cab .neg span{color:#444;font-size:12px;display:block}
.cab .tit{margin-left:auto;text-align:right}.cab .tit b{font-size:16px;display:block;text-transform:uppercase}.cab .tit span{font-size:12px;color:#444}
.pac{display:grid;grid-template-columns:repeat(4,1fr);gap:6px 14px;border:1px solid #bbb;border-radius:8px;padding:10px 12px;margin-bottom:14px}
.pac small{display:block;color:#555;font-size:10.5px;text-transform:uppercase}
.pac b{font-size:12.5px}
.pac .ancho{grid-column:span 2}
h3{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#333;border-bottom:1px solid #ddd;padding-bottom:3px;margin:14px 0 6px}
p{margin:0 0 4px;white-space:pre-line}
.at{border-top:2px dashed #999;margin-top:22px;padding-top:12px}
.at:first-of-type{border-top:0;margin-top:0;padding-top:0}
.at .enc{font-weight:bold;font-size:13px;margin-bottom:6px}
.sv span{display:inline-block;border:1px solid #ccc;border-radius:6px;padding:2px 7px;margin:0 4px 4px 0;font-size:12px}
.nota{border-left:3px solid #c9a100;padding:4px 8px;margin:6px 0;background:#fffbea}
.nota small{display:block;color:#7a6200;font-weight:bold}
.rx{font-size:15px;line-height:1.7;min-height:220px;padding:6px 0}
.rx .simbolo{font-size:30px;font-weight:bold;font-family:Georgia,serif}
.firma{margin-top:40px;display:flex;justify-content:flex-end}
.firma div{text-align:center;min-width:260px}
.firma img{max-height:70px;max-width:220px;display:block;margin:0 auto -6px}
.firma .linea{border-top:1px solid #111;padding-top:4px}
.firma b{display:block}.firma span{display:block;font-size:12px;color:#333}
.pie{margin-top:24px;border-top:1px solid #ddd;padding-top:8px;font-size:10.5px;color:#666;display:flex;justify-content:space-between;gap:10px}
.bar{max-width:820px;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end}
.bar button,.bar a{border:0;border-radius:999px;padding:9px 16px;font-weight:bold;cursor:pointer;background:#0b0d0c;color:#fff;text-decoration:none;font-size:13px}
.bar a{background:#fff;color:#111;border:1px solid #ccc}
.cert{font-size:15px;line-height:1.8;margin:26px 0}
@media print{body{background:#fff}.hoja{box-shadow:none;margin:0;max-width:none;padding:0}.bar{display:none}@page{margin:16mm 14mm}.at{break-inside:auto}}
@media (max-width:640px){.hoja{padding:20px 16px;margin:0}.pac{grid-template-columns:repeat(2,1fr)}.cab{flex-wrap:wrap}.cab .tit{margin-left:0;text-align:left}}
</style>
</head>
<body>
<div class="bar"><a href="javascript:history.back()">← Volver</a><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
<div class="hoja">
    <div class="cab">
        @if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="">@endif
        <div class="neg"><b>{{ $tienda->nombre }}</b>
            @if ($tienda->nit)<span>NIT {{ $tienda->nit }}</span>@endif
            <span>{{ trim(implode(' · ', array_filter([$tienda->direccion, $tienda->ciudad, $tienda->telefono]))) }}</span></div>
        <div class="tit"><b>{{ $titulos[$doc] }}</b><span>Impreso el {{ now()->format('d/m/Y h:i a') }}</span></div>
    </div>

    <div class="pac">
        <div class="ancho"><small>Paciente</small><b>{{ $p->nombre_completo }}</b></div>
        <div><small>Documento</small><b>{{ trim($p->tipo_documento . ' ' . $p->documento) ?: '—' }}</b></div>
        <div><small>Edad</small><b>{{ $p->edad ?: '—' }}</b></div>
        @if (in_array($doc, ['historia', 'completa'], true))
        <div><small>Nacimiento</small><b>{{ $p->fecha_nacimiento?->format('d/m/Y') ?: '—' }}</b></div>
        <div><small>Sexo</small><b>{{ $sexo }}</b></div>
        <div><small>EPS / prepagada</small><b>{{ $p->aseguradora ?: '—' }}</b></div>
        <div><small>Celular</small><b>{{ $p->telefono ?: '—' }}</b></div>
        <div class="ancho"><small>Dirección</small><b>{{ trim($p->direccion . ($p->ciudad ? ', ' . $p->ciudad : ''), ', ') ?: '—' }}</b></div>
        <div class="ancho"><small>Ocupación</small><b>{{ $p->ocupacion ?: '—' }}</b></div>
        @endif
    </div>

    @if (in_array($doc, ['historia', 'completa'], true))
        @if ($p->alergias)<h3>Alergias</h3><p><b>{{ $p->alergias }}</b></p>@endif
        @php $ant = array_filter((array) $p->antecedentes); @endphp
        @if ($ant)
        <h3>Antecedentes</h3>
        @foreach ($ant as $k => $v)<p><b>{{ \App\Models\Paciente::ANTECEDENTES[$k] ?? $k }}:</b> {{ $v }}</p>@endforeach
        @endif

        @forelse ($atenciones as $a)
        @php $c = $a->contenido ?? []; $s = $c['signos'] ?? []; @endphp
        <div class="at">
            <h3 style="border:0;margin-top:18px">Atención n.º {{ $a->numero }} · {{ $a->fecha->format('d/m/Y h:i a') }} · {{ \App\Support\Consultorio::plantilla($a->plantilla)['nombre'] }}</h3>
            @foreach (['motivo', 'enfermedad_actual'] as $k)
            @if (!empty($c[$k]))<p><b>{{ $etiquetas[$k] }}:</b> {{ $c[$k] }}</p>@endif
            @endforeach
            @if ($s)
            <div class="sv">@foreach (\App\Support\Consultorio::SIGNOS as [$k, $lab, $t, $ay])@if (!empty($s[$k]))<span>{{ $lab }}: {{ $s[$k] }} {{ \Illuminate\Support\Str::before($ay, ' ·') }}</span>@endif @endforeach
                @if (!empty($s['peso']) && !empty($s['talla']) && (float) $s['talla'] > 0)<span>IMC: {{ number_format((float) $s['peso'] / pow((float) $s['talla'] / 100, 2), 1, ',', '.') }}</span>@endif</div>
            @endif
            @foreach ($c as $k => $v)
            @if (!in_array($k, ['motivo', 'enfermedad_actual', 'signos', 'analisis', 'plan'], true) && is_string($v) && trim($v) !== '')<p><b>{{ $etiquetas[$k] ?? ucfirst(str_replace('_', ' ', $k)) }}:</b> {{ $v }}</p>@endif
            @endforeach
            @if ($a->diagnosticos)
            <p><b>Diagnósticos:</b></p>
            @foreach ($a->diagnosticos as $d)<p>· {{ $d['codigo'] ?? '' }} {{ $d['texto'] ?? '' }} ({{ ($d['tipo'] ?? '') === 'confirmado' ? 'confirmado' : 'impresión diagnóstica' }})</p>@endforeach
            @endif
            @foreach (['analisis', 'plan'] as $k)
            @if (!empty($c[$k]))<p><b>{{ $etiquetas[$k] }}:</b> {{ $c[$k] }}</p>@endif
            @endforeach
            @if ($a->formula)<p><b>Fórmula:</b> {{ $a->formula }}</p>@endif
            @if ($a->incapacidad_dias)<p><b>Incapacidad:</b> {{ $a->incapacidad_dias }} día(s) desde el {{ $a->incapacidad_desde?->format('d/m/Y') }}</p>@endif
            @foreach ($a->notas as $n)
            <div class="nota"><small>Nota aclaratoria · {{ $n->autor }} · {{ $n->created_at?->format('d/m/Y h:i a') }}</small>{{ $n->texto }}</div>
            @endforeach
            <p style="font-size:11.5px;color:#444;margin-top:6px">
                {{ $a->cerrada ? 'Firmada electrónicamente por' : 'BORRADOR SIN FIRMAR ·' }} {{ $a->profesional_nombre }}{{ $a->profesional_especialidad ? ' · ' . $a->profesional_especialidad : '' }}{{ $a->profesional_registro ? ' · Reg. ' . $a->profesional_registro : '' }}{{ $a->cerrada ? ' · ' . $a->cerrada_at->format('d/m/Y h:i a') : '' }}
                @if ($a->huella)<br>Huella de integridad: {{ substr($a->huella, 0, 16) }}…@endif</p>
        </div>
        @empty
        <p style="margin-top:14px">No hay atenciones cerradas.</p>
        @endforelse

        @php $ult = $atenciones->last(); @endphp
        @if ($ult && $doc === 'historia')
        <div class="firma"><div>
            @if (!empty($firmas[$ult->profesional_id]))<img src="{{ $firmas[$ult->profesional_id] }}" alt="Firma">@endif
            <div class="linea"><b>{{ $ult->profesional_nombre }}</b>@if ($ult->profesional_especialidad)<span>{{ $ult->profesional_especialidad }}</span>@endif @if ($ult->profesional_registro)<span>Registro profesional {{ $ult->profesional_registro }}</span>@endif</div>
        </div></div>
        @endif

    @elseif ($doc === 'formula')
        @php $a = $atenciones->first(); @endphp
        <p><b>Fecha:</b> {{ $a->fecha->format('d/m/Y') }}@if ($a->diagnosticos) · <b>Dx:</b> {{ collect($a->diagnosticos)->pluck('codigo')->filter()->implode(', ') }}@endif</p>
        <div class="rx"><span class="simbolo">℞</span><br>{!! nl2br(e($a->formula)) !!}</div>
        @if (!empty($a->contenido['plan']))<h3>Recomendaciones</h3><p>{{ $a->contenido['plan'] }}</p>@endif
        <div class="firma"><div>
            @if (!empty($firmas[$a->profesional_id]))<img src="{{ $firmas[$a->profesional_id] }}" alt="Firma">@endif
            <div class="linea"><b>{{ $a->profesional_nombre }}</b>@if ($a->profesional_especialidad)<span>{{ $a->profesional_especialidad }}</span>@endif @if ($a->profesional_registro)<span>Registro profesional {{ $a->profesional_registro }}</span>@endif</div>
        </div></div>

    @elseif ($doc === 'incapacidad')
        @php $a = $atenciones->first(); $hasta = $a->incapacidad_desde?->copy()->addDays(max(0, (int) $a->incapacidad_dias - 1)); @endphp
        <div class="cert">
            Se certifica que <b>{{ $p->nombre_completo }}</b>, identificado(a) con {{ \App\Models\Paciente::DOCUMENTOS[$p->tipo_documento] ?? $p->tipo_documento }} n.º <b>{{ $p->documento ?: '—' }}</b>,
            fue atendido(a) el {{ $a->fecha->format('d/m/Y') }} y requiere incapacidad por <b>{{ $a->incapacidad_dias }} día(s)</b>,
            desde el <b>{{ $a->incapacidad_desde?->format('d/m/Y') }}</b> hasta el <b>{{ $hasta?->format('d/m/Y') }}</b>, ambos inclusive.
            @if ($a->diagnosticos)<br>Diagnóstico (CIE-10): {{ collect($a->diagnosticos)->pluck('codigo')->filter()->implode(', ') }}@endif
        </div>
        <div class="firma"><div>
            @if (!empty($firmas[$a->profesional_id]))<img src="{{ $firmas[$a->profesional_id] }}" alt="Firma">@endif
            <div class="linea"><b>{{ $a->profesional_nombre }}</b>@if ($a->profesional_especialidad)<span>{{ $a->profesional_especialidad }}</span>@endif @if ($a->profesional_registro)<span>Registro profesional {{ $a->profesional_registro }}</span>@endif</div>
        </div></div>
    @endif

    <div class="pie"><span>Documento reservado: contiene datos de salud (Res. 1995 de 1999 · Ley 1581 de 2012).</span><span>{{ $tienda->nombre }}</span></div>
</div>
</body>
</html>
