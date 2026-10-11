@extends('layouts.app')

@section('title', 'PQRS')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>PQRS y <span>daños</span></h1>
            <span class="text-muted fw-semibold">Peticiones, quejas, reclamos, sugerencias y reportes de daños</span></div>
        <div class="lp-quick">
            @foreach (['abiertas' => 'Abiertas', 'cerradas' => 'Cerradas', 'todas' => 'Todas'] as $k => $v)
            <a href="{{ route('conjunto.pqrs', ['estado' => $k]) }}" class="btn btn-sm {{ $estado === $k ? 'btn-dark' : 'btn-light' }}">{{ $v }}</a>
            @endforeach
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    <details class="lp-box mb-3">
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-plus me-1" style="color:#16b34a"></i> Registrar solicitud</summary>
        <form method="post" action="{{ route('conjunto.pqrs.store') }}" class="row g-2 mt-1">
            @csrf
            <div class="col-6"><select name="tipo" class="form-select">@foreach (\App\Models\Conjunto\Pqrs::TIPOS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-6"><select name="unidad_id" class="form-select"><option value="">General (sin unidad)</option>@foreach ($unidades as $u)<option value="{{ $u->id }}">{{ $u->nombre }}</option>@endforeach</select></div>
            <div class="col-12"><input name="asunto" class="form-control" maxlength="120" required placeholder="Asunto"></div>
            <div class="col-12"><textarea name="detalle" class="form-control" rows="3" maxlength="3000" placeholder="Detalle"></textarea></div>
            <div class="col-12"><button class="btn btn-primary">Guardar</button></div>
        </form>
    </details>
    @forelse ($lista as $q)
    <div class="lp-box mb-2">
        <h3 class="mb-1"><span>{{ $q->asunto }}</span><span class="badge {{ $q->estado === 'cerrada' ? 'bg-success' : ($q->estado === 'en_proceso' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ \App\Models\Conjunto\Pqrs::ESTADOS[$q->estado] }}</span></h3>
        <small class="text-muted fw-semibold">{{ \App\Models\Conjunto\Pqrs::TIPOS[$q->tipo] ?? '' }} · {{ $q->unidad->nombre ?? 'General' }} · {{ $q->created_at->locale('es')->diffForHumans() }}</small>
        @if ($q->detalle)<p class="mt-2 mb-2" style="white-space:pre-line">{{ $q->detalle }}</p>@endif
        <form method="post" action="{{ route('conjunto.pqrs.update', $q) }}" class="row g-2 mt-1">
            @csrf @method('PUT')
            <div class="col-md-8"><input name="respuesta" class="form-control form-control-sm" maxlength="3000" value="{{ $q->respuesta }}" placeholder="Respuesta / qué se hizo"></div>
            <div class="col-6 col-md-2"><select name="estado" class="form-select form-select-sm">@foreach (\App\Models\Conjunto\Pqrs::ESTADOS as $k => $v)<option value="{{ $k }}" @selected($q->estado === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-sm btn-dark">Guardar</button></div>
        </form>
    </div>
    @empty
    <div class="lp-box"><p class="lp-empty mb-0">No hay solicitudes aquí.</p></div>
    @endforelse
</div>
@endsection
