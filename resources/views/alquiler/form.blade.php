@extends('layouts.app')

@php $nuevo = !$alquiler->exists; @endphp
@section('title', $nuevo ? 'Crear servicio en alquiler' : 'Editar alquiler')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-calendar-days me-1"></i>Alquiler</small>
            <h1>@if ($nuevo)Crear servicio en <span>alquiler</span>@else Editar <span>{{ $alquiler->numero }}</span>@endif</h1>
            <span class="text-muted fw-semibold">Registre qué se alquila, cuándo, dónde y quién responde.</span></div>
        <div class="lp-quick"><a href="{{ route('alquileres.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Alquiler</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ $nuevo ? route('alquileres.store') : route('alquileres.update', $alquiler) }}" class="lp-box" style="max-width:760px">
        @csrf
        @unless ($nuevo) @method('PUT') @endunless

        <h3><span><i class="fa-solid fa-tag me-1"></i> ¿Qué se alquila?</span></h3>
        <div class="mb-3">
            <input name="que_alquila" class="form-control form-control-lg" maxlength="120" required autofocus
                placeholder="Ej: Sonido para fiesta, salón de eventos, carpa 6x6, vestido de novia…" value="{{ old('que_alquila', $alquiler->que_alquila) }}">
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="form-label fw-bold">Fecha *</label>
                <input type="date" name="fecha" class="form-control" required value="{{ old('fecha', optional($alquiler->fecha)->format('Y-m-d')) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-bold">Hora *</label>
                <input type="time" name="hora" class="form-control" required value="{{ old('hora', $alquiler->hora ? substr($alquiler->hora, 0, 5) : '') }}">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Lugar *</label>
                <input name="lugar" class="form-control" maxlength="150" required placeholder="Dirección o sitio donde se presta el servicio" value="{{ old('lugar', $alquiler->lugar) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-bold">Número del responsable *</label>
                <input type="tel" name="responsable_telefono" class="form-control" required data-celular value="{{ old('responsable_telefono', $alquiler->responsable_telefono) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label fw-bold">Valor del alquiler *</label>
                <input name="valor" type="number" min="0" class="form-control" required data-dinero placeholder="0" value="{{ old('valor', $alquiler->exists ? $alquiler->valor : '') }}">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Nota <span class="text-muted fw-normal">(opcional)</span></label>
                <textarea name="nota" class="form-control" rows="2" maxlength="255" placeholder="Ej: incluye transporte, entregar 1 hora antes, abono pendiente…">{{ old('nota', $alquiler->nota) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('alquileres.index') }}" class="btn btn-light btn-lg">Cancelar</a>
            <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $nuevo ? 'Crear alquiler' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection
