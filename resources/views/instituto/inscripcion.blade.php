@extends('layouts.app')

@section('title', $e->id ? 'Editar estudiante' : 'Nueva inscripción')

@section('content')
@php
    $v = fn ($campo) => old($campo, $e->{$campo});
    $fecha = fn ($campo) => old($campo, $e->{$campo}?->format('Y-m-d'));
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>@if ($e->id)<a href="{{ route('instituto.estudiante', $e) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $e->nombre }}</a>@else<i class="fa-solid fa-school me-1"></i>Instituto @endif</small>
            <h1>{{ $e->id ? 'Editar datos del' : 'Formulario de' }} <span>{{ $e->id ? 'estudiante' : 'inscripción' }}</span></h1>
            <span class="text-muted fw-semibold">{{ $e->id ? 'Inscripción No. ' . $e->consecutivo : 'Los mismos datos del formato de papel. Solo nombres y apellidos son obligatorios.' }}</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ $e->id ? route('instituto.estudiantes.update', $e) : route('instituto.estudiantes.store') }}">
        @csrf @if ($e->id) @method('PUT') @endif

        <div class="lp-box mb-3">
            <h3>Programa técnico</h3>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Programa</label>
                    <select name="programa_id" class="form-select" id="ins-prog">
                        <option value="">— Elegir después —</option>
                        @foreach ($programas as $p)<option value="{{ $p->id }}" data-ins="{{ (int) $p->valor_inscripcion }}" @selected((int) $v('programa_id') === $p->id)>{{ $p->nombre }}</option>@endforeach
                    </select>
                    @if ($programas->isEmpty())<div class="small mt-1">Aún no hay programas: <a href="{{ route('instituto.programas') }}">créelos aquí</a>.</div>@endif
                </div>
                <div class="col-6 col-md-3"><label class="form-label">Jornada</label>
                    <select name="jornada" class="form-select"><option value="">—</option>@foreach (\App\Models\Instituto\Estudiante::JORNADAS as $k => $n)<option value="{{ $k }}" @selected($v('jornada') === $k)>{{ $n }}</option>@endforeach</select></div>
                <div class="col-6 col-md-3"><label class="form-label">Fecha de recepción</label><input type="date" name="fecha_inscripcion" class="form-control" value="{{ $fecha('fecha_inscripcion') }}"></div>
                @unless ($e->id)
                <div class="col-12" id="ins-cobro" hidden><div class="form-check"><input class="form-check-input" type="checkbox" name="cobrar_inscripcion" value="1" id="cobrar" checked><label class="form-check-label" for="cobrar">Cargar el cobro de la inscripción (<b id="ins-valor"></b>) a su estado de cuenta</label></div></div>
                @endunless
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>1. Información personal</h3>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Nombres *</label><input name="nombres" class="form-control" maxlength="80" required value="{{ $v('nombres') }}"></div>
                <div class="col-md-6"><label class="form-label">Apellidos *</label><input name="apellidos" class="form-control" maxlength="80" required value="{{ $v('apellidos') }}"></div>
                <div class="col-5 col-md-2"><label class="form-label">Documento</label><select name="tipo_doc" class="form-select">@foreach (\App\Models\Instituto\Estudiante::TIPOS_DOC as $k => $n)<option value="{{ $k }}" title="{{ $n }}" @selected($v('tipo_doc') === $k)>{{ $k }}</option>@endforeach</select></div>
                <div class="col-7 col-md-4"><label class="form-label">Número</label><input name="documento" class="form-control" maxlength="20" inputmode="numeric" value="{{ $v('documento') }}"></div>
                <div class="col-md-3"><label class="form-label">Lugar de expedición</label><input name="lugar_expedicion" class="form-control" maxlength="60" value="{{ $v('lugar_expedicion') }}"></div>
                <div class="col-md-3"><label class="form-label">Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" value="{{ $fecha('fecha_nacimiento') }}"></div>
                <div class="col-md-4"><label class="form-label">Lugar de nacimiento</label><input name="lugar_nacimiento" class="form-control" maxlength="60" value="{{ $v('lugar_nacimiento') }}"></div>
                <div class="col-6 col-md-3"><label class="form-label">Nacionalidad</label><input name="nacionalidad" class="form-control" maxlength="40" value="{{ $v('nacionalidad') }}"></div>
                <div class="col-6 col-md-3"><label class="form-label">Estado civil</label><select name="estado_civil" class="form-select"><option value="">—</option>@foreach (['Soltero(a)', 'Casado(a)', 'Unión libre', 'Separado(a)', 'Viudo(a)'] as $ec)<option @selected($v('estado_civil') === $ec)>{{ $ec }}</option>@endforeach</select></div>
                <div class="col-4 col-md-2"><label class="form-label">Estrato</label><select name="estrato" class="form-select"><option value="">—</option>@foreach (['1', '2', '3', '4', '5', '6'] as $es)<option @selected($v('estrato') === $es)>{{ $es }}</option>@endforeach</select></div>
                <div class="col-8 col-md-4"><label class="form-label">EPS o Sisbén</label><input name="eps" class="form-control" maxlength="60" value="{{ $v('eps') }}"></div>
                <div class="col-md-6"><label class="form-label">¿Tiene alguna enfermedad? ¿Cuál?</label><input name="enfermedad" class="form-control" maxlength="120" value="{{ $v('enfermedad') }}" placeholder="Vacío = no"></div>
                <div class="col-md-6"><label class="form-label">Dirección de residencia</label><input name="direccion" class="form-control" maxlength="120" value="{{ $v('direccion') }}"></div>
                <div class="col-6 col-md-3"><label class="form-label">Ciudad</label><input name="ciudad" class="form-control" maxlength="60" value="{{ $v('ciudad') }}"></div>
                <div class="col-6 col-md-3"><label class="form-label">Departamento</label><input name="departamento" class="form-control" maxlength="60" value="{{ $v('departamento') }}"></div>
                <div class="col-md-4"><label class="form-label">Celular (WhatsApp)</label><input name="celular" class="form-control" maxlength="20" inputmode="tel" value="{{ $v('celular') }}"></div>
                <div class="col-md-3"><label class="form-label">Teléfono fijo</label><input name="telefono" class="form-control" maxlength="20" inputmode="tel" value="{{ $v('telefono') }}"></div>
                <div class="col-md-5"><label class="form-label">Correo</label><input type="email" name="email" class="form-control" maxlength="120" value="{{ $v('email') }}"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>2. Información laboral actual <span class="text-muted fw-normal small">(si trabaja)</span></h3>
            <div class="row g-2">
                <div class="col-md-5"><label class="form-label">Entidad</label><input name="lab_entidad" class="form-control" maxlength="80" value="{{ $v('lab_entidad') }}"></div>
                <div class="col-md-4"><label class="form-label">Cargo</label><input name="lab_cargo" class="form-control" maxlength="60" value="{{ $v('lab_cargo') }}"></div>
                <div class="col-md-3"><label class="form-label">Teléfono</label><input name="lab_telefono" class="form-control" maxlength="20" value="{{ $v('lab_telefono') }}"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>3. Información académica</h3>
            <div class="row g-2">
                <div class="col-md-4"><label class="form-label">Título de bachiller</label><input name="bach_titulo" class="form-control" maxlength="80" value="{{ $v('bach_titulo') }}" placeholder="Ej: Bachiller académico"></div>
                <div class="col-md-4"><label class="form-label">Institución</label><input name="bach_institucion" class="form-control" maxlength="120" value="{{ $v('bach_institucion') }}"></div>
                <div class="col-6 col-md-2"><label class="form-label">Ciudad</label><input name="bach_ciudad" class="form-control" maxlength="60" value="{{ $v('bach_ciudad') }}"></div>
                <div class="col-6 col-md-2"><label class="form-label">Año</label><input name="bach_fecha" class="form-control" maxlength="20" value="{{ $v('bach_fecha') }}"></div>
                <div class="col-md-8"><label class="form-label">Si no es bachiller: terminó grado noveno en</label><input name="noveno_institucion" class="form-control" maxlength="120" value="{{ $v('noveno_institucion') }}"></div>
                <div class="col-md-4"><label class="form-label">Año grado noveno</label><input name="noveno_fecha" class="form-control" maxlength="20" value="{{ $v('noveno_fecha') }}"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>4. Acudiente <span class="text-muted fw-normal small">(obligatorio si es menor de edad)</span></h3>
            <div class="row g-2">
                <div class="col-md-3"><label class="form-label">Parentesco</label><input name="acu_parentesco" class="form-control" maxlength="30" value="{{ $v('acu_parentesco') }}" placeholder="Madre, padre…"></div>
                <div class="col-md-5"><label class="form-label">Nombres y apellidos</label><input name="acu_nombre" class="form-control" maxlength="100" value="{{ $v('acu_nombre') }}"></div>
                <div class="col-md-4"><label class="form-label">Teléfono</label><input name="acu_telefono" class="form-control" maxlength="20" value="{{ $v('acu_telefono') }}"></div>
            </div>
        </div>

        <div class="lp-box mb-3">
            <h3>Autorizaciones</h3>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="autoriza_datos" value="1" id="ad" @checked(old('autoriza_datos', $e->autoriza_datos ?? false))><label class="form-check-label" for="ad">Autoriza el <b>tratamiento de sus datos personales</b> (Ley 1581 de 2012) para fines académicos, laborales y administrativos del instituto.</label></div>
            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="autoriza_imagen" value="1" id="ai" @checked(old('autoriza_imagen', $e->autoriza_imagen ?? false))><label class="form-check-label" for="ai">Autoriza el <b>uso de su imagen</b> (fotos y videos) en la difusión de los programas del instituto.</label></div>
            <div class="small text-muted mt-2">Igual queda la firma en el formato impreso. <label class="mt-2 d-block">Notas internas <input name="notas" class="form-control" maxlength="255" value="{{ $v('notas') }}"></label></div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $e->id ? 'Guardar cambios' : 'Guardar inscripción' }}</button>
            <a href="{{ $e->id ? route('instituto.estudiante', $e) : route('instituto.estudiantes') }}" class="btn btn-light btn-lg">Cancelar</a>
        </div>
    </form>
</div>
@unless ($e->id)
@push('js')
<script>
(function () {
    var s = document.getElementById('ins-prog'), box = document.getElementById('ins-cobro'), val = document.getElementById('ins-valor');
    if (!s || !box) return;
    var fmt = new Intl.NumberFormat('es-CO');
    function ver() { var o = s.options[s.selectedIndex], n = +(o && o.dataset.ins || 0); box.hidden = !n; val.textContent = '$' + fmt.format(n); }
    s.addEventListener('change', ver); ver();
})();
</script>
@endpush
@endunless
@endsection
