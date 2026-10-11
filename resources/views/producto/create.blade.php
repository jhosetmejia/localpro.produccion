@extends('layouts.app')

@section('title','Crear Producto')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Producto</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productos.index')}}">Productos</a></li>
        <li class="breadcrumb-item active">Crear producto</li>
    </ol>

    <div class="card">
        <form action="{{ route('productos.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <!----Codigo---->
                    <div class="col-md-6">
                        <label for="codigo" class="form-label">Código <span class="text-muted fw-normal">(vacío = automático)</span>:</label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{old('codigo')}}">
                        @error('codigo')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Nombre---->
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="{{old('nombre')}}">
                        @error('nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Descripción---->
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control">{{old('descripcion')}}</textarea>
                        @error('descripcion')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Precio de venta---->
                    <div class="col-md-6">
                        <label for="precio_venta" class="form-label">Precio de venta (COP):</label>
                        <input type="number" min="0" step="{{ paso_dinero() }}" name="precio_venta" id="precio_venta" class="form-control" value="{{old('precio_venta')}}" placeholder="Ej: {{ moneda()['decimales'] ? '29.90' : '59900' }}">
                        <small class="text-muted">Las tallas y colores pueden tener otro precio si lo necesita.</small>
                        @error('precio_venta')
                        <small class="text-danger d-block">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    @unless (\App\Support\TiendaActual::tienda()?->usa_tallas)
                    <!---Cantidad inicial---->
                    <div class="col-md-6">
                        <label for="cantidad_inicial" class="form-label">Cantidad disponible:</label>
                        <input type="number" min="0" name="cantidad_inicial" id="cantidad_inicial" class="form-control" value="{{ old('cantidad_inicial', 0) }}">
                        @error('cantidad_inicial')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    @endunless

                    <!---Costo---->
                    <div class="col-md-6">
                        <label for="precio_costo" class="form-label">Precio de costo <span class="text-muted fw-normal">(lo que le cuesta a usted)</span>:</label>
                        <input type="number" min="0" step="{{ paso_dinero() }}" name="precio_costo" id="precio_costo" class="form-control" value="{{ old('precio_costo') }}" placeholder="Opcional, para calcular su ganancia">
                        @error('precio_costo')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Controla inventario---->
                    <div class="col-md-6 d-flex align-items-end">
                        <input type="hidden" name="controla_inventario" value="0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="controla_inventario" value="1" id="controla_inventario" @checked(old('controla_inventario', !in_array(\App\Support\TiendaActual::tienda()?->tipo_negocio, ['restaurante', 'cafeteria'])))>
                            <label class="form-check-label" for="controla_inventario">Controlar inventario <small class="text-muted d-block">Apáguelo en comidas preparadas o servicios: se vende sin límite de cantidad.</small></label>
                        </div>
                    </div>

                    <!---Imagen---->
                    <div class="col-md-6">
                        <label for="img_path" class="form-label">Imagen:</label>
                        <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*">
                        @error('img_path')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Categorías---->
                    <div class="col-md-6">
                        <label for="categorias" class="form-label">Categorías:</label>
                        <select data-size="4" title="Seleccione las categorías" data-live-search="true" name="categorias[]" id="categorias" class="form-control selectpicker show-tick" multiple>
                            @foreach ($categorias as $item)
                            <option value="{{$item->id}}" {{ (in_array($item->id , old('categorias',[]))) ? 'selected' : '' }}>{{$item->nombre}}</option>
                            @endforeach
                        </select>
                        @error('categorias')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                </div>
            </div>
            
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>


</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
@endpush