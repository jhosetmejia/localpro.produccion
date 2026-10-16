@extends('layouts.app')

@section('title', 'Prospectos')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Prospectos <span>contadores</span></h1>
            <span class="text-muted fw-semibold">
                @if ($cfg['activo'] && $cfg['smtp_usuario'] && $cfg['tieneClave'])
                    <i class="fa-solid fa-circle text-success me-1"></i>Envío automático encendido: hasta {{ $cfg['limite'] }} correos por día hábil, de 8 a. m. a 5 p. m.
                @elseif ($cfg['activo'])
                    <i class="fa-solid fa-circle text-warning me-1"></i>Encendido, pero falta el buzón de envío: no sale nada todavía.
                @else
                    <i class="fa-solid fa-circle text-secondary me-1"></i>Envío automático apagado.
                @endif
            </span></div>
        <div class="lp-quick">
            <form method="post" action="{{ route('admin.prospectos.correr') }}">@csrf<input type="hidden" name="que" value="buscar"><button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar correos ahora</button></form>
            <form method="post" action="{{ route('admin.prospectos.correr') }}">@csrf<input type="hidden" name="que" value="enviar"><button class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Enviar los de hoy ya</button></form>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-paper-plane"></i></div><div class="lbl">Enviados hoy</div><div class="val">{{ $kpi['hoy'] }} / {{ $cfg['limite'] }}</div><div class="sub">{{ $kpi['semana'] }} esta semana · {{ $kpi['total'] }} en total @if($kpi['fallidos'])· <b>{{ $kpi['fallidos'] }} fallidos hoy</b>@endif</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-reply"></i></div><div class="lbl">Respondieron</div><div class="val">{{ $porEstado['respondio'] ?? 0 }}</div><div class="sub">Atiéndalos en el buzón de envío · {{ $porEstado['baja'] ?? 0 }} pidieron no escribir</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-user-plus"></i></div><div class="lbl">Se registraron</div><div class="val">{{ $kpi['registrados'] }}</div><div class="sub">Cuentas creadas desde el enlace del correo</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-inbox"></i></div><div class="lbl">Reserva</div><div class="val">{{ $porEstado['nuevo'] ?? 0 }}</div><div class="sub">Listos para el 1.er correo · {{ $porEstado['sin_correo'] ?? 0 }} buscando correo</div></div>
    </div>
    @if ($ultimoError && $ultimoError->created_at->gt(now()->subDay()))
        <div class="alert alert-warning small"><b>Último error de envío</b> ({{ $ultimoError->created_at->diffForHumans() }}, {{ $ultimoError->correo }}): {{ $ultimoError->detalle }}</div>
    @endif

    <div class="lp-grid">
        <form method="post" action="{{ route('admin.prospectos.guardar') }}" class="lp-box">
            @csrf
            <h3><i class="fa-solid fa-gear me-1"></i> Envío automático</h3>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked($cfg['activo'])>
                <label class="form-check-label fw-semibold" for="activo">Enviar correos todos los días hábiles</label>
            </div>
            <label class="form-label">Correos por día (incluye seguimientos)</label>
            <input name="limite" type="number" min="1" max="200" class="form-control mb-3" value="{{ old('limite', $cfg['limite']) }}">

            <h3 class="mt-2"><i class="fa-solid fa-envelope me-1"></i> Buzón de envío</h3>
            <p class="text-muted small">Use un correo aparte (no el de Brevo), por ejemplo una cuenta de Gmail de LocalProo. En Gmail: active la verificación en dos pasos y cree una <b>clave de aplicación</b> en myaccount.google.com/apppasswords. Las respuestas llegan a este buzón.</p>
            <label class="form-label">Correo</label>
            <input name="smtp_usuario" type="email" class="form-control mb-2" value="{{ old('smtp_usuario', $cfg['smtp_usuario']) }}" placeholder="ventas.localproo@gmail.com">
            <label class="form-label">Clave de aplicación</label>
            <input name="smtp_clave" type="password" class="form-control mb-2" autocomplete="new-password" placeholder="{{ $cfg['tieneClave'] ? 'Guardada · escriba solo para cambiarla' : '16 letras que da Google' }}">
            <div class="row g-2 mb-2">
                <div class="col-8"><label class="form-label">Servidor</label><input name="smtp_host" class="form-control" value="{{ old('smtp_host', $cfg['smtp_host']) }}"></div>
                <div class="col-4"><label class="form-label">Puerto</label><select name="smtp_puerto" class="form-select"><option @selected($cfg['smtp_puerto'] == 587)>587</option><option @selected($cfg['smtp_puerto'] == 465)>465</option></select></div>
            </div>
            <label class="form-label">Nombre y firma</label>
            <input name="remitente" class="form-control mb-3" maxlength="80" value="{{ old('remitente', $cfg['remitente']) }}">

            <h3 class="mt-2"><i class="fa-solid fa-map-location-dot me-1"></i> Búsqueda en Google Maps</h3>
            <p class="text-muted small">Opcional. Con una clave de la API de Places, el sistema busca solo firmas contables ciudad por ciudad y saca el correo de su página. Sin clave, se revisan las páginas que usted agregue.</p>
            <label class="form-label">Clave de Google Maps (Places API)</label>
            <input name="maps_clave" type="password" class="form-control mb-2" autocomplete="new-password" placeholder="{{ $cfg['tieneMaps'] ? 'Guardada · escriba solo para cambiarla' : 'AIza…' }}">
            <label class="form-label">Ciudades, en orden (una por línea)</label>
            <textarea name="ciudades" rows="4" class="form-control mb-3">{{ old('ciudades', $cfg['ciudades']) }}</textarea>
            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
            <div class="d-flex gap-2 mt-2">
                @foreach ([1, 2, 3] as $n)
                <button form="prueba{{ $n }}" class="btn btn-outline-dark btn-sm flex-fill">Probar correo {{ $n }}</button>
                @endforeach
            </div>
            @unless ($cfg['imap'])<p class="text-muted small mt-2 mb-0">Las respuestas se marcan a mano: el servidor no tiene IMAP para leerlas solo.</p>@endunless
        </form>
        @foreach ([1, 2, 3] as $n)
        <form id="prueba{{ $n }}" method="post" action="{{ route('admin.prospectos.prueba') }}" class="d-none">@csrf<input type="hidden" name="paso" value="{{ $n }}"></form>
        @endforeach

        <div>
            <form method="post" action="{{ route('admin.prospectos.agregar') }}" class="lp-box mb-3">
                @csrf
                <h3><i class="fa-solid fa-plus me-1"></i> Agregar prospectos</h3>
                <p class="text-muted small mb-2">Uno por línea: <code>empresa; correo o página web; ciudad</code>. Si solo pone la página, el sistema busca el correo publicado.</p>
                <textarea name="lineas" rows="4" class="form-control mb-2" placeholder="Firma Contable Sur; contacto@firmasur.com; Pasto&#10;www.contadoresejemplo.com"></textarea>
                <button class="btn btn-dark btn-sm"><i class="fa-solid fa-plus me-1"></i>Agregar</button>
            </form>

            <div class="lp-box">
                <form class="d-flex flex-wrap gap-2 mb-2">
                    <select name="estado" class="form-select form-select-sm" style="max-width:200px" onchange="this.form.submit()">
                        <option value="">Todos ({{ $porEstado->sum() }})</option>
                        @foreach (\App\Models\Prospecto::ESTADOS as $k => [$t])
                            <option value="{{ $k }}" @selected($estado === $k)>{{ $t }} ({{ $porEstado[$k] ?? 0 }})</option>
                        @endforeach
                    </select>
                    <input name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Buscar empresa, correo, ciudad">
                </form>
                <div class="table-responsive">
                <table class="table table-sm align-middle small">
                    <thead><tr><th>Empresa</th><th>Correo</th><th>Estado</th><th>Correos</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($lista as $p)
                        @php [$et, $ec] = \App\Models\Prospecto::ESTADOS[$p->estado] ?? [$p->estado, 'secondary']; @endphp
                        <tr>
                            <td><b>{{ $p->empresa }}</b><small class="d-block text-muted">{{ $p->ciudad }} @if($p->telefono)· {{ $p->telefono }}@endif @if($p->web)· <a href="{{ $p->web }}" target="_blank" rel="noopener">web</a>@endif</small></td>
                            <td>{{ $p->correo ?: '—' }}@if($p->fuente && str_starts_with($p->fuente, 'http'))<small class="d-block"><a href="{{ $p->fuente }}" target="_blank" rel="noopener" class="text-muted">dónde se publicó</a></small>@endif</td>
                            <td><span class="badge text-bg-{{ $ec }}">{{ $et }}</span>@if($p->notas)<small class="d-block text-muted" style="max-width:220px">{{ \Illuminate\Support\Str::limit($p->notas, 70) }}</small>@endif</td>
                            <td>{{ $p->paso }}/3 @if($p->proximo_envio)<small class="d-block text-muted">sigue {{ $p->proximo_envio->format('d/m') }}</small>@endif</td>
                            <td>
                                <form method="post" action="{{ route('admin.prospectos.estado', $p) }}" class="d-flex gap-1">@csrf
                                    <select name="estado" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                                        <option value="">Cambiar…</option>
                                        @foreach (['respondio', 'registrado', 'descartado', 'nuevo'] as $k)<option value="{{ $k }}">{{ \App\Models\Prospecto::ESTADOS[$k][0] }}</option>@endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Sin prospectos todavía.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                {{ $lista->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
