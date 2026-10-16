@extends('layouts.app')

@section('title','Editar producto')

@push('css')
<style>
    .pe-sec{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem 1.1rem;margin-bottom:12px}
    .pe-sec h3{font-size:1rem;font-weight:800;margin:0 0 .7rem;display:flex;align-items:center;gap:.5rem;justify-content:space-between}
    .pe-sec h3 a{font-size:.8rem;font-weight:700}
    .pe-sec .form-label{margin-bottom:.25rem;font-size:.85rem}
    .pe-foto{display:flex;gap:12px;align-items:center}
    .pe-foto .ph{width:84px;height:84px;border-radius:14px;background:#fff;border:1.5px solid var(--lp-line);display:grid;place-items:center;overflow:hidden;flex:none;color:#bfeccb;font-size:1.6rem}
    .pe-foto .ph img{width:100%;height:100%;object-fit:contain}
    .pe-stock{border:1.5px solid var(--lp-line);border-radius:14px;overflow:auto;max-height:260px}
    .pe-fila{display:flex;align-items:center;gap:10px;padding:.4rem .7rem;border-bottom:1px solid var(--lp-line)}
    .pe-fila:last-child{border-bottom:0}
    .pe-fila b{flex:1;font-size:.9rem}
    .pe-fila input{width:90px;text-align:center;font-weight:800}
    .pe-fila input.cambio{border-color:#16b34a;background:#ecfbf0}
    .pe-tot{display:flex;justify-content:space-between;font-weight:800;font-size:.85rem;margin-top:.5rem;color:var(--lp-muted)}
    .pe-guardar{position:sticky;bottom:10px;z-index:5;display:flex;gap:8px}
</style>
@endpush

@section('content')
@php
    $variantes = $producto->variantes()->where('estado', 1)->orderBy('talla')->orderBy('color')->get();
    $soloUna = $variantes->count() === 1 && !$variantes[0]->talla && !$variantes[0]->color;
    $puedeStock = auth()->user()?->can('ajustar-inventario');
@endphp
<div class="lp-dash" style="max-width:1200px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('productos.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Productos</a></small><h1>Editar <span>producto</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form action="{{ route('productos.update', ['producto' => $producto]) }}" method="post" enctype="multipart/form-data" id="pe">
        @method('PATCH')
        @csrf
        <div class="row g-3">
            {{-- Izquierda: datos y precios --}}
            <div class="col-lg-7">
                <div class="pe-sec">
                    <h3>Datos y precio</h3>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control fw-bold" value="{{ old('nombre', $producto->nombre) }}" maxlength="80" required>
                            @error('nombre')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-12 mt-0">@include('producto._seo')</div>
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo', $producto->codigo) }}" maxlength="50">
                            @error('codigo')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="precio_venta" class="form-label">Precio de venta ({{ moneda()['moneda'] }})</label>
                            <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_venta" id="precio_venta" class="form-control fw-bold" value="{{ old('precio_venta', (float) $producto->precio_venta) }}" required>
                            @error('precio_venta')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="precio_costo" class="form-label">Precio de costo <span class="text-muted fw-normal">(lo que le costó)</span></label>
                            <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_costo" id="precio_costo" class="form-control" value="{{ old('precio_costo', (float) $producto->precio_costo ?: '') }}" placeholder="Para calcular su ganancia">
                            @error('precio_costo')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        @include('producto._promo', ['col' => 'col-12'])
                        @include('producto._impuesto', ['col' => 'col-md-6'])
                        @if (\App\Support\TiendaActual::tienda()?->mod_medida)
                        <div class="col-md-6">
                            <label for="unidad" class="form-label">Se vende por</label>
                            <select name="unidad" id="unidad" class="form-select">
                                @foreach (\App\Models\Producto::UNIDADES as $k => $u)<option value="{{ $k }}" @selected(old('unidad', $producto->unidad ?: 'und') === $k)>{{ $u[0] }}{{ $k !== 'und' ? ' (' . $u[1] . ')' : '' }}</option>@endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción <span class="text-muted fw-normal">(sale en el catálogo)</span></label>
                            <textarea name="descripcion" id="descripcion" rows="2" class="form-control" maxlength="255" style="resize:none" placeholder="Material, detalles…">{{ old('descripcion', $producto->descripcion) }}</textarea>
                            @error('descripcion')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>

                <div class="pe-sec">
                    <h3>Categoría</h3>
                    <div class="row">
                        @include('producto._categorias', ['seleccionadas' => $producto->categorias->pluck('id')->all(), 'para' => $producto->para])
                    </div>
                </div>
            </div>

            {{-- Derecha: foto y cantidades --}}
            <div class="col-lg-5">
                <div class="pe-sec">
                    <h3>Foto</h3>
                    <div class="pe-foto">
                        <span class="ph" id="pePrev">@if ($producto->img_path)<img src="{{ asset('storage/productos/' . rawurlencode($producto->img_path)) }}" alt="">@else<i class="fa-regular fa-image"></i>@endif</span>
                        <div class="flex-grow-1" style="min-width:0">
                            <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*" data-foto-lista>
                            <script src="{{ asset('js/foto-lista.js') }}?v=2"></script>
                            <small class="text-muted">{{ $producto->img_path ? 'Suba otra solo si la quiere cambiar.' : 'Así la ven sus clientes en el catálogo.' }}</small>
                            @error('img_path')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>

                <div class="pe-sec">
                    <h3>{{ $soloUna ? 'Cantidad disponible' : 'Cantidad por talla y color' }}
                        @can('editar-producto')<a href="{{ route('productos.variantes', $producto) }}"><i class="fa-solid fa-plus me-1"></i>{{ \App\Support\TiendaActual::tienda()?->usa_tallas ? 'Agregar tallas / colores' : 'Más opciones' }}</a>@endcan
                    </h3>
                    @if ($variantes->isEmpty())
                        <p class="text-muted small mb-0">Este producto aún no tiene cantidades. <a href="{{ route('productos.variantes', $producto) }}">Agréguelas aquí</a>.</p>
                    @elseif (!$producto->controla_inventario)
                        <p class="text-muted small mb-0">Este producto se vende sin límite de cantidad (no controla inventario).</p>
                    @else
                        <div class="pe-stock" id="peStock">
                            @foreach ($variantes as $v)
                            <div class="pe-fila">
                                <b>{{ $soloUna ? 'Unidades' : $v->etiqueta }}</b>
                                <input type="hidden" name="stock_antes[{{ $v->id }}]" value="{{ (float) $v->stock }}">
                                <input type="number" min="0" step="any" class="form-control form-control-sm" name="stock[{{ $v->id }}]" value="{{ old('stock.' . $v->id, (float) $v->stock) }}" data-antes="{{ (float) $v->stock }}" inputmode="numeric" @disabled(!$puedeStock)>
                            </div>
                            @endforeach
                        </div>
                        <div class="pe-tot"><span>{{ $puedeStock ? 'Escriba la cantidad que hay ahora.' : 'No tiene permiso para cambiar cantidades.' }}</span><span>Total: <span id="peTotal">{{ cantidad($variantes->sum('stock')) }}</span></span></div>
                    @endif
                    <input type="hidden" name="controla_inventario" value="0">
                    <div class="form-check form-switch mt-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="controla_inventario" value="1" id="controla_inventario" @checked(old('controla_inventario', $producto->controla_inventario))>
                        <label class="form-check-label small" for="controla_inventario"><b>Controlar inventario</b> <span class="text-muted d-block">Apáguelo en comidas preparadas o servicios.</span></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="pe-guardar">
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1" style="padding:.85rem"><i class="fa-solid fa-check me-1"></i>Guardar cambios</button>
            <a href="{{ route('productos.index') }}" class="btn btn-light btn-lg" style="padding:.85rem 1.3rem">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var f = document.getElementById('pe');
    // Vista previa de la foto nueva
    var foto = document.getElementById('img_path');
    foto.addEventListener('change', function () {
        var a = this.files[0]; if (!a) return;
        document.getElementById('pePrev').innerHTML = '<img src="' + URL.createObjectURL(a) + '" alt="">';
    });
    // Cantidades: marca las que cambió y suma el total
    var st = document.getElementById('peStock');
    if (st) st.addEventListener('input', function () {
        var t = 0;
        st.querySelectorAll('input[type=number]').forEach(function (i) {
            t += parseFloat(i.value) || 0;
            i.classList.toggle('cambio', String(parseFloat(i.value) || 0) !== String(parseFloat(i.dataset.antes) || 0));
        });
        document.getElementById('peTotal').textContent = Math.round(t * 1000) / 1000;
    });
    // Avisa antes de salir si hay cambios sin guardar
    var cambios = false;
    f.addEventListener('input', function () { cambios = true; });
    f.addEventListener('change', function () { cambios = true; });
    f.addEventListener('submit', function () { cambios = false; });
    window.addEventListener('beforeunload', function (e) { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush
