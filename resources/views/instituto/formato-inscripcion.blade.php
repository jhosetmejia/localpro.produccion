<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inscripción {{ $e->consecutivo }} · {{ $e->nombre }}</title>
@include('instituto._formato_css')
</head>
<body>
<div class="bar"><button onclick="window.print()">🖨️ Imprimir / guardar PDF</button><a href="{{ route('instituto.estudiante', $e) }}">Volver</a></div>
<div class="hoja">
    @include('instituto._formato_enc', ['titulo' => 'FORMULARIO DE INSCRIPCIÓN', 'codigo' => 'Inscripción'])
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:10px">
        <div style="width:96px;height:110px;border:1px solid #333;border-radius:12px;display:grid;place-items:center;color:#777;font-size:10px">FOTO</div>
        <div style="text-align:right;line-height:2">
            <b>Fecha de recepción:</b> {{ $e->fecha_inscripcion?->format('d/m/Y') ?: '__________' }}<br>
            <b>Consecutivo No.:</b> {{ $e->consecutivo }}
        </div>
    </div>

    <h4>1. INFORMACIÓN PERSONAL</h4>
    <table>
        <tr><td colspan="2"><small>Apellidos</small><b>{{ $e->apellidos }}</b></td><td colspan="2"><small>Nombres</small><b>{{ $e->nombres }}</b></td></tr>
        <tr><td colspan="2"><small>Lugar y fecha de nacimiento</small><b>{{ $e->lugar_nacimiento }} {{ $e->fecha_nacimiento?->format('d/m/Y') }}</b></td><td><small>Nacionalidad</small><b>{{ $e->nacionalidad }}</b></td><td><small>Estado civil</small><b>{{ $e->estado_civil }}</b></td></tr>
        <tr><td colspan="2"><small>Documento de identidad No.</small><b>{{ $e->tipo_doc }} {{ $e->documento }}</b>{{ $e->lugar_expedicion ? ' · de ' . $e->lugar_expedicion : '' }}</td><td><small>Estrato</small><b>{{ $e->estrato }}</b></td><td><small>EPS o Sisbén</small><b>{{ $e->eps }}</b></td></tr>
        <tr><td colspan="2"><small>Dirección de residencia</small><b>{{ $e->direccion }}</b></td><td><small>Ciudad</small><b>{{ $e->ciudad }}</b></td><td><small>Departamento</small><b>{{ $e->departamento }}</b></td></tr>
        <tr><td colspan="2"><small>E-mail</small><b>{{ $e->email }}</b></td><td colspan="2"><small>Teléfono y celular</small><b>{{ trim($e->telefono . ' ' . $e->celular) }}</b></td></tr>
    </table>

    @include('instituto._formato_programas', ['sel' => $e->programa_id, 'selNombre' => $e->programa->nombre ?? ''])

    <h4>2. INFORMACIÓN LABORAL ACTUAL</h4>
    <table><tr><td><small>Entidad</small><b>{{ $e->lab_entidad }}</b></td><td><small>Cargo</small><b>{{ $e->lab_cargo }}</b></td><td><small>Teléfonos</small><b>{{ $e->lab_telefono }}</b></td></tr></table>

    <h4>3. INFORMACIÓN ACADÉMICA</h4>
    <table>
        <tr><td><small>Estudios bachiller · Título obtenido</small><b>{{ $e->bach_titulo }}</b></td><td><small>Ciudad</small><b>{{ $e->bach_ciudad }}</b></td><td><small>Institución donde realizó sus estudios</small><b>{{ $e->bach_institucion }}</b></td><td><small>Fecha</small><b>{{ $e->bach_fecha }}</b></td></tr>
        <tr><td colspan="2"><small>Terminación grado noveno</small><b>{{ $e->noveno_institucion }}</b></td><td colspan="2"><small>Fecha</small><b>{{ $e->noveno_fecha }}</b></td></tr>
    </table>

    <h4>4. INFORMACIÓN ACUDIENTE</h4>
    <table><tr><td><small>Parentesco</small><b>{{ $e->acu_parentesco }}</b></td><td><small>Apellidos y nombres</small><b>{{ $e->acu_nombre }}</b></td><td><small>Teléfono</small><b>{{ $e->acu_telefono }}</b></td></tr></table>

    <p class="legal" style="font-size:11px;margin-top:16px"><b>COMPROMISO.</b> Yo, <u>&nbsp;{{ $e->nombre }}&nbsp;</u>, me comprometo a leer el manual de convivencia que se encuentra en la página web de la institución y acepto las disposiciones contenidas en el mismo. Entiendo que el incumplimiento del manual de convivencia puede conllevar sanciones disciplinarias.</p>

    <div class="firmas" style="max-width:300px"><div>Firma del aspirante</div></div>
    @if ($inst->descripcion && mb_strlen($inst->descripcion) <= 90)<div class="lema">"{{ mb_strtoupper($inst->descripcion) }}"</div>@endif
</div>
</body>
</html>
