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

    <form method="post" action="{{ $nuevo ? route('alquileres.store') : route('alquileres.update', $alquiler) }}" class="lp-box lp-form-wide">
        @csrf
        @unless ($nuevo) @method('PUT') @endunless

        @if ($alquiler->cobrado)
        <div class="alert border-0" style="background:#fff5d6;color:#8a6400"><i class="fa-solid fa-user-shield me-1"></i><b>Edición de administrador.</b> Este alquiler ya se cobró con la venta {{ $alquiler->venta?->numero }}. Si cambia el valor, también se cambia esa venta (solo si es tiquete y la caja sigue abierta).</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label fw-bold lp-q-lbl" for="queAlq"><i class="fa-solid fa-tag me-1"></i> ¿Qué se alquila? *</label>
                <input id="queAlq" name="que_alquila" class="form-control form-control-lg" maxlength="120" required autofocus
                    placeholder="Ej: Sonido para fiesta, salón de eventos, carpa 6x6…" value="{{ old('que_alquila', $alquiler->que_alquila) }}">
            </div>
            <div class="col-lg-6">
                <label class="form-label fw-bold lp-q-lbl" for="lugarAlq"><i class="fa-solid fa-location-dot me-1"></i> Lugar *</label>
                <input id="lugarAlq" name="lugar" class="form-control form-control-lg" maxlength="150" required placeholder="Dirección o sitio donde se presta el servicio" value="{{ old('lugar', $alquiler->lugar) }}">
            </div>

            <div class="col-6 col-lg-3">
                <label class="form-label fw-bold">Fecha *</label>
                <input type="date" name="fecha" class="form-control" required value="{{ old('fecha', optional($alquiler->fecha)->format('Y-m-d')) }}">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label fw-bold">Hora *</label>
                <input type="time" name="hora" class="form-control" required value="{{ old('hora', $alquiler->hora ? substr($alquiler->hora, 0, 5) : '') }}">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label fw-bold">Número del responsable *</label>
                <input type="tel" name="responsable_telefono" class="form-control" required data-celular value="{{ old('responsable_telefono', $alquiler->responsable_telefono) }}">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label fw-bold">Valor del alquiler *</label>
                <input name="valor" id="valor" type="number" min="0" class="form-control" required data-dinero placeholder="0" value="{{ old('valor', $alquiler->exists ? $alquiler->valor : '') }}">
            </div>

            <div class="col-lg-4">
                <label class="form-label fw-bold d-none d-lg-block">&nbsp;</label>
                <div class="lp-sw-row">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="conCom" name="con_comision" value="1" @checked(old('con_comision', $alquiler->con_comision))>
                        <label class="form-check-label fw-bold" for="conCom">Pago de comisión</label>
                    </div>
                    <small class="text-muted">Si se le paga a alguien (dueño del equipo, quien lo consiguió…).</small>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4 {{ old('con_comision', $alquiler->con_comision) ? '' : 'd-none' }}" id="boxCom">
                <label class="form-label fw-bold">Valor de la comisión *</label>
                <input name="comision" id="comision" type="number" min="0" class="form-control" data-dinero placeholder="0" value="{{ old('comision', $alquiler->con_comision ? $alquiler->comision : '') }}">
            </div>
            <div class="col-sm-6 col-lg-4 lp-gan-col {{ old('con_comision', $alquiler->con_comision) ? '' : 'd-none' }}" id="boxGan">
                <div class="lp-gan">
                    <small>Ganancia del local <span class="fw-normal">(valor − comisión)</span></small>
                    <b id="ganancia">$0</b>
                </div>
            </div>

            @php
                $puedePagar = !$alquiler->cobrado && $alquiler->estado !== 'cancelado';
                $yaAbono = (float) ($alquiler->abonado ?? 0);
                $pagoSel = old('pago', 'despues');
            @endphp
            @if ($puedePagar)
            <div class="col-12">
                <div class="lp-pago" id="lpPago" data-abonado="{{ $yaAbono }}">
                    <div class="lp-pago-top">
                        <label class="form-label fw-bold mb-0"><i class="fa-solid fa-cash-register me-1"></i> ¿Cómo paga?</label>
                        @if ($yaAbono > 0)<small class="lp-pago-ya">Ya abonó <b>{{ dinero($yaAbono) }}</b></small>@endif
                    </div>
                    <div class="lp-pago-row">
                        <div class="lp-seg" role="radiogroup">
                            <input type="radio" class="btn-check" name="pago" id="pagoDesp" value="despues" @checked($pagoSel === 'despues')>
                            <label for="pagoDesp"><i class="fa-regular fa-clock"></i> Paga después</label>
                            <input type="radio" class="btn-check" name="pago" id="pagoComp" value="completo" @checked($pagoSel === 'completo')>
                            <label for="pagoComp"><i class="fa-solid fa-circle-check"></i> {{ $yaAbono > 0 ? 'Paga lo que falta' : 'Paga completo' }}</label>
                            <input type="radio" class="btn-check" name="pago" id="pagoAbo" value="abono" @checked($pagoSel === 'abono')>
                            <label for="pagoAbo"><i class="fa-solid fa-hand-holding-dollar"></i> {{ $yaAbono > 0 ? 'Abona otra parte' : 'Abona' }}</label>
                        </div>
                        <div class="lp-pago-campos" id="pagoCampos">
                            <div class="lp-pago-monto" id="pagoMontoBox">
                                <label class="form-label fw-bold" for="pagoMonto">¿Cuánto abona? *</label>
                                <input name="pago_monto" id="pagoMonto" type="number" min="1" class="form-control" data-dinero placeholder="0" value="{{ old('pago_monto') }}">
                            </div>
                            <div>
                                <label class="form-label fw-bold" for="pagoMetodo">¿Cómo le pagaron? *</label>
                                <select name="pago_metodo" id="pagoMetodo" class="form-select">
                                    @foreach ($metodos as $m)<option value="{{ $m->id }}" @selected(old('pago_metodo') ? old('pago_metodo') == $m->id : $m->es_efectivo)>{{ $m->nombre }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="lp-pago-res" id="pagoRes">
                            <div><small>Cobra ahora</small><b id="resCobra">$0</b></div>
                            <div class="falta"><small>Queda debiendo</small><b id="resFalta">$0</b></div>
                        </div>
                    </div>
                    @unless ($cajaAbierta)
                    <small class="lp-pago-aviso" id="pagoAviso"><i class="fa-solid fa-triangle-exclamation me-1"></i>Para recibir el pago aquí, primero <a href="{{ route('cajas.abrir') }}">abra su caja</a>. Mientras tanto puede guardarlo como "Paga después".</small>
                    @endunless
                    @error('pago')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                    @error('pago_monto')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                    @error('pago_metodo')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                </div>
            </div>
            @endif

            <div class="col-12">
                <label class="form-label fw-bold">Nota <span class="text-muted fw-normal">(opcional)</span></label>
                <textarea name="nota" class="form-control" rows="1" maxlength="255" placeholder="Ej: incluye transporte, entregar 1 hora antes…">{{ old('nota', $alquiler->nota) }}</textarea>
            </div>
        </div>

        <div class="lp-form-actions sticky">
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
        boxC.classList.toggle('d-none', !chk.checked);
        boxG.classList.toggle('d-none', !chk.checked);
        com.required = chk.checked;
        var g = num(val) - (chk.checked ? num(com) : 0);
        out.textContent = fmt(g);
        out.style.color = g < 0 ? '#ff6f9c' : '#2fd15a';
    }
    chk.addEventListener('change', calc);
    document.addEventListener('input', function () { setTimeout(calc, 0); }, true);
    document.addEventListener('change', function () { setTimeout(calc, 0); }, true);
    calc();

    // ¿Cómo paga?: muestra el monto del abono y calcula cuánto cobra ahora y cuánto queda debiendo
    var box = document.getElementById('lpPago');
    if (box) {
        var ya = parseFloat(box.dataset.abonado || '0') || 0;
        var monto = document.getElementById('pagoMonto'), campos = document.getElementById('pagoCampos'), res = document.getElementById('pagoRes');
        var mBox = document.getElementById('pagoMontoBox');
        function pago() {
            var sel = (box.querySelector('input[name=pago]:checked') || {}).value || 'despues';
            var falta = Math.max(0, num(val) - ya), cobra = 0;
            campos.style.display = sel === 'despues' ? 'none' : '';
            mBox.style.display = sel === 'abono' ? '' : 'none';
            monto.required = sel === 'abono';
            if (sel === 'completo') cobra = falta;
            if (sel === 'abono') cobra = Math.min(num(monto), falta);
            document.getElementById('resCobra').textContent = fmt(cobra);
            document.getElementById('resFalta').textContent = fmt(falta - cobra);
            res.classList.toggle('ok', falta - cobra <= 0 && sel !== 'despues');
            var mVis = monto.nextElementSibling && monto.nextElementSibling.hasAttribute('data-dinero-vista') ? monto.nextElementSibling : monto;
            mVis.classList.toggle('is-invalid', sel === 'abono' && num(monto) > falta);
            box.dataset.sel = sel;
        }
        box.addEventListener('change', pago);
        document.addEventListener('input', function () { setTimeout(pago, 0); }, true);
        pago();
    }
    var nota = document.querySelector('textarea[name=nota]');
    function crecer() { nota.style.height = 'auto'; nota.style.height = Math.min(nota.scrollHeight + 2, 140) + 'px'; }
    if (nota) { nota.addEventListener('input', crecer); crecer(); }
})();
</script>
@endpush
