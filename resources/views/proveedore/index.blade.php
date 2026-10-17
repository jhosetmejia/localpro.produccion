@extends('layouts.app')

@section('title','proveedores')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.3.0/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
@php $negocioNombre = \App\Models\Negocio::datos()->nombre ?? 'nuestro negocio'; @endphp

@include('layouts.partials.alert')


<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Proveedores</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Proveedores</li>
    </ol>

    @can('crear-proveedore')
    <div class="mb-4">
        <a href="{{route('proveedores.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
        </a>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla proveedores
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Documento</th>
                        <th>Tipo de persona</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proveedores as $item)
                    <tr>
                        <td>
                            <b>{{$item->persona->razon_social}}</b>
                            @if ($item->persona->telefono)<div class="text-muted small"><i class="fa-solid fa-mobile-screen-button me-1"></i>{{ $item->persona->telefono }}</div>@endif
                        </td>
                        <td>
                            {{$item->persona->direccion}}
                        </td>
                        <td>
                            <p class="fw-semibold mb-1">{{ $item->persona->documento->tipo_documento ?? "" }}</p>
                            <p class="text-muted mb-0">{{ str_starts_with((string) $item->persona->numero_documento, 'TEL-') ? 'Sin documento' : $item->persona->numero_documento }}</p>
                        </td>
                        <td>
                            {{$item->persona->tipo_persona}}
                        </td>
                        <td>
                            @if ($item->persona->estado == 1)
                            <span class="badge rounded-pill text-bg-success">activo</span>
                            @else
                            <span class="badge rounded-pill text-bg-danger">eliminado</span>
                            @endif
                        </td>
                        <td>
                            <div class="lp-acc">
                                @php $wa = numero_whatsapp($item->persona->telefono); @endphp
                                @if ($wa)
                                <a class="wa" target="_blank" rel="noopener" title="Escribirle por WhatsApp" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hola ' . strtok((string) $item->persona->razon_social, ' ') . ' 👋, le escribimos de ' . $negocioNombre . '.') }}"><i class="fa-brands fa-whatsapp"></i></a>
                                @else
                                <span class="off" title="Sin celular: agréguelo para escribirle"><i class="fa-brands fa-whatsapp"></i></span>
                                @endif
                                @can('editar-proveedore')
                                <a href="{{ route('proveedores.edit', ['proveedore' => $item]) }}" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                @endcan
                                @can('eliminar-proveedore')
                                @if ($item->persona->estado == 1)
                                <button type="button" class="del" title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"><i class="fa-regular fa-trash-can"></i></button>
                                @else
                                <button type="button" title="Restaurar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"><i class="fa-solid fa-rotate-left"></i></button>
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
                                    {{ $item->persona->estado == 1 ? '¿Seguro que quieres eliminar el proveedor?' : '¿Seguro que quieres restaurar el proveedor?' }}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('proveedores.destroy',['proveedore'=>$item->persona->id]) }}" method="post">
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