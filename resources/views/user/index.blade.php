@extends('layouts.app')

@section('title','usuarios')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" type="text/css">
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

    @if ($comp = session('compartir'))
    <div class="card border-success mb-4" id="lpAccesos">
        <div class="card-body">
            <h5 class="fw-bold mb-1"><i class="fa-brands fa-whatsapp text-success me-1"></i> Accesos de {{ $comp['nombre'] }} listos</h5>
            <p class="small text-muted mb-2">Toque el botón para enviárselos por WhatsApp. La clave nueva solo se muestra esta vez.</p>
            <textarea class="form-control mb-2" rows="9" readonly id="lpAccesosTexto">{{ $comp['mensaje'] }}</textarea>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $comp['url'] }}" target="_blank" rel="noopener" class="btn btn-success fw-bold"><i class="fa-brands fa-whatsapp me-1"></i> Enviar por WhatsApp</a>
                <button type="button" class="btn btn-outline-secondary" onclick="var t=document.getElementById('lpAccesosTexto');(navigator.clipboard?navigator.clipboard.writeText(t.value):Promise.reject()).catch(function(){t.select();document.execCommand('copy')});this.textContent='Copiado ✓'"><i class="fa-regular fa-copy me-1"></i> Copiar mensaje</button>
            </div>
        </div>
    </div>
    @endif

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
                                <button type="button" class="btn btn-sm btn-success fw-bold text-nowrap" style="width:auto;padding:0 .7rem;background:#25d366;border-color:#25d366;color:#fff;font-size:.85rem" title="Compartir accesos por WhatsApp" data-bs-toggle="modal" data-bs-target="#compartirModal-{{$item->id}}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir accesos por WhatsApp</button>
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
                    @if ((int) $item->id !== (int) auth()->id() && !$item->es_superadmin)
                    <div class="modal fade" id="compartirModal-{{$item->id}}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <form class="modal-content" action="{{ route('users.compartir', ['user' => $item]) }}" method="post">
                                @csrf
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5"><i class="fa-brands fa-whatsapp text-success me-1"></i> Compartir accesos a {{ $item->name }}</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Se le pone una <b>clave nueva</b> y se arma el mensaje con su correo, la clave y el link para descargar la app.</p>
                                    <label class="form-label fw-bold">WhatsApp del trabajador</label>
                                    <input type="tel" name="telefono" class="form-control mb-1" value="{{ $item->telefono }}" placeholder="Ej: 3001234567" inputmode="tel" autocomplete="off">
                                    <div class="form-text mb-3">Si lo deja vacío, WhatsApp le deja escoger el contacto.</div>
                                    <label class="form-label fw-bold">Clave nueva <span class="fw-normal text-muted">(opcional)</span></label>
                                    <input type="text" name="clave" class="form-control mb-1" minlength="8" maxlength="60" placeholder="Déjela vacía y se crea una sola" autocomplete="off">
                                    <div class="form-text">La clave que tenía deja de funcionar.</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success fw-bold">Crear mensaje</button>
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
                                    ¿Seguro que quieres eliminar el usuario?
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
@endpush