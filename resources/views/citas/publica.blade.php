<!DOCTYPE html>
<html lang="es">
<head>
@include('citas._estilo')
<title>Pedir cita · {{ $t->nombre }}</title>
<meta name="description" content="Pida su cita en {{ $t->nombre }}{{ $t->ciudad ? ' (' . $t->ciudad . ')' : '' }} en línea: elija el día y la hora.">
<meta name="robots" content="{{ $t->en_google && !$borrador ? 'index, follow' : 'noindex' }}">
<meta property="og:title" content="Pida su cita en {{ $t->nombre }}">
@if ($t->logo_url)<meta property="og:image" content="{{ $t->logo_url }}">@endif
</head>
<body>
<div class="pg">
    @include('citas._cab')
    @if ($borrador)<div class="err"><i class="fa-solid fa-eye"></i> Vista previa: las citas en línea están apagadas. Actívelas en Consultorio → Mi perfil → Citas en línea.</div>@endif
    @if ($errors->any())<div class="err"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('citas.store', $t->slug) }}" id="f">
        @csrf
        <input type="text" name="web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <input type="hidden" name="fecha" id="fecha" value="{{ old('fecha') }}">
        <input type="hidden" name="hora" id="hora" value="{{ old('hora') }}">

        <div class="card">
            <h2><i class="fa-solid fa-calendar-days"></i>1. Elija el día</h2>
            @if (!$dias)
            <p class="muted">No hay horas disponibles en los próximos días. Escríbale al consultorio{{ $t->telefono ? ' al ' . $t->telefono : '' }}.</p>
            @else
            <div class="dias">
                @foreach ($dias as $d)
                <button type="button" class="dia" data-fecha="{{ $d['fecha'] }}"><small>{{ $d['dia'] }}</small><b>{{ $d['num'] }}</b><small>{{ $d['mes'] }}</small></button>
                @endforeach
            </div>
            <div id="horas-box" style="display:none">
                <label style="margin-top:14px">2. Elija la hora</label>
                <div class="horas" id="horas"></div>
            </div>
            @endif
        </div>

        <div class="card" id="datos" style="{{ old('hora') ? '' : 'display:none' }}">
            <h2><i class="fa-solid fa-user"></i>3. Sus datos</h2>
            @if ($servicios->count())
            <label>¿Para qué es la cita?</label>
            <select name="servicio"><option value="">Valoración / consulta</option>
                @foreach ($servicios as $s)<option value="{{ $s->id }}" @selected(old('servicio') == $s->id)>{{ $s->nombre }}</option>@endforeach</select>
            @endif
            <div class="g2">
                <div><label>Tipo de documento</label><select name="tipo_documento">@foreach (\App\Models\Paciente::DOCUMENTOS as $k => $n)<option value="{{ $k }}" @selected(old('tipo_documento', 'CC') === $k)>{{ $n }}</option>@endforeach</select></div>
                <div><label>Número de documento</label><input name="documento" value="{{ old('documento') }}" required maxlength="30" inputmode="numeric"></div>
                <div><label>Nombres</label><input name="nombres" value="{{ old('nombres') }}" required maxlength="80" autocomplete="given-name"></div>
                <div><label>Apellidos</label><input name="apellidos" value="{{ old('apellidos') }}" required maxlength="80" autocomplete="family-name"></div>
                <div><label>Celular (WhatsApp)</label><input name="telefono" value="{{ old('telefono') }}" required maxlength="30" inputmode="tel" autocomplete="tel"></div>
                <div><label>Correo (para el recordatorio)</label><input type="email" name="correo" value="{{ old('correo') }}" maxlength="120" autocomplete="email"></div>
                <div><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" max="{{ today()->subDay()->toDateString() }}"></div>
                <div><label>Algo que debamos saber (opcional)</label><input name="nota" value="{{ old('nota') }}" maxlength="200" placeholder="Ej. dolor en una muela"></div>
            </div>
            <label class="chk" style="margin-top:14px"><input type="checkbox" name="autoriza" value="1" required @checked(old('autoriza'))>
                <span>Autorizo a {{ $t->nombre }} a tratar mis datos personales y de salud para atenderme y recordarme mis citas (Ley 1581 de 2012).</span></label>
            <div id="resumen" class="ok" style="margin:14px 0 10px;display:none"></div>
            <button class="btn prim" id="enviar"><i class="fa-solid fa-calendar-check"></i>Pedir mi cita</button>
            @if ($c['mensaje'])<p class="muted" style="margin:12px 0 0"><i class="fa-solid fa-circle-info"></i> {{ $c['mensaje'] }}</p>@endif
        </div>
    </form>
    <div class="pie">Citas en línea con <a href="{{ url('/odontologos') }}">LocalProo</a></div>
</div>
<script>
(function () {
    var fecha = document.getElementById('fecha'), hora = document.getElementById('hora'), box = document.getElementById('horas-box'), lista = document.getElementById('horas'),
        datos = document.getElementById('datos'), res = document.getElementById('resumen'), etiqueta = '';
    function marcar(sel, el) { document.querySelectorAll(sel).forEach(function (x) { x.classList.toggle('on', x === el); }); }
    function resumen() {
        if (!hora.value) { res.style.display = 'none'; return; }
        res.style.display = ''; res.innerHTML = '<i class="fa-solid fa-calendar-check"></i> ' + etiqueta;
    }
    function cargar(f, btnDia) {
        fecha.value = f; hora.value = ''; marcar('.dia', btnDia); resumen();
        box.style.display = ''; lista.innerHTML = '<span class="muted">Buscando horas…</span>';
        fetch({!! json_encode(route('citas.horas', $t->slug)) !!} + '?fecha=' + f, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                lista.innerHTML = '';
                if (!j.horas.length) { lista.innerHTML = '<span class="muted">Ese día ya no quedan horas. Elija otro.</span>'; return; }
                j.horas.forEach(function (h) {
                    var b = document.createElement('button'); b.type = 'button'; b.className = 'hora'; b.textContent = h.t;
                    b.addEventListener('click', function () {
                        hora.value = h.v; marcar('.hora', b);
                        var d = new Date(f + 'T12:00:00');
                        etiqueta = d.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }) + ' a las ' + h.t;
                        datos.style.display = ''; resumen();
                        datos.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                    lista.appendChild(b);
                });
            })
            .catch(function () { lista.innerHTML = '<span class="muted">No se pudieron cargar las horas. Intente de nuevo.</span>'; });
    }
    document.querySelectorAll('.dia').forEach(function (b) { b.addEventListener('click', function () { cargar(b.dataset.fecha, b); }); });
    var prim = document.querySelector('.dia'); if (prim && !fecha.value) cargar(prim.dataset.fecha, prim);
    else if (fecha.value) { var b = document.querySelector('.dia[data-fecha="' + fecha.value + '"]'); if (b) cargar(fecha.value, b); }
    document.getElementById('f').addEventListener('submit', function (e) {
        if (!hora.value) { e.preventDefault(); box.scrollIntoView({ behavior: 'smooth' }); return; }
        document.getElementById('enviar').disabled = true;
    });
})();
</script>
</body>
</html>
