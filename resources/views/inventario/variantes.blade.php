@extends('layouts.app')

@section('title', 'Tallas y colores')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><a href="{{ route('productos.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Productos</a></small>
            <h1>{{ $producto->nombre }}</h1>
            <span class="text-muted fw-semibold">Código {{ $producto->codigo }} · Precio base @pesos($producto->precio_venta)</span>
        </div>
        <div class="lp-kpi" style="padding:.8rem 1.2rem">
            <div class="lbl">Unidades en total</div>
            <div class="val" style="font-size:1.8rem">{{ $producto->variantes->where('estado', 1)->sum('stock') }}</div>
        </div>
    </div>

    @include('layouts.partials.lp-flash')

    @if (\App\Support\TiendaActual::tienda()?->usa_tallas || $producto->variantes->isEmpty())
    @can('editar-producto')
    <div class="lp-box mb-3">
        <h3>{{ \App\Support\TiendaActual::tienda()?->usa_tallas ? 'Agregar tallas y colores' : 'Cantidad disponible' }}</h3>
        <form method="post" action="{{ route('variantes.store', $producto) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Tallas <span class="text-muted fw-normal">(separadas por coma)</span></label>
                <input name="tallas" class="form-control" placeholder="S, M, L, XL   ó   28, 30, 32" value="{{ old('tallas') }}">
                <div class="d-flex gap-1 mt-2 flex-wrap">
                    @foreach (['XS, S, M, L, XL', '6, 8, 10, 12, 14', '28, 30, 32, 34, 36', 'ÚNICA'] as $preset)
                    <button type="button" class="btn btn-sm btn-info py-0 px-2" onclick="this.form.tallas.value='{{ $preset }}'">{{ $preset }}</button>
                    @endforeach
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Colores <span class="text-muted fw-normal">(separados por coma)</span></label>
                <input name="colores" class="form-control" placeholder="Negro, Blanco, Azul" value="{{ old('colores') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad de cada una</label>
                <input name="stock" type="number" min="0" class="form-control" value="{{ old('stock', 0) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Precio <span class="text-muted fw-normal">(opcional)</span></label>
                <input name="precio" type="number" min="0" step="{{ paso_dinero() }}" class="form-control" placeholder="Igual al base">
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-primary"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="col-12"><small class="text-muted">Se crea cada combinación. Ej: 3 tallas × 2 colores = 6 referencias, cada una con su cantidad.</small></div>
        </form>
    </div>
    @endcan
    @endif

    <div class="lp-box">
        <h3>Inventario por talla y color <span><a href="{{ route('etiquetas.producto', $producto) }}" class="me-3"><i class="fa-solid fa-barcode me-1"></i>Imprimir etiquetas</a><a href="{{ route('inventario.movimientos') }}">Ver movimientos</a></span></h3>
        @if ($producto->variantes->isEmpty())
            <div class="lp-empty"><i class="fa-solid fa-shirt fa-2x mb-2 d-block" style="color:#bfeccb"></i>Este producto aún no tiene tallas ni colores. Agréguelos arriba para poder venderlo en el POS.</div>
        @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Talla</th><th>Color</th><th>Stock</th><th>Precio</th><th>Código de barras</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach ($producto->variantes as $v)
                    <tr class="{{ $v->estado ? '' : 'opacity-50' }}">
                        <td class="fw-bold">{{ $v->talla ?? '—' }}</td>
                        <td>{{ $v->color ?? '—' }}</td>
                        <td><span class="badge {{ $v->stock <= 0 ? 'bg-danger' : ($v->stock <= 5 ? 'bg-warning text-dark' : 'bg-success') }} fs-6">{{ $v->stock }}</span></td>
                        <td>@pesos($v->precio_final) @if($v->precio === null)<small class="text-muted">(base)</small>@endif</td>
                        <td><code>{{ $v->codigo_barras ?? '—' }}</code></td>
                        <td class="text-end text-nowrap">
                            @can('ajustar-inventario')
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#aj{{ $v->id }}"><i class="fa-solid fa-boxes-stacked me-1"></i>Stock</button>
                            @endcan
                            @can('editar-producto')
                            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#ed{{ $v->id }}"><i class="fa-solid fa-pen"></i></button>
                            <form method="post" action="{{ route('variantes.destroy', $v) }}" class="d-inline" onsubmit="return confirm('¿Quitar {{ $v->etiqueta }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>

                @endforeach
                </tbody>
            </table>
        </div>
        @foreach ($producto->variantes as $v)
                    @can('ajustar-inventario')
                    <div class="modal fade" id="aj{{ $v->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('variantes.ajustar', $v) }}" style="border-radius:22px">
                            @csrf
                            <div class="modal-header border-0"><h5 class="modal-title fw-bold">{{ $producto->nombre }} · {{ $v->etiqueta }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body pt-0">
                                <p class="text-muted">Stock actual: <b>{{ $v->stock }}</b> unidades</p>
                                <label class="form-label">¿Qué pasó?</label>
                                <select name="tipo" class="form-select mb-3">
                                    <option value="entrada">Llegó mercancía (sumar)</option>
                                    <option value="salida">Salida por daño, pérdida o regalo (restar)</option>
                                    <option value="ajuste">Conteo físico (dejar cantidad exacta)</option>
                                </select>
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="cantidad" min="0" class="form-control mb-3" required>
                                <label class="form-label">Nota (opcional)</label>
                                <input name="nota" class="form-control" maxlength="255" placeholder="Ej: Pedido proveedor #123">
                            </div>
                            <div class="modal-footer border-0"><button class="btn btn-primary w-100">Guardar</button></div>
                        </form></div>
                    </div>
                    @endcan
                    @can('editar-producto')
                    <div class="modal fade" id="ed{{ $v->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="{{ route('variantes.update', $v) }}" style="border-radius:22px">
                            @csrf @method('PUT')
                            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Editar {{ $v->etiqueta }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body pt-0">
                                <label class="form-label">Precio propio (vacío = precio base)</label>
                                <input type="number" name="precio" min="0" step="{{ paso_dinero() }}" class="form-control mb-3" value="{{ $v->precio !== null ? (float) $v->precio : '' }}">
                                <label class="form-label">Costo propio (vacío = costo del producto)</label>
                                <input type="number" name="costo" min="0" step="{{ paso_dinero() }}" class="form-control mb-3" value="{{ $v->costo !== null ? (float) $v->costo : '' }}">
                                <label class="form-label">Código de barras</label>
                                <input name="codigo_barras" class="form-control mb-3" value="{{ $v->codigo_barras }}" placeholder="Escanee o escriba">
                                <input type="hidden" name="estado" value="0">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="estado" value="1" id="es{{ $v->id }}" {{ $v->estado ? 'checked' : '' }}><label class="form-check-label" for="es{{ $v->id }}">Activa para vender</label></div>
                            </div>
                            <div class="modal-footer border-0"><button class="btn btn-primary w-100">Guardar</button></div>
                        </form></div>
                    </div>
                    @endcan
        @endforeach
        @endif
    </div>
</div>
@endsection
