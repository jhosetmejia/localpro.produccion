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
                            {{ $item->es_superadmin ? 'Administrador LocalPro' : (\App\Http\Controllers\userController::ROLES_TIENDA[$rol] ?? $rol) }}
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
                                @endcan
                                @can('eliminar-user')
                                @if ((int) $item->id !== (int) auth()->id() && !$item->es_superadmin)
                                <button type="button" class="del" title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"><i class="fa-regular fa-trash-can"></i></button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>

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