@extends('layouts.app')

@section('title', 'Programas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-school me-1"></i>Instituto</small><h1>Programas <span>académicos</span></h1>
            <span class="text-muted fw-semibold">{{ $programas->where('activo', true)->count() }} activos</span></div>
        @if ($programas->isEmpty())
        <form method="post" action="{{ route('instituto.programas.ejemplo') }}" class="lp-quick">@csrf<button class="btn btn-dark"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Cargar programas técnicos de ejemplo</button></form>
        @endif
    </div>
    @include('layouts.partials.lp-flash')

    <details class="lp-box mb-3" @if ($programas->isEmpty()) open @endif>
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-plus me-1" style="color:#16b34a"></i> Agregar un programa</summary>
        <form method="post" action="{{ route('instituto.programas.store') }}" class="row g-2 mt-1">
            @csrf
            @include('instituto._form_programa', ['p' => null])
            <div class="col-12"><button class="btn btn-primary">Guardar programa</button></div>
        </form>
    </details>

    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($programas as $p)
            <li style="flex-wrap:wrap;{{ $p->activo ? '' : 'opacity:.55' }}">
                <div class="av"><i class="fa-solid fa-book-open"></i></div>
                <div class="t"><b>{{ $p->nombre }}</b><small>{{ $p->tipo }} · {{ $p->semestres }} semestre(s) · semestre {{ $p->valor_semestre > 0 ? dinero($p->valor_semestre) : 'sin valor' }}{{ $p->valor_inscripcion > 0 ? ' · inscripción ' . dinero($p->valor_inscripcion) : '' }} · {{ $p->matriculados }} matriculado(s){{ $p->activo ? '' : ' · inactivo' }}</small></div>
                <details style="width:100%">
                    <summary class="small fw-bold" style="cursor:pointer;color:#16b34a">Editar</summary>
                    <form method="post" action="{{ route('instituto.programas.update', $p) }}" class="row g-2 mt-1">
                        @csrf @method('PUT')
                        @include('instituto._form_programa', ['p' => $p])
                        <div class="col-12"><input type="hidden" name="activo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" id="pa{{ $p->id }}" @checked($p->activo)><label class="form-check-label" for="pa{{ $p->id }}">Activo (se puede inscribir y matricular)</label></div></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm">Guardar</button></div>
                    </form>
                </details>
            </li>
            @empty
            <li class="lp-empty">Aún no hay programas. Agréguelos arriba o cargue los de ejemplo.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
