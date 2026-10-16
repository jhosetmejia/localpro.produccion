@extends('layouts.app')

@section('title','Editar Producto')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Producto</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productos.index')}}">Productos</a></li>
        <li class="breadcrumb-item active">Editar producto</li>
    </ol>

    <div class="card text-bg-light">
        <form action="{{route('productos.update',['producto'=>$producto])}}" method="post" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            <div class="card-body">

                <div class="row g-4">
                    <!----Codigo---->
                    <div class="col-md-6">
                        <label for="codigo" class="form-label">Código:</label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{old('codigo',$producto->codigo)}}">
                        @error('codigo')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Nombre---->
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="{{old('nombre',$producto->nombre)}}">
                        @error('nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Descripción---->
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control">{{old('descripcion',$producto->descripcion)}}</textarea>
                        @error('descripcion')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Precio de venta---->
                    <div class="col-md-6">
                        <label for="precio_venta" class="form-label">Precio de venta (COP):</label>
                        <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_venta" id="precio_venta" class="form-control" value="{{old('precio_venta',(float) $producto->precio_venta)}}" placeholder="Ej: {{ moneda()['decimales'] ? '29.90' : '59900' }}">
                        <small class="text-muted">Las tallas y colores pueden tener otro precio si lo necesita.</small>
                        @error('precio_venta')
                        <small class="text-danger d-block">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    @include('producto._impuesto', ['col' => 'col-md-6'])
                    @if (\App\Support\TiendaActual::tienda()?->mod_medida)
                    <div class="col-md-6">
                        <label for="unidad" class="form-label">Se vende por</label>
                        <select name="unidad" id="unidad" class="form-select">
                            @foreach (\App\Models\Producto::UNIDADES as $k => $u)<option value="{{ $k }}" @selected(old('unidad', $producto->unidad ?: 'und') === $k)>{{ $u[0] }}{{ $k !== 'und' ? ' (' . $u[1] . ')' : '' }}</option>@endforeach
                        </select>
                        <small class="text-muted">El precio de venta es por cada unidad de medida.</small>
                    </div>
                    @endif

                    <!---Costo---->
                    <div class="col-md-6">
                        <label for="precio_costo" class="form-label">Precio de costo <span class="text-muted fw-normal">(lo que le cuesta a usted)</span>:</label>
                        <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_costo" id="precio_costo" class="form-control" value="{{ old('precio_costo', (float) $producto->precio_costo ?: '') }}" placeholder="Opcional, para calcular su ganancia">
                        @error('precio_costo')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Controla inventario---->
                    <div class="col-md-6 d-flex align-items-end">
                        <input type="hidden" name="controla_inventario" value="0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="controla_inventario" value="1" id="controla_inventario" @checked(old('controla_inventario', $producto->controla_inventario))>
                            <label class="form-check-label" for="controla_inventario">Controlar inventario <small class="text-muted d-block">Apáguelo en comidas preparadas o servicios: se vende sin límite de cantidad.</small></label>
                        </div>
                    </div>

                    <!---Imagen---->
                    <div class="col-md-6">
                        <label for="img_path" class="form-label">Imagen:</label>
                        <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*" data-foto-lista>
                        <script src="{{ asset('js/foto-lista.js') }}?v=2"></script>
                        @error('img_path')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Categorías---->
                    @include('producto._categorias', ['seleccionadas' => $producto->categorias->pluck('id')->all(), 'para' => $producto->para])
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button type="reset" class="btn btn-secondary">Reiniciar</button>
            </div>
        </form>
    </div>



</div>
@endsection

@push('js')
<script>
// Avisa antes de salir si hay cambios sin guardar en el producto
(function () {
    var f = document.querySelector('form[enctype]') || document.querySelector('main form');
    if (!f) return;
    var cambios = false;
    f.addEventListener('input', function () { cambios = true; });
    f.addEventListener('change', function () { cambios = true; });
    f.addEventListener('submit', function () { cambios = false; });
    window.addEventListener('beforeunload', function (e) { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush

