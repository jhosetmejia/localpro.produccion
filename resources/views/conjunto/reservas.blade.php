@extends('layouts.app')

@section('title', 'Zonas comunes')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-building me-1"></i>Administración</small><h1>Reservas de <span>zonas comunes</span></h1>
            <span class="text-muted fw-semibold">Salón social, BBQ, piscina, canchas… sin cruces de horario</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if (empty($zonas))
    <div class="lp-box mb-3" style="background:#fff5d6;border-color:#f3e0a6">Primero escriba las zonas comunes en <a href="{{ route('conjunto.cobros') }}#">Cobros → Configuración</a> (ej. Salón social, BBQ, Piscina).</div>
    @else
    <div class="lp-box mb-3">
        <h3>Nueva reserva</h3>
        <form method="post" action="{{ route('conjunto.reservas.store') }}" class="row g-2">
            @csrf
            <div class="col-6 col-md-3"><select name="zona" class="form-select">@foreach ($zonas as $z)<option>{{ $z }}</option>@endforeach</select></div>
            <div class="col-6 col-md-3"><select name="unidad_id" class="form-select" required><option value="">Unidad…</option>@foreach ($unidades as $u)<option value="{{ $u->id }}" @selected((int) old('unidad_id') === $u->id)>{{ $u->nombre }}</option>@endforeach</select></div>
            <div class="col-12 col-md-2"><input type="date" name="fecha" class="form-control" min="{{ today()->format('Y-m-d') }}" value="{{ old('fecha', today()->format('Y-m-d')) }}" required></div>
            <div class="col-6 col-md-2"><input type="time" name="hora_inicio" class="form-control" value="{{ old('hora_inicio', '14:00') }}" required></div>
            <div class="col-6 col-md-2"><input type="time" name="hora_fin" class="form-control" value="{{ old('hora_fin', '18:00') }}" required></div>
            <div class="col-md-9"><input name="nota" class="form-control" maxlength="255" placeholder="Nota (ej. cumpleaños, 30 personas)"></div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary">Reservar</button></div>
        </form>
    </div>
    @endif
    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($lista as $r)
            <li style="{{ $r->estado === 'cancelada' ? 'opacity:.5' : '' }}"><div class="av"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="t"><b>{{ $r->zona }} · {{ $r->fecha->locale('es')->isoFormat('ddd D MMM') }} {{ substr($r->hora_inicio, 0, 5) }}–{{ substr($r->hora_fin, 0, 5) }}</b><small>{{ $r->unidad->nombre ?? '' }}{{ $r->nota ? ' · ' . $r->nota : '' }}{{ $r->estado === 'cancelada' ? ' · CANCELADA' : '' }}</small></div>
                @if ($r->estado === 'confirmada')<form method="post" action="{{ route('conjunto.reservas.cancelar', $r) }}" data-confirm="¿Cancelar esta reserva?" data-confirm-ok="Sí, cancelar" data-peligro>@csrf<button class="btn btn-sm btn-light">Cancelar</button></form>@endif</li>
            @empty
            <li class="lp-empty">Sin reservas.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
