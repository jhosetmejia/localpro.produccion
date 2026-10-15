@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('nomina.index') }}">Nómina</a></small><h1>Empleados</h1></div>
        <div class="lp-quick"><a href="{{ route('nomina.empleados.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>Nuevo empleado</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="mb-3 d-flex gap-2">
        @foreach (['activos' => 'Activos', 'retirados' => 'Retirados', 'todos' => 'Todos'] as $k => $txt)
        <a href="?ver={{ $k }}" class="btn btn-sm {{ $ver === $k ? 'btn-dark' : 'btn-light' }}">{{ $txt }}</a>
        @endforeach
    </div>

    <div class="lp-box">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Empleado</th><th>Cargo</th><th>Salario</th><th>Liquidación</th><th>DIAN</th><th>Ingreso</th><th></th></tr></thead>
                <tbody>
                @forelse ($empleados as $e)
                <tr class="{{ $e->activo ? '' : 'opacity-50' }}">
                    <td><b>{{ $e->nombre }}</b><small class="d-block text-muted">{{ $e->documento }}</small></td>
                    <td>{{ $e->cargo ?: '—' }}</td>
                    <td class="fw-bold">@pesos($e->salario)
                        @if ($e->salario < $anio['smmlv'] && !str_starts_with($e->tipo, 'aprendiz'))<small class="d-block" style="color:#b0206a">Menos del mínimo</small>@endif
                    </td>
                    <td>@if ($e->personalizado)<span class="badge" style="background:#fef3c7;color:#92600a">Personalizada</span>@else<span class="badge bg-success">Legal</span>@endif</td>
                    <td>@if ($tienda->nomina_modo === 'electronica')<small>{{ $e->enviar_dian ? 'Se reporta' : 'No se reporta' }}</small>@else<small class="text-muted">—</small>@endif</td>
                    <td><small>{{ $e->fecha_ingreso?->format('d/m/Y') }}</small></td>
                    <td class="text-end"><a href="{{ route('nomina.empleados.edit', $e) }}" class="btn btn-sm btn-dark"><i class="fa-solid fa-pen"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="lp-empty">No hay empleados registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
