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

        @if ($alquiler->cobrado)
        <div class="alert border-0 mb-3" style="background:#fff5d6;color:#8a6400"><i class="fa-solid fa-user-shield me-1"></i><b>Edición de administrador.</b> Este alquiler ya se cobró con la venta {{ $alquiler->venta?->numero }}. Si cambia el valor, también se cambia esa venta (solo si es tiquete y la caja sigue abierta).</div>
        @endif
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
                <input name="valor" id="valor" type="number" min="0" class="form-control" required data-dinero placeholder="0" value="{{ old('valor', $alquiler->exists ? $alquiler->valor : '') }}">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="conCom" name="con_comision" value="1" @checked(old('con_comision', $alquiler->con_comision))>
                    <label class="form-check-label fw-bold" for="conCom">Pago de comisión</label>
                </div>
                <small class="text-muted">Actívelo si de este alquiler se paga una comisión (al dueño del equipo, a quien lo consiguió…). La ganancia del local es el valor menos la comisión.</small>
            </div>
            <div class="col-sm-6" id="boxCom" style="{{ old('con_comision', $alquiler->con_comision) ? '' : 'display:none' }}">
                <label class="form-label fw-bold">Valor de la comisión *</label>
                <input name="comision" id="comision" type="number" min="0" class="form-control" data-dinero placeholder="0" value="{{ old('comision', $alquiler->con_comision ? $alquiler->comision : '') }}">
            </div>
            <div class="col-sm-6 d-flex align-items-end" id="boxGan" style="{{ old('con_comision', $alquiler->con_comision) ? '' : 'display:none' }}">
                <div class="w-100 rounded-4 p-3" style="background:#0b0d0c;color:#fff">
                    <small class="fw-bold" style="color:#c9d3cc">Ganancia del local</small>
                    <div class="fs-4 fw-bold" style="color:#2fd15a" id="ganancia">$0</div>
                </div>
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

@push('js')
<script>
(function () {
    var chk = document.getElementById('conCom'), val = document.getElementById('valor'), com = document.getElementById('comision');
    var boxC = document.getElementById('boxCom'), boxG = document.getElementById('boxGan'), out = document.getElementById('ganancia');
    var M = window.LP_MONEDA || { miles: '.', decimales: 0 };
    function num(el) { var v = parseFloat(String(el.value || '0').replace(/[^0-9.\-]/g, '')); return isNaN(v) ? 0 : v; }
    function fmt(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, M.miles || '.'); }
    function calc() {
        boxC.style.display = boxG.style.display = chk.checked ? '' : 'none';
        com.required = chk.checked;
        var g = num(val) - (chk.checked ? num(com) : 0);
        out.textContent = fmt(g);
        out.style.color = g < 0 ? '#ff6f9c' : '#2fd15a';
    }
    chk.addEventListener('change', calc);
    document.addEventListener('input', function () { setTimeout(calc, 0); }, true);
    document.addEventListener('change', function () { setTimeout(calc, 0); }, true);
    calc();
})();
</script>
@endpush
