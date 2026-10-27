{{--
    País → Departamento/Provincia → Ciudad.
    Uso: @include('layouts.partials.ubicacion', ['pais' => .., 'departamento' => .., 'ciudad' => .., 'clase' => 'in', 'envoltura' => 'field', 'etiqueta' => ''])
    Colombia, Costa Rica y El Salvador tienen listas; en otros países se escribe a mano.
    Opcionales: 'paisSelect' => id de un <select name="pais"> que ya está en el formulario (no se dibuja otro),
    'obligatorio' => false para no exigir departamento y ciudad, 'estilo' => estilo en línea de los campos.
--}}
@php
    $uid = 'ub' . substr(md5(uniqid('', true)), 0, 6);
    $clase = $clase ?? 'form-control';
    $claseSel = $claseSel ?? ($clase === 'form-control' ? 'form-select' : $clase);
    $envoltura = $envoltura ?? 'col-md-4';
    $etiqueta = $etiqueta ?? 'form-label';
    $paisSel = old('pais', $pais ?? 'CO') ?: 'CO';
    $depSel = old('departamento', $departamento ?? '');
    $ciuSel = old('ciudad', $ciudad ?? '');
    $obligatorio = $obligatorio ?? true;
    $paisSelect = $paisSelect ?? null;
    $estilo = $estilo ?? '';
@endphp
@unless ($paisSelect)
<div class="{{ $envoltura }}">
    <label class="{{ $etiqueta }}" for="{{ $uid }}p">País</label>
    <select class="{{ $claseSel }}" id="{{ $uid }}p" name="pais" required autocomplete="off">
        @foreach (\App\Support\Paises::lista() as $codigo => $p)
            <option value="{{ $codigo }}" @selected($paisSel === $codigo)>{{ $p[0] }} · {{ $p[1] }}</option>
        @endforeach
    </select>
</div>
@endunless
<div class="{{ $envoltura }}">
    <label class="{{ $etiqueta }}" for="{{ $uid }}d" id="{{ $uid }}dl">Departamento</label>
    <select class="{{ $claseSel }}" id="{{ $uid }}d" style="{{ $estilo }}"></select>
    <input class="{{ $clase }}" id="{{ $uid }}dt" maxlength="60" style="{{ $estilo }};display:none">
</div>
<div class="{{ $envoltura }}">
    <label class="{{ $etiqueta }}" for="{{ $uid }}c" id="{{ $uid }}cl">Ciudad</label>
    <select class="{{ $claseSel }}" id="{{ $uid }}c" style="{{ $estilo }}"></select>
    <input class="{{ $clase }}" id="{{ $uid }}ct" maxlength="60" style="{{ $estilo }};display:none">
</div>
<script>
(function () {
    var G = @json(\App\Support\Ubicaciones::todas());
    var id = @json($uid), ini = { d: @json($depSel), c: @json($ciuSel) };
    var $ = function (s) { return document.getElementById(id + s); };
    var obligatorio = @json((bool) $obligatorio);
    var pais = @json($paisSelect) ? document.getElementById(@json($paisSelect)) : $('p'), dS = $('d'), dT = $('dt'), cS = $('c'), cT = $('ct');
    var opt = function (v, t, sel) { var o = document.createElement('option'); o.value = v; o.textContent = t; if (sel) o.selected = true; return o; };

    // Solo el campo visible se envía con el formulario
    function nombres(lista) {
        dS.name = lista ? 'departamento' : ''; cS.name = lista ? 'ciudad' : '';
        dT.name = lista ? '' : 'departamento'; cT.name = lista ? '' : 'ciudad';
        dS.style.display = cS.style.display = lista ? '' : 'none';
        dT.style.display = cT.style.display = lista ? 'none' : '';
        dS.required = cS.required = obligatorio && !!lista;
        dT.required = cT.required = obligatorio && !lista;
    }
    function ciudades(dep, elegida) {
        cS.innerHTML = '';
        var g = G[pais.value], lista = g && g.datos[dep];
        cS.appendChild(opt('', lista ? 'Elija ' + g.ciu.toLowerCase().split(' /')[0] + '…' : 'Primero elija ' + (g ? g.dep.toLowerCase() : 'el departamento')));
        (lista || []).forEach(function (c) { cS.appendChild(opt(c, c, c === elegida)); });
        cS.disabled = !lista;
    }
    function pintar(primera) {
        var g = G[pais.value];
        $('dl').textContent = g ? g.dep : 'Departamento / estado';
        $('cl').textContent = g ? g.ciu : 'Ciudad';
        nombres(!!g);
        if (!g) {
            if (primera) { dT.value = ini.d || ''; cT.value = ini.c || ''; } else { dT.value = cT.value = ''; }
            return;
        }
        var dep = primera ? ini.d : '';
        dS.innerHTML = '';
        dS.appendChild(opt('', 'Elija ' + g.dep.toLowerCase() + '…'));
        Object.keys(g.datos).forEach(function (d) { dS.appendChild(opt(d, d, d === dep)); });
        ciudades(dep, primera ? ini.c : '');
    }
    var pintado = null;
    var pintarBase = pintar;
    pintar = function (primera) { pintado = pais.value; pintarBase(primera); };
    pais.addEventListener('change', function () { pintar(false); });
    dS.addEventListener('change', function () { ciudades(dS.value, ''); });
    pintar(true);
    // El navegador puede devolver el país que estaba elegido (al recargar o volver atrás) sin avisar:
    // si el país cambió, se vuelven a cargar sus departamentos y ciudades
    function revisar() { if (pais.value !== pintado) pintar(pais.value === @json($paisSel)); }
    document.addEventListener('DOMContentLoaded', revisar);
    window.addEventListener('pageshow', revisar);
    window.addEventListener('load', revisar);
    setTimeout(revisar, 300);
})();
</script>
