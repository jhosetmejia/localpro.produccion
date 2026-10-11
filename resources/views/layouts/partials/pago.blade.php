{{--
    Dónde se le paga a una persona: banco o billetera digital del país elegido, tipo de cuenta y número.
    Uso: @include('layouts.partials.pago', ['banco' => $a?->banco, 'pais' => 'CO', 'modo' => 'bs'|'in', 'titulo' => 'Dónde se le paga'])
    Escucha el <select name="pais"> del mismo formulario para cambiar la lista de bancos.
--}}
@php
    $pid = 'pg' . substr(md5(uniqid('', true)), 0, 6);
    $modo = $modo ?? 'bs';
    $paisIni = old('pais', $pais ?? 'CO') ?: 'CO';
    $ini = \App\Support\Bancos::partir($banco ?? '', $paisIni);
    foreach (['entidad', 'otro', 'tipo', 'numero', 'titular'] as $k) { $ini[$k] = old('pago_' . $k, $ini[$k]); }
    $titulo = $titulo ?? 'Dónde se le paga';
    $in = $modo === 'in' ? 'in' : 'form-control';
    $sel = $modo === 'in' ? 'in' : 'form-select';
    $lab = $modo === 'in' ? '' : 'form-label';
    $col = $modo === 'in' ? 'field' : 'col-md-6';
@endphp
<div class="{{ $modo === 'in' ? 'pg-box' : 'col-12' }}" id="{{ $pid }}">
    <div class="pg-card">
        <div class="pg-tit"><i class="fa-solid fa-building-columns"></i> {{ $titulo }} <span>Ahí le llegan las comisiones</span></div>
        <div class="{{ $modo === 'in' ? '' : 'row g-3' }}">
            <div class="{{ $col }}">
                <label class="{{ $lab }}" for="{{ $pid }}e">Banco o billetera</label>
                <select class="{{ $sel }}" id="{{ $pid }}e" name="pago_entidad"></select>
            </div>
            <div class="{{ $col }}" data-pg="otro" style="display:none">
                <label class="{{ $lab }}" for="{{ $pid }}o">¿Cuál?</label>
                <input class="{{ $in }}" id="{{ $pid }}o" name="pago_otro" maxlength="60" value="{{ $ini['otro'] }}" placeholder="Nombre del banco o billetera">
            </div>
            <div class="{{ $col }}" data-pg="tipo" style="display:none">
                <label class="{{ $lab }}" for="{{ $pid }}t">Tipo de cuenta</label>
                <div class="pg-tipos" id="{{ $pid }}t" role="radiogroup"></div>
            </div>
            <div class="{{ $col }}" data-pg="numero" style="display:none">
                <label class="{{ $lab }}" for="{{ $pid }}n" id="{{ $pid }}nl">Número de cuenta</label>
                <input class="{{ $in }}" id="{{ $pid }}n" name="pago_numero" maxlength="60" value="{{ $ini['numero'] }}" autocomplete="off">
            </div>
            <div class="{{ $col }}" data-pg="titular" style="display:none">
                <label class="{{ $lab }}" for="{{ $pid }}h">Titular <span style="font-weight:500;opacity:.65">(si la cuenta es de otra persona)</span></label>
                <input class="{{ $in }}" id="{{ $pid }}h" name="pago_titular" maxlength="80" value="{{ $ini['titular'] }}" placeholder="Opcional">
            </div>
        </div>
    </div>
</div>
@once
<style>
.pg-card{border:1.5px dashed #cfe9d6;background:#f6fcf7;border-radius:18px;padding:1rem 1rem 1.1rem}
.pg-box{margin-bottom:1rem}
.pg-box .pg-card .field{margin-bottom:.8rem}.pg-box .pg-card .field:last-child{margin-bottom:0}
.pg-tit{font-weight:800;margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.pg-tit i{color:#16b34a}
.pg-tit span{font-weight:500;font-size:.82rem;color:#6b7570}
.pg-tipos{display:flex;gap:.4rem;flex-wrap:wrap}
.pg-tipos label{flex:1 1 0;min-width:110px;margin:0;cursor:pointer}
.pg-tipos input{position:absolute;opacity:0;pointer-events:none}
.pg-tipos span{display:flex;align-items:center;justify-content:center;gap:.4rem;border:1.5px solid #dfe6e2;background:#fff;border-radius:12px;padding:.62rem .6rem;font-weight:700;font-size:.92rem;color:#33403a;transition:all .12s;text-align:center}
.pg-tipos input:checked + span{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.pg-tipos input:focus-visible + span{box-shadow:0 0 0 4px rgba(47,209,90,.25)}
</style>
@endonce
<script>
(function () {
    var B = @json(\App\Support\Bancos::todas());
    var id = @json($pid), ini = @json($ini);
    var raiz = document.getElementById(id), $ = function (s) { return document.getElementById(id + s); };
    var ent = $('e'), tipos = $('t'), num = $('n'), numL = $('nl');
    var form = raiz.closest('form');
    var pais = form && form.querySelector('select[name="pais"]');
    var caja = function (k) { return raiz.querySelector('[data-pg="' + k + '"]'); };
    var opt = function (v, t) { var o = document.createElement('option'); o.value = v; o.textContent = t; return o; };
    var grupo = function (nombre, lista) {
        var g = document.createElement('optgroup'); g.label = nombre;
        lista.forEach(function (x) { g.appendChild(opt(x, x)); });
        return g;
    };
    function datos() { return B[pais ? pais.value : 'CO'] || null; }
    function esBilletera(v) {
        var d = datos();
        return B._.internacional.indexOf(v) !== -1 || (d && d.billeteras.indexOf(v) !== -1);
    }
    function lista(elegida) {
        var d = datos();
        ent.innerHTML = '';
        ent.appendChild(opt('', 'Elija el banco o la billetera…'));
        if (d) {
            ent.appendChild(grupo('Billeteras digitales', d.billeteras));
            ent.appendChild(grupo('Bancos', d.bancos));
        }
        ent.appendChild(grupo('Internacional', B._.internacional));
        var o = document.createElement('optgroup'); o.label = 'Otro'; o.appendChild(opt('_otro', 'Otro banco o billetera…')); ent.appendChild(o);
        var hay = [].some.call(ent.options, function (x) { return x.value === elegida; });
        ent.value = hay ? elegida : (elegida ? '_otro' : '');
        if (elegida && !hay && elegida !== '_otro') $('o').value = elegida;
        pintarTipos();
        mostrar();
    }
    function pintarTipos() {
        var d = datos(), t = (d ? d.tipos : B._.tipos), actual = (tipos.querySelector('input:checked') || {}).value || ini.tipo || 'ahorros';
        tipos.innerHTML = '';
        Object.keys(t).forEach(function (k, i) {
            var l = document.createElement('label'), r = document.createElement('input'), s = document.createElement('span');
            r.type = 'radio'; r.name = 'pago_tipo'; r.value = k; r.checked = k === actual || (!t[actual] && i === 0);
            s.textContent = t[k]; l.appendChild(r); l.appendChild(s); tipos.appendChild(l);
        });
    }
    function mostrar() {
        var v = ent.value, bil = esBilletera(v), correo = B._.correo.indexOf(v) !== -1;
        caja('otro').style.display = v === '_otro' ? '' : 'none';
        caja('tipo').style.display = v && !bil ? '' : 'none';
        caja('numero').style.display = v ? '' : 'none';
        caja('titular').style.display = v ? '' : 'none';
        tipos.querySelectorAll('input').forEach(function (r) { r.disabled = !v || bil; });
        if (correo) { numL.textContent = 'Correo o usuario'; num.placeholder = 'usuario@correo.com'; num.inputMode = 'email'; }
        else if (v === 'Llave Bre-B') { numL.textContent = 'Llave Bre-B'; num.placeholder = 'Celular, cédula, correo o @llave'; num.inputMode = 'text'; }
        else if (bil) { numL.textContent = 'Número de celular'; num.placeholder = 'Ej: 300 123 4567'; num.inputMode = 'tel'; }
        else { numL.textContent = 'Número de cuenta'; num.placeholder = 'Ej: 123 456 789 00'; num.inputMode = 'numeric'; }
    }
    ent.addEventListener('change', mostrar);
    if (pais) pais.addEventListener('change', function () { lista(esBilletera(ent.value) && B._.internacional.indexOf(ent.value) !== -1 ? ent.value : ''); });
    lista(ini.entidad);
})();
</script>
