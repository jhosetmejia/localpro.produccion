@extends('layouts.app')

@section('title', 'Pacientes')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-user-doctor me-1"></i>Consultorio</small><h1><span>Pacientes</span></h1>
            <span class="text-muted fw-semibold">{{ $pacientes->total() }} paciente(s)</span></div>
        <div class="lp-quick"><a href="{{ route('consultorio.pacientes.create') }}" class="cs-btn prim"><i class="fa-solid fa-user-plus"></i>Nuevo paciente</a></div>
    </div>
    @include('consultorio._nav')

    <div class="cs-card">
        <form method="get" class="mb-2"><input name="q" class="form-control" value="{{ $q }}" placeholder="Buscar por nombre, documento o celular…" autofocus></form>
        @forelse ($pacientes as $p)
        <a href="{{ route('consultorio.paciente', $p) }}" class="cs-pac">
            <div class="cs-av">{{ mb_strtoupper(mb_substr($p->nombres, 0, 1) . mb_substr((string) $p->apellidos, 0, 1)) }}</div>
            <div class="tx"><b>{{ $p->nombre_completo }}</b>
                <small>{{ trim($p->tipo_documento . ' ' . $p->documento) ?: 'Sin documento' }}{{ $p->edad ? ' · ' . $p->edad : '' }}{{ $p->telefono ? ' · ' . $p->telefono : '' }}</small></div>
            @if ($p->atenciones_count)<span class="small fw-bold text-muted text-nowrap"><i class="fa-solid fa-file-medical me-1"></i>{{ $p->atenciones_count }}</span>@endif
        </a>
        @empty
        <div class="lp-empty">{{ $q ? 'No hay pacientes con "' . $q . '".' : 'Aún no hay pacientes.' }} <a href="{{ route('consultorio.pacientes.create') }}">Crear paciente</a></div>
        @endforelse
        <div class="mt-2">{{ $pacientes->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
