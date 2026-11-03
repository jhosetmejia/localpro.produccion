@extends('layouts.app')

@section('title', 'Comisiones por odontólogo')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hand-holding-dollar me-1"></i>Consultorio odontológico</small><h1>Comisiones por <span>odontólogo</span></h1>
            <span class="text-muted fw-semibold">Lo que produjo cada profesional en el mes (procedimientos marcados como hechos) y su comisión.</span></div>
        <form method="get" class="d-flex gap-2"><input type="month" name="mes" value="{{ $mes->format('Y-m') }}" class="form-control" onchange="this.form.submit()" style="border-radius:999px"></form>
    </div>
    @include('consultorio._nav')
    @include('odontologia._sub')

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="cs-card">
                <h3>{{ ucfirst($mes->translatedFormat('F Y')) }} <small>Total producido {{ dinero($filas->sum('produccion')) }} · Comisiones {{ dinero($filas->sum('comision')) }}</small></h3>
                @forelse ($filas as $f)
                <details class="py-2 border-bottom">
                    <summary class="d-flex justify-content-between align-items-center gap-2" style="cursor:pointer;list-style:none">
                        <span><b>{{ $f['nombre'] }}</b><br><small class="text-muted">{{ $f['procedimientos'] }} procedimiento(s) · {{ $atenciones[$f['id']] ?? 0 }} atención(es) cerrada(s)</small></span>
                        <span class="text-end"><small class="text-muted d-block">Producción {{ dinero($f['produccion']) }} × {{ rtrim(rtrim(number_format($f['porcentaje'], 2, ',', '.'), '0'), ',') }} %</small><b style="font-size:1.15rem;color:#0e7490">{{ dinero($f['comision']) }}</b></span>
                    </summary>
                    <div class="small mt-2">
                        @foreach ($f['detalle'] as $i)
                        <div class="d-flex justify-content-between border-top py-1"><span>{{ $i->hecho_at?->format('d/m') }} · {{ $i->plan?->paciente?->nombre_completo }} · {{ $i->descripcion }}{{ $i->pieza ? ' (' . $i->pieza . ')' : '' }}</span><span>{{ dinero($i->total) }}</span></div>
                        @endforeach
                    </div>
                </details>
                @empty
                <div class="lp-empty">En este mes no hay procedimientos marcados como hechos.</div>
                @endforelse
                <small class="text-muted d-block mt-2">La producción es el valor de los procedimientos hechos según el plan (antes de descuentos). Cuenta para quien marcó el procedimiento como hecho.</small>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="cs-card">
                <h3>Porcentaje de cada profesional</h3>
                <form method="post" action="{{ route('odonto.comisiones.guardar') }}" class="d-grid gap-2">
                    @csrf
                    @forelse ($profes as $u)
                    <label class="d-flex justify-content-between align-items-center gap-2"><span class="fw-bold">{{ $u->name }}</span>
                        <span class="d-flex align-items-center gap-1"><input type="number" name="pct[{{ $u->id }}]" min="0" max="100" step="0.5" value="{{ $pct[$u->id] ?? '' }}" class="form-control form-control-sm" style="width:80px"> %</span></label>
                    @empty
                    <div class="text-muted small">No hay usuarios con permiso de Historia clínica.</div>
                    @endforelse
                    <button class="cs-btn prim justify-content-center"><i class="fa-solid fa-floppy-disk"></i>Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
