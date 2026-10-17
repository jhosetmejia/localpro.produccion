@extends('layouts.app')

@section('title','clientes')

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
    <h1 class="mt-4 text-center">Clientes</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Clientes</li>
    </ol>

    @can('crear-cliente')
    <div class="mb-4 d-flex flex-wrap gap-2 align-items-stretch">
        <a href="{{ route('clientes.create') }}" class="btn btn-primary d-inline-flex align-items-center"><i class="fa-solid fa-user-plus me-2"></i>Añadir un cliente</a>
        <a href="{{ route('clientes.importar') }}" class="btn btn-dark d-inline-flex align-items-center"><i class="fa-solid fa-file-excel me-2" style="color:#a6f34a"></i>Añadir base de datos de clientes</a>
        <a href="{{ route('vender.index') }}" class="btn btn-success d-inline-flex align-items-center"><i class="fa-solid fa-rocket me-2"></i>Vender más</a>
        <a href="{{ route('clientes.contactos') }}" class="btn btn-light d-inline-flex align-items-center" style="border:1.5px solid #bfeccb"><i class="fa-solid fa-mobile-screen me-2" style="color:#16b34a"></i>Crear base de datos (contactos del celular)</a>
    </div>
    <div class="mb-4 d-flex gap-3 align-items-center flex-wrap" style="background:linear-gradient(180deg,#f4fcf6,#fff);border:1.5px solid #bfeccb;border-radius:20px;padding:1rem 1.2rem">
        <div style="width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c;font-size:1.2rem;flex:none"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
        <div style="flex:1 1 260px;min-width:0"><b>¿Ya tiene sus clientes en Excel?</b><div class="text-muted small fw-semibold">Suba su archivo y no tenga que volver a crearlos. Sirve cualquier Excel con datos de personas: LocalPro busca solo el nombre, la cédula, el celular, el correo y la dirección, y los convierte en clientes en un instante.</div></div>
        <a href="{{ route('clientes.importar') }}" class="btn btn-primary"><i class="fa-solid fa-upload me-1"></i>Subir mi Excel</a>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla clientes
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
                        <th>Contacto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $item)
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
                            @if ($item->contactado_at)
                            <span class="badge rounded-pill {{ $item->contacto_estado === 'enviado' ? 'text-bg-success' : 'text-bg-warning' }}">{{ ['enviado' => '✓ Contactado', 'numero_malo' => 'Número malo', 'no_interesa' => 'No le interesa'][$item->contacto_estado] ?? '✓ Contactado' }}</span>
                            <div class="text-muted small">{{ $item->contactado_at->format('d/m/Y') }}@if ($item->contactadoPor) · {{ $item->contactadoPor->name }}@endif</div>
                            @else
                            <span class="badge rounded-pill text-bg-light border">Sin contactar</span>
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
                                <a href="{{ route('clientes.show', ['cliente' => $item]) }}" title="Ver compras"><i class="fa-solid fa-bag-shopping"></i></a>
                                @can('editar-cliente')
                                <a href="{{ route('clientes.edit', ['cliente' => $item]) }}" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                @endcan
                                @can('eliminar-cliente')
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
                                    {{ $item->persona->estado == 1 ? '¿Seguro que quieres eliminar el cliente?' : '¿Seguro que quieres restaurar el cliente?' }}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('clientes.destroy',['cliente'=>$item->persona->id]) }}" method="post">
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
<script src="{{ asset('js/datatables-simple-demo.js') }}?v=3"></script>
@endpush