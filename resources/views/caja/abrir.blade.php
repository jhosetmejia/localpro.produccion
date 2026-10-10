@extends('layouts.app')

@section('title', 'Abrir caja')

@section('content')
<div class="lp-dash" style="max-width:560px;margin:0 auto">
    @include('layouts.partials.lp-flash')
    <div class="lp-box text-center" style="padding:2.2rem">
        <div class="mx-auto mb-3" style="width:72px;height:72px;border-radius:22px;background:var(--lp-grad);display:grid;place-items:center;font-size:1.8rem;color:#0b0d0c"><i class="fa-solid fa-cash-register"></i></div>
        <h1 style="font-size:2rem">Abrir caja</h1>
        <p class="text-muted">Cuente el dinero con el que empieza el turno (la base) y escríbalo aquí.</p>
        <form method="post" action="{{ route('cajas.guardarApertura') }}" class="text-start mt-3">
            @csrf
            <label class="form-label">Base de caja (efectivo inicial)</label>
            <div class="input-group mb-3">
                <span class="input-group-text fw-bold" style="border-radius:12px 0 0 12px">$</span>
                <input type="number" name="monto_inicial" min="0" step="50" class="form-control form-control-lg fw-bold" value="{{ old('monto_inicial', 0) }}" required autofocus>
            </div>
            <div class="d-flex gap-2 flex-wrap mb-3">
                @foreach ([50000, 100000, 200000, 300000] as $b)
                <button type="button" class="btn btn-sm btn-info" onclick="this.form.monto_inicial.value={{ $b }}">@pesos($b)</button>
                @endforeach
            </div>
            <label class="form-label">Nota (opcional)</label>
            <input name="nota_apertura" class="form-control mb-4" maxlength="255" placeholder="Ej: Turno mañana">
            <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-lock-open me-1"></i>Abrir caja e ir al POS</button>
        </form>
        @if ($ultima)
        <p class="text-muted small mt-3 mb-0">Último cierre: {{ $ultima->cerrada_en?->format('d/m/Y h:i a') }} · contó @pesos($ultima->efectivo_contado)</p>
        @endif
    </div>
</div>
@endsection
