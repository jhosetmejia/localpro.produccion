@extends('layouts.app')

@section('title', 'Agenda')

@push('css')
<style>
.ag-sem{display:flex;align-items:center;gap:8px;margin-bottom:1rem}
.ag-dias{flex:1;display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
.ag-dia{border:1.5px solid var(--lp-line);background:#fff;border-radius:16px;padding:.5rem .3rem;text-align:center;text-decoration:none;color:var(--lp-ink)}
.ag-dia small{display:block;font-weight:800;font-size:.68rem;color:var(--lp-muted);text-transform:uppercase}
.ag-dia b{display:block;font-size:1.2rem;font-weight:900;line-height:1.1}
.ag-dia .n{display:inline-block;margin-top:2px;background:#e7f6ec;color:#11702f;border-radius:999px;font-size:.66rem;font-weight:900;padding:0 .4rem}
.ag-dia.hoy{border-color:var(--lp-green)}
.ag-dia.on{background:var(--lp-black);border-color:var(--lp-black);color:#fff}.ag-dia.on small{color:#a8b3ab}.ag-dia.on .n{background:var(--lp-lime);color:#0b0d0c}
.ag-flecha{flex:none;width:40px;height:40px;border-radius:12px;border:1.5px solid var(--lp-line);background:#fff;display:grid;place-items:center;color:var(--lp-ink);text-decoration:none}
.ag-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;align-items:start}
.ag-cita{display:grid;grid-template-columns:70px 1fr;gap:.8rem;padding:.8rem 0;border-bottom:1px solid #eef3ef}
.ag-cita:last-child{border-bottom:0}
.ag-cita.off{opacity:.55}
.ag-hora{font-weight:900;text-align:center;background:#f4f8f5;border-radius:14px;padding:.45rem .2rem;align-self:start}
.ag-hora small{display:block;font-weight:700;color:var(--lp-muted);font-size:.68rem}
.ag-cita b{display:block}.ag-cita .det{color:var(--lp-muted);font-size:.84rem;font-weight:600}
.ag-acc{display:flex;gap:6px;flex-wrap:wrap;margin-top:.45rem}
.ag-form label{font-weight:800;font-size:.8rem;color:var(--lp-muted);margin-bottom:.2rem}
.ag-busca{position:relative}
.ag-res{position:absolute;left:0;right:0;top:100%;z-index:30;background:#fff;border:1.5px solid var(--lp-line);border-radius:14px;margin-top:4px;box-shadow:0 18px 30px -18px rgba(0,0,0,.4);max-height:260px;overflow:auto;display:none}
.ag-res button{display:block;width:100%;text-align:left;border:0;background:none;padding:.55rem .8rem;border-bottom:1px solid #f1f4f2}
.ag-res button:hover{background:#f0fbf4}
.ag-res small{color:var(--lp-muted)}
.ag-elegido{display:none;align-items:center;justify-content:space-between;gap:.5rem;background:#e7f6ec;border-radius:12px;padding:.5rem .8rem;font-weight:800}
@media (max-width:991px){.ag-grid{grid-template-columns:1fr}.ag-form-box{order:-1}}
@media (max-width:575px){.ag-dias{grid-template-columns:repeat(7,minmax(38px,1fr))}.ag-dia b{font-size:1rem}.ag-flecha{display:none}}
</style>
@endpush

@section('content')
@php
    $dia = $fecha->toDateString();
    $delDia = $citas[$dia] ?? collect();
    $tienda = \App\Support\TiendaActual::tienda();
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-user-doctor me-1"></i>Consultorio</small><h1><span>Agenda</span> de citas</h1>
            <span class="text-muted fw-semibold">{{ ucfirst($fecha->translatedFormat('l j \d\e F')) }}</span></div>
    </div>
    @include('consultorio._nav')

    <div class="ag-sem">
        <a class="ag-flecha" href="{{ route('consultorio.agenda', ['fecha' => $desde->copy()->subWeek()->toDateString()]) }}" aria-label="Semana anterior"><i class="fa-solid fa-chevron-left"></i></a>
        <div class="ag-dias">
            @foreach ($dias as $d)
            @php $n = ($citas[$d->toDateString()] ?? collect())->whereIn('estado', ['programada', 'confirmada'])->count(); @endphp
            <a href="{{ route('consultorio.agenda', ['fecha' => $d->toDateString()]) }}" class="ag-dia {{ $d->isSameDay($fecha) ? 'on' : '' }} {{ $d->isToday() ? 'hoy' : '' }}">
                <small>{{ $d->translatedFormat('D') }}</small><b>{{ $d->day }}</b>@if ($n)<span class="n">{{ $n }}</span>@endif
            </a>
            @endforeach
        </div>
        <a class="ag-flecha" href="{{ route('consultorio.agenda', ['fecha' => $desde->copy()->addWeek()->toDateString()]) }}" aria-label="Semana siguiente"><i class="fa-solid fa-chevron-right"></i></a>
    </div>

    <div class="ag-grid">
        <div class="cs-card">
            <h3>{{ $fecha->isToday() ? 'Hoy' : ucfirst($fecha->translatedFormat('l j')) }} <small>{{ $delDia->count() }} cita(s)</small></h3>
            @forelse ($delDia as $c)
            @php [$en, $col] = \App\Models\Cita::ESTADOS[$c->estado];
                $msg = 'Hola ' . $c->paciente?->nombres . ', le recordamos su cita en ' . $tienda->nombre . ' el ' . $c->fecha->translatedFormat('l j \d\e F') . ' a las ' . $c->hora_corta . '. Por favor confírmenos su asistencia. ¡Gracias!'; @endphp
            <div class="ag-cita {{ in_array($c->estado, ['cancelada', 'no_asistio']) ? 'off' : '' }}">
                <div class="ag-hora">{{ \Carbon\Carbon::parse($c->hora)->format('h:i') }}<small>{{ \Carbon\Carbon::parse($c->hora)->format('a') }} · {{ $c->duracion }}′</small></div>
                <div>
                    <b><a href="{{ route('consultorio.paciente', $c->paciente_id) }}" class="text-decoration-none" style="color:inherit">{{ $c->paciente?->nombre_completo }}</a> <span class="cs-est" style="background:{{ $col }}">{{ $en }}</span></b>
                    <div class="det">{{ $c->motivo ?: 'Consulta' }}{{ $c->profesional ? ' · ' . $c->profesional->name : '' }}{{ $c->valor ? ' · ' . dinero($c->valor) : '' }}{{ $c->nota ? ' · ' . $c->nota : '' }}</div>
                    <div class="ag-acc">
                        @if ($c->activa)
                            @if (auth()->user()->can('historia-clinica') || auth()->user()->es_superadmin)
                            <form method="post" action="{{ route('consultorio.atencion.nueva', $c->paciente_id) }}">@csrf<input type="hidden" name="cita" value="{{ $c->id }}"><button class="cs-btn prim sm"><i class="fa-solid fa-notes-medical"></i>Atender</button></form>
                            @endif
                            @if ($c->paciente?->whatsapp)<a class="cs-btn sm" target="_blank" rel="noopener" href="https://wa.me/{{ $c->paciente->whatsapp }}?text={{ rawurlencode($msg) }}"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>Recordar</a>@endif
                            @if ($c->estado === 'programada')
                            <form method="post" action="{{ route('consultorio.citas.estado', $c) }}">@csrf<input type="hidden" name="estado" value="confirmada"><button class="cs-btn sm">Confirmó</button></form>
                            @endif
                            <button type="button" class="cs-btn sm" data-editar='@json(['id' => $c->id, 'paciente_id' => $c->paciente_id, 'paciente' => $c->paciente?->nombre_completo, 'fecha' => $c->fecha->toDateString(), 'hora' => \Carbon\Carbon::parse($c->hora)->format('H:i'), 'duracion' => $c->duracion, 'profesional_id' => $c->profesional_id, 'motivo' => $c->motivo, 'valor' => $c->valor ? (float) $c->valor : null, 'nota' => $c->nota])'><i class="fa-solid fa-pen"></i>Cambiar</button>
                            <form method="post" action="{{ route('consultorio.citas.estado', $c) }}">@csrf<input type="hidden" name="estado" value="no_asistio"><button class="cs-btn sm">No vino</button></form>
                            <form method="post" action="{{ route('consultorio.citas.estado', $c) }}" data-confirm="¿Cancelar esta cita?" data-confirm-ok="Sí, cancelar" data-peligro>@csrf<input type="hidden" name="estado" value="cancelada"><button class="cs-btn sm text-danger">Cancelar</button></form>
                        @else
                            @if ($c->atencion_id && (auth()->user()->can('historia-clinica') || auth()->user()->es_superadmin))<a href="{{ route('consultorio.atencion', $c->atencion_id) }}" class="cs-btn sm"><i class="fa-solid fa-file-medical"></i>Ver atención</a>@endif
                            @if (in_array($c->estado, ['cancelada', 'no_asistio']))
                            <form method="post" action="{{ route('consultorio.citas.estado', $c) }}">@csrf<input type="hidden" name="estado" value="programada"><button class="cs-btn sm">Volver a programar</button></form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="lp-empty">No hay citas este día.</div>
            @endforelse
        </div>

        <div class="cs-card ag-form-box" id="nueva">
            <h3 id="fTitulo">Agendar cita</h3>
            <form method="post" action="{{ route('consultorio.citas.store') }}" class="ag-form row g-2" id="fCita">
                @csrf
                <input type="hidden" name="_method" value="POST" id="fMetodo">
                <input type="hidden" name="paciente_id" id="fPac" value="{{ old('paciente_id', $paciente?->id) }}">
                <div class="col-12">
                    <label class="d-block">Paciente</label>
                    <div class="ag-elegido" id="fElegido"><span id="fElegidoTxt">{{ $paciente?->nombre_completo }}</span><button type="button" class="btn btn-sm btn-link p-0 text-danger fw-bold" id="fCambiarPac">Cambiar</button></div>
                    <div class="ag-busca" id="fBusca">
                        <input type="search" class="form-control" id="fBuscaIn" placeholder="Buscar por nombre, documento o celular…" autocomplete="off">
                        <div class="ag-res" id="fRes"></div>
                    </div>
                    <small class="text-muted">¿Es nuevo? <a href="{{ route('consultorio.pacientes.create', ['despues' => 'cita']) }}">Créelo aquí</a> y vuelve solo a la agenda.</small>
                </div>
                <div class="col-6"><label class="d-block">Fecha</label><input type="date" name="fecha" class="form-control" id="fFecha" value="{{ old('fecha', $dia) }}" required></div>
                <div class="col-6"><label class="d-block">Hora</label><input type="time" name="hora" class="form-control" id="fHora" value="{{ old('hora', '08:00') }}" step="300" required></div>
                <div class="col-6"><label class="d-block">Duración (min)</label><input type="number" name="duracion" class="form-control" id="fDur" min="5" max="480" step="5" value="{{ old('duracion', $duracion) }}"></div>
                <div class="col-6"><label class="d-block">Valor (opcional)</label><input type="number" data-dinero name="valor" class="form-control" id="fValor" min="0" value="{{ old('valor') }}"></div>
                @if ($profesionales->count())
                <div class="col-12"><label class="d-block">Profesional</label>
                    <select name="profesional_id" class="form-select" id="fProf">
                        @if ($profesionales->count() > 1)<option value="">Sin asignar</option>@endif
                        @foreach ($profesionales as $pr)<option value="{{ $pr->id }}" @selected(old('profesional_id', auth()->id()) == $pr->id)>{{ $pr->name }}{{ $pr->especialidad ? ' · ' . $pr->especialidad : '' }}</option>@endforeach
                    </select></div>
                @endif
                <div class="col-12"><label class="d-block">Motivo</label><input name="motivo" class="form-control" id="fMotivo" maxlength="160" value="{{ old('motivo') }}" placeholder="Ej. Primera vez · Control · Limpieza"></div>
                <div class="col-12"><label class="d-block">Nota interna</label><input name="nota" class="form-control" id="fNota" maxlength="255" value="{{ old('nota') }}" placeholder="Opcional"></div>
                <div class="col-12 d-flex gap-2">
                    <button class="cs-btn prim flex-fill justify-content-center"><i class="fa-solid fa-calendar-check"></i><span id="fBoton">Agendar</span></button>
                    <button type="button" class="cs-btn" id="fLimpiar" style="display:none">Cancelar cambio</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var URL_PAC = @json(route('consultorio.pacientes')), URL_NUEVA = @json(route('consultorio.citas.store')), URL_CITA = @json(url('consultorio/citas')) + '/';
    var pac = document.getElementById('fPac'), el = document.getElementById('fElegido'), elTxt = document.getElementById('fElegidoTxt'),
        busca = document.getElementById('fBusca'), inp = document.getElementById('fBuscaIn'), res = document.getElementById('fRes'), t = null;
    function elegir(id, nombre) {
        pac.value = id || ''; elTxt.textContent = nombre || '';
        el.style.display = id ? 'flex' : 'none'; busca.style.display = id ? 'none' : '';
        res.style.display = 'none'; inp.value = '';
    }
    elegir(pac.value, elTxt.textContent);
    document.getElementById('fCambiarPac').addEventListener('click', function () { elegir('', ''); inp.focus(); });
    inp.addEventListener('input', function () {
        clearTimeout(t); var q = inp.value.trim();
        if (q.length < 2) { res.style.display = 'none'; return; }
        t = setTimeout(function () {
            fetch(URL_PAC + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (l) {
                res.innerHTML = l.length ? l.map(function (p) {
                    return '<button type="button" data-id="' + p.id + '" data-n="' + p.nombre.replace(/"/g, '&quot;') + '"><b>' + p.nombre.replace(/</g, '&lt;') + '</b><br><small>' + (p.documento || '') + (p.telefono ? ' · ' + p.telefono : '') + '</small></button>';
                }).join('') : '<div class="p-3 small text-muted">No hay pacientes con "' + q.replace(/</g, '&lt;') + '". Créelo con el enlace de abajo.</div>';
                res.style.display = 'block';
            });
        }, 250);
    });
    res.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (b) elegir(b.dataset.id, b.dataset.n); });

    // Cambiar una cita: el mismo formulario pasa a modo edición
    var f = document.getElementById('fCita'), lim = document.getElementById('fLimpiar');
    function set(id, v) { var x = document.getElementById(id); if (x) { x.value = v == null ? '' : v; x.dispatchEvent(new Event('input')); } }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-editar]'); if (!b) return;
        var c = JSON.parse(b.dataset.editar);
        f.action = URL_CITA + c.id; document.getElementById('fMetodo').value = 'PUT';
        elegir(c.paciente_id, c.paciente);
        set('fFecha', c.fecha); set('fHora', c.hora); set('fDur', c.duracion); set('fProf', c.profesional_id); set('fMotivo', c.motivo); set('fValor', c.valor); set('fNota', c.nota);
        document.getElementById('fTitulo').textContent = 'Cambiar cita de ' + c.paciente;
        document.getElementById('fBoton').textContent = 'Guardar cambios'; lim.style.display = '';
        document.getElementById('nueva').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    lim.addEventListener('click', function () { location.href = location.pathname + location.search; });
})();
</script>
@endpush
