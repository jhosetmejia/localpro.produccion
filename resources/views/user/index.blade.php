@extends('layouts.app')

@section('title','usuarios')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<style>
.lp-wa-form,.lp-wa-wrap{display:inline-flex;margin:0}
.lp-wa-btn{display:inline-flex;align-items:center;gap:.45rem;height:36px;padding:0 .9rem 0 .35rem;border:0;border-radius:999px;background:linear-gradient(135deg,#2fe07a,#25d366 55%,#1aa952);color:#fff;font-weight:800;font-size:.82rem;white-space:nowrap;text-decoration:none;cursor:pointer;box-shadow:0 8px 18px -10px rgba(37,211,102,.9);transition:transform .15s,box-shadow .15s,filter .15s}
.lp-wa-btn .ic{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.22);display:inline-grid;place-items:center;font-size:1.05rem}
.lp-wa-btn:hover{color:#fff;filter:brightness(1.05);transform:translateY(-1px);box-shadow:0 12px 22px -10px rgba(37,211,102,1)}
.lp-wa-btn:active{transform:none}
.lp-wa-btn[disabled]{opacity:.7;cursor:wait}
.lp-wa-card{background:#fff;border:1.5px solid #c8f0d6;border-radius:18px;padding:16px;box-shadow:0 14px 30px -24px rgba(22,179,74,.8)}
.lp-wa-card-top{display:flex;gap:12px;align-items:center;margin-bottom:10px}
.lp-wa-ico{flex:none;width:44px;height:44px;border-radius:14px;background:#e9fbef;color:#1aa952;display:grid;place-items:center;font-size:1.5rem}
#lpAccesosTexto{font-size:.9rem;background:#f7faf8}
@media (max-width:576px){.lp-acc .lp-wa-btn .tx{display:none}.lp-acc .lp-wa-btn{padding:0 .35rem}}
</style>
@endpush

@section('content')

@include('layouts.partials.alert')
<div class="container-fluid px-4 pt-3">@include('layouts.partials.limite-plan', ['que' => 'usuarios'])</div>

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Usuarios</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Usuarios</li>
    </ol>

    @php $comp = session('compartir'); @endphp
    <div id="lpAccesos" class="lp-wa-card mb-4" @if (!$comp) hidden @endif>
        <div class="lp-wa-card-top">
            <span class="lp-wa-ico"><i class="fa-brands fa-whatsapp"></i></span>
            <div>
                <div class="fw-bold">Accesos de <span id="lpAccesosNombre">{{ $comp['nombre'] ?? '' }}</span> listos</div>
                <div class="small text-muted">Si WhatsApp no se abrió, toque <b>Enviar</b>. La clave nueva solo se muestra esta vez.</div>
            </div>
        </div>
        <textarea class="form-control" rows="8" readonly id="lpAccesosTexto">{{ $comp['mensaje'] ?? '' }}</textarea>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <a href="{{ $comp['url'] ?? '#' }}" id="lpAccesosEnviar" target="_blank" rel="noopener" class="lp-wa-btn"><span class="ic"><i class="fa-brands fa-whatsapp"></i></span> Enviar</a>
            <button type="button" class="btn btn-light border fw-bold" id="lpAccesosCopiar"><i class="fa-regular fa-copy me-1"></i> Copiar mensaje</button>
        </div>
    </div>

    @can('crear-user')
    <div class="mb-4">
        <a href="{{route('users.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo usuario</button>
        </a>
    </div>
    @endcan



    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla de usuarios
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $item)
                    <tr>
                        <td>{{$item->name}}</td>
                        <td>{{$item->email}}</td>
                        <td>
                            @php $rol = $item->getRoleNames()->first(); @endphp
                            {{ \App\Support\RolesNegocio::etiqueta($item) }}
                            @if ($rol === 'vendedor' && !$item->es_superadmin && ($extra = $item->getDirectPermissions()->count()))
                            <span class="badge bg-info text-dark ms-1" title="Permisos extra dados por el dueño">+{{ $extra }} permisos</span>
                            @endif
                        </td>
                        <td>
                            <div class="lp-acc">
                                @can('editar-user')
                                <a href="{{ route('users.edit', ['user' => $item]) }}" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                @if ($rol === 'vendedor' && !$item->es_superadmin)
                                <a href="{{ route('users.permisos', ['user' => $item]) }}" title="Permisos del vendedor"><i class="fa-solid fa-key"></i></a>
                                @endif
                                @if ((int) $item->id !== (int) auth()->id() && !$item->es_superadmin)
                                @if ($item->telefono)
                                <form method="post" action="{{ route('users.compartir', ['user' => $item]) }}" class="lp-wa-form">
                                    @csrf
                                    <button type="submit" class="lp-wa-btn" title="Le pone una clave nueva y le envía los accesos al {{ $item->telefono }}"><span class="ic"><i class="fa-brands fa-whatsapp"></i></span><span class="tx">Compartir accesos por WhatsApp</span></button>
                                </form>
                                @else
                                <div class="lp-wa-wrap"><button type="button" class="lp-wa-btn" title="Compartir accesos por WhatsApp" data-bs-toggle="modal" data-bs-target="#compartirModal-{{$item->id}}"><span class="ic"><i class="fa-brands fa-whatsapp"></i></span><span class="tx">Compartir accesos por WhatsApp</span></button></div>
                                @endif
                                @endif
                                @endcan
                                @can('eliminar-user')
                                @if ((int) $item->id !== (int) auth()->id() && !$item->es_superadmin)
                                <button type="button" class="del" title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"><i class="fa-regular fa-trash-can"></i></button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>

                    @can('editar-user')
                    @if ((int) $item->id !== (int) auth()->id() && !$item->es_superadmin && !$item->telefono)
                    <div class="modal fade" id="compartirModal-{{$item->id}}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content lp-wa-form" action="{{ route('users.compartir', ['user' => $item]) }}" method="post">
                                @csrf
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5"><i class="fa-brands fa-whatsapp text-success me-1"></i> Compartir accesos a {{ $item->name }}</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Se le pone una <b>clave nueva</b> y se arma el mensaje con su correo, la clave y el link para descargar la app.</p>
                                    <label class="form-label fw-bold">WhatsApp del trabajador</label>
                                    <input type="tel" name="telefono" class="form-control mb-1" placeholder="Ej: 300 123 4567" inputmode="tel" autocomplete="off">
                                    <div class="form-text mb-3">Se le queda guardado para la próxima vez. Si lo deja vacío, WhatsApp le deja escoger el contacto.</div>
                                    <label class="form-label fw-bold">Clave nueva <span class="fw-normal text-muted">(opcional)</span></label>
                                    <input type="text" name="clave" class="form-control mb-1" minlength="8" maxlength="60" placeholder="Déjela vacía y se crea una sola" autocomplete="off">
                                    <div class="form-text">La clave que tenía deja de funcionar.</div>
                                    <div class="text-danger small fw-bold mt-2 lp-wa-error"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="lp-wa-btn"><span class="ic"><i class="fa-brands fa-whatsapp"></i></span> Enviar por WhatsApp</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                    @endcan

                    <!-- Modal de confirmación-->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Seguro que quieres eliminar el usuario? Ya no podrá entrar. Si tiene ventas, quedan a su nombre como "ex empleado" para los reportes.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('users.destroy',['user'=>$item->id]) }}" method="post">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}?v=4"></script>
<script>
(function () {
    var card = document.getElementById('lpAccesos');
    function mostrar(d) {
        document.getElementById('lpAccesosNombre').textContent = d.nombre;
        document.getElementById('lpAccesosTexto').value = d.mensaje;
        document.getElementById('lpAccesosEnviar').href = d.url;
        card.hidden = false;
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    document.getElementById('lpAccesosCopiar').addEventListener('click', function () {
        var t = document.getElementById('lpAccesosTexto'), b = this;
        var ok = function () { b.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copiado'; };
        if (navigator.clipboard) navigator.clipboard.writeText(t.value).then(ok, function () { t.select(); document.execCommand('copy'); ok(); });
        else { t.select(); document.execCommand('copy'); ok(); }
    });
    // Compartir: se abre la ventana de una vez (si no, el celular la bloquea) y se manda a WhatsApp cuando llega el mensaje
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.classList || !f.classList.contains('lp-wa-form') || !window.fetch) return;
        e.preventDefault();
        var btn = f.querySelector('[type=submit]'), err = f.querySelector('.lp-wa-error');
        if (btn.disabled) return;
        btn.disabled = true; if (err) err.textContent = '';
        var w = window.open('', '_blank');
        if (w) { try { w.document.write('<p style="font-family:sans-serif;padding:24px">Abriendo WhatsApp…</p>'); } catch (x) {} }
        fetch(f.action, { method: 'POST', body: new FormData(f), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (d) { if (!r.ok) throw d; return d; }); })
            .then(function (d) {
                if (w) w.location.href = d.url; else location.href = d.url;
                var m = f.closest('.modal');
                if (m && window.bootstrap) bootstrap.Modal.getOrCreateInstance(m).hide();
                mostrar(d);
            })
            .catch(function (d) {
                if (w) w.close();
                var msg = (d && d.errors) ? Object.values(d.errors)[0][0] : ((d && d.message) || 'No se pudo crear el mensaje. Intente de nuevo.');
                if (err) err.textContent = msg; else { card.hidden = false; document.getElementById('lpAccesosNombre').textContent = '—'; document.getElementById('lpAccesosTexto').value = msg; }
            })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
@endpush