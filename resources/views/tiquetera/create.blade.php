@extends('layouts.app')

@section('title', 'Vender tiquetera')

@section('content')
<div class="lp-dash" style="max-width:820px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('tiqueteras.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Tiquetera</a></small><h1>Vender <span>tiquetera</span></h1>
            <span class="text-muted fw-semibold">El cliente paga por adelantado y cada vez que consume se le descuenta uno.</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @if (!$caja)
    <div class="alert alert-warning fw-bold">Para vender la tiquetera abra primero su caja: el pago entra a la caja como una venta. <a href="{{ route('cajas.abrir') }}">Abrir caja</a></div>
    @endif

    <form method="post" action="{{ route('tiqueteras.store') }}" data-confirm="¿Vender esta tiquetera?" data-confirm-texto="El pago entra a su caja como una venta." data-confirm-ok="Sí, vender">
        @csrf
        <div class="lp-box mb-3">
            <h3>Cliente</h3>
            <div class="row g-2">
                <div class="col-md-7"><label class="form-label">Nombre</label><input name="cliente_nombre" class="form-control" maxlength="80" required value="{{ old('cliente_nombre') }}" placeholder="Ej: Carlos Rosero"></div>
                <div class="col-md-5"><label class="form-label">Celular (WhatsApp)</label><input name="cliente_telefono" class="form-control" maxlength="20" inputmode="tel" value="{{ old('cliente_telefono') }}" placeholder="Para avisarle cuántos le quedan"></div>
            </div>
        </div>
        <div class="lp-box mb-3">
            <h3>Qué incluye</h3>
            @if ($planes->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach ($planes as $p)<button type="button" class="btn btn-sm btn-light border rounded-pill fw-bold" data-plan="{{ json_encode(['nombre' => $p->nombre, 'total' => $p->total, 'valor' => (int) $p->valor]) }}">{{ $p->total }} × {{ $p->nombre }} · {{ dinero($p->valor) }}</button>@endforeach
            </div>
            @endif
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" id="tqNombre" class="form-control" maxlength="80" required value="{{ old('nombre', 'Almuerzo') }}" placeholder="Ej: Almuerzo ejecutivo"></div>
                <div class="col-6 col-md-3"><label class="form-label">¿Cuántos?</label><input type="number" name="total" id="tqTotal" class="form-control" min="1" max="1000" required value="{{ old('total', 20) }}"></div>
                <div class="col-6 col-md-3"><label class="form-label">Valor total</label><input data-dinero type="number" name="valor" id="tqValor" class="form-control fw-bold" min="0" required value="{{ old('valor') }}"></div>
                <div class="col-md-6"><label class="form-label">Vence <span class="text-muted fw-normal">(opcional)</span></label><input type="date" name="vence" class="form-control" value="{{ old('vence') }}" min="{{ today()->toDateString() }}"></div>
                <div class="col-md-6"><label class="form-label">Cómo pagó</label>
                    <select name="metodo_id" class="form-select" required>@foreach ($metodos as $m)<option value="{{ $m->id }}" @selected(old('metodo_id') ? old('metodo_id') == $m->id : $m->es_efectivo)>{{ $m->nombre }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Nota <span class="text-muted fw-normal">(opcional)</span></label><input name="nota" class="form-control" maxlength="255" value="{{ old('nota') }}" placeholder="Ej: incluye sopa y jugo"></div>
            </div>
            <div class="small fw-semibold mt-2" style="color:#11702f" id="tqCada"></div>
        </div>
        <button class="btn btn-primary btn-lg rounded-pill fw-bold" @disabled(!$caja)><i class="fa-solid fa-ticket me-1"></i>Vender tiquetera</button>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var n = document.getElementById('tqNombre'), t = document.getElementById('tqTotal'), v = document.getElementById('tqValor'), c = document.getElementById('tqCada'), fmt = new Intl.NumberFormat('es-CO');
    function calc() { var tot = +t.value || 0, val = +v.value || 0; c.textContent = tot && val ? 'Cada uno le sale en $' + fmt.format(Math.round(val / tot)) : ''; }
    document.querySelectorAll('[data-plan]').forEach(function (b) { b.addEventListener('click', function () { var p = JSON.parse(b.dataset.plan); n.value = p.nombre; t.value = p.total; v.value = p.valor; calc(); }); });
    document.addEventListener('input', calc); calc();
})();
</script>
@endpush
