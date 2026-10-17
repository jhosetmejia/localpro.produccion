@extends('layouts.app')

@section('title', $p ? 'Editar paciente' : 'Nuevo paciente')

@push('css')
<style>
.pf label{font-weight:800;font-size:.8rem;color:var(--lp-muted);margin-bottom:.2rem;display:block}
.pf h4{font-size:.95rem;font-weight:900;margin:1.2rem 0 .6rem;display:flex;align-items:center;gap:.4rem}
.pf h4:first-child{margin-top:0}
.pf .aut{display:flex;gap:.6rem;align-items:flex-start;background:#f4f8f5;border-radius:14px;padding:.7rem .9rem;font-size:.85rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ $p ? route('consultorio.paciente', $p) : route('consultorio.pacientes') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p ? $p->nombre_completo : 'Pacientes' }}</a></small>
            <h1>{{ $p ? 'Editar' : 'Nuevo' }} <span>paciente</span></h1></div>
    </div>
    @include('consultorio._nav')

    <form method="post" action="{{ $p ? route('consultorio.pacientes.update', $p) : route('consultorio.pacientes.store') }}" class="cs-card pf">
        @csrf @if ($p) @method('PUT') @endif
        <input type="hidden" name="despues" value="{{ request('despues') }}">
        <h4><i class="fa-solid fa-id-card" style="color:#16b34a"></i>Identificación</h4>
        <div class="row g-2">
            <div class="col-5 col-md-3"><label>Documento</label>
                <select name="tipo_documento" class="form-select">@foreach (\App\Models\Paciente::DOCUMENTOS as $k => $v)<option value="{{ $k }}" @selected(old('tipo_documento', $p->tipo_documento ?? 'CC') === $k)>{{ $k }} · {{ $v }}</option>@endforeach</select></div>
            <div class="col-7 col-md-3"><label>Número</label><input name="documento" class="form-control" value="{{ old('documento', $p->documento ?? '') }}" maxlength="30" inputmode="numeric"></div>
            <div class="col-md-3"><label>Nombres *</label><input name="nombres" class="form-control" value="{{ old('nombres', $p->nombres ?? '') }}" required maxlength="80"></div>
            <div class="col-md-3"><label>Apellidos</label><input name="apellidos" class="form-control" value="{{ old('apellidos', $p->apellidos ?? '') }}" maxlength="80"></div>
            <div class="col-6 col-md-3"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento', optional($p?->fecha_nacimiento)->toDateString()) }}" max="{{ today()->toDateString() }}"></div>
            <div class="col-6 col-md-3"><label>Sexo</label>
                <select name="sexo" class="form-select"><option value="">—</option>@foreach (['F' => 'Femenino', 'M' => 'Masculino', 'I' => 'Intersexual'] as $k => $v)<option value="{{ $k }}" @selected(old('sexo', $p->sexo ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><label>EPS o prepagada</label><input name="aseguradora" class="form-control" value="{{ old('aseguradora', $p->aseguradora ?? '') }}" maxlength="80" placeholder="Informativo"></div>
            <div class="col-md-3"><label>Ocupación</label><input name="ocupacion" class="form-control" value="{{ old('ocupacion', $p->ocupacion ?? '') }}" maxlength="80"></div>
        </div>

        <h4><i class="fa-solid fa-phone" style="color:#16b34a"></i>Contacto</h4>
        <div class="row g-2">
            <div class="col-md-3"><label>Celular (WhatsApp)</label><input name="telefono" class="form-control" value="{{ old('telefono', $p->telefono ?? '') }}" maxlength="30" inputmode="tel"></div>
            <div class="col-md-3"><label>Correo</label><input type="email" name="correo" class="form-control" value="{{ old('correo', $p->correo ?? '') }}" maxlength="120"></div>
            <div class="col-md-3"><label>Dirección</label><input name="direccion" class="form-control" value="{{ old('direccion', $p->direccion ?? '') }}" maxlength="160"></div>
            <div class="col-md-3"><label>Ciudad</label><input name="ciudad" class="form-control" value="{{ old('ciudad', $p->ciudad ?? '') }}" maxlength="80"></div>
            <div class="col-12"><label>Acompañante o contacto de emergencia</label><input name="acompanante" class="form-control" value="{{ old('acompanante', $p->acompanante ?? '') }}" maxlength="160" placeholder="Nombre, parentesco y celular"></div>
        </div>

        @if ($clinico)
        <h4><i class="fa-solid fa-lock" style="color:#e11d48"></i>Datos clínicos <small class="text-muted fw-bold" style="font-size:.72rem">solo los ven los profesionales · se guardan cifrados</small></h4>
        <div class="row g-2">
            <div class="col-12"><label>Alergias</label><input name="alergias" class="form-control" value="{{ old('alergias', $p->alergias ?? '') }}" maxlength="2000" placeholder="Ej. Penicilina, AINES, látex. Escriba &quot;Niega&quot; si no tiene."></div>
            @foreach (\App\Models\Paciente::ANTECEDENTES as $k => $v)
            <div class="col-md-6"><label>{{ $v }}</label><textarea name="antecedentes[{{ $k }}]" class="form-control" rows="2" maxlength="3000">{{ old('antecedentes.' . $k, $p->antecedentes[$k] ?? '') }}</textarea></div>
            @endforeach
            <div class="col-12"><label>Notas</label><textarea name="notas" class="form-control" rows="2" maxlength="3000">{{ old('notas', $p->notas ?? '') }}</textarea></div>
        </div>
        @endif

        <h4><i class="fa-solid fa-shield-halved" style="color:#16b34a"></i>Tratamiento de datos</h4>
        @if ($p?->autoriza_datos_at)
        <div class="aut"><i class="fa-solid fa-circle-check text-success mt-1"></i><span>Autorizó el tratamiento de sus datos el {{ $p->autoriza_datos_at->format('d/m/Y h:i a') }}.</span></div>
        @else
        <label class="aut"><input type="checkbox" name="autoriza_datos" value="1" class="form-check-input mt-1" @checked(old('autoriza_datos'))>
            <span style="font-weight:600;color:var(--lp-ink)">El paciente autoriza el tratamiento de sus datos personales y de salud para su atención, según la Ley 1581 de 2012 y la política de datos del consultorio.</span></label>
        @endif

        <div class="d-flex gap-2 mt-3">
            <button class="cs-btn prim"><i class="fa-solid fa-floppy-disk"></i>{{ request('despues') === 'cita' ? 'Guardar y agendar cita' : 'Guardar paciente' }}</button>
            <a href="{{ $p ? route('consultorio.paciente', $p) : route('consultorio.pacientes') }}" class="cs-btn">Cancelar</a>
        </div>
    </form>
</div>
@endsection
