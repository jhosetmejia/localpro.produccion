@extends('layouts.app')

@section('title', 'Estudiantes')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-school me-1"></i>Instituto</small><h1>Estudiantes y <span>aspirantes</span></h1>
            <span class="text-muted fw-semibold">{{ (int) ($conteo['matriculado'] ?? 0) }} matriculados · {{ (int) ($conteo['aspirante'] ?? 0) }} aspirantes</span></div>
        <div class="lp-quick"><a href="{{ route('instituto.inscripcion') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>Nueva inscripción</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-box">
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-5"><input name="q" class="form-control" placeholder="Buscar por nombre, documento, celular o No. de inscripción" value="{{ $q }}"></div>
            <div class="col-6 col-md-3"><select name="estado" class="form-select" onchange="this.form.submit()"><option value="">Todos los estados</option>@foreach (\App\Models\Instituto\Estudiante::ESTADOS as $k => $v)<option value="{{ $k }}" @selected($estado === $k)>{{ $v }} ({{ (int) ($conteo[$k] ?? 0) }})</option>@endforeach</select></div>
            <div class="col-6 col-md-3"><select name="programa" class="form-select" onchange="this.form.submit()"><option value="">Todos los programas</option>@foreach ($programas as $p)<option value="{{ $p->id }}" @selected($programa === $p->id)>{{ $p->nombre }}</option>@endforeach</select></div>
            <div class="col-md-1 d-grid"><button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button></div>
        </form>
        <ul class="lp-list">
            @forelse ($estudiantes as $e)
            @php $s = (float) ($saldos[$e->id] ?? 0); @endphp
            <li>
                <div class="av" style="{{ $e->estado === 'aspirante' ? 'background:#fff5d6;color:#8a6100' : '' }}"><i class="fa-solid {{ $e->estado === 'aspirante' ? 'fa-user-clock' : 'fa-user-graduate' }}"></i></div>
                <div class="t"><b><a href="{{ route('instituto.estudiante', $e) }}" class="text-decoration-none" style="color:inherit">{{ $e->apellidos }} {{ $e->nombres }}</a></b>
                    <small>{{ $e->tipo_doc }} {{ $e->documento ?: '—' }} · {{ $e->programa->nombre ?? 'Sin programa' }} · {{ \App\Models\Instituto\Estudiante::ESTADOS[$e->estado] ?? $e->estado }}</small></div>
                <span class="fw-bold {{ $s > 0 ? 'text-danger' : 'text-success' }}" style="white-space:nowrap">{{ $s > 0 ? dinero($s) : 'Al día' }}</span>
            </li>
            @empty
            <li class="lp-empty">No hay estudiantes{{ $q !== '' || $estado || $programa ? ' con esos filtros' : '. Empiece con una inscripción' }}.</li>
            @endforelse
        </ul>
        <div class="mt-3">{{ $estudiantes->links() }}</div>
    </div>
</div>
@endsection
