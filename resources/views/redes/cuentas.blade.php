@extends('layouts.app')

@section('title', 'Mis redes · Cuentas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-hashtag me-1"></i>Mis redes</small><h1>Cuentas <span>conectadas</span></h1>
            <span class="text-muted fw-semibold">Las páginas de Facebook y cuentas de Instagram de su negocio.</span></div>
        @php
            $cupo = \App\Http\Controllers\redesController::cupoRedes();
            $maxR = max(1, (int) (\App\Support\TiendaActual::tienda()?->redes_max ?? 1));
        @endphp
        @if ($activo && $cupo > 0)
        <div class="lp-quick"><a href="{{ route('redes.conectar') }}" class="btn btn-lg" style="background:#1877f2;color:#fff;font-weight:800"><i class="fa-brands fa-facebook me-1"></i>{{ $cupo < $maxR ? 'Conectar otra página' : 'Conectar página' }}</a></div>
        @endif
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    @include('redes._tabs')

    <div class="lp-box">
        <ul class="lp-list">
            @forelse ($cuentas as $c)
            <li style="{{ $c->estado === 'desconectada' ? 'opacity:.55' : '' }}">
                @if ($c->es_correo)
                <div class="av" style="background:#fdecea;color:#ea4335"><i class="fa-solid fa-envelope"></i></div>
                <div class="t">
                    <b>{{ $c->page_nombre }}</b>
                    <small><span class="rd-ico correo" style="width:16px;height:16px;font-size:.55rem"><i class="fa-solid fa-envelope"></i></span> Correo ({{ \App\Support\Correo::PROVEEDORES[$c->correo_config['proveedor'] ?? 'otro'][0] ?? 'Correo' }})
                        · {{ $c->sincronizado_en ? 'revisado ' . $c->sincronizado_en->locale('es')->diffForHumans() : 'conectado ' . $c->created_at->format('d/m/Y') }}</small>
                    @if ($c->estado === 'error')<small class="d-block" style="color:#b0173f"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $c->error }} — vuelva a conectarlo abajo.</small>@endif
                </div>
                @else
                <div class="av" style="overflow:hidden">@if ($c->page_foto)<img src="{{ $c->page_foto }}" alt="" style="width:100%;height:100%;object-fit:cover">@else<i class="fa-brands fa-facebook-f"></i>@endif</div>
                <div class="t">
                    <b>{{ $c->page_nombre }}</b>
                    <small><span class="rd-ico facebook" style="width:16px;height:16px;font-size:.55rem"><i class="fa-brands fa-facebook-f"></i></span> Página de Facebook
                        · @if ($c->ig_usuario)<span class="rd-ico instagram" style="width:16px;height:16px;font-size:.55rem"><i class="fa-brands fa-instagram"></i></span> {{ '@' . $c->ig_usuario }}@else<span style="color:#a86b00">Sin Instagram vinculado</span>@endif
                        · {{ $c->sincronizado_en ? 'actualizada ' . $c->sincronizado_en->locale('es')->diffForHumans() : 'conectada ' . $c->created_at->format('d/m/Y') }}</small>
                    @if ($c->estado === 'error')<small class="d-block" style="color:#b0173f"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $c->error }} — vuelva a conectarla.</small>@endif
                </div>
                @endif
                @if ($c->estado === 'desconectada')
                <span class="badge bg-secondary">Desconectada</span>
                @else
                <span class="badge {{ $c->estado === 'error' ? 'bg-danger' : 'bg-success' }}">{{ $c->estado === 'error' ? 'Con error' : 'Activa' }}</span>
                <form method="post" action="{{ route('redes.desconectar', $c) }}" data-confirm="¿Desconectar {{ $c->page_nombre }}?" data-confirm-texto="Ya no llegarán mensajes nuevos ni podrá publicar en ella desde LocalPro. Los chats guardados se conservan." data-confirm-ok="Sí, desconectar" data-peligro>@csrf<button class="btn btn-sm btn-outline-danger">Desconectar</button></form>
                @endif
            </li>
            @empty
            <li class="lp-empty">No hay cuentas conectadas. <a href="{{ route('redes.index') }}">Conectar ahora</a></li>
            @endforelse
        </ul>
        <div class="mt-3"><a href="{{ route('redes.cuentas', ['revisar' => 1]) }}#revision" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-stethoscope me-1"></i>Revisar si llegan los mensajes</a></div>
        @if ($revision !== null)
        <ul class="list-unstyled mt-2 mb-0" id="revision">
            @foreach ($revision as [$ok, $titulo, $detalle])
            <li class="d-flex gap-2 py-1" style="border-bottom:1px solid #eef2ef"><span style="width:22px;flex:none">{{ $ok === true ? '✅' : ($ok === false ? '❌' : 'ℹ️') }}</span><span><b>{{ $titulo }}</b><small class="d-block text-muted">{{ $detalle }}</small></span></li>
            @endforeach
            <li class="d-flex gap-2 py-1"><span style="width:22px;flex:none">{{ $webhook ? (str_contains($webhook, 'INVÁLIDA') ? '❌' : '✅') : 'ℹ️' }}</span><span><b>Avisos en tiempo real de Meta</b><small class="d-block text-muted">{{ $webhook ? 'Último aviso: ' . $webhook : 'Todavía no ha llegado ningún aviso en tiempo real (mientras la app esté en revisión, Meta solo los manda para los administradores de la app). Los mensajes igual se revisan cada 2 minutos.' }}</small></span></li>
        </ul>
        @endif
        <p class="small text-muted mt-3 mb-1"><i class="fa-solid fa-layer-group me-1"></i>Cada negocio conecta <b>{{ $maxR === 1 ? 'una página de Facebook' : $maxR . ' páginas de Facebook' }}</b> con su Instagram. Si la cuenta falla, vuelva a conectarla con <a href="{{ route('redes.conectar', ['reconectar' => 1]) }}">Reconectar</a>.</p>
        <p class="small text-muted mt-0 mb-0"><i class="fa-solid fa-circle-info me-1"></i>¿No aparece su Instagram? Debe ser <b>cuenta profesional</b> y estar <b>vinculada a la página</b> de Facebook. Vincúlela y toque "Reconectar" para actualizar.</p>
    </div>

    {{-- Correo en la bandeja (opcional) --}}
    <div class="lp-box mt-3" id="correo">
        <h3><span><i class="fa-solid fa-envelope me-1" style="color:#ea4335"></i>Correo en Mis redes <small class="text-muted fw-semibold">(opcional)</small></span></h3>
        <p class="text-muted small mb-2">Los correos que le lleguen a su Gmail, Outlook o al correo de su negocio aparecen en <b>Mensajes</b> junto a Facebook e Instagram, y los responde desde aquí. Se usa una <b>contraseña de aplicación</b> (no su clave normal): la puede borrar cuando quiera desde su cuenta de correo.</p>
        @if ($cupoCorreo > 0)
        <form method="post" action="{{ route('redes.correo') }}" class="row g-2" id="fCorreo" autocomplete="off">
            @csrf
            <div class="col-md-4"><label class="form-label small fw-bold mb-1">Su correo es de</label>
                <select name="proveedor" id="cProv" class="form-select" data-nativo>@foreach ($proveedores as $k => $p)<option value="{{ $k }}" data-ayuda="{{ $p[6] }}" @selected(old('proveedor', 'gmail') === $k)>{{ $p[0] }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label small fw-bold mb-1">Correo</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="sunegocio@gmail.com" required></div>
            <div class="col-md-4"><label class="form-label small fw-bold mb-1">Contraseña de aplicación</label><input type="password" name="clave" class="form-control" placeholder="xxxx xxxx xxxx xxxx" required autocomplete="new-password"></div>
            <div class="col-12 row g-2 m-0 p-0" id="cOtro" style="{{ old('proveedor') === 'otro' ? '' : 'display:none' }}">
                <div class="col-md-4"><label class="form-label small fw-bold mb-1">Servidor IMAP (leer)</label><input name="imap_host" class="form-control" value="{{ old('imap_host') }}" placeholder="mail.sunegocio.com"></div>
                <div class="col-md-2"><label class="form-label small fw-bold mb-1">Puerto</label><input name="imap_port" type="number" class="form-control" value="{{ old('imap_port', 993) }}"></div>
                <div class="col-md-4"><label class="form-label small fw-bold mb-1">Servidor SMTP (enviar)</label><input name="smtp_host" class="form-control" value="{{ old('smtp_host') }}" placeholder="mail.sunegocio.com"></div>
                <div class="col-md-2"><label class="form-label small fw-bold mb-1">Puerto</label><input name="smtp_port" type="number" class="form-control" value="{{ old('smtp_port', 465) }}"></div>
            </div>
            <div class="col-12 small text-muted" id="cAyuda">
                <b>Gmail:</b> active la verificación en dos pasos y cree la contraseña en <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener" id="cLink">myaccount.google.com/apppasswords</a> (póngale de nombre "LocalPro"). Copie las 16 letras y péguelas aquí.
            </div>
            <div class="col-12"><button class="btn btn-dark"><i class="fa-solid fa-plug me-1"></i>Probar y conectar</button></div>
        </form>
        <script>
        (function () {
            var s = document.getElementById('cProv'), o = document.getElementById('cOtro'), a = document.getElementById('cAyuda');
            var txt = {
                gmail: '<b>Gmail:</b> active la verificación en dos pasos y cree la contraseña en <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">myaccount.google.com/apppasswords</a> (nombre: "LocalPro"). Copie las 16 letras y péguelas aquí.',
                outlook: '<b>Outlook / Hotmail:</b> active la verificación en dos pasos y cree una contraseña de aplicación en <a href="https://account.live.com/proofs/AppPassword" target="_blank" rel="noopener">account.live.com</a>.',
                yahoo: '<b>Yahoo:</b> en Seguridad de la cuenta, toque "Generar contraseña de aplicación" en <a href="https://login.yahoo.com/account/security" target="_blank" rel="noopener">login.yahoo.com</a>.',
                otro: '<b>Correo de su dominio:</b> pídale a su proveedor de hosting los datos IMAP y SMTP (casi siempre puerto 993 para leer y 465 para enviar) y use la clave de ese correo.'
            };
            function poner() { o.style.display = s.value === 'otro' ? '' : 'none'; a.innerHTML = txt[s.value] || ''; }
            s.addEventListener('change', poner); poner();
        })();
        </script>
        @else
        <p class="small fw-semibold mb-0" style="color:#11702f"><i class="fa-solid fa-circle-check me-1"></i>Ya tiene conectados {{ \App\Http\Controllers\redesController::MAX_CORREOS }} correos (el máximo).</p>
        @endif
    </div>
</div>
@endsection

