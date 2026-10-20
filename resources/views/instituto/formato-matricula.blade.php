<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Matrícula {{ $m->periodo_texto }} · {{ $e->nombre }}</title>
@include('instituto._formato_css')
</head>
<body>
@php
    $J = \App\Models\Instituto\Estudiante::JORNADAS;
    $x = fn ($c) => $c ? 'X' : '';
    [$anio, $per] = array_pad(explode('-', $m->periodo), 2, '1');
    $dia = $inst->inst_dia_pago ?: 5;
@endphp
<div class="bar"><button onclick="window.print()">🖨️ Imprimir / guardar PDF</button><a href="{{ route('instituto.estudiante', $e) }}">Volver</a></div>
<div class="hoja">
    @include('instituto._formato_enc', ['titulo' => 'FORMATO DE MATRÍCULA', 'codigo' => 'Matrícula'])
    <div style="display:flex;justify-content:space-between;margin-top:8px">
        <table style="width:auto"><tr><td style="height:auto"><small>Fecha</small><b>{{ $m->fecha->format('d/m/Y') }}</b></td></tr></table>
        <b style="align-self:center">PERIODO <span class="chk">{{ $x($per === '1') }}</span>I &nbsp; <span class="chk">{{ $x($per === '2') }}</span>II &nbsp; DE {{ $anio }}</b>
    </div>
    <h4 style="text-align:center">DATOS PERSONALES</h4>
    <table>
        <tr><td colspan="2"><small>Apellidos</small><b>{{ $e->apellidos }}</b></td><td colspan="2"><small>Nombres</small><b>{{ $e->nombres }}</b></td></tr>
        <tr><td colspan="2"><small>No. documento de identidad</small><b>{{ $e->tipo_doc }} {{ $e->documento }}</b></td><td><small>Lugar expedición documento</small><b>{{ $e->lugar_expedicion }}</b></td><td><small>Teléfono fijo</small><b>{{ $e->telefono }}</b></td></tr>
        <tr><td colspan="2"><small>No. celular</small><b>{{ $e->celular }}</b></td><td colspan="2"><small>E-mail</small><b>{{ $e->email }}</b></td></tr>
        <tr><td colspan="4"><small>¿Tiene alguna enfermedad?</small><b>No <span class="chk">{{ $x(!$e->enfermedad) }}</span> Sí <span class="chk">{{ $x($e->enfermedad) }}</span> ¿Cuál? {{ $e->enfermedad }}</b></td></tr>
    </table>

    <h4 style="text-align:center">INFORMACIÓN MATRÍCULA · MATRÍCULA ACADÉMICA</h4>
    @include('instituto._formato_programas', ['sel' => $m->programa_id, 'selNombre' => $m->programa_nombre])
    <table style="margin-top:8px">
        <tr><td><small>Semestre</small><b>{{ $m->semestre }}</b></td><td colspan="3"><small>Jornada</small><b>@foreach ($J as $k => $n)<span class="chk">{{ $x($m->jornada === $k) }}</span>{{ $n }} &nbsp; @endforeach</b></td></tr>
        <tr><td colspan="4" style="height:44px"><small>Módulos pendientes</small><b>{!! nl2br(e($m->modulos_pendientes)) !!}</b></td></tr>
    </table>

    <h4 style="text-align:center">MATRÍCULA FINANCIERA</h4>
    <table>
        <tr>
            <td><small>CONTADO: Valor semestre</small><b>{{ $m->modalidad === 'contado' ? dinero($m->valor_semestre) : '' }}</b></td>
            <td colspan="2"><small>CRÉDITO: Valor semestre</small><b>{{ $m->modalidad === 'credito' ? dinero($m->valor_semestre) : '' }}</b></td>
        </tr>
        <tr>
            <td><small>Cuota inicial</small><b>{{ $m->modalidad === 'credito' ? dinero($m->cuota_inicial) : '' }}</b></td>
            <td><small>No. cuotas</small><b>{{ $m->modalidad === 'credito' ? $m->num_cuotas : '' }}</b></td>
            <td><small>Valor cuotas mensuales</small><b>{{ $m->modalidad === 'credito' ? dinero($m->valor_cuota) : '' }}</b></td>
        </tr>
    </table>
    <p style="margin:6px 0 0"><b>Nota:</b> Las cuotas se deben cancelar del 01 al {{ str_pad((string) $dia, 2, '0', STR_PAD_LEFT) }} de cada mes. El incumplimiento ocasiona la cancelación del crédito.</p>

    <div class="firmas"><div>Firma estudiante</div><div>Firma acudiente</div><div>Vo. Bo. administrativo</div></div>

    <div class="legal" style="border-top:1px dashed #333;padding-top:8px;margin-top:22px">
        <b>AUTORIZACIÓN USO DE IMÁGENES Y CONSENTIMIENTO INFORMADO PARA PARTICIPAR EN PROCESOS DE DIFUSIÓN Y DIVULGACIÓN DE LOS PROGRAMAS.</b>
        Yo, <u>&nbsp;{{ $e->nombre }}&nbsp;</u>, identificado(a) con documento No. <u>&nbsp;{{ $e->documento }}&nbsp;</u>, expedido en <u>&nbsp;{{ $e->lugar_expedicion }}&nbsp;</u>,
        AUTORIZO a {{ $inst->nombre }} para incluir, reproducir y divulgar, a través de cualquier medio y sin reparo de su alcance, el material audiovisual (fotografías, grabaciones) que me sea realizado en el desarrollo del programa, teniendo en cuenta el uso exclusivamente pedagógico e institucional que dará al mismo, y la consecuente garantía del respeto a mi dignidad e intimidad en su divulgación. <span class="chk">{{ $x($e->autoriza_imagen) }}</span><br><br>
        También autorizo libre, previa, expresa y debidamente informada, para que permita el tratamiento de mis datos personales para el cumplimiento del objeto de la prestación de servicios educativos y académicos, finalidades laborales y formativas o científicas, y las demás señaladas en la política para el manejo y tratamiento de datos personales (Ley 1581 de 2012). <span class="chk">{{ $x($e->autoriza_datos) }}</span><br><br>
        Igualmente me comprometo a leer el manual de convivencia que se encuentra en la página web de la institución y a acepto las disposiciones contenidas en el mismo. Entiendo que el incumplimiento del manual de convivencia puede conllevar sanciones disciplinarias.<br><br>
        En constancia de lo anterior, firmo a los ______ días del mes de ________________ del año ______.
    </div>
    <div class="firmas" style="align-items:flex-end"><div>Firma del estudiante</div><div style="border:0"><div class="huella"></div><small>Huella</small></div><div>Firma acudiente</div></div>
</div>
</body>
</html>
